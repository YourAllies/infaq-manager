<?php
// laporan-donasi-barang.php
require_once 'config/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil filter
$tanggal_dari = $_GET['tanggal_dari'] ?? '';
$tanggal_sampai = $_GET['tanggal_sampai'] ?? '';
$nama_donatur = $_GET['nama_donatur'] ?? '';
$jenis_barang = $_GET['jenis_barang'] ?? '';
$no_hp = $_GET['no_hp'] ?? '';

// Query data
$sql = "
    SELECT 
        id,
        tanggal,
        nama_donatur,
        alamat,
        no_hp,
        jenis_barang,
        jumlah_satuan,
        nilai_rupiah,
        keterangan
    FROM donasi_barang
    WHERE 1=1
";

$params = [];

if (!empty($tanggal_dari)) {
    $sql .= " AND tanggal >= ?";
    $params[] = $tanggal_dari;
}
if (!empty($tanggal_sampai)) {
    $sql .= " AND tanggal <= ?";
    $params[] = $tanggal_sampai;
}
if (!empty($nama_donatur)) {
    $sql .= " AND nama_donatur LIKE ?";
    $params[] = '%' . $nama_donatur . '%';
}
if (!empty($jenis_barang)) {
    $sql .= " AND jenis_barang LIKE ?";
    $params[] = '%' . $jenis_barang . '%';
}
if (!empty($no_hp)) {
    $sql .= " AND no_hp LIKE ?";
    $params[] = '%' . $no_hp . '%';
}

$sql .= " ORDER BY tanggal DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Proses edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = $_POST['id'];
    $tanggal = $_POST['tanggal'];
    $nama_donatur = trim($_POST['nama_donatur']);
    $alamat = trim($_POST['alamat']);
    $no_hp = trim($_POST['no_hp']);
    $jenis_barang = trim($_POST['jenis_barang']);
    $jumlah_satuan = trim($_POST['jumlah_satuan']);
    $nilai_rupiah = (float)($_POST['nilai_rupiah'] ?? 0);
    $keterangan = trim($_POST['keterangan']);

    try {
        $stmt = $pdo->prepare("UPDATE donasi_barang SET tanggal = ?, nama_donatur = ?, alamat = ?, no_hp = ?, jenis_barang = ?, jumlah_satuan = ?, nilai_rupiah = ?, keterangan = ? WHERE id = ?");
        $stmt->execute([$tanggal, $nama_donatur, $alamat, $no_hp ?: null, $jenis_barang, $jumlah_satuan, $nilai_rupiah, $keterangan ?: null, $id]);
        $_SESSION['success'] = "Data berhasil diperbarui!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal update data.";
    }

    header('Location: laporan-donasi-barang.php?' . http_build_query($_GET));
    exit;
}

// Proses hapus
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM donasi_barang WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Data berhasil dihapus!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal hapus data.";
    }
    header('Location: laporan-donasi-barang.php?' . http_build_query($_GET));
    exit;
}
?>

<?php include 'includes/header.php'; ?>

