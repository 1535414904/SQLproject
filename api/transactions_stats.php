<?php
session_save_path(dirname(__DIR__) . '/sessions');
session_start();
header('Content-Type: application/json');
require_once __DIR__.'/../db.php';

$uid=$_SESSION['user_id'];
$sql="
SELECT c.name,SUM(t.amount) total
FROM transactions t JOIN categories c ON t.category_id=c.category_id
WHERE t.user_id=? AND t.type=1
GROUP BY c.name";
$stmt=$conn->prepare($sql);
$stmt->bind_param("i",$uid);
$stmt->execute();
$r=$stmt->get_result();

$labels=[];$values=[];
while($row=$r->fetch_assoc()){
 $labels[]=$row['name'];
 $values[]=$row['total'];
}

echo json_encode(compact("labels","values"));
