<?php
// ========================================================
// KONFIGURASI DATABASE SMART DUAL-ENVIRONMENT: ADAM JAYA
// ========================================================

// Aktifkan Error Reporting untuk diagnosa live server
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Cek jika ada file konfigurasi khusus produksi di Hostinger (Di-ignore oleh Git)
if (file_exists(__DIR__ . '/database.production.php')) {
    require_once __DIR__ . '/database.production.php';
} else {
    // 2. Auto Detect Environment (Localhost XAMPP vs Hostinger Live Domain)
    $http_host = $_SERVER['HTTP_HOST'] ?? '';
    $server_name = $_SERVER['SERVER_NAME'] ?? '';

    $is_local = (
        strpos($http_host, 'localhost') !== false ||
        strpos($http_host, '127.0.0.1') !== false ||
        strpos($server_name, 'localhost') !== false ||
        strpos($server_name, '127.0.0.1') !== false ||
        php_sapi_name() === 'cli'
    );

    if ($is_local) {
        // === LOCALHOST XAMPP ENVIRONMENT ===
        defined('DB_HOST') || define('DB_HOST', 'localhost');
        defined('DB_USER') || define('DB_USER', 'root');
        defined('DB_PASS') || define('DB_PASS', '');
        defined('DB_NAME') || define('DB_NAME', 'adamjaya_db');
    } else {
        // === HOSTINGER PRODUCTION LIVE SERVER ===
        defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
        defined('DB_USER') || define('DB_USER', getenv('DB_USER') ?: 'u198399581_adamjaya');
        defined('DB_PASS') || define('DB_PASS', getenv('DB_PASS') ?: '');
        defined('DB_NAME') || define('DB_NAME', getenv('DB_NAME') ?: 'u198399581_adamjaya');
    }

    // Inisialisasi Koneksi MySQLi
    $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if (!$conn) {
        die("Koneksi database gagal: " . mysqli_connect_error() . "<br><small style='color:#b91c1c;'>Tips: Buat file <code>config/database.production.php</code> di Hostinger agar tidak pernah ter-overwrite saat git push/deploy.</small>");
    }

    mysqli_set_charset($conn, "utf8mb4");
}

// Global Timezone Enforcement (WIB / Asia/Jakarta GMT+7)
date_default_timezone_set('Asia/Jakarta');
if (isset($conn) && $conn) {
    mysqli_set_charset($conn, "utf8mb4");
    @mysqli_query($conn, "SET time_zone = '+07:00'");
}
