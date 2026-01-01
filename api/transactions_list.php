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

$user_id = $_SESSION['user_id'];

$where = "WHERE t.user_id = ?";
$params = [$user_id];
$types = "i";

if (!empty($_GET['keyword'])) {
    $where .= " AND t.note LIKE ?";
    $params[] = "%" . $_GET['keyword'] . "%";
    $types .= "s";
}
if (isset($_GET['type']) && ($_GET['type'] === '1' || $_GET['type'] === '2')) {
    $where .= " AND t.type = ?";
    $params[] = (int)$_GET['type'];
    $types .= "i";
}
if (!empty($_GET['category_id'])) {
    $where .= " AND t.category_id = ?";
    $params[] = (int)$_GET['category_id'];
    $types .= "i";
}
if (!empty($_GET['start_date'])) {
    $where .= " AND t.date >= ?";
    $params[] = $_GET['start_date'];
    $types .= "s";
}
if (!empty($_GET['end_date'])) {
    $where .= " AND t.date <= ?";
    $params[] = $_GET['end_date'];
    $types .= "s";
}

$sql = "
SELECT 
  t.transaction_id,
  t.amount,
  t.type,
  t.note,
  t.date,
  c.name AS category
FROM transactions t
JOIN categories c ON t.category_id = c.category_id
$where
ORDER BY t.date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
$total_income = 0;
$total_expense = 0;

while ($row = $res->fetch_assoc()) {
    if ($row['type'] == 2) $total_income += $row['amount'];
    if ($row['type'] == 1) $total_expense += $row['amount'];
    $data[] = $row;
}
$stmt->close();

echo json_encode([
    "summary" => [
        "income" => $total_income,
        "expense" => $total_expense,
        "balance" => $total_income - $total_expense
    ],
    "records" => $data
]);
