<?php
require_once __DIR__ . '/includes/header.php';
require_login();

// ========================================================
// 1. DATA PENGELUARAN KAS
// ========================================================
// Expense by category
$query_cat = "SELECT d.kategori, COUNT(d.id) as total_trx, SUM(d.total_harga) as total_nominal 
              FROM pengeluaran_detail d 
              JOIN pengeluaran_header h ON d.pengeluaran_id = h.id 
              GROUP BY d.kategori ORDER BY total_nominal DESC";
$res_cat = mysqli_query($conn, $query_cat);

$cat_labels = [];
$cat_values = [];
$cat_table = [];
while ($c = mysqli_fetch_assoc($res_cat)) {
    $cat_labels[] = $c['kategori'];
    $cat_values[] = (float)$c['total_nominal'];
    $cat_table[] = $c;
}

// Expense by month
$query_month = "SELECT DATE_FORMAT(tanggal, '%b %Y') as m_label, SUM(total_pengeluaran) as total 
                FROM pengeluaran_header 
                GROUP BY YEAR(tanggal), MONTH(tanggal) 
                ORDER BY tanggal ASC LIMIT 12";
$res_month = mysqli_query($conn, $query_month);

$m_labels = [];
$m_values = [];
while ($m = mysqli_fetch_assoc($res_month)) {
    $m_labels[] = $m['m_label'];
    $m_values[] = (float)$m['total'];
}

// ========================================================
// 2. DATA TREN PENGADAAN & PELUNASAN (PEMBELIAN)
// ========================================================
$query_trend = "SELECT DATE_FORMAT(created_at, '%b %Y') as m_label, COUNT(id) as total_trx, SUM(CAST(estimasi_dana AS DECIMAL(15,2))) as total_val 
                FROM pengajuan 
                GROUP BY YEAR(created_at), MONTH(created_at) 
                ORDER BY created_at ASC LIMIT 12";
$res_trend = mysqli_query($conn, $query_trend);

$t_labels = [];
$t_values = [];
while ($t = mysqli_fetch_assoc($res_trend)) {
    $t_labels[] = $t['m_label'];
    $t_values[] = (float)$t['total_val'];
}

// Status bayar breakdown
$res_pay = mysqli_query($conn, "SELECT status_pembayaran, COUNT(id) as total FROM pengajuan GROUP BY status_pembayaran");
$pay_labels = [];
$pay_values = [];
while ($p = mysqli_fetch_assoc($res_pay)) {
    $pay_labels[] = ($p['status_pembayaran'] === 'dibayar') ? 'Sudah Lunas' : 'Belum Lunas';
    $pay_values[] = (int)$p['total'];
}

// ========================================================
// 3. DATA PEMBELI TERBANYAK (TOP BUYERS)
// ========================================================
$query_top_buyers = "SELECT nama_pembeli, telepon_pembeli, 
                            COUNT(id) as total_trx, 
                            SUM(CAST(estimasi_dana AS DECIMAL(15,2))) as total_belanja,
                            SUM(CASE WHEN status_pembayaran = 'dibayar' THEN 1 ELSE 0 END) as total_lunas
                     FROM pengajuan 
                     WHERE nama_pembeli IS NOT NULL AND TRIM(nama_pembeli) != ''
                     GROUP BY nama_pembeli, telepon_pembeli
                     ORDER BY total_belanja DESC LIMIT 10";
$res_top_buyers = mysqli_query($conn, $query_top_buyers);
$top_buyers_list = [];
$buyer_labels = [];
$buyer_values = [];
while ($b = mysqli_fetch_assoc($res_top_buyers)) {
    $top_buyers_list[] = $b;
    if (count($buyer_labels) < 5) {
        $buyer_labels[] = $b['nama_pembeli'];
        $buyer_values[] = (float)$b['total_belanja'];
    }
}

// ========================================================
// 4. DATA STOK PALING BANYAK DIBELI (MOST PURCHASED STOCK)
// ========================================================
$query_top_stock = "SELECT pd.nama_barang, pd.nama_jenis, pd.satuan, 
                           SUM(pd.jumlah) as total_qty, 
                           SUM(pd.jumlah * pd.harga_satuan) as total_nominal,
                           COUNT(DISTINCT pd.pengajuan_id) as total_pesanan
                    FROM pengajuan_detail pd
                    JOIN pengajuan p ON pd.pengajuan_id = p.id
                    GROUP BY pd.nama_barang, pd.nama_jenis, pd.satuan
                    ORDER BY total_qty DESC LIMIT 10";
