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
<title>分類管理</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
  <h2 class="mb-4">分類管理</h2>

  <div id="msg" class="alert d-none"></div>

  <form id="addForm" class="mb-4 d-flex gap-2">
    <input type="text" class="form-control" name="name" placeholder="新增分類">
    <button class="btn btn-success">新增</button>
    <a href="index.php" class="btn btn-secondary">回主頁</a>
  </form>

  <table class="table table-bordered bg-white">
    <thead class="table-success">
      <tr>
        <th>分類名稱</th>
        <th style="width:180px">操作</th>
      </tr>
    </thead>
    <tbody id="catBody"></tbody>
  </table>
</div>

<script>
const msg = document.getElementById("msg");
const body = document.getElementById("catBody");

function showMsg(text, ok=true){
  msg.textContent = text;
  msg.className = "alert " + (ok ? "alert-success" : "alert-danger");
}

async function loadCats(){
  const res = await fetch("/api/categories_list.php");
  const data = await res.json();

  body.innerHTML = "";
  data.forEach(c=>{
    body.innerHTML += `
      <tr>
        <td>${c.name}</td>
        <td>
          <button class="btn btn-sm btn-warning"
            onclick="editCat(${c.category_id},'${c.name}')">編輯</button>
          <button class="btn btn-sm btn-danger"
            onclick="delCat(${c.category_id})">刪除</button>
        </td>
      </tr>`;
  });
}


document.getElementById("addForm").onsubmit = async e=>{
  e.preventDefault();
  const name = e.target.name.value;
  const res = await fetch("/api/categories_add.php",{
    method:"POST",
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({name})
  });
  const r = await res.json();
  res.ok ? showMsg("新增成功") : showMsg(r.error,false);
  e.target.reset();
  loadCats();
};

async function editCat(id,name){
  const n = prompt("修改分類名稱",name);
  if(!n) return;
  const res = await fetch("/api/categories_edit.php",{
    method:"POST",
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({category_id:id,name:n})
  });
  const r = await res.json();
  res.ok ? showMsg("更新成功") : showMsg(r.error,false);
  loadCats();
}

async function delCat(id){
  if(!confirm("確定刪除？")) return;
  const res = await fetch("/api/categories_delete.php",{
    method:"POST",
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({category_id:id})
  });
  const r = await res.json();
  res.ok ? showMsg("已刪除") : showMsg(r.error,false);
  loadCats();
}

loadCats();
</script>
</body>
</html>
