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
<title>口袋黑洞</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
  background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
  font-family: 'Segoe UI', sans-serif;
}
.summary-card {
  background: #fff;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  justify-content: space-around;
  margin-bottom: 1rem;
}
</style>
</head>
<body>

<div class="container py-4">
  <h2 class="mb-3">我的記帳清單</h2>

  <div class="mb-3 text-end">
    <a href="chart.php" class="btn btn-outline-info">統計圖</a>
    <a href="add.php" class="btn btn-success">新增記錄</a>
  </div>

  <div class="summary-card">
    <div>
      <h6>總收入</h6>
      <div id="income" class="text-success fw-bold">$0</div>
    </div>
    <div>
      <h6>總支出</h6>
      <div id="expense" class="text-danger fw-bold">$0</div>
    </div>
    <div>
      <h6>目前餘額</h6>
      <div id="balance" class="text-primary fw-bold">$0</div>
    </div>
  </div>

  <form id="filterForm" class="row g-2 mb-3">
    <div class="col-md-3">
      <input type="text" name="keyword" class="form-control" placeholder="備註關鍵字">
    </div>
    <div class="col-md-2">
      <select name="type" class="form-select">
        <option value="">所有類型</option>
        <option value="1">支出</option>
        <option value="2">收入</option>
      </select>
    </div>
    <div class="col-md-2">
      <select name="category_id" class="form-select" id="categorySelect">
        <option value="">所有分類</option>
      </select>
    </div>
    <div class="col-md-2">
      <input type="date" name="start_date" class="form-control">
    </div>
    <div class="col-md-2">
      <input type="date" name="end_date" class="form-control">
    </div>
    <div class="col-md-1">
      <button class="btn btn-outline-primary w-100">搜尋</button>
    </div>
  </form>

  <table class="table table-bordered bg-white">
    <thead class="table-success">
      <tr>
        <th>金額</th>
        <th>類型</th>
        <th>分類</th>
        <th>備註</th>
        <th>日期</th>
      </tr>
    </thead>
    <tbody id="tbody"></tbody>
  </table>
</div>

<script>
async function loadCategories(){
  const res = await fetch("/api/categories_list.php");
  const data = await res.json();
  const sel = document.getElementById("categorySelect");
  data.forEach(c=>{
    sel.innerHTML += `<option value="${c.category_id}">${c.name}</option>`;
  });
}

async function loadData(){
  const params = new URLSearchParams(new FormData(
    document.getElementById("filterForm")
  ));
  const res = await fetch("/api/transactions_list.php?" + params);
  const data = await res.json();

  document.getElementById("income").textContent  = "$" + data.summary.income;
  document.getElementById("expense").textContent = "$" + data.summary.expense;
  document.getElementById("balance").textContent = "$" + data.summary.balance;

  const tbody = document.getElementById("tbody");
  tbody.innerHTML = "";
  if (data.records.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">尚無資料</td></tr>`;
    return;
  }

  data.records.forEach(r=>{
    tbody.innerHTML += `
      <tr>
        <td>$ ${r.amount}</td>
        <td>${r.type == 1 ? '支出' : '收入'}</td>
        <td>${r.category}</td>
        <td>${r.note}</td>
        <td>${r.date}</td>
      </tr>`;
  });
}

document.getElementById("filterForm").onsubmit = e=>{
  e.preventDefault();
  loadData();
};

loadCategories();
loadData();
</script>
</body>
</html>