$res_top_stock = mysqli_query($conn, $query_top_stock);
$top_stock_list = [];
$stock_labels = [];
$stock_values = [];
while ($s = mysqli_fetch_assoc($res_top_stock)) {
    $top_stock_list[] = $s;
    if (count($stock_labels) < 5) {
        $label = $s['nama_barang'] . ($s['nama_jenis'] ? ' ('.$s['nama_jenis'].')' : '');
        $stock_labels[] = $label;
        $stock_values[] = (float)$s['total_qty'];
    }
}
?>

<!-- HEADER CURVED EXECUTIVE BANNER -->
<header class="page-header">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <span class="page-eyebrow"><i class="fa-solid fa-chart-pie"></i> Business Intelligence &middot; Adam Jaya</span>
            <h1 class="page-title">Grafik & Analisis Business Intelligence</h1>
            <p class="page-subtitle">Visualisasi statistik terpadu untuk tren pengadaan barang dan analisis pengeluaran kas operasional.</p>
        </div>
        <div class="header-action">
            <a href="insert_admin.php" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-semibold">
                <i class="fa-solid fa-cart-flatbed me-1"></i> Data Transaksi
            </a>
            <a href="pengeluaran.php" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-semibold">
                <i class="fa-solid fa-wallet me-1"></i> Data Pengeluaran
            </a>
        </div>
    </div>
</header>

<!-- SECTION 1: GRAFIK TREN PENGADAAN & PELUNASAN -->
<div class="glass-card p-4 mb-4">
    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-chart-column text-wine me-2"></i> 1. Statistik Tren Pengadaan & Rasio Pelunasan</h5>
    <div class="row g-4">
        <div class="col-lg-8">
            <h6 class="text-muted small fw-semibold">TREN NILAI TRANSAKSI PENGADAAN BULANAN (RP)</h6>
            <div style="position: relative; height: 250px;">
                <canvas id="trendValueChart"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <h6 class="text-muted small fw-semibold">RASIO PELUNASAN PEMBAYARAN NOTA</h6>
            <div style="position: relative; height: 250px;">
                <canvas id="payRatioChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 2: GRAFIK PENGELUARAN OPERASIONAL -->
<div class="glass-card p-4 mb-4">
    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-chart-pie text-gold me-2"></i> 2. Visualisasi Grafik Pengeluaran Kas Operasional</h5>
    <div class="row g-4">
        <div class="col-lg-6">
            <h6 class="text-muted small fw-semibold">DISTRIBUSI PENGELUARAN PER KATEGORI</h6>
            <div style="position: relative; height: 250px;">
                <canvas id="statCatChart"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <h6 class="text-muted small fw-semibold">TREN PENGELUARAN OPERASIONAL BULANAN</h6>
            <div style="position: relative; height: 250px;">
                <canvas id="statMonthChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 3: GRAFIK PEMBELI TERBANYAK & STOK PALING BANYAK DIBELI -->
<div class="glass-card p-4 mb-4">
    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-trophy text-wine me-2"></i> 3. Visualisasi Pembeli Terbanyak & Stok Paling Banyak Dibeli</h5>
    <div class="row g-4">
        <div class="col-lg-6">
            <h6 class="text-muted small fw-semibold text-uppercase">TOP 5 PEMBELI TERBANYAK (TOTAL NOMINAL BELANJA)</h6>
            <div style="position: relative; height: 260px;">
                <canvas id="buyerChart"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <h6 class="text-muted small fw-semibold text-uppercase">TOP 5 STOK PALING BANYAK DIBELI (TOTAL KUANTITAS)</h6>
            <div style="position: relative; height: 260px;">
                <canvas id="topStockChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 4: TABEL RANKING PEMBELI TERBANYAK -->
