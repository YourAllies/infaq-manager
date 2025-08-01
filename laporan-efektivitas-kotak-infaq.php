<?php
// laporan-efektivitas-kotak-infaq.php
require_once 'config/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fungsi: Daftar tahun ajaran (6 terakhir)
function getTahunAjaranList() {
    $tahun_sekarang = (int)date('Y');
    $semester_sekarang = date('n') < 7 ? -1 : 0;
    $awal = $tahun_sekarang + $semester_sekarang;

    $list = [];
    for ($i = 0; $i < 6; $i++) {
        $a = $awal - $i;
        $b = $a + 1;
        $list[] = ['value' => "$a/$b", 'label' => "$a/$b"];
    }
    return $list;
}

$tahun_ajaran_list = getTahunAjaranList();
$tahun_ajaran = $_GET['tahun_ajaran'] ?? $tahun_ajaran_list[0]['value'];
list($awal, $akhir) = explode('/', $tahun_ajaran);
$tanggal_dari = "$awal-07-01";
$tanggal_sampai = "$akhir-06-30";

// Daftar bulan (Juli - Juni)
$bulan_list = [
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober',
    11 => 'November', 12 => 'Desember',
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni'
];

// Query: Semua data infaq + petugas
$sql = "
    SELECT 
        i.petugas_id,
        p.nama_petugas,
        i.nama_outlet,
        i.jumlah_donasi,
        MONTH(i.tanggal) as bulan
    FROM infaq i
    LEFT JOIN petugas p ON i.petugas_id = p.id
    WHERE i.tanggal >= ? AND i.tanggal <= ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$tanggal_dari, $tanggal_sampai]);
$data = $stmt->fetchAll();

// Hitung statistik umum
$outlets = array_unique(array_column($data, 'nama_outlet'));
$jumlah_kotak_dimiliki = count($outlets);
$jumlah_kotak_terambil = count($data);
$total_dana = array_sum(array_column($data, 'jumlah_donasi'));
$rata_per_pengambilan = $jumlah_kotak_terambil > 0 ? $total_dana / $jumlah_kotak_terambil : 0;
$rata_per_outlet = $jumlah_kotak_dimiliki > 0 ? $total_dana / $jumlah_kotak_dimiliki : 0;
$efektivitas = $rata_per_outlet >= 500000 ? 'Tinggi' : ($rata_per_outlet >= 200000 ? 'Sedang' : 'Rendah');

// Peringkat: Top 5 & Bottom 5 Outlet (dengan petugas)
$per_outlet = [];
foreach ($data as $d) {
    $key = $d['nama_outlet'];
    if (!isset($per_outlet[$key])) {
        $per_outlet[$key] = [
            'nama_outlet' => $key,
            'total_donasi' => 0,
            'petugas' => []
        ];
    }
    $per_outlet[$key]['total_donasi'] += $d['jumlah_donasi'];
    if (!in_array($d['nama_petugas'], $per_outlet[$key]['petugas'])) {
        $per_outlet[$key]['petugas'][] = $d['nama_petugas'];
    }
}

uasort($per_outlet, function($a, $b) { return $b['total_donasi'] <=> $a['total_donasi']; });
$top_5 = array_slice($per_outlet, 0, 5);
$bottom_5 = array_slice($per_outlet, -5, 5);

// Rekap per petugas per bulan
$petugas_data = [];

// Inisialisasi data petugas
$stmt = $pdo->query("SELECT id, nama_petugas FROM petugas WHERE is_active = 1 ORDER BY nama_petugas");
foreach ($stmt as $p) {
    $petugas_data[$p['id']] = [
        'nama_petugas' => $p['nama_petugas'],
        'per_bulan' => array_fill_keys(array_keys($bulan_list), 0),
        'outlets' => []
    ];
}

// Isi data
foreach ($data as $d) {
    $pid = $d['petugas_id'];
    if (!isset($petugas_data[$pid])) continue;

    if (isset($petugas_data[$pid]['per_bulan'][$d['bulan']])) {
        $petugas_data[$pid]['per_bulan'][$d['bulan']]++;
    }

    if (!in_array($d['nama_outlet'], $petugas_data[$pid]['outlets'])) {
        $petugas_data[$pid]['outlets'][] = $d['nama_outlet'];
    }
}
?>

<?php include 'includes/header.php'; ?>

