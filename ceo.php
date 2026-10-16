<?php
require_once __DIR__ . '/includes/header.php';
require_login(); // Admin and CEO can view

// ========================================================
// FILTER DASHBOARD EXECUTIVE (STATUS & PERIODE)
// ========================================================
$filter_status = sanitize($_GET['status'] ?? ''); // '', 'lunas', 'piutang'
$filter_bulan = sanitize($_GET['bulan'] ?? '');   // '', '01'..'12'
$filter_tahun = sanitize($_GET['tahun'] ?? '');   // '', '2026', dst

$where_p = ["1=1"];
$params_p = [];
$types_p = "";

if ($filter_status === 'lunas') {
    $where_p[] = "p.status_pembayaran = 'dibayar'";
} elseif ($filter_status === 'piutang') {
    $where_p[] = "p.status_pembayaran != 'dibayar'";
}

if (!empty($filter_bulan)) {
    $where_p[] = "MONTH(p.created_at) = ?";
    $params_p[] = (int)$filter_bulan;
    $types_p .= "i";
}
if (!empty($filter_tahun)) {
    $where_p[] = "YEAR(p.created_at) = ?";
    $params_p[] = (int)$filter_tahun;
    $types_p .= "i";
}
$where_p_sql = implode(" AND ", $where_p);

// Query Executive Stats Terintegrasi
$query_kpi = "SELECT 
    COUNT(p.id) as total_trx,
    IFNULL(SUM(CAST(p.estimasi_dana AS DECIMAL(15,2))), 0) as total_omzet,
    IFNULL(SUM(CASE 
        WHEN p.status_pembayaran = 'dibayar' THEN CAST(p.estimasi_dana AS DECIMAL(15,2))
        WHEN p.status_pembayaran = 'cicilan' THEN CAST(p.jumlah_dibayar AS DECIMAL(15,2))
        ELSE 0 
    END), 0) as total_kas_masuk,
    IFNULL(SUM(CASE 
        WHEN p.status_pembayaran = 'dibayar' THEN 0
        WHEN p.status_pembayaran = 'cicilan' THEN CAST(p.sisa_pembayaran AS DECIMAL(15,2))
        ELSE CAST(p.estimasi_dana AS DECIMAL(15,2))
    END), 0) as total_piutang,
    COUNT(CASE WHEN p.status_pembayaran = 'dibayar' THEN 1 END) as trx_lunas,
    COUNT(CASE WHEN p.status_pembayaran != 'dibayar' THEN 1 END) as trx_piutang,
    COUNT(DISTINCT p.nama_pembeli) as total_pembeli
FROM pengajuan p
WHERE $where_p_sql";

$stmt_kpi = mysqli_prepare($conn, $query_kpi);
if (!empty($types_p)) {
    mysqli_stmt_bind_param($stmt_kpi, $types_p, ...$params_p);
}
mysqli_stmt_execute($stmt_kpi);
$row_kpi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_kpi));

// Filter Pengeluaran Kas Operasional Sesuai Periode
$where_exp = ["1=1"];
$params_exp = [];
$types_exp = "";
if (!empty($filter_bulan)) {
    $where_exp[] = "MONTH(tanggal) = ?";
    $params_exp[] = (int)$filter_bulan;
    $types_exp .= "i";
}
if (!empty($filter_tahun)) {
    $where_exp[] = "YEAR(tanggal) = ?";
    $params_exp[] = (int)$filter_tahun;
    $types_exp .= "i";
}
$where_exp_sql = implode(" AND ", $where_exp);

$stmt_exp = mysqli_prepare($conn, "SELECT IFNULL(SUM(total_pengeluaran), 0) as total_exp, COUNT(id) as total_exp_trx FROM pengeluaran_header WHERE $where_exp_sql");
if (!empty($types_exp)) {
    mysqli_stmt_bind_param($stmt_exp, $types_exp, ...$params_exp);
}
mysqli_stmt_execute($stmt_exp);
$row_pengeluaran = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_exp));

$total_kas_masuk = (float)($row_kpi['total_kas_masuk'] ?? 0);
$total_piutang = (float)($row_kpi['total_piutang'] ?? 0);
$total_omzet = (float)($row_kpi['total_omzet'] ?? 0);
$total_pengeluaran = (float)($row_pengeluaran['total_exp'] ?? 0);
$laba_bersih_kas = $total_kas_masuk - $total_pengeluaran;
$potensi_laba_total = ($total_kas_masuk + $total_piutang) - $total_pengeluaran;

