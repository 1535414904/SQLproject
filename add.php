<?php
session_save_path(__DIR__ . '/sessions');
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$user_id = $_SESSION['user_id'];
$error = "";

// 新增記錄
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_transaction'])) {
    $amount = $_POST['amount'];
    $category_id = $_POST['category_id'];
    $type = $_POST['type'];
    $note = $_POST['note'];
    $date = $_POST['date'];

    $stmt = $conn->prepare("INSERT INTO transactions (user_id, category_id, type, amount, note, date)
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiidss", $user_id, $category_id, $type, $amount, $note, $date);
    if ($stmt->execute()) {
        header("Location: index.php?success=1");
        exit;
    } else {
        $error = "❌ 發生錯誤：" . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>➕ 新增記錄</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>➕ 新增記帳記錄</h2>
    <div>
      👤 <?= htmlspecialchars($_SESSION['name']) ?> |
      <a href="logout.php" class="btn btn-sm btn-outline-secondary">登出</a>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" class="p-4 bg-white shadow rounded">
    <input type="hidden" name="add_transaction" value="1">
    <div class="row g-3">
      <div class="col-md-2">
        <label class="form-label">金額</label>
        <input type="number" step="0.01" name="amount" class="form-control" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">類型</label>
        <select name="type" class="form-select" required>
          <option value="1">支出</option>
          <option value="2">收入</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">分類</label>
        <select name="category_id" class="form-select" required>
          <option value="">-- 請選擇分類 --</option>
          <?php
          $res = $conn->query("SELECT category_id, name FROM categories ORDER BY name");
          while ($row = $res->fetch_assoc()) {
              echo "<option value='" . htmlspecialchars($row['category_id']) . "'>" . htmlspecialchars($row['name']) . "</option>";
          }
          ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">日期</label>
        <input type="date" name="date" class="form-control" required value="<?= date('Y-m-d') ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">備註</label>
        <input type="text" name="note" class="form-control">
      </div>
    </div>

    <div class="mt-4 text-end">
      <button type="submit" class="btn btn-success">✅ 儲存記錄</button>
      <a href="categories.php" class="btn btn-outline-primary">📂 分類管理</a>
      <a href="index.php" class="btn btn-secondary">返回主頁</a>
    </div>
  </form>
</div>
</body>
</html>
