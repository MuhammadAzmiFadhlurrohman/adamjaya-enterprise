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
    <!-- Favicon / Logo Sidebar Tab Icon -->
    <link rel="icon" type="image/png" href="assets/adamjaya.png">
    <link rel="shortcut icon" type="image/png" href="assets/adamjaya.png">
    <link rel="apple-touch-icon" href="assets/adamjaya.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Custom CSS dengan Cache Buster -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css'); ?>">
</head>
<body class="login-body">

    <!-- Fullscreen Login Loading Overlay with Spinning Sidebar Logo -->
    <div id="loginLoadingOverlay" class="login-loading-overlay" style="display: none;">
        <div class="login-loading-content text-center">
            <div class="login-spin-logo-wrapper">
                <img src="assets/adamjaya.png" alt="Adam Jaya Logo" class="login-spin-logo-img">
                <div class="login-spin-outer-ring"></div>
            </div>
            <h5 class="fw-bold text-white mb-1" style="font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: 0.04em;">ADAM JAYA ENTERPRISE</h5>
            <p class="text-gold small mb-0 fw-semibold" style="letter-spacing: 0.02em;">
                <i class="fa-solid fa-circle-notch fa-spin me-1"></i> Memproses Login & Memverifikasi Akun...
            </p>
        </div>
    </div>

    <div class="login-card">
        <div class="text-center mb-4">
            <img src="assets/adamjaya.png" alt="Adam Jaya Enterprise Logo" class="login-brand-logo mb-3" style="width: 82px !important; max-width: 82px !important; height: 82px !important; object-fit: contain !important; background: #ffffff !important; padding: 6px !important; border-radius: 50% !important; border: 2.5px solid var(--gold) !important; box-shadow: 0 8px 24px rgba(201, 151, 62, 0.35) !important;">
            <h3 class="fw-extrabold text-wine mb-1" style="font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: 0.02em; font-size: 1.45rem;">ADAM JAYA</h3>
            <div class="login-subtitle">ENTERPRISE SYSTEM</div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 rounded-3 shadow-sm border-0" role="alert" style="background:#fee2e2; color:#991b1b;">
                <i class="fa-solid fa-circle-exclamation fs-5 flex-shrink-0"></i>
                <div class="small fw-semibold"><?= e($error); ?></div>
            </div>
        <?php endif; ?>

        <form id="loginForm" action="login.php" method="POST">
            <?= csrf_field(); ?>
            <div class="mb-3">
                <label for="username" class="form-label text-muted small fw-bold" style="font-size:0.72rem; letter-spacing:0.05em;">USERNAME</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-user text-wine"></i></span>
                    <input type="text" name="username" id="username" class="form-control border-start-0 ps-1" placeholder="Masukkan username" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label text-muted small fw-bold" style="font-size:0.72rem; letter-spacing:0.05em;">PASSWORD</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock text-wine"></i></span>
                    <input type="password" name="password" id="password" class="form-control border-start-0 border-end-0 ps-1" placeholder="Masukkan password" required>
                    <button class="btn btn-light border border-start-0 text-muted" type="button" id="togglePasswordBtn" onclick="togglePasswordVisibility()">
                        <i class="fa-regular fa-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" id="btnLoginSubmit" class="btn btn-wine-login w-100 py-3 rounded-3 fs-6">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk ke Sistem
            </button>
        </form>
    </div>

    <!-- Footer Copyright -->
    <div class="text-center mt-3 small text-white-50" style="font-size: 0.72rem; z-index: 2;">
        &copy; <?= date('Y'); ?> Adam Jaya Enterprise &middot; Procurement & Operational Portal
    </div>

    <script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const icon = document.getElementById('togglePasswordIcon');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById("loginForm");
        const overlay = document.getElementById("loginLoadingOverlay");
        const btnSubmit = document.getElementById("btnLoginSubmit");

        if (form) {
            form.addEventListener("submit", function() {
                if (overlay) overlay.style.display = "flex";
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = `<img src="assets/adamjaya.png" class="btn-spin-logo-sm me-2" alt="Logo"> Memproses Login...`;
                }
            });
        }
    });
    </script>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