// Query Stok
$res_stok = mysqli_query($conn, "SELECT COUNT(id) as total_varian, IFNULL(SUM(stok), 0) as total_unit FROM jenis_barang");
$row_stok = mysqli_fetch_assoc($res_stok);

// Dapatkan daftar tahun unik untuk dropdown filter
$res_years = mysqli_query($conn, "SELECT DISTINCT YEAR(created_at) as yr FROM pengajuan WHERE created_at IS NOT NULL ORDER BY yr DESC");
$available_years = [];
while ($y = mysqli_fetch_assoc($res_years)) {
    if (!empty($y['yr'])) $available_years[] = $y['yr'];
}
if (empty($available_years)) {
    $available_years = [date('Y')];
}

// Data Grafik Barang Paling Banyak Terjual / Dikeluarkan
$query_top_sold = "SELECT 
    pd.nama_barang,
    pd.nama_jenis,
    SUM(pd.jumlah) as total_qty 
FROM pengajuan_detail pd
JOIN pengajuan p ON pd.pengajuan_id = p.id
WHERE $where_p_sql
GROUP BY pd.nama_barang, pd.nama_jenis 
ORDER BY total_qty DESC LIMIT 6";

$stmt_sold = mysqli_prepare($conn, $query_top_sold);
if (!empty($types_p)) {
    mysqli_stmt_bind_param($stmt_sold, $types_p, ...$params_p);
}
mysqli_stmt_execute($stmt_sold);
$res_top_sold = mysqli_stmt_get_result($stmt_sold);
$sold_labels = [];
$sold_values = [];
if ($res_top_sold && mysqli_num_rows($res_top_sold) > 0) {
    while ($s = mysqli_fetch_assoc($res_top_sold)) {
        $name = trim($s['nama_barang']);
        if (!empty($s['nama_jenis']) && $s['nama_jenis'] !== '-') {
            $name .= ' (' . trim($s['nama_jenis']) . ')';
        }
        $name = trim(rtrim($name, ')'));

        $sold_labels[] = $name;
        $sold_values[] = (float)$s['total_qty'];
    }
}
if (empty($sold_labels)) {
    $sold_labels = ['Belum Ada Data'];
    $sold_values = [0];
}

// Data Grafik Pengadaan Bulanan
$query_monthly = "SELECT DATE_FORMAT(p.created_at, '%b %Y') as m_label, SUM(CAST(p.estimasi_dana AS DECIMAL(15,2))) as total 
                  FROM pengajuan p
                  WHERE $where_p_sql
                  GROUP BY YEAR(p.created_at), MONTH(p.created_at) 
                  ORDER BY p.created_at ASC LIMIT 12";
$stmt_monthly = mysqli_prepare($conn, $query_monthly);
if (!empty($types_p)) {
    mysqli_stmt_bind_param($stmt_monthly, $types_p, ...$params_p);
}
mysqli_stmt_execute($stmt_monthly);
$res_monthly = mysqli_stmt_get_result($stmt_monthly);
$month_labels = [];
$month_values = [];
while ($m = mysqli_fetch_assoc($res_monthly)) {
    $month_labels[] = $m['m_label'];
    $month_values[] = (float)$m['total'];
}

// Audit Persediaan Stok Barang Low Stock / Audit List
$query_audit = "SELECT j.*, b.nama_barang 
                FROM jenis_barang j 
                JOIN stok_barang b ON j.barang_id = b.id 
                ORDER BY j.stok ASC LIMIT 10";
$res_audit = mysqli_query($conn, $query_audit);

$audit_items = [];
if ($res_audit) {
    while ($row = mysqli_fetch_assoc($res_audit)) {
        $audit_items[] = $row;
    }
}
?>

