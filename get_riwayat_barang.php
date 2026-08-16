<?php
require_once __DIR__ . '/config/auth.php';
require_login();

$nama_barang = trim($_GET['nama_barang'] ?? '');
$nama_jenis = trim($_GET['nama_jenis'] ?? '');
$satuan = trim($_GET['satuan'] ?? '');

if (empty($nama_barang)) {
    echo '<div class="alert alert-danger p-3"><i class="fa-solid fa-triangle-exclamation me-2"></i> Parameter nama barang tidak valid.</div>';
    exit;
}

// Optional date range filters if passed from parent
$start_date = trim($_GET['start_date'] ?? '');
$end_date = trim($_GET['end_date'] ?? '');

$sql = "
    SELECT p.id as pengajuan_id, p.custom_id, p.created_at, p.nama_pembeli, p.telepon_pembeli, 
           p.status_pembayaran, p.status_pengiriman, p.jumlah_dibayar, p.sisa_pembayaran,
           u.username as creator_name,
           pd.id as detail_id, pd.nama_barang, pd.nama_jenis, pd.jumlah, pd.satuan, pd.harga_satuan,
           (pd.jumlah * pd.harga_satuan) as subtotal
    FROM pengajuan_detail pd
    JOIN pengajuan p ON pd.pengajuan_id = p.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE pd.nama_barang = ?
";

$params = [$nama_barang];
$types = "s";

if (!empty($nama_jenis) && $nama_jenis !== '-') {
    $sql .= " AND pd.nama_jenis = ?";
    $params[] = $nama_jenis;
    $types .= "s";
} else {
    $sql .= " AND (pd.nama_jenis IS NULL OR pd.nama_jenis = '' OR pd.nama_jenis = '-')";
}

if (!empty($satuan)) {
    $sql .= " AND pd.satuan = ?";
    $params[] = $satuan;
    $types .= "s";
}

if (!empty($start_date)) {
    $sql .= " AND DATE(p.created_at) >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if (!empty($end_date)) {
    $sql .= " AND DATE(p.created_at) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

$sql .= " ORDER BY p.created_at DESC, p.id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$history = [];
$total_qty = 0;
$total_nominal = 0;
while ($row = mysqli_fetch_assoc($res)) {
    $history[] = $row;
    $total_qty += (float)$row['jumlah'];
    $total_nominal += (float)$row['subtotal'];
}
?>

<div class="product-history-wrapper">
    <!-- Header Summary Card -->
    <div class="card border-0 rounded-4 shadow-sm mb-3" style="background: linear-gradient(135deg, #7A1E33 0%, #58101F 100%); color: white;">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <span class="badge bg-warning text-dark px-2.5 py-1 fw-bold rounded-pill mb-1">
                        <i class="fa-solid fa-box me-1"></i> Rincian Barang
                    </span>
                    <h5 class="fw-bold text-white mb-0"><?= e($nama_barang); ?></h5>
                    <?php if (!empty($nama_jenis) && $nama_jenis !== '-'): ?>
                        <div class="text-white-50 small mt-0.5"><i class="fa-solid fa-tag me-1"></i> Varian: <span class="text-white fw-semibold"><?= e($nama_jenis); ?></span></div>
                    <?php endif; ?>
                </div>
                <div class="text-end">
                    <span class="badge bg-white-subtle text-white border border-white-50 px-3 py-1.5 rounded-pill fw-bold">
                        <i class="fa-solid fa-receipt me-1"></i> <?= count($history); ?> Kali Transaksi
                    </span>
                </div>
            </div>
            
            <div class="row g-2 mt-2 pt-2 border-top border-white-50">
                <div class="col-6 col-md-4">
                    <small class="text-white-50 d-block" style="font-size:0.72rem;">TOTAL KUANTITAS TERJUAL:</small>
                    <span class="fs-6 fw-bold text-warning"><?= format_stok($total_qty); ?> <?= e($satuan ?: 'unit'); ?></span>
                </div>
                <div class="col-6 col-md-4">
                    <small class="text-white-50 d-block" style="font-size:0.72rem;">TOTAL OMZET/HARGA (RP):</small>
                    <span class="fs-6 fw-bold text-white"><?= formatRupiah($total_nominal); ?></span>
                </div>
                <div class="col-12 col-md-4 text-md-end">
                    <small class="text-white-50 d-block" style="font-size:0.72rem;">RATA-RATA HARGA JUAL:</small>
                    <span class="fs-6 fw-bold text-white-50"><?= formatRupiah($total_qty > 0 ? ($total_nominal / $total_qty) : 0); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="table-responsive rounded-3 border" style="max-height: 400px; overflow-y: auto;">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="table-light sticky-top text-muted" style="z-index: 1;">
                <tr>
                    <th width="35" class="text-center">#</th>
                    <th>No. Faktur & Tanggal</th>
                    <th>Nama Pembeli</th>
                    <th class="text-center">Status Bayar</th>
                    <th class="text-center">Kuantitas</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-end">Subtotal</th>
                    <th width="70" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($history) > 0): ?>
                    <?php 
                    $no = 1;
                    foreach ($history as $h): 
                        $status_bayar_badge = ($h['status_pembayaran'] === 'dibayar') 
                            ? '<span class="badge bg-success-subtle text-success border border-success px-2 py-0.5 rounded-pill"><i class="fa-solid fa-check me-1"></i>Lunas</span>'
                            : (($h['status_pembayaran'] === 'cicilan')
                                ? '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-0.5 rounded-pill"><i class="fa-solid fa-clock me-1"></i>Cicilan</span>'
                                : '<span class="badge bg-danger-subtle text-danger border border-danger px-2 py-0.5 rounded-pill"><i class="fa-solid fa-hourglass-start me-1"></i>Belum Lunas</span>');
                    ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++; ?></td>
                            <td>
                                <strong class="text-wine d-block">#<?= e($h['custom_id']); ?></strong>
                                <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?= date('d M Y H:i', strtotime($h['created_at'])); ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($h['nama_pembeli'] ?: 'Pelanggan Umum'); ?></div>
                                <?php if (!empty($h['telepon_pembeli'])): ?>
                                    <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= e($h['telepon_pembeli']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?= $status_bayar_badge; ?>
                            </td>
                            <td class="text-center fw-bold text-dark">
                                <?= format_stok($h['jumlah']); ?> <?= e($h['satuan']); ?>
                            </td>
                            <td class="text-end text-muted">
                                <?= formatRupiah($h['harga_satuan']); ?>
                            </td>
                            <td class="text-end fw-bold text-success">
                                <?= formatRupiah($h['subtotal']); ?>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2 rounded-2" 
                                        onclick="openDetailFaktur(<?= $h['pengajuan_id']; ?>)" title="Buka Detail Transaksi">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-circle-exclamation fs-3 d-block text-secondary mb-2"></i>
                            Tidak ada riwayat transaksi pada periode yang dipilih.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
