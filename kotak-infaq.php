<?php
// kotak-infaq.php
require_once 'config/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil data petugas
$stmt = $pdo->query("SELECT id, nama_petugas FROM petugas WHERE is_active = 1 ORDER BY nama_petugas");
$petugas_list = $stmt->fetchAll();

// Ambil data wilayah
$stmt = $pdo->query("SELECT id, nama_wilayah FROM wilayah WHERE is_active = 1 ORDER BY nama_wilayah");
$wilayah_list = $stmt->fetchAll();

// Proses form saat disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $tanggal = $_POST['tanggal'] ?? '';
    $petugas_id = $_POST['petugas_id'] ?? '';
    $wilayah_id = $_POST['wilayah_id'] ?? '';
    $outlets = $_POST['outlet'] ?? [];

    // Validasi header
    if (empty($tanggal)) {
        $errors[] = "Tanggal harus diisi.";
    }
    if (empty($petugas_id)) {
        $errors[] = "Petugas harus dipilih.";
    }
    if (empty($wilayah_id)) {
        $errors[] = "Wilayah harus dipilih.";
    }

    // Validasi setiap outlet
    foreach ($outlets as $outlet) {
        $nama_outlet = trim($outlet['nama_outlet'] ?? '');
        $hp_pemilik = trim($outlet['hp_pemilik'] ?? '');
        $jumlah_donasi = $outlet['jumlah_donasi'] ?? '';
        $keterangan = trim($outlet['keterangan'] ?? '');

        // Validasi wajib
        if (empty($nama_outlet)) {
            $errors[] = "Nama outlet harus diisi.";
            continue;
        }

        // Konversi dan validasi jumlah_donasi sebagai angka bulat
        $jumlah_donasi = (int)$jumlah_donasi;

        if ($jumlah_donasi <= 0) {
            $errors[] = "Jumlah donasi harus lebih dari 0 untuk outlet: $nama_outlet";
            continue;
        }

        // Validasi petugas_id dan wilayah_id
        $check_petugas = $pdo->prepare("SELECT COUNT(*) as count FROM petugas WHERE id = ?");
        $check_petugas->execute([$petugas_id]);
        $row = $check_petugas->fetch(PDO::FETCH_ASSOC);

        if ($row['count'] === 0) {
            $errors[] = "Petugas ID tidak valid.";
        }

        $check_wilayah = $pdo->prepare("SELECT COUNT(*) as count FROM wilayah WHERE id = ?");
        $check_wilayah->execute([$wilayah_id]);
        $row = $check_wilayah->fetch(PDO::FETCH_ASSOC);

        if ($row['count'] === 0) {
            $errors[] = "Wilayah ID tidak valid.";
        }

        // Simpan ke database
        try {
            $stmt = $pdo->prepare("INSERT INTO infaq (tanggal, nama_outlet, petugas_id, wilayah_id, jumlah_donasi, hp_pemilik, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $tanggal,
                $nama_outlet,
                $petugas_id,
                $wilayah_id,
                $jumlah_donasi,
                $hp_pemilik ?: null,
                $keterangan ?: null
            ]);
        } catch (PDOException $e) {
            $errors[] = "Gagal simpan '$nama_outlet': " . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = "Error tidak diketahui: " . $e->getMessage();
        }
    }

    // Redirect jika sukses
    if (empty($errors)) {
        $_SESSION['success'] = "Data kotak infaq berhasil disimpan!";
        header('Location: kotak-infaq.php');
        exit;
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>

<?php include 'includes/header.php'; ?>

<main class="content">
    <h2>Kotak Infaq</h2>

    <!-- Pesan Error -->
    <?php if (isset($error_message)): ?>
        <div class="text-danger" style="background: hsl(360, 60%, 15%); padding: 12px; border-radius: 6px; margin-bottom: 16px;">
            ❌ <?= $error_message ?>
        </div>
    <?php endif; ?>

    <!-- Pesan Sukses -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="text-success" style="background: hsl(150, 45%, 18%); padding: 12px; border-radius: 6px; margin-bottom: 16px;">
            ✅ <?= $_SESSION['success'] ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Form Input -->
    <form method="POST" id="form-kotak-infaq">
        <!-- Bagian 1: Tanggal, Petugas, Wilayah (Sekali Isi) -->
        <div class="card" style="padding: 16px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%); font-size: 1.1em;">Informasi Umum</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label for="tanggal">Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label for="petugas_id">Petugas</label>
                    <select name="petugas_id" id="petugas_id" class="form-control" required>
                        <option value="">Pilih Petugas</option>
                        <?php foreach ($petugas_list as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['nama_petugas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="wilayah_id">Wilayah</label>
                    <select name="wilayah_id" id="wilayah_id" class="form-control" required>
                        <option value="">Pilih Wilayah</option>
                        <?php foreach ($wilayah_list as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= e($w['nama_wilayah']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Bagian 2: Daftar Outlet (Bisa Ditambah) -->
        <div id="outlet-container">
            <h3 style="color: hsl(0, 0%, 90%); margin-bottom: 12px;">Daftar Outlet</h3>

            <!-- Outlet 1 (Default) -->
            <div class="outlet-item" data-index="1">
                <div class="card" style="position: relative; padding: 16px; margin-bottom: 16px;">
                    <!-- Tombol Hapus -->
                    <button type="button" class="btn btn-danger btn-sm" onclick="hapusOutlet(this)" style="
                        position: absolute;
                        top: 12px;
                        right: 12px;
                        padding: 4px 8px;
                        font-size: 0.8em;
                        border-radius: 6px;
                    ">×</button>

                    <!-- Field Outlet -->
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 2fr; gap: 16px;">
                        <div class="form-group">
                            <label for="nama_outlet_1">Nama Outlet</label>
                            <input type="text" name="outlet[1][nama_outlet]" id="nama_outlet_1" class="form-control" placeholder="Nama outlet" required>
                        </div>
                        <div class="form-group">
                            <label for="hp_pemilik_1">Nomor HP Pemilik Outlet</label>
                            <input type="text" name="outlet[1][hp_pemilik]" id="hp_pemilik_1" class="form-control" placeholder="bisa menyusul">
                        </div>
                        <div class="form-group">
                            <label for="jumlah_donasi_1">Jumlah Donasi</label>
                            <input 
                                type="number" 
                                name="outlet[1][jumlah_donasi]" 
                                id="jumlah_donasi_1" 
                                class="form-control" 
                                placeholder="Contoh: 500000" 
                                min="0" 
                                step="1" 
                                style="text-align: right;" 
                                required>
                        </div>
                        <div class="form-group">
                            <label for="keterangan_1">Keterangan</label>
                            <input type="text" name="outlet[1][keterangan]" id="keterangan_1" class="form-control" placeholder="Opsional">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Tambah Outlet -->
        <div style="margin: 20px 0;">
            <button type="button" class="btn btn-secondary" id="btn-tambah-outlet">+ Tambah Outlet</button>
        </div>

        <!-- Tombol Simpan & Kembali -->
        <div style="margin-top: 24px;">
            <button type="submit" class="btn btn-primary">Simpan Semua Data</button>
            <a href="index.php" class="btn btn-secondary" style="margin-left: 10px;">Kembali</a>
        </div>
    </form>
</main>

<?php include 'includes/footer.php'; ?>

<!-- CDN Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>

<script>
// Inisialisasi Date Picker
document.addEventListener('DOMContentLoaded', function () {
    flatpickr("#tanggal", {
        altInput: true,
        altFormat: "d/m/Y",
        dateFormat: "Y-m-d",
        locale: "id",                    // 🔥 Bahasa Indonesia
        //showMonths: 2,                   // 2 bulan berdampingan
        onChange: function (selectedDates, dateStr, instance) {
            document.getElementById('tanggal_hidden').value = dateStr;
        },
        onReady: function () {
            // Tambahkan garis pembatas antar bulan
            const monthElements = document.querySelectorAll('.flatpickr-month');
            if (monthElements.length > 1) {
                monthElements[0].style.borderRight = '2px solid #16a34a';
                monthElements[1].style.borderLeft = '2px solid #16a34a';
            }
        }
    });
});
// Tambah outlet dinamis
let outletIndex = 1;
document.getElementById('btn-tambah-outlet').addEventListener('click', function () {
    outletIndex++;
    const container = document.getElementById('outlet-container');

    const div = document.createElement('div');
    div.className = 'outlet-item';
    div.dataset.index = outletIndex;
    div.innerHTML = `
        <div class="card" style="position: relative; padding: 16px; margin-bottom: 16px;">
            <!-- Tombol Hapus -->
            <button type="button" class="btn btn-danger btn-sm" onclick="hapusOutlet(this)" style="
                position: absolute;
                top: 12px;
                right: 12px;
                padding: 4px 8px;
                font-size: 0.8em;
                border-radius: 6px;
            ">×</button>

            <!-- Field Outlet -->
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 2fr; gap: 16px;">
                <div class="form-group">
                    <label>Nama Outlet</label>
                    <input type="text" name="outlet[${outletIndex}][nama_outlet]" class="form-control" placeholder="Nama outlet" required>
                </div>
                <div class="form-group">
                    <label>Nomor HP Pemilik Outlet</label>
                    <input type="text" name="outlet[${outletIndex}][hp_pemilik]" class="form-control" placeholder="Opsional">
                </div>
                <div class="form-group">
                    <label>Jumlah Donasi</label>
                    <input 
                        type="number" 
                        name="outlet[${outletIndex}][jumlah_donasi]" 
                        class="form-control" 
                        placeholder="Contoh: 500000" 
                        min="0" 
                        step="1" 
                        style="text-align: right;" 
                        required>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <input type="text" name="outlet[${outletIndex}][keterangan]" class="form-control" placeholder="Opsional">
                </div>
            </div>
        </div>
    `;
    container.appendChild(div);
});

// Hapus outlet
function hapusOutlet(button) {
    const outletItems = document.querySelectorAll('.outlet-item');
    if (outletItems.length > 1) {
        button.closest('.outlet-item').remove();
    } else {
        alert('Minimal satu outlet harus ada.');
    }
}
</script>