<!-- HEADER CURVED EXECUTIVE BANNER -->
<header class="page-header">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <span class="page-eyebrow"><i class="fa-solid fa-crown"></i> Executive Suite &middot; Adam Jaya Enterprise</span>
            <h1 class="page-title">Executive Management Dashboard</h1>
            <p class="page-subtitle">Ringkasan performa finansial, pelunasan kas, piutang pelanggan, dan audit persediaan.</p>
        </div>
        <div class="header-action">
            <?php if (is_ceo()): ?>
                <span class="badge bg-warning text-dark px-3 py-2 fw-bold fs-6 shadow-sm">
                    <i class="fa-solid fa-eye me-1"></i> CEO READ-ONLY MONITORING MODE
                </span>
            <?php else: ?>
                <a href="insert_admin.php" class="btn-pengajuan-header">
                    <i class="fa-solid fa-list-check"></i> Kelola Transaksi
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- EXECUTIVE FILTER BAR -->
<div class="filter-card mb-4">
    <form method="GET" action="ceo.php">
        <div class="filter-row">
            <div class="filter-group">
                <label><i class="fa-solid fa-filter me-1 text-wine"></i> STATUS PEMBAYARAN</label>
                <select name="status" class="form-select">
                    <option value="">Semua Transaksi (Lunas & Piutang)</option>
                    <option value="lunas" <?= ($filter_status === 'lunas') ? 'selected' : ''; ?>>Hanya Sudah Lunas (Kas Masuk Penuh)</option>
                    <option value="piutang" <?= ($filter_status === 'piutang') ? 'selected' : ''; ?>>Hanya Piutang (Belum Lunas & Cicilan)</option>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fa-solid fa-calendar-days me-1 text-wine"></i> BULAN</label>
                <select name="bulan" class="form-select">
                    <option value="">Semua Bulan</option>
                    <?php 
                    $months = [
                        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                    ];
                    foreach ($months as $m_num => $m_name): 
                    ?>
                        <option value="<?= $m_num; ?>" <?= ($filter_bulan === $m_num) ? 'selected' : ''; ?>><?= $m_name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fa-solid fa-calendar me-1 text-wine"></i> TAHUN</label>
                <select name="tahun" class="form-select">
                    <option value="">Semua Tahun</option>
                    <?php foreach ($available_years as $yr): ?>
                        <option value="<?= $yr; ?>" <?= ($filter_tahun == $yr) ? 'selected' : ''; ?>><?= $yr; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-actions align-self-end">
                <button type="submit" class="btn btn-wine btn-sm rounded-pill px-3 py-2 fw-bold">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Terapkan Filter
                </button>
                <?php if (!empty($filter_status) || !empty($filter_bulan) || !empty($filter_tahun)): ?>
                    <a href="ceo.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2 fw-semibold">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- EXECUTIVE NET PROFIT & FINANCIAL HIGHLIGHT BANNER -->
<div class="p-3 p-md-4 rounded-3 text-white shadow-sm mb-4 position-relative overflow-hidden" style="background: linear-gradient(135deg, #1A2A4A 0%, #0A1628 100%); border: 1.5px solid #C9A84C;">
    <div class="row align-items-center g-3">
        <div class="col-12 col-lg-7">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge rounded-pill px-3 py-1.5 fw-bold" style="background: rgba(201, 168, 76, 0.2); color: #ffd700; border: 1px solid rgba(201, 168, 76, 0.4);">
                    <i class="fa-solid fa-scale-balanced me-1"></i> Ringkasan Laba Bersih Perusahaan
                </span>
                <?php if ($laba_bersih_kas >= 0): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-bold small">
                        <i class="fa-solid fa-arrow-trend-up me-1"></i> Surplus Kas
                    </span>
                <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 fw-bold small">
                        <i class="fa-solid fa-arrow-trend-down me-1"></i> Defisit Kas
                    </span>
                <?php endif; ?>
            </div>
            <h2 class="fw-bolder mb-1" style="color: #ffd700 !important; font-size: 2rem; letter-spacing: 0.5px;">
                <?= formatRupiah($laba_bersih_kas); ?>
            </h2>
            <p class="mb-0 text-white-50 small" style="line-height: 1.5;">
                <strong>Laba Bersih Kas Riil</strong> dihitung dari: 
                <span class="text-white fw-semibold">Pemasukan Diterima (<?= formatRupiah($total_kas_masuk); ?>)</span> 
                dikurangi 
                <span class="text-white fw-semibold">Kas Pengeluaran Beban (<?= formatRupiah($total_pengeluaran); ?>)</span>.
            </p>
        </div>
        <div class="col-12 col-lg-5 text-lg-end border-top border-lg-0 pt-2 pt-lg-0" style="border-color: rgba(255,255,255,0.1) !important;">
            <div class="p-2.5 rounded-3" style="background: rgba(255,255,255,0.06); border: 1px dashed rgba(201, 168, 76, 0.3);">
                <div class="small text-warning fw-semibold mb-1"><i class="fa-solid fa-clock-rotate-left me-1"></i> Potensi Laba Tambahan (Piutang)</div>
                <div class="text-white fw-bold mb-1" style="font-size: 1.15rem;"><?= formatRupiah($total_piutang); ?></div>
                <small class="text-white-50 d-block" style="font-size: 0.72rem;">Jika seluruh piutang berhasil ditagih, estimasi total keuntungan menjadi <strong class="text-white"><?= formatRupiah($potensi_laba_total); ?></strong></small>
            </div>
        </div>
    </div>
