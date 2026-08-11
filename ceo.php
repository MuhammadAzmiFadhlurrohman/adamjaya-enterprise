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

// Data Grafik Pengeluaran Per Kategori
$query_cat = "SELECT d.kategori, SUM(d.total_harga) as total 
              FROM pengeluaran_detail d 
              JOIN pengeluaran_header h ON d.pengeluaran_id = h.id 
              GROUP BY d.kategori ORDER BY total DESC";
$res_cat = mysqli_query($conn, $query_cat);
$cat_labels = [];
$cat_values = [];
while ($c = mysqli_fetch_assoc($res_cat)) {
    $cat_labels[] = $c['kategori'];
    $cat_values[] = (float)$c['total'];
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
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chart-pie text-wine me-2"></i> Pengeluaran Berdasarkan Kategori</h5>
                <span class="badge bg-gold-soft text-wine small fw-bold">Live Breakdown</span>
            </div>
            <div style="position: relative; height: 260px;">
                <canvas id="chartKategori"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- AUDIT STOK DAFTAR PERSEDIAAN BARANG -->
<div class="table-container mb-4">
    <div class="table-container-header">
        <h2><i class="fa-solid fa-shield-halved me-2 text-wine"></i> Audit Persediaan Stok Varian Terendah</h2>
        <a href="stok_barang.php" class="btn btn-sm btn-outline-indigo">Lihat Seluruh Stok &rarr;</a>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
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
                <?php while ($audit = mysqli_fetch_assoc($res_audit)): 
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
                <?php endwhile; ?>
            </tbody>
        </table>
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
    // Chart 1: Doughnut — Kategori Pengeluaran (Segmen Muncul Satu per Satu)
    // ────────────────────────────────────────────────
    const catData   = <?= json_encode($cat_values); ?>;
    const catLabels = <?= json_encode($cat_labels); ?>;
    const catColors = ['#7A1E33','#C9973E','#1E8A5B','#1A2A4A','#58101F','#A5334C'];
    let catCurrent  = catData.map(() => 0);

    const ctxCat = document.getElementById('chartKategori').getContext('2d');
    const donutChart = new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: catLabels,
            datasets: [{
                data: [...catCurrent],
                backgroundColor: catColors,
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
                        label: (ctx) => '  ' + ctx.label + ': Rp ' + ctx.parsed.toLocaleString('id-ID')
                    }
                }
            }
        }
    });

    catData.forEach((val, i) => {
        setTimeout(() => {
            catCurrent[i] = val;
            donutChart.data.datasets[0].data = [...catCurrent];
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
