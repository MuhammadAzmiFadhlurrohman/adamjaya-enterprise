<?php
require_once __DIR__ . '/config/auth.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login terlebih dahulu.']);
    exit;
}

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Hanya Admin yang dapat mengubah data.']);
    exit;
}

$action = sanitize($_POST['action'] ?? '');
$id = (int)($_POST['id'] ?? 0);
$csrf_token = $_POST['csrf_token'] ?? '';

if (!verify_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
    exit;
}

// Check if pengajuan exists
$stmt = mysqli_prepare($conn, "SELECT * FROM pengajuan WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$p) {
    echo json_encode(['success' => false, 'message' => 'Data pengajuan tidak ditemukan.']);
    exit;
}

if ($action === 'bayar_tanpa_bukti') {
    $stmt_u = mysqli_prepare($conn, "UPDATE pengajuan SET status_pembayaran = 'dibayar' WHERE id = ?");
    mysqli_stmt_bind_param($stmt_u, "i", $id);
    if (mysqli_stmt_execute($stmt_u)) {
        echo json_encode(['success' => true, 'message' => 'Status pembayaran berhasil diubah menjadi LUNAS DIBAYAR (Tanpa Bukti).']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengubah status pembayaran.']);
    }
    exit;
}

if ($action === 'bayar_dengan_bukti') {
    $metode = sanitize($_POST['metode'] ?? 'transfer'); // 'transfer' or 'tunai'
    if (!isset($_FILES['bukti_file']) || $_FILES['bukti_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Silakan pilih berkas bukti terlebih dahulu.']);
        exit;
    }

    $ext = strtolower(pathinfo($_FILES['bukti_file']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Format file harus berupa JPG, PNG, WEBP, atau PDF.']);
        exit;
    }

    $prefix = ($metode === 'tunai') ? 'tunai_' : 'tf_';
    $new_name = $prefix . $p['custom_id'] . '_' . time() . '.' . $ext;
    $upload_dir = __DIR__ . '/uploads/bukti/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    if (move_uploaded_file($_FILES['bukti_file']['tmp_name'], $upload_dir . $new_name)) {
        $path = 'uploads/bukti/' . $new_name;
        if ($metode === 'tunai') {
            $stmt_u = mysqli_prepare($conn, "UPDATE pengajuan SET status_pembayaran = 'dibayar', bukti_tunai = ? WHERE id = ?");
        } else {
            $stmt_u = mysqli_prepare($conn, "UPDATE pengajuan SET status_pembayaran = 'dibayar', bukti_transfer = ? WHERE id = ?");
        }
        mysqli_stmt_bind_param($stmt_u, "si", $path, $id);
        mysqli_stmt_execute($stmt_u);

        echo json_encode(['success' => true, 'message' => 'Bukti pembayaran berhasil diunggah dan status diubah menjadi LUNAS DIBAYAR.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan berkas ke server.']);
    }
    exit;
}

if ($action === 'batal_bayar') {
    $stmt_u = mysqli_prepare($conn, "UPDATE pengajuan SET status_pembayaran = 'belum_dibayar' WHERE id = ?");
    mysqli_stmt_bind_param($stmt_u, "i", $id);
    if (mysqli_stmt_execute($stmt_u)) {
        echo json_encode(['success' => true, 'message' => 'Status pembayaran berhasil dikembalikan ke BELUM DIBAYAR.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengembalikan status pembayaran.']);
    }
    exit;
}

if ($action === 'kirim') {
    $stmt_u = mysqli_prepare($conn, "UPDATE pengajuan SET status_pengiriman = 'sudah_dikirim' WHERE id = ?");
    mysqli_stmt_bind_param($stmt_u, "i", $id);
    if (mysqli_stmt_execute($stmt_u)) {
        echo json_encode(['success' => true, 'message' => 'Status pengiriman berhasil diubah menjadi SUDAH DIKIRIM.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengubah status pengiriman.']);
    }
    exit;
}

if ($action === 'batal_kirim') {
    $stmt_u = mysqli_prepare($conn, "UPDATE pengajuan SET status_pengiriman = 'belum_dikirim' WHERE id = ?");
    mysqli_stmt_bind_param($stmt_u, "i", $id);
    if (mysqli_stmt_execute($stmt_u)) {
        echo json_encode(['success' => true, 'message' => 'Status pengiriman dikembalikan ke BELUM DIKIRIM (PENDING).']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengembalikan status pengiriman.']);
    }
    exit;
}

if ($action === 'upload_kwitansi') {
    if (!isset($_FILES['bukti_kwitansi']) || $_FILES['bukti_kwitansi']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Silakan pilih berkas kwitansi terlebih dahulu.']);
        exit;
    }

    $ext = strtolower(pathinfo($_FILES['bukti_kwitansi']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Format file harus berupa JPG, PNG, WEBP, atau PDF.']);
        exit;
    }

    $new_name = 'kwitansi_' . $p['custom_id'] . '_' . time() . '.' . $ext;
    $upload_dir = __DIR__ . '/uploads/bukti/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    if (move_uploaded_file($_FILES['bukti_kwitansi']['tmp_name'], $upload_dir . $new_name)) {
        $path = 'uploads/bukti/' . $new_name;
        $stmt_u = mysqli_prepare($conn, "UPDATE pengajuan SET bukti_pembelian = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_u, "si", $path, $id);
        mysqli_stmt_execute($stmt_u);

        echo json_encode(['success' => true, 'message' => 'Bukti kwitansi nota berhasil diunggah.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan berkas kwitansi.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
