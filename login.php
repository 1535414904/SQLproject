<?php
session_save_path(__DIR__ . '/sessions');
session_start();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<title>登入</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center">
<form id="f" class="p-4 bg-white shadow">
<h4>登入</h4>
<input name="email" class="form-control mb-2" placeholder="Email">
<input name="password" type="password" class="form-control mb-2" placeholder="密碼">
<button class="btn btn-success w-100">登入</button>
</form>

<script>
f.onsubmit=async e=>{
 e.preventDefault();
 const r=await fetch("/api/auth_login.php",{
  method:"POST",
  headers:{'Content-Type':'application/json'},
  body:JSON.stringify({
    email:f.email.value,
    password:f.password.value
  })
 });
 r.ok?location.href="index.php":alert("登入失敗");
};
</script>
</body>
</html>
