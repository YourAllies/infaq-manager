<?php
// panduan.php
require_once 'config/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Daftar isi (anchor link)
$daftar_isi = [
    'login' => 'Login & Keamanan',
    'dashboard' => 'Dashboard Utama',
    'kotak-infaq' => 'Input Kotak Infaq',
    'donasi-barang' => 'Input Donasi Barang',
    'laporan-realtime' => 'Laporan Realtime',
    'efektivitas' => 'Laporan Efektivitas',
    'rekap-tahunan' => 'Rekap Laporan Tahunan',
    'kelola-petugas' => 'Kelola Petugas',
    'kelola-wilayah' => 'Kelola Wilayah',
    'profil' => 'Profil & Ganti Password',
    'backup' => 'Backup Database',
    'logout' => 'Logout'
];
?>

<?php include 'includes/header.php'; ?>

<main class="content">
    <h2>Panduan Penggunaan Aplikasi</h2>

    <div style="background: hsl(240, 12%, 16%); padding: 20px; border-radius: 10px; border: 1px solid hsl(240, 10%, 30%);">
        <p style="color: hsl(0,0%,70%); font-size: 0.95em; margin: 0;">
            Panduan ini menjelaskan seluruh fitur aplikasi <strong>Infaq Manager</strong> secara lengkap. Gunakan daftar isi untuk navigasi cepat.
        </p>
    </div>

    <!-- Daftar Isi 2 Kolom -->
    <div style="margin: 30px 0;">
        <h3>📋 Daftar Isi</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-top: 16px;">
            <?php $half = ceil(count($daftar_isi) / 2); ?>
            <?php $i = 0; ?>
            <?php foreach ($daftar_isi as $id => $judul): ?>
                <?php if ($i === $half): ?>
                    </div><div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-top: 16px;">
                <?php endif; ?>
                <a href="#<?= $id ?>" style="color: hsl(150, 45%, 42%); text-decoration: none;">
                    <?= $judul ?>
                </a>
                <?php $i++; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Konten Panduan -->
    <div style="line-height: 1.8; color: hsl(0,0%,90%); font-size: 0.95em;">

        <!-- Login -->
        <div id="login" class="section" style="margin-bottom: 30px; padding: 16px; border-left: 4px solid hsl(210, 60%, 40%);">
            <h3 style="color: hsl(210, 60%, 60%); margin-top: 0;">1. Login & Keamanan</h3>
            <p>Login dilakukan melalui halaman <strong>login.php</strong>. Masukkan username dan password yang telah diberikan oleh administrator.</p>
            <ul>
                <li><strong>Ingat Saya:</strong> Centang untuk menyimpan username di cookie (30 hari).</li>
                <li><strong>Lupa Password:</strong> Klik tautan untuk mengatur ulang password (jika fitur sudah aktif).</li>
                <li><strong>Sesi:</strong> Otomatis keluar setelah 4 jam tidak aktif.</li>
            </ul>
        </div>

        <!-- Dashboard -->
        <div id="dashboard" class="section" style="margin-bottom: 30px; padding: 16px; border-left: 4px solid hsl(120, 40%, 40%);">
            <h3 style="color: hsl(120, 40%, 60%); margin-top: 0;">2. Dashboard Utama</h3>
            <p>Halaman utama yang menampilkan ringkasan data penting:</p>
            <ul>
                <li><strong>Kartu Statistik:</strong> Pemasukan bulan ini, jumlah outlet, nilai barang, petugas, dan wilayah.</li>
                <li><strong>Ringkasan Efektivitas:</strong> Jumlah kotak dimiliki, terambil, rata-rata, dan level efektivitas.</li>
                <li><strong>Chart:</strong> Pemasukan per bulan, perbandingan tahunan, dan top petugas.</li>
                <li><strong>Kalender Event:</strong> Menampilkan hari besar dan target donasi.</li>
                <li><strong>Akses Cepat:</strong> Tombol cepat ke semua fitur utama.</li>
            </ul>
        </div>

        <!-- Input Kotak Infaq -->
        <div id="kotak-infaq" class="section" style="margin-bottom: 30px; padding: 16px; border-left: 4px solid hsl(150, 45%, 40%);">
            <h3 style="color: hsl(150, 45%, 60%); margin-top: 0;">3. Input Kotak Infaq</h3>
            <p>Gunakan halaman <strong>kotak-infaq.php</strong> untuk mencatat pengambilan kotak infaq.</p>
            <ul>
                <li>Isi <strong>Nama Outlet</strong>, <strong>HP Pemilik</strong>, <strong>Wilayah</strong>, <strong>Petugas</strong>.</li>
                <li>Masukkan <strong>Jumlah Donasi</strong> dan <strong>Keterangan</strong> (opsional).</li>
                <li>Gunakan <strong>Flatpickr</strong> untuk memilih tanggal.</li>
                <li>Klik <strong>Simpan</strong> untuk menyimpan data.</li>
            </ul>
        </div>

        <!-- Donasi Barang -->
        <div id="donasi-barang" class="section" style="margin-bottom: 30px; padding: 16px; border-left: 4px solid hsl(45, 60%, 40%);">
            <h3 style="color: hsl(45, 60%, 60%); margin-top: 0;">4. Input Donasi Barang</h3>
            <p>Gunakan <strong>donasi-barang.php</strong> untuk mencatat donasi non-tunai.</p>
            <ul>
                <li>Isi <strong>Nama Barang</strong>, <strong>Jumlah</strong>, <strong>Nilai Rupiah</strong>.</li>
                <li>Pilih <strong>Outlet</strong> dan <strong>Petugas</strong>.</li>
                <li>Gunakan <strong>Tanggal</strong> sesuai waktu pengambilan.</li>
                <li>Data akan muncul di laporan dan dashboard.</li>
            </ul>
        </div>

        <!-- Laporan Realtime -->
        <div id="laporan-realtime" class="section" style="margin-bottom: 30px; padding: 16px; border-left: 4px solid hsl(200, 50%, 40%);">
            <h3 style="color: hsl(200, 50%, 60%); margin-top: 0;">5. Laporan Realtime</h3>
            <p>Halaman <strong>laporan-realtime.php</strong> menampilkan semua entri secara langsung.</p>
            <ul>
                <li>Gunakan <strong>filter</strong> untuk mencari berdasarkan tanggal, outlet, petugas, dll.</li>
                <li>Data ditampilkan per baris dengan detail lengkap.</li>
                <li>Tombol <strong>Export Excel</strong> untuk ekspor data.</li>
            </ul>
        </div>

        <!-- Efektivitas -->
        <div id="efektivitas" class="section" style="margin-bottom: 30px; padding: 16px; border-left: 4px solid hsl(160, 50%, 40%);">
            <h3 style="color: hsl(160, 50%, 60%); margin-top: 0;">6. Laporan Efektivitas</h3>
            <p>Analisis kinerja petugas dan outlet melalui <strong>laporan-efektivitas-kotak-infaq.php</strong>.</p>
            <ul>
                <li>Filter berdasarkan <strong>Tahun Ajaran</strong>.</li>
                <li>Lihat <strong>Top 5</strong> dan <strong>Bottom 5</strong> outlet.</li>
                <li>Tabel rekap per petugas dengan jumlah kotak terambil per bulan.</li>
                <li>Gunakan <strong>Export Excel</strong> untuk laporan resmi.</li>
            </ul>
        </div>

        <!-- Rekap Tahunan -->
        <div id="rekap-tahunan" class="section" style="margin-bottom: 30px; padding: 16px; border-left: 4px solid hsl(240, 40%, 40%);">
            <h3 style="color: hsl(240, 40%, 60%); margin-top: 0;">7. Rekap Laporan Tahunan</h3>
            <p>Gunakan <strong>rekap-laporan-tahunan.php</strong> untuk melihat data per tahun ajaran.</p>
            <ul>
                <li>Pilih <strong>Tahun Ajaran</strong> dari dropdown.</li>
                <li>Lihat total pemasukan, jumlah outlet, dan rata-rata.</li>
                <li>Tersedia tabel bulanan dan ringkasan.</li>
            </ul>
        </div>

        <!-- Kelola Petugas -->
        <div id="kelola-petugas" class="section" style="margin-bottom: 30px; padding: 16px; border-left: 4px solid hsl(300, 50%, 40%);">
            <h3 style="color: hsl(300, 50%, 60%); margin-top: 0;">8. Kelola Petugas</h3>
            <p>Halaman <strong>kelola-petugas.php</strong> untuk manajemen data petugas.</p>
            <ul>
                <li><strong>Tambah:</strong> Masukkan nama dan pilih status (Aktif/Tidak Aktif).</li>
                <li><strong>Edit:</strong> Ubah nama petugas.</li>
                <li><strong>Non-Aktifkan:</strong> Hentikan akses tanpa menghapus data.</li>
                <li><strong>Export Excel:</strong> Ekspor daftar petugas.</li>
            </ul>
        </div>

        <!-- Kelola Wilayah -->
        <div id="kelola-wilayah" class="section" style="margin-bottom: 30px; padding: 16px; border-left: 4px solid hsl(330, 60%, 40%);">
            <h3 style="color: hsl(330, 60%, 60%); margin-top: 0;">9. Kelola Wilayah</h3>
            <p>Gunakan <strong>kelola-wilayah.php</strong> untuk mengatur wilayah operasional.</p>
            <ul>
                <li>Fungsi sama seperti kelola petugas: tambah, edit, non-aktifkan.</li>
                <li>Wilayah digunakan saat input data infaq atau donasi.</li>
            </ul>
        </div>

        <!-- Profil -->
        <div id="profil" class="section" style="margin-bottom: 30px; padding: 16px; border-left: 4px solid hsl(60, 60%, 40%);">
            <h3 style="color: hsl(60, 60%, 60%); margin-top: 0;">10. Profil & Ganti Password</h3>
            <p>Buka <strong>profil.php</strong> untuk mengelola akun Anda.</p>
            <ul>
                <li>Lihat <strong>Nama</strong>, <strong>Username</strong>, dan <strong>Role</strong>.</li>
                <li><strong>Ganti Password:</strong> Masukkan password lama dan baru.</li>
                <li>Gunakan fitur ini secara berkala untuk keamanan.</li>
            </ul>
        </div>

        <!-- Backup -->
        <div id="backup" class="section" style="margin-bottom: 30px; padding: 16px; border-left: 4px solid hsl(360, 60%, 40%);">
            <h3 style="color: hsl(360, 60%, 60%); margin-top: 0;">11. Backup Database</h3>
            <p>Gunakan <strong>backup.php</strong> (hanya admin) untuk backup manual.</p>
            <ul>
                <li>File disimpan di folder <code>/backups/</code> dengan format <code>backup_YYYYMMDD_HHIISS.sql</code>.</li>
                <li>Fitur otomatis bisa dijadwalkan via <strong>Cron Job</strong> atau <strong>Task Scheduler</strong>.</li>
                <li>Backup penting untuk mencegah kehilangan data.</li>
            </ul>
        </div>

        <!-- Logout -->
        <div id="logout" class="section" style="margin-bottom: 30px; padding: 16px; border-left: 4px solid hsl(0, 0%, 40%);">
            <h3 style="color: hsl(0, 0%, 60%); margin-top: 0;">12. Logout</h3>
            <p>Klik tombol <strong>Logout</strong> di pojok kanan atas untuk keluar dari sistem.</p>
            <ul>
                <li>Session akan dihancurkan.</li>
                <li>Anda harus login kembali untuk mengakses aplikasi.</li>
                <li>Disarankan logout setelah selesai bekerja.</li>
            </ul>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<!-- Gaya Internal Tambahan -->
<style>
.section h3 {
    border-bottom: 1px solid hsl(240, 10%, 30%);
    padding-bottom: 8px;
}
.section ul {
    margin: 12px 0;
    padding-left: 20px;
}
.section li {
    margin: 6px 0;
}
</style>