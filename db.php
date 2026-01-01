<?php
$conn = new mysqli(
    "db",          // Docker service name
    "accuser",     // MYSQL_USER
    "accpass",     // MYSQL_PASSWORD
    "accounting"   // MYSQL_DATABASE
);

if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
