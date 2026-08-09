<?php
require_once __DIR__ . '/includes/header.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT * FROM pengajuan WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$p = mysqli_fetch_assoc($res);

if (!$p) {
    set_flash('error', 'Gagal', 'Pengajuan tidak ditemukan.');
    header('Location: insert_admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('error', 'Gagal', 'Token CSRF tidak valid.');
    } else {
        if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            if (in_array($ext, $allowed)) {
                $new_name = 'tf_' . $p['custom_id'] . '_' . time() . '.' . $ext;
                $upload_dir = __DIR__ . '/uploads/bukti/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                if (move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $upload_dir . $new_name)) {
                    $path = 'uploads/bukti/' . $new_name;
                    $stmt_u = mysqli_prepare($conn, "UPDATE pengajuan SET bukti_transfer = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_u, "si", $path, $id);
                    mysqli_stmt_execute($stmt_u);

                    set_flash('success', 'Berhasil', 'Bukti transfer bank berhasil diunggah.');
                    header('Location: insert_admin.php');
                    exit;
                } else {
                    set_flash('error', 'Gagal', 'Gagal mengunggah berkas ke server.');
                }
            } else {
                set_flash('error', 'Gagal', 'Format file harus berupa JPG, PNG, WEBP, atau PDF.');
            }
        } else {
            set_flash('error', 'Gagal', 'Silakan pilih berkas bukti transfer terlebih dahulu.');
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1 text-dark">Upload Bukti Transfer Bank</h3>
        <p class="text-muted mb-0">Lampirkan foto/dokumen transfer untuk nota <strong>#<?= e($p['custom_id']); ?></strong></p>
    </div>
    <a href="insert_admin.php" class="btn btn-secondary-custom">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="glass-card p-4 mx-auto" style="max-width: 600px;">
    <div class="p-3 rounded bg-light border mb-4">
        <div class="d-flex justify-content-between">
            <span class="text-muted">Nama Pembeli:</span>
            <strong class="text-dark"><?= e($p['nama_pembeli']); ?></strong>
        </div>
        <div class="d-flex justify-content-between mt-2">
            <span class="text-muted">Estimasi Tagihan:</span>
            <strong class="text-success"><?= formatRupiah($p['estimasi_dana']); ?></strong>
        </div>
    </div>

    <?php if (!empty($p['bukti_transfer']) && file_exists(__DIR__ . '/' . $p['bukti_transfer'])): ?>
        <div class="mb-4 text-center">
            <p class="text-muted small mb-2">Bukti Transfer Saat Ini:</p>
            <a href="<?= e($p['bukti_transfer']); ?>" target="_blank" class="btn btn-sm btn-outline-info">
                <i class="fa-solid fa-file-image me-1"></i> Lihat File Terlampir
            </a>
        </div>
    <?php endif; ?>

    <form action="upload_bukti_transfer.php?id=<?= $id; ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field(); ?>
        <div class="mb-4">
            <label class="form-label text-muted small fw-semibold">PILIH BERKAS BUKTI TRANSFER (MAX 2MB)</label>
            <input type="file" name="bukti_transfer" class="form-control" accept="image/*,.pdf" required>
        </div>

        <button type="submit" class="btn btn-primary-custom w-100 py-2">
            <i class="fa-solid fa-cloud-arrow-up me-2"></i> Unggah Bukti Transfer
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
