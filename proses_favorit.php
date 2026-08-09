<?php
require_once __DIR__ . '/config/auth.php';
require_admin();

$action = $_REQUEST['action'] ?? '';
$csrf_token = $_REQUEST['csrf_token'] ?? '';

if (!verify_csrf_token($csrf_token)) {
    set_flash('error', 'Gagal', 'Token CSRF tidak valid.');
    header('Location: favorit_pembeli.php');
    exit;
}

$user_id = current_user()['id'];

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pembeli = sanitize($_POST['nama_pembeli'] ?? '');
    $telepon_pembeli = sanitize($_POST['telepon_pembeli'] ?? '');
    $alamat_pembeli = sanitize($_POST['alamat_pembeli'] ?? '');

    if (empty($nama_pembeli)) {
        set_flash('error', 'Gagal', 'Nama pembeli tidak boleh kosong.');
        header('Location: favorit_pembeli.php');
        exit;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO favorit_pembeli (user_id, nama_pembeli, telepon_pembeli, alamat_pembeli) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $nama_pembeli, $telepon_pembeli, $alamat_pembeli);

    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', 'Berhasil', 'Pembeli favorit berhasil ditambahkan.');
    } else {
        set_flash('error', 'Gagal', 'Gagal menyimpan pembeli favorit.');
    }
    header('Location: favorit_pembeli.php');
    exit;

} else if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $nama_pembeli = sanitize($_POST['nama_pembeli'] ?? '');
    $telepon_pembeli = sanitize($_POST['telepon_pembeli'] ?? '');
    $alamat_pembeli = sanitize($_POST['alamat_pembeli'] ?? '');

    $stmt = mysqli_prepare($conn, "UPDATE favorit_pembeli SET nama_pembeli=?, telepon_pembeli=?, alamat_pembeli=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "sssi", $nama_pembeli, $telepon_pembeli, $alamat_pembeli, $id);

    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', 'Berhasil', 'Data pembeli favorit berhasil diperbarui.');
    } else {
        set_flash('error', 'Gagal', 'Gagal memutakhirkan pembeli favorit.');
    }
    header('Location: favorit_pembeli.php');
    exit;

} else if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = mysqli_prepare($conn, "DELETE FROM favorit_pembeli WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', 'Berhasil', 'Pembeli favorit berhasil dihapus.');
    } else {
        set_flash('error', 'Gagal', 'Gagal menghapus pembeli favorit.');
    }
    header('Location: favorit_pembeli.php');
    exit;
}

header('Location: favorit_pembeli.php');
exit;
