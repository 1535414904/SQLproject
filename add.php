<?php
session_save_path(__DIR__ . '/sessions');
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>新增記錄</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
      font-family: 'Segoe UI', sans-serif;
    }
    .top-bar {
      background-color: #2e7d32;
      color: white;
      padding: 0.75rem 1rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .form-container {
      background-color: #ffffff;
      border-radius: 12px;
      padding: 2rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    .btn-success {
      background-color: #388e3c;
      border-color: #388e3c;
    }
    .btn-success:hover {
      background-color: #2e7d32;
      border-color: #2e7d32;
    }
    .btn-outline-primary {
      color: #2e7d32;
      border-color: #2e7d32;
    }
    .btn-outline-primary:hover {
      background-color: #2e7d32;
      color: white;
    }
  </style>
</head>
<body>

<div class="top-bar">
  <div>新增記帳記錄</div>
  <div>
    <?= htmlspecialchars($_SESSION['name'] ?? '使用者') ?> |
    <a href="logout.php" class="btn btn-sm btn-light">登出</a>
  </div>
</div>

<div class="container py-4">
  <div id="errorBox" class="alert alert-danger d-none"></div>

  <form id="addForm" class="form-container">
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
      <button type="submit" class="btn btn-success">儲存記錄</button>
      <a href="categories.php" class="btn btn-outline-primary">分類管理</a>
      <a href="index.php" class="btn btn-secondary">返回主頁</a>
    </div>
  </form>
</div>

<script>
async function loadCategories() {
  const res = await fetch("/api/categories_list.php");
  if (!res.ok) return;

  const data = await res.json();
  const select = document.querySelector('[name="category_id"]');

  data.forEach(c => {
    const opt = document.createElement("option");
    opt.value = c.category_id;
    opt.textContent = c.name;
    select.appendChild(opt);
  });
}

document.getElementById("addForm").addEventListener("submit", async function (e) {
  e.preventDefault();

  const errorBox = document.getElementById("errorBox");
  errorBox.classList.add("d-none");

  const data = {
    amount: document.querySelector('[name="amount"]').value,
    type: document.querySelector('[name="type"]').value,
    category_id: document.querySelector('[name="category_id"]').value,
    date: document.querySelector('[name="date"]').value,
    note: document.querySelector('[name="note"]').value
  };

  const res = await fetch("/api/transaction_add.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data)
  });

  const result = await res.json();

  if (res.ok) {
    window.location.href = "index.php?success=1";
  } else {
    errorBox.textContent = result.error || "新增失敗";
    errorBox.classList.remove("d-none");
  }
});

loadCategories();
</script>

</body>
</html>