<div class="table-container mb-4">
    <div class="table-container-header">
        <h2><i class="fa-solid fa-users-viewfinder me-2 text-wine"></i> Ranking Pembeli Terbanyak (Top Buyers Leaderboard)</h2>
        <span class="badge-count"><?= count($top_buyers_list); ?> Pelanggan Utama</span>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th style="width: 80px;">Peringkat</th>
                    <th>Nama Pembeli & Kontak</th>
                    <th>Jumlah Transaksi</th>
                    <th>Status Pelunasan</th>
                    <th>Total Nilai Pembelian</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($top_buyers_list) > 0): ?>
                    <?php 
                    $rank = 1;
                    foreach ($top_buyers_list as $tb): 
                        $badge_class = 'bg-secondary';
                        $rank_label = '#' . $rank;
                        if ($rank === 1) { $badge_class = 'bg-warning text-dark'; $rank_label = '🥇 Top #1'; }
                        elseif ($rank === 2) { $badge_class = 'bg-light text-dark border'; $rank_label = '🥈 Top #2'; }
                        elseif ($rank === 3) { $badge_class = 'bg-info text-white'; $rank_label = '🥉 Top #3'; }
                    ?>
                        <tr>
                            <td data-label="Peringkat">
                                <span class="badge <?= $badge_class; ?> px-2 py-1 fw-bold"><?= $rank_label; ?></span>
                            </td>
                            <td data-label="Nama Pembeli & Kontak">
                                <strong class="text-dark fs-6 d-block"><?= e($tb['nama_pembeli']); ?></strong>
                                <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= e($tb['telepon_pembeli'] ?: '-'); ?></small>
                            </td>
                            <td data-label="Jumlah Transaksi">
                                <span class="badge-status info"><i class="fa-solid fa-receipt me-1"></i><?= $tb['total_trx']; ?> Transaksi</span>
                            </td>
                            <td data-label="Status Pelunasan">
                                <span class="badge-status success"><i class="fa-solid fa-circle-check me-1"></i><?= $tb['total_lunas']; ?> Lunas</span>
                                <?php if ($tb['total_trx'] - $tb['total_lunas'] > 0): ?>
                                    <span class="badge-status warning ms-1"><i class="fa-solid fa-clock me-1"></i><?= ($tb['total_trx'] - $tb['total_lunas']); ?> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Total Nilai Pembelian">
                                <strong class="text-success amount-cell fs-6"><?= formatRupiah($tb['total_belanja']); ?></strong>
                            </td>
                        </tr>
                    <?php 
                        $rank++;
                    endforeach; 
                    ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">
                            <div class="no-data">
                                <i class="fa-solid fa-user-slash"></i>
                                <h5>Belum Ada Data Pembeli</h5>
                                <p class="text-muted">Data pembeli terbanyak akan otomatis terkalkulasi setelah transaksi dibuat.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- SECTION 5: TABEL RANKING STOK PALING BANYAK DIBELI -->
<div class="table-container mb-4">
    <div class="table-container-header">
        <h2><i class="fa-solid fa-fire me-2 text-wine"></i> Ranking Stok Paling Banyak Dibeli (Most Popular Stock)</h2>
        <span class="badge-count"><?= count($top_stock_list); ?> Barang Terlaris</span>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th style="width: 80px;">Peringkat</th>
                    <th>Nama Barang & Varian</th>
                    <th>Total Kuantitas Terjual</th>
                    <th>Frekuensi Pesanan</th>
                    <th>Total Nominal Transaksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($top_stock_list) > 0): ?>
                    <?php 
                    $rank = 1;
                    foreach ($top_stock_list as $ts): 
                        $badge_class = 'bg-secondary';
                        $rank_label = '#' . $rank;
                        if ($rank === 1) { $badge_class = 'bg-warning text-dark'; $rank_label = '🔥 #1 Terlaris'; }
                        elseif ($rank === 2) { $badge_class = 'bg-light text-dark border'; $rank_label = '⭐ #2 Terlaris'; }
                        elseif ($rank === 3) { $badge_class = 'bg-info text-white'; $rank_label = '✨ #3 Terlaris'; }
                    ?>
                        <tr>
                            <td data-label="Peringkat">
                                <span class="badge <?= $badge_class; ?> px-2 py-1 fw-bold"><?= $rank_label; ?></span>
                            </td>
                            <td data-label="Nama Barang & Varian">
                                <strong class="text-dark fs-6 d-block"><?= e($ts['nama_barang']); ?></strong>
                                <small class="text-muted"><?= e($ts['nama_jenis'] ?: '-'); ?></small>
                            </td>
                            <td data-label="Total Kuantitas Terjual">
                                <strong class="text-wine fs-6"><?= format_stok($ts['total_qty']); ?> <?= e($ts['satuan']); ?></strong>
                            </td>
                            <td data-label="Frekuensi Pesanan">
                                <span class="badge-status info"><i class="fa-solid fa-cart-shopping me-1"></i><?= $ts['total_pesanan']; ?> Pesanan</span>
                            </td>
                            <td data-label="Total Nominal Transaksi">
                                <strong class="text-success amount-cell fs-6"><?= formatRupiah($ts['total_nominal']); ?></strong>
                            </td>
                        </tr>
                    <?php 
                        $rank++;
                    endforeach; 
                    ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">
                            <div class="no-data">
                                <i class="fa-solid fa-boxes-stacked"></i>
                                <h5>Belum Ada Data Item Dibeli</h5>
                                <p class="text-muted">Data stok paling banyak dibeli akan muncul setelah ada rincian item pengajuan.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- SECTION 6: RINCIAN TABEL PENGELUARAN PER KATEGORI -->
