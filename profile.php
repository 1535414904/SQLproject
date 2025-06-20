<?php
session_save_path(__DIR__ . '/sessions');
session_start();

include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// 取得使用者資料
$stmt = $conn->prepare("SELECT name, email, password FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 修改密碼流程
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["new_password"])) {
    $old_password = trim($_POST["old_password"]);
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    $old_hash = hash('sha256', $old_password);
    if ($old_hash !== $user['password']) {
        $error = "❌ 舊密碼錯誤，請重新輸入。";
    } elseif ($new_password !== $confirm_password) {
        $error = "❌ 新密碼與確認密碼不一致。";
    } else {
        $new_hash = hash('sha256', $new_password);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_hash, $user_id);
        if ($stmt->execute()) {
            $success = "✅ 密碼已成功更新！";
        } else {
            $error = "❌ 密碼更新失敗：" . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>個人資料</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #f1f8e9, #dcedc8);
      font-family: 'Segoe UI', sans-serif;
    }
    .profile-card {
      background-color: #ffffff;
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
    }
    .btn-back {
      background-color: #388e3c;
      border-color: #388e3c;
    }
    .btn-back:hover {
      background-color: #2e7d32;
      border-color: #2e7d32;
    }
  </style>
</head>
<body>
<div class="container py-5">
  <h2 class="mb-4 text-success">個人資料</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="profile-card mb-4">
    <h5 class="mb-3">基本資訊</h5>
    <p><strong>姓名：</strong><?= htmlspecialchars($user['name']) ?></p>
    <p><strong>Email：</strong><?= htmlspecialchars($user['email']) ?></p>
  </div>

  <div class="profile-card">
    <h5 class="mb-3">修改密碼</h5>
    <form method="post">
      <div class="mb-3">
        <label for="old_password" class="form-label">舊密碼</label>
        <input type="password" name="old_password" id="old_password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="new_password" class="form-label">新密碼</label>
        <input type="password" name="new_password" id="new_password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="confirm_password" class="form-label">確認新密碼</label>
        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-success">更新密碼</button>
      <a href="index.php" class="btn btn-secondary ms-2">返回主頁</a>
    </form>
  </div>
</div>
</body>
</html>
