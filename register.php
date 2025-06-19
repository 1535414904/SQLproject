<?php
session_save_path(__DIR__ . '/sessions');
session_start();
include 'db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $pw = $_POST['password'];
    $hash = hash('sha256', $pw);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $check = $stmt->get_result();
    if ($check->num_rows > 0) {
        $error = "該 Email 已被註冊，請使用其他帳號。";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hash);
        if ($stmt->execute()) {
            $success = "註冊成功，將自動前往登入頁面！";
            header("refresh:1.5;url=login.php");
        } else {
            $error = "註冊失敗：" . $conn->error;
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>註冊帳號 - 記帳系統</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #f0f4f8, #e8f5e9);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', sans-serif;
    }
    .register-box {
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      padding: 40px;
      width: 100%;
      max-width: 460px;
    }
    .register-title {
      font-size: 1.75rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      text-align: center;
      color: #2e7d32;
    }
  </style>
</head>
<body>
  <div class="register-box">
    <div class="register-title">註冊你的帳號</div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
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
      <div class="d-grid">
        <button type="submit" class="btn btn-success">註冊</button>
      </div>
      <p class="mt-3 text-center">已經有帳號？<a href="login.php">前往登入</a></p>
    </form>
  </div>
</body>
</html>
