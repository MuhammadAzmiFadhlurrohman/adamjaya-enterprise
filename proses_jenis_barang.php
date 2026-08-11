<?php
require_once __DIR__ . '/config/auth.php';
require_admin();

$action = $_REQUEST['action'] ?? '';
$csrf_token = $_REQUEST['csrf_token'] ?? '';

if (!verify_csrf_token($csrf_token)) {
    set_flash('error', 'Gagal', 'Token CSRF tidak valid.');
    header('Location: jenis_barang.php');
    exit;
}

$user_id = current_user()['id'];
$redirect_barang_id = (int)($_REQUEST['redirect_barang_id'] ?? 0);
$redirect_url = $redirect_barang_id > 0 ? "jenis_barang.php?barang_id=$redirect_barang_id" : "jenis_barang.php";

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $barang_id = (int)($_POST['barang_id'] ?? 0);
    $nama_jenis = sanitize($_POST['nama_jenis'] ?? '');
    $stok = parseJumlah($_POST['stok'] ?? 0);
    $satuan = sanitize($_POST['satuan'] ?? 'pcs');
    $harga = unformatRupiah($_POST['harga'] ?? 0);

    if ($barang_id <= 0 || empty($nama_jenis)) {
        set_flash('error', 'Gagal', 'Barang Induk dan Nama Varian wajib diisi.');
        header("Location: $redirect_url");
        exit;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO jenis_barang (barang_id, nama_jenis, stok, satuan, harga) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isdsd", $barang_id, $nama_jenis, $stok, $satuan, $harga);

    if (mysqli_stmt_execute($stmt)) {
        $jenis_id = mysqli_insert_id($conn);
        
        // Log ke riwayat_stok
        $stmt_log = mysqli_prepare($conn, "INSERT INTO riwayat_stok (jenis_id, user_id, perubahan, stok_sebelum, stok_sesudah, aksi, keterangan, tanggal) VALUES (?, ?, ?, 0.00, ?, 'tambah', 'Penambahan varian barang baru', NOW())");
        mysqli_stmt_bind_param($stmt_log, "iidd", $jenis_id, $user_id, $stok, $stok);
        mysqli_stmt_execute($stmt_log);

        set_flash('success', 'Berhasil', 'Varian barang baru berhasil ditambahkan.');
        $anchor_url = $redirect_barang_id > 0 ? "jenis_barang.php?barang_id=$redirect_barang_id&scroll_to=row-jenis-$jenis_id#row-jenis-$jenis_id" : "jenis_barang.php?scroll_to=row-jenis-$jenis_id#row-jenis-$jenis_id";
        header("Location: $anchor_url");
        exit;
    } else {
        set_flash('error', 'Gagal', 'Gagal menyimpan varian barang.');
    }
    header("Location: $redirect_url");
    exit;

} else if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $barang_id = (int)($_POST['barang_id'] ?? 0);
    $nama_jenis = sanitize($_POST['nama_jenis'] ?? '');
    $stok_baru = parseJumlah($_POST['stok'] ?? 0);
    $satuan = sanitize($_POST['satuan'] ?? 'pcs');
    $harga = unformatRupiah($_POST['harga'] ?? 0);

    // Ambil stok sebelum
    $stmt_old = mysqli_prepare($conn, "SELECT stok FROM jenis_barang WHERE id = ?");
    mysqli_stmt_bind_param($stmt_old, "i", $id);
    mysqli_stmt_execute($stmt_old);
    $res_old = mysqli_stmt_get_result($stmt_old);
    $row_old = mysqli_fetch_assoc($res_old);
    $stok_sebelum = (float)($row_old['stok'] ?? 0);

    $stmt = mysqli_prepare($conn, "UPDATE jenis_barang SET barang_id=?, nama_jenis=?, stok=?, satuan=?, harga=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "isdsdi", $barang_id, $nama_jenis, $stok_baru, $satuan, $harga, $id);

    if (mysqli_stmt_execute($stmt)) {
        $perubahan = $stok_baru - $stok_sebelum;
        
        // Log ke riwayat_stok jika stok berubah
        if ($perubahan != 0) {
            $ket = "Pembaruan data varian & penyesuaian stok";
            $stmt_log = mysqli_prepare($conn, "INSERT INTO riwayat_stok (jenis_id, user_id, perubahan, stok_sebelum, stok_sesudah, aksi, keterangan, tanggal) VALUES (?, ?, ?, ?, ?, 'edit', ?, NOW())");
            mysqli_stmt_bind_param($stmt_log, "iiddss", $id, $user_id, $perubahan, $stok_sebelum, $stok_baru, $ket);
            mysqli_stmt_execute($stmt_log);
        }

        set_flash('success', 'Berhasil', 'Varian barang berhasil diperbarui.');
        $anchor_url = $redirect_barang_id > 0 ? "jenis_barang.php?barang_id=$redirect_barang_id&scroll_to=row-jenis-$id#row-jenis-$id" : "jenis_barang.php?scroll_to=row-jenis-$id#row-jenis-$id";
        header("Location: $anchor_url");
        exit;
    } else {
        set_flash('error', 'Gagal', 'Gagal memperbarui varian barang.');
    }
    header("Location: $redirect_url");
    exit;

} else if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);

    // Ambil stok sebelum hapus
    $stmt_old = mysqli_prepare($conn, "SELECT stok FROM jenis_barang WHERE id = ?");
    mysqli_stmt_bind_param($stmt_old, "i", $id);
    mysqli_stmt_execute($stmt_old);
    $res_old = mysqli_stmt_get_result($stmt_old);
    if ($row_old = mysqli_fetch_assoc($res_old)) {
        $stok_sebelum = (float)$row_old['stok'];
        
        // Log hapus stok
        $stmt_log = mysqli_prepare($conn, "INSERT INTO riwayat_stok (jenis_id, user_id, perubahan, stok_sebelum, stok_sesudah, aksi, keterangan, tanggal) VALUES (?, ?, ?, ?, 0.00, 'hapus', 'Penghapusan varian barang', NOW())");
        $neg_stok = -$stok_sebelum;
        mysqli_stmt_bind_param($stmt_log, "iidd", $id, $user_id, $neg_stok, $stok_sebelum);
        mysqli_stmt_execute($stmt_log);
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM jenis_barang WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', 'Berhasil', 'Varian barang berhasil dihapus.');
    } else {
        set_flash('error', 'Gagal', 'Gagal menghapus varian barang.');
    }
    header("Location: $redirect_url");
    exit;
}

header("Location: $redirect_url");
exit;
