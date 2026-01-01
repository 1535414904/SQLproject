<?php
session_save_path(dirname(__DIR__) . '/sessions');
session_start();
header('Content-Type: application/json');
require_once __DIR__.'/../db.php';

$d=json_decode(file_get_contents("php://input"),true);
$hash=hash('sha256',$d['password']);

$stmt=$conn->prepare("SELECT user_id,name FROM users WHERE email=? AND password=?");
$stmt->bind_param("ss",$d['email'],$hash);
$stmt->execute();
$r=$stmt->get_result();

if($r->num_rows===1){
 $u=$r->fetch_assoc();
 $_SESSION['user_id']=$u['user_id'];
 $_SESSION['name']=$u['name'];
 echo json_encode(["ok"=>1]);
}else{
 http_response_code(401);
}
