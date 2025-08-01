<?php
// donasi-barang.php
require_once 'config/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil filter tanggal (opsional, jika ingin default)
$tanggal = $_POST['tanggal'] ?? date('Y-m-d');

// Proses form saat disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $tanggal = $_POST['tanggal'] ?? '';
    $nama_donatur = trim($_POST['nama_donatur'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $barangs = $_POST['barang'] ?? [];

    foreach ($barangs as $barang) {
        $jenis_barang = trim($barang['jenis_barang'] ?? '');
        $jumlah_satuan = trim($barang['jumlah_satuan'] ?? '');
        $nilai_rupiah = (float)($barang['nilai_rupiah'] ?? 0);
        $keterangan = trim($barang['keterangan'] ?? '');

        if (empty($jenis_barang) || empty($jumlah_satuan)) {
            $errors[] = "Nama barang dan jumlah & satuan wajib diisi.";
            continue;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO donasi_barang (tanggal, nama_donatur, alamat, no_hp, jenis_barang, jumlah_satuan, nilai_rupiah, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $tanggal,
                $nama_donatur,
                $alamat,
                $no_hp ?: null,
                $jenis_barang,
                $jumlah_satuan,
                $nilai_rupiah,
                $keterangan ?: null
            ]);
        } catch (Exception $e) {
            $errors[] = "Gagal simpan barang: $jenis_barang";
        }
    }

    if (empty($errors)) {
        $_SESSION['success'] = "Data donasi barang berhasil disimpan!";
        header('Location: donasi-barang.php');
        exit;
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>

<?php include 'includes/header.php'; ?>

<main class="content">
    <h2>Donasi Barang</h2>

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
    <form method="POST" id="form-donasi-barang">
        <!-- Bagian 1: Informasi Donatur (1-4) -->
        <div class="card" style="padding: 16px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%); font-size: 1.1em;">Informasi Donatur</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <!-- Row 1: Tanggal, Nama Donatur -->
                <div class="form-group">
                    <label for="tanggal">1. Tanggal</label>
                    <input 
                        type="text" 
                        name="tanggal" 
                        id="tanggal" 
                        class="form-control" 
                        placeholder="Pilih tanggal"
                        value="<?= e($tanggal) ?>"
                        readonly
                    >
                    <input type="hidden" name="tanggal_hidden" id="tanggal_hidden" value="<?= e($tanggal) ?>">
                </div>
                <div class="form-group">
                    <label for="nama_donatur">2. Nama Donatur</label>
                    <input type="text" name="nama_donatur" id="nama_donatur" class="form-control" placeholder="Nama Donatur" required>
                </div>

                <!-- Row 2: Alamat, Nomor Telepon -->
                <div class="form-group">
                    <label for="alamat">3. Alamat</label>
                    <textarea name="alamat" id="alamat" class="form-control" rows="2" placeholder="Alamat Lengkap" required></textarea>
                </div>
                <div class="form-group">
                    <label for="no_hp">4. Nomor Telepon</label>
                    <input type="text" name="no_hp" id="no_hp" class="form-control" placeholder="Opsional (bisa diisi nanti)">
                </div>
            </div>
        </div>

        <!-- Bagian 2: Daftar Barang (5-8) -->
        <div id="barang-container">
            <h3 style="color: hsl(0, 0%, 90%); margin-bottom: 12px;">Daftar Barang</h3>

            <!-- Barang 1 (Default) -->
            <div class="barang-item" data-index="1">
                <div class="card" style="position: relative; padding: 16px; margin-bottom: 16px;">
                    <!-- Tombol Hapus -->
                    <button type="button" class="btn btn-danger btn-sm" onclick="hapusBarang(this)" style="
                        position: absolute;
                        top: 12px;
                        right: 12px;
                        padding: 4px 8px;
                        font-size: 0.8em;
                        border-radius: 6px;
                    ">×</button>

                    <!-- Grid 2 Kolom × 2 Baris -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <!-- Row 1: Nama Barang, Jumlah & Satuan -->
                        <div class="form-group">
                            <label for="jenis_barang_1">5. Nama Barang</label>
                            <input type="text" name="barang[1][jenis_barang]" id="jenis_barang_1" class="form-control" placeholder="Contoh: Beras, Minyak" required>
                        </div>
                        <div class="form-group">
                            <label for="jumlah_satuan_1">6. Jumlah & Satuan</label>
                            <input type="text" name="barang[1][jumlah_satuan]" id="jumlah_satuan_1" class="form-control" placeholder="Contoh: 10 Dus, 50 Kg" required>
                        </div>

                        <!-- Row 2: Nilai Rupiah, Keterangan -->
                        <div class="form-group">
                            <label for="nilai_rupiah_1">7. Nilai (Rp)</label>
                            <input type="number" name="barang[1][nilai_rupiah]" id="nilai_rupiah_1" class="form-control" placeholder="100000" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="keterangan_1">8. Keterangan</label>
                            <input type="text" name="barang[1][keterangan]" id="keterangan_1" class="form-control" placeholder="Opsional">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Tambah Barang -->
        <div style="margin: 20px 0;">
            <button type="button" class="btn btn-secondary" id="btn-tambah-barang">+ Tambah Barang</button>
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

// Tambah Barang
let barangIndex = 1;
document.getElementById('btn-tambah-barang').addEventListener('click', function () {
    barangIndex++;
    const container = document.getElementById('barang-container');

    const div = document.createElement('div');
    div.className = 'barang-item';
    div.dataset.index = barangIndex;
    div.innerHTML = `
        <div class="card" style="position: relative; padding: 16px; margin-bottom: 16px;">
            <!-- Tombol Hapus -->
            <button type="button" class="btn btn-danger btn-sm" onclick="hapusBarang(this)" style="
                position: absolute;
                top: 12px;
                right: 12px;
                padding: 4px 8px;
                font-size: 0.8em;
                border-radius: 6px;
            ">×</button>

            <!-- Grid 2×2 -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <!-- Row 1 -->
                <div class="form-group">
                    <label>5. Nama Barang</label>
                    <input type="text" name="barang[${barangIndex}][jenis_barang]" class="form-control" placeholder="Contoh: Beras, Minyak" required>
                </div>
                <div class="form-group">
                    <label>6. Jumlah & Satuan</label>
                    <input type="text" name="barang[${barangIndex}][jumlah_satuan]" class="form-control" placeholder="Contoh: 10 Dus, 50 Kg" required>
                </div>

                <!-- Row 2 -->
                <div class="form-group">
                    <label>7. Nilai (Rp)</label>
                    <input type="number" name="barang[${barangIndex}][nilai_rupiah]" class="form-control" placeholder="100000" min="0" value="0">
                </div>
                <div class="form-group">
                    <label>8. Keterangan</label>
                    <input type="text" name="barang[${barangIndex}][keterangan]" class="form-control" placeholder="Opsional">
                </div>
            </div>
        </div>
    `;
    container.appendChild(div);
});

function hapusBarang(button) {
    const items = document.querySelectorAll('.barang-item');
    if (items.length > 1) {
        button.closest('.barang-item').remove();
    } else {
        alert('Minimal satu barang harus ada.');
    }
}
</script>