<?php
session_save_path(__DIR__ . '/sessions');
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// 顯示提示訊息
if (isset($_GET['success'])) {
    if ($_GET['success'] === '1') {
        $success = "✅ 記錄已成功新增！";
    } elseif ($_GET['success'] === 'edit') {
        $success = "✅ 已成功更新記錄 ID " . htmlspecialchars($_GET['id']) . "。";
    } elseif ($_GET['success'] === 'delete' && isset($_GET['id'])) {
        $success = "✅ 已刪除記錄 ID " . htmlspecialchars($_GET['id']) . "。";
    }
}

// 統計收入與支出
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

// 判斷是否為編輯模式
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$edit_transaction = null;
if ($edit_id) {
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE transaction_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $edit_id, $user_id);
    $stmt->execute();
    $edit_transaction = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// 刪除記錄
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM transactions WHERE transaction_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    if ($stmt->execute()) {
        header("Location: index.php?success=delete&id=$id");
        exit;
    } else {
        $error = "❌ 刪除失敗：" . $conn->error;
    }
    $stmt->close();
}

// 更新記錄
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_transaction'])) {
    $edit_id = (int) $_POST['edit_id'];
    $amount = $_POST['amount'];
    $category_id = $_POST['category_id'];
    $type = $_POST['type'];
    $note = $_POST['note'];
    $date = $_POST['date'];

    $stmt = $conn->prepare("UPDATE transactions SET category_id=?, type=?, amount=?, note=?, date=? WHERE transaction_id=? AND user_id=?");
    $stmt->bind_param("iidssii", $category_id, $type, $amount, $note, $date, $edit_id, $user_id);
    if ($stmt->execute()) {
        header("Location: index.php?success=edit&id=$edit_id");
        exit;
    } else {
        $error = "❌ 更新失敗：" . $conn->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>口袋黑洞</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-3 text-end">
  👤 <?= htmlspecialchars($_SESSION['name']) ?> |
  <a href="logout.php" class="btn btn-sm btn-outline-secondary">登出</a>
</div>

<div class="container py-4">
  <h2 class="mb-4">📒 我的記帳清單</h2>

  <div class="alert alert-info d-flex justify-content-between">
    <div>💵 <strong>總收入：</strong> $<?= number_format($total_income, 2) ?></div>
    <div>💸 <strong>總支出：</strong> $<?= number_format($total_expense, 2) ?></div>
    <div>🧮 <strong>目前餘額：</strong> $<?= number_format($balance, 2) ?></div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($edit_transaction): ?>
  <div class="card mb-4 shadow">
    <div class="card-header bg-warning text-dark">✏️ 編輯記錄</div>
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="edit_id" value="<?= $edit_transaction['transaction_id'] ?>">
        <div class="mb-2">
          <label>金額</label>
          <input type="number" step="0.01" name="amount" class="form-control" required value="<?= htmlspecialchars($edit_transaction['amount']) ?>">
        </div>
        <div class="mb-2">
          <label>類型</label>
          <select name="type" class="form-select" required>
            <option value="1" <?= $edit_transaction['type'] == 1 ? 'selected' : '' ?>>支出</option>
            <option value="2" <?= $edit_transaction['type'] == 2 ? 'selected' : '' ?>>收入</option>
          </select>
        </div>
        <div class="mb-2">
          <label>分類</label>
          <select name="category_id" class="form-select" required>
            <?php
            $res = $conn->query("SELECT category_id, name FROM categories ORDER BY name");
            while ($row = $res->fetch_assoc()) {
                $selected = ($row['category_id'] == $edit_transaction['category_id']) ? 'selected' : '';
                echo "<option value='{$row['category_id']}' $selected>" . htmlspecialchars($row['name']) . "</option>";
            }
            ?>
          </select>
        </div>
        <div class="mb-2">
          <label>備註</label>
          <input type="text" name="note" class="form-control" value="<?= htmlspecialchars($edit_transaction['note']) ?>">
        </div>
        <div class="mb-3">
          <label>日期</label>
          <input type="date" name="date" class="form-control" required value="<?= htmlspecialchars($edit_transaction['date']) ?>">
        </div>
        <button type="submit" name="update_transaction" class="btn btn-primary">儲存修改</button>
        <a href="index.php" class="btn btn-secondary">取消</a>
      </form>
    </div>
  </div>
<?php endif; ?>

  <!-- 📊 操作按鈕 -->
  <div class="mb-3 text-end">
    <a href="chart.php" class="btn btn-info">📊 統計圖</a>
    <a href="add.php" class="btn btn-success">➕ 新增記錄</a>
  </div>

  <!-- 🔍 查詢區塊 -->
  <form method="get" class="row gy-2 gx-3 align-items-center mb-3 bg-white p-3 rounded shadow-sm">
    <div class="col-auto">
      <input type="text" name="keyword" class="form-control" placeholder="輸入備註關鍵字" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
    </div>
    <div class="col-auto">
      <select name="type" class="form-select">
        <option value="">所有類型</option>
        <option value="1" <?= (isset($_GET['type']) && $_GET['type'] === '1') ? 'selected' : '' ?>>支出</option>
        <option value="2" <?= (isset($_GET['type']) && $_GET['type'] === '2') ? 'selected' : '' ?>>收入</option>
      </select>
    </div>
    <div class="col-auto">
      <select name="category_id" class="form-select">
        <option value="">所有分類</option>
        <?php
        $res = $conn->query("SELECT category_id, name FROM categories ORDER BY name");
        while ($row = $res->fetch_assoc()) {
            $selected = (isset($_GET['category_id']) && $_GET['category_id'] == $row['category_id']) ? 'selected' : '';
            echo "<option value='{$row['category_id']}' $selected>" . htmlspecialchars($row['name']) . "</option>";
        }
        ?>
      </select>
    </div>
    <div class="col-auto">
      <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
    </div>
    <div class="col-auto">
      <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-outline-primary">搜尋</button>
      <a href="index.php" class="btn btn-outline-secondary">清除</a>
    </div>
  </form>

  <!-- 🔎 篩選條件顯示 -->
  <?php if (!empty($_GET['start_date']) || !empty($_GET['end_date'])): ?>
    <p class="text-muted">
      📅 顯示日期：
      <?= htmlspecialchars($_GET['start_date'] ?? '') ?>
      <?= (!empty($_GET['start_date']) && !empty($_GET['end_date'])) ? ' ~ ' : '' ?>
      <?= htmlspecialchars($_GET['end_date'] ?? '') ?>
    </p>
  <?php endif; ?>

  <!-- 📋 記帳清單 -->
  <table class="table table-hover table-bordered bg-white shadow">
    <thead class="table-dark">
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
        <td><?= $row['type'] == 1 ? '支出' : '收入' ?></td>
        <td><?= htmlspecialchars($row['category']) ?></td>
        <td><?= htmlspecialchars($row['note']) ?></td>
        <td><?= htmlspecialchars($row['date']) ?></td>
        <td>
          <a href="?edit=<?= $row['transaction_id'] ?>" class="btn btn-sm btn-warning">編輯</a>
          <a href="?delete=<?= $row['transaction_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('確定刪除？')">刪除</a>
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
</body>
</html>
