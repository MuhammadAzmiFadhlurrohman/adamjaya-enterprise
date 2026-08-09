<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

// Jika sudah login, redirect langsung ke Executive Dashboard (ceo.php)
if (isset($_SESSION['user_id'])) {
    header("Location: ceo.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Token CSRF tidak valid. Silakan coba lagi.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username dan Password wajib diisi!';
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id, username, password, role, no_telepon, email, lokasi FROM users WHERE username = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($res)) {
                if (password_verify($password, $row['password'])) {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = $row['role'];
                    $_SESSION['no_telepon'] = $row['no_telepon'];
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['lokasi'] = $row['lokasi'];

                    // Redirect ke Executive Dashboard (ceo.php) untuk semua role
                    header("Location: ceo.php");
                    exit;
                } else {
                    $error = 'Password yang Anda masukkan salah!';
                }
            } else {
                $error = 'Username tidak ditemukan!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Adam Jaya Enterprise</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">

    <div class="login-card">
        <div class="text-center mb-4">
            <div class="brand-icon mx-auto mb-3" style="width: 54px; height: 54px; font-size: 1.5rem;">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1">Adam Jaya Enterprise</h4>
            <p class="text-muted small">Sistem Operasional Procurement & Inventaris</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 rounded-3" role="alert">
                <i class="fa-solid fa-circle-exclamation fs-5"></i>
                <div class="small"><?= e($error); ?></div>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <?= csrf_field(); ?>
            <div class="mb-3">
                <label for="username" class="form-label text-muted small fw-semibold">USERNAME</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" id="username" class="form-control border-start-0 ps-0" placeholder="Masukkan username" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label text-muted small fw-semibold">PASSWORD</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control border-start-0 ps-0" placeholder="Masukkan password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary-custom w-100 py-2.5">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk ke Sistem
            </button>
        </form>

        <div class="mt-4 pt-3 text-center border-top">
            <small class="text-muted">Akses Default Demo:</small>
            <div class="d-flex justify-content-center gap-3 mt-2 text-muted small">
                <span><strong>Admin:</strong> admin / admin123</span>
                <span>|</span>
                <span><strong>CEO:</strong> ceo / ceo123</span>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
