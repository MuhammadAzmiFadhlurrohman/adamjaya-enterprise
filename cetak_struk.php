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
    
    <style id="printStyle">
        @page { size: 80mm auto; margin: 0mm; }
    </style>

    <style>
        body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-family: 'Courier New', Courier, monospace;
        }

        .no-print-toolbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.85rem 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        /* Container Struk Thermal */
        .struk-card {
            margin: 1.5rem auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            padding: 1.2rem;
            border: 1px solid #cbd5e1;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        /* Paper Size Presets Screen Preview */
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

        .btn-paper-size {
            font-size: 0.82rem;
            font-weight: 600;
            border-radius: 20px;
            padding: 0.35rem 0.9rem;
        }

        .btn-paper-size.active {
            background-color: #7a1e33 !important;
            border-color: #58101f !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(122, 30, 51, 0.3);
        }

        @media print {
            .no-print-toolbar {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .struk-card {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                border-radius: 0 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Toolbar Pengaturan Cetak & Ukuran Kertas -->
    <div class="no-print-toolbar text-center">
        <div class="d-flex flex-wrap align-items-center justify-content-center gap-2 mb-2">
            <span class="small fw-bold text-muted me-1"><i class="fa-solid fa-print me-1 text-primary"></i> PILIH UKURAN KERTAS:</span>
            <button id="btn-58mm" onclick="setPaperSize('58mm')" class="btn btn-outline-secondary btn-paper-size btn-sm">
                <i class="fa-solid fa-receipt me-1"></i> 58mm (POS Thermal Kecil)
            </button>
            <button id="btn-80mm" onclick="setPaperSize('80mm')" class="btn btn-outline-secondary btn-paper-size btn-sm active">
                <i class="fa-solid fa-receipt me-1"></i> 80mm (POS Thermal Standar)
            </button>
            <button id="btn-A4" onclick="setPaperSize('A4')" class="btn btn-outline-secondary btn-paper-size btn-sm">
                <i class="fa-solid fa-file-lines me-1"></i> Halaman A4 / PDF
            </button>
        </div>

        <div class="d-flex justify-content-center gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm px-4 fw-bold rounded-pill">
                <i class="fa-solid fa-print me-1"></i> CETAK STRUK SEKARANG
            </button>
            <button onclick="window.close()" class="btn btn-secondary btn-sm px-3 rounded-pill">
                <i class="fa-solid fa-xmark me-1"></i> Tutup
            </button>
        </div>
    </div>

    <!-- Container Struk Nota -->
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

    <script>
    function setPaperSize(size) {
        const card = document.getElementById('strukCard');
        const printStyle = document.getElementById('printStyle');
        
        document.querySelectorAll('.btn-paper-size').forEach(btn => {
            btn.classList.remove('active');
        });
        const activeBtn = document.getElementById('btn-' + size);
        if (activeBtn) activeBtn.classList.add('active');

        card.className = 'struk-card size-' + size;

        if (size === '58mm') {
            printStyle.innerHTML = `@page { size: 58mm auto; margin: 0mm; } body { margin: 0; } .struk-card { width: 58mm !important; max-width: 58mm !important; padding: 2mm !important; font-size: 10px !important; }`;
        } else if (size === '80mm') {
            printStyle.innerHTML = `@page { size: 80mm auto; margin: 0mm; } body { margin: 0; } .struk-card { width: 80mm !important; max-width: 80mm !important; padding: 3mm !important; font-size: 11px !important; }`;
        } else {
            printStyle.innerHTML = `@page { size: A4 portrait; margin: 10mm; } .struk-card { width: 100% !important; max-width: 750px !important; padding: 20px !important; font-size: 13px !important; }`;
        }
        
        try {
            localStorage.setItem('preferred_paper_size', size);
        } catch(e){}
    }

    document.addEventListener("DOMContentLoaded", function() {
        const savedSize = localStorage.getItem('preferred_paper_size') || '80mm';
        setPaperSize(savedSize);
    });
    </script>
</body>
</html>
