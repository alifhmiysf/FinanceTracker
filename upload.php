<?php
require 'vendor/autoload.php';
$db = new SQLite3(__DIR__ . '/data.db');

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "message" => "Upload gagal"]);
    exit;
}

$tmpFile = $_FILES['file']['tmp_name'];
$spreadsheet = IOFactory::load($tmpFile);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();

array_shift($rows); // skip header

$count = 0;
foreach ($rows as $row) {
    $date        = $row[0] ?? '';
    $type        = $row[1] ?? '';
    $category    = $row[2] ?? '';
    $description = $row[3] ?? '';
    $amount      = $row[4] ?? 0;

    if ($date && $type && $category && $description && $amount > 0) {
        $stmt = $db->prepare("INSERT OR REPLACE INTO transactions (date, type, category, description, amount) 
                              VALUES (?, ?, ?, ?, ?)");
                              
        $stmt->bindValue(1, $date);
        $stmt->bindValue(2, $type);
        $stmt->bindValue(3, $category);
        $stmt->bindValue(4, $description);
        $stmt->bindValue(5, $amount);
        $stmt->execute();
        $count++;
    }
}

echo json_encode(["success" => true, "inserted" => $count]);
