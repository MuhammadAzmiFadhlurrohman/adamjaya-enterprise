<?php
require_once __DIR__ . '/includes/header.php';
require_login(); // Admin and CEO can view

// Query Executive Stats
$res_pengajuan = mysqli_query($conn, "SELECT COUNT(id) as total_trx, IFNULL(SUM(CAST(estimasi_dana AS DECIMAL(15,2))), 0) as total_val FROM pengajuan");
$row_pengajuan = mysqli_fetch_assoc($res_pengajuan);

$res_pengeluaran = mysqli_query($conn, "SELECT IFNULL(SUM(total_pengeluaran), 0) as total_exp FROM pengeluaran_header");
$row_pengeluaran = mysqli_fetch_assoc($res_pengeluaran);

$res_stok = mysqli_query($conn, "SELECT COUNT(id) as total_varian, IFNULL(SUM(stok), 0) as total_unit FROM jenis_barang");
$row_stok = mysqli_fetch_assoc($res_stok);

$res_pembeli = mysqli_query($conn, "SELECT COUNT(DISTINCT nama_pembeli) as total_pembeli FROM pengajuan");
$row_pembeli = mysqli_fetch_assoc($res_pembeli);

// Data Grafik Barang Paling Banyak Terjual / Dikeluarkan
$query_top_sold = "SELECT 
    CASE 
        WHEN nama_barang REGEXP '^(Item|item)\\s*:' THEN TRIM(REGEXP_REPLACE(nama_barang, '^(Item|item)\\s*:\\s*', ''))
        ELSE nama_barang 
    END as raw_name,
    nama_jenis,
    SUM(jumlah) as total_qty 
FROM pengajuan_detail 
GROUP BY raw_name, nama_jenis 
ORDER BY total_qty DESC LIMIT 6";

$res_top_sold = mysqli_query($conn, $query_top_sold);
$sold_labels = [];
$sold_values = [];
if ($res_top_sold && mysqli_num_rows($res_top_sold) > 0) {
    while ($s = mysqli_fetch_assoc($res_top_sold)) {
        $name = trim($s['raw_name']);
        if (!empty($s['nama_jenis']) && $s['nama_jenis'] !== '-') {
            $name .= ' (' . trim($s['nama_jenis']) . ')';
        }
        // Jika nama hanya berupa ukuran/berat (contoh: "6 kg", "8kg", "1kg", "2kg"), tambahkan nama yang deskriptif "Plastik"
        if (preg_match('/^\d+\s*(kg|g|mm|meter|pack|unit)\)?$/i', $name)) {
            $name = 'Plastik ' . rtrim($name, ')');
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
$query_monthly = "SELECT DATE_FORMAT(created_at, '%b %Y') as m_label, SUM(CAST(estimasi_dana AS DECIMAL(15,2))) as total 
                  FROM pengajuan 
                  GROUP BY YEAR(created_at), MONTH(created_at) 
                  ORDER BY created_at ASC LIMIT 12";
$res_monthly = mysqli_query($conn, $query_monthly);
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
            <p class="page-subtitle">Ringkasan performa bisnis, audit persediaan, dan analisis tren pengadaan perusahaan.</p>
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

<!-- KPI Overview Cards -->
<div class="row g-3 stats-row mb-4">
    <div class="col-6 col-md-3 dash-reveal" style="--reveal-delay: 0.05s">
        <div class="stats-card">
            <div class="card-body">
                <div class="stat-icon icon-wine"><i class="fa-solid fa-chart-line"></i></div>
                <div>
                    <div class="stat-value"><?= formatRupiah($row_pengajuan['total_val']); ?></div>
                    <div class="stat-label">Total Pengadaan (<?= $row_pengajuan['total_trx']; ?> Trx)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 dash-reveal" style="--reveal-delay: 0.15s">
        <div class="stats-card">
            <div class="card-body">
                <div class="stat-icon icon-gold"><i class="fa-solid fa-wallet"></i></div>
                <div>
                    <div class="stat-value"><?= formatRupiah($row_pengeluaran['total_exp']); ?></div>
                    <div class="stat-label">Kas Pengeluaran</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 dash-reveal" style="--reveal-delay: 0.25s">
        <div class="stats-card">
            <div class="card-body">
                <div class="stat-icon icon-success"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div>
                    <div class="stat-value"><?= format_stok($row_stok['total_unit']); ?> Total Stok</div>
                    <div class="stat-label">Stok (<?= $row_stok['total_varian']; ?> Varian)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 dash-reveal" style="--reveal-delay: 0.35s">
        <div class="stats-card">
            <div class="card-body">
                <div class="stat-icon icon-danger"><i class="fa-solid fa-users"></i></div>
                <div>
                    <div class="stat-value"><?= $row_pembeli['total_pembeli']; ?> Pembeli</div>
                    <div class="stat-label">Klien Pelanggan Aktif</div>
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
