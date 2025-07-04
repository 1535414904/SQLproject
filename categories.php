<?php
session_save_path(__DIR__ . '/sessions');
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$error = "";
$success = "";
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'edit') {
        $success = "分類已成功更新！";
    } elseif ($_GET['success'] === 'add') {
        $success = "新增分類成功！";
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $check = $conn->prepare("SELECT COUNT(*) AS count FROM categories WHERE LOWER(name) = LOWER(?)");
        $check->bind_param("s", $name);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        $check->close();

        if ($result['count'] > 0) {
            $error = "分類名稱「 $name 」已存在，請勿重複新增。";
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                header("Location: categories.php?success=add");
                exit;
            } else {
                $error = "新增失敗：" . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $error = "分類名稱不能為空";
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['category_id'];
    $name = trim($_POST['name']);

    $check = $conn->prepare("SELECT COUNT(*) AS count FROM categories WHERE LOWER(name) = LOWER(?) AND category_id != ?");
    $check->bind_param("si", $name, $id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    $check->close();

    if ($result['count'] > 0) {
        $error = "無法更新，分類名稱「 $name 」已被使用。";
    } else {
        $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE category_id = ?");
        $stmt->bind_param("si", $name, $id);
        if ($stmt->execute()) {
            header("Location: categories.php?success=edit");
            exit;
        } else {
            $error = "更新失敗：" . $stmt->error;
        }
        $stmt->close();
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $check = $conn->query("SELECT COUNT(*) AS total FROM transactions WHERE category_id = $id");
    $count = $check->fetch_assoc()['total'];

    if ($count > 0) {
        $error = "無法刪除，已有 $count 筆記帳資料使用此分類。";
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "已刪除分類 ID：$id";
        } else {
            $error = "刪除失敗：" . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>分類管理</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
      font-family: 'Segoe UI', sans-serif;
    }
    .top-bar {
      position: sticky;
      top: 0;
      background-color: #2e7d32;
      color: white;
      padding: 0.75rem 1rem;
      z-index: 1000;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .card, .table {
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    }
    .btn-success {
      background-color: #388e3c;
      border-color: #388e3c;
    }
    .btn-success:hover {
      background-color: #2e7d32;
      border-color: #2e7d32;
    }
    .btn-outline-info {
      color: #2e7d32;
      border-color: #2e7d32;
    }
    .btn-outline-info:hover {
      background-color: #2e7d32;
      color: white;
    }
  </style>
</head>
<body>
<div class="top-bar">
  <div><?= htmlspecialchars($_SESSION['name']) ?></div>
  <a href="logout.php" class="btn btn-sm btn-light">登出</a>
</div>

<div class="container py-4">
  <h2 class="mb-4">分類管理</h2>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="card p-4 mb-4">
    <form method="post">
      <input type="hidden" name="action" value="add">
      <div class="row g-2">
        <div class="col">
          <input type="text" name="name" class="form-control" placeholder="新增分類名稱" required>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-success">新增分類</button>
        </div>
        <div class="col-auto">
          <a href="index.php" class="btn btn-outline-primary">回主頁</a>
        </div>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered bg-white">
      <thead class="table-success">
        <tr>
          <th>分類名稱</th>
          <th style="width: 200px;">操作</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
        $res = $conn->query("SELECT * FROM categories ORDER BY category_id");
        while ($row = $res->fetch_assoc()):
        ?>
        <tr>
          <td>
            <?php if ($edit_id === (int)$row['category_id']): ?>
              <form method="post" class="d-flex">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="category_id" value="<?= $row['category_id'] ?>">
                <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" class="form-control me-2" required>
                <button type="submit" class="btn btn-success btn-sm">儲存</button>
              </form>
            <?php else: ?>
              <?= htmlspecialchars($row['name']) ?>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($edit_id === (int)$row['category_id']): ?>
              <a href="categories.php" class="btn btn-secondary btn-sm">取消</a>
            <?php else: ?>
              <a href="?edit=<?= $row['category_id'] ?>" class="btn btn-outline-warning btn-sm">編輯</a>
            <?php endif; ?>
            <a href="?delete=<?= $row['category_id'] ?>" class="btn btn-outline-danger btn-sm ms-2" onclick="return confirm('確定要刪除這個分類嗎？')">刪除</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
