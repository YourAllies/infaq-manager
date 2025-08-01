<?php
// export_laporan-donasi-barang.php
require_once 'config/config.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Ambil filter
$tanggal_dari = $_GET['tanggal_dari'] ?? '';
$tanggal_sampai = $_GET['tanggal_sampai'] ?? '';
$nama_donatur = $_GET['nama_donatur'] ?? '';
$jenis_barang = $_GET['jenis_barang'] ?? '';
$no_hp = $_GET['no_hp'] ?? '';

// Query data (sama seperti di laporan)
$sql = "
    SELECT 
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

$sql .= " ORDER BY tanggal DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Buat Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Laporan Barang');

// Judul
$sheet->setCellValue('A1', 'Laporan Donasi Barang');
$sheet->mergeCells('A1:H1');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2D5016']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// Filter
$row = 3;
if ($tanggal_dari || $tanggal_sampai) {
    $dari = $tanggal_dari ? date('d/m/Y', strtotime($tanggal_dari)) : '...';
    $sampai = $tanggal_sampai ? date('d/m/Y', strtotime($tanggal_sampai)) : '...';
    $sheet->setCellValue("A{$row}", 'Rentang Tanggal:');
    $sheet->setCellValue("B{$row}", "$dari hingga $sampai");
    $row++;
}
if ($nama_donatur) {
    $sheet->setCellValue("A{$row}", 'Nama Donatur:');
    $sheet->setCellValue("B{$row}", $nama_donatur);
    $row++;
}
if ($jenis_barang) {
    $sheet->setCellValue("A{$row}", 'Jenis Barang:');
    $sheet->setCellValue("B{$row}", $jenis_barang);
    $row++;
}
if ($no_hp) {
    $sheet->setCellValue("A{$row}", 'No HP:');
    $sheet->setCellValue("B{$row}", $no_hp);
    $row++;
}

$row += 1;

// Header
$headers = ['Tanggal', 'Nama Donatur', 'Alamat', 'No HP', 'Jenis Barang', 'Jumlah & Satuan', 'Nilai (Rp)', 'Keterangan'];
$columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

foreach ($headers as $i => $header) {
    $sheet->setCellValue("{$columns[$i]}{$row}", $header);
}

$sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F7A3A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
    ]
]);

// Data
$startRow = $row + 1;
$total_nilai = 0;

foreach ($data as $item) {
    $sheet->setCellValue("A{$startRow}", $item['tanggal']);
    $sheet->setCellValue("B{$startRow}", $item['nama_donatur']);
    $sheet->setCellValue("C{$startRow}", $item['alamat']);
    $sheet->setCellValue("D{$startRow}", $item['no_hp'] ?: '-');
    $sheet->setCellValue("E{$startRow}", $item['jenis_barang']);
    $sheet->setCellValue("F{$startRow}", $item['jumlah_satuan']);
    $sheet->setCellValue("G{$startRow}", $item['nilai_rupiah']);
    $sheet->setCellValue("H{$startRow}", $item['keterangan'] ?: '-');
    $total_nilai += $item['nilai_rupiah'];
    $startRow++;
}

// Format angka
$sheet->getStyle("G5:G{$startRow}")->getNumberFormat()->setFormatCode('#,##0');

// Total
if (count($data) > 0) {
    $sheet->setCellValue("F{$startRow}", 'TOTAL');
    $sheet->setCellValue("G{$startRow}", $total_nilai);
    $sheet->getStyle("F{$startRow}:G{$startRow}")->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E6F3E6']]
    ]);
    $sheet->getStyle("G{$startRow}")->getNumberFormat()->setFormatCode('#,##0');
}

// Auto size
foreach ($columns as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output
$filename = "Laporan_Donasi_Barang_" . date('Y-m-d') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;