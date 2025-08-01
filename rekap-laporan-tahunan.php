<?php
// rekap-laporan-tahunan.php
require_once 'config/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fungsi: Dapatkan daftar tahun ajaran
function getTahunAjaranList() {
    $tahun_sekarang = (int)date('Y');
    $semester_sekarang = date('n') < 7 ? -1 : 0; // < Juli → semester lalu
    $awal_tahun_ajaran = $tahun_sekarang + $semester_sekarang;

    $list = [];
    for ($i = 0; $i < 6; $i++) {
        $awal = $awal_tahun_ajaran - $i;
        $akhir = $awal + 1;
        $list[] = [
            'value' => "$awal/$akhir",
            'label' => "$awal/$akhir"
        ];
    }
    return $list;
}

$tahun_ajaran_list = getTahunAjaranList();
$tahun_ajaran = $_GET['tahun_ajaran'] ?? $tahun_ajaran_list[0]['value'];

// Ekstrak tahun
list($awal, $akhir) = explode('/', $tahun_ajaran);
$tanggal_dari = "$awal-07-01";
$tanggal_sampai = "$akhir-06-30";

// Tahun lalu
$prev_awal = $awal - 1;
$prev_akhir = $akhir - 1;
$prev_tanggal_dari = "$prev_awal-07-01";
$prev_tanggal_sampai = "$prev_akhir-06-30";

// Total tahun ini
$stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_donasi), 0) as total FROM infaq WHERE tanggal >= ? AND tanggal <= ?");
$stmt->execute([$tanggal_dari, $tanggal_sampai]);
$total_tahun_ini = (int)$stmt->fetchColumn();

// Total tahun lalu
$stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_donasi), 0) as total FROM infaq WHERE tanggal >= ? AND tanggal <= ?");
$stmt->execute([$prev_tanggal_dari, $prev_tanggal_sampai]);
$total_tahun_lalu = (int)$stmt->fetchColumn();

// Pertumbuhan (%)
$pertumbuhan = $total_tahun_lalu > 0 
    ? (($total_tahun_ini - $total_tahun_lalu) / $total_tahun_lalu) * 100 
    : ($total_tahun_ini > 0 ? 100 : 0);
$pertumbuhan = round($pertumbuhan, 1);

// Daftar bulan (Juli - Juni)
$bulan_list = [
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober',
    11 => 'November', 12 => 'Desember',
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni'
];

// Ambil semua petugas aktif
$stmt = $pdo->query("SELECT id, nama_petugas FROM petugas WHERE is_active = 1 ORDER BY nama_petugas");
$petugas_list = $stmt->fetchAll();

// Query: Rekap infaq per petugas per bulan
$sql = "
    SELECT 
        petugas_id,
        MONTH(tanggal) as bulan,
        SUM(jumlah_donasi) as total
    FROM infaq 
    WHERE tanggal >= ? AND tanggal <= ?
    GROUP BY petugas_id, bulan
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$tanggal_dari, $tanggal_sampai]);
$data_raw = $stmt->fetchAll();

// Konversi ke array: [petugas_id][bulan] = total
$data = [];
foreach ($data_raw as $row) {
    $data[$row['petugas_id']][$row['bulan']] = $row['total'];
}

// Hitung total per petugas
$total_per_petugas = [];
$total_per_bulan = array_fill_keys(array_keys($bulan_list), 0);
$total_keseluruhan = 0;

foreach ($petugas_list as $p) {
    $total = 0;
    foreach ($bulan_list as $bulan => $nama) {
        $nominal = $data[$p['id']][$bulan] ?? 0;
        $total += $nominal;
        $total_per_bulan[$bulan] += $nominal;
    }
    $total_per_petugas[$p['id']] = $total;
    $total_keseluruhan += $total;
}

// Data untuk chart: hanya petugas dengan donasi > 0
$chart_data = [];
foreach ($petugas_list as $p) {
    $total = $total_per_petugas[$p['id']];
    if ($total > 0) {
        $chart_data[] = [
            'nama' => $p['nama_petugas'],
            'total' => $total
        ];
    }
}
usort($chart_data, function($a, $b) { return $b['total'] <=> $a['total']; });
?>

<?php include 'includes/header.php'; ?>

