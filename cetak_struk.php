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
    <style>
        body {
            background-color: #f8fafc;
            color: #1e293b;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .struk-card {
            max-width: 750px;
            margin: 2rem auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 2.5rem;
            border: 1px solid #e2e8f0;
        }
        .receipt-header {
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .table-receipt th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .struk-card {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print text-center my-3">
        <button onclick="window.print()" class="btn btn-primary btn-lg px-4 me-2">
            <i class="fa-solid fa-print me-1"></i> Cetak / Simpan PDF
        </button>
        <button onclick="window.close()" class="btn btn-secondary btn-lg px-4">
            <i class="fa-solid fa-xmark me-1"></i> Tutup
        </button>
    </div>

    <div class="struk-card">
        <!-- Header Nota -->
        <div class="receipt-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark mb-0">ADAM JAYA ENTERPRISE</h3>
                <small class="text-muted d-block">Procurement & Inventory Operations</small>
                <small class="text-muted">Surakarta, Jawa Tengah | Telp: 081234567890</small>
            </div>
            <div class="text-end">
                <h5 class="fw-bold text-primary mb-1"><?= e($p['custom_id']); ?></h5>
                <small class="text-muted d-block"><?= date('d F Y H:i', strtotime($p['created_at'])); ?></small>
                <div class="mt-1">
                    <?php if ($p['status_pembayaran'] === 'dibayar'): ?>
                        <span class="badge bg-success">LUNAS DIBAYAR</span>
                    <?php else: ?>
                        <span class="badge bg-danger">BELUM DIBAYAR</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Info Pembeli & Admin -->
        <div class="row mb-4">
            <div class="col-6">
                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Kepada Yth Pelanggan:</small>
                <h6 class="fw-bold mb-0 text-dark"><?= e($p['nama_pembeli'] ?: '-'); ?></h6>
                <small class="text-muted">Telp: <?= e($p['telepon_pembeli'] ?: '-'); ?></small>
            </div>
            <div class="col-6 text-end">
                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Petugas Admin:</small>
                <h6 class="fw-bold mb-0 text-dark"><?= e($p['username']); ?></h6>
                <small class="text-muted">Status Kirim: <?= e(strtoupper($p['status_pengiriman'])); ?></small>
            </div>
        </div>

        <!-- Tabel Rincian Item -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-receipt align-middle">
                <thead>
                    <tr>
                        <th width="40">#</th>
                        <th>Nama Barang & Varian</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Harga Satuan</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $grand_total = 0;
                    while ($d = mysqli_fetch_assoc($res_d)):
                        $sub = $d['jumlah'] * $d['harga_satuan'];
                        $grand_total += $sub;
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td>
                                <strong><?= e($d['nama_barang']); ?></strong>
                                <?php if (!empty($d['nama_jenis'])): ?>
                                    <div class="small text-muted"><?= e($d['nama_jenis']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= number_format($d['jumlah'], 2, ',', '.'); ?> <?= e($d['satuan']); ?></td>
                            <td class="text-end"><?= formatRupiah($d['harga_satuan']); ?></td>
                            <td class="text-end fw-bold"><?= formatRupiah($sub); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end text-dark fs-6">TOTAL ESTIMASI NOTA:</th>
                        <th class="text-end fw-bold text-primary fs-5"><?= formatRupiah($grand_total); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Footer Signatures -->
        <div class="row pt-4 mt-4 border-top">
            <div class="col-6 text-center">
                <small class="text-muted d-block mb-5">Penerima / Pembeli,</small>
                <p class="fw-bold mb-0">( <?= e($p['nama_pembeli'] ?: '.....................'); ?> )</p>
            </div>
            <div class="col-6 text-center">
                <small class="text-muted d-block mb-5">Hormat Kami,</small>
                <p class="fw-bold mb-0">Adam Jaya Enterprise</p>
            </div>
        </div>
    </div>

</body>
</html>
