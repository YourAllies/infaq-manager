<?php
// laporan-realtime.php
require_once 'config/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil filter
$tanggal_range = $_GET['tanggal_range'] ?? '';
$tanggal_dari = $_GET['tanggal_dari'] ?? '';
$tanggal_sampai = $_GET['tanggal_sampai'] ?? '';
$nama_outlet = $_GET['nama_outlet'] ?? '';
$hp_pemilik = $_GET['hp_pemilik'] ?? '';
$petugas_id = $_GET['petugas_id'] ?? '';
$wilayah_id = $_GET['wilayah_id'] ?? '';

// Query dasar untuk data dan statistik
$sql_base = "
    SELECT 
        i.id,
        i.tanggal,
        i.nama_outlet,
        i.hp_pemilik,
        i.jumlah_donasi,
        i.keterangan,
        p.nama_petugas,
        w.nama_wilayah
    FROM infaq i
    LEFT JOIN petugas p ON i.petugas_id = p.id
    LEFT JOIN wilayah w ON i.wilayah_id = w.id
    WHERE 1=1
";

$sql_count = "
    SELECT 
        COUNT(*) as total_terambil,
        SUM(i.jumlah_donasi) as total_donasi
    FROM infaq i
    LEFT JOIN petugas p ON i.petugas_id = p.id
    LEFT JOIN wilayah w ON i.wilayah_id = w.id
    WHERE 1=1
";

$params = [];

// Filter
if (!empty($tanggal_dari)) {
    $sql_base .= " AND i.tanggal >= ?";
    $sql_count .= " AND i.tanggal >= ?";
    $params[] = $tanggal_dari;
}
if (!empty($tanggal_sampai)) {
    $sql_base .= " AND i.tanggal <= ?";
    $sql_count .= " AND i.tanggal <= ?";
    $params[] = $tanggal_sampai;
}
if (!empty($nama_outlet)) {
    $sql_base .= " AND i.nama_outlet LIKE ?";
    $sql_count .= " AND i.nama_outlet LIKE ?";
    $params[] = '%' . $nama_outlet . '%';
}
if (!empty($hp_pemilik)) {
    $sql_base .= " AND i.hp_pemilik LIKE ?";
    $sql_count .= " AND i.hp_pemilik LIKE ?";
    $params[] = '%' . $hp_pemilik . '%';
}
if (!empty($petugas_id)) {
    $sql_base .= " AND i.petugas_id = ?";
    $sql_count .= " AND i.petugas_id = ?";
    $params[] = $petugas_id;
}
if (!empty($wilayah_id)) {
    $sql_base .= " AND i.wilayah_id = ?";
    $sql_count .= " AND i.wilayah_id = ?";
    $params[] = $wilayah_id;
}

// Eksekusi query statistik
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$stats = $stmt_count->fetch();

$total_terambil = (int)$stats['total_terambil'];
$total_donasi = (int)$stats['total_donasi'];
$rata_per_kotak = $total_terambil > 0 ? $total_donasi / $total_terambil : 0;

// Hitung jumlah kotak infaq (jumlah outlet unik yang pernah dimasuki)
$sql_outlets = str_replace('SELECT COUNT(*)', 'SELECT DISTINCT i.nama_outlet', $sql_base);
$stmt_outlets = $pdo->prepare($sql_outlets);
$stmt_outlets->execute($params);
$outlets = $stmt_outlets->fetchAll(PDO::FETCH_COLUMN);
$jumlah_kotak_infaq = count($outlets);

