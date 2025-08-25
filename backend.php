<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Composer autoload untuk PhpSpreadsheet
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Koneksi SQLite
$db = new SQLite3(__DIR__ . '/data.db');

// Buat tabel jika belum ada
$db->exec("CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date TEXT,
    type TEXT,
    category TEXT,
    description TEXT,
    amount REAL
)");

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    header('Content-Type: application/json');

    $where = [];
    $params = [];

    // Ambil filter
    $startDate = $_GET['startDate'] ?? '';
    $endDate   = $_GET['endDate'] ?? '';
    $type      = $_GET['type'] ?? '';
    $category  = $_GET['category'] ?? '';

    if ($startDate !== '') {
        $where[] = "date >= ?";
        $params[] = $startDate;
    }
    if ($endDate !== '') {
        $where[] = "date <= ?";
        $params[] = $endDate;
    }
    if ($type !== '') {
        $where[] = "type = ?";
        $params[] = $type;
    }
    if ($category !== '') {
        $where[] = "category = ?";
        $params[] = $category;
    }

    $sql = "SELECT * FROM transactions";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $stmt = $db->prepare($sql);
    foreach ($params as $i => $val) {
        $stmt->bindValue($i + 1, $val);
    }
    $result = $stmt->execute();

    $data = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data);

} elseif ($action === 'add') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) {
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO transactions (date, type, category, description, amount) VALUES (?, ?, ?, ?, ?)");
    $stmt->bindValue(1, $input['date']);
    $stmt->bindValue(2, $input['type']);
    $stmt->bindValue(3, $input['category']);
    $stmt->bindValue(4, $input['description']);
    $stmt->bindValue(5, $input['amount']);
    $stmt->execute();

    echo json_encode(['status' => 'success']);

} elseif ($action === 'delete') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !isset($input['id'])) {
        echo json_encode(['error' => 'Missing ID']);
        exit;
    }

    $stmt = $db->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->bindValue(1, $input['id']);
    $stmt->execute();
    echo json_encode(['status' => 'deleted']);

} elseif ($action === 'update') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !isset($input['id'])) {
        echo json_encode(['error' => 'Missing ID']);
        exit;
    }

    $stmt = $db->prepare("UPDATE transactions 
        SET date = ?, type = ?, category = ?, description = ?, amount = ? 
        WHERE id = ?");
    $stmt->bindValue(1, $input['date']);
    $stmt->bindValue(2, $input['type']);
    $stmt->bindValue(3, $input['category']);
    $stmt->bindValue(4, $input['description']);
    $stmt->bindValue(5, $input['amount']);
    $stmt->bindValue(6, $input['id']);
    $stmt->execute();

    echo json_encode(['status' => 'updated']);

} elseif ($action === 'chart') {
    header('Content-Type: application/json');

    $result = $db->query("SELECT category, SUM(amount) as total 
                          FROM transactions 
                          WHERE type = 'expense' 
                          GROUP BY category");
    $data = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data);

} elseif ($action === 'download_excel') {
    // Header untuk download Excel
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=\"transactions.xlsx\"");

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header kolom
    $sheet->fromArray(['ID', 'Date', 'Type', 'Category', 'Description', 'Amount'], NULL, 'A1');

    // Data
    $result = $db->query("SELECT * FROM transactions");
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_NUM)) {
        $rows[] = $row;
    }

    $sheet->fromArray($rows, NULL, 'A2');

    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");
    exit;

} elseif ($action === 'upload') {
    header('Content-Type: application/json');

    if (!isset($_FILES['file'])) {
        echo json_encode(["success" => false, "message" => "File tidak ditemukan"]);
        exit;
    }

    $filePath = $_FILES['file']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Kosongkan tabel lama
        $db->exec("DELETE FROM transactions");

        $inserted = 0;
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // skip header

            [$id, $date, $type, $category, $description, $amount] = $row;

            if (!$date || !$type || !$category || !$description || !$amount) {
                continue; // skip kosong
            }

            $stmt = $db->prepare("INSERT INTO transactions (date, type, category, description, amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->bindValue(1, $date);
            $stmt->bindValue(2, $type);
            $stmt->bindValue(3, $category);
            $stmt->bindValue(4, $description);
            $stmt->bindValue(5, (float)$amount);
            $stmt->execute();

            $inserted++;
        }

        echo json_encode(["success" => true, "message" => "Upload sukses! $inserted data dimasukkan."]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }

} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unknown action']);
}
