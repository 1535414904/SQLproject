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
$id   = (int)($data['category_id'] ?? 0);
$name = trim($data['name'] ?? '');

if ($id <= 0 || $name === '') {
    http_response_code(400);
    exit;
}

$check = $conn->prepare(
    "SELECT COUNT(*) AS c FROM categories 
     WHERE LOWER(name)=LOWER(?) AND category_id != ?"
);
$check->bind_param("si", $name, $id);
$check->execute();
$count = $check->get_result()->fetch_assoc()['c'];
$check->close();

if ($count > 0) {
    http_response_code(409);
    echo json_encode(["error" => "名稱已被使用"]);
    exit;
}

$stmt = $conn->prepare("UPDATE categories SET name=? WHERE category_id=?");
$stmt->bind_param("si", $name, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "ok"]);
} else {
    http_response_code(500);
}
$stmt->close();
