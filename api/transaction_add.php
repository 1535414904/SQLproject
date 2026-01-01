<?php
session_save_path(dirname(__DIR__) . '/sessions');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "未登入"]);
    exit;
}

require_once __DIR__ . '/../db.php';

$data = json_decode(file_get_contents("php://input"), true);

$user_id     = $_SESSION['user_id'];
$amount      = $data['amount'] ?? null;
$category_id = $data['category_id'] ?? null;
$type        = $data['type'] ?? null;
$date        = $data['date'] ?? date('Y-m-d');
$note        = $data['note'] ?? '';

if (!$amount || !$category_id || !$type) {
    http_response_code(400);
    echo json_encode(["error" => "資料不完整"]);
    exit;
}

$stmt = $conn->prepare(
  "INSERT INTO transactions (user_id, category_id, type, amount, note, date)
   VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
  "iiidss",
  $user_id,
  $category_id,
  $type,
  $amount,
  $note,
  $date
);

if ($stmt->execute()) {
    echo json_encode(["status" => "ok"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "新增失敗"]);
}
$stmt->close();
