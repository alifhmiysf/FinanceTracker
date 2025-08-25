<?php
require 'vendor/autoload.php'; // pastikan sudah install phpoffice/phpspreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$data = json_decode(file_get_contents('php://input'), true);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header
$sheet->setCellValue('A1', 'Date');
$sheet->setCellValue('B1', 'Type');
$sheet->setCellValue('C1', 'Category');
$sheet->setCellValue('D1', 'Description');
$sheet->setCellValue('E1', 'Amount');

// Data
$row = 2;
foreach ($data as $item) {
    $sheet->setCellValue('A' . $row, $item['date']);
    $sheet->setCellValue('B' . $row, $item['type']);
    $sheet->setCellValue('C' . $row, $item['category']);
    $sheet->setCellValue('D' . $row, $item['description']);
    $sheet->setCellValue('E' . $row, $item['amount']);
    $row++;
}

// Simpan file sementara
$filename = 'finance_export.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($filename);

// Unduh file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
readfile($filename);
unlink($filename); // hapus setelah download
exit;
