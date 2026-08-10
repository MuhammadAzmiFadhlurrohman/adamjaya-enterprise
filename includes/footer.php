            <!-- Modal Dynamic Container for Pengajuan Detail -->
            <div class="modal fade" id="detailPengajuanModal" tabindex="-1" aria-labelledby="detailPengajuanModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content glass-modal">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-white mb-0" id="detailPengajuanModalLabel">
                                <i class="fa-solid fa-file-invoice text-gold me-2"></i> Rincian Pengajuan Pembelian
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4" id="detailModalContent">
                            <!-- Dynamic Content Loaded via AJAX -->
                        </div>
                        <div class="modal-footer bg-light border-top p-3 d-flex justify-content-between align-items-center">
                            <span class="small text-muted"><i class="fa-solid fa-building me-1 text-gold"></i> Adam Jaya Enterprise System</span>
                            <button type="button" class="btn btn-secondary-custom px-4 fw-bold" data-bs-dismiss="modal">
                                <i class="fa-solid fa-xmark me-1"></i> Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAGE CONTENT FOOTER (Paling Bawah Setiap Halaman) -->
            <footer class="page-content-footer mt-5 pt-3 pb-3 border-top">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-2 text-center text-md-start">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-building text-gold"></i>
                        <span class="fw-bold text-dark small">ADAM JAYA ENTERPRISE</span>
                        <span class="text-muted small d-none d-sm-inline">&middot; Operational Management System</span>
                    </div>
                    <div class="text-muted small" style="font-size: 0.75rem;">
                        &copy; <?= date('Y'); ?> ADAM JAYA ENTERPRISE &middot; All Rights Reserved &middot; <span class="badge bg-light text-dark border px-2 py-1 rounded-pill"><i class="fa-solid fa-shield-halved text-success me-1"></i> System Secure</span>
                    </div>
                </div>
            </footer>

        </div><!-- End #main-content -->
    </div><!-- End d-flex -->

    <!-- Core Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/main.js"></script>

    <?php display_flash_msg(); ?>

    <!-- Scroll To Top Button -->
    <button id="scrollToTopBtn" onclick="scrollToTop()" title="Kembali ke atas">
        <i class="fa-solid fa-chevron-up"></i>
    </button>

    <style>
        #scrollToTopBtn {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7a1e33 0%, #4a0b18 100%);
            color: #ffd700;
            border: 1.5px solid rgba(201, 168, 76, 0.5);
            box-shadow: 0 4px 16px rgba(88, 16, 31, 0.45);
            font-size: 1rem;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
        }
        #scrollToTopBtn:hover {
            background: linear-gradient(135deg, #c9973e 0%, #ffd700 100%);
            color: #58101f;
            box-shadow: 0 6px 22px rgba(201, 168, 76, 0.5);
            transform: translateY(-3px) scale(1.08);
        }
        body.modal-open #scrollToTopBtn,
        .modal.show ~ #scrollToTopBtn {
            display: none !important;
        }
    </style>

    <script>
        // Scroll To Top Logic
        const scrollBtn = document.getElementById('scrollToTopBtn');

        function handleScroll() {
            if (document.body.classList.contains('modal-open')) {
                if (scrollBtn) scrollBtn.style.display = 'none';
                return;
            }
            const scrollY = window.scrollY || document.documentElement.scrollTop;
            if (scrollY > 280) {
                if (scrollBtn) scrollBtn.style.display = 'flex';
            } else {
                if (scrollBtn) scrollBtn.style.display = 'none';
            }
        }

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        window.addEventListener('scroll', handleScroll);
        document.addEventListener('show.bs.modal', function () {
            if (scrollBtn) scrollBtn.style.display = 'none';
        });
        document.addEventListener('hidden.bs.modal', function () {
            handleScroll();
        });
    </script>
</body>
</html>