</div>

<!-- KPI Overview Cards: Transparan Pemisahan Lunas vs Piutang -->
<div class="row g-3 stats-row mb-4">
    <!-- Card 1: Total Nilai Transaksi (Bruto) -->
    <div class="col-6 col-lg-3 dash-reveal" style="--reveal-delay: 0.05s">
        <div class="stats-card h-100">
            <div class="card-body">
                <div class="stat-icon icon-wine"><i class="fa-solid fa-chart-line"></i></div>
                <div>
                    <div class="stat-value"><?= formatRupiah($total_omzet); ?></div>
                    <div class="stat-label">Total Omzet / Transaksi</div>
                    <small class="text-muted d-block mt-1" style="font-size: 0.73rem;">
                        <i class="fa-solid fa-receipt me-1 text-wine"></i> <?= number_format($row_kpi['total_trx'] ?? 0); ?> Total Transaksi
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Pemasukan Kas Diterima (Lunas & DP) -->
    <div class="col-6 col-lg-3 dash-reveal" style="--reveal-delay: 0.15s">
        <div class="stats-card h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="stat-icon icon-success"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div>
                    <div class="stat-value text-success"><?= formatRupiah($total_kas_masuk); ?></div>
                    <div class="stat-label">Pemasukan Kas Riil (Lunas)</div>
                    <small class="text-success fw-semibold d-block mt-1" style="font-size: 0.73rem;">
                        <i class="fa-solid fa-circle-check me-1"></i> <?= number_format($row_kpi['trx_lunas'] ?? 0); ?> Transaksi Lunas
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Piutang Berjalan (Belum Lunas & Cicilan) -->
    <div class="col-6 col-lg-3 dash-reveal" style="--reveal-delay: 0.25s">
        <div class="stats-card h-100 border-start border-4 border-danger">
            <div class="card-body">
                <div class="stat-icon icon-danger"><i class="fa-solid fa-hourglass-half"></i></div>
                <div>
                    <div class="stat-value text-danger"><?= formatRupiah($total_piutang); ?></div>
                    <div class="stat-label">Total Piutang Berjalan</div>
                    <small class="text-danger fw-semibold d-block mt-1" style="font-size: 0.73rem;">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <?= number_format($row_kpi['trx_piutang'] ?? 0); ?> Belum Lunas / Cicilan
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Pengeluaran Kas Operasional -->
    <div class="col-6 col-lg-3 dash-reveal" style="--reveal-delay: 0.35s">
        <div class="stats-card h-100">
            <div class="card-body">
                <div class="stat-icon icon-gold"><i class="fa-solid fa-wallet"></i></div>
                <div>
                    <div class="stat-value"><?= formatRupiah($total_pengeluaran); ?></div>
                    <div class="stat-label">Beban Kas Pengeluaran</div>
                    <small class="text-muted d-block mt-1" style="font-size: 0.73rem;">
                        <i class="fa-solid fa-file-invoice-dollar me-1 text-gold"></i> <?= number_format($row_pengeluaran['total_exp_trx'] ?? 0); ?> Catatan Beban Kas
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Info Row: Stok & Pelanggan -->
<div class="row g-3 stats-row mb-4">
    <div class="col-6 col-md-6 dash-reveal" style="--reveal-delay: 0.40s">
        <div class="stats-card">
            <div class="card-body py-2.5">
                <div class="stat-icon icon-gold" style="width: 40px; height: 40px; font-size: 1rem;"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div>
                    <div class="stat-value" style="font-size: 1.15rem;"><?= format_stok($row_stok['total_unit']); ?> Unit</div>
                    <div class="stat-label">Total Persediaan Gudang (<?= $row_stok['total_varian']; ?> Varian)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 dash-reveal" style="--reveal-delay: 0.45s">
        <div class="stats-card">
            <div class="card-body py-2.5">
                <div class="stat-icon icon-wine" style="width: 40px; height: 40px; font-size: 1rem;"><i class="fa-solid fa-users"></i></div>
                <div>
                    <div class="stat-value" style="font-size: 1.15rem;"><?= number_format($row_kpi['total_pembeli'] ?? 0); ?> Pembeli</div>
                    <div class="stat-label">Pelanggan Aktif Terdata</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION ANALISIS EXEC: CHART PENGELUARAN & TREN PENGADAAN (DIBUAT TERINTEGRASI DALAM 1 DASHBOARD EXECUTIVE) -->

