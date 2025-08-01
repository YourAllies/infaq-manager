<?php
// export_excel-rekap-laporan-tahunan.php
require_once 'config/config.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Color; // ✅ Tambahkan use

// Ambil parameter
$tahun_ajaran = $_GET['tahun_ajaran'] ?? '';
if (empty($tahun_ajaran)) {
    die("Tahun ajaran tidak ditemukan.");
}

list($awal, $akhir) = explode('/', $tahun_ajaran);
$tanggal_dari = "$awal-07-01";
$tanggal_sampai = "$akhir-06-30";

// Tahun lalu
$prev_awal = $awal - 1;
$prev_akhir = $akhir - 1;
$prev_tanggal_dari = "$prev_awal-07-01";
$prev_tanggal_sampai = "$prev_akhir-06-30";

// Hitung total tahun ini dan tahun lalu
$stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_donasi), 0) FROM infaq WHERE tanggal >= ? AND tanggal <= ?");
$stmt->execute([$tanggal_dari, $tanggal_sampai]);
$total_tahun_ini = (int)$stmt->fetchColumn();

$stmt->execute([$prev_tanggal_dari, $prev_tanggal_sampai]);
$total_tahun_lalu = (int)$stmt->fetchColumn();

// Hitung pertumbuhan
$pertumbuhan = $total_tahun_lalu > 0 
    ? (($total_tahun_ini - $total_tahun_lalu) / $total_tahun_lalu) * 100 
    : ($total_tahun_ini > 0 ? 100 : 0);
$pertumbuhan = round($pertumbuhan, 1);

// Daftar bulan (Juli - Juni)
$bulan_list = [
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober',
    11 => 'November', 12 => 'Desember',
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni'
];

// Ambil semua petugas aktif
$stmt = $pdo->query("SELECT id, nama_petugas FROM petugas WHERE is_active = 1 ORDER BY nama_petugas");
$petugas_list = $stmt->fetchAll();

// Query: Rekap per petugas per bulan
$sql = "
    SELECT petugas_id, MONTH(tanggal) as bulan, SUM(jumlah_donasi) as total
    FROM infaq 
    WHERE tanggal >= ? AND tanggal <= ?
    GROUP BY petugas_id, bulan
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$tanggal_dari, $tanggal_sampai]);
$data_raw = $stmt->fetchAll();

// Konversi ke array
$data = [];
foreach ($data_raw as $row) {
    $data[$row['petugas_id']][$row['bulan']] = $row['total'];
}

// Hitung total
$total_per_bulan = array_fill_keys(array_keys($bulan_list), 0);
$total_per_petugas = [];
$total_keseluruhan = 0;

foreach ($petugas_list as $p) {
    $total = 0;
    foreach ($bulan_list as $bulan => $nama) {
        $nominal = $data[$p['id']][$bulan] ?? 0;
        $total += $nominal;
        $total_per_bulan[$bulan] += $nominal;
    }
    $total_per_petugas[$p['id']] = $total;
    $total_keseluruhan += $total;
}

// Buat Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// --- ✅ Fix: Ganti / dengan - di nama sheet ---
$sheet_title = str_replace('/', '-', "Rekap $tahun_ajaran");
$sheet_title = substr($sheet_title, 0, 31); // Maks 31 karakter
$sheet->setTitle($sheet_title);

// Judul
$sheet->setCellValue('A1', 'Rekap Laporan Tahunan Kotak Infaq');
$sheet->mergeCells('A1:M1');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2D5016']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// Tahun Ajaran
$sheet->setCellValue('A2', 'Tahun Ajaran:');
$sheet->setCellValue('B2', $tahun_ajaran);

// Ringkasan Statistik
$sheet->setCellValue('B4', 'Total Tahun Ini (Rp)');
$sheet->setCellValue('C4', $total_tahun_ini);
$sheet->setCellValue('B5', 'Total Tahun Lalu (Rp)');
$sheet->setCellValue('C5', $total_tahun_lalu);
$sheet->setCellValue('B6', 'Pertumbuhan (%)');
$sheet->setCellValue('C6', $pertumbuhan . '%');

$sheet->getStyle('B4:C6')->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E6F3E6']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);
$sheet->getStyle('B4:B5')->getNumberFormat()->setFormatCode('#,##0');

// --- ✅ Fix: setColor() dengan objek Color ---
$colorCode = $pertumbuhan >= 0 ? 'FF006400' : 'FFB22222'; // FF + hex
$sheet->getStyle('B6')->getFont()->setColor(new Color($colorCode));

// Header Tabel
$row = 8;
$sheet->setCellValue("A{$row}", 'No');
$sheet->setCellValue("B{$row}", 'Nama Petugas');
$col = 'C';
foreach ($bulan_list as $nama) {
    $sheet->setCellValue($col . $row, $nama);
    $col++;
}
$sheet->setCellValue($col . $row, 'Total');

$sheet->getStyle("A{$row}:{$col}{$row}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F7A3A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

// Data Tabel
$startData = $row + 1;
$no = 1;
foreach ($petugas_list as $p) {
    $row++;
    $sheet->setCellValue("A{$row}", $no++);
    $sheet->setCellValue("B{$row}", $p['nama_petugas']);
    
    $total = 0;
    $col = 'C';
    foreach ($bulan_list as $bulan => $nama) {
        $nominal = $data[$p['id']][$bulan] ?? 0;
        $sheet->setCellValue($col . $row, $nominal);
        $total += $nominal;
        $col++;
    }
    $sheet->setCellValue($col . $row, $total);
}

// Total Akhir
$row++;
$sheet->setCellValue("A{$row}", '');
$sheet->setCellValue("B{$row}", 'TOTAL');
$col = 'C';
foreach ($bulan_list as $bulan => $nama) {
    $sheet->setCellValue($col . $row, $total_per_bulan[$bulan]);
    $col++;
}
$sheet->setCellValue($col . $row, $total_keseluruhan);

$sheet->getStyle("A{$row}:{$col}{$row}")->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E6F3E6']]
]);

// Format Angka
for ($r = $startData; $r <= $row; $r++) {
    for ($c = 'C'; $c <= $col; $c++) {
        $sheet->getStyle("$c$r")->getNumberFormat()->setFormatCode('#,##0');
    }
}

// Auto Size Kolom
$sheet->getColumnDimension('B')->setWidth(20);
for ($i = 0; $i < count($bulan_list) + 1; $i++) {
    $sheet->getColumnDimension(chr(67 + $i))->setWidth(12);
}

// Output
$filename = "Rekap_Kotak_Infaq_$tahun_ajaran.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;