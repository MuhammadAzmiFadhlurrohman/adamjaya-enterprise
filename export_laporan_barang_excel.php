<?php
require_once __DIR__ . '/config/auth.php';
require_login();

// ========================================================
// 1. FILTER & PENCARIAN PARAMETER
// ========================================================
$search     = trim($_GET['search'] ?? '');
$periode    = trim($_GET['periode'] ?? 'all');
$start_date = trim($_GET['start_date'] ?? '');
$end_date   = trim($_GET['end_date'] ?? '');
$sort       = trim($_GET['sort'] ?? 'omzet_desc');

// Setup Preset Tanggal jika Periode dipilih
$today = date('Y-m-d');
$periode_teks = "Semua Waktu";
if ($periode === 'today') {
    $start_date = $today;
    $end_date = $today;
    $periode_teks = "Hari Ini (" . date('d M Y') . ")";
} elseif ($periode === 'this_month') {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
    $periode_teks = "Bulan Ini (" . date('F Y') . ")";
} elseif ($periode === 'this_year') {
    $start_date = date('Y-01-01');
    $end_date = date('Y-12-31');
    $periode_teks = "Tahun Ini (" . date('Y') . ")";
} elseif (!empty($start_date) && !empty($end_date)) {
    $periode_teks = date('d M Y', strtotime($start_date)) . " s/d " . date('d M Y', strtotime($end_date));
}

// ========================================================
// 2. QUERY REKAP PENJUALAN PER BARANG
// ========================================================
$where_clauses = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "(pd.nama_barang LIKE ? OR pd.nama_jenis LIKE ?)";
    $s_term = "%$search%";
    $params[] = $s_term;
    $params[] = $s_term;
    $types .= "ss";
}

