<?php
// index.php - Dashboard Utama (versi final + notifikasi, event, PDF)
require_once 'config/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Set locale
setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'indonesian');

function bulan_indo($bulan) {
    $nama = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $nama[$bulan] ?? '';
}

// Cek apakah sudah input hari ini
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM infaq WHERE tanggal = ?");
$stmt->execute([$today]);
$sudah_input = (int)$stmt->fetchColumn() > 0;

// Fungsi: Dapatkan bulan dan tahun sekarang
$bulan_ini = (int)date('m');
$tahun_ini = (int)date('Y');
$tahun_lalu = $tahun_ini - 1;

// Rentang: Bulan ini
$tanggal_dari_bulan_ini = date('Y-m-01');
$tanggal_sampai_bulan_ini = date('Y-m-t');

// Rentang: Bulan lalu
$bulan_lalu = $bulan_ini - 1;
$tahun_bulan_lalu = $tahun_ini;
if ($bulan_lalu == 0) {
    $bulan_lalu = 12;
    $tahun_bulan_lalu = $tahun_ini - 1;
}
$tanggal_dari_bulan_lalu = date("$tahun_bulan_lalu-$bulan_lalu-01");
$tanggal_sampai_bulan_lalu = date("$tahun_bulan_lalu-$bulan_lalu-t");

// --- 1. KARTU UTAMA (5 Kolom) ---
$stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_donasi), 0) FROM infaq WHERE tanggal >= ? AND tanggal <= ?");
$stmt->execute([$tanggal_dari_bulan_ini, $tanggal_sampai_bulan_ini]);
$pemasukan_bulan_ini = (int)$stmt->fetchColumn();

$stmt->execute([$tanggal_dari_bulan_lalu, $tanggal_sampai_bulan_lalu]);
$pemasukan_bulan_lalu = (int)$stmt->fetchColumn();

$pertumbuhan = $pemasukan_bulan_lalu > 0 
    ? (($pemasukan_bulan_ini - $pemasukan_bulan_lalu) / $pemasukan_bulan_lalu) * 100 
    : ($pemasukan_bulan_ini > 0 ? 100 : 0);
$pertumbuhan = round($pertumbuhan, 1);

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT nama_outlet) FROM infaq WHERE tanggal >= ? AND tanggal <= ?");
$stmt->execute([$tanggal_dari_bulan_ini, $tanggal_sampai_bulan_ini]);
$jml_outlet = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(nilai_rupiah), 0) FROM donasi_barang WHERE tanggal >= ? AND tanggal <= ?");
$stmt->execute([$tanggal_dari_bulan_ini, $tanggal_sampai_bulan_ini]);
$nilai_barang_bulan_ini = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM petugas WHERE is_active = 1");
$jml_petugas = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM wilayah WHERE is_active = 1");
$jml_wilayah = (int)$stmt->fetchColumn();

// --- 2. RINGKASAN EFEKTIVITAS ---
$awal_ta = date('n') < 7 ? date('Y') - 1 : date('Y');
$akhir_ta = $awal_ta + 1;
$tanggal_dari_ta = "$awal_ta-07-01";
$tanggal_sampai_ta = "$akhir_ta-06-30";

$sql = "SELECT COUNT(DISTINCT nama_outlet), COUNT(*), SUM(jumlah_donasi) FROM infaq WHERE tanggal >= ? AND tanggal <= ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$tanggal_dari_ta, $tanggal_sampai_ta]);
[$jml_kotak, $terambil, $total] = $stmt->fetch(PDO::FETCH_NUM);

$total = (int)$total;
$rata_per_kotak = $terambil > 0 ? $total / $terambil : 0;
$efektivitas_level = $rata_per_kotak >= 500000 ? 'Tinggi' : ($rata_per_kotak >= 200000 ? 'Sedang' : 'Rendah');

// --- 3. CHART 1: Pemasukan per Bulan (Tahun Ajaran) ---
$bulan_list = [
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober',
    11 => 'November', 12 => 'Desember',
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni'
];

$pemasukan_per_bulan = array_fill_keys(array_keys($bulan_list), 0);
$stmt = $pdo->prepare("SELECT MONTH(tanggal), SUM(jumlah_donasi) FROM infaq WHERE tanggal >= ? AND tanggal <= ? GROUP BY 1");
$stmt->execute([$tanggal_dari_ta, $tanggal_sampai_ta]);
foreach ($stmt->fetchAll() as $row) {
    if (isset($pemasukan_per_bulan[$row[0]])) {
        $pemasukan_per_bulan[$row[0]] = (int)$row[1];
    }
}