<div class="table-container mb-4">
    <div class="table-container-header">
        <h2><i class="fa-solid fa-list-check me-2 text-wine"></i> Rincian Nominal Beban Pengeluaran Per Kategori</h2>
        <span class="badge-count"><?= count($cat_table); ?> Kategori</span>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Kategori Beban</th>
                    <th>Jumlah Transaksi</th>
                    <th>Total Nominal Pengeluaran</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($cat_table) > 0): ?>
                    <?php foreach ($cat_table as $ct): ?>
                        <tr>
                            <td data-label="Kategori Beban">
                                <strong class="text-dark fs-6"><?= e($ct['kategori']); ?></strong>
                            </td>
                            <td data-label="Jumlah Transaksi">
                                <span class="badge-status info"><i class="fa-solid fa-receipt"></i> <?= $ct['total_trx']; ?> Transaksi</span>
                            </td>
                            <td data-label="Total Nominal">
                                <strong class="text-danger amount-cell fs-6"><?= formatRupiah($ct['total_nominal']); ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">
                            <div class="no-data">
                                <i class="fa-solid fa-chart-pie"></i>
                                <h5>Belum Ada Data Pengeluaran</h5>
                                <p class="text-muted">Rincian statistik akan muncul setelah ada data pengeluaran kas yang diinput.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {

    // ──────────────────────────────────────────────
    // Chart 1: Bar — Tren Nilai Pengadaan (Tumbuh 1 per 1 dari 0)
    // ──────────────────────────────────────────────
    const trendData   = <?= json_encode($t_values); ?>;
    const trendLabels = <?= json_encode($t_labels); ?>;
    let trendCurrent  = trendData.map(() => 0);

    const barChart = new Chart(document.getElementById('trendValueChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Nilai Pengadaan (Rp)',
                data: [...trendCurrent],
                backgroundColor: '#7A1E33',
                borderColor: '#C9973E',
                borderWidth: 1.5,
                borderRadius: { topLeft: 7, topRight: 7 },
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 550, easing: 'easeOutQuart' },
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, border: { display: false }, ticks: { callback: v => 'Rp ' + (v/1000000).toFixed(0) + ' Jt' } },
                x: { grid: { display: false }, border: { display: false } }
            }
        }
    });

    trendData.forEach((val, i) => {
        setTimeout(() => {
            trendCurrent[i] = val;
            barChart.data.datasets[0].data = [...trendCurrent];
            barChart.update();
        }, 300 + i * 200);
    });

    // ──────────────────────────────────────────────
    // Chart 2: Pie — Rasio Pembayaran (Muncul 1 per 1)
    // ──────────────────────────────────────────────
    const payData   = <?= json_encode($pay_values); ?>;
    const payLabels = <?= json_encode($pay_labels); ?>;
    let payCurrent  = payData.map(() => 0);

    const pieChart = new Chart(document.getElementById('payRatioChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: payLabels,
            datasets: [{
                data: [...payCurrent],
                backgroundColor: ['#1E8A5B', '#B23A3A'],
                borderWidth: 3,
                borderColor: '#ffffff',
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 600, easing: 'easeOutQuart' }
        }
    });

    payData.forEach((val, i) => {
        setTimeout(() => {
            payCurrent[i] = val;
            pieChart.data.datasets[0].data = [...payCurrent];
            pieChart.update();
        }, 400 + i * 350);
    });

    // ──────────────────────────────────────────────
    // Chart 3: Doughnut — Kategori Pengeluaran (Muncul 1 per 1)
    // ──────────────────────────────────────────────
    const catData   = <?= json_encode($cat_values); ?>;
    const catLabels = <?= json_encode($cat_labels); ?>;
    let catCurrent  = catData.map(() => 0);

    const donutChart = new Chart(document.getElementById('statCatChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: catLabels,
            datasets: [{
                data: [...catCurrent],
                backgroundColor: ['#7A1E33','#C9973E','#1E8A5B','#1A2A4A','#58101F','#A5334C'],
                borderWidth: 3,
                borderColor: '#ffffff',
                hoverOffset: 12,
                spacing: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            animation: { duration: 600, easing: 'easeOutQuart' }
        }
    });

    catData.forEach((val, i) => {
        setTimeout(() => {
            catCurrent[i] = val;
            donutChart.data.datasets[0].data = [...catCurrent];
            donutChart.update();
        }, 400 + i * 350);
    });

    // ──────────────────────────────────────────────
    // Chart 4: Line — Pengeluaran Bulanan (Muncul 1 per 1)
    // ──────────────────────────────────────────────
    const monthData   = <?= json_encode($m_values); ?>;
    const monthLabels = <?= json_encode($m_labels); ?>;
    let monthCurrent  = monthData.map(() => null);

    const lineChart = new Chart(document.getElementById('statMonthChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Total Pengeluaran (Rp)',
                data: [...monthCurrent],
                borderColor: '#C9973E',
                backgroundColor: 'rgba(201,151,62,0.12)',
                pointBackgroundColor: '#C9973E',
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true,
                tension: 0.4,
                borderWidth: 2.5,
                spanGaps: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 500, easing: 'easeOutCubic' },
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, border: { display: false }, ticks: { callback: v => 'Rp ' + (v/1000000).toFixed(0) + ' Jt' } },
                x: { grid: { display: false }, border: { display: false } }
            }
        }
    });

    monthData.forEach((val, i) => {
        setTimeout(() => {
            monthCurrent[i] = val;
            lineChart.data.datasets[0].data = [...monthCurrent];
            lineChart.update();
        }, 300 + i * 200);
    });

    // ──────────────────────────────────────────────
    // Chart 5: Bar — Top 5 Pembeli Terbanyak (Tumbuh 1 per 1 dari 0)
    // ──────────────────────────────────────────────
    const buyerData   = <?= json_encode($buyer_values); ?>;
    const buyerLabels = <?= json_encode($buyer_labels); ?>;
    let buyerCurrent  = buyerData.map(() => 0);

    const buyerChart = new Chart(document.getElementById('buyerChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: buyerLabels,
            datasets: [{
                label: 'Total Belanja (Rp)',
                data: [...buyerCurrent],
                backgroundColor: '#1E8A5B',
                borderColor: 'rgba(30,138,91,0.8)',
                borderWidth: 1.5,
                borderRadius: { topLeft: 7, topRight: 7 },
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 550, easing: 'easeOutQuart' },
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, border: { display: false }, ticks: { callback: v => 'Rp ' + (v/1000000).toFixed(0) + ' Jt' } },
                x: { grid: { display: false }, border: { display: false } }
            }
        }
    });

    buyerData.forEach((val, i) => {
        setTimeout(() => {
            buyerCurrent[i] = val;
            buyerChart.data.datasets[0].data = [...buyerCurrent];
            buyerChart.update();
        }, 300 + i * 200);
    });

    // ──────────────────────────────────────────────
    // Chart 6: Bar — Top 5 Stok Paling Banyak Dibeli (Tumbuh 1 per 1 dari 0)
    // ──────────────────────────────────────────────
    const stockData   = <?= json_encode($stock_values); ?>;
    const stockLabels = <?= json_encode($stock_labels); ?>;
    let stockCurrent  = stockData.map(() => 0);

    const stockChart = new Chart(document.getElementById('topStockChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: stockLabels,
            datasets: [{
                label: 'Total Kuantitas',
                data: [...stockCurrent],
                backgroundColor: '#C9973E',
                borderColor: 'rgba(201,151,62,0.8)',
                borderWidth: 1.5,
                borderRadius: { topLeft: 7, topRight: 7 },
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 550, easing: 'easeOutQuart' },
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, border: { display: false } },
                x: { grid: { display: false }, border: { display: false } }
            }
        }
    });

    stockData.forEach((val, i) => {
        setTimeout(() => {
            stockCurrent[i] = val;
            stockChart.data.datasets[0].data = [...stockCurrent];
            stockChart.update();
        }, 300 + i * 200);
    });

});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