if (!empty($start_date)) {
    $where_clauses[] = "DATE(p.created_at) >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if (!empty($end_date)) {
    $where_clauses[] = "DATE(p.created_at) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Sorting Order
$order_by = "total_omzet DESC";
if ($sort === 'qty_desc') {
    $order_by = "total_qty DESC";
} elseif ($sort === 'frekuensi_desc') {
    $order_by = "total_frekuensi DESC";
} elseif ($sort === 'nama_asc') {
    $order_by = "pd.nama_barang ASC, pd.nama_jenis ASC";
}

$query = "
    SELECT 
        pd.nama_barang, 
        pd.nama_jenis, 
        pd.satuan,
        COUNT(DISTINCT pd.pengajuan_id) as total_frekuensi,
        SUM(pd.jumlah) as total_qty,
        SUM(pd.jumlah * pd.harga_satuan) as total_omzet,
        AVG(pd.harga_satuan) as avg_harga,
        MIN(p.created_at) as first_sale,
        MAX(p.created_at) as last_sale
    FROM pengajuan_detail pd
    JOIN pengajuan p ON pd.pengajuan_id = p.id
    $where_sql
    GROUP BY pd.nama_barang, pd.nama_jenis, pd.satuan
    ORDER BY $order_by
";

$stmt = mysqli_prepare($conn, $query);
if (count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$items_report = [];
$kpi_total_varian = 0;
$kpi_total_frekuensi = 0;
$kpi_total_qty = 0;
$kpi_total_omzet = 0;

while ($row = mysqli_fetch_assoc($res)) {
    $items_report[] = $row;
    $kpi_total_varian++;
    $kpi_total_frekuensi += (int)$row['total_frekuensi'];
    $kpi_total_qty += (float)$row['total_qty'];
    $kpi_total_omzet += (float)$row['total_omzet'];
}

// Set Headers untuk Excel (.xls)
$filename = "Laporan_Penjualan_Barang_AdamJaya_" . date('Ymd_His') . ".xls";
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
                    <x:Name>Laporan Penjualan Barang</x:Name>
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
        .report-subtitle { font-size: 11pt; font-weight: bold; color: #333333; }
        .filter-info { font-size: 9pt; color: #666666; font-style: italic; }
        
        table.excel-table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        table.excel-table th { background-color: #7A1E33; color: #FFD700; font-weight: bold; text-align: center; border: 1px solid #4A0B18; padding: 10px 8px; font-size: 10pt; }
        table.excel-table td { border: 1px solid #CCCCCC; padding: 7px 8px; font-size: 10pt; vertical-align: middle; }
        table.excel-table tr.even { background-color: #FDFBF7; }
        table.excel-table tr.odd { background-color: #FFFFFF; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }

        /* KPI Summary Table */
        table.kpi-table { border-collapse: collapse; width: 100%; margin-top: 8px; margin-bottom: 12px; }
        table.kpi-table th { background-color: #58101F; color: #FFFFFF; font-size: 9.5pt; font-weight: bold; padding: 6px 10px; border: 1px solid #440010; text-align: left; }
        table.kpi-table td { background-color: #FFFDF8; font-size: 12pt; font-weight: bold; color: #7A1E33; padding: 8px 10px; border: 1px solid #E5D5BA; }

        /* Total Row */
        tr.total-row td { background-color: #10B981; color: #FFFFFF; font-weight: bold; font-size: 11pt; border: 1px solid #059669; padding: 10px 8px; }
    </style>
</head>
<body>
    <!-- HEADER PERUSAHAAN & LAPORAN -->
    <table style="width: 100%;">
        <tr>
            <td colspan="8" style="vertical-align: middle; padding-bottom: 4px;">
                <div class="company-title">ADAM JAYA ENTERPRISE</div>
                <div class="report-subtitle">LAPORAN &amp; ANALISIS PENJUALAN BARANG (FREKUENSI, VOLUME &amp; OMZET)</div>
                <div class="filter-info">Periode: <?= e($periode_teks); ?> &middot; Diunduh pada: <?= date('d F Y, H:i:s'); ?> WIB &middot; Oleh: <?= e(ucwords((string)(current_user()['username'] ?? 'Admin'))); ?></div>
            </td>
        </tr>
        <tr><td colspan="8"></td></tr>
    </table>

    <!-- RINGKASAN KPI -->
    <table class="kpi-table">
        <tr>
            <th width="25%">TOTAL PRODUK &amp; VARIAN</th>
            <th width="25%">TOTAL FREKUENSI TERJUAL</th>
            <th width="25%">TOTAL VOLUME STOK KELUAR</th>
            <th width="25%">TOTAL OMZET PENJUALAN</th>
        </tr>
        <tr>
            <td><?= number_format($kpi_total_varian); ?> Produk/Varian</td>
            <td><?= number_format($kpi_total_frekuensi); ?> Kali Masuk Nota</td>
            <td><?= format_stok($kpi_total_qty); ?></td>
            <td style="color: #065F46;"><?= formatRupiah($kpi_total_omzet); ?></td>
        </tr>
    </table>

    <!-- TABEL DATA UTAMA -->
    <table class="excel-table">
        <thead>
            <tr>
                <th width="50">NO</th>
                <th width="80">PERINGKAT</th>
                <th width="220">NAMA BARANG</th>
                <th width="180">VARIAN / SPESIFIKASI</th>
                <th width="140">FREKUENSI TERJUAL</th>
                <th width="140">TOTAL KUANTITAS</th>
                <th width="150">RATA-RATA HARGA (RP)</th>
                <th width="180">TOTAL OMZET PENJUALAN (RP)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($items_report) > 0): ?>
                <?php 
                $no = 1;
                foreach ($items_report as $item): 
                    $row_class = ($no % 2 === 0) ? 'even' : 'odd';
                    $rank_label = '#' . $no;
                    if ($no === 1) { $rank_label = '#1 Terlaris'; }
                    elseif ($no === 2) { $rank_label = '#2 Terlaris'; }
                    elseif ($no === 3) { $rank_label = '#3 Terlaris'; }
                ?>
                    <tr class="<?= $row_class; ?>">
                        <td class="text-center"><?= $no; ?></td>
                        <td class="text-center text-bold"><?= $rank_label; ?></td>
                        <td class="text-bold"><?= e($item['nama_barang']); ?></td>
                        <td><?= e($item['nama_jenis'] ?: '-'); ?></td>
                        <td class="text-center"><?= number_format($item['total_frekuensi']); ?> Pesanan</td>
                        <td class="text-center text-bold"><?= format_stok($item['total_qty']); ?> <?= e($item['satuan'] ?: 'unit'); ?></td>
                        <td class="text-right"><?= formatRupiah($item['avg_harga']); ?></td>
                        <td class="text-right text-bold" style="color: #065F46;"><?= formatRupiah($item['total_omzet']); ?></td>
                    </tr>
                <?php 
                    $no++;
                endforeach; 
                ?>
                <!-- TOTAL FOOTER ROW -->
                <tr class="total-row">
                    <td colspan="4" class="text-right text-bold">TOTAL KESELURUHAN (<?= count($items_report); ?> BARANG):</td>
                    <td class="text-center text-bold"><?= number_format($kpi_total_frekuensi); ?> Kali</td>
                    <td class="text-center text-bold"><?= format_stok($kpi_total_qty); ?></td>
                    <td class="text-right">-</td>
                    <td class="text-right text-bold"><?= formatRupiah($kpi_total_omzet); ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px; color: #777777;">
                        Tidak ada data penjualan barang pada periode yang dipilih.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
