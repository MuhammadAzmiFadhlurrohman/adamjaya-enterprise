<?php
require_once __DIR__ . '/includes/header.php';
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
if ($periode === 'today') {
    $start_date = $today;
    $end_date = $today;
} elseif ($periode === 'this_month') {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
} elseif ($periode === 'this_year') {
    $start_date = date('Y-01-01');
    $end_date = date('Y-12-31');
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
?>

<!-- HEADER CURVED EXECUTIVE BANNER -->
<header class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <span class="page-eyebrow d-none d-md-block"><i class="fa-solid fa-boxes-packing"></i> Laporan Manajemen Stok & Penjualan &middot; Adam Jaya</span>
            <h1 class="page-title">Laporan & Analisis Penjualan Barang</h1>
            <p class="page-subtitle mb-0">Rincian performa penjualan setiap produk, frekuensi pembelian, volume kuantitas, dan akumulasi omzet.</p>
        </div>
        <div class="header-action">
            <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-semibold shadow-sm" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i> Cetak Laporan
            </button>
            <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-semibold shadow-sm" onclick="exportToCSV()">
                <i class="fa-solid fa-file-excel me-1"></i> Ekspor CSV
            </button>
            <a href="stok_barang.php" class="btn-pengajuan-header">
                <i class="fa-solid fa-box-archive"></i> Master Barang
            </a>
        </div>
    </div>
</header>

<!-- SECTION 1: SUMMARY KPI METRIC CARDS -->
<div class="row g-2.5 stats-row mb-3">
    <!-- Card 1: Total Varian Produk -->
    <div class="col-6 col-md-3">
        <div class="stats-card">
            <div class="card-body">
                <div class="stat-icon icon-wine"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div>
                    <div class="stat-value"><?= number_format($kpi_total_varian); ?></div>
                    <div class="stat-label">Total Produk Terjual</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Frekuensi Pesanan -->
    <div class="col-6 col-md-3">
        <div class="stats-card">
            <div class="card-body">
                <div class="stat-icon icon-wine"><i class="fa-solid fa-cart-shopping"></i></div>
                <div>
                    <div class="stat-value"><?= number_format($kpi_total_frekuensi); ?> <small style="font-size:0.75rem; font-weight:600;">Kali</small></div>
                    <div class="stat-label">Frekuensi Masuk Nota</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Total Kuantitas Terjual -->
    <div class="col-6 col-md-3">
        <div class="stats-card">
            <div class="card-body">
                <div class="stat-icon icon-gold"><i class="fa-solid fa-scale-balanced"></i></div>
                <div>
                    <div class="stat-value"><?= format_stok($kpi_total_qty); ?></div>
                    <div class="stat-label">Volume Stok Keluar</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Total Omzet Penjualan (Rp) -->
    <div class="col-6 col-md-3">
        <div class="stats-card">
            <div class="card-body">
                <div class="stat-icon icon-success"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                <div>
                    <div class="stat-value text-success"><?= formatRupiah($kpi_total_omzet); ?></div>
                    <div class="stat-label">Total Omzet Penjualan</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 2: FILTER & PENCARIAN BAR -->
<div class="filter-card no-print">
    <form method="GET" action="laporan_barang.php" id="filterForm">
        <div class="filter-row">
            <!-- Filter Periode Preset -->
            <div class="filter-group">
                <label>PERIODE</label>
                <select name="periode" id="periodeSelect" class="form-select" onchange="toggleDateInputs(this.value)">
                    <option value="all" <?= ($periode === 'all') ? 'selected' : ''; ?>>Semua Waktu</option>
                    <option value="today" <?= ($periode === 'today') ? 'selected' : ''; ?>>Hari Ini</option>
                    <option value="this_month" <?= ($periode === 'this_month') ? 'selected' : ''; ?>>Bulan Ini</option>
                    <option value="this_year" <?= ($periode === 'this_year') ? 'selected' : ''; ?>>Tahun Ini</option>
                    <option value="custom" <?= ($periode === 'custom') ? 'selected' : ''; ?>>Custom Tanggal</option>
                </select>
            </div>

            <!-- Tanggal Mulai -->
            <div class="filter-group" id="startDateGroup">
                <label>DARI TANGGAL</label>
                <input type="date" name="start_date" id="startDateInput" class="form-control" value="<?= e($start_date); ?>">
            </div>

            <!-- Tanggal Selesai -->
            <div class="filter-group" id="endDateGroup">
                <label>SAMPAI TANGGAL</label>
                <input type="date" name="end_date" id="endDateInput" class="form-control" value="<?= e($end_date); ?>">
            </div>

            <!-- Pengurutan (Sort) -->
            <div class="filter-group">
                <label>URUTKAN</label>
                <select name="sort" class="form-select">
                    <option value="omzet_desc" <?= ($sort === 'omzet_desc') ? 'selected' : ''; ?>>💰 Omzet Terbesar (Rp)</option>
                    <option value="qty_desc" <?= ($sort === 'qty_desc') ? 'selected' : ''; ?>>⚖️ Kuantitas Terbanyak</option>
                    <option value="frekuensi_desc" <?= ($sort === 'frekuensi_desc') ? 'selected' : ''; ?>>🛒 Paling Sering Dibeli</option>
                    <option value="nama_asc" <?= ($sort === 'nama_asc') ? 'selected' : ''; ?>>🔤 Nama Barang (A - Z)</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="filter-actions">
                <button type="submit" class="btn btn-filter-primary"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <a href="laporan_barang.php" class="btn btn-filter-reset"><i class="fa-solid fa-rotate-left me-1"></i> Reset</a>
            </div>
        </div>

        <!-- Search Row -->
        <div class="filter-search-row">
            <div class="filter-search-group">
                <label><i class="fa-solid fa-magnifying-glass me-1"></i> Cari Nama Barang atau Varian</label>
                <div class="search-input-wrapper">
                    <i class="fa-solid fa-search search-icon"></i>
                    <input type="text" name="search" class="search-input-field" placeholder="Ketik nama produk (contoh: Plastik, Kain, Tampir, Katel...)" value="<?= e($search); ?>">
                    <?php if (!empty($search)): ?>
                        <a href="laporan_barang.php" class="search-clear-btn">&times;</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- SECTION 3: TABEL REKAPITULASI PENJUALAN BARANG -->
<div class="table-container mb-4">
    <div class="table-container-header">
        <div>
            <h2><i class="fa-solid fa-boxes-packing text-wine me-2"></i> Rekapitulasi Penjualan Seluruh Produk</h2>
            <small class="text-muted">
                Menampilkan <?= count($items_report); ?> barang 
                <?= (!empty($start_date) && !empty($end_date)) ? "periode " . date('d M Y', strtotime($start_date)) . " s/d " . date('d M Y', strtotime($end_date)) : "(Semua Periode)"; ?>
            </small>
        </div>
        <span class="badge-count"><?= count($items_report); ?> Item Terjual</span>
    </div>

    <div class="table-responsive">
        <table class="table align-middle table-hover" id="reportTable">
            <thead>
                <tr>
                    <th style="width: 80px;" class="text-center">Peringkat</th>
                    <th>Nama Barang & Varian</th>
                    <th class="text-center">Frekuensi Terjual</th>
                    <th class="text-center">Total Kuantitas</th>
                    <th class="text-end">Rata-rata Harga</th>
                    <th class="text-end">Total Omzet Penjualan</th>
                    <th class="text-center" style="width: 130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items_report) > 0): ?>
                    <?php 
                    $rank = 1;
                    foreach ($items_report as $item): 
                        $badge_class = 'bg-secondary';
                        $rank_label = '#' . $rank;
                        if ($rank === 1) { $badge_class = 'bg-warning text-dark'; $rank_label = '🔥 #1 Terlaris'; }
                        elseif ($rank === 2) { $badge_class = 'bg-light text-dark border'; $rank_label = '⭐ #2 Terlaris'; }
                        elseif ($rank === 3) { $badge_class = 'bg-info text-white'; $rank_label = '✨ #3 Terlaris'; }
                    ?>
                        <tr>
                            <!-- Peringkat -->
                            <td data-label="Peringkat" class="text-center">
                                <span class="badge <?= $badge_class; ?> px-2.5 py-1 fw-bold"><?= $rank_label; ?></span>
                            </td>

                            <!-- Nama Barang & Varian -->
                            <td data-label="Nama Barang & Varian">
                                <strong class="text-dark fs-6 d-block"><?= e($item['nama_barang']); ?></strong>
                                <?php if (!empty($item['nama_jenis']) && $item['nama_jenis'] !== '-'): ?>
                                    <small class="text-muted d-block mt-0.5">
                                        <i class="fa-solid fa-tag me-1 text-wine opacity-75"></i><?= e($item['nama_jenis']); ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>

                            <!-- Frekuensi Terjual (Berapa Kali Dibeli) -->
                            <td data-label="Frekuensi Terjual" class="text-center">
                                <span class="badge-status info px-2.5 py-1 fw-semibold">
                                    <i class="fa-solid fa-cart-shopping me-1"></i><?= $item['total_frekuensi']; ?> Pesanan
                                </span>
                            </td>

                            <!-- Total Kuantitas Terjual -->
                            <td data-label="Total Kuantitas" class="text-center">
                                <strong class="text-wine fs-6"><?= format_stok($item['total_qty']); ?> <?= e($item['satuan'] ?: 'unit'); ?></strong>
                            </td>

                            <!-- Rata-rata Harga Satuan -->
                            <td data-label="Rata-rata Harga" class="text-end text-muted">
                                <?= formatRupiah($item['avg_harga']); ?>
                            </td>

                            <!-- Total Omzet Penjualan -->
                            <td data-label="Total Omzet Penjualan" class="text-end">
                                <strong class="text-success amount-cell fs-6 fw-bold"><?= formatRupiah($item['total_omzet']); ?></strong>
                            </td>

                            <!-- Aksi: Riwayat Transaksi -->
                            <td data-label="Aksi" class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-wine rounded-pill px-3 py-1 fw-semibold shadow-xs" 
                                        onclick="showRiwayatBarang('<?= addslashes(e($item['nama_barang'])); ?>', '<?= addslashes(e($item['nama_jenis'] ?: '')); ?>', '<?= addslashes(e($item['satuan'] ?: '')); ?>')">
                                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Riwayat
                                </button>
                            </td>
                        </tr>
                    <?php 
                        $rank++;
                    endforeach; 
                    ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="no-data py-5">
                                <i class="fa-solid fa-boxes-stacked fs-1 text-secondary opacity-50 mb-3"></i>
                                <h5>Tidak Ada Data Penjualan Barang</h5>
                                <p class="text-muted">Coba sesuaikan kata kunci pencarian atau filter rentang tanggal di atas.</p>
                                <a href="laporan_barang.php" class="btn btn-outline-wine btn-sm rounded-pill mt-2">
                                    <i class="fa-solid fa-rotate-left me-1"></i> Reset Semua Filter
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL RIWAYAT TRANSAKSI PRODUK -->
<div class="modal fade" id="riwayatBarangModal" tabindex="-1" aria-labelledby="riwayatBarangModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass-modal rounded-4 shadow-lg border-0">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #7A1E33 0%, #58101F 100%); border-radius: 1rem 1rem 0 0;">
                <h5 class="modal-title fw-bold" id="riwayatBarangModalLabel">
                    <i class="fa-solid fa-clock-rotate-left me-2 text-warning"></i> Riwayat Transaksi Barang
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" id="riwayatBarangModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-wine" role="status">
                        <span class="visually-hidden">Memuat data...</span>
                    </div>
                    <div class="mt-2 text-muted small">Mengambil rincian transaksi barang...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DETAIL PENGAJUAN / FAKTUR (INNER INSPECT) -->
<div class="modal fade" id="detailFakturModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content glass-modal rounded-4 shadow-lg border-0">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #7A1E33 0%, #58101F 100%); border-radius: 1rem 1rem 0 0;">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-receipt me-2 text-warning"></i> Rincian Faktur Pengajuan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" id="detailFakturModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-wine" role="status">
                        <span class="visually-hidden">Memuat faktur...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.btn-outline-wine {
    color: var(--wine, #7A1E33);
    border-color: var(--wine, #7A1E33);
    background: transparent;
    transition: all 0.2s ease;
}
.btn-outline-wine:hover {
    color: #ffffff;
    background-color: var(--wine, #7A1E33);
    border-color: var(--wine, #7A1E33);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(122, 30, 51, 0.25);
}
.amount-cell {
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.02em;
}
.shadow-xs {
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
@media print {
    .no-print, #sidebar-wrapper, .page-header .header-action, .btn-sidebar-toggle {
        display: none !important;
    }
    #content-wrapper {
        margin: 0 !important;
        padding: 0 !important;
    }
    .page-header {
        background: none !important;
        color: black !important;
        padding: 0 !important;
        margin-bottom: 20px !important;
    }
    .page-header * {
        color: black !important;
    }
    .stats-card {
        border: 1px solid #ddd !important;
        break-inside: avoid;
    }
    .table-container {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<script>
function toggleDateInputs(val) {
    const startInput = document.getElementById('startDateInput');
    const endInput = document.getElementById('endDateInput');

    const today = new Date();
    const formatDate = (d) => d.toISOString().split('T')[0];

    if (val === 'today') {
        startInput.value = formatDate(today);
        endInput.value = formatDate(today);
    } else if (val === 'this_month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        startInput.value = formatDate(firstDay);
        endInput.value = formatDate(lastDay);
    } else if (val === 'this_year') {
        const firstDay = new Date(today.getFullYear(), 0, 1);
        const lastDay = new Date(today.getFullYear(), 11, 31);
        startInput.value = formatDate(firstDay);
        endInput.value = formatDate(lastDay);
    } else if (val === 'all') {
        startInput.value = '';
        endInput.value = '';
    }
}

function showRiwayatBarang(namaBarang, namaJenis, satuan) {
    const modalEl = document.getElementById('riwayatBarangModal');
    const modalBody = document.getElementById('riwayatBarangModalBody');
    const modal = new bootstrap.Modal(modalEl);

    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-wine" role="status">
                <span class="visually-hidden">Memuat data...</span>
            </div>
            <div class="mt-2 text-muted small">Mengambil riwayat transaksi <strong>${namaBarang}</strong>...</div>
        </div>
    `;

    modal.show();

    const startDate = document.getElementById('startDateInput')?.value || '';
    const endDate = document.getElementById('endDateInput')?.value || '';

    const queryParams = new URLSearchParams({
        nama_barang: namaBarang,
        nama_jenis: namaJenis,
        satuan: satuan,
        start_date: startDate,
        end_date: endDate
    });

    fetch(`get_riwayat_barang.php?${queryParams.toString()}`)
        .then(res => res.text())
        .then(html => {
            modalBody.innerHTML = html;
        })
        .catch(err => {
            modalBody.innerHTML = `
                <div class="alert alert-danger p-3">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> Gagal memuat data riwayat transaksi. Silakan coba kembali.
                </div>
            `;
        });
}

function openDetailFaktur(pengajuanId) {
    const modalEl = document.getElementById('detailFakturModal');
    const modalBody = document.getElementById('detailFakturModalBody');
    const modal = new bootstrap.Modal(modalEl);

    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-wine" role="status">
                <span class="visually-hidden">Memuat faktur...</span>
            </div>
        </div>
    `;

    modal.show();

    fetch(`get_pengajuan_detail.php?id=${pengajuanId}`)
        .then(res => res.text())
        .then(html => {
            modalBody.innerHTML = html;
        })
        .catch(err => {
            modalBody.innerHTML = `
                <div class="alert alert-danger p-3">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> Gagal memuat detail pengajuan.
                </div>
            `;
        });
}

function exportToCSV() {
    const table = document.getElementById("reportTable");
    let csv = [];
    const rows = table.querySelectorAll("tr");

    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        // Skip last column (Aksi)
        for (let j = 0; j < cols.length - 1; j++) {
            let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/\s+/g, " ").trim();
            text = text.replace(/"/g, '""');
            row.push('"' + text + '"');
        }
        if (row.length > 0) csv.push(row.join(","));
    }

    const csvFile = new Blob([csv.join("\n")], { type: "text/csv;charset=utf-8;" });
    const downloadLink = document.createElement("a");
    const filename = "Laporan_Penjualan_Barang_AdamJaya_" + new Date().toISOString().split('T')[0] + ".csv";

    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
