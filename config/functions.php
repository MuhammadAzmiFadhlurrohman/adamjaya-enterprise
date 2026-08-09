<?php
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
 * Format Angka ke Format Rupiah
 */
function formatRupiah($val, $with_prefix = true) {
    $num = (float)$val;
    $formatted = number_format($num, 0, ',', '.');
    return $with_prefix ? 'Rp ' . $formatted : $formatted;
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
 * Custom Auto-Increment ID Nota / Pengajuan (Angka Integer Murni)
 * Format Integer: YYYYMM0001 (Contoh: 2026080001)
 */
function generate_pengajuan_custom_id($conn) {
    $prefix = date('Ym');
    $query = "SELECT custom_id FROM pengajuan WHERE custom_id LIKE '{$prefix}%' ORDER BY custom_id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_id = $row['custom_id'];
        $last_num = (int)substr($last_id, strlen($prefix));
        $next_num = $last_num + 1;
    } else {
        $next_num = 1;
    }

    return $prefix . str_pad($next_num, 4, '0', STR_PAD_LEFT);
}

/**
 * Custom Auto-Increment ID Pengeluaran Kas (Angka Integer Murni)
 * Format Integer: 88YYYYMM0001 (Contoh: 882026080001)
 */
function generate_pengeluaran_custom_id($conn) {
    $prefix = '88' . date('Ym');
    $query = "SELECT custom_id FROM pengeluaran_header WHERE custom_id LIKE '{$prefix}%' ORDER BY custom_id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_id = $row['custom_id'];
        $last_num = (int)substr($last_id, strlen($prefix));
        $next_num = $last_num + 1;
    } else {
        $next_num = 1;
    }

    return $prefix . str_pad($next_num, 4, '0', STR_PAD_LEFT);
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