// Eksekusi data untuk tabel
$stmt = $pdo->prepare($sql_base . " ORDER BY i.tanggal DESC, i.id DESC");
$stmt->execute($params);
$data = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<main class="content">
    <h2>Laporan Realtime Kotak Infaq</h2>

    <!-- Kartu Statistik -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 20px;">
        <div style="background: hsl(200, 30%, 18%); padding: 16px; border-radius: 8px; text-align: center; color: white;">
            <div style="font-size: 0.9em; opacity: 0.9;">Jumlah Kotak Infaq</div>
            <div style="font-size: 1.4em; font-weight: bold; margin-top: 4px;"><?= $jumlah_kotak_infaq ?></div>
        </div>
        <div style="background: hsl(160, 40%, 18%); padding: 16px; border-radius: 8px; text-align: center; color: white;">
            <div style="font-size: 0.9em; opacity: 0.9;">Kotak Terambil</div>
            <div style="font-size: 1.4em; font-weight: bold; margin-top: 4px;"><?= $total_terambil ?></div>
        </div>
        <div style="background: hsl(120, 50%, 18%); padding: 16px; border-radius: 8px; text-align: center; color: white;">
            <div style="font-size: 0.9em; opacity: 0.9;">Total Donasi</div>
            <div style="font-size: 1.3em; font-weight: bold; margin-top: 4px;"><?= number_format($total_donasi, 0, ',', '.') ?></div>
        </div>
        <div style="background: hsl(45, 60%, 20%); padding: 16px; border-radius: 8px; text-align: center; color: white;">
            <div style="font-size: 0.9em; opacity: 0.9;">Rata/Kotak</div>
            <div style="font-size: 1.3em; font-weight: bold; margin-top: 4px;"><?= number_format($rata_per_kotak, 0, ',', '.') ?></div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px;">
            <div class="form-group">
                <label>Rentang Tanggal</label>
                <input 
                    type="text" 
                    name="tanggal_range" 
                    id="tanggal-range" 
                    class="form-control" 
                    placeholder="Pilih rentang tanggal"
                    value="<?= e($tanggal_range) ?>"
                    readonly
                >
                <input type="hidden" name="tanggal_dari" id="tanggal_dari" value="<?= e($tanggal_dari) ?>">
                <input type="hidden" name="tanggal_sampai" id="tanggal_sampai" value="<?= e($tanggal_sampai) ?>">
            </div>
            <div class="form-group">
                <label>Nama Outlet</label>
                <input type="text" name="nama_outlet" class="form-control" placeholder="Cari outlet" value="<?= e($nama_outlet) ?>">
            </div>
            <div class="form-group">
                <label>HP Pemilik</label>
                <input type="text" name="hp_pemilik" class="form-control" placeholder="Cari HP" value="<?= e($hp_pemilik) ?>">
            </div>
            <div class="form-group">
                <label>Petugas</label>
                <select name="petugas_id" class="form-control">
                    <option value="">Semua Petugas</option>
                    <?php
                    $stmt = $pdo->query("SELECT id, nama_petugas FROM petugas ORDER BY nama_petugas");
                    foreach ($stmt as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $petugas_id ? 'selected' : '' ?>>
                            <?= e($p['nama_petugas']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Wilayah</label>
                <select name="wilayah_id" class="form-control">
                    <option value="">Semua Wilayah</option>
                    <?php
                    $stmt = $pdo->query("SELECT id, nama_wilayah FROM wilayah ORDER BY nama_wilayah");
                    foreach ($stmt as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= $w['id'] == $wilayah_id ? 'selected' : '' ?>>
                            <?= e($w['nama_wilayah']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="grid-column: span 4; display: flex; gap: 10px; margin-top: 10px;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="laporan-realtime.php" class="btn btn-secondary">Reset</a>
                <a href="export_excel-laporan-realtime.php?<?= http_build_query($_GET) ?>" class="btn btn-success">📥 Export Excel</a>
            </div>
        </form>
    </div>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Outlet</th>
                    <th>HP Pemilik</th>
                    <th>Wilayah</th>
                    <th>Petugas</th>
                    <th>Jumlah Donasi</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: hsl(0,0%,60%);">Tidak ada data ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td><?= e($row['tanggal']) ?></td>
                            <td><?= e($row['nama_outlet']) ?></td>
                            <td><?= e($row['hp_pemilik']) ?></td>
                            <td><?= e($row['nama_wilayah']) ?></td>
                            <td><?= e($row['nama_petugas']) ?></td>
                            <td style="text-align: right;"><?= number_format($row['jumlah_donasi'], 0, ',', '.') ?></td>
                            <td><?= e($row['keterangan']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<!-- CDN Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    flatpickr("#tanggal-range", {
        mode: "range",
        altInput: true,
        altFormat: "d/m/Y",
        dateFormat: "Y-m-d",
        locale: "id",
        showMonths: 2,
        defaultDate: [<?= $tanggal_dari ? "'$tanggal_dari'" : 'null' ?>, <?= $tanggal_sampai ? "'$tanggal_sampai'" : 'null' ?>],
        onChange: function (selectedDates) {
            if (selectedDates.length === 2) {
                const dari = selectedDates[0].toISOString().split('T')[0];
                const sampai = selectedDates[1].toISOString().split('T')[0];
                document.getElementById('tanggal_dari').value = dari;
                document.getElementById('tanggal_sampai').value = sampai;
            }
        },
        onReady: function () {
            const months = document.querySelectorAll('.flatpickr-month');
            if (months.length > 1) {
                months[0].style.borderRight = '2px solid #16a34a';
                months[1].style.borderLeft = '2px solid #16a34a';
            }
        }
    });
});
</script>