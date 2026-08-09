<?php
require_once __DIR__ . '/config/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT h.*, u.username FROM pengeluaran_header h JOIN users u ON h.user_id = u.id WHERE h.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$h = mysqli_fetch_assoc($res);

if (!$h) {
    echo '<div class="alert alert-danger">Data pengeluaran tidak ditemukan.</div>';
    exit;
}

$stmt_d = mysqli_prepare($conn, "SELECT * FROM pengeluaran_detail WHERE pengeluaran_id = ? ORDER BY id ASC");
mysqli_stmt_bind_param($stmt_d, "i", $id);
mysqli_stmt_execute($stmt_d);
$res_d = mysqli_stmt_get_result($stmt_d);
?>

<div class="row mb-3 pb-3 border-bottom">
    <div class="col-md-6">
        <small class="text-muted d-block fw-semibold">REF ID PENGELUARAN</small>
        <h5 class="fw-bold text-warning mb-1"><?= e($h['custom_id']); ?></h5>
        <small class="text-muted"><i class="fa-regular fa-calendar me-1"></i> Tanggal: <?= date('d M Y', strtotime($h['tanggal'])); ?></small>
    </div>
    <div class="col-md-6 text-md-end">
        <small class="text-muted d-block fw-semibold">DICATAT OLEH</small>
        <strong class="text-dark"><?= e($h['username']); ?></strong>
        <div class="text-muted small mt-1"><?= e($h['keterangan'] ?: '-'); ?></div>
    </div>
</div>

<h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-list me-2 text-warning"></i> Item Rincian Pengeluaran Kas</h6>
<div class="table-responsive">
    <table class="table table-custom align-middle">
        <thead>
            <tr>
                <th width="40">#</th>
                <th>Nama Item</th>
                <th>Kategori</th>
                <th class="text-end">Jumlah</th>
                <th class="text-end">Harga Satuan</th>
                <th class="text-end">Total Biaya</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while ($d = mysqli_fetch_assoc($res_d)):
            ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><strong class="text-dark"><?= e($d['nama_item']); ?></strong></td>
                    <td><span class="badge bg-light text-dark border"><?= e($d['kategori']); ?></span></td>
                    <td class="text-end"><?= number_format($d['jumlah'], 2, ',', '.'); ?> <?= e($d['satuan']); ?></td>
                    <td class="text-end"><?= formatRupiah($d['harga_satuan']); ?></td>
                    <td class="text-end fw-bold text-danger"><?= formatRupiah($d['total_harga']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-end text-dark fs-6">TOTAL HARGA OUT:</th>
                <th class="text-end fw-bold text-danger fs-5"><?= formatRupiah($h['total_pengeluaran']); ?></th>
            </tr>
        </tfoot>
    </table>
</div>
