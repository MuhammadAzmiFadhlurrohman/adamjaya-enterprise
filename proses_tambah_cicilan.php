<?php
require_once __DIR__ . '/config/auth.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan.']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token keamanan (CSRF) tidak valid. Silakan refresh halaman.']);
    exit;
}

$pengajuan_id = (int)($_POST['pengajuan_id'] ?? 0);
$nominal_raw = $_POST['nominal_bayar'] ?? 0;
$nominal_bayar = unformatRupiah($nominal_raw);
$catatan = trim(sanitize($_POST['catatan'] ?? ''));

if ($pengajuan_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID Transaksi/Pengajuan tidak valid.']);
    exit;
}

if ($nominal_bayar <= 0) {
    echo json_encode(['success' => false, 'message' => 'Nominal bayar cicilan harus lebih dari Rp 0.']);
    exit;
}

// Fetch current transaction
$stmt = mysqli_prepare($conn, "SELECT id, estimasi_dana, jumlah_dibayar, sisa_pembayaran, status_pembayaran FROM pengajuan WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $pengajuan_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$p = mysqli_fetch_assoc($res);

if (!$p) {
    echo json_encode(['success' => false, 'message' => 'Data transaksi tidak ditemukan.']);
    exit;
}

$total_tagihan = (float)$p['estimasi_dana'];
$dibayar_lama = (float)$p['jumlah_dibayar'];
$sisa_sebelum = (float)$p['sisa_pembayaran'];

if ($sisa_sebelum <= 0 && $dibayar_lama > 0) {
    echo json_encode(['success' => false, 'message' => 'Transaksi ini sudah LUNAS 100%! Tidak dapat menambah cicilan lagi.']);
    exit;
}

$dibayar_baru = $dibayar_lama + $nominal_bayar;
if ($dibayar_baru >= $total_tagihan) {
    $dibayar_baru = $total_tagihan;
    $sisa_sesudah = 0.00;
    $status_baru = 'dibayar';
} else {
    $sisa_sesudah = $total_tagihan - $dibayar_baru;
    $status_baru = 'cicilan';
}

$user_id = $_SESSION['user_id'] ?? 1;
if (empty($catatan)) {
    $catatan = ($status_baru === 'dibayar') ? 'Pelunasan 100%' : 'Pembayaran Cicilan';
}

// Insert into riwayat_cicilan
$stmt_log = mysqli_prepare($conn, "INSERT INTO riwayat_cicilan (pengajuan_id, user_id, nominal_bayar, sisa_sebelum, sisa_sesudah, catatan) VALUES (?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt_log, "iiddds", $pengajuan_id, $user_id, $nominal_bayar, $sisa_sebelum, $sisa_sesudah, $catatan);
$saved_log = mysqli_stmt_execute($stmt_log);

if (!$saved_log) {
    echo json_encode(['success' => false, 'message' => 'Gagal mencatat log cicilan: ' . mysqli_error($conn)]);
    exit;
}

// Update pengajuan header
$stmt_upd = mysqli_prepare($conn, "UPDATE pengajuan SET jumlah_dibayar = ?, sisa_pembayaran = ?, status_pembayaran = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt_upd, "ddsi", $dibayar_baru, $sisa_sesudah, $status_baru, $pengajuan_id);
$updated = mysqli_stmt_execute($stmt_upd);

if ($updated) {
    echo json_encode([
        'success' => true,
        'message' => ($status_baru === 'dibayar') ? 'Pembayaran berhasil dicatat & Transaksi telah LUNAS 100%!' : 'Pembayaran cicilan sebesar ' . formatRupiah($nominal_bayar) . ' berhasil dicatat!',
        'status_pembayaran' => $status_baru,
        'jumlah_dibayar' => $dibayar_baru,
        'sisa_pembayaran' => $sisa_sesudah,
        'formatted_dibayar' => formatRupiah($dibayar_baru),
        'formatted_sisa' => formatRupiah($sisa_sesudah)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status transaksi: ' . mysqli_error($conn)]);
}
