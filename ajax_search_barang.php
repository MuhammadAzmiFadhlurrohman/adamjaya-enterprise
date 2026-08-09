<?php
require_once __DIR__ . '/config/auth.php';
require_login();

header('Content-Type: application/json');

$q = sanitize($_GET['q'] ?? '');

if (empty($q) || strlen(trim($q)) === 0) {
    echo json_encode([]);
    exit;
}

$search_term = "%$q%";
$query = "SELECT j.id as jenis_id, j.nama_jenis, j.satuan, j.harga, j.stok, b.id as barang_id, b.nama_barang 
          FROM jenis_barang j 
          JOIN stok_barang b ON j.barang_id = b.id 
          WHERE b.nama_barang LIKE ? OR j.nama_jenis LIKE ? 
          ORDER BY b.nama_barang ASC, j.nama_jenis ASC LIMIT 15";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $search_term, $search_term);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$results = [];
while ($row = mysqli_fetch_assoc($res)) {
    $results[] = [
        'barang_id' => (int)$row['barang_id'],
        'nama_barang' => $row['nama_barang'],
        'jenis_id' => (int)$row['jenis_id'],
        'nama_jenis' => $row['nama_jenis'],
        'satuan' => $row['satuan'],
        'harga' => (float)$row['harga'],
        'stok' => (float)$row['stok']
    ];
}

echo json_encode($results);
