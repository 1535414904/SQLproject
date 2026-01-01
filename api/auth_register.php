<?php
session_save_path(dirname(__DIR__) . '/sessions');
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$data = json_decode(file_get_contents("php://input"), true);
$name = $data['name'];
$email = $data['email'];
$hash = hash('sha256', $data['password']);

$chk = $conn->prepare("SELECT 1 FROM users WHERE email=?");
$chk->bind_param("s",$email);
$chk->execute();
if ($chk->get_result()->num_rows>0){
  http_response_code(409);
  echo json_encode(["error"=>"Email 已存在"]);
  exit;
}

$stmt = $conn->prepare("INSERT INTO users(name,email,password) VALUES(?,?,?)");
$stmt->bind_param("sss",$name,$email,$hash);
$stmt->execute();
echo json_encode(["status"=>"ok"]);