<main class="content">
    <h2>Laporan Efektivitas Kotak Infaq</h2>

    <!-- Filter Tahun Ajaran dan Export -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <form method="GET" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
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
                <a href="export_excel-laporan-efektivitas-kotak-infaq.php?tahun_ajaran=<?= $tahun_ajaran ?>"
                   class="btn btn-primary" style="padding: 8px 16px; font-size: 0.95em;">
                    📥 Export Excel
                </a>
            </div>
        </form>
    </div>

    <!-- Kartu Statistik -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 30px;">
        <div style="background: hsl(200, 30%, 18%); padding: 16px; border-radius: 8px; text-align: center; color: white;">
            <div style="font-size: 0.9em; opacity: 0.9;">Kotak Dimiliki</div>
            <div style="font-size: 1.4em; font-weight: bold; margin-top: 4px;"><?= $jumlah_kotak_dimiliki ?></div>
        </div>
        <div style="background: hsl(160, 40%, 18%); padding: 16px; border-radius: 8px; text-align: center; color: white;">
            <div style="font-size: 0.9em; opacity: 0.9;">Kotak Terambil</div>
            <div style="font-size: 1.4em; font-weight: bold; margin-top: 4px;"><?= $jumlah_kotak_terambil ?></div>
        </div>
        <div style="background: hsl(120, 50%, 18%); padding: 16px; border-radius: 8px; text-align: center; color: white;">
            <div style="font-size: 0.9em; opacity: 0.9;">Dana Terkumpul</div>
            <div style="font-size: 1.3em; font-weight: bold; margin-top: 4px;"><?= number_format($total_dana, 0, ',', '.') ?></div>
        </div>
        <div style="background: hsl(45, 60%, 20%); padding: 16px; border-radius: 8px; text-align: center; color: white;">
            <div style="font-size: 0.9em; opacity: 0.9;">Rata/Kotak</div>
            <div style="font-size: 1.3em; font-weight: bold; margin-top: 4px;"><?= number_format($rata_per_pengambilan, 0, ',', '.') ?></div>
        </div>
        <div style="background: <?= $efektivitas == 'Tinggi' ? 'hsl(150, 50%, 18%)' : ($efektivitas == 'Sedang' ? 'hsl(45, 60%, 18%)' : 'hsl(360, 60%, 18%)') ?>;
              padding: 16px; border-radius: 8px; text-align: center; color: white;">
            <div style="font-size: 0.9em; opacity: 0.9;">Efektivitas</div>
            <div style="font-size: 1.3em; font-weight: bold; margin-top: 4px;"><?= $efektivitas ?></div>
        </div>
    </div>

    <!-- Peringkat Terbaik & Terendah -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        <!-- TOP 5 -->
        <div class="card" style="padding: 16px;">
            <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%);">🏆 Top 5 Outlet Terbaik</h3>
            <table class="table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Outlet</th>
                        <th>Petugas</th>
                        <th style="text-align: right;">Donasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($top_5 as $t): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= e($t['nama_outlet']) ?></td>
                            <td><?= implode(', ', $t['petugas']) ?></td>
                            <td style="text-align: right;"><?= number_format($t['total_donasi'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- BOTTOM 5 -->
        <div class="card" style="padding: 16px;">
            <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%);">📉 Outlet Kurang Efektif</h3>
            <table class="table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Outlet</th>
                        <th>Petugas</th>
                        <th style="text-align: right;">Donasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = $jumlah_kotak_dimiliki - count($bottom_5) + 1;
                    foreach ($bottom_5 as $b): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= e($b['nama_outlet']) ?></td>
                            <td><?= implode(', ', $b['petugas']) ?></td>
                            <td style="text-align: right;"><?= number_format($b['total_donasi'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabel Rekap Petugas (12 Bulan) -->
    <div class="card" style="padding: 16px;">
        <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%);">Rekap Kotak Infaq per Petugas (<?= $tahun_ajaran ?>)</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Petugas</th>
                        <th style="text-align: center;">Kotak Dimiliki</th>
                        <?php foreach ($bulan_list as $nama): ?>
                            <th style="text-align: center; font-size: 0.9em;"><?= $nama ?></th>
                        <?php endforeach; ?>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php foreach ($petugas_data as $id => $p): ?>
                        <?php $total = array_sum($p['per_bulan']); ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= e($p['nama_petugas']) ?></td>
                            <td style="text-align: center;"><?= count($p['outlets']) ?></td>
                            <?php foreach ($bulan_list as $bulan => $nama): ?>
                                <td style="text-align: center;"><?= $p['per_bulan'][$bulan] ?></td>
                            <?php endforeach; ?>
                            <td style="text-align: right; font-weight: bold;"><?= number_format($total, 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($petugas_data)): ?>
                        <tr>
                            <td colspan="<?= 3 + count($bulan_list) + 1 ?>" style="text-align: center; color: hsl(0,0%,60%);">Tidak ada data.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr style="font-weight: bold; background: hsl(240, 10%, 15%); color: white;">
                        <td colspan="2" style="text-align: center;">TOTAL</td>
                        <td style="text-align: center;"></td>
                        <?php foreach ($bulan_list as $bulan => $nama): ?>
                            <td style="text-align: center;">
                                <?php 
                                $sum = 0;
                                foreach ($petugas_data as $p) {
                                    $sum += $p['per_bulan'][$bulan];
                                }
                                echo $sum;
                                ?>
                            </td>
                        <?php endforeach; ?>
                        <td style="text-align: right;"><?= number_format($jumlah_kotak_terambil, 0, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>