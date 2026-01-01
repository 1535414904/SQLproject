<?php
session_save_path(__DIR__ . '/sessions');
session_start();
session_destroy();
header("Location: login.php");
