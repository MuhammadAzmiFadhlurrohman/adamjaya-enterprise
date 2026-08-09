<?php
require_once __DIR__ . '/config/auth.php';
require_admin();

$action = $_REQUEST['action'] ?? '';
$csrf_token = $_REQUEST['csrf_token'] ?? '';

if (!verify_csrf_token($csrf_token)) {
    set_flash('error', 'Gagal', 'Token CSRF tidak valid.');
    header('Location: daftar_user.php');
    exit;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'admin';
    $no_telepon = sanitize($_POST['no_telepon'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $lokasi = sanitize($_POST['lokasi'] ?? '');

    if (empty($username) || empty($password)) {
        set_flash('error', 'Gagal', 'Username dan password tidak boleh kosong.');
        header('Location: daftar_user.php');
        exit;
    }

    $pass_hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, role, no_telepon, email, lokasi) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssssss", $username, $pass_hash, $role, $no_telepon, $email, $lokasi);

    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', 'Berhasil', 'User baru telah berhasil ditambahkan.');
    } else {
        set_flash('error', 'Gagal', 'Gagal menambah user: ' . mysqli_error($conn));
    }
    header('Location: daftar_user.php');
    exit;

} else if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'admin';
    $no_telepon = sanitize($_POST['no_telepon'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $lokasi = sanitize($_POST['lokasi'] ?? '');

    if (!empty($password)) {
        $pass_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET username=?, password=?, role=?, no_telepon=?, email=?, lokasi=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssssssi", $username, $pass_hash, $role, $no_telepon, $email, $lokasi, $id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET username=?, role=?, no_telepon=?, email=?, lokasi=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssssi", $username, $role, $no_telepon, $email, $lokasi, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', 'Berhasil', 'Data user berhasil diperbarui.');
    } else {
        set_flash('error', 'Gagal', 'Gagal memperbarui user: ' . mysqli_error($conn));
    }
    header('Location: daftar_user.php');
    exit;

} else if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $curr_id = current_user()['id'];

    if ($id === $curr_id) {
        set_flash('error', 'Ditolak', 'Anda tidak dapat menghapus akun Anda sendiri.');
        header('Location: daftar_user.php');
        exit;
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', 'Berhasil', 'User berhasil dihapus.');
    } else {
        set_flash('error', 'Gagal', 'Gagal menghapus user.');
    }
    header('Location: daftar_user.php');
    exit;
}

header('Location: daftar_user.php');
exit;