<div class="row g-4 mb-4">
    <div class="col-md-6 dash-reveal" style="--reveal-delay: 0.45s">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chart-column text-wine me-2"></i> Tren Nilai Pengadaan Bulanan</h5>
                <span class="badge bg-gold-soft text-wine small fw-bold">Performa Periode</span>
            </div>
            <div style="position: relative; height: 260px;">
                <canvas id="chartPengadaan"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-6 dash-reveal" style="--reveal-delay: 0.55s">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-fire text-wine me-2"></i> Barang Paling Banyak Terjual</h5>
                <span class="badge bg-gold-soft text-wine small fw-bold">Top Selling Items</span>
            </div>
            <div style="position: relative; height: 260px;">
                <canvas id="chartTerjual"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- AUDIT STOK DAFTAR PERSEDIAAN BARANG -->
<div class="table-container mb-4">
    <div class="table-container-header d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 border-bottom">
        <h2 class="mb-0"><i class="fa-solid fa-shield-halved me-2 text-wine"></i> Audit Persediaan Stok Varian Terendah</h2>
        <a href="stok_barang.php" class="btn btn-sm btn-outline-indigo px-3 rounded-pill fw-bold" style="font-size: 0.78rem;">Lihat Seluruh Stok &rarr;</a>
    </div>

    <!-- DESKTOP VIEW (Table Format) -->
    <div class="table-responsive d-none d-md-block p-2">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Barang Induk & Varian</th>
                    <th>Satuan</th>
                    <th>Harga Satuan</th>
                    <th>Stok Tersedia</th>
                    <th>Status Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audit_items as $audit): 
                    $stok_val = (float)$audit['stok'];
                ?>
                    <tr>
                        <td>
                            <strong class="text-dark d-block"><?= e($audit['nama_barang']); ?></strong>
                            <small class="text-muted"><?= e($audit['nama_jenis']); ?></small>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= e($audit['satuan']); ?></span></td>
                        <td><strong class="text-success"><?= formatRupiah($audit['harga']); ?></strong></td>
                        <td>
                            <strong class="fs-6 <?= $stok_val <= 5 ? 'text-danger' : 'text-dark'; ?>">
                                <?= format_stok($stok_val); ?> <?= e($audit['satuan']); ?>
                            </strong>
                        </td>
                        <td>
                            <?php if ($stok_val <= 0): ?>
                                <span class="badge-status danger"><i class="fa-solid fa-triangle-exclamation"></i> STOK HABIS</span>
                            <?php elseif ($stok_val <= 5): ?>
                                <span class="badge-status warning"><i class="fa-solid fa-circle-exclamation"></i> MENIPIS</span>
                            <?php else: ?>
                                <span class="badge-status success"><i class="fa-solid fa-check"></i> AMAN</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- MOBILE VIEW (2 Columns Grid / Dua Jajar Ke Bawah - Lega & Rapi) -->
    <div class="d-block d-md-none p-3">
        <div class="row g-3">
            <?php foreach ($audit_items as $audit): 
                $stok_val = (float)$audit['stok'];
            ?>
                <div class="col-6">
                    <div class="card h-100 border border-light-subtle shadow-sm rounded-3 p-3 bg-white position-relative d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-light text-secondary border px-2 py-1 rounded-2" style="font-size: 0.68rem; font-weight: 600;">
                                <?= e($audit['satuan']); ?>
                            </span>
                            <?php if ($stok_val <= 0): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold" style="font-size: 0.64rem; padding: 3px 7px;">HABIS</span>
                            <?php elseif ($stok_val <= 5): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold" style="font-size: 0.64rem; padding: 3px 7px;">MENIPIS</span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold" style="font-size: 0.64rem; padding: 3px 7px;">AMAN</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-2">
                            <h6 class="fw-bold text-dark mb-1 text-truncate" style="font-size: 0.86rem; line-height: 1.25;" title="<?= e($audit['nama_barang']); ?>">
                                <?= e($audit['nama_barang']); ?>
                            </h6>
                            <small class="text-muted d-block text-truncate" style="font-size: 0.72rem; line-height: 1.2;" title="<?= e($audit['nama_jenis']); ?>">
                                <?= e($audit['nama_jenis']); ?>
                            </small>
                        </div>

                        <div class="pt-2 border-top d-flex align-items-center justify-content-between mt-auto">
                            <div class="small fw-bold text-success" style="font-size: 0.76rem;">
                                <?= formatRupiah($audit['harga']); ?>
                            </div>
                            <div class="small fw-bold <?= $stok_val <= 5 ? 'text-danger' : 'text-dark'; ?>" style="font-size: 0.8rem;">
                                <?= format_stok($stok_val); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {

    // ── Staggered reveal (cards & chart containers) ──
    document.querySelectorAll('.dash-reveal').forEach(el => {
        el.style.animationDelay = el.style.getPropertyValue('--reveal-delay') || '0s';
        el.classList.add('dash-reveal-active');
    });

    // ────────────────────────────────────────────────
    // Chart 1: Doughnut — Barang Paling Banyak Terjual
    // ────────────────────────────────────────────────
    const soldData   = <?= json_encode($sold_values); ?>;
    const soldLabels = <?= json_encode($sold_labels); ?>;
    const soldColors = ['#7A1E33','#C9973E','#1E8A5B','#1A2A4A','#58101F','#A5334C'];
    let soldCurrent  = soldData.map(() => 0);

    const ctxSold = document.getElementById('chartTerjual').getContext('2d');
    const donutChart = new Chart(ctxSold, {
        type: 'doughnut',
        data: {
            labels: soldLabels,
            datasets: [{
                data: [...soldCurrent],
                backgroundColor: soldColors,
                borderWidth: 4,
                borderColor: '#ffffff',
                hoverOffset: 14,
                spacing: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            animation: { duration: 600, easing: 'easeOutQuart' },
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 14,
                        font: { size: 12, weight: '600' }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) => '  ' + ctx.label + ': ' + ctx.parsed.toLocaleString('id-ID') + ' Qty'
                    }
                }
            }
        }
    });

    soldData.forEach((val, i) => {
        setTimeout(() => {
            soldCurrent[i] = val;
            donutChart.data.datasets[0].data = [...soldCurrent];
            donutChart.update();
        }, 400 + i * 350);
    });

    // ────────────────────────────────────────────────
    // Chart 2: Bar — Tren Nilai Pengadaan Bulanan (Tumbuh 1 per 1 dari 0)
    // ────────────────────────────────────────────────
    const barData   = <?= json_encode($month_values); ?>;
    const barLabels = <?= json_encode($month_labels); ?>;
    let barCurrent  = barData.map(() => 0);

    const ctxMonthly = document.getElementById('chartPengadaan').getContext('2d');
    const barGrad = ctxMonthly.createLinearGradient(0, 0, 0, 260);
    barGrad.addColorStop(0, '#C9973E');
    barGrad.addColorStop(0.35, '#A5334C');
    barGrad.addColorStop(1, '#58101F');

    const barChart = new Chart(ctxMonthly, {
        type: 'bar',
        data: {
            labels: barLabels,
            datasets: [{
                label: 'Total Nilai (Rp)',
                data: [...barCurrent],
                backgroundColor: barGrad,
                borderColor: '#C9973E',
                borderWidth: 1.5,
                borderRadius: { topLeft: 8, topRight: 8 },
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 550, easing: 'easeOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1A0308',
                    borderColor: '#C9973E',
                    borderWidth: 1,
                    titleColor: '#E8D5A0',
                    bodyColor: '#ffffff',
                    padding: 12,
                    callbacks: {
                        label: (ctx) => '  Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    border: { display: false },
                    ticks: {
                        color: '#6B7280',
                        font: { size: 11 },
                        callback: (val) => 'Rp ' + (val / 1000000).toFixed(0) + ' Jt'
                    }
                },
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: '#6B7280', font: { size: 11 } }
                }
            }
        }
    });

    barData.forEach((val, i) => {
        setTimeout(() => {
            barCurrent[i] = val;
            barChart.data.datasets[0].data = [...barCurrent];
            barChart.update();
        }, 300 + i * 200);
    });

});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
