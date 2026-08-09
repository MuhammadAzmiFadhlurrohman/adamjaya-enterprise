<?php
require_once __DIR__ . '/config/auth.php';
require_login();

header('Content-Type: application/json');

$query = "SELECT id, nama_pembeli, telepon_pembeli, alamat_pembeli FROM favorit_pembeli ORDER BY nama_pembeli ASC";
$result = mysqli_query($conn, $query);

$favorit = [];
while ($row = mysqli_fetch_assoc($result)) {
    $favorit[] = $row;
}

echo json_encode($favorit);
