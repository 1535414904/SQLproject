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
$edit_transaction = null;
$total_income = 0;
$total_expense = 0;
$balance = 0;

if (isset($_GET['success'])) {
    if ($_GET['success'] === '1') {
        $success = "記錄已成功新增！";
    } elseif ($_GET['success'] === 'edit') {
        $success = "已成功更新記錄 ID " . htmlspecialchars($_GET['id'] ?? '');
    } elseif ($_GET['success'] === 'delete' && isset($_GET['id'])) {
        $success = "已刪除記錄 ID " . htmlspecialchars($_GET['id']);
    }
}

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE transaction_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $edit_id, $user_id);
    $stmt->execute();
    $edit_transaction = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$sql = "SELECT 
            SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) AS total_income,
            SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) AS total_expense
        FROM transactions
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_income = $summary['total_income'] ?? 0;
$total_expense = $summary['total_expense'] ?? 0;
$balance = $total_income - $total_expense;


if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM transactions WHERE transaction_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_id, $user_id);

    if ($stmt->execute()) {
        header("Location: index.php?success=delete&id=" . $delete_id);
        exit;
    } else {
        $error = "刪除失敗：" . $conn->error;
    }
    $stmt->close();
}


// --- update record ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_transaction'])) {
    $edit_id = (int)$_POST['transaction_id'];
    $amount = $_POST['amount'];
    $category_id = $_POST['category_id'];
    $type = $_POST['type'];
    $note = $_POST['note'];
    $date = $_POST['date'];

    $stmt = $conn->prepare("UPDATE transactions SET category_id=?, type=?, amount=?, note=?, date=? WHERE transaction_id=? AND user_id=?");
    $stmt->bind_param("iidssii", $category_id, $type, $amount, $note, $date, $edit_id, $user_id);
    if ($stmt->execute()) {
        header("Location: index.php?success=edit&id=" . $edit_id);
        exit;
    } else {
        $error = "更新失敗：" . $conn->error;
    }
    $stmt->close();

}

