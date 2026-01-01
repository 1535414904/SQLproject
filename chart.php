<?php
session_save_path(__DIR__ . '/sessions');
session_start();
if(!isset($_SESSION['user_id'])) header("Location:login.php");
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<title>統計圖</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<canvas id="pie"></canvas>

<script>
fetch("/api/transactions_stats.php")
.then(r=>r.json())
.then(d=>{
 new Chart(pie,{
  type:"pie",
  data:{labels:d.labels,datasets:[{data:d.values}]}
 });
});
</script>
</body>
</html>
