<?php
require_once __DIR__ . '/config/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pengeluaran.php');
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    set_flash('error', 'Gagal', 'Token CSRF tidak valid.');
    header('Location: tambah_pengeluaran.php');
    exit;
}

$user_id = (int)(current_user()['id'] ?? 0);
$u_check = mysqli_query($conn, "SELECT id FROM users WHERE id = $user_id");
if (!$u_check || mysqli_num_rows($u_check) === 0) {
    $u_fallback = mysqli_query($conn, "SELECT id FROM users ORDER BY id ASC LIMIT 1");
    if ($u_row = mysqli_fetch_assoc($u_fallback)) {
        $user_id = (int)$u_row['id'];
    }
}

$tanggal = sanitize($_POST['tanggal'] ?? date('Y-m-d'));
$keterangan = sanitize($_POST['keterangan'] ?? '');

// Deteksi indeks item dari POST (nama_item_N)
$item_indexes = [];
foreach ($_POST as $key => $val) {
    if (strpos($key, 'nama_item_') === 0) {
        $idx = str_replace('nama_item_', '', $key);
        $item_indexes[] = $idx;
    }
}

if (empty($item_indexes)) {
    set_flash('error', 'Gagal', 'Tidak ada rincian item pengeluaran.');
    header('Location: tambah_pengeluaran.php');
    exit;
}

mysqli_autocommit($conn, FALSE);

try {
    $custom_id = generate_pengeluaran_custom_id($conn);
    $grand_total = 0.0;
    $items_to_insert = [];

    foreach ($item_indexes as $idx) {
        $nama_item = sanitize($_POST["nama_item_$idx"] ?? 'Item Pengeluaran');
        $kategori = sanitize($_POST["kategori_$idx"] ?? 'Operasional');
        $jumlah = parseJumlah($_POST["jumlah_$idx"] ?? 1);
        $satuan = sanitize($_POST["satuan_$idx"] ?? 'unit');
        $harga_satuan = unformatRupiah($_POST["harga_satuan_$idx"] ?? 0);
        $total_harga = $jumlah * $harga_satuan;
        $grand_total += $total_harga;

        $items_to_insert[] = [
            'nama_item' => $nama_item,
            'kategori' => $kategori,
            'jumlah' => $jumlah,
            'satuan' => $satuan,
            'harga_satuan' => $harga_satuan,
            'total_harga' => $total_harga
        ];
    }

    // Insert Header
    $stmt_head = mysqli_prepare($conn, "INSERT INTO pengeluaran_header (custom_id, tanggal, total_pengeluaran, keterangan, user_id) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt_head, "ssdsi", $custom_id, $tanggal, $grand_total, $keterangan, $user_id);
    if (!mysqli_stmt_execute($stmt_head)) {
        throw new Exception("Gagal menyimpan header pengeluaran: " . mysqli_error($conn));
    }
    $pengeluaran_id = mysqli_insert_id($conn);

    // Deteksi nama kolom pada tabel pengeluaran_detail (header_id vs pengeluaran_id)
    $col_h = mysqli_query($conn, "SHOW COLUMNS FROM pengeluaran_detail LIKE 'header_id'");
    $has_header_id = ($col_h && mysqli_num_rows($col_h) > 0);
    $col_p = mysqli_query($conn, "SHOW COLUMNS FROM pengeluaran_detail LIKE 'pengeluaran_id'");
    $has_pengeluaran_id = ($col_p && mysqli_num_rows($col_p) > 0);

    foreach ($items_to_insert as $item) {
        if ($has_header_id && $has_pengeluaran_id) {
            $stmt_det = mysqli_prepare($conn, "INSERT INTO pengeluaran_detail (header_id, pengeluaran_id, nama_item, kategori, jumlah, satuan, harga_satuan, total_harga) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_det, "iissdsdd", $pengeluaran_id, $pengeluaran_id, $item['nama_item'], $item['kategori'], $item['jumlah'], $item['satuan'], $item['harga_satuan'], $item['total_harga']);
        } else if ($has_header_id) {
            $stmt_det = mysqli_prepare($conn, "INSERT INTO pengeluaran_detail (header_id, nama_item, kategori, jumlah, satuan, harga_satuan, total_harga) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_det, "issdsdd", $pengeluaran_id, $item['nama_item'], $item['kategori'], $item['jumlah'], $item['satuan'], $item['harga_satuan'], $item['total_harga']);
        } else {
            $stmt_det = mysqli_prepare($conn, "INSERT INTO pengeluaran_detail (pengeluaran_id, nama_item, kategori, jumlah, satuan, harga_satuan, total_harga) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_det, "issdsdd", $pengeluaran_id, $item['nama_item'], $item['kategori'], $item['jumlah'], $item['satuan'], $item['harga_satuan'], $item['total_harga']);
        }

        if (!mysqli_stmt_execute($stmt_det)) {
            throw new Exception("Gagal menyimpan detail pengeluaran: " . mysqli_error($conn));
        }
    }

    mysqli_commit($conn);
    mysqli_autocommit($conn, TRUE);

    set_flash('success', 'Berhasil', "Catatan pengeluaran kas #$custom_id berhasil disimpan.");
    header('Location: pengeluaran.php');
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    mysqli_autocommit($conn, TRUE);

    set_flash('error', 'Gagal', $e->getMessage());
    header('Location: tambah_pengeluaran.php');
    exit;
}
