<?php
// export-excel.php
require_once 'config/config.php';
require_once 'vendor/autoload.php'; // Sesuaikan jika folder berbeda

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Ambil filter dari URL
$tanggal_dari = $_GET['tanggal_dari'] ?? '';
$tanggal_sampai = $_GET['tanggal_sampai'] ?? '';
$nama_outlet = $_GET['nama_outlet'] ?? '';
$hp_pemilik = $_GET['hp_pemilik'] ?? '';
$petugas_id = $_GET['petugas_id'] ?? '';
$wilayah_id = $_GET['wilayah_id'] ?? '';

// Ambil nama petugas (jika ada)
$nama_petugas = '';
if (!empty($petugas_id)) {
    $stmt = $pdo->prepare("SELECT nama_petugas FROM petugas WHERE id = ?");
    $stmt->execute([$petugas_id]);
    $p = $stmt->fetch();
    $nama_petugas = $p ? $p['nama_petugas'] : 'Tidak Diketahui';
}

// Query data
$sql = "
    SELECT 
        i.tanggal,
        i.nama_outlet,
        i.hp_pemilik,
        i.jumlah_donasi,
        i.keterangan,
        p.nama_petugas,
        w.nama_wilayah
    FROM infaq i
    LEFT JOIN petugas p ON i.petugas_id = p.id
    LEFT JOIN wilayah w ON i.wilayah_id = w.id
    WHERE 1=1
";

$params = [];

if (!empty($tanggal_dari)) {
    $sql .= " AND i.tanggal >= ?";
    $params[] = $tanggal_dari;
}
if (!empty($tanggal_sampai)) {
    $sql .= " AND i.tanggal <= ?";
    $params[] = $tanggal_sampai;
}
if (!empty($nama_outlet)) {
    $sql .= " AND i.nama_outlet LIKE ?";
    $params[] = '%' . $nama_outlet . '%';
}
if (!empty($hp_pemilik)) {
    $sql .= " AND i.hp_pemilik LIKE ?";
    $params[] = '%' . $hp_pemilik . '%';
}
if (!empty($petugas_id)) {
    $sql .= " AND i.petugas_id = ?";
    $params[] = $petugas_id;
}
if (!empty($wilayah_id)) {
    $sql .= " AND i.wilayah_id = ?";
    $params[] = $wilayah_id;
}

$sql .= " ORDER BY i.tanggal DESC, i.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Hitung tahun dari rentang tanggal
$tahun = '';
if ($tanggal_dari && $tanggal_sampai) {
    $tahun_dari = date('Y', strtotime($tanggal_dari));
    $tahun_sampai = date('Y', strtotime($tanggal_sampai));
    $tahun = ($tahun_dari == $tahun_sampai) ? $tahun_dari : "$tahun_dari - $tahun_sampai";
} elseif ($tanggal_dari) {
    $tahun = date('Y', strtotime($tanggal_dari));
} elseif ($tanggal_sampai) {
    $tahun = date('Y', strtotime($tanggal_sampai));
}

// Buat Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Laporan Infaq');

// === 1. Judul Utama ===
$sheet->setCellValue('A1', 'Laporan Pemasukan Kotak Infaq');
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2D5016']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// === 2. Informasi Filter ===
$row = 3;

// Rentang Tanggal
$tanggal_text = '-';
if ($tanggal_dari && $tanggal_sampai) {
    $dari = date('d/m/Y', strtotime($tanggal_dari));
    $sampai = date('d/m/Y', strtotime($tanggal_sampai));
    $tanggal_text = "$dari hingga $sampai";
} elseif ($tanggal_dari) {
    $dari = date('d/m/Y', strtotime($tanggal_dari));
    $tanggal_text = "$dari hingga ...";
} elseif ($tanggal_sampai) {
    $sampai = date('d/m/Y', strtotime($tanggal_sampai));
    $tanggal_text = "... hingga $sampai";
}
$sheet->setCellValue("A{$row}", 'Rentang tanggal yang dipilih:');
$sheet->setCellValue("B{$row}", $tanggal_text);
$row++;

// Tahun
if ($tahun) {
    $sheet->setCellValue("A{$row}", 'Tahun:');
    $sheet->setCellValue("B{$row}", $tahun);
    $row++;
}

// Petugas
if ($nama_petugas) {
    $sheet->setCellValue("A{$row}", 'Petugas:');
    $sheet->setCellValue("B{$row}", $nama_petugas);
    $row++;
}

// Spasi sebelum tabel
$row += 1;

// === 3. Header Tabel ===
$headers = ['No', 'Tanggal', 'Nama Outlet', 'HP Pemilik', 'Wilayah', 'Petugas', 'Jumlah Donasi', 'Keterangan'];
$columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

foreach ($headers as $i => $header) {
    $sheet->setCellValue("{$columns[$i]}{$row}", $header);
}

// Styling Header
$sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F7A3A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
    ]
]);

// === 4. Isi Data ===
$startRow = $row + 1;
$nomor = 1;
$total_donasi = 0;

foreach ($data as $item) {
    $sheet->setCellValue("A{$startRow}", $nomor++);
    $sheet->setCellValue("B{$startRow}", $item['tanggal']);
    $sheet->setCellValue("C{$startRow}", $item['nama_outlet']);
    $sheet->setCellValue("D{$startRow}", $item['hp_pemilik'] ?: '-');
    $sheet->setCellValue("E{$startRow}", $item['nama_wilayah']);
    $sheet->setCellValue("F{$startRow}", $item['nama_petugas']);
    $sheet->setCellValue("G{$startRow}", $item['jumlah_donasi']);
    $sheet->setCellValue("H{$startRow}", $item['keterangan'] ?: '-');

    $total_donasi += $item['jumlah_donasi'];
    $startRow++;
}

// Format Kolom Angka
$sheet->getStyle("G5:G{$startRow}")->getNumberFormat()->setFormatCode('#,##0');

// === 5. Total ===
if (count($data) > 0) {
    $sheet->setCellValue("F{$startRow}", 'TOTAL');
    $sheet->setCellValue("G{$startRow}", $total_donasi);
    $sheet->getStyle("F{$startRow}:G{$startRow}")->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E6F3E6']],
        'borders' => [
            'top' => ['borderStyle' => Border::BORDER_MEDIUM],
            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM]
        ]
    ]);
    $sheet->getStyle("G{$startRow}")->getNumberFormat()->setFormatCode('#,##0');
}

// === 6. Auto Size Kolom ===
foreach ($columns as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// === 7. Output ke Browser ===
$filename = "Laporan_Pemasukan_Kotak_Infaq_" . date('Y-m-d') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;