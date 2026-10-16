<?php
require_once __DIR__ . '/includes/header.php';
require_login();

$is_admin = is_admin();

// Filter
$bulan = sanitize($_GET['bulan'] ?? '');
$tahun = sanitize($_GET['tahun'] ?? date('Y'));
$current_filter_url = 'pengeluaran.php?' . http_build_query(['bulan' => $bulan, 'tahun' => $tahun]);

$where_clauses = ["1=1"];
$params = [];
$types = "";

if (!empty($bulan)) {
    $where_clauses[] = "MONTH(h.tanggal) = ?";
    $params[] = (int)$bulan;
    $types .= "i";
}

if (!empty($tahun)) {
    $where_clauses[] = "YEAR(h.tanggal) = ?";
    $params[] = (int)$tahun;
    $types .= "i";
}

$where_sql = implode(" AND ", $where_clauses);

$query = "SELECT h.*, u.username, COUNT(d.id) as total_items 
          FROM pengeluaran_header h 
          JOIN users u ON h.user_id = u.id 
          LEFT JOIN pengeluaran_detail d ON h.id = d.pengeluaran_id 
          WHERE $where_sql 
          GROUP BY h.id 
          ORDER BY h.tanggal DESC, h.created_at DESC, h.id DESC";

$stmt = mysqli_prepare($conn, $query);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$total_pengeluaran_sum = 0.0;
$headers_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $headers_data[] = $row;
    $total_pengeluaran_sum += (float)$row['total_pengeluaran'];
}

