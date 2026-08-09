<?php
require_once __DIR__ . '/config/auth.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$csrf_token = $_GET['csrf_token'] ?? '';

if (!verify_csrf_token($csrf_token)) {
    set_flash('error', 'Gagal', 'Token CSRF tidak valid.');
    header('Location: pengeluaran.php');
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM pengeluaran_header WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    set_flash('success', 'Berhasil', 'Catatan pengeluaran kas berhasil dihapus.');
} else {
    set_flash('error', 'Gagal', 'Gagal menghapus catatan pengeluaran.');
}

header('Location: pengeluaran.php');
exit;
