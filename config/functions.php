<?php
// Always enforce Asia/Jakarta (WIB) timezone
date_default_timezone_set('Asia/Jakarta');

// ========================================================
// HELPER & UTILITY FUNCTIONS: ADAM JAYA ENTERPRISE
// ========================================================

/**
 * Sanitasi String (XSS Protection)
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function e($str) {
    return sanitize($str);
}

/**
 * Auto Migration Helper: Pastikan Tabel riwayat_stok ada di Database
 */
function ensure_riwayat_stok_table_exists($conn) {
    static $checked = false;
    if ($checked || !$conn) return;
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `riwayat_stok` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `jenis_id` int(11) NOT NULL,
      `user_id` int(11) NOT NULL,
      `aksi` varchar(50) NOT NULL,
      `stok_sebelum` decimal(10,4) NOT NULL DEFAULT 0.0000,
      `perubahan` decimal(10,4) NOT NULL DEFAULT 0.0000,
      `stok_sesudah` decimal(10,4) NOT NULL DEFAULT 0.0000,
      `keterangan` text DEFAULT NULL,
      `tanggal` datetime DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `jenis_id` (`jenis_id`),
      KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    
    // Auto self-heal outlier swapped QTY & Price records (e.g. QTY 15,000,000 vs Price 1.00)
    @mysqli_query($conn, "UPDATE pengajuan_detail SET harga_satuan = jumlah, jumlah = 1 WHERE jumlah >= 1000000 AND harga_satuan <= 10");
    $checked = true;
}

/**
 * Format Angka ke Format Rupiah
 */
function formatRupiah($val, $with_prefix = true) {
    $num = (float)$val;
    $formatted = number_format($num, 0, ',', '.');
    return $with_prefix ? 'Rp ' . $formatted : $formatted;
}

/**
 * Format Tanggal & Jam Indonesia (WIB)
 * Contoh: "11 Agu 2026, 12:43 WIB"
 */
function format_tanggal_indo($datetime, $include_time = true, $with_wib = true) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') return '-';
    
    $timestamp = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
    if (!$timestamp) return '-';

    $bulan_indo = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];

    $hari = date('d', $timestamp);
    $bulan = $bulan_indo[(int)date('m', $timestamp)] ?? date('M', $timestamp);
    $tahun = date('Y', $timestamp);

    $formatted = "$hari $bulan $tahun";
    if ($include_time) {
        $jam = date('H:i', $timestamp);
        $formatted .= ", $jam";
        if ($with_wib) {
            $formatted .= " WIB";
        }
    }

    return $formatted;
}

/**
 * Format Stok tanpa nol menggantung di belakang
 * Contoh: 25.0 -> 25 | 25.50 -> 25,5 | 25.75 -> 25,75
 */
function format_stok($val) {
    $num = (float)$val;
    if (floor($num) == $num) {
        return number_format($num, 0, ',', '.');
    }
    $formatted = number_format($num, 4, ',', '.');
    $formatted = rtrim($formatted, '0');
    $formatted = rtrim($formatted, ',');
    return $formatted;
}

/**
 * Parse input jumlah/kuantitas desimal murni
 */
function parseJumlah($val) {
    if (empty($val)) return 0.0;
    $cleaned = str_replace(',', '.', (string)$val);
    return (float)$cleaned;
}

/**
 * Membersihkan Format Rupiah menjadi Float/Decimal murni
 * Contoh: "Rp 1.450.000" -> 1450000.00 | "Rp 55.000,50" -> 55000.50
 */
function unformatRupiah($str) {
    if (empty($str) && $str !== '0' && $str !== 0) return 0.0;
    if (is_numeric($str)) return (float)$str;

    // Hapus Rp, spasi, dan huruf
    $cleaned = preg_replace('/[^\d,.-]/', '', (string)$str);
    if (empty($cleaned)) return 0.0;

    // Jika ada koma, titik adalah pemisah ribuan dan koma desimal
    if (strpos($cleaned, ',') !== false) {
        $cleaned = str_replace('.', '', $cleaned);
        $cleaned = str_replace(',', '.', $cleaned);
    } else {
        // Dalam string formatted Rupiah (misal "145.000" atau "8.500.000"), titik adalah pemisah ribuan
        $cleaned = str_replace('.', '', $cleaned);
    }
    return (float)$cleaned;
}

/**
 * CSRF Protection Helpers
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * SweetAlert Flash Message Helpers
 */
function set_flash($icon, $title, $text) {
    $_SESSION['flash_msg'] = [
        'icon' => $icon,
        'title' => $title,
        'text' => $text
    ];
}

function get_flash() {
    if (isset($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        return [
            'type' => $msg['icon'] ?? 'info',
            'title' => $msg['title'] ?? '',
            'message' => $msg['text'] ?? ''
        ];
    }
    return null;
}

function display_flash_msg() {
    $flash = get_flash();
    if ($flash) {
        $icon = e($flash['type']);
        $title = addslashes($flash['title']);
        $text = addslashes($flash['message']);
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: '{$icon}',
                    title: '{$title}',
                    text: '{$text}',
                    confirmButtonColor: '#7A1E33'
                });
            });
        </script>";
    }
}

/**
 * Custom 5-Digit Random ID Nota / Pengajuan (Contoh: 48291, 71940)
 */
function generate_pengajuan_custom_id($conn) {
    do {
        $rand_id = (string)mt_rand(10000, 99999);
        $stmt = mysqli_prepare($conn, "SELECT id FROM pengajuan WHERE custom_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $rand_id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $exists = ($res && mysqli_num_rows($res) > 0);
        } else {
            $exists = false;
        }
    } while ($exists);

    return $rand_id;
}

/**
 * Custom 5-Digit Random ID Pengeluaran Kas (Contoh: 38192, 90214)
 */
function generate_pengeluaran_custom_id($conn) {
    do {
        $rand_id = (string)mt_rand(10000, 99999);
        $stmt = mysqli_prepare($conn, "SELECT id FROM pengeluaran_header WHERE custom_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $rand_id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $exists = ($res && mysqli_num_rows($res) > 0);
        } else {
            $exists = false;
        }
    } while ($exists);

    return $rand_id;
}

/**
 * Mencari path relatif file bukti yang valid di folder uploads
 */
function resolve_bukti_path($path) {
    if (empty($path)) return null;
    $path = trim((string)$path, "`'\" ");
    if (empty($path) || strpos($path, '`') !== false || $path === 'bukti_transfer' || $path === 'bukti_tunai' || $path === 'bukti_pembelian') {
        return null;
    }
    
    $base_dir = dirname(__DIR__);
    if (file_exists($base_dir . '/' . $path)) {
        return $path;
    }
    
    $folders = ['uploads/bukti_transfer/', 'uploads/bukti_tunai/', 'uploads/bukti_pembelian/', 'uploads/bukti/', 'uploads/barang/'];
    foreach ($folders as $f) {
        if (file_exists($base_dir . '/' . $f . $path)) {
            return $f . $path;
        }
    }
    return null;
}
