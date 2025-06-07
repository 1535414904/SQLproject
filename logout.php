<?php
session_save_path(__DIR__ . '/sessions');  // 改為存自己資料夾
session_start();
session_unset();       // 清除 $_SESSION 中所有變數
session_destroy();     // 銷毀 session 檔案與 ID
header("Location: login.php");
exit;
