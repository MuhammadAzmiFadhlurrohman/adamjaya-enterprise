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

if ($id > 0) {
    // Delete detail items first to prevent foreign key constraint failures
    @mysqli_query($conn, "DELETE FROM pengeluaran_detail WHERE pengeluaran_id = $id OR header_id = $id");
    
    $stmt = mysqli_prepare($conn, "DELETE FROM pengeluaran_header WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', 'Berhasil', 'Catatan pengeluaran kas berhasil dihapus.');
    } else {
        set_flash('error', 'Gagal', 'Gagal menghapus catatan pengeluaran: ' . mysqli_error($conn));
    }
} else {
    set_flash('error', 'Gagal', 'ID Pengeluaran tidak valid.');
}

header('Location: pengeluaran.php');
exit;