<main class="content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Laporan Donasi Barang</h2>
        <a href="export_excel-laporan-donasi-barang.php?<?= http_build_query($_GET) ?>"
           class="btn btn-primary" style="padding: 8px 16px; font-size: 0.95em;">
            📥 Export Excel
        </a>
    </div>

    <!-- Pesan -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="text-success" style="background: hsl(150, 45%, 18%); padding: 12px; border-radius: 6px; margin-bottom: 16px;">
            ✅ <?= $_SESSION['success'] ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="text-danger" style="background: hsl(360, 60%, 15%); padding: 12px; border-radius: 6px; margin-bottom: 16px;">
            ❌ <?= $_SESSION['error'] ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%); font-size: 1.1em;">Filter Data</h3>
        <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px;">
            <div class="form-group">
                <label>Rentang Tanggal</label>
                <input 
                    type="text" 
                    name="tanggal_range" 
                    id="tanggal-range" 
                    class="form-control" 
                    placeholder="Pilih rentang tanggal"
                    value="<?= e($_GET['tanggal_range'] ?? '') ?>"
                    readonly
                >
                <input type="hidden" name="tanggal_dari" id="tanggal_dari" value="<?= e($tanggal_dari) ?>">
                <input type="hidden" name="tanggal_sampai" id="tanggal_sampai" value="<?= e($tanggal_sampai) ?>">
            </div>
            <div class="form-group">
                <label>Nama Donatur</label>
                <input type="text" name="nama_donatur" class="form-control" placeholder="Cari nama donatur" value="<?= e($nama_donatur) ?>">
            </div>
            <div class="form-group">
                <label>Jenis Barang</label>
                <input type="text" name="jenis_barang" class="form-control" placeholder="Cari barang" value="<?= e($jenis_barang) ?>">
            </div>
            <div class="form-group">
                <label>No HP</label>
                <input type="text" name="no_hp" class="form-control" placeholder="Cari nomor HP" value="<?= e($no_hp) ?>">
            </div>
            <div style="grid-column: span 4; display: flex; gap: 10px; margin-top: 10px;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="laporan-donasi-barang.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Donatur</th>
                    <th>Alamat</th>
                    <th>No HP</th>
                    <th>Jenis Barang</th>
                    <th>Jumlah & Satuan</th>
                    <th>Nilai (Rp)</th>
                    <th>Keterangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: hsl(0,0%,60%);">Tidak ada data ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                            <td><?= e($row['nama_donatur']) ?></td>
                            <td><?= e($row['alamat']) ?></td>
                            <td><?= e($row['no_hp'] ?: '<em>(kosong)</em>') ?></td>
                            <td><?= e($row['jenis_barang']) ?></td>
                            <td><?= e($row['jumlah_satuan']) ?></td>
                            <td style="text-align: right;"><?= number_format($row['nilai_rupiah'], 0, ',', '.') ?></td>
                            <td><?= e($row['keterangan'] ?: '-') ?></td>
                            <td>
                                <button onclick="openEditModal(
                                    <?= $row['id'] ?>,
                                    '<?= addslashes($row['tanggal']) ?>',
                                    '<?= addslashes($row['nama_donatur']) ?>',
                                    '<?= addslashes($row['alamat']) ?>',
                                    '<?= addslashes($row['no_hp']) ?>',
                                    '<?= addslashes($row['jenis_barang']) ?>',
                                    '<?= addslashes($row['jumlah_satuan']) ?>',
                                    '<?= $row['nilai_rupiah'] ?>',
                                    '<?= addslashes($row['keterangan']) ?>'
                                )" class="btn btn-sm btn-warning">Edit</button>
                                <a href="?delete=<?= $row['id'] ?>&<?= http_build_query($_GET) ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Yakin hapus data ini?')">
                                    Hapus
                                </a>
                            </td>
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>

<script>
const flatpickrId = {
        weekdays: {
            shorthand: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],
            longhand: ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"]
        },
        months: {
            shorthand: ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"],
            longhand: ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"]
        },
        firstDayOfWeek: 1,
        rangeSeparator: " - ",
        weekAbbreviation: "Mg",
        scrollTitle: "Gulir untuk memperbesar",
        toggleTitle: "Klik untuk beralih",
        yearAriaLabel: "Tahun",
        monthAriaLabel: "Bulan",
        hourAriaLabel: "Jam",
        minuteAriaLabel: "Menit"
    };
document.addEventListener('DOMContentLoaded', function () {
    // Format tampilan rentang
    let tanggalDari = '<?= e($tanggal_dari) ?>';
    let tanggalSampai = '<?= e($tanggal_sampai) ?>';
    let displayText = '';
    if (tanggalDari && tanggalSampai) {
        displayText = `${tanggalDari} hingga ${tanggalSampai}`;
    } else if (tanggalDari) {
        displayText = `${tanggalDari} hingga ...`;
    } else if (tanggalSampai) {
        displayText = `... hingga ${tanggalSampai}`;
    }

    flatpickr("#tanggal-range", {
        mode: "range",
        altInput: true,
        altFormat: "d/m/Y",
        dateFormat: "Y-m-d",
        locale: "id",
        showMonths: 2,
        defaultDate: [tanggalDari, tanggalSampai],
        onChange: function (selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                const dari = selectedDates[0].toISOString().split('T')[0];
                const sampai = selectedDates[1].toISOString().split('T')[0];
                document.getElementById('tanggal_dari').value = dari;
                document.getElementById('tanggal_sampai').value = sampai;
            } else if (selectedDates.length === 1) {
                document.getElementById('tanggal_dari').value = selectedDates[0].toISOString().split('T')[0];
                document.getElementById('tanggal_sampai').value = '';
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

// Modal Edit
function openEditModal(id, tanggal, nama_donatur, alamat, no_hp, jenis_barang, jumlah_satuan, nilai_rupiah, keterangan) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-tanggal').value = tanggal;
    document.getElementById('edit-nama-donatur').value = nama_donatur;
    document.getElementById('edit-alamat').value = alamat;
    document.getElementById('edit-no-hp').value = no_hp || '';
    document.getElementById('edit-jenis-barang').value = jenis_barang;
    document.getElementById('edit-jumlah-satuan').value = jumlah_satuan;
    document.getElementById('edit-nilai-rupiah').value = nilai_rupiah;
    document.getElementById('edit-keterangan').value = keterangan || '';
    document.getElementById('edit-modal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('edit-modal');
    if (event.target === modal) {
        closeEditModal();
    }
}
</script>