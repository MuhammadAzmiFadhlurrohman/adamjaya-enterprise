<?php
require_once __DIR__ . '/config/auth.php';
require_login();

$bulan = sanitize($_GET['bulan'] ?? '');
$tahun = sanitize($_GET['tahun'] ?? date('Y'));
$status_pembayaran = sanitize($_GET['status_pembayaran'] ?? '');
$status_pengiriman = sanitize($_GET['status_pengiriman'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$where_clauses = ["1=1"];
$params = [];
$types = "";

if (!empty($bulan)) {
    $where_clauses[] = "MONTH(p.created_at) = ?";
    $params[] = (int)$bulan;
    $types .= "i";
}

if (!empty($tahun)) {
    $where_clauses[] = "YEAR(p.created_at) = ?";
    $params[] = (int)$tahun;
    $types .= "i";
}

if (!empty($status_pembayaran)) {
    $where_clauses[] = "p.status_pembayaran = ?";
    $params[] = $status_pembayaran;
    $types .= "s";
}

if (!empty($status_pengiriman)) {
    $where_clauses[] = "p.status_pengiriman = ?";
    $params[] = $status_pengiriman;
    $types .= "s";
}

if (!empty($search)) {
    $where_clauses[] = "(p.custom_id LIKE ? OR p.nama_pembeli LIKE ?)";
    $s_term = "%$search%";
    $params[] = $s_term;
    $params[] = $s_term;
    $types .= "ss";
}

$where_sql = implode(" AND ", $where_clauses);
$query = "SELECT p.*, u.username FROM pengajuan p JOIN users u ON p.user_id = u.id WHERE $where_sql ORDER BY p.id DESC";
$stmt = mysqli_prepare($conn, $query);

if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Set HTTP Headers untuk CSV/Excel Download
$filename = "Laporan_Pengajuan_AdamJaya_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// BOM UTF-8 agar karakter bahasa Indonesia terbaca sempurna di Excel
fputs($output, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header Kolom CSV
fputcsv($output, [
    'ID NOTA',
    'TANGGAL',
    'ADMIN PEMBUAT',
    'NAMA PEMBELI',
    'TELEPON PEMBELI',
    'TOTAL ESTIMASI DANA (RP)',
    'STATUS PEMBAYARAN',
    'STATUS PENGIRIMAN'
]);

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['custom_id'],
        date('Y-m-d H:i:s', strtotime($row['created_at'])),
        $row['username'],
        $row['nama_pembeli'],
        $row['telepon_pembeli'],
        (float)$row['estimasi_dana'],
        strtoupper($row['status_pembayaran']),
        strtoupper($row['status_pengiriman'])
    ]);
}

fclose($output);
exit;
