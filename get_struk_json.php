<?php
require_once __DIR__ . '/config/auth.php';
require_login();

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT p.*, u.username FROM pengajuan p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$p = mysqli_fetch_assoc($res);

if (!$p) {
    http_response_code(404);
    echo json_encode(['error' => 'Pengajuan tidak ditemukan']);
    exit;
}

$stmt_d = mysqli_prepare($conn, "SELECT * FROM pengajuan_detail WHERE pengajuan_id = ? ORDER BY id ASC");
mysqli_stmt_bind_param($stmt_d, "i", $id);
mysqli_stmt_execute($stmt_d);
$res_d = mysqli_stmt_get_result($stmt_d);

$items = [];
$total = 0.0;
while ($d = mysqli_fetch_assoc($res_d)) {
    $sub = (float)$d['jumlah'] * (float)$d['harga_satuan'];
    $total += $sub;
    $items[] = [
        'id' => $d['id'],
        'nama_barang' => $d['nama_barang'],
        'nama_jenis' => $d['nama_jenis'],
        'is_custom' => (bool)$d['is_custom'],
        'jumlah' => (float)$d['jumlah'],
        'satuan' => $d['satuan'],
        'harga_satuan' => (float)$d['harga_satuan'],
        'subtotal' => $sub
    ];
}

$struk_data = [
    'perusahaan' => 'ADAM JAYA ENTERPRISE',
    'alamat' => 'Bandung, Jawa Barat',
    'telepon' => '081234567890',
    'nota_id' => $p['custom_id'],
    'tanggal' => $p['created_at'],
    'admin' => $p['username'],
    'pembeli' => [
        'nama' => $p['nama_pembeli'],
        'telepon' => $p['telepon_pembeli']
    ],
    'status_pembayaran' => $p['status_pembayaran'],
    'status_pengiriman' => $p['status_pengiriman'],
    'jumlah_dibayar' => (float)$p['jumlah_dibayar'],
    'sisa_pembayaran' => (float)$p['sisa_pembayaran'],
    'items' => $items,
    'total_estimasi' => (float)$p['estimasi_dana'],
    'total_dihitung' => $total
];

echo json_encode($struk_data, JSON_PRETTY_PRINT);
