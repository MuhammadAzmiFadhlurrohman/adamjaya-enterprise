<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
$user = current_user();
$initial_letter = strtoupper(substr($user['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adam Jaya Enterprise - Sistem Manajemen Operasional</title>
    
    <!-- Favicon / Logo Sidebar Tab Icon -->
    <link rel="icon" type="image/png" href="assets/adamjaya.png">
    <link rel="shortcut icon" type="image/png" href="assets/adamjaya.png">
    <link rel="apple-touch-icon" href="assets/adamjaya.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom Design Tokens -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar Navigation -->
        <?php include_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Workspace (Flex Grow 1 Min-Width 0 to fit perfectly alongside fixed Sidebar) -->
        <div id="main-content" class="flex-grow-1 min-w-0">
            <!-- Topbar Header -->
            <div class="top-navbar">
                <!-- Left: Mobile Toggle & Mobile Logo (Mobile Only) & Desktop Portal Tag -->
                <div class="d-flex align-items-center gap-3">
                    <!-- Toggle button ONLY on mobile (< 992px) -->
                    <button id="sidebar-toggle" class="btn-sidebar-toggle d-lg-none" title="Toggle Navigasi Sidebar">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    
                    <!-- Logo & Brand Title di Kanan Toggle (Dengan Spasi Lega Khusus Mobile) -->
                    <div class="d-flex align-items-center gap-2 d-lg-none ms-1 ps-1">
                        <img src="assets/adamjaya.png" alt="Adam Jaya" class="rounded-circle border border-warning shadow-sm" style="width: 32px; height: 32px; object-fit: contain; background: #ffffff;">
                        <span class="fw-bold text-white fs-6" style="letter-spacing: 0.05em; font-family: 'Plus Jakarta Sans', sans-serif;">ADAM JAYA</span>
                    </div>

                    <!-- System Portal Tag on Desktop -->
                    <div class="d-none d-lg-flex align-items-center">
                        <div class="topbar-portal-tag">
                            <i class="fa-solid fa-boxes-stacked me-2 text-gold"></i>
                            <span>PROCUREMENT & OPERATIONAL SYSTEM</span>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Username (di Kiri Inisial), Avatar Initial Badge & Logout Button -->
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <!-- Username & Role (Berada di KIRI Inisial Avatar) -->
                        <div class="text-end">
                            <div class="user-name-text fw-bold text-white" style="font-size: 0.88rem; line-height: 1.1;"><?= e($user['username']); ?></div>
                            <div class="user-role-text text-gold small d-none d-sm-block" style="font-size: 0.7rem; font-weight: 600;"><?= e(strtoupper($user['role'])); ?></div>
                        </div>
                        
                        <!-- Avatar Initial Badge -->
                        <div class="user-initial-badge" title="User: <?= e($user['username']); ?> (<?= e(strtoupper($user['role'])); ?>)">
                            <?= $initial_letter; ?>
                        </div>
                    </div>

                    <!-- Logout Button -->
                    <a href="logout.php" class="btn-header-logout" title="Keluar dari Sistem">
                        <i class="fa-solid fa-right-from-bracket me-1"></i>
                        <span class="d-none d-md-inline">Logout</span>
                    </a>
                </div>
            </div>
