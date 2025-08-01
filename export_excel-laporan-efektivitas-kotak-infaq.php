<?php
// export_excel-laporan-efektivitas-kotak-infaq.php
require_once 'config/config.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Ambil parameter
$tahun_ajaran = $_GET['tahun_ajaran'] ?? '';
if (empty($tahun_ajaran)) {
    die("Tahun ajaran tidak ditemukan.");
}
list($awal, $akhir) = explode('/', $tahun_ajaran);
$tanggal_dari = "$awal-07-01";
$tanggal_sampai = "$akhir-06-30";

// Daftar bulan (Juli - Juni)
$bulan_list = [
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober',
    11 => 'November', 12 => 'Desember',
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni'
];

// Query: Semua data
$sql = "
    SELECT 
        i.petugas_id,
        p.nama_petugas,
        i.nama_outlet,
        i.jumlah_donasi,
        MONTH(i.tanggal) as bulan
    FROM infaq i
    LEFT JOIN petugas p ON i.petugas_id = p.id
    WHERE i.tanggal >= ? AND i.tanggal <= ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$tanggal_dari, $tanggal_sampai]);
$data = $stmt->fetchAll();

// Hitung statistik umum
$outlets = array_unique(array_column($data, 'nama_outlet'));
$jumlah_kotak_dimiliki = count($outlets);
$jumlah_kotak_terambil = count($data);
$total_dana = array_sum(array_column($data, 'jumlah_donasi'));
$rata_per_pengambilan = $jumlah_kotak_terambil > 0 ? $total_dana / $jumlah_kotak_terambil : 0;
$rata_per_outlet = $jumlah_kotak_dimiliki > 0 ? $total_dana / $jumlah_kotak_dimiliki : 0;
$efektivitas = $rata_per_outlet >= 500000 ? 'Tinggi' : ($rata_per_outlet >= 200000 ? 'Sedang' : 'Rendah');

// Rekap per petugas
$petugas_data = [];
$stmt = $pdo->query("SELECT id, nama_petugas FROM petugas WHERE is_active = 1 ORDER BY nama_petugas");
foreach ($stmt as $p) {
    $petugas_data[$p['id']] = [
        'nama_petugas' => $p['nama_petugas'],
        'per_bulan' => array_fill_keys(array_keys($bulan_list), 0),
        'outlets' => []
    ];
}

foreach ($data as $d) {
    $pid = $d['petugas_id'];
    if (!isset($petugas_data[$pid])) continue;
    if (isset($petugas_data[$pid]['per_bulan'][$d['bulan']])) {
        $petugas_data[$pid]['per_bulan'][$d['bulan']]++;
    }
    if (!in_array($d['nama_outlet'], $petugas_data[$pid]['outlets'])) {
        $petugas_data[$pid]['outlets'][] = $d['nama_outlet'];
    }
}

// Peringkat outlet
$per_outlet = [];
foreach ($data as $d) {
    $key = $d['nama_outlet'];
    if (!isset($per_outlet[$key])) {
        $per_outlet[$key] = [
            'nama_outlet' => $key,
            'total_donasi' => 0,
            'petugas' => []
        ];
    }
    $per_outlet[$key]['total_donasi'] += $d['jumlah_donasi'];
    if (!in_array($d['nama_petugas'], $per_outlet[$key]['petugas'])) {
        $per_outlet[$key]['petugas'][] = $d['nama_petugas'];
    }
}
uasort($per_outlet, function($a, $b) { return $b['total_donasi'] <=> $a['total_donasi']; });
$top_5 = array_slice($per_outlet, 0, 5);
$bottom_5 = array_slice($per_outlet, -5, 5);

// Buat Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Efektivitas");

// Judul
$sheet->setCellValue('A1', 'Laporan Efektivitas Kotak Infaq');
$sheet->mergeCells('A1:P1');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2D5016']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// Tahun Ajaran
$sheet->setCellValue('A2', 'Tahun Ajaran:');$sheet->mergeCells('A2:P2');
$sheet->setCellValue('A3', $tahun_ajaran);$sheet->mergeCells('A3:P3');

// Statistik
$row = 4;
$sheet->setCellValue("B{$row}", 'Jumlah Kotak Dimiliki');
$sheet->setCellValue("C{$row}", $jumlah_kotak_dimiliki);
$sheet->setCellValue("D{$row}", 'Jumlah Kotak Terambil');
$sheet->setCellValue("E{$row}", $jumlah_kotak_terambil);
$sheet->setCellValue("F{$row}", 'Dana Terkumpul');
$sheet->setCellValue("G{$row}", $total_dana);
$sheet->setCellValue("H{$row}", 'Rata/Kotak');
$sheet->setCellValue("I{$row}", $rata_per_pengambilan);
$sheet->setCellValue("J{$row}", 'Efektivitas');
$sheet->setCellValue("K{$row}", $efektivitas);

