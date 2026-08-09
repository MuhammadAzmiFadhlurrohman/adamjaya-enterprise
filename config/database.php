<?php
// ========================================================
// KONFIGURASI DATABASE: ADAM JAYA ENTERPRISE
// ========================================================

// Aktifkan Error Reporting untuk diagnosa live server
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'adamjaya_db');

// Inisialisasi Koneksi MySQLi
$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
