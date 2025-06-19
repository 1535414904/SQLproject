<?php
session_save_path(__DIR__ . '/sessions');
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$user_id = $_SESSION['user_id'];
$keyword = $_GET['keyword'] ?? '';
$type = $_GET['type'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// 🔍 建立查詢條件
$where = "WHERE t.user_id = ?";
$params = [$user_id];
$types = "i";

if ($keyword !== '') {
    $where .= " AND t.note LIKE ?";
    $params[] = "%" . $keyword . "%";
    $types .= "s";
}
if ($type === '1' || $type === '2') {
    $where .= " AND t.type = ?";
    $params[] = (int)$type;
    $types .= "i";
}
if ($category_id !== '') {
    $where .= " AND t.category_id = ?";
    $params[] = (int)$category_id;
    $types .= "i";
}
if ($start_date !== '') {
    $where .= " AND t.date >= ?";
    $params[] = $start_date;
    $types .= "s";
}
if ($end_date !== '') {
    $where .= " AND t.date <= ?";
    $params[] = $end_date;
    $types .= "s";
}

// 🗂 查詢結果
$sql = "SELECT t.transaction_id, t.amount, t.type, t.note, t.date, c.name AS category
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        $where ORDER BY t.date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>查詢記錄</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="mb-4">🔍 查詢記帳紀錄</h3>

  <!-- 查詢表單 -->
  <form method="get" class="bg-white p-4 rounded shadow mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-md-2">
        <label class="form-label">關鍵字</label>
        <input type="text" name="keyword" class="form-control" value="<?= htmlspecialchars($keyword) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">類型</label>
        <select name="type" class="form-select">
          <option value="">全部</option>
          <option value="1" <?= $type === '1' ? 'selected' : '' ?>>支出</option>
          <option value="2" <?= $type === '2' ? 'selected' : '' ?>>收入</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">分類</label>
        <select name="category_id" class="form-select">
          <option value="">全部</option>
          <?php
          $cats = $conn->query("SELECT * FROM categories ORDER BY name");
          while ($row = $cats->fetch_assoc()):
              $selected = $category_id == $row['category_id'] ? 'selected' : '';
              echo "<option value='{$row['category_id']}' $selected>" . htmlspecialchars($row['name']) . "</option>";
          endwhile;
          ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">起始日期</label>
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">結束日期</label>
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
      </div>
      <div class="col-md-2 text-end">
        <button type="submit" class="btn btn-primary">查詢</button>
        <a href="search.php" class="btn btn-secondary">重置</a>
      </div>
    </div>
  </form>

  <!-- 查詢結果 -->
  <table class="table table-bordered bg-white shadow">
    <thead class="table-dark">
      <tr>
        <th>金額</th>
        <th>類型</th>
        <th>分類</th>
        <th>備註</th>
        <th>日期</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td>$ <?= htmlspecialchars($row['amount']) ?></td>
            <td><?= $row['type'] == 1 ? '支出' : '收入' ?></td>
            <td><?= htmlspecialchars($row['category']) ?></td>
            <td><?= htmlspecialchars($row['note']) ?></td>
            <td><?= htmlspecialchars($row['date']) ?></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="5" class="text-center text-muted">沒有符合條件的紀錄。</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <a href="index.php" class="btn btn-secondary mt-3">← 返回主頁</a>
</div>
</body>
</html>