$sheet->getStyle("B{$row}:K{$row}")->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E6F3E6']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);
$sheet->getStyle("F{$row}:H{$row}")->getNumberFormat()->setFormatCode('#,##0');

// Peringkat Top 5
$row += 2;
$sheet->setCellValue("A{$row}", '🏆 Top 5 Outlet Terbaik');
$sheet->mergeCells("A{$row}:D{$row}");
$sheet->getStyle("A{$row}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F7A3A']]
]);

$row++;
$sheet->setCellValue("A{$row}", 'No');
$sheet->setCellValue("B{$row}", 'Outlet');
$sheet->setCellValue("C{$row}", 'Petugas');
$sheet->setCellValue("D{$row}", 'Donasi');

$sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F7A3A']]
]);

$no = 1;
foreach ($top_5 as $t) {
    $row++;
    $sheet->setCellValue("A{$row}", $no++);
    $sheet->setCellValue("B{$row}", $t['nama_outlet']);
    $sheet->setCellValue("C{$row}", implode(', ', $t['petugas']));
    $sheet->setCellValue("D{$row}", $t['total_donasi']);
    $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0');
}

// Peringkat Bottom 5
$sheet->setCellValue("F{$row}", '📉 Outlet Kurang Efektif');
$sheet->mergeCells("F{$row}:I{$row}");
$sheet->getStyle("F{$row}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B22222']]
]);

$sheet->setCellValue("F{$row}", 'No');
$sheet->setCellValue("G{$row}", 'Outlet');
$sheet->setCellValue("H{$row}", 'Petugas');
$sheet->setCellValue("I{$row}", 'Donasi');

$sheet->getStyle("F{$row}:I{$row}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B22222']]
]);

$no = $jumlah_kotak_dimiliki - count($bottom_5) + 1;
foreach ($bottom_5 as $b) {
    $row++;
    $sheet->setCellValue("F{$row}", $no++);
    $sheet->setCellValue("G{$row}", $b['nama_outlet']);
    $sheet->setCellValue("H{$row}", implode(', ', $b['petugas']));
    $sheet->setCellValue("I{$row}", $b['total_donasi']);
    $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode('#,##0');
}

// Tabel Rekap Petugas
$row += 2;
$sheet->setCellValue("A{$row}", 'Rekap Petugas (Tahun Ajaran: ' . $tahun_ajaran . ')');
$sheet->mergeCells("A{$row}:O{$row}");
$sheet->getStyle("A{$row}")->applyFromArray([
    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F7A3A']]
]);

$row++;
$col = 'A';
$sheet->setCellValue($col++ . $row, 'No');
$sheet->setCellValue($col++ . $row, 'Nama Petugas');
$sheet->setCellValue($col++ . $row, 'Kotak Dimiliki');
foreach ($bulan_list as $nama) {
    $sheet->setCellValue($col++ . $row, $nama);
}
$sheet->setCellValue($col . $row, 'Total');

$sheet->getStyle("A{$row}:{$col}{$row}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F7A3A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

// Data Tabel
$startRow = $row + 1;
$no = 1;
foreach ($petugas_data as $id => $p) {
    $row++;
    $total = array_sum($p['per_bulan']);
    $sheet->setCellValue("A{$row}", $no++);
    $sheet->setCellValue("B{$row}", $p['nama_petugas']);
    $sheet->setCellValue("C{$row}", count($p['outlets']));
    $c = 'D';
    foreach ($bulan_list as $bulan => $nama) {
        $sheet->setCellValue($c . $row, $p['per_bulan'][$bulan]);
        $c++;
    }
    $sheet->setCellValue($c . $row, $total);
    $sheet->getStyle("{$c}{$row}")->getNumberFormat()->setFormatCode('#,##0');
}

// Total Akhir
$row++;
$sheet->setCellValue("A{$row}", '');
$sheet->setCellValue("B{$row}", 'TOTAL');
$sheet->setCellValue("C{$row}", '');
$c = 'D';
foreach ($bulan_list as $bulan => $nama) {
    $sum = 0;
    foreach ($petugas_data as $p) {
        $sum += $p['per_bulan'][$bulan];
    }
    $sheet->setCellValue($c . $row, $sum);
    $c++;
}
$sheet->setCellValue($c . $row, $jumlah_kotak_terambil);
$sheet->getStyle("A{$row}:{$c}{$row}")->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E6F3E6']]
]);

// Format Angka
for ($r = $startRow; $r <= $row; $r++) {
    for ($c = 'D'; $c <= 'O'; $c++) {
        $sheet->getStyle("$c$r")->getNumberFormat()->setFormatCode('#,##0');
    }
}

// Auto Size Kolom
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('C')->setWidth(14);
foreach (range('D', 'O') as $col) {
    $sheet->getColumnDimension($col)->setWidth(10);
}

// Output
$filename = "Laporan_Efektivitas_Kotak_Infaq_{$tahun_ajaran}_" . date('Y-m-d') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;