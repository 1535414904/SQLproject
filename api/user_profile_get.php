<?php
session_save_path(dirname(__DIR__) . '/sessions');
session_start();
header('Content-Type: application/json');
require_once __DIR__.'/../db.php';

$uid=$_SESSION['user_id'];
$stmt=$conn->prepare("SELECT name,email FROM users WHERE user_id=?");
$stmt->bind_param("i",$uid);
$stmt->execute();
echo json_encode($stmt->get_result()->fetch_assoc());
