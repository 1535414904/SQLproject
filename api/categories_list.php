<?php
session_save_path(dirname(__DIR__) . '/sessions');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/../db.php';

$res = $conn->query("SELECT category_id, name FROM categories ORDER BY category_id");
$data = [];

while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
