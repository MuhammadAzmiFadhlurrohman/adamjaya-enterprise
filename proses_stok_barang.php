<?php
require_once __DIR__ . '/config/auth.php';
require_admin();

$action = $_REQUEST['action'] ?? '';
$csrf_token = $_REQUEST['csrf_token'] ?? '';
$search = sanitize($_REQUEST['search'] ?? '');
$search_query_str = !empty($search) ? '&search=' . urlencode($search) : '';

if (!verify_csrf_token($csrf_token)) {
    set_flash('error', 'Gagal', 'Token CSRF tidak valid.');
    header('Location: stok_barang.php' . (!empty($search) ? '?search=' . urlencode($search) : ''));
    exit;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = sanitize($_POST['nama_barang'] ?? '');

    if (empty($nama_barang)) {
        set_flash('error', 'Gagal', 'Nama barang induk tidak boleh kosong.');
        header('Location: stok_barang.php' . (!empty($search) ? '?search=' . urlencode($search) : ''));
        exit;
    }

    $gambar_path = null;
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $new_name = 'barang_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $upload_dir = __DIR__ . '/uploads/barang/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $new_name)) {
                $gambar_path = 'uploads/barang/' . $new_name;
            }
        }
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO stok_barang (nama_barang, gambar) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ss", $nama_barang, $gambar_path);

    if (mysqli_stmt_execute($stmt)) {
        $new_id = mysqli_insert_id($conn);
        set_flash('success', 'Berhasil', 'Barang induk berhasil ditambahkan.');
        header("Location: stok_barang.php?scroll_to=row-barang-$new_id#row-barang-$new_id" . $search_query_str);
        exit;
    } else {
        set_flash('error', 'Gagal', 'Gagal menambah barang induk.');
        header('Location: stok_barang.php' . (!empty($search) ? '?search=' . urlencode($search) : ''));
        exit;
    }

} else if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $nama_barang = sanitize($_POST['nama_barang'] ?? '');

    $gambar_path = null;
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $new_name = 'barang_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $upload_dir = __DIR__ . '/uploads/barang/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $new_name)) {
                $gambar_path = 'uploads/barang/' . $new_name;
            }
        }
    }

    if ($gambar_path) {
        $stmt = mysqli_prepare($conn, "UPDATE stok_barang SET nama_barang=?, gambar=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssi", $nama_barang, $gambar_path, $id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE stok_barang SET nama_barang=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "si", $nama_barang, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', 'Berhasil', 'Data barang induk berhasil diperbarui.');
        header("Location: stok_barang.php?scroll_to=row-barang-$id#row-barang-$id" . $search_query_str);
        exit;
    } else {
        set_flash('error', 'Gagal', 'Gagal memperbarui barang induk.');
        header('Location: stok_barang.php' . (!empty($search) ? '?search=' . urlencode($search) : ''));
        exit;
    }

} else if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = mysqli_prepare($conn, "DELETE FROM stok_barang WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', 'Berhasil', 'Barang induk beserta seluruh variannya berhasil dihapus.');
    } else {
        set_flash('error', 'Gagal', 'Gagal menghapus barang induk.');
    }
    header('Location: stok_barang.php' . (!empty($search) ? '?search=' . urlencode($search) : ''));
    exit;
}

header('Location: stok_barang.php');
exit;