// --- 4. CHART 2: Perbandingan Tahun Ini vs Tahun Lalu ---
$bulan_nama_pendek = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$tahun_ini_data = array_fill(1, 12, 0);
$tahun_lalu_data = array_fill(1, 12, 0);

for ($m = 1; $m <= 12; $m++) {
    $dari = "$tahun_ini-$m-01";
    $sampai = date("Y-m-t", strtotime($dari));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_donasi), 0) FROM infaq WHERE tanggal >= ? AND tanggal <= ?");
    $stmt->execute([$dari, $sampai]);
    $tahun_ini_data[$m] = (int)$stmt->fetchColumn();
}

for ($m = 1; $m <= 12; $m++) {
    $dari = "$tahun_lalu-$m-01";
    $sampai = date("Y-m-t", strtotime($dari));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_donasi), 0) FROM infaq WHERE tanggal >= ? AND tanggal <= ?");
    $stmt->execute([$dari, $sampai]);
    $tahun_lalu_data[$m] = (int)$stmt->fetchColumn();
}

// --- 5. CHART 3: Top 10 Petugas (Bulan Ini) ---
$sql = "SELECT p.nama_petugas, COALESCE(SUM(i.jumlah_donasi), 0) as total FROM petugas p LEFT JOIN infaq i ON p.id = i.petugas_id AND i.tanggal >= ? AND i.tanggal <= ? WHERE p.is_active = 1 GROUP BY p.id, p.nama_petugas ORDER BY total DESC LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute([$tanggal_dari_bulan_ini, $tanggal_sampai_bulan_ini]);
$top_petugas = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<main class="content">
    <!-- NOTIFIKASI: Hari Ini Belum Input -->
    <?php if (!$sudah_input): ?>
        <div style="background: hsl(45, 100%, 25%); color: white; padding: 12px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: bold;">
            ⚠️ Hari ini belum ada input kotak infaq. Segera input untuk menjaga kelengkapan data!
        </div>
    <?php endif; ?>

    <!-- KARTU UTAMA (5 Kolom) -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 30px;">
        <div style="background: hsl(120, 40%, 18%); padding: 16px; border-radius: 8px; text-align: center; color: white;">
            <div style="font-size: 0.9em; opacity: 0.9;">Pemasukan Bulan Ini</div>
            <div style="font-size: 1.3em; font-weight: bold;"><?= number_format($pemasukan_bulan_ini, 0, ',', '.') ?></div>
            <div style="font-size: 0.9em; margin-top: 4px; color: <?= $pertumbuhan >= 0 ? 'hsl(150, 50%, 70%)' : 'hsl(360, 60%, 70%)' ?>;">
                <?= $pertumbuhan >= 0 ? '↑' : '↓' ?> <?= abs($pertumbuhan) ?>% dari bulan lalu
            </div>
        </div>
        <div style="background: hsl(210, 50%, 18%); padding: 16px; border-radius: 8px; text-align: center; color: white;">
            <div style="font-size: 0.9em; opacity: 0.9;">Nilai Barang (<?= bulan_indo($bulan_ini) ?>)</div>
            <div style="font-size: 1.3em; font-weight: bold;"><?= number_format($nilai_barang_bulan_ini, 0, ',', '.') ?></div>
        </div>
        <div style="background: hsl(45, 60%, 18%); padding: 16px; border-radius: 8px; text-align: center; color: white;">
            <div style="font-size: 0.9em; opacity: 0.9;">Petugas Aktif</div>
            <div style="font-size: 1.3em; font-weight: bold;"><?= $jml_petugas ?></div>
        </div>
        <div style="background: hsl(240, 30%, 18%); padding: 16px; border-radius: 8px; text-align: center; color: white;">
            <div style="font-size: 0.9em; opacity: 0.9;">Wilayah Aktif</div>
            <div style="font-size: 1.3em; font-weight: bold;"><?= $jml_wilayah ?></div>
        </div>
    </div>

    <!-- Ringkasan Efektivitas -->
    <div class="card" style="padding: 16px; margin-bottom: 30px;">
        <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%);">📊 Ringkasan Efektivitas (<?= $awal_ta ?>/<?= $akhir_ta ?>)</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 16px;">
            <div style="text-align: center;">
                <div style="font-size: 1.2em; font-weight: bold;"><?= $jml_kotak ?></div>
                <div style="font-size: 0.9em; color: hsl(0,0%,70%);">Outlet / Kotak Dimiliki</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 1.2em; font-weight: bold;"><?= $terambil ?></div>
                <div style="font-size: 0.9em; color: hsl(0,0%,70%);">Kotak Terambil</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 1.2em; font-weight: bold;"><?= number_format($rata_per_kotak, 0, ',', '.') ?></div>
                <div style="font-size: 0.9em; color: hsl(0,0%,70%);">Rata-rata per Kotak</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 1.2em; font-weight: bold; color: <?= $efektivitas_level == 'Tinggi' ? 'hsl(150,50%,70%)' : ($efektivitas_level == 'Sedang' ? 'hsl(45,70%,60%)' : 'hsl(360,60%,70%)') ?>;">
                    <?= $efektivitas_level ?>
                </div>
                <div style="font-size: 0.9em; color: hsl(0,0%,70%);">Efektivitas</div>
            </div>
        </div>
    </div>
	
	<!-- Chart 1 Baris 3 Kolom -->
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        <div class="card" style="padding: 16px;">
            <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%);">📈 Pemasukan per Bulan (<?= $awal_ta ?>/<?= $akhir_ta ?>)</h3>
            <canvas id="chart-bulanan" height="200"></canvas>
        </div>
        <div class="card" style="padding: 16px;">
            <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%);">📊 Perbandingan Tahunan</h3>
            <canvas id="chart-tahunan" height="200"></canvas>
        </div>
        <div class="card" style="padding: 16px;">
            <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%);">🏆 Petugas Top (<?= bulan_indo($bulan_ini) ?> <?= $tahun_ini ?>)</h3>
            <canvas id="chart-top-petugas" height="200"></canvas>
        </div>
    </div>

    <!-- Akses Cepat & PDF -->
    <div class="card" style="padding: 16px;">
        <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%);">🎯 Akses Cepat</h3>
        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;">
            <a href="kotak-infaq.php" class="btn btn-primary" style="flex: 1; min-width: 120px;">➕ Input Infaq</a>
            <a href="donasi-barang.php" class="btn btn-primary" style="flex: 1; min-width: 120px;">📦 Input Barang</a>
            <a href="laporan-realtime.php" class="btn btn-secondary" style="flex: 1; min-width: 120px;">🔍 Laporan Realtime</a>
            <a href="rekap-laporan-tahunan.php" class="btn btn-secondary" style="flex: 1; min-width: 120px;">📊 Rekap Tahunan</a>
            <a href="laporan-efektivitas-kotak-infaq.php" class="btn btn-secondary" style="flex: 1; min-width: 120px;">🎯 Efektivitas</a>
            <a href="laporan-donasi-barang.php" class="btn btn-secondary" style="flex: 1; min-width: 120px;">📦 Laporan Barang</a>
			<a href="backup.php" "btn btn-secondary" style="flex: 1; min-width: 120px;" onclick="return confirm('Yakin ingin backup database sekarang?')">
    💾 Backup Sekarang
