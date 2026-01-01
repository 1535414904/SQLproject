<?php
session_save_path(dirname(__DIR__) . '/sessions');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

require_once __DIR__ . '/../db.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = (int)($data['category_id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    exit;
}

$check = $conn->query(
    "SELECT COUNT(*) AS c FROM transactions WHERE category_id=$id"
);
$count = $check->fetch_assoc()['c'];

if ($count > 0) {
    http_response_code(409);
    echo json_encode(["error" => "已有記帳資料使用此分類"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM categories WHERE category_id=?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "ok"]);
} else {
    http_response_code(500);
}
$stmt->close();
