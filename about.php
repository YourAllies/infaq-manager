<?php
// about.php
require_once 'config/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<?php include 'includes/header.php'; ?>

<main class="content">
    <h2>Tentang Aplikasi</h2>

    <div style="background: hsl(240, 12%, 16%); padding: 30px; border-radius: 12px; border: 1px solid hsl(240, 10%, 30%); max-width: 800px; margin: 0 auto; color: hsl(0,0%,90%);">
        <div style="text-align: center; margin-bottom: 25px;">
            <h3 style="color: hsl(150, 45%, 42%); margin: 0 0 10px 0;">Infaq Manager</h3>
            <p style="color: hsl(0,0%,60%); font-size: 0.95em; margin: 0;">Sistem Manajemen Kotak Infaq & Donasi</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; font-size: 0.95em;">
            <div>
                <strong>Versi:</strong> 1.0<br>
                <strong>Tahun Rilis:</strong> 2025<br>
                <strong>Lisensi:</strong> <a href ="">MIT Lisence</a>
            </div>
            <div>
                <strong>Developer:</strong> M. Ali Musthofa<br>
                <strong>Kontak:</strong> <a href="mailto:alimuzthofa.@gmail.com" style="color: hsl(210, 60%, 60%);">alimuzthofa.@gmail.com</a><br>
                <strong>Website:</strong> <a href="https://github.com/yourallies" target="_blank" style="color: hsl(210, 60%, 60%);">github.com/YourAllies</a>
            </div>
        </div>

        <div style="border-top: 1px solid hsl(240, 10%, 30%); padding-top: 20px;">
            <h4 style="color: hsl(210, 60%, 60%); margin: 0 0 10px 0;">Deskripsi</h4>
            <p style="color: hsl(0,0%,70%); line-height: 1.6; font-size: 0.95em;">
                Infaq Manager adalah aplikasi berbasis web yang dirancang khusus untuk membantu lembaga sosial, masjid, dan yayasan dalam mengelola data kotak infaq dan donasi barang secara digital, terpusat, dan efisien.
            </p>
            <p style="color: hsl(0,0%,70%); line-height: 1.6; font-size: 0.95em; margin-top: 10px;">
                Aplikasi ini menyediakan fitur input real-time, laporan efektivitas, manajemen petugas, dan backup otomatis untuk memastikan data aman dan mudah dilacak.
            </p>
        </div>

        <div style="margin-top: 25px; text-align: center; color: hsl(0,0%,60%); font-size: 0.85em;">
            &copy; <?= date('Y') ?> Infaq Manager. Dibuat dengan penuh dedikasi untuk kemajuan lembaga sosial.
        </div>
    </div>

    <!-- Tombol Kembali -->
    <div style="text-align: center; margin-top: 30px;">
        <a href="index.php" class="btn btn-secondary" style="padding: 10px 20px; font-size: 0.95em;">
            &larr; Kembali ke Dashboard
        </a>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<style>
    .content a {
        color: hsl(210, 60%, 60%);
        text-decoration: none;
    }
    .content a:hover {
        text-decoration: underline;
    }
</style>