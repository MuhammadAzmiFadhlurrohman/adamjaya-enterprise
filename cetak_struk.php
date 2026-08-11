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
    die("Data pengajuan tidak ditemukan.");
}

$stmt_d = mysqli_prepare($conn, "SELECT * FROM pengajuan_detail WHERE pengajuan_id = ? ORDER BY id ASC");
mysqli_stmt_bind_param($stmt_d, "i", $id);
mysqli_stmt_execute($stmt_d);
$res_d = mysqli_stmt_get_result($stmt_d);

$items = [];
$grand_total = 0;
while ($row = mysqli_fetch_assoc($res_d)) {
    $sub = $row['jumlah'] * $row['harga_satuan'];
    $grand_total += $sub;
    $items[] = array_merge($row, ['subtotal' => $sub]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Nota - <?= e($p['custom_id']); ?></title>
    <!-- Favicon / Logo Sidebar Tab Icon -->
    <link rel="icon" type="image/png" href="assets/adamjaya.png">
    <link rel="shortcut icon" type="image/png" href="assets/adamjaya.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style id="printStyle">
        @page { size: 80mm auto; margin: 0mm; }
    </style>

    <style>
        body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* Fixed Left Control Sidebar (Compact & Sleek) */
        .print-sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            padding: 1.1rem 0.95rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            z-index: 100;
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.05);
            overflow-y: auto;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        /* Main Preview Container on the Right */
        .print-preview-container {
            margin-left: 250px;
            min-height: 100vh;
            padding: 1.5rem 1rem;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            box-sizing: border-box;
        }

        /* Paper Size Presets Screen Preview */
        .struk-card {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            padding: 1.2rem;
            border: 1px solid #cbd5e1;
            transition: all 0.3s ease;
            box-sizing: border-box;
            margin: 0 auto;
        }

        .struk-card.size-58mm {
            width: 240px;
            font-size: 11px;
            font-family: 'Courier New', monospace;
            line-height: 1.3;
        }

        .struk-card.size-80mm {
            width: 320px;
            font-size: 12px;
            font-family: 'Courier New', monospace;
            line-height: 1.35;
        }

        .struk-card.size-A4 {
            width: 760px;
            font-size: 13px;
            font-family: 'Segoe UI', system-ui, sans-serif;
            padding: 2.5rem;
            border-radius: 12px;
        }

        .receipt-dashed {
            border-top: 1px dashed #475569;
            margin: 0.6rem 0;
        }

        .receipt-double-dashed {
            border-top: 2px dashed #0f172a;
            margin: 0.6rem 0;
        }

        .btn-paper-option {
            width: 100%;
            text-align: left;
            padding: 0.5rem 0.65rem;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            font-weight: 600;
            font-size: 0.78rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-paper-option:hover {
            border-color: #7a1e33;
            background: #fff5f7;
            color: #7a1e33;
        }

        .btn-paper-option.active {
            background: linear-gradient(135deg, #7A1E33 0%, #4a0b18 100%) !important;
            border-color: #58101F !important;
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(122, 30, 51, 0.25);
        }

        .default-box-compact {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.6rem 0.7rem;
        }

        .btn-wine-print {
            background: linear-gradient(135deg, #7A1E33 0%, #4a0b18 100%);
            border: 1px solid #58101F;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.82rem;
            padding: 0.6rem 0.8rem;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(122, 30, 51, 0.25);
            transition: all 0.2s ease;
        }

        .btn-wine-print:hover {
            background: linear-gradient(135deg, #9b2c47 0%, #7A1E33 100%);
            color: #ffffff;
            transform: translateY(-1px);
        }

        @media print {
            .print-sidebar {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .print-preview-container {
                margin-left: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                display: block !important;
            }
            .struk-card {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                border-radius: 0 !important;
            }
        }

        @media (max-width: 768px) {
            .print-sidebar {
                width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid #cbd5e1;
            }
            .print-preview-container {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

    <!-- Left Fixed Control Sidebar -->
    <div class="print-sidebar">
        <div>
            <!-- Header Title -->
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <img src="assets/adamjaya.png" alt="Adam Jaya Logo" style="width: 28px; height: 28px; object-fit: contain;">
                <div>
                    <h6 class="fw-bold text-wine mb-0" style="letter-spacing: 0.02em; font-size: 0.92rem; font-family: 'Plus Jakarta Sans', sans-serif;">ADAM JAYA</h6>
                    <small class="text-muted fw-bold" style="font-size: 0.64rem; letter-spacing: 0.06em;">PENGATURAN CETAK</small>
                </div>
            </div>

            <!-- Paper Size Options -->
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold mb-1.5" style="font-size: 0.68rem; letter-spacing: 0.05em;">
                    <i class="fa-solid fa-receipt me-1 text-wine"></i> UKURAN KERTAS
                </label>
                
                <div class="d-flex flex-column gap-1.5">
                    <button id="btn-58mm" onclick="selectPaperSize('58mm')" class="btn-paper-option">
                        <span><i class="fa-solid fa-receipt me-1.5"></i> 58mm (Thermal)</span>
                        <i class="fa-solid fa-check check-icon" style="display: none;"></i>
                    </button>
                    <button id="btn-80mm" onclick="selectPaperSize('80mm')" class="btn-paper-option">
                        <span><i class="fa-solid fa-receipt me-1.5"></i> 80mm (Thermal)</span>
                        <i class="fa-solid fa-check check-icon" style="display: none;"></i>
                    </button>
                    <button id="btn-A4" onclick="selectPaperSize('A4')" class="btn-paper-option">
                        <span><i class="fa-solid fa-file-lines me-1.5"></i> Kertas A4 / PDF</span>
                        <i class="fa-solid fa-check check-icon" style="display: none;"></i>
                    </button>
                </div>
            </div>

            <!-- Set As Default Compact Box -->
            <div class="default-box-compact mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1.5">
                    <span class="text-secondary fw-semibold" style="font-size: 0.7rem;">Default:</span>
                    <span id="defaultBadge" class="badge bg-warning text-dark border border-warning px-1.5 py-0.5" style="font-size: 0.65rem; display: none;">
                        <i class="fa-solid fa-star me-1"></i> <b id="defaultSizeText">80mm</b>
                    </span>
                </div>
                <button onclick="saveCurrentAsDefault()" class="btn btn-warning btn-sm w-100 fw-bold py-1 px-2 rounded-2 shadow-xs" style="font-size: 0.72rem;" title="Jadikan ukuran saat ini sebagai default cetak selanjutnya">
                    <i class="fa-solid fa-star me-1 text-dark"></i> Set Sebagai Default
                </button>
                <small class="text-muted d-block text-center mt-1" style="font-size: 0.63rem; line-height: 1.2;">
                    Tersimpan otomatis untuk pencetakan berikutnya.
                </small>
            </div>
        </div>

        <!-- Bottom Action Buttons -->
        <div class="pt-2 border-top">
            <button onclick="window.print()" class="btn btn-wine-print w-100 mb-1.5">
                <i class="fa-solid fa-print me-1"></i> CETAK STRUK SEKARANG
            </button>
            <button onclick="window.close()" class="btn btn-light border text-muted w-100 btn-sm py-1.5 rounded-2" style="font-size: 0.76rem;">
                <i class="fa-solid fa-xmark me-1"></i> Tutup Halaman
            </button>
        </div>
    </div>

    <!-- Main Live Receipt Preview Area on the Right -->
    <div class="print-preview-container">
        <div id="strukCard" class="struk-card size-80mm">
            <!-- Logo & Header Toko -->
            <div class="text-center">
                <img src="assets/adamjaya.png" alt="Adam Jaya Logo" style="width: 48px; height: 48px; object-fit: contain; margin-bottom: 4px;">
                <div class="fw-bold text-uppercase" style="font-size: 1.1em; letter-spacing: 0.05em;">ADAM JAYA ENTERPRISE</div>
                <div class="small">Procurement & Inventory System</div>
                <div class="small text-muted">Surakarta, Jawa Tengah</div>
                <div class="small text-muted">Telp: 0812-3456-7890</div>
            </div>

            <div class="receipt-double-dashed"></div>

            <!-- Info Transaksi -->
            <div class="d-flex justify-content-between">
                <span>NO NOTA :</span>
                <span class="fw-bold"><?= e($p['custom_id']); ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span>TANGGAL :</span>
                <span><?= date('d/m/Y H:i', strtotime($p['created_at'])); ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span>ADMIN   :</span>
                <span><?= e($p['username']); ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span>PELANGGAN:</span>
                <span class="fw-bold"><?= e($p['nama_pembeli'] ?: '-'); ?></span>
            </div>
            <?php if (!empty($p['telepon_pembeli'])): ?>
            <div class="d-flex justify-content-between">
                <span>TELP HP :</span>
                <span><?= e($p['telepon_pembeli']); ?></span>
            </div>
            <?php endif; ?>

            <div class="receipt-dashed"></div>

            <!-- Daftar Barang -->
            <div class="mb-1">
                <?php foreach ($items as $item): ?>
                    <div class="fw-bold text-uppercase"><?= e($item['nama_barang']); ?></div>
                    <?php if (!empty($item['nama_jenis']) && $item['nama_jenis'] !== '-'): ?>
                        <div class="small text-muted ps-2" style="font-size: 0.9em;"><?= e($item['nama_jenis']); ?></div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between small">
                        <span><?= number_format($item['jumlah'], 2, ',', '.'); ?> <?= e($item['satuan']); ?> x <?= formatRupiah($item['harga_satuan']); ?></span>
                        <span class="fw-bold"><?= formatRupiah($item['subtotal']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="receipt-dashed"></div>

            <!-- Total Pembayaran -->
            <div class="d-flex justify-content-between fs-6 fw-bold">
                <span>TOTAL :</span>
                <span><?= formatRupiah($grand_total); ?></span>
            </div>

            <div class="d-flex justify-content-between small mt-1">
                <span>STATUS BAYAR :</span>
                <?php if ($p['status_pembayaran'] === 'dibayar'): ?>
                    <span class="badge bg-success">LUNAS</span>
                <?php else: ?>
                    <span class="badge bg-danger">BELUM LUNAS</span>
                <?php endif; ?>
            </div>
            <div class="d-flex justify-content-between small">
                <span>STATUS KIRIM :</span>
                <span class="fw-bold"><?= e(strtoupper($p['status_pengiriman'])); ?></span>
            </div>

            <div class="receipt-double-dashed"></div>

            <!-- Footer Ucapan & QR Code -->
            <div class="text-center my-2">
                <div class="small fw-bold">TERIMA KASIH ATAS KUNJUNGAN ANDA</div>
                <div class="small text-muted" style="font-size: 0.85em;">Barang yang sudah dibeli tidak dapat ditukar / dikembalikan.</div>
                <div class="mt-2">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data=<?= urlencode('ADAM-JAYA-' . $p['custom_id']); ?>" alt="QR Code Validasi" style="width: 75px; height: 75px; border: 1px solid #cbd5e1; padding: 2px; border-radius: 4px;">
                </div>
                <div class="small text-muted mt-1" style="font-size: 0.75em; letter-spacing: 0.05em;">VALIDATED DIGITAL RECEIPT</div>
            </div>
        </div>
    </div>

    <script>
    let currentPaperSize = '80mm';

    function selectPaperSize(size) {
        currentPaperSize = size;
        const card = document.getElementById('strukCard');
        const printStyle = document.getElementById('printStyle');
        
        document.querySelectorAll('.btn-paper-option').forEach(btn => {
            btn.classList.remove('active');
            const check = btn.querySelector('.check-icon');
            if (check) check.style.display = 'none';
        });
        
        const activeBtn = document.getElementById('btn-' + size);
        if (activeBtn) {
            activeBtn.classList.add('active');
            const check = activeBtn.querySelector('.check-icon');
            if (check) check.style.display = 'inline-block';
        }

        card.className = 'struk-card size-' + size;

        if (size === '58mm') {
            printStyle.innerHTML = `@page { size: 58mm auto; margin: 0mm; } body { margin: 0; } .struk-card { width: 58mm !important; max-width: 58mm !important; padding: 2mm !important; font-size: 10px !important; }`;
        } else if (size === '80mm') {
            printStyle.innerHTML = `@page { size: 80mm auto; margin: 0mm; } body { margin: 0; } .struk-card { width: 80mm !important; max-width: 80mm !important; padding: 3mm !important; font-size: 11px !important; }`;
        } else {
            printStyle.innerHTML = `@page { size: A4 portrait; margin: 10mm; } .struk-card { width: 100% !important; max-width: 750px !important; padding: 20px !important; font-size: 13px !important; }`;
        }
    }

    function saveCurrentAsDefault() {
        try {
            localStorage.setItem('adamjaya_default_paper_size', currentPaperSize);
            updateDefaultBadgeDisplay();
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Default Berhasil Disimpan!',
                    text: 'Ukuran kertas (' + currentPaperSize + ') dijadikan sebagai default utama pencetakan struk.',
                    timer: 2500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                alert('Ukuran kertas (' + currentPaperSize + ') berhasil dijadikan default!');
            }
        } catch(e){}
    }

    function updateDefaultBadgeDisplay() {
        const savedDefault = localStorage.getItem('adamjaya_default_paper_size') || '80mm';
        const badge = document.getElementById('defaultBadge');
        const text = document.getElementById('defaultSizeText');
        if (badge && text) {
            text.innerText = savedDefault;
            badge.style.display = 'inline-flex';
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const savedSize = localStorage.getItem('adamjaya_default_paper_size') || '80mm';
        selectPaperSize(savedSize);
        updateDefaultBadgeDisplay();
    });
    </script>
</body>
</html>
