<?php
require_once __DIR__ . '/config/auth.php';
require_login();

$bulan = isset($_GET['bulan']) ? sanitize($_GET['bulan']) : date('m');
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
$query = "SELECT p.*, u.username FROM pengajuan p JOIN users u ON p.user_id = u.id WHERE $where_sql ORDER BY p.created_at DESC, p.id DESC";
$stmt = mysqli_prepare($conn, $query);

if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Nama bulan Bahasa Indonesia
$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

$bulan_teks = !empty($bulan) ? ($nama_bulan[str_pad($bulan, 2, '0', STR_PAD_LEFT)] ?? 'Bulan ' . $bulan) : 'Semua Bulan';
$tahun_teks = !empty($tahun) ? $tahun : 'Semua Tahun';

// Set Headers untuk Excel (.xls) Download dengan Formatted HTML
$filename = "Laporan_Pembelian_AdamJaya_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");
?>
<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Laporan Pembelian</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11pt; }
        .company-title { font-size: 16pt; font-weight: bold; color: #7A1E33; text-align: left; }
        .report-subtitle { font-size: 11pt; font-weight: bold; color: #555555; }
        .filter-info { font-size: 9pt; color: #777777; font-style: italic; }
        table.excel-table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        table.excel-table th { background-color: #7A1E33; color: #FFD700; font-weight: bold; text-align: center; border: 1px solid #4A0B18; padding: 10px 8px; font-size: 10pt; }
        table.excel-table td { border: 1px solid #CCCCCC; padding: 7px 8px; font-size: 10pt; vertical-align: middle; }
        table.excel-table tr.even { background-color: #FDFBF7; }
        table.excel-table tr.odd { background-color: #FFFFFF; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }

        /* Status Badges */
        .status-lunas { background-color: #D1FAE5; color: #065F46; font-weight: bold; text-align: center; }
        .status-dp { background-color: #FEF3C7; color: #92400E; font-weight: bold; text-align: center; }
        .status-belum { background-color: #FEE2E2; color: #991B1B; font-weight: bold; text-align: center; }

        .status-terkirim { background-color: #D1FAE5; color: #065F46; font-weight: bold; text-align: center; }
        .status-diproses { background-color: #E0E7FF; color: #3730A3; font-weight: bold; text-align: center; }
        .status-pending { background-color: #FEF3C7; color: #92400E; font-weight: bold; text-align: center; }

        /* Total Row */
        tr.total-row td { background-color: #10B981; color: #FFFFFF; font-weight: bold; font-size: 11pt; border: 1px solid #059669; padding: 10px 8px; }
    </style>
</head>
<body>
    <table style="width: 100%;">
        <tr>
            <td colspan="9" style="vertical-align: middle; padding-bottom: 4px;">
                <div class="company-title">ADAM JAYA ENTERPRISE</div>
                <div class="report-subtitle">LAPORAN PEMBELIAN BARANG &middot; Periode: <?= e($bulan_teks); ?> <?= e($tahun_teks); ?></div>
                <div class="filter-info">Diunduh pada: <?= date('d F Y, H:i:s'); ?> WIB &middot; Oleh: <?= e(ucwords((string)(current_user()['username'] ?? 'Admin'))); ?></div>
            </td>
        </tr>
        <tr><td colspan="9"></td></tr>
    </table>

    <table class="excel-table">
        <thead>
            <tr>
                <th width="40">NO</th>
                <th width="110">ID NOTA</th>
                <th width="160">TANGGAL &amp; WAKTU</th>
                <th width="120">ADMIN</th>
                <th width="160">NAMA PEMBELI</th>
                <th width="130">NO TELEPON</th>
                <th width="130">STATUS BAYAR</th>
                <th width="130">STATUS KIRIM</th>
                <th width="160">TOTAL ESTIMASI (RP)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $grand_total_semua = 0;
            if (mysqli_num_rows($result) > 0):
                while ($row = mysqli_fetch_assoc($result)):
                    $total_row = (float)$row['estimasi_dana'];
                    $grand_total_semua += $total_row;
                    $row_class = ($no % 2 === 0) ? 'even' : 'odd';

                    // Format Status Bayar Label & Style (Mendukung 'dibayar' dan 'lunas')
                    $st_bayar = strtolower($row['status_pembayaran'] ?? '');
                    $bayar_class = 'status-belum';
                    $bayar_label = 'Belum Dibayar';
                    if ($st_bayar === 'dibayar' || $st_bayar === 'lunas') {
                        $bayar_class = 'status-lunas';
                        $bayar_label = 'Dibayar';
                    } else if ($st_bayar === 'dp') {
                        $bayar_class = 'status-dp';
                        $bayar_label = 'DP';
                    }

                    // Format Status Kirim Label & Style (Mendukung 'sudah_dikirim', 'dikirim', 'terkirim')
                    $st_kirim = strtolower($row['status_pengiriman'] ?? '');
                    $kirim_class = 'status-pending';
                    $kirim_label = 'Pending';
                    if ($st_kirim === 'sudah_dikirim' || $st_kirim === 'dikirim' || $st_kirim === 'terkirim') {
                        $kirim_class = 'status-terkirim';
                        $kirim_label = 'Sudah Dikirim';
                    } else if ($st_kirim === 'diproses') {
                        $kirim_class = 'status-diproses';
                        $kirim_label = 'Diproses';
                    }
            ?>
                <tr class="<?= $row_class; ?>">
                    <td class="text-center"><?= $no++; ?></td>
                    <td class="text-center text-bold" style="mso-number-format:'\@';">'<?= e($row['custom_id']); ?></td>
                    <td class="text-center"><?= date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                    <td><?= e(ucwords((string)($row['username'] ?? ''))); ?></td>
                    <td><?= e(ucwords((string)($row['nama_pembeli'] ?? ''))); ?></td>
                    <td class="text-center" style="mso-number-format:'\@';">'<?= e($row['telepon_pembeli'] ?: '-'); ?></td>
                    <td class="<?= $bayar_class; ?>"><?= e($bayar_label); ?></td>
                    <td class="<?= $kirim_class; ?>"><?= e($kirim_label); ?></td>
                    <td class="text-right text-bold" style="mso-number-format:'\#\,\#\#0';"><?= number_format($total_row, 0, ',', '.'); ?></td>
                </tr>
            <?php
                endwhile;
            else:
            ?>
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px; color: #888;">Tidak ada data transaksi yang sesuai dengan filter.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="8" class="text-right">GRAND TOTAL ESTIMASI KESELURUHAN:</td>
                <td class="text-right" style="mso-number-format:'\#\,\#\#0';">Rp <?= number_format($grand_total_semua, 0, ',', '.'); ?></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
<?php
exit;
