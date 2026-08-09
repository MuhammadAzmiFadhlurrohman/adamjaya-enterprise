<?php
require_once __DIR__ . '/includes/header.php';
require_login();

$is_admin = is_admin();
$barang_id = (int)($_GET['barang_id'] ?? 0);

// Ambil daftar barang induk untuk dropdown filter / context
$query_induk = "SELECT * FROM stok_barang ORDER BY nama_barang ASC";
$res_induk = mysqli_query($conn, $query_induk);

// Query Varian
if ($barang_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT j.*, b.nama_barang FROM jenis_barang j JOIN stok_barang b ON j.barang_id = b.id WHERE j.barang_id = ? ORDER BY j.id DESC");
    mysqli_stmt_bind_param($stmt, "i", $barang_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $query = "SELECT j.*, b.nama_barang FROM jenis_barang j JOIN stok_barang b ON j.barang_id = b.id ORDER BY b.nama_barang ASC, j.nama_jenis ASC";
    $result = mysqli_query($conn, $query);
}
?>

<!-- Datalist pilihan preset satuan -->
<datalist id="preset_satuan_list">
    <option value="unit">
    <option value="kg">
    <option value="meter">
    <option value="mm">
    <option value="pcs">
</datalist>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1 text-dark">Varian & Stok Detail</h3>
        <p class="text-muted mb-0">Manajemen varian spesifikasi, harga standar, stok persediaan, dan satuan</p>
    </div>
    <?php if ($is_admin): ?>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addJenisModal">
            <i class="fa-solid fa-plus me-1"></i> Tambah Varian Baru
        </button>
    <?php endif; ?>
</div>

<div class="glass-card p-4 mb-4">
    <form method="GET" action="jenis_barang.php" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label text-muted small fw-semibold">FILTER BARANG INDUK</label>
            <select name="barang_id" class="form-select" onchange="this.form.submit()">
                <option value="0">-- Semuanya (Semua Barang Induk) --</option>
                <?php 
                mysqli_data_seek($res_induk, 0);
                while ($b = mysqli_fetch_assoc($res_induk)): 
                ?>
                    <option value="<?= $b['id']; ?>" <?= ($barang_id == $b['id']) ? 'selected' : ''; ?>>
                        <?= e($b['nama_barang']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label text-muted small fw-semibold">CARI VARIAN / SPESIFIKASI</label>
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="searchVarianInput" class="form-control ps-5" placeholder="Ketik varian untuk mencari..." onkeyup="filterVarianLive(this.value)" autocomplete="off">
            </div>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <?php if ($barang_id > 0): ?>
                <a href="jenis_barang.php" class="btn btn-secondary-custom w-100 text-nowrap">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="glass-card p-4">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th width="60">ID</th>
                    <th>Barang Induk</th>
                    <th>Nama Varian / Spesifikasi</th>
                    <th>Stok Tersedia</th>
                    <th>Satuan</th>
                    <th>Harga Satuan</th>
                    <th width="150" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($j = mysqli_fetch_assoc($result)): ?>
                        <tr id="row-jenis-<?= $j['id']; ?>">
                            <td><strong>#<?= $j['id']; ?></strong></td>
                            <td>
                                <span class="badge rounded-2 px-2.5 py-1.5" style="background: #FDF5F6; color: #7A1E33; border: 1px solid #F5D5DA; font-weight: 700; font-size: 0.8rem;">
                                    <i class="fa-solid fa-box-archive me-1 text-gold"></i> <?= e($j['nama_barang']); ?>
                                </span>
                            </td>
                            <td><strong class="text-dark"><?= e($j['nama_jenis']); ?></strong></td>
                            <td>
                                <?php if ($j['stok'] <= 10): ?>
                                    <span class="badge-status danger"><i class="fa-solid fa-triangle-exclamation"></i> <?= format_stok($j['stok']); ?></span>
                                <?php else: ?>
                                    <span class="badge-status success"><i class="fa-solid fa-check"></i> <?= format_stok($j['stok']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="text-muted fw-semibold"><?= e($j['satuan']); ?></span></td>
                            <td><span class="fw-bold text-success"><?= formatRupiah($j['harga']); ?></span></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <?php if ($is_admin): ?>
                                        <button class="btn btn-sm btn-outline-info" onclick="editJenis(<?= htmlspecialchars(json_encode($j)); ?>)">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <a href="#" class="btn btn-sm btn-outline-danger" 
                                           onclick="confirmDelete(event, 'proses_jenis_barang.php?action=delete&id=<?= $j['id']; ?>&barang_id=<?= $barang_id; ?>&csrf_token=<?= generate_csrf_token(); ?>')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Read-Only</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada varian barang terdaftar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($is_admin): ?>
<!-- Modal Tambah Varian -->
<div class="modal fade" id="addJenisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-list-check me-2 text-indigo"></i> Tambah Varian Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_jenis_barang.php" method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="redirect_barang_id" value="<?= $barang_id; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">PILIH BARANG INDUK</label>
                        <select name="barang_id" class="form-select" required>
                            <option value="">-- Pilih Barang Induk --</option>
                            <?php 
                            mysqli_data_seek($res_induk, 0);
                            while ($b = mysqli_fetch_assoc($res_induk)): 
                            ?>
                                <option value="<?= $b['id']; ?>" <?= ($barang_id == $b['id']) ? 'selected' : ''; ?>>
                                    <?= e($b['nama_barang']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">NAMA VARIAN / SPESIFIKASI</label>
                        <input type="text" name="nama_jenis" class="form-control" placeholder="Contoh: Besi Polos 10mm (12m)" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">STOK AWAL</label>
                            <input type="number" step="0.01" name="stok" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">SATUAN</label>
                            <input type="text" name="satuan" class="form-control" list="preset_satuan_list" placeholder="Pilih atau ketik (unit / kg / meter / mm)" value="unit" required>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label text-muted small fw-semibold">HARGA STANDAR SATUAN (RP)</label>
                        <input type="text" name="harga" class="form-control rupiah-input" placeholder="Rp 0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Varian</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Varian -->
<div class="modal fade" id="editJenisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-pen-to-square me-2 text-info"></i> Edit Varian Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_jenis_barang.php" method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_j_id">
                <input type="hidden" name="redirect_barang_id" value="<?= $barang_id; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">BARANG INDUK</label>
                        <select name="barang_id" id="edit_j_barang_id" class="form-select" required>
                            <?php 
                            mysqli_data_seek($res_induk, 0);
                            while ($b = mysqli_fetch_assoc($res_induk)): 
                            ?>
                                <option value="<?= $b['id']; ?>"><?= e($b['nama_barang']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">NAMA VARIAN / SPESIFIKASI</label>
                        <input type="text" name="nama_jenis" id="edit_j_nama" class="form-control" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">STOK TERSEDIA</label>
                            <input type="number" step="0.01" name="stok" id="edit_j_stok" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">SATUAN</label>
                            <input type="text" name="satuan" id="edit_j_satuan" class="form-control" list="preset_satuan_list" required>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label text-muted small fw-semibold">HARGA STANDAR SATUAN (RP)</label>
                        <input type="text" name="harga" id="edit_j_harga" class="form-control rupiah-input" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Update Varian</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filterVarianLive(query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('tbody tr[id^="row-jenis-"]');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(q)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function editJenis(j) {
    document.getElementById('edit_j_id').value = j.id;
    document.getElementById('edit_j_barang_id').value = j.barang_id;
    document.getElementById('edit_j_nama').value = j.nama_jenis;
    
    // Format stok tanpa desimal .00 jika angka bulat saat modal edit dibuka
    const stokNum = parseFloat(j.stok) || 0;
    document.getElementById('edit_j_stok').value = (stokNum % 1 === 0) ? stokNum.toFixed(0) : stokNum;
    
    document.getElementById('edit_j_satuan').value = j.satuan;
    document.getElementById('edit_j_harga').value = formatRupiahJS(j.harga, 'Rp ');
    new bootstrap.Modal(document.getElementById('editJenisModal')).show();
}

document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const scrollTo = urlParams.get('scroll_to') || window.location.hash.replace('#', '');
    if (scrollTo) {
        setTimeout(() => {
            const target = document.getElementById(scrollTo);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                target.classList.add('row-highlight-pulse');
                setTimeout(() => target.classList.remove('row-highlight-pulse'), 2500);
            }
        }, 250);
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