// Query Pemasukan Kas Transaksi & Piutang Sesuai Periode Filter
$in_where = ["1=1"];
$in_params = [];
$in_types = "";
if (!empty($bulan)) {
    $in_where[] = "MONTH(created_at) = ?";
    $in_params[] = (int)$bulan;
    $in_types .= "i";
}
if (!empty($tahun)) {
    $in_where[] = "YEAR(created_at) = ?";
    $in_params[] = (int)$tahun;
    $in_types .= "i";
}
$in_where_sql = implode(" AND ", $in_where);
$stmt_in = mysqli_prepare($conn, "
    SELECT 
        IFNULL(SUM(CASE 
            WHEN status_pembayaran = 'dibayar' THEN CAST(estimasi_dana AS DECIMAL(15,2))
            WHEN status_pembayaran = 'cicilan' THEN CAST(jumlah_dibayar AS DECIMAL(15,2))
            ELSE 0 
        END), 0) as total_in,
        IFNULL(SUM(CASE 
            WHEN status_pembayaran = 'dibayar' THEN 0
            WHEN status_pembayaran = 'cicilan' THEN CAST(sisa_pembayaran AS DECIMAL(15,2))
            ELSE CAST(estimasi_dana AS DECIMAL(15,2))
        END), 0) as total_piutang
    FROM pengajuan 
    WHERE $in_where_sql
");
if (!empty($in_types)) {
    mysqli_stmt_bind_param($stmt_in, $in_types, ...$in_params);
}
mysqli_stmt_execute($stmt_in);
$res_in = mysqli_stmt_get_result($stmt_in);
$row_in = mysqli_fetch_assoc($res_in);
$total_in_periode = (float)($row_in['total_in'] ?? 0);
$total_piutang_periode = (float)($row_in['total_piutang'] ?? 0);
$saldo_bersih_periode = $total_in_periode - $total_pengeluaran_sum;
?>

<!-- HEADER CURVED EXECUTIVE BANNER -->
<header class="page-header">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <span class="page-eyebrow"><i class="fa-solid fa-wallet"></i> Operasional &middot; Kas Beban Pengeluaran</span>
            <h1 class="page-title">Pengeluaran Operasional Perusahaan</h1>
            <p class="page-subtitle">Pencatatan kas keluar, perbandingan arus kas pemasukan, dan saldo laba bersih.</p>
        </div>
        <div class="header-action">
            <?php if ($is_admin): ?>
                <a href="tambah_pengeluaran.php" class="btn-pengajuan-header">
                    <i class="fa-solid fa-plus-circle"></i> Catat Pengeluaran Baru
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Header Summary KPI Cards -->
<div class="row g-3 stats-row mb-4">
    <div class="col-6 col-lg-3">
        <div class="stats-card h-100 border-start border-3 border-danger">
            <div class="card-body">
                <div class="stat-icon icon-wine"><i class="fa-solid fa-wallet"></i></div>
                <div>
                    <div class="stat-value text-danger"><?= formatRupiah($total_pengeluaran_sum); ?></div>
                    <div class="stat-label">Kas Pengeluaran</div>
                    <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;"><?= count($headers_data); ?> Catatan Beban Kas</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stats-card h-100 border-start border-3 border-success">
            <div class="card-body">
                <div class="stat-icon icon-success"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div>
                    <div class="stat-value text-success"><?= formatRupiah($total_in_periode); ?></div>
                    <div class="stat-label">Pemasukan Kas Diterima</div>
                    <small class="text-success d-block mt-0.5" style="font-size: 0.72rem;">Uang riil masuk periode ini</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stats-card h-100 border-start border-3 border-warning">
            <div class="card-body">
                <div class="stat-icon icon-gold"><i class="fa-solid fa-hourglass-half"></i></div>
                <div>
                    <div class="stat-value text-dark"><?= formatRupiah($total_piutang_periode); ?></div>
                    <div class="stat-label">Piutang Belum Cair</div>
                    <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;">Sisa tagihan pelanggan</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stats-card h-100 border-start border-3 <?= $saldo_bersih_periode >= 0 ? 'border-primary' : 'border-danger'; ?>">
            <div class="card-body">
                <div class="stat-icon icon-indigo"><i class="fa-solid fa-scale-balanced"></i></div>
                <div>
                    <div class="stat-value <?= $saldo_bersih_periode >= 0 ? 'text-primary' : 'text-danger'; ?>"><?= formatRupiah($saldo_bersih_periode); ?></div>
                    <div class="stat-label">Saldo / Laba Bersih Kas</div>
                    <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;">Kas Masuk − Beban Kas</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card Bar -->
<div class="filter-card">
    <form method="GET" action="pengeluaran.php">
        <div class="filter-row">
            <div class="filter-group">
                <label>FILTER BULAN</label>
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
                        <option value="<?= $m_num; ?>" <?= ($bulan === $m_num) ? 'selected' : ''; ?>><?= $m_name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>FILTER TAHUN</label>
                <select name="tahun" class="form-select">
                    <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                        <option value="<?= $y; ?>" <?= ($tahun == $y) ? 'selected' : ''; ?>><?= $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-filter-primary"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <a href="pengeluaran.php" class="btn btn-filter-reset"><i class="fa-solid fa-rotate-left me-1"></i> Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Table Container -->
<div class="table-container">
    <div class="table-container-header">
        <h2><i class="fa-solid fa-receipt me-2 text-wine"></i> Daftar Transaksi Pengeluaran Kas</h2>
        <span class="badge-count"><?= count($headers_data); ?> Record</span>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th width="85">ID Nota Kas</th>
                    <th>Tanggal & Petugas</th>
                    <th>Keterangan / Beban Kas</th>
                    <th>Total Item</th>
                    <th>Total Biaya Pengeluaran</th>
                    <th class="text-center" width="160">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($headers_data) > 0): ?>
                    <?php foreach ($headers_data as $h): 
                        $ket = $h['keterangan'] ?? $h['judul'] ?? 'Pengeluaran Kas Operasional';
                        if (empty(trim($ket))) $ket = 'Pengeluaran Kas Operasional';
                    ?>
                        <tr>
                            <td data-label="ID Nota Kas">
                                <span class="id-chip">#<?= e($h['custom_id']); ?></span>
                            </td>
                            <td data-label="Tanggal & Petugas">
                                <strong class="text-dark d-block"><?= date('d M Y', strtotime($h['tanggal'])); ?></strong>
                                <small class="text-muted d-block" style="font-size:0.75rem;"><i class="fa-solid fa-user me-1 text-indigo"></i> <?= e($h['username']); ?></small>
                            </td>
                            <td data-label="Keterangan / Beban Kas">
                                <strong class="text-dark d-block fs-6"><?= e($ket); ?></strong>
                                <small class="text-muted d-block"><i class="fa-solid fa-calendar-day me-1 text-gold"></i> Waktu Input: <?= date('d M Y, H:i', strtotime($h['created_at'])); ?></small>
                            </td>
                            <td data-label="Total Item">
                                <span class="badge-status info"><i class="fa-solid fa-layer-group"></i> <?= $h['total_items']; ?> Item</span>
                            </td>
                            <td data-label="Total Biaya">
                                <strong class="text-danger amount-cell fs-6"><?= formatRupiah($h['total_pengeluaran']); ?></strong>
                            </td>
                            <td data-label="Aksi" class="text-center">
                                <div class="action-btns justify-content-end justify-content-md-center">
                                    <button type="button" class="action-btn btn-detail" onclick="openDetailPengeluaranModal(<?= $h['id']; ?>)">
                                        <i class="fa-solid fa-eye"></i> Detail
                                    </button>

                                    <?php if ($is_admin): ?>
                                        <a href="edit_pengeluaran.php?id=<?= $h['id']; ?>&return_url=<?= urlencode($current_filter_url); ?>" class="action-btn btn-edit">
                                            <i class="fa-solid fa-pen"></i> Edit
                                        </a>
                                        <a href="#" class="action-btn btn-delete" 
                                           onclick="confirmDelete(event, 'proses_hapus_pengeluaran.php?id=<?= $h['id']; ?>&csrf_token=<?= generate_csrf_token(); ?>&return_url=<?= urlencode($current_filter_url); ?>')">
                                            <i class="fa-solid fa-trash"></i> Hapus
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="no-data-row">
                        <td colspan="6" class="no-data-td">
                            <div class="no-data">
                                <i class="fa-solid fa-wallet"></i>
                                <h5>Belum Ada Catatan Pengeluaran Kas</h5>
                                <p class="text-muted">Tidak ada pengeluaran kas operasional yang tercatat untuk filter ini.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Detail Pengeluaran -->
<div class="modal fade" id="modalDetailPengeluaran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-receipt me-2"></i> Rincian Pengeluaran Kas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailPengeluaranModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2">Memuat rincian pengeluaran...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openDetailPengeluaranModal(id) {
    const modalBody = document.getElementById('detailPengeluaranModalBody');
    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2">Memuat rincian pengeluaran...</p>
        </div>
    `;
    const myModal = new bootstrap.Modal(document.getElementById('modalDetailPengeluaran'));
    myModal.show();

    fetch(`ajax_pengeluaran_detail.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            modalBody.innerHTML = html;
        })
        .catch(err => {
            modalBody.innerHTML = `<div class="alert alert-danger">Gagal memuat rincian pengeluaran.</div>`;
        });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
