<?php
require_once __DIR__ . '/config/auth.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$csrf_token = $_GET['csrf_token'] ?? '';

if (!verify_csrf_token($csrf_token)) {
    set_flash('error', 'Gagal', 'Token CSRF tidak valid.');
    header('Location: insert_admin.php');
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE pengajuan SET status_pembayaran = 'belum_dibayar' WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    set_flash('warning', 'Status Dibatalkan', 'Status pembayaran dikembalikan ke BELUM DIBAYAR.');
} else {
    set_flash('error', 'Gagal', 'Gagal membatalkan status pembayaran.');
}

header('Location: insert_admin.php');
exit;