$sql = "SELECT 
            SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) AS total_income,
            SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) AS total_expense
        FROM transactions
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_income = $summary['total_income'] ?? 0;
$total_expense = $summary['total_expense'] ?? 0;
$balance = $total_income - $total_expense;
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>口袋黑洞</title>
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
    .summary-card {
      background-color: #ffffff;
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
      display: flex;
      justify-content: space-around;
      gap: 1rem;
      margin-bottom: 1rem;
    }
    .summary-item {
      text-align: center;
    }
    .summary-item h5 {
      margin-bottom: 0.5rem;
      color: #2e7d32;
    }
    .table-container {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
      overflow: hidden;
    }
    .filter-form {
      background: #ffffff;
      border-radius: 12px;
      padding: 1rem;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 1rem;
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
  <div>歡迎，<?= htmlspecialchars($_SESSION['name'] ?? '使用者') ?></div>
  <a href="logout.php" class="btn btn-sm btn-light">登出</a>
</div>

<div class="container py-4">
  <h2 class="mb-4">我的記帳清單</h2>
  <div class="mb-3 text-end">
    <a href="chart.php" class="btn btn-outline-info">統計圖</a>
    <a href="add.php" class="btn btn-success">新增記錄</a>
  </div>

  <div class="summary-card">
    <div class="summary-item">
      <h5>總收入</h5>
      <div class="text-success fw-bold fs-5">$<?= number_format($total_income, 2) ?></div>
    </div>
    <div class="summary-item">
      <h5>總支出</h5>
      <div class="text-danger fw-bold fs-5">$<?= number_format($total_expense, 2) ?></div>
    </div>
    <div class="summary-item">
      <h5>目前餘額</h5>
      <div class="text-primary fw-bold fs-5">$<?= number_format($balance, 2) ?></div>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($edit_transaction): ?>
    <div class="card mb-4">
      <div class="card-header bg-success text-white">編輯記錄</div>
      <div class="card-body">
        <form action="index.php" method="post">
          <input type="hidden" name="transaction_id" value="<?= htmlspecialchars($edit_transaction['transaction_id']) ?>">
          <input type="hidden" name="update_transaction" value="1">

          <div class="mb-3">
            <label for="amount" class="form-label">金額</label>
            <input type="number" name="amount" id="amount" class="form-control" value="<?= htmlspecialchars($edit_transaction['amount']) ?>" required>
          </div>

          <div class="mb-3">
            <label for="type" class="form-label">類型</label>
            <select name="type" id="type" class="form-select" required>
              <option value="1" <?= $edit_transaction['type'] == 1 ? 'selected' : '' ?>>支出</option>
              <option value="2" <?= $edit_transaction['type'] == 2 ? 'selected' : '' ?>>收入</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="category_id" class="form-label">分類</label>
            <select name="category_id" id="category_id" class="form-select" required>
              <?php
              $res = $conn->query("SELECT category_id, name FROM categories ORDER BY name");
              while ($row = $res->fetch_assoc()) {
                  $selected = $edit_transaction['category_id'] == $row['category_id'] ? 'selected' : '';
                  echo "<option value='{$row['category_id']}' $selected>" . htmlspecialchars($row['name']) . "</option>";
              }
              ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="note" class="form-label">備註</label>
            <input type="text" name="note" id="note" class="form-control" value="<?= htmlspecialchars($edit_transaction['note']) ?>">
          </div>

          <div class="mb-3">
            <label for="date" class="form-label">日期</label>
            <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($edit_transaction['date']) ?>" required>
          </div>

          <button type="submit" class="btn btn-success">更新記錄</button>
          <a href="index.php" class="btn btn-secondary ms-2">取消</a>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <form method="get" class="row gy-2 gx-3 align-items-center filter-form">
    <div class="col-md-3">
      <input type="text" name="keyword" class="form-control" placeholder="輸入備註關鍵字" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <select name="type" class="form-select">
        <option value="">所有類型</option>
        <option value="1" <?= ($_GET['type'] ?? '') === '1' ? 'selected' : '' ?>>支出</option>
        <option value="2" <?= ($_GET['type'] ?? '') === '2' ? 'selected' : '' ?>>收入</option>
      </select>
    </div>
    <div class="col-md-2">
      <select name="category_id" class="form-select">
        <option value="">所有分類</option>
        <?php
        $res = $conn->query("SELECT category_id, name FROM categories ORDER BY name");
        while ($row = $res->fetch_assoc()) {
            $selected = ($_GET['category_id'] ?? '') == $row['category_id'] ? 'selected' : '';
            echo "<option value='{$row['category_id']}' $selected>" . htmlspecialchars($row['name']) . "</option>";
        }
        ?>
      </select>
    </div>
    <div class="col-md-2">
      <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
    </div>
    <div class="col-md-1">
      <button type="submit" class="btn btn-outline-primary w-100">搜尋</button>
    </div>
  </form>

  <div class="table-container">
    <table class="table table-hover table-bordered align-middle m-0" style="border-radius: 12px; overflow: hidden;">
      <thead style="background: linear-gradient(90deg, #81c784, #66bb6a); color: white;">

        <tr>
          <th>金額</th>
          <th>類型</th>
          <th>分類</th>
          <th>備註</th>
          <th>日期</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $where = "WHERE t.user_id = ?";
        $params = [$user_id];
        $types = "i";

        if (!empty($_GET['keyword'])) {
            $where .= " AND t.note LIKE ?";
            $params[] = "%" . $_GET['keyword'] . "%";
            $types .= "s";
        }
        if (isset($_GET['type']) && ($_GET['type'] === '1' || $_GET['type'] === '2')) {
            $where .= " AND t.type = ?";
            $params[] = (int)$_GET['type'];
            $types .= "i";
        }
        if (!empty($_GET['category_id'])) {
            $where .= " AND t.category_id = ?";
            $params[] = (int)$_GET['category_id'];
            $types .= "i";
        }
        if (!empty($_GET['start_date'])) {
            $where .= " AND t.date >= ?";
            $params[] = $_GET['start_date'];
            $types .= "s";
        }
        if (!empty($_GET['end_date'])) {
            $where .= " AND t.date <= ?";
            $params[] = $_GET['end_date'];
            $types .= "s";
        }

        $sql = "SELECT t.transaction_id, t.amount, t.note, t.date, c.name AS category, t.type
                FROM transactions t
                JOIN categories c ON t.category_id = c.category_id
                $where
                ORDER BY t.date DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
        ?>
        <tr>
          <td>$ <?= htmlspecialchars($row['amount']) ?></td>
          <td><?= $row['type'] == 1 ? '<span class="text-danger">支出</span>' : '<span class="text-success">收入</span>' ?></td>
          <td><?= htmlspecialchars($row['category']) ?></td>
          <td><?= htmlspecialchars($row['note']) ?></td>
          <td><?= htmlspecialchars($row['date']) ?></td>
          <td>
            <a href="?edit=<?= $row['transaction_id'] ?>" class="btn btn-outline-warning btn-sm">編輯</a>
            <a href="?delete=<?= $row['transaction_id'] ?>" class="btn btn-outline-danger btn-sm ms-2" onclick="return confirm('確定要刪除嗎？')">刪除</a>
          </td>
        </tr>
        <?php
            endwhile;
        else:
            echo "<tr><td colspan='6' class='text-center text-muted'>尚無任何記帳紀錄</td></tr>";
        endif;
        $stmt->close();
        ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
