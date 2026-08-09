<?php
require_once __DIR__ . '/includes/header.php';
require_login();

// Auto-create tabel riwayat_stok jika belum ada di database live
ensure_riwayat_stok_table_exists($conn);

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
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <span class="page-eyebrow"><i class="fa-solid fa-clock-rotate-left"></i> Audit Trail &middot; Adam Jaya</span>
            <h1 class="page-title">Audit Trail Riwayat Stok</h1>
            <p class="page-subtitle mb-0">Catatan log otomatis setiap perubahan, pengadaan, dan penyesuaian persediaan barang.</p>
        </div>
        <div class="header-action">
            <a href="stok_barang.php" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-semibold">
                <i class="fa-solid fa-boxes-stacked me-1"></i> Ke Stok Induk
            </a>
        </div>
    </div>
</header>

<!-- Desktop Table Container (>= 768px) -->
<div class="table-container d-none d-md-block">
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
                <?php if ($total_logs > 0): 
                    mysqli_data_seek($result, 0);
                ?>
                    <?php while ($r = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><span class="id-chip">#<?= $r['id']; ?></span></td>
                            <td><span class="badge-status info"><i class="fa-solid fa-user me-1"></i> <?= e($r['username']); ?></span></td>
                            <td><strong class="text-dark d-block"><?= e($r['nama_barang']); ?> &mdash; <?= e($r['nama_jenis']); ?></strong></td>
                            <td>
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
                            <td><?= format_stok($r['stok_sebelum']); ?> <?= e($r['satuan']); ?></td>
                            <td>
                                <?php if ($r['perubahan'] > 0): ?>
                                    <span class="fw-bold text-success">+<?= format_stok($r['perubahan']); ?></span>
                                <?php elseif ($r['perubahan'] < 0): ?>
                                    <span class="fw-bold text-danger"><?= format_stok($r['perubahan']); ?></span>
                                <?php else: ?>
                                    <span class="fw-bold text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td><strong class="text-dark"><?= format_stok($r['stok_sesudah']); ?> <?= e($r['satuan']); ?></strong></td>
                            <td>
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

<!-- Mobile Executive Card View (< 768px) -->
<div class="d-md-none">
    <div class="d-flex justify-content-between align-items-center mb-3 px-1">
        <h6 class="fw-bold text-wine mb-0" style="font-size:0.95rem;"><i class="fa-solid fa-list-check me-1.5"></i> Catatan Log Stok</h6>
        <span class="badge bg-wine text-white rounded-pill px-2.5 py-1" style="font-size:0.7rem;"><?= $total_logs; ?> Log Terbaru</span>
    </div>

    <?php if ($total_logs > 0): 
        mysqli_data_seek($result, 0);
    ?>
        <?php while ($r = mysqli_fetch_assoc($result)): ?>
            <div class="audit-card-mobile mb-3">
                <!-- Top Header Row -->
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-1.5">
                        <span class="id-chip" style="font-size:0.68rem; padding:2px 7px;">#<?= $r['id']; ?></span>
                        <span class="badge bg-light text-dark border fw-bold" style="font-size:0.68rem;">
                            <i class="fa-solid fa-user me-1 text-wine"></i> <?= e($r['username']); ?>
                        </span>
                    </div>
                    <div>
                        <?php if ($r['aksi'] === 'tambah'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5 fw-bold" style="font-size:0.66rem;"><i class="fa-solid fa-plus me-1"></i> TAMBAH</span>
                        <?php elseif ($r['aksi'] === 'edit'): ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5 fw-bold" style="font-size:0.66rem;"><i class="fa-solid fa-pen me-1"></i> EDIT</span>
                        <?php elseif ($r['aksi'] === 'hapus'): ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0.5 fw-bold" style="font-size:0.66rem;"><i class="fa-solid fa-trash me-1"></i> HAPUS</span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5 fw-bold" style="font-size:0.66rem;"><i class="fa-solid fa-cart-shopping me-1"></i> TRANSAKSI</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Item & Varian Title -->
                <div class="mb-2">
                    <h6 class="fw-bold text-dark mb-0" style="font-size:0.92rem; line-height:1.25;"><?= e($r['nama_barang']); ?></h6>
                    <small class="text-wine fw-bold" style="font-size:0.78rem;"><?= e($r['nama_jenis']); ?></small>
                </div>

                <!-- Stock Flow Transition Box -->
                <div class="p-2 rounded-3 bg-light border mb-2 d-flex justify-content-between align-items-center text-center">
                    <div>
                        <div class="text-muted" style="font-size:0.65rem; font-weight:700;">SEBELUM</div>
                        <div class="fw-bold text-secondary" style="font-size:0.82rem;"><?= format_stok($r['stok_sebelum']); ?> <?= e($r['satuan']); ?></div>
                    </div>
                    <div class="px-1">
                        <?php if ($r['perubahan'] > 0): ?>
                            <span class="badge bg-success px-2 py-1" style="font-size:0.75rem;"><i class="fa-solid fa-arrow-right me-1"></i>+<?= format_stok($r['perubahan']); ?></span>
                        <?php elseif ($r['perubahan'] < 0): ?>
                            <span class="badge bg-danger px-2 py-1" style="font-size:0.75rem;"><i class="fa-solid fa-arrow-right me-1"></i><?= format_stok($r['perubahan']); ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary px-2 py-1" style="font-size:0.75rem;">0</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.65rem; font-weight:700;">SESUDAH</div>
                        <div class="fw-bold text-wine" style="font-size:0.82rem;"><?= format_stok($r['stok_sesudah']); ?> <?= e($r['satuan']); ?></div>
                    </div>
                </div>

                <!-- Footer Timestamp & Notes -->
                <div class="d-flex justify-content-between align-items-center text-muted" style="font-size:0.72rem;">
                    <span><i class="fa-regular fa-clock me-1 text-gold"></i> <?= date('d M Y, H:i', strtotime($r['tanggal'])); ?></span>
                    <?php if (!empty($r['keterangan'])): ?>
                        <span class="text-truncate ms-2" style="max-width:50%;" title="<?= e($r['keterangan']); ?>"><i class="fa-solid fa-info-circle me-1"></i> <?= e($r['keterangan']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="glass-card text-center py-4">
            <i class="fa-solid fa-clock-rotate-left fs-1 text-muted mb-2"></i>
            <h6 class="fw-bold text-dark">Belum Ada Log Riwayat Stok</h6>
            <p class="text-muted small mb-0">Aktivitas stok akan tercatat otomatis di sini.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
