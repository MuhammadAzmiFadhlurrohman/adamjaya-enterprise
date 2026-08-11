<?php
require_once __DIR__ . '/includes/header.php';
require_admin(); // Hanya Admin yang bisa mengelola User

// Ambil daftar user
$query = "SELECT * FROM users ORDER BY id ASC";
$result = mysqli_query($conn, $query);

$total_user = mysqli_num_rows($result);
?>

<!-- HEADER CURVED EXECUTIVE BANNER -->
<header class="page-header">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <span class="page-eyebrow"><i class="fa-solid fa-users-gear"></i> Sistem Pengaturan &middot; Akun Pengguna</span>
            <h1 class="page-title">Manajemen User System</h1>
            <p class="page-subtitle">Kelola akun pengguna dengan hak akses Administrator atau CEO Monitoring.</p>
        </div>
        <div class="header-action">
            <button class="btn-pengajuan-header" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fa-solid fa-user-plus"></i> Tambah User Baru
            </button>
        </div>
    </div>
</header>

<!-- Table Container -->
<div class="table-container">
    <div class="table-container-header">
        <h2><i class="fa-solid fa-user-shield me-2 text-wine"></i> Daftar Pengguna Aplikasi</h2>
        <span class="badge-count"><?= $total_user; ?> Akun Terdaftar</span>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th width="45">ID</th>
                    <th>Username Pengguna</th>
                    <th>Hak Akses / Role</th>
                    <th>No. Telepon</th>
                    <th>Email</th>
                    <th>Lokasi</th>
                    <th width="150" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td data-label="ID">
                            <span class="id-chip">#<?= $u['id']; ?></span>
                        </td>
                        <td data-label="Username">
                            <strong class="text-dark fs-6"><?= e($u['username']); ?></strong>
                        </td>
                        <td data-label="Role">
                            <?php if (strtolower($u['role']) === 'admin'): ?>
                                <span class="badge-status success"><i class="fa-solid fa-user-shield"></i> ADMIN</span>
                            <?php else: ?>
                                <span class="badge-status warning"><i class="fa-solid fa-user-tie"></i> CEO</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Kontak"><?= e($u['no_telepon'] ?: '-'); ?></td>
                        <td data-label="Email"><?= e($u['email'] ?: '-'); ?></td>
                        <td data-label="Lokasi"><?= e($u['lokasi'] ?: '-'); ?></td>
                        <td data-label="Aksi" class="text-center">
                            <div class="action-btns justify-content-end justify-content-md-center">
                                <button class="action-btn btn-detail" 
                                        onclick="editUser(<?= htmlspecialchars(json_encode($u)); ?>)">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>
                                <?php if ($u['id'] != $user['id']): ?>
                                    <a href="#" class="action-btn btn-delete" 
                                       onclick="confirmDelete(event, 'proses_user.php?action=delete&id=<?= $u['id']; ?>&csrf_token=<?= generate_csrf_token(); ?>')">
                                        <i class="fa-solid fa-trash"></i> Hapus
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-user-plus me-2"></i> Tambah User Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_user.php" method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">USERNAME *</label>
                        <input type="text" name="username" class="form-control" placeholder="Nama pengguna" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">PASSWORD *</label>
                        <input type="password" name="password" class="form-control" placeholder="Kata sandi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">ROLE AKSES *</label>
                        <select name="role" class="form-select" required>
                            <option value="admin">Administrator (Akses Penuh)</option>
                            <option value="CEO">CEO (Read-Only Monitoring)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">NOMOR TELEPON</label>
                        <input type="text" name="no_telepon" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">EMAIL</label>
                        <input type="email" name="email" class="form-control" placeholder="email@adamjaya.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">LOKASI</label>
                        <input type="text" name="lokasi" class="form-control" placeholder="Surakarta, Jawa Tengah">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-user-pen me-2"></i> Edit Akun User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_user.php" method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">USERNAME *</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">PASSWORD BARU (OPSIONAL)</label>
                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diubah">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">ROLE AKSES *</label>
                        <select name="role" id="edit_role" class="form-select" required>
                            <option value="admin">Administrator (Akses Penuh)</option>
                            <option value="CEO">CEO (Read-Only Monitoring)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">NOMOR TELEPON</label>
                        <input type="text" name="no_telepon" id="edit_no_telepon" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">EMAIL</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">LOKASI</label>
                        <input type="text" name="lokasi" id="edit_lokasi" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Update Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(u) {
    document.getElementById('edit_id').value = u.id;
    document.getElementById('edit_username').value = u.username;
    document.getElementById('edit_role').value = u.role;
    document.getElementById('edit_no_telepon').value = u.no_telepon || '';
    document.getElementById('edit_email').value = u.email || '';
    document.getElementById('edit_lokasi').value = u.lokasi || '';

    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
