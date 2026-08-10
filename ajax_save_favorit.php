<?php
require_once __DIR__ . '/config/auth.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak diizinkan']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

$nama = trim($data['nama_pembeli'] ?? '');
$telepon = trim($data['telepon_pembeli'] ?? '');

if (empty($nama)) {
    echo json_encode(['status' => 'error', 'message' => 'Nama pembeli tidak boleh kosong']);
    exit;
}

// Cek apakah sudah ada nama pembeli yang sama di database
$stmt_check = mysqli_prepare($conn, "SELECT id FROM favorit_pembeli WHERE LOWER(nama_pembeli) = LOWER(?)");
mysqli_stmt_bind_param($stmt_check, "s", $nama);
mysqli_stmt_execute($stmt_check);
$res_check = mysqli_stmt_get_result($stmt_check);

if ($existing = mysqli_fetch_assoc($res_check)) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Pembeli sudah ada di daftar favorit!',
        'id' => $existing['id'],
        'nama_pembeli' => $nama,
        'telepon_pembeli' => $telepon
    ]);
    exit;
}

// Insert baru ke favorit_pembeli
$stmt = mysqli_prepare($conn, "INSERT INTO favorit_pembeli (nama_pembeli, telepon_pembeli) VALUES (?, ?)");
mysqli_stmt_bind_param($stmt, "ss", $nama, $telepon);

if (mysqli_stmt_execute($stmt)) {
    $new_id = mysqli_insert_id($conn);
    echo json_encode([
        'status' => 'success',
        'message' => 'Data pembeli berhasil disimpan ke Favorit!',
        'id' => $new_id,
        'nama_pembeli' => $nama,
        'telepon_pembeli' => $telepon
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database']);
}
