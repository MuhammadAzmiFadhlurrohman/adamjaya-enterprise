<?php
require_once __DIR__ . '/includes/header.php';
require_login();

$query = "SELECT r.*, j.nama_jenis, j.satuan, b.nama_barang, u.username 
          FROM riwayat_stok r 
          JOIN jenis_barang j ON r.jenis_id = j.id 
          JOIN stok_barang b ON j.barang_id = b.id 
          JOIN users u ON r.user_id = u.id 
          ORDER BY r.id DESC LIMIT 100";
$result = mysqli_query($conn, $query);

$total_logs = mysqli_num_rows($result);
?>

<!-- HEADER CURVED EXECUTIVE BANNER -->
<header class="page-header">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <span class="page-eyebrow"><i class="fa-solid fa-clock-rotate-left"></i> Audit Trail &middot; Adam Jaya</span>
            <h1 class="page-title">Audit Trail Riwayat Stok</h1>
            <p class="page-subtitle">Catatan log otomatis setiap perubahan, pengadaan, dan penyesuaian persediaan barang.</p>
        </div>
        <div class="header-action">
            <a href="stok_barang.php" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-semibold">
                <i class="fa-solid fa-boxes-stacked me-1"></i> Ke Stok Induk
            </a>
        </div>
    </div>
</header>

<!-- Table Container -->
<div class="table-container">
    <div class="table-container-header">
        <h2><i class="fa-solid fa-list-check me-2 text-wine"></i> Catatan Aktivitas Perubahan Stok</h2>
        <span class="badge-count"><?= $total_logs; ?> Log Terbaru</span>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th width="70">ID Log</th>
                    <th>User / Aktor</th>
                    <th>Barang & Varian</th>
                    <th>Aksi Log</th>
                    <th>Stok Sebelum</th>
                    <th>Perubahan</th>
                    <th>Stok Sesudah</th>
                    <th>Waktu / Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_logs > 0): ?>
                    <?php while ($r = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td data-label="ID Log">
                                <span class="id-chip">#<?= $r['id']; ?></span>
                            </td>
                            <td data-label="User / Aktor">
                                <span class="badge-status info"><i class="fa-solid fa-user me-1"></i> <?= e($r['username']); ?></span>
                            </td>
                            <td data-label="Barang & Varian">
                                <strong class="text-dark d-block"><?= e($r['nama_barang']); ?> &mdash; <?= e($r['nama_jenis']); ?></strong>
                            </td>
                            <td data-label="Aksi Log">
                                <?php if ($r['aksi'] === 'tambah'): ?>
                                    <span class="badge-status success"><i class="fa-solid fa-plus"></i> TAMBAH</span>
                                <?php elseif ($r['aksi'] === 'edit'): ?>
                                    <span class="badge-status info"><i class="fa-solid fa-pen"></i> EDIT</span>
                                <?php elseif ($r['aksi'] === 'hapus'): ?>
                                    <span class="badge-status danger"><i class="fa-solid fa-trash"></i> HAPUS</span>
                                <?php else: ?>
                                    <span class="badge-status warning"><i class="fa-solid fa-cart-shopping"></i> TRANSAKSI</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Stok Sebelum"><?= format_stok($r['stok_sebelum']); ?> <?= e($r['satuan']); ?></td>
                            <td data-label="Perubahan">
                                <?php if ($r['perubahan'] > 0): ?>
                                    <span class="fw-bold text-success">+<?= format_stok($r['perubahan']); ?></span>
                                <?php elseif ($r['perubahan'] < 0): ?>
                                    <span class="fw-bold text-danger"><?= format_stok($r['perubahan']); ?></span>
                                <?php else: ?>
                                    <span class="fw-bold text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Stok Sesudah">
                                <strong class="text-dark"><?= format_stok($r['stok_sesudah']); ?> <?= e($r['satuan']); ?></strong>
                            </td>
                            <td data-label="Waktu / Keterangan">
                                <small class="text-muted d-block"><i class="fa-regular fa-clock me-1 text-gold"></i> <?= date('d M Y, H:i', strtotime($r['tanggal'])); ?></small>
                                <small class="text-muted d-block"><?= e($r['keterangan'] ?: '-'); ?></small>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">
                            <div class="no-data">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <h5>Belum Ada Log Riwayat Stok</h5>
                                <p class="text-muted">Aktivitas penambahan, transaksi, dan pengubahan stok akan tercatat otomatis di sini.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
