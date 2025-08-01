<?php
// generate-pdf-dashboard.php
require_once 'config/config.php';

// Pastikan mpdf ada
$mpdfPath = $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
if (!file_exists($mpdfPath)) {
    die("MPDF tidak ditemukan. Jalankan: composer require mpdf/mpdf");
}
require_once $mpdfPath;

use Mpdf\Mpdf;

$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'orientation' => 'P'
]);

// Ambil data (sama seperti index.php)
$tahun_ini = date('Y');
$bulan_ini = (int)date('m');
$bulan_nama = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_donasi), 0) FROM infaq WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
$stmt->execute([$bulan_ini, $tahun_ini]);
$pemasukan_bulan_ini = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM petugas WHERE is_active = 1");
$jml_petugas = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM wilayah WHERE is_active = 1");
$jml_wilayah = (int)$stmt->fetchColumn();

// HTML
$html = '
<h1 style="text-align: center; color: #006400;">Dashboard Laporan Infaq</h1>
<p style="text-align: center; color: #555;">' . date('d F Y') . '</p>

<h2>Kartu Statistik</h2>
<table border="1" cellpadding="10" cellspacing="0" width="100%">
    <tr>
        <td><strong>Pemasukan Bulan Ini</strong><br>' . number_format($pemasukan_bulan_ini, 0, ',', '.') . '</td>
        <td><strong>Petugas Aktif</strong><br>' . $jml_petugas . '</td>
        <td><strong>Wilayah Aktif</strong><br>' . $jml_wilayah . '</td>
    </tr>
</table>

<p style="color: #777; font-size: 0.9em; text-align: center;">Dokumen ini di-generate otomatis oleh sistem.</p>
';

$mpdf->WriteHTML($html);
$mpdf->Output("Dashboard_Infaq_" . date('Y-m-d') . ".pdf", "D");