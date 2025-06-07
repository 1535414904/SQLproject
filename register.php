<?php
session_save_path(__DIR__ . '/sessions');  // 改為存自己資料夾
session_start();

include 'db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $pw = $_POST['password'];
    $hash = hash('sha256', $pw);

    // 檢查 email 是否已存在
    $check = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $error = "❌ 該 Email 已被註冊，請使用其他帳號。";
    } else {
        // 寫入新使用者
        $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$hash')";
        // if ($conn->query($sql)) {
        //     $success = "✅ 註冊成功，請前往登入！";
        // } else {
        //     $error = "❌ 註冊失敗：" . $conn->error;
        // }
        if ($conn->query($sql)) {
            $success = "✅ 註冊成功，將自動前往登入！";
            header("refresh:1.5;url=login.php");  // 加這一行即可
        } else {
            $error = "❌ 註冊失敗：" . $conn->error;
        }

    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>註冊帳號</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 500px;">
  <h3 class="mb-4">📝 註冊新帳號</h3>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php elseif ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
    <!-- <div><a href="login.php" class="btn btn-outline-primary">前往登入</a></div> -->
  <?php endif; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">暱稱</label>
      <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">電子郵件</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">密碼</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success">註冊</button>
    <a href="login.php" class="btn btn-outline-secondary">已有帳號？登入</a>
  </form>
</div>
</body>
</html>
