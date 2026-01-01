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
$name = trim($data['name'] ?? '');

if ($name === '') {
    http_response_code(400);
    echo json_encode(["error" => "分類名稱不能為空"]);
    exit;
}

$check = $conn->prepare(
    "SELECT COUNT(*) AS c FROM categories WHERE LOWER(name)=LOWER(?)"
);
$check->bind_param("s", $name);
$check->execute();
$count = $check->get_result()->fetch_assoc()['c'];
$check->close();

if ($count > 0) {
    http_response_code(409);
    echo json_encode(["error" => "分類已存在"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
$stmt->bind_param("s", $name);

if ($stmt->execute()) {
    echo json_encode(["status" => "ok"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "新增失敗"]);
}
$stmt->close();
