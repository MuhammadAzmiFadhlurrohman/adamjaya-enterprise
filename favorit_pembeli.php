<?php
require_once __DIR__ . '/includes/header.php';
require_admin();

$user_id = current_user()['id'];
$query = "SELECT * FROM favorit_pembeli ORDER BY id DESC";
$result = mysqli_query($conn, $query);

$total_fav = mysqli_num_rows($result);
?>

<!-- HEADER CURVED EXECUTIVE BANNER -->
<header class="page-header">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <span class="page-eyebrow"><i class="fa-solid fa-star"></i> Pengaturan &middot; Pelanggan Favorit</span>
            <h1 class="page-title">Daftar Pembeli Favorit</h1>
            <p class="page-subtitle">Kelola daftar kontak pelanggan favorit untuk mengisi pengajuan secara otomatis.</p>
        </div>
        <div class="header-action">
            <button class="btn-pengajuan-header" data-bs-toggle="modal" data-bs-target="#addFavoritModal">
                <i class="fa-solid fa-plus-circle"></i> Tambah Pembeli Favorit
            </button>
        </div>
    </div>
</header>

<!-- Table Container -->
<div class="table-container">
    <div class="table-container-header">
        <h2><i class="fa-solid fa-users text-wine me-2"></i> Kontak Pelanggan Favorit</h2>
        <span class="badge-count"><?= $total_fav; ?> Kontak</span>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th width="70">ID</th>
                    <th>Nama Pembeli / Perusahaan</th>
                    <th>No. Telepon / WA</th>
                    <th>Alamat / Lokasi</th>
                    <th width="150" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_fav > 0): ?>
                    <?php while ($f = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td data-label="ID">
                                <span class="id-chip">#<?= $f['id']; ?></span>
                            </td>
                            <td data-label="Nama Pembeli">
                                <strong class="text-dark fs-6"><?= e($f['nama_pembeli']); ?></strong>
                            </td>
                            <td data-label="No. Telepon">
                                <?php if (!empty($f['telepon_pembeli'])): ?>
                                    <span class="badge-status info"><i class="fa-solid fa-phone me-1"></i> <?= e($f['telepon_pembeli']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Alamat"><?= e($f['alamat_pembeli'] ?: '-'); ?></td>
                            <td data-label="Aksi" class="text-center">
                                <div class="action-btns justify-content-end justify-content-md-center">
                                    <button class="action-btn btn-detail" onclick="editFavorit(<?= htmlspecialchars(json_encode($f)); ?>)">
                                        <i class="fa-solid fa-pen"></i> Edit
                                    </button>
                                    <a href="#" class="action-btn btn-delete" 
                                       onclick="confirmDelete(event, 'proses_favorit.php?action=delete&id=<?= $f['id']; ?>&csrf_token=<?= generate_csrf_token(); ?>')">
                                        <i class="fa-solid fa-trash"></i> Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">
                            <div class="no-data">
                                <i class="fa-solid fa-star"></i>
                                <h5>Belum Ada Pembeli Favorit</h5>
                                <p class="text-muted">Klik tombol Tambah Pembeli Favorit untuk menyimpan kontak rujukan.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Favorit -->
<div class="modal fade" id="addFavoritModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-star me-2"></i> Tambah Pembeli Favorit</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_favorit.php" method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">NAMA PEMBELI / PT / CV</label>
                        <input type="text" name="nama_pembeli" class="form-control" placeholder="Contoh: PT Mitra Perkasa" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">NOMOR TELEPON / WA</label>
                        <input type="tel" name="telepon_pembeli" class="form-control" inputmode="numeric" pattern="[0-9]*" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">ALAMAT PEMBELI</label>
                        <textarea name="alamat_pembeli" class="form-control" rows="3" placeholder="Alamat lengkap..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Kontak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Favorit -->
<div class="modal fade" id="editFavoritModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-pen-to-square me-2"></i> Edit Pembeli Favorit</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_favorit.php" method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">NAMA PEMBELI / PT / CV</label>
                        <input type="text" name="nama_pembeli" id="edit_nama_pembeli" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">NOMOR TELEPON / WA</label>
                        <input type="tel" name="telepon_pembeli" id="edit_telepon_pembeli" class="form-control" inputmode="numeric" pattern="[0-9]*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">ALAMAT PEMBELI</label>
                        <textarea name="alamat_pembeli" id="edit_alamat_pembeli" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Update Kontak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editFavorit(f) {
    document.getElementById('edit_id').value = f.id;
    document.getElementById('edit_nama_pembeli').value = f.nama_pembeli;
    document.getElementById('edit_telepon_pembeli').value = f.telepon_pembeli || '';
    document.getElementById('edit_alamat_pembeli').value = f.alamat_pembeli || '';

    const modal = new bootstrap.Modal(document.getElementById('editFavoritModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
