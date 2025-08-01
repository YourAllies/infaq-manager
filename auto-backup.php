<?php
// auto-backup.php - Backup otomatis via Task Scheduler

// Konfigurasi
$host = 'localhost';
$username = 'root';
$password = ''; // Sesuaikan jika ada password
$database = 'infaq_manager_app';
$backup_dir = __DIR__ . '/backups/';

// Buat folder jika belum ada
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Nama file: auto_backup_20250405.sql
$filename = $backup_dir . 'auto_backup_' . date('Ymd') . '.sql';

// Path lengkap ke mysqldump (penting di Windows)
$mysqldump = '"C:\xampp\mysql\bin\mysqldump.exe"'; // Sesuaikan path XAMPP

// Perintah backup
$cmd = "$mysqldump --host=$host --user=$username --password=$password --no-tablespaces --single-transaction $database > \"$filename\"";

// Eksekusi
$output = [];
$return_var = null;
exec($cmd . ' 2>&1', $output, $return_var);

// Log hasil
$log = date('Y-m-d H:i:s') . " - Backup " . ($return_var === 0 ? "BERHASIL" : "GAGAL") . "\n";
$log .= "File: $filename\n";
$log .= "Return: $return_var\n";
$log .= "Output: " . implode(" | ", $output) . "\n";
$log .= "--------------------------\n";

file_put_contents($backup_dir . 'backup-log.txt', $log, FILE_APPEND);

// Opsional: Hapus backup lebih dari 30 hari
array_map('unlink', glob($backup_dir . 'auto_backup_*.sql', 0) ?: []); // Hapus semua
// Atau gunakan: find + unlink untuk batasi jumlah

exit($return_var);