<main class="content">
    <h2>Rekap Laporan Tahunan Kotak Infaq</h2>

    <!-- Filter Tahun Ajaran -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <form method="GET" style="display: flex; gap: 16px; align-items: center;">
            <div class="form-group" style="flex: 1;">
                <label for="tahun_ajaran">Tahun Ajaran</label>
                <select name="tahun_ajaran" id="tahun_ajaran" class="form-control" onchange="this.form.submit()">
                    <?php foreach ($tahun_ajaran_list as $ta): ?>
                        <option value="<?= $ta['value'] ?>" <?= $ta['value'] == $tahun_ajaran ? 'selected' : '' ?>>
                            <?= $ta['label'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-top: 24px;">
                <a href="export_excel-rekap-laporan-tahunan.php?tahun_ajaran=<?= $tahun_ajaran ?>"
                   class="btn btn-primary" style="padding: 8px 16px; font-size: 0.95em;">
                    📥 Export Excel
                </a>
            </div>
        </form>
    </div>

    <!-- Kartu & Chart -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        <!-- KARTU RINGKASAN -->
        <div style="background: hsl(240, 12%, 16%); padding: 20px; border-radius: 12px; border: 1px solid hsl(240, 10%, 30%);">
            <h3 style="color: hsl(0, 0%, 90%); margin: 0 0 16px;">Ringkasan Tahunan</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div style="padding: 12px; background: hsl(120, 40%, 18%); border-radius: 8px; text-align: center;">
                    <div style="font-size: 1em; color: hsl(120, 50%, 70%);">Tahun Ini</div>
                    <div style="font-size: 1.3em; font-weight: bold; color: white; margin-top: 4px;">
                        <?= number_format($total_tahun_ini, 0, ',', '.') ?>
                    </div>
                </div>
                <div style="padding: 12px; background: hsl(210, 30%, 18%); border-radius: 8px; text-align: center;">
                    <div style="font-size: 1em; color: hsl(210, 50%, 70%);">Tahun Lalu</div>
                    <div style="font-size: 1.3em; font-weight: bold; color: white; margin-top: 4px;">
                        <?= number_format($total_tahun_lalu, 0, ',', '.') ?>
                    </div>
                </div>
            </div>
            <div style="margin-top: 16px; padding: 12px; background: <?= $pertumbuhan >= 0 ? 'hsl(150, 50%, 18%)' : 'hsl(360, 60%, 18%)' ?>; border-radius: 8px; text-align: center;">
                <div style="font-size: 1.1em; font-weight: bold; color: white;">
                    <?= $pertumbuhan >= 0 ? '↑' : '↓' ?> <?= abs($pertumbuhan) ?>% dari tahun lalu
                </div>
            </div>
        </div>

        <!-- CHART -->
        <div style="background: hsl(240, 12%, 16%); padding: 20px; border-radius: 12px; border: 1px solid hsl(240, 10%, 30%);">
            <h3 style="color: hsl(0, 0%, 90%); margin: 0 0 16px;">Pendapatan Petugas (<?= $tahun_ajaran ?>)</h3>
            <canvas id="chart-petugas" height="100"></canvas>
        </div>
    </div>

    <!-- Tabel Rekap -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Petugas</th>
                    <?php foreach ($bulan_list as $nama): ?>
                        <th style="text-align: right;"><?= $nama ?></th>
                    <?php endforeach; ?>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; ?>
                <?php foreach ($petugas_list as $p): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= e($p['nama_petugas']) ?></td>
                        <?php $total = 0; ?>
                        <?php foreach ($bulan_list as $bulan => $nama): ?>
                            <?php $nominal = $data[$p['id']][$bulan] ?? 0; ?>
                            <td style="text-align: right;">
                                <?= number_format($nominal, 0, ',', '.') ?>
                            </td>
                            <?php $total += $nominal; ?>
                        <?php endforeach; ?>
                        <td style="text-align: right; font-weight: bold;">
                            <?= number_format($total, 0, ',', '.') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; background: hsl(240, 10%, 15%); color: white;">
                    <td colspan="2" style="text-align: center;">TOTAL</td>
                    <?php foreach ($bulan_list as $bulan => $nama): ?>
                        <td style="text-align: right;">
                            <?= number_format($total_per_bulan[$bulan], 0, ',', '.') ?>
                        </td>
                    <?php endforeach; ?>
                    <td style="text-align: right;"><?= number_format($total_keseluruhan, 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<!-- CDN Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('chart-petugas').getContext('2d');
    const data = {
        labels: <?= json_encode(array_column($chart_data, 'nama')) ?>,
        datasets: [{
            label: 'Total Pemasukan (Rp)',
            data: <?= json_encode(array_column($chart_data, 'total')) ?>,
            backgroundColor: 'hsl(160, 60%, 45%)',
            borderColor: 'hsl(160, 60%, 35%)',
            borderWidth: 1
        }]
    };

    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.parsed.y;
                            return 'Rp ' + value.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    ticks: { color: '#ccc' }
                }
            }
        }
    });
});
</script>