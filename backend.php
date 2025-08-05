<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Buka database SQLite
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
    $result = $db->query("SELECT * FROM transactions");
    $data = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data);
}

elseif ($action === 'add') {
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
}

elseif ($action === 'delete') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !isset($input['id'])) {
        echo json_encode(['error' => 'Missing ID']);
        exit;
    }

    $stmt = $db->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->bindValue(1, $input['id']);
    $stmt->execute();
    echo json_encode(['status' => 'deleted']);
}

elseif ($action === 'update') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !isset($input['id'])) {
        echo json_encode(['error' => 'Missing ID']);
        exit;
    }

    $stmt = $db->prepare("UPDATE transactions SET date = ?, type = ?, category = ?, description = ?, amount = ? WHERE id = ?");
    $stmt->bindValue(1, $input['date']);
    $stmt->bindValue(2, $input['type']);
    $stmt->bindValue(3, $input['category']);
    $stmt->bindValue(4, $input['description']);
    $stmt->bindValue(5, $input['amount']);
    $stmt->bindValue(6, $input['id']);
    $stmt->execute();
    echo json_encode(['status' => 'updated']);
}

elseif ($action === 'chart') {
    $result = $db->query("SELECT category, SUM(amount) as total FROM transactions WHERE type = 'expense' GROUP BY category");
    $data = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data);
}

elseif ($action === 'download_excel') {
    // Ganti header agar bisa download Excel
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=\"transactions.xlsx\"");

    require 'vendor/autoload.php'; // pastikan PHPSpreadsheet sudah di-install

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header kolom
    $sheet->fromArray(['ID', 'Date', 'Type', 'Category', 'Description', 'Amount'], NULL, 'A1');

    // Ambil data dari SQLite (pakai SQLite3, bukan PDO!)
    $result = $db->query("SELECT * FROM transactions");
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_NUM)) {
        $rows[] = $row;
    }

    // Isi data mulai dari baris ke-2
    $sheet->fromArray($rows, NULL, 'A2');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save("php://output");
    exit;
}

else {
    echo json_encode(['error' => 'Unknown action']);
}
?>
