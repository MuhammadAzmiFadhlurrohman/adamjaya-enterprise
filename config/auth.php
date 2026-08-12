<?php
// ========================================================
// AUTHENTICATION & ROLE-BASED ACCESS CONTROL (RBAC)
// ========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Auto self-heal & migrate cicilan database schema on all requests
if (isset($conn) && $conn) {
    ensure_cicilan_schema_exists($conn);
}

/**
 * Cek apakah user sudah login
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Ambil data user aktif
 */
function current_user() {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
        'no_telepon' => $_SESSION['no_telepon'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'lokasi' => $_SESSION['lokasi'] ?? ''
    ];
}

/**
 * Cek apakah role saat ini adalah CEO (Read-Only monitoring)
 */
function is_ceo() {
    return is_logged_in() && strtolower($_SESSION['role']) === 'ceo';
}

/**
 * Cek apakah role saat ini adalah Admin (Full Access)
 */
function is_admin() {
    return is_logged_in() && strtolower($_SESSION['role']) === 'admin';
}

/**
 * Guard: Wajib Login
 */
function require_login() {
    if (!is_logged_in()) {
        set_flash('error', 'Akses Ditolak', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        header('Location: login.php');
        exit;
    }
}

/**
 * Guard: Wajib Role Admin (Mencegah CEO melakukan operasi CUD)
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        set_flash('error', 'Akses Ditolak', 'Role CEO tidak memiliki izin untuk menambah, mengubah, atau menghapus data.');
        header('Location: ceo.php');
        exit;
    }
}

/**
 * Guard: Role CEO atau Admin
 */
function require_ceo() {
    require_login();
}
