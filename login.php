<?php
session_save_path(__DIR__ . '/sessions');
session_start();
include 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $pw = $_POST['password'];
    $hash = hash('sha256', $pw);

    $sql = "SELECT * FROM users WHERE email = '$email' AND password = '$hash'";
    $result = $conn->query($sql);

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        header("Location: index.php");
        exit;
    } else {
        $error = "登入失敗，帳號或密碼錯誤。";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>登入 - 記帳系統</title>
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
    .login-box {
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      padding: 40px;
      width: 100%;
      max-width: 420px;
    }
    .login-title {
      font-size: 1.75rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      text-align: center;
      color: #2e7d32;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="login-title">登入你的記帳本</div>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label for="email" class="form-label">電子郵件</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">密碼</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-success">登入</button>
      </div>
      <p class="mt-3 text-center">尚未有帳號？<a href="register.php">前往註冊</a></p>
    </form>
  </div>
</body>
</html>
