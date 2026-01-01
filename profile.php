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
<title>個人資料</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
  <h2 class="mb-4 text-success">個人資料</h2>

  <div id="msg"></div>

  <div class="card p-4 mb-4">
    <h5>基本資訊</h5>
    <p><strong>姓名：</strong><span id="name"></span></p>
    <p><strong>Email：</strong><span id="email"></span></p>
  </div>

  <div class="card p-4">
    <h5>修改密碼</h5>
    <form id="pwForm">
      <input type="password" name="old" class="form-control mb-2" placeholder="舊密碼" required>
      <input type="password" name="new" class="form-control mb-2" placeholder="新密碼" required>
      <input type="password" name="confirm" class="form-control mb-2" placeholder="確認新密碼" required>
      <button class="btn btn-success">更新密碼</button>
      <a href="index.php" class="btn btn-secondary ms-2">返回主頁</a>
    </form>
  </div>
</div>

<script>
fetch("/api/user_profile_get.php")
  .then(r=>r.json())
  .then(d=>{
    name.textContent=d.name;
    email.textContent=d.email;
  });

pwForm.onsubmit=async e=>{
  e.preventDefault();
  if(pwForm.new.value!==pwForm.confirm.value){
    msg.innerHTML=`<div class="alert alert-danger">新密碼不一致</div>`;
    return;
  }
  const r=await fetch("/api/user_password_update.php",{
    method:"POST",
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({
      old:pwForm.old.value,
      new:pwForm.new.value
    })
  });
  msg.innerHTML=r.ok
    ?`<div class="alert alert-success">密碼更新成功</div>`
    :`<div class="alert alert-danger">更新失敗</div>`;
};
</script>
</body>
</html>
