<?php
require_once __DIR__ . '/config/auth.php';
require_login();

header('Content-Type: application/json');

$barang_id = (int)($_GET['barang_id'] ?? 0);

if ($barang_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT id, nama_jenis, stok, satuan, harga FROM jenis_barang WHERE barang_id = ? ORDER BY nama_jenis ASC");
    mysqli_stmt_bind_param($stmt, "i", $barang_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
} else {
    $res = mysqli_query($conn, "SELECT id, nama_jenis, stok, satuan, harga FROM jenis_barang ORDER BY nama_jenis ASC");
}

$data = [];
while ($row = mysqli_fetch_assoc($res)) {
    $data[] = [
        'id' => $row['id'],
        'nama_jenis' => $row['nama_jenis'],
        'stok' => (float)$row['stok'],
        'satuan' => $row['satuan'],
        'harga' => (float)$row['harga']
    ];
}

echo json_encode($data);
