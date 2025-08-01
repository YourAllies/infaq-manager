<?php
// kelola-wilayah.php
require_once 'config/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Proses tambah wilayah
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama = trim($_POST['nama_wilayah'] ?? '');
    $is_active = $_POST['is_active'] ?? 1; // Default: aktif

    if (empty($nama)) {
        $_SESSION['error'] = "Nama wilayah wajib diisi.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO wilayah (nama_wilayah, is_active) VALUES (?, ?)");
            $stmt->execute([$nama, $is_active]);
            $_SESSION['success'] = "Wilayah berhasil ditambahkan!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Gagal menambahkan wilayah. Nama mungkin sudah ada.";
        }
    }
    header('Location: kelola-wilayah.php');
    exit;
}

// Proses edit wilayah
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $nama = trim($_POST['nama_wilayah'] ?? '');
    if (empty($nama)) {
        $_SESSION['error'] = "Nama wilayah wajib diisi.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE wilayah SET nama_wilayah = ? WHERE id = ?");
            $stmt->execute([$nama, $id]);
            $_SESSION['success'] = "Wilayah berhasil diperbarui!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Gagal memperbarui wilayah.";
        }
    }
    header('Location: kelola-wilayah.php');
    exit;
}

// Proses non-aktifkan
if (isset($_GET['nonaktifkan'])) {
    $id = $_GET['nonaktifkan'];
    try {
        $stmt = $pdo->prepare("UPDATE wilayah SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Wilayah berhasil dinonaktifkan.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal menonaktifkan wilayah.";
    }
    header('Location: kelola-wilayah.php');
    exit;
}

// Proses aktifkan kembali
if (isset($_GET['aktifkan'])) {
    $id = $_GET['aktifkan'];
    try {
        $stmt = $pdo->prepare("UPDATE wilayah SET is_active = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Wilayah berhasil diaktifkan kembali.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal mengaktifkan wilayah.";
    }
    header('Location: kelola-wilayah.php');
    exit;
}

// Ambil data wilayah
$stmt = $pdo->query("SELECT * FROM wilayah ORDER BY nama_wilayah");
$wilayah_list = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<main class="content">
    <h2>Kelola Wilayah</h2>

    <!-- Pesan Sukses -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success" style="background: hsl(150, 45%, 18%); padding: 12px; border-radius: 6px; margin-bottom: 16px; color: white; font-size: 0.95em;">
            ✅ <?= $_SESSION['success'] ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Pesan Error -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error" style="background: hsl(360, 60%, 15%); padding: 12px; border-radius: 6px; margin-bottom: 16px; color: white; font-size: 0.95em;">
            ❌ <?= $_SESSION['error'] ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Tombol Tambah & Export -->
    <div style="margin-bottom: 20px;">
        <button type="button" class="btn btn-primary" onclick="openTambahModal()">
            ➕ Tambah Wilayah
        </button>
        <a href="export_excel-kelola-wilayah.php" class="btn btn-success" style="margin-left: 10px;">
            📥 Export Excel
        </a>
    </div>

    <!-- Pencarian -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <input type="text" id="search-wilayah" class="form-control" placeholder="Cari nama wilayah..." style="width: 300px;">
    </div>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table" id="tabel-wilayah">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Wilayah</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($wilayah_list)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: hsl(0,0%,60%); padding: 20px;">
                            Tidak ada wilayah ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($wilayah_list as $index => $w): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= e($w['nama_wilayah']) ?></td>
                            <td>
                                <span style="color: <?= $w['is_active'] ? 'hsl(150, 50%, 70%)' : 'hsl(360, 60%, 70%)' ?>; font-weight: bold;">
                                    <?= $w['is_active'] ? 'Aktif' : 'Tidak Aktif' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="openEditModal(<?= $w['id'] ?>, '<?= addslashes($w['nama_wilayah']) ?>')">
                                    Edit
                                </button>
                                <?php if ($w['is_active']): ?>
                                    <a href="?nonaktifkan=<?= $w['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menonaktifkan wilayah ini?')">
                                        Non-Aktifkan
                                    </a>
                                <?php else: ?>
                                    <a href="?aktifkan=<?= $w['id'] ?>" class="btn btn-sm btn-secondary">
                                        Aktifkan
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<!-- Modal Tambah -->
<div id="modal-tambah" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center;">
    <div class="modal-content" style="background: hsl(240, 12%, 16%); padding: 24px; border-radius: 12px; width: 400px; max-width: 90%; border: 1px solid hsl(240, 10%, 30%);">
        <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%);">Tambah Wilayah Baru</h3>
        <form method="POST">
            <input type="hidden" name="action" value="tambah">
            <div class="form-group">
                <label>Nama Wilayah</label>
                <input type="text" name="nama_wilayah" class="form-control" placeholder="Contoh: Jakarta Selatan" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="is_active" class="form-control" required>
                    <option value="1">✅ Aktif</option>
                    <option value="0">⏸️ Tidak Aktif</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeModal('modal-tambah')" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div id="modal-edit" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center;">
    <div class="modal-content" style="background: hsl(240, 12%, 16%); padding: 24px; border-radius: 12px; width: 400px; max-width: 90%; border: 1px solid hsl(240, 10%, 30%);">
        <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%);">Edit Wilayah</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-group">
                <label>Nama Wilayah</label>
                <input type="text" name="nama_wilayah" id="edit-nama" class="form-control" placeholder="Masukkan nama wilayah" required>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeModal('modal-edit')" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
// Buka modal tambah
function openTambahModal() {
    document.getElementById('modal-tambah').style.display = 'flex';
}

// Buka modal edit
function openEditModal(id, nama) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-nama').value = nama;
    document.getElementById('modal-edit').style.display = 'flex';
}

// Tutup modal
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// Pencarian real-time
document.getElementById('search-wilayah').addEventListener('keyup', function () {
    const input = this.value.toLowerCase();
    const rows = document.querySelectorAll('#tabel-wilayah tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
});

// Tutup modal saat klik di luar
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>