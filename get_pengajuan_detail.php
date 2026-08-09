<?php
require_once __DIR__ . '/config/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT p.*, u.username FROM pengajuan p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$p = mysqli_fetch_assoc($res);

if (!$p) {
    echo '<div class="alert alert-danger">Data pengajuan tidak ditemukan.</div>';
    exit;
}

// Ambil Items Detail
$stmt_detail = mysqli_prepare($conn, "SELECT * FROM pengajuan_detail WHERE pengajuan_id = ? ORDER BY id ASC");
mysqli_stmt_bind_param($stmt_detail, "i", $id);
mysqli_stmt_execute($stmt_detail);
$res_detail = mysqli_stmt_get_result($stmt_detail);

$items_data = [];
$grand_total = 0;
while ($row = mysqli_fetch_assoc($res_detail)) {
    $items_data[] = $row;
    $grand_total += ($row['jumlah'] * $row['harga_satuan']);
}

$is_admin_user = is_admin();
$csrf_token_val = generate_csrf_token();
?>

<!-- 1. ROW TOP CARDS (Informasi Pengajuan & Informasi Pembeli/Keuangan) -->
<div class="row g-3 mb-3">
    <!-- Card 1: Informasi Pengajuan -->
    <div class="col-md-6">
        <div class="modal-info-card">
            <div class="card-title-header">
                <div class="icon-circle wine"><i class="fa-solid fa-mobile-screen-button"></i></div>
                <h5>Informasi Pengajuan</h5>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-6">
                    <div class="info-block d-flex align-items-start gap-2">
                        <div class="block-icon"><i class="fa-solid fa-hashtag"></i></div>
                        <div>
                            <span class="label">ID PENGAJUAN</span>
                            <span class="val">#<?= e($p['custom_id']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="info-block d-flex align-items-start gap-2">
                        <div class="block-icon"><i class="fa-regular fa-calendar-days"></i></div>
                        <div>
                            <span class="label">TANGGAL</span>
                            <span class="val"><?= date('d M Y H:i', strtotime($p['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-6 mt-3">
                    <div class="info-block d-flex align-items-start gap-2">
                        <div class="block-icon"><i class="fa-regular fa-user"></i></div>
                        <div>
                            <span class="label">DIAJUKAN</span>
                            <span class="val"><?= e($p['username']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-6 mt-3">
                    <div class="info-block d-flex align-items-start gap-2">
                        <div class="block-icon"><i class="fa-solid fa-truck"></i></div>
                        <div>
                            <span class="label">STATUS KIRIM</span>
                            <span class="val mt-1">
                                <?php if ($p['status_pengiriman'] === 'sudah_dikirim'): ?>
                                    <span class="badge-status success"><i class="fa-solid fa-circle-check"></i> Sudah Dikirim</span>
                                <?php else: ?>
                                    <span class="badge-status warning"><i class="fa-solid fa-box"></i> Pending Kirim</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Informasi Pembeli & Keuangan -->
    <div class="col-md-6">
        <div class="modal-info-card">
            <div class="card-title-header">
                <div class="icon-circle wine"><i class="fa-solid fa-dollar-sign"></i></div>
                <h5>Informasi Pembeli & Keuangan</h5>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-6">
                    <div class="info-block d-flex align-items-start gap-2">
                        <div class="block-icon"><i class="fa-regular fa-user"></i></div>
                        <div>
                            <span class="label">NAMA PEMBELI</span>
                            <span class="val"><?= e($p['nama_pembeli'] ?: '-'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="info-block d-flex align-items-start gap-2">
                        <div class="block-icon"><i class="fa-solid fa-money-bill-1"></i></div>
                        <div>
                            <span class="label">TOTAL ESTIMASI</span>
                            <span class="val text-wine fw-bold"><?= formatRupiah($p['estimasi_dana']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 mt-2">
                    <div class="info-block d-flex align-items-start gap-2">
                        <div class="block-icon"><i class="fa-solid fa-phone"></i></div>
                        <div>
                            <span class="label">TELEPON</span>
                            <span class="val"><?= e($p['telepon_pembeli'] ?: '-'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-top border-dashed my-2 pt-2">
                <div class="info-block d-flex align-items-start gap-2">
                    <div class="block-icon"><i class="fa-regular fa-comment-dots"></i></div>
                    <div>
                        <span class="label">KETERANGAN</span>
                        <span class="val text-muted fst-italic" style="font-size:0.82rem;"><?= e($p['catatan'] ?? $p['keterangan'] ?? 'Tidak ada keterangan'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 2. UPDATE STATUS KIRIM ACTION CARD -->
<?php if ($is_admin_user): ?>
<div class="modal-subcard mb-3 p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-truck text-muted"></i>
        <span class="fw-semibold text-dark small">Update Status Kirim</span>
    </div>
    <div>
        <?php if ($p['status_pengiriman'] === 'sudah_dikirim'): ?>
            <button type="button" class="btn btn-outline-warning btn-sm fw-bold px-3 py-1" onclick="processDirectAction('batal_kirim', <?= $p['id']; ?>, '<?= $csrf_token_val; ?>')">
                <i class="fa-solid fa-rotate-left me-1"></i> Batalkan Kirim
            </button>
        <?php else: ?>
            <button type="button" class="btn btn-outline-success btn-sm fw-bold px-3 py-1" onclick="processDirectAction('kirim', <?= $p['id']; ?>, '<?= $csrf_token_val; ?>')">
                <i class="fa-solid fa-truck-fast me-1"></i> Tandai Sudah Dikirim
            </button>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- 3. DETAIL BARANG TABLE CARD -->
<div class="modal-table-section mb-3">
    <div class="section-header-bar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-box-archive text-white"></i>
            <h6 class="mb-0 text-white fw-bold">Detail Barang</h6>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-white text-dark rounded-pill px-2.5 py-1" style="font-size:0.7rem; font-weight:600;"><i class="fa-solid fa-box me-1"></i> <?= count($items_data); ?> Item</span>
            <a href="cetak_struk.php?id=<?= $p['id']; ?>" target="_blank" class="btn btn-light btn-sm fw-bold rounded-2 px-3 py-1" style="font-size:0.75rem;">
                <i class="fa-solid fa-print me-1"></i> Cetak Struk
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table modal-detail-table align-middle mb-0">
            <thead>
                <tr>
                    <th width="40" class="text-center">#</th>
                    <th>NAMA BARANG</th>
                    <th>JENIS</th>
                    <th class="text-center">JUMLAH</th>
                    <th class="text-end">HARGA</th>
                    <th class="text-end">SUBTOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items_data) > 0): ?>
                    <?php 
                    $no = 1;
                    foreach ($items_data as $d):
                        $sub = $d['jumlah'] * $d['harga_satuan'];
                    ?>
                        <tr>
                            <td class="text-center text-muted small"><?= $no++; ?></td>
                            <td>
                                <strong class="text-dark d-block"><?= e($d['nama_barang']); ?></strong>
                            </td>
                            <td>
                                <span class="text-muted d-block small"><?= e($d['nama_jenis'] ?: '-'); ?></span>
                                <?php if ($d['is_custom']): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-0.5 mt-1" style="font-size:0.65rem; font-weight:600;">
                                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Custom Item
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-semibold text-dark"><?= format_stok($d['jumlah']); ?> <?= e($d['satuan']); ?></td>
                            <td class="text-end text-muted"><?= formatRupiah($d['harga_satuan']); ?></td>
                            <td class="text-end fw-bold text-dark"><?= formatRupiah($sub); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-3 text-muted">Belum ada rincian barang.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="footer-row-pink">
                    <td colspan="5" class="text-end fw-bold text-wine fs-6">Total</td>
                    <td class="text-end fw-bold text-wine fs-5"><?= formatRupiah($grand_total); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- 4. METODE PEMBAYARAN CARD -->
<div class="modal-table-section mb-3">
    <div class="section-header-bar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-receipt text-white"></i>
            <h6 class="mb-0 text-white fw-bold">Metode Pembayaran</h6>
        </div>
        <div>
            <?php if ($p['status_pembayaran'] === 'dibayar'): ?>
                <span class="badge bg-success rounded-pill px-2.5 py-1" style="font-size:0.7rem;"><i class="fa-solid fa-check-circle me-1"></i> Lunas</span>
            <?php else: ?>
                <span class="badge bg-danger rounded-pill px-2.5 py-1" style="font-size:0.7rem;"><i class="fa-solid fa-clock me-1"></i> Belum lunas</span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="p-3 bg-white">
        <?php if ($p['status_pembayaran'] === 'belum_dibayar'): ?>
            <!-- Warning Box -->
            <div class="alert alert-warning border-warning-subtle text-dark-emphasis mb-3 d-flex align-items-center justify-content-center gap-2 py-2.5" style="background:#FFF9E6; border-radius:10px;">
                <i class="fa-solid fa-triangle-exclamation text-warning fs-5"></i>
                <span class="fw-semibold small">Pembayaran belum lunas. Pilih metode pembayaran di bawah ini.</span>
            </div>

            <?php if ($is_admin_user): ?>
                <div id="payment_main_buttons">
                    <small class="text-muted d-block mb-2 fw-semibold" style="font-size:0.75rem;"><i class="fa-solid fa-credit-card me-1"></i> Pilih metode pembayaran</small>
                    <div class="row g-3">
                        <div class="col-6">
                            <button type="button" class="payment-card-btn" onclick="selectPaymentMethod('transfer')">
                                <i class="fa-solid fa-building-columns text-primary fs-3 mb-2"></i>
                                <span class="fw-bold text-dark d-block">Transfer</span>
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="payment-card-btn" onclick="selectPaymentMethod('tunai')">
                                <i class="fa-solid fa-money-bill-wave text-success fs-3 mb-2"></i>
                                <span class="fw-bold text-dark d-block">Tunai</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sub Opsi setelah memilih -->
                <div id="payment_sub_box" class="mt-3 p-3 bg-light rounded border d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="text-dark small" id="selected_method_title">Metode Dipilih: -</strong>
                        <button type="button" class="btn-close btn-sm" onclick="resetPaymentSelection()"></button>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-2" id="sub_choice_buttons">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="showProofUploadForm()">
                            <i class="fa-solid fa-file-arrow-up me-1"></i> Unggah Bukti
                        </button>
                        <button type="button" class="btn btn-success btn-sm" onclick="processPaymentWithoutProof(<?= $p['id']; ?>, '<?= $csrf_token_val; ?>')">
                            <i class="fa-solid fa-circle-check me-1"></i> Proses Langsung Lunas
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="resetPaymentSelection()">
                            <i class="fa-solid fa-xmark me-1"></i> Batal
                        </button>
                    </div>

                    <!-- Inline Form Upload Bukti -->
                    <form id="formModalUploadProof" enctype="multipart/form-data" class="mt-3 d-none" onsubmit="submitPaymentWithProof(event, <?= $p['id']; ?>)">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token_val; ?>">
                        <input type="hidden" name="action" value="bayar_dengan_bukti">
                        <input type="hidden" name="id" value="<?= $p['id']; ?>">
                        <input type="hidden" name="metode" id="modal_payment_metode" value="transfer">

                        <label class="form-label small text-muted fw-semibold mb-1">PILIH FILE BUKTI (IMAGE / PDF)</label>
                        <div class="input-group">
                            <input type="file" name="bukti_file" id="input_bukti_file" class="form-control form-control-sm" accept="image/*,.pdf" required>
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Unggah & Bayar
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-2">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 mb-3 d-inline-block">
                    <i class="fa-solid fa-circle-check me-1"></i> Pembayaran Sudah Lunas
                </span>
                <?php if ($is_admin_user): ?>
                    <div>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-4 fw-semibold" onclick="processDirectAction('batal_bayar', <?= $p['id']; ?>, '<?= $csrf_token_val; ?>')">
                            <i class="fa-solid fa-rotate-left me-1"></i> Batalkan Pembayaran
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 5. LAMPIRAN BUKTI UPLOAD CARD -->
<?php 
$bukti_tf_path = resolve_bukti_path($p['bukti_transfer'] ?? '');
$bukti_tn_path = resolve_bukti_path($p['bukti_tunai'] ?? '');
$bukti_pb_path = resolve_bukti_path($p['bukti_pembelian'] ?? '');
$has_any_bukti = $bukti_tf_path || $bukti_tn_path || $bukti_pb_path;
?>

<?php if ($has_any_bukti): ?>
<div class="modal-table-section mb-3">
    <div class="section-header-bar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-paperclip text-white"></i>
            <h6 class="mb-0 text-white fw-bold">Bukti Upload & Lampiran Pembayaran</h6>
        </div>
        <span class="badge bg-white text-dark rounded-pill px-2.5 py-1" style="font-size:0.7rem; font-weight:600;">
            <i class="fa-solid fa-file-image text-gold me-1"></i> Terlampir
        </span>
    </div>
    
    <div class="p-3 bg-white">
        <div class="row g-3 justify-content-center">
            <?php if ($bukti_tf_path): ?>
                <div class="col-md-4 text-center">
                    <div class="p-2 border rounded bg-light">
                        <small class="fw-bold d-block text-dark mb-2"><i class="fa-solid fa-building-columns text-primary me-1"></i> Bukti Transfer Bank</small>
                        <?php if (pathinfo($bukti_tf_path, PATHINFO_EXTENSION) === 'pdf'): ?>
                            <a href="<?= e($bukti_tf_path); ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100 py-3">
                                <i class="fa-solid fa-file-pdf fs-2 text-danger d-block mb-1"></i> Lihat Berkas PDF
                            </a>
                        <?php else: ?>
                            <a href="<?= e($bukti_tf_path); ?>" target="_blank">
                                <img src="<?= e($bukti_tf_path); ?>" class="img-fluid rounded border shadow-sm" style="max-height: 180px; object-fit: contain;" title="Klik untuk membuka gambar">
                            </a>
                            <a href="<?= e($bukti_tf_path); ?>" target="_blank" class="btn btn-sm btn-link text-decoration-none mt-1 d-block text-truncate" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Buka Ukuran Penuh
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($bukti_tn_path): ?>
                <div class="col-md-4 text-center">
                    <div class="p-2 border rounded bg-light">
                        <small class="fw-bold d-block text-dark mb-2"><i class="fa-solid fa-money-bill-wave text-success me-1"></i> Bukti Tunai / Cash</small>
                        <?php if (pathinfo($bukti_tn_path, PATHINFO_EXTENSION) === 'pdf'): ?>
                            <a href="<?= e($bukti_tn_path); ?>" target="_blank" class="btn btn-sm btn-outline-success w-100 py-3">
                                <i class="fa-solid fa-file-pdf fs-2 text-danger d-block mb-1"></i> Lihat Berkas PDF
                            </a>
                        <?php else: ?>
                            <a href="<?= e($bukti_tn_path); ?>" target="_blank">
                                <img src="<?= e($bukti_tn_path); ?>" class="img-fluid rounded border shadow-sm" style="max-height: 180px; object-fit: contain;" title="Klik untuk membuka gambar">
                            </a>
                            <a href="<?= e($bukti_tn_path); ?>" target="_blank" class="btn btn-sm btn-link text-decoration-none mt-1 d-block text-truncate" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Buka Ukuran Penuh
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($bukti_pb_path): ?>
                <div class="col-md-4 text-center">
                    <div class="p-2 border rounded bg-light">
                        <small class="fw-bold d-block text-dark mb-2"><i class="fa-solid fa-receipt text-warning me-1"></i> Bukti Pembelian / Struk</small>
                        <?php if (pathinfo($bukti_pb_path, PATHINFO_EXTENSION) === 'pdf'): ?>
                            <a href="<?= e($bukti_pb_path); ?>" target="_blank" class="btn btn-sm btn-outline-warning w-100 py-3">
                                <i class="fa-solid fa-file-pdf fs-2 text-danger d-block mb-1"></i> Lihat Berkas PDF
                            </a>
                        <?php else: ?>
                            <a href="<?= e($bukti_pb_path); ?>" target="_blank">
                                <img src="<?= e($bukti_pb_path); ?>" class="img-fluid rounded border shadow-sm" style="max-height: 180px; object-fit: contain;" title="Klik untuk membuka gambar">
                            </a>
                            <a href="<?= e($bukti_pb_path); ?>" target="_blank" class="btn btn-sm btn-link text-decoration-none mt-1 d-block text-truncate" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Buka Ukuran Penuh
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
