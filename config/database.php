<?php
// ========================================================
// KONFIGURASI DATABASE: ADAM JAYA ENTERPRISE
// ========================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'adamjaya_db');

// Inisialisasi Koneksi MySQLi
$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Buat database jika belum ada
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_select_db($conn, DB_NAME);
mysqli_set_charset($conn, "utf8mb4");

/**
 * Auto Init Database Tables & Default Users jika belum diimport
 */
function auto_init_db($conn) {
    // Cek apakah tabel users ada
    $check = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
    if (mysqli_num_rows($check) == 0) {
        $sql_file = __DIR__ . '/../database.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            mysqli_multi_query($conn, $sql);
            // Flush multi queries
            while (mysqli_more_results($conn) && mysqli_next_result($conn)) {
                if ($result = mysqli_store_result($conn)) {
                    mysqli_free_result($result);
                }
            }
        }
    }
}

auto_init_db($conn);