</a>
        </div>
	</div>
</main>

<?php include 'includes/footer.php'; ?>

<!-- CDN Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Chart scripts (sama seperti sebelumnya)
document.addEventListener('DOMContentLoaded', function () {
    const ctx1 = document.getElementById('chart-bulanan').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_values($bulan_list)) ?>,
            datasets: [{
                label: 'Pemasukan (Rp)',
                 <?= json_encode(array_values($pemasukan_per_bulan)) ?>,
                backgroundColor: 'hsl(160, 60%, 45%)',
                borderColor: 'hsl(160, 60%, 35%)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => 'Rp ' + v.toLocaleString() }
                },
                x: { ticks: { color: '#ccc' } }
            }
        }
    });

    const ctx2 = document.getElementById('chart-tahunan').getContext('2d');
    new Chart(ctx2, {
        type: 'line',
         {
            labels: <?= json_encode($bulan_nama_pendek) ?>,
            datasets: [
                { label: '<?= $tahun_ini ?>',  <?= json_encode(array_values($tahun_ini_data)) ?>, borderColor: 'hsl(120,70%,50%)', tension: 0.3 },
                { label: '<?= $tahun_lalu ?>', <?= json_encode(array_values($tahun_lalu_data)) ?>, borderColor: 'hsl(240,70%,50%)', borderDash: [5,5], tension: 0.3 }
            ]
        },
        options: { responsive: true }
    });

    const ctx3 = document.getElementById('chart-top-petugas').getContext('2d');
    new Chart(ctx3, {
        type: 'bar',
         {
            labels: <?= json_encode(array_column($top_petugas, 'nama_petugas')) ?>,
            datasets: [{
                label: 'Total (Rp)',
                 <?= json_encode(array_column($top_petugas, 'total')) ?>,
                backgroundColor: 'hsl(210, 60%, 45%)',
                borderColor: 'hsl(210, 60%, 35%)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { callback: v => 'Rp ' + v.toLocaleString() } },
                y: { ticks: { color: '#ccc' } }
            }
        }
    });
});
</script>