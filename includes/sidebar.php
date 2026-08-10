<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_admin_user = is_admin();
$active_user = current_user();
?>
<div id="sidebar-wrapper" class="<?= $is_admin_user ? 'sidebar-admin-wine' : 'sidebar-ceo-navy'; ?>">
    <div class="sidebar-brand">
        <div class="d-flex align-items-center gap-3">
            <img src="assets/adamjaya.png" alt="Adam Jaya Logo" class="sidebar-brand-logo">
            <div class="brand-text-group">
                <div class="brand-title">ADAM JAYA</div>
                <div class="brand-subtitle">ENTERPRISE APP</div>
            </div>
        </div>
        <!-- Close button for mobile -->
        <button type="button" class="btn btn-sm text-white-50 d-lg-none p-0 ms-auto" onclick="document.getElementById('sidebar-wrapper').classList.remove('show'); document.getElementById('sidebar-overlay').classList.remove('show');">
            <i class="fa-solid fa-xmark fs-5"></i>
        </button>
    </div>

    <ul class="sidebar-nav">
        <!-- EXECUTIVE DASHBOARD (SELALU PALING ATAS & LANDING PAGE UTAMA) -->
        <div class="sidebar-section-title">Dashboard Utama</div>
        <li class="nav-item">
            <a href="ceo.php" class="nav-link <?= ($current_page == 'ceo.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-line"></i>
                <span>Executive Dashboard</span>
            </a>
        </li>

        <?php if ($is_admin_user): ?>
            <!-- ADMIN MENU -->
            <div class="sidebar-section-title">Pengadaan & Transaksi</div>
            <li class="nav-item">
                <a href="insert_admin.php" class="nav-link <?= in_array($current_page, ['insert_admin.php', 'tambah_pengajuan.php', 'edit_pengajuan.php']) ? 'active' : ''; ?>">
                    <i class="fa-solid fa-cart-flatbed"></i>
                    <span>Daftar Pembelian</span>
                </a>
            </li>

            <div class="sidebar-section-title">Manajemen Stok</div>
            <li class="nav-item">
                <a href="stok_barang.php" class="nav-link <?= in_array($current_page, ['stok_barang.php', 'jenis_barang.php']) ? 'active' : ''; ?>">
                    <i class="fa-solid fa-box-archive"></i>
                    <span>Master Barang</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="riwayat_stok.php" class="nav-link <?= ($current_page == 'riwayat_stok.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Riwayat Stok</span>
                </a>
            </li>

            <div class="sidebar-section-title">Operasional</div>
            <li class="nav-item">
                <a href="pengeluaran.php" class="nav-link <?= in_array($current_page, ['pengeluaran.php', 'tambah_pengeluaran.php', 'edit_pengeluaran.php', 'upload_bukti_pembelian.php', 'upload_bukti_transfer.php', 'upload_bukti_tunai.php']) ? 'active' : ''; ?>">
                    <i class="fa-solid fa-wallet"></i>
                    <span>Pengeluaran Kas</span>
                </a>
            </li>

            <div class="sidebar-section-title">Monitoring & Analisis</div>
            <li class="nav-item">
                <a href="statistik.php" class="nav-link <?= ($current_page == 'statistik.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Grafik & Analisis Business</span>
                </a>
            </li>

            <div class="sidebar-section-title">Pengaturan System</div>
            <li class="nav-item">
                <a href="daftar_user.php" class="nav-link <?= ($current_page == 'daftar_user.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>Manajemen User</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="favorit_pembeli.php" class="nav-link <?= ($current_page == 'favorit_pembeli.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-star"></i>
                    <span>Pembeli Favorit</span>
                </a>
            </li>

        <?php else: ?>
            <!-- CEO MENU (READ-ONLY) -->
            <div class="sidebar-section-title">Monitoring Laporan</div>
            <li class="nav-item">
                <a href="insert_admin.php" class="nav-link <?= in_array($current_page, ['insert_admin.php', 'tambah_pengajuan.php', 'edit_pengajuan.php']) ? 'active' : ''; ?>">
                    <i class="fa-solid fa-cart-flatbed"></i>
                    <span>Laporan Pengajuan</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="pengeluaran.php" class="nav-link <?= in_array($current_page, ['pengeluaran.php', 'tambah_pengeluaran.php', 'edit_pengeluaran.php']) ? 'active' : ''; ?>">
                    <i class="fa-solid fa-wallet"></i>
                    <span>Laporan Pengeluaran</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="riwayat_stok.php" class="nav-link <?= ($current_page == 'riwayat_stok.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Audit Riwayat Stok</span>
                </a>
            </li>

            <div class="sidebar-section-title">Visualisasi & Laporan</div>
            <li class="nav-item">
                <a href="statistik.php" class="nav-link <?= ($current_page == 'statistik.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Grafik & Analisis Business</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <!-- SIDEBAR FOOTER -->
    <div class="sidebar-footer">
        <div class="sidebar-copyright">
            <span>&copy; <?= date('Y'); ?> ADAM JAYA ENTERPRISE</span>
            <small class="d-block">v2 &middot; All Rights Reserved</small>
        </div>
    </div>
</div>
