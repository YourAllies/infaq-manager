<?php
// kelola-petugas.php
require_once 'config/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Proses tambah petugas
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama = trim($_POST['nama_petugas'] ?? '');
    if (empty($nama)) {
        $_SESSION['error'] = "Nama petugas wajib diisi.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO petugas (nama_petugas, is_active) VALUES (?, 1)");
            $stmt->execute([$nama]);
            $_SESSION['success'] = "Petugas berhasil ditambahkan!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Gagal menambahkan petugas.";
        }
    }
    header('Location: kelola-petugas.php');
    exit;
}

// Proses edit petugas
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $nama = trim($_POST['nama_petugas'] ?? '');
    if (empty($nama)) {
        $_SESSION['error'] = "Nama petugas wajib diisi.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE petugas SET nama_petugas = ? WHERE id = ?");
            $stmt->execute([$nama, $id]);
            $_SESSION['success'] = "Petugas berhasil diperbarui!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Gagal memperbarui petugas.";
        }
    }
    header('Location: kelola-petugas.php');
    exit;
}

// Proses hapus (non-aktifkan)
if (isset($_GET['nonaktifkan'])) {
    $id = $_GET['nonaktifkan'];
    try {
        $stmt = $pdo->prepare("UPDATE petugas SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Petugas berhasil dinonaktifkan.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal menonaktifkan petugas.";
    }
    header('Location: kelola-petugas.php');
    exit;
}

// Proses aktifkan kembali
if (isset($_GET['aktifkan'])) {
    $id = $_GET['aktifkan'];
    try {
        $stmt = $pdo->prepare("UPDATE petugas SET is_active = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Petugas berhasil diaktifkan kembali.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal mengaktifkan petugas.";
    }
    header('Location: kelola-petugas.php');
    exit;
}

// Ambil data petugas
$stmt = $pdo->query("SELECT * FROM petugas ORDER BY nama_petugas");
$petugas_list = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<main class="content">
    <h2>Kelola Petugas</h2>

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

    <!-- Tombol Tambah -->
    <div style="margin-bottom: 20px;">
        <button type="button" class="btn btn-primary" onclick="openTambahModal()">
            ➕ Tambah Petugas
        </button>
        <a href="export_excel-kelola-petugas.php" class="btn btn-success" style="margin-left: 10px;">
            📥 Export Excel
        </a>
    </div>

    <!-- Pencarian -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <input type="text" id="search-petugas" class="form-control" placeholder="Cari nama petugas..." style="width: 300px;">
    </div>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table" id="tabel-petugas">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Petugas</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($petugas_list)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: hsl(0,0%,60%);">Tidak ada petugas ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($petugas_list as $index => $p): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= e($p['nama_petugas']) ?></td>
                            <td>
                                <span style="color: <?= $p['is_active'] ? 'hsl(150, 50%, 70%)' : 'hsl(360, 60%, 70%)' ?>; font-weight: bold;">
                                    <?= $p['is_active'] ? 'Aktif' : 'Tidak Aktif' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="openEditModal(<?= $p['id'] ?>, '<?= addslashes($p['nama_petugas']) ?>')">
                                    Edit
                                </button>
                                <?php if ($p['is_active']): ?>
                                    <a href="?nonaktifkan=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin non-aktifkan petugas ini?')">
                                        Non-Aktifkan
                                    </a>
                                <?php else: ?>
                                    <a href="?aktifkan=<?= $p['id'] ?>" class="btn btn-sm btn-secondary">
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
        <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%);">Tambah Petugas</h3>
        <form method="POST">
            <input type="hidden" name="action" value="tambah">
            <div class="form-group">
                <label>Nama Petugas</label>
                <input type="text" name="nama_petugas" class="form-control" placeholder="Masukkan nama petugas" required>
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
        <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%);">Edit Petugas</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-group">
                <label>Nama Petugas</label>
                <input type="text" name="nama_petugas" id="edit-nama" class="form-control" required>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeModal('modal-edit')" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTambahModal() {
    document.getElementById('modal-tambah').style.display = 'flex';
}

function openEditModal(id, nama) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-nama').value = nama;
    document.getElementById('modal-edit').style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// Pencarian Realtime
document.getElementById('search-petugas').addEventListener('keyup', function () {
    const input = this.value.toLowerCase();
    const rows = document.querySelectorAll('#tabel-petugas tbody tr');
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