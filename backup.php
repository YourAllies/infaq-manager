<?php
// backup.php
require_once 'config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

// Konfigurasi
$host = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$database = DB_NAME;
$backup_dir = __DIR__ . '/backups/';

// Buat folder jika belum ada
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Nama file
$filename = $backup_dir . 'backup_' . date('Ymd_His') . '.sql';

// 🔧 PATH LENGKAP ke mysqldump.exe (sesuaikan dengan lokasi XAMPP Anda)
$mysqldump_path = '"C:\xampp\mysql\bin\mysqldump.exe"'; // Gunakan petik agar aman dari spasi

// Perintah dengan path lengkap
$cmd = "$mysqldump_path --host=$host --user=$username --password=$password --no-tablespaces --single-transaction --routines --triggers \"$database\" > \"$filename\"";

// Eksekusi
exec($cmd . ' 2>&1', $output, $return_var);

if ($return_var === 0 && file_exists($filename)) {
    $_SESSION['success'] = "Backup berhasil! File: " . basename($filename);
} else {
    $error_msg = implode("\n", $output);
    $_SESSION['error'] = "Backup gagal: " . $error_msg;
}

header('Location: index.php');
exit;