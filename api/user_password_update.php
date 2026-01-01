<?php
session_save_path(dirname(__DIR__) . '/sessions');
session_start();
header('Content-Type: application/json');
require_once __DIR__.'/../db.php';

$uid=$_SESSION['user_id'];
$d=json_decode(file_get_contents("php://input"),true);

$old=hash('sha256',$d['old']);
$new=hash('sha256',$d['new']);

$stmt=$conn->prepare("SELECT password FROM users WHERE user_id=?");
$stmt->bind_param("i",$uid);
$stmt->execute();
$pwd=$stmt->get_result()->fetch_assoc()['password'];

if($pwd!==$old){
  http_response_code(400);
  exit;
}

$u=$conn->prepare("UPDATE users SET password=? WHERE user_id=?");
$u->bind_param("si",$new,$uid);
$u->execute();
