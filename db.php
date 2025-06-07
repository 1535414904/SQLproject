<?php
// $host = 'localhost';        // 主機位置（通常本機就是 localhost）
$host = "127.0.0.1";  // 🚀 直接用 IP 避開 DNS 解析慢的問題
$user = 'root';             // 使用者名稱（預設 root）
$password = '12345';             // 密碼（通常預設是空）
$database = 'finance_db';   // 你剛建立的資料庫名稱

// 建立連線
$conn = new mysqli($host, $user, $password, $database);

// 檢查是否連線成功
if ($conn->connect_error) {
    die("❌ 資料庫連線失敗：" . $conn->connect_error);
}

// 成功連線就顯示這一行（你也可以刪掉）
// echo "✅ 成功連線到資料庫！";
?>
