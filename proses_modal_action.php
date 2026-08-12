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
    $metode_raw = sanitize($_POST['metode'] ?? 'Cash');
    $metode_val = (strtolower($metode_raw) === 'transfer') ? 'Transfer' : 'Cash';
    
    $grand_total = (float)$p['estimasi_dana'];
    $sisa_sebelum = (float)$p['sisa_pembayaran'];
    $user_id = $_SESSION['user_id'] ?? 1;

    $stmt_u = mysqli_prepare($conn, "UPDATE pengajuan SET status_pembayaran = 'dibayar', jumlah_dibayar = ?, sisa_pembayaran = 0.00 WHERE id = ?");
    mysqli_stmt_bind_param($stmt_u, "di", $grand_total, $id);
    if (mysqli_stmt_execute($stmt_u)) {
        if ($sisa_sebelum > 0) {
            $catatan_pelunasan = 'Pelunasan Instan Sisa Tagihan (Lunas 100%)';
            $stmt_log = mysqli_prepare($conn, "INSERT INTO riwayat_cicilan (pengajuan_id, user_id, nominal_bayar, metode_pembayaran, sisa_sebelum, sisa_sesudah, catatan) VALUES (?, ?, ?, ?, ?, 0.00, ?)");
            mysqli_stmt_bind_param($stmt_log, "iidsds", $id, $user_id, $sisa_sebelum, $metode_val, $sisa_sebelum, $catatan_pelunasan);
            mysqli_stmt_execute($stmt_log);
        }

        echo json_encode(['success' => true, 'message' => 'Pelunasan Instan berhasil! Sisa piutang kini Rp 0 dan transaksi LUNAS 100%.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengubah status pembayaran.']);
    }
    exit;
}

if ($action === 'bayar_dengan_bukti') {
    $metode = sanitize($_POST['metode'] ?? 'transfer');
    $metode_val = (strtolower($metode) === 'transfer') ? 'Transfer' : 'Cash';

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
        $grand_total = (float)$p['estimasi_dana'];
        $sisa_sebelum = (float)$p['sisa_pembayaran'];
        $user_id = $_SESSION['user_id'] ?? 1;

        if ($metode === 'tunai') {
            $stmt_u = mysqli_prepare($conn, "UPDATE pengajuan SET status_pembayaran = 'dibayar', jumlah_dibayar = ?, sisa_pembayaran = 0.00, bukti_tunai = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_u, "dsi", $grand_total, $path, $id);
        } else {
            $stmt_u = mysqli_prepare($conn, "UPDATE pengajuan SET status_pembayaran = 'dibayar', jumlah_dibayar = ?, sisa_pembayaran = 0.00, bukti_transfer = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_u, "dsi", $grand_total, $path, $id);
        }
        mysqli_stmt_execute($stmt_u);

        if ($sisa_sebelum > 0) {
            $catatan_pelunasan = 'Pelunasan Instan dengan Unggah Bukti (Lunas 100%)';
            $stmt_log = mysqli_prepare($conn, "INSERT INTO riwayat_cicilan (pengajuan_id, user_id, nominal_bayar, metode_pembayaran, sisa_sebelum, sisa_sesudah, catatan) VALUES (?, ?, ?, ?, ?, 0.00, ?)");
            mysqli_stmt_bind_param($stmt_log, "iidsds", $id, $user_id, $sisa_sebelum, $metode_val, $sisa_sebelum, $catatan_pelunasan);
            mysqli_stmt_execute($stmt_log);
        }

        echo json_encode(['success' => true, 'message' => 'Bukti pembayaran berhasil diunggah! Sisa piutang kini Rp 0 dan transaksi LUNAS 100%.']);
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
