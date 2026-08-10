<?php
require_once __DIR__ . '/includes/header.php';
require_login();

$is_admin = is_admin();

// Parameter Filter
$bulan = sanitize($_GET['bulan'] ?? '');
$tahun = sanitize($_GET['tahun'] ?? date('Y'));
$status_pembayaran = sanitize($_GET['status_pembayaran'] ?? '');
$status_pengiriman = sanitize($_GET['status_pengiriman'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$where_clauses = ["1=1"];
$params = [];
$types = "";

if (!empty($bulan)) {
    $where_clauses[] = "MONTH(p.created_at) = ?";
    $params[] = (int)$bulan;
    $types .= "i";
}

if (!empty($tahun)) {
    $where_clauses[] = "YEAR(p.created_at) = ?";
    $params[] = (int)$tahun;
    $types .= "i";
}

if (!empty($status_pembayaran)) {
    $where_clauses[] = "p.status_pembayaran = ?";
    $params[] = $status_pembayaran;
    $types .= "s";
}

if (!empty($status_pengiriman)) {
    $where_clauses[] = "p.status_pengiriman = ?";
    $params[] = $status_pengiriman;
    $types .= "s";
}

if (!empty($search)) {
    $where_clauses[] = "(p.custom_id LIKE ? OR p.nama_pembeli LIKE ?)";
    $s_term = "%$search%";
    $params[] = $s_term;
    $params[] = $s_term;
    $types .= "ss";
}

$where_sql = implode(" AND ", $where_clauses);

// Query Data Pengajuan
$query = "SELECT p.*, u.username FROM pengajuan p JOIN users u ON p.user_id = u.id WHERE $where_sql ORDER BY p.id DESC";
$stmt = mysqli_prepare($conn, $query);

if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// KPI Stats
$total_count = 0;
$total_nominal = 0.0;
$total_dibayar = 0.0;
$total_belum_dibayar = 0.0;

$rows_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows_data[] = $row;
    $total_count++;
    $nom = (float)$row['estimasi_dana'];
    $total_nominal += $nom;
    if ($row['status_pembayaran'] === 'dibayar') {
        $total_dibayar += $nom;
    } else {
        $total_belum_dibayar += $nom;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pembelian</title>
    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* ===== CSS VARIABLES & RESET ===== */
        :root {
            --primary: #6C3A8C;
            --primary-light: #8B5FAC;
            --primary-dark: #4A1F63;
            --gold: #D4A843;
            --gold-light: #E8C96E;
            --wine: #7A1F4A;
            --wine-light: #A63A6A;
            --success: #28A745;
            --danger: #DC3545;
            --warning: #FFC107;
            --info: #17A2B8;
            --card-shadow: 0 8px 32px rgba(0,0,0,0.08);
            --card-hover-shadow: 0 12px 48px rgba(0,0,0,0.12);
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ===== SCROLLBAR STYLING ===== */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, var(--primary-light), var(--wine)); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary); }

        body {
            background: linear-gradient(135deg, #f8f4ff 0%, #f0e8f8 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            padding: 0;
        }

        .main-content {
            padding: 24px 28px 40px;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* ===== HEADER BANNER ===== */
        .page-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 40%, var(--wine) 100%);
            padding: 32px 40px;
            border-radius: var(--border-radius);
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 48px rgba(106, 13, 173, 0.25);
        }
        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
            border-radius: 50%;
        }
        .page-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(212, 168, 67, 0.08) 0%, transparent 70%);
            border-radius: 50%;
        }
        .page-header > * { position: relative; z-index: 1; }

        .page-eyebrow {
            font-size: 0.75rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.6);
            font-weight: 600;
        }
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
            margin: 4px 0 6px;
            letter-spacing: -0.5px;
        }
        .page-subtitle {
            color: rgba(255,255,255,0.75);
            font-size: 0.95rem;
        }

        .header-action {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn-pengajuan-header {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.25);
            color: #fff;
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-pengajuan-header:hover {
            background: rgba(255,255,255,0.25);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }

        /* ===== STATS CARDS ===== */
        .stats-row {
            margin-bottom: 28px;
        }
        .stats-card {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.6);
            border-radius: var(--border-radius);
            padding: 20px 22px;
            transition: var(--transition);
            box-shadow: var(--card-shadow);
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-hover-shadow);
            border-color: var(--gold-light);
        }
        .stats-card .card-body {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 0;
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
            transition: var(--transition);
        }
        .stats-card:hover .stat-icon {
            transform: scale(1.08) rotate(-2deg);
        }
        .icon-wine { background: linear-gradient(135deg, var(--wine-light), var(--wine)); color: #fff; }
        .icon-gold { background: linear-gradient(135deg, var(--gold-light), var(--gold)); color: #fff; }
        .icon-success { background: linear-gradient(135deg, #34CE57, var(--success)); color: #fff; }
        .icon-danger { background: linear-gradient(135deg, #FF6B6B, var(--danger)); color: #fff; }

        .stat-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1a1a2e;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #6b6b7a;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ===== FILTER CARD ===== */
        .filter-card {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.6);
            border-radius: var(--border-radius);
            padding: 24px 28px;
            margin-bottom: 28px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }
        .filter-card:hover {
            box-shadow: var(--card-hover-shadow);
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px 20px;
            align-items: end;
        }
        .filter-group label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6b6b7a;
            display: block;
            margin-bottom: 4px;
        }
        .filter-group .form-select {
            border-radius: 12px;
            border: 1.5px solid #e8e3f0;
            background-color: #faf8ff;
            font-size: 0.9rem;
            padding: 10px 14px;
            transition: var(--transition);
            color: #1a1a2e;
        }
        .filter-group .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(108, 58, 140, 0.1);
            background-color: #fff;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-filter-primary {
            background: linear-gradient(135deg, var(--primary), var(--wine));
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-filter-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(108, 58, 140, 0.3);
            color: #fff;
        }
        .btn-filter-reset {
            background: transparent;
            border: 1.5px solid #d5d0e0;
            color: #6b6b7a;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-filter-reset:hover {
            background: #f5f2fa;
            border-color: var(--primary);
            color: var(--primary);
        }

        .filter-search-row {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #f0ebf8;
        }
        .filter-search-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #4a4a5a;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .search-input-wrapper {
            position: relative;
        }
        .search-input-wrapper .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #a9a0b8;
        }
        .search-input-field {
            width: 100%;
            padding: 12px 18px 12px 48px;
            border-radius: 14px;
            border: 1.5px solid #e8e3f0;
            background: #faf8ff;
            font-size: 0.95rem;
            transition: var(--transition);
            color: #1a1a2e;
        }
        .search-input-field:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(108, 58, 140, 0.08);
            background: #fff;
            outline: none;
        }
        .search-clear-btn {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: #f0ebf8;
            color: #6b6b7a;
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            text-decoration: none;
            transition: var(--transition);
        }
        .search-clear-btn:hover {
            background: #e0d8f0;
            color: var(--primary);
        }

        /* ===== TABLE CONTAINER ===== */
        .table-container {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(12px);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.6);
        }
        .table-container-header {
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            border-bottom: 1px solid #f0ebf8;
            background: linear-gradient(135deg, #faf8ff, #f5f0fa);
        }
        .table-container-header h2 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .table-container-header h2 .text-wine { color: var(--wine); }
        .badge-count {
            background: linear-gradient(135deg, var(--primary-light), var(--wine-light));
            color: #fff;
            padding: 6px 18px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .table {
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        .table thead {
            background: linear-gradient(135deg, #f8f4ff, #f0e8f8);
            border-bottom: 2px solid #e8e3f0;
        }
        .table thead th {
            padding: 16px 18px;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #5a5a6a;
            border-bottom: none;
            white-space: nowrap;
        }
        .table tbody tr {
            transition: var(--transition);
            border-bottom: 1px solid #f0ebf8;
        }
        .table tbody tr:hover {
            background: #faf8ff;
            transform: scale(1.002);
        }
        .table tbody td {
            padding: 16px 18px;
            vertical-align: middle;
            color: #2a2a3e;
        }

        /* Status Row Highlight */
        .status-selesai { border-left: 4px solid var(--success); }
        .status-proses { border-left: 4px solid var(--gold); }
        .status-belum { border-left: 4px solid var(--danger); }

        .id-chip {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.8rem;
            background: #f0ebf8;
            color: var(--primary-dark);
            letter-spacing: 0.3px;
        }
        .id-chip.status-selesai { background: #e6f7ed; color: var(--success); }
        .id-chip.status-proses { background: #fff8e6; color: #b8860b; }
        .id-chip.status-belum { background: #fde8e8; color: var(--danger); }

        .amount-cell {
            font-weight: 700;
            color: var(--primary-dark);
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .badge-status.success { background: #e6f7ed; color: var(--success); }
        .badge-status.danger { background: #fde8e8; color: var(--danger); }
        .badge-status.info { background: #e3f3fa; color: var(--info); }
        .badge-status.warning { background: #fff8e6; color: #b8860b; }

        .action-btns {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            background: transparent;
            color: #6b6b7a;
        }
        .action-btn i { font-size: 0.75rem; }
        .action-btn:hover { transform: translateY(-1px); }
        .btn-detail { background: #e8f0fe; color: #1a73e8; }
        .btn-detail:hover { background: #d2e3fc; color: #1557b0; }
        .btn-edit { background: #fff8e6; color: #b8860b; }
        .btn-edit:hover { background: #fef0d0; color: #8a6a08; }
        .btn-delete { background: #fde8e8; color: var(--danger); }
        .btn-delete:hover { background: #fcd5d5; color: #b02a37; }

        /* ===== NO DATA ===== */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #a9a0b8;
        }
        .no-data i {
            font-size: 4rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        .no-data h5 {
            color: #4a4a5a;
            font-weight: 600;
        }

        /* ===== MODAL ===== */
        .glass-modal .modal-content {
            background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,244,255,0.98));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: var(--border-radius);
            box-shadow: 0 24px 80px rgba(0,0,0,0.2);
        }
        .glass-modal .modal-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--wine));
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            padding: 20px 28px;
        }
        .glass-modal .modal-header .modal-title {
            color: #fff;
            font-weight: 700;
        }
        .glass-modal .modal-body {
            padding: 28px;
            max-height: 70vh;
            overflow-y: auto;
        }
        .glass-modal .modal-footer {
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            background: #faf8ff;
        }
        .btn-secondary-custom {
            background: linear-gradient(135deg, #6b6b7a, #4a4a5a);
            color: #fff;
            border: none;
            padding: 10px 28px;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
        }
        .btn-secondary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            color: #fff;
        }

        /* ===== ROW HIGHLIGHT ANIMATION ===== */
        @keyframes highlightPulse {
            0%, 100% { background: transparent; }
            25% { background: rgba(212, 168, 67, 0.15); }
            75% { background: rgba(212, 168, 67, 0.08); }
        }
        .row-highlight-pulse {
            animation: highlightPulse 1.5s ease 2;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .main-content { padding: 16px; }
            .page-header { padding: 24px 20px; }
            .page-title { font-size: 1.5rem; }
            .header-action { width: 100%; justify-content: flex-start; }
            .filter-row { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 768px) {
            .main-content { padding: 12px; }
            .page-header { padding: 20px 16px; border-radius: 12px; }
            .page-title { font-size: 1.3rem; }
            .page-subtitle { font-size: 0.85rem; }
            .btn-pengajuan-header { padding: 8px 18px; font-size: 0.8rem; }
            .filter-card { padding: 16px; }
            .filter-row { grid-template-columns: 1fr 1fr; gap: 12px; }
            .filter-actions { grid-column: 1 / -1; }
            .stats-card { padding: 16px; }
            .stat-value { font-size: 1.3rem; }
            .stat-icon { width: 44px; height: 44px; font-size: 1.1rem; }
            .table-container-header { padding: 16px; flex-direction: column; align-items: stretch; text-align: center; }
            .table-container-header h2 { font-size: 1rem; justify-content: center; }
            .badge-count { align-self: center; }
            .table tbody td { padding: 12px 14px; }
            .action-btn { font-size: 0.65rem; padding: 4px 10px; }
            .glass-modal .modal-body { padding: 16px; }
        }

        @media (max-width: 576px) {
            .main-content { padding: 8px; }
            .page-header { padding: 16px 12px; border-radius: 10px; }
            .page-title { font-size: 1.1rem; }
            .page-eyebrow { font-size: 0.65rem; }
            .stats-row .col-6 { padding: 0 4px; }
            .stats-card { padding: 12px 14px; border-radius: 12px; }
            .stat-value { font-size: 1.1rem; }
            .stat-label { font-size: 0.65rem; }
            .stat-icon { width: 36px; height: 36px; font-size: 0.9rem; }
            .filter-card { padding: 12px; border-radius: 12px; }
            .filter-row { grid-template-columns: 1fr; gap: 10px; }
            .filter-group .form-select { font-size: 0.8rem; padding: 8px 12px; }
            .btn-filter-primary, .btn-filter-reset { font-size: 0.75rem; padding: 8px 16px; width: 100%; justify-content: center; }
            .search-input-field { font-size: 0.85rem; padding: 10px 14px 10px 42px; }
            .table thead { display: none; }
            .table tbody tr {
                display: block;
                margin-bottom: 12px;
                border-radius: 12px;
                background: #fff;
                box-shadow: 0 2px 12px rgba(0,0,0,0.06);
                border-left: 4px solid var(--primary-light);
                padding: 4px 0;
            }
            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 14px;
                border-bottom: 1px solid #f5f2fa;
                font-size: 0.85rem;
            }
            .table tbody td:last-child { border-bottom: none; }
            .table tbody td::before {
                content: attr(data-label);
                font-weight: 700;
                font-size: 0.7rem;
                color: #6b6b7a;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-right: 12px;
                flex-shrink: 0;
            }
            .table tbody td .badge-status { font-size: 0.65rem; padding: 3px 10px; }
            .action-btns { justify-content: flex-end; width: 100%; }
            .action-btn { font-size: 0.6rem; padding: 3px 8px; }
            .glass-modal .modal-content { border-radius: 12px; }
            .glass-modal .modal-header { padding: 16px; border-radius: 12px 12px 0 0; }
            .glass-modal .modal-header .modal-title { font-size: 1rem; }
            .glass-modal .modal-footer { flex-direction: column; gap: 8px; }
            .btn-secondary-custom { width: 100%; justify-content: center; }
        }

        /* ===== PRINT ===== */
        @media print {
            .page-header, .filter-card, .action-btns, .btn-close, .modal-footer { display: none !important; }
            .table-container { box-shadow: none; border: 1px solid #ddd; border-radius: 0; }
            .table thead { background: #f5f5f5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge-status { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
<div class="main-content">
    <!-- ===== HEADER ===== -->
    <header class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="page-eyebrow"><i class="fa-solid fa-store me-1"></i> Panel Operational &middot; Pengadaan Barang</span>
                <h1 class="page-title">Daftar Pembelian & Transaksi</h1>
                <p class="page-subtitle mb-0">Pantau, verifikasi, dan kelola seluruh pengajuan pembelian dari satu tempat.</p>
            </div>
            <div class="header-action">
                <a href="export_excel.php?<?= http_build_query($_GET); ?>" class="btn-pengajuan-header" style="background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.2);">
                    <i class="fa-solid fa-file-excel me-1"></i> Ekspor Excel
                </a>
                <?php if ($is_admin): ?>
                    <a href="tambah_pengajuan.php" class="btn-pengajuan-header">
                        <i class="fa-solid fa-plus-circle"></i> Buat Pembelian Baru
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- ===== KPI STATS ===== -->
    <div class="row g-2 g-md-3 stats-row">
        <div class="col-6 col-md-3">
            <div class="stats-card">
                <div class="card-body">
                    <div class="stat-icon icon-wine"><i class="fa-solid fa-file-lines"></i></div>
                    <div>
                        <div class="stat-value"><?= number_format($total_count); ?></div>
                        <div class="stat-label">Total Pengajuan</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stats-card">
                <div class="card-body">
                    <div class="stat-icon icon-gold"><i class="fa-solid fa-sack-dollar"></i></div>
                    <div>
                        <div class="stat-value"><?= formatRupiah($total_nominal); ?></div>
                        <div class="stat-label">Total Nilai Transaksi</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stats-card">
                <div class="card-body">
                    <div class="stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <div class="stat-value"><?= formatRupiah($total_dibayar); ?></div>
                        <div class="stat-label">Sudah Lunas Dibayar</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stats-card">
                <div class="card-body">
                    <div class="stat-icon icon-danger"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div>
                        <div class="stat-value"><?= formatRupiah($total_belum_dibayar); ?></div>
                        <div class="stat-label">Belum Dibayar</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FILTER ===== -->
    <div class="filter-card">
        <form method="GET" action="insert_admin.php">
            <div class="filter-row">
                <div class="filter-group">
                    <label><i class="fa-regular fa-calendar me-1"></i> Bulan</label>
                    <select name="bulan" class="form-select">
                        <option value="">Semua Bulan</option>
                        <?php 
                        $months = [
                            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                        ];
                        foreach ($months as $m_num => $m_name): 
                        ?>
                            <option value="<?= $m_num; ?>" <?= ($bulan === $m_num) ? 'selected' : ''; ?>><?= $m_name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fa-regular fa-calendar me-1"></i> Tahun</label>
                    <select name="tahun" class="form-select">
                        <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                            <option value="<?= $y; ?>" <?= ($tahun == $y) ? 'selected' : ''; ?>><?= $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fa-regular fa-credit-card me-1"></i> Status Bayar</label>
                    <select name="status_pembayaran" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="belum_dibayar" <?= ($status_pembayaran === 'belum_dibayar') ? 'selected' : ''; ?>>Belum Dibayar</option>
                        <option value="dibayar" <?= ($status_pembayaran === 'dibayar') ? 'selected' : ''; ?>>Dibayar (Lunas)</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fa-solid fa-truck me-1"></i> Status Kirim</label>
                    <select name="status_pengiriman" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="belum_dikirim" <?= ($status_pengiriman === 'belum_dikirim') ? 'selected' : ''; ?>>Belum Dikirim</option>
                        <option value="sudah_dikirim" <?= ($status_pengiriman === 'sudah_dikirim') ? 'selected' : ''; ?>>Sudah Dikirim</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-filter-primary"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                    <a href="insert_admin.php" class="btn-filter-reset"><i class="fa-solid fa-rotate-left me-1"></i> Reset</a>
                </div>
            </div>

            <div class="filter-search-row">
                <div class="filter-search-group">
                    <label><i class="fa-solid fa-magnifying-glass me-1"></i> Cari ID Nota / Nama Pembeli</label>
                    <div class="search-input-wrapper">
                        <i class="fa-solid fa-search search-icon"></i>
                        <input type="text" name="search" class="search-input-field" placeholder="Ketik nomor ID nota atau nama pembeli..." value="<?= e($search); ?>">
                        <?php if (!empty($search)): ?>
                            <a href="insert_admin.php" class="search-clear-btn">&times;</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ===== TABLE ===== -->
    <div class="table-container">
        <div class="table-container-header">
            <h2><i class="fa-solid fa-list-check me-2 text-wine"></i> Daftar Transaksi Pengajuan</h2>
            <span class="badge-count"><i class="fa-regular fa-file-lines me-1"></i> <?= $total_count; ?> Record</span>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID Nota</th>
                        <th>Pelanggan & Tanggal</th>
                        <th>Admin</th>
                        <th class="text-end">Estimasi Dana</th>
                        <th>Status Bayar</th>
                        <th>Status Kirim</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows_data) > 0): ?>
                        <?php foreach ($rows_data as $p): 
                            $status_class = ($p['status_pembayaran'] === 'dibayar' && $p['status_pengiriman'] === 'sudah_dikirim') ? 'status-selesai' : (($p['status_pembayaran'] === 'dibayar') ? 'status-proses' : 'status-belum');
                        ?>
                            <tr id="row-nota-<?= $p['id']; ?>" class="<?= $status_class; ?>">
                                <td data-label="ID Nota">
                                    <span class="id-chip <?= $status_class; ?>">#<?= e($p['custom_id']); ?></span>
                                </td>
                                <td data-label="Pelanggan & Tanggal">
                                    <strong class="text-dark d-block"><?= e($p['nama_pembeli'] ?: '-'); ?></strong>
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">
                                        <i class="fa-regular fa-clock me-1 text-gold"></i> <?= date('d M Y, H:i', strtotime($p['created_at'])); ?>
                                    </small>
                                </td>
                                <td data-label="Admin">
                                    <span class="badge bg-light text-dark border px-2 py-1 rounded-pill fw-medium" style="font-size: 0.75rem;">
                                        <i class="fa-solid fa-user-circle me-1 text-wine"></i> <?= e($p['username'] ?: 'Admin'); ?>
                                    </span>
                                </td>
                                <td data-label="Estimasi Dana" class="text-end">
                                    <strong class="amount-cell"><?= formatRupiah($p['estimasi_dana']); ?></strong>
                                </td>
                                <td data-label="Status Bayar">
                                    <?php if ($p['status_pembayaran'] === 'dibayar'): ?>
                                        <span class="badge-status success"><i class="fa-solid fa-check-circle"></i> Dibayar</span>
                                    <?php else: ?>
                                        <span class="badge-status danger"><i class="fa-solid fa-clock"></i> Belum Dibayar</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status Kirim">
                                    <?php if ($p['status_pengiriman'] === 'sudah_dikirim'): ?>
                                        <span class="badge-status info"><i class="fa-solid fa-truck-fast"></i> Dikirim</span>
                                    <?php else: ?>
                                        <span class="badge-status warning"><i class="fa-solid fa-box"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Aksi" class="text-center">
                                    <div class="action-btns justify-content-center">
                                        <button class="action-btn btn-detail" onclick="openDetailModal(<?= $p['id']; ?>, 'row-nota-<?= $p['id']; ?>')">
                                            <i class="fa-solid fa-eye"></i> Detail
                                        </button>
                                        <?php if ($is_admin): ?>
                                            <a href="edit_pengajuan.php?id=<?= $p['id']; ?>&return_row=row-nota-<?= $p['id']; ?>" class="action-btn btn-edit">
                                                <i class="fa-solid fa-pen"></i> Edit
                                            </a>
                                            <a href="#" class="action-btn btn-delete" 
                                               onclick="confirmDelete(event, 'proses_hapus_pengajuan.php?id=<?= $p['id']; ?>&csrf_token=<?= generate_csrf_token(); ?>')">
                                                <i class="fa-solid fa-trash"></i> Hapus
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="no-data">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <h5>Data Pengajuan Tidak Ditemukan</h5>
                                    <p class="text-muted">Tidak ada transaksi pengajuan pembelian yang sesuai dengan kriteria filter.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== MODAL ===== -->
<div class="modal fade" id="modalDetailPengajuan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Rincian Detail Nota</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2">Memuat detail transaksi...</p>
                </div>
            </div>
            <div class="modal-footer">
                <span class="small text-muted"><i class="fa-solid fa-building me-1 text-gold"></i> Adam Jaya Enterprise System</span>
                <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let lastActiveRowId = '';

function openDetailModal(id, rowId) {
    if (rowId) lastActiveRowId = rowId;
    const modalBody = document.getElementById('detailModalBody');
    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-wine" role="status"></div>
            <p class="text-muted mt-2">Memuat detail transaksi...</p>
        </div>
    `;
    const myModal = new bootstrap.Modal(document.getElementById('modalDetailPengajuan'));
    myModal.show();

    fetch(`get_pengajuan_detail.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            modalBody.innerHTML = html;
        })
        .catch(err => {
            modalBody.innerHTML = `<div class="alert alert-danger">Gagal memuat detail data pengajuan.</div>`;
        });
}

document.addEventListener("DOMContentLoaded", function() {
    const modalElem = document.getElementById('modalDetailPengajuan');
    if (modalElem) {
        modalElem.addEventListener('hidden.bs.modal', function () {
            if (window.needsPageReload) {
                const targetHash = lastActiveRowId ? '#' + lastActiveRowId : '';
                window.location.href = window.location.pathname + window.location.search + targetHash;
                window.location.reload();
            } else if (lastActiveRowId) {
                scrollToRow(lastActiveRowId);
            }
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    const scrollTo = urlParams.get('scroll_to') || window.location.hash.replace('#', '');
    if (scrollTo) {
        setTimeout(() => scrollToRow(scrollTo), 250);
    }
});

function scrollToRow(rowId) {
    const target = document.getElementById(rowId);
    if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.classList.add('row-highlight-pulse');
        setTimeout(() => target.classList.remove('row-highlight-pulse'), 2500);
    }
}

function selectPaymentMethod(metode) {
    const mainBox = document.getElementById('payment_main_buttons');
    const subBox = document.getElementById('payment_sub_box');
    const titleElem = document.getElementById('selected_method_title');
    const hiddenMetode = document.getElementById('modal_payment_metode');

    if (mainBox) mainBox.classList.add('d-none');
    if (subBox) subBox.classList.remove('d-none');
    if (hiddenMetode) hiddenMetode.value = metode;

    if (titleElem) {
        titleElem.innerText = 'Metode Dipilih: ' + (metode === 'transfer' ? 'Transfer Bank' : 'Cash / Tunai');
    }
    showProofUploadForm();
}

function resetPaymentSelection() {
    const mainBox = document.getElementById('payment_main_buttons');
    const subBox = document.getElementById('payment_sub_box');
    const proofForm = document.getElementById('formModalUploadProof');

    if (mainBox) mainBox.classList.remove('d-none');
    if (subBox) subBox.classList.add('d-none');
    if (proofForm) proofForm.classList.add('d-none');
}

function showProofUploadForm() {
    const proofForm = document.getElementById('formModalUploadProof');
    if (proofForm) proofForm.classList.remove('d-none');
}

function processPaymentWithoutProof(id, csrfToken) {
    const metodeInput = document.getElementById('modal_payment_metode');
    const metode = metodeInput ? metodeInput.value : 'tunai';

    Swal.fire({
        title: 'Konfirmasi Pembayaran',
        text: `Apakah Anda yakin ingin memproses pembayaran (${metode.toUpperCase()}) TANPA bukti dokumen?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Proses Lunas',
        cancelButtonText: 'Batal'
    }).then((res) => {
        if (res.isConfirmed) {
            fetch('bayar_metode.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&metode=${metode}&csrf_token=${csrfToken}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal!', data.message, 'error');
                }
            });
        }
    });
}

function submitPaymentWithProof(event, id) {
    event.preventDefault();
    const form = document.getElementById('formModalUploadProof');
    const formData = new FormData(form);

    fetch('proses_modal_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('Gagal!', data.message, 'error');
        }
    });
}

function processDirectAction(action, id, csrfToken) {
    let confirmMsg = 'Apakah Anda yakin?';
    if (action === 'kirim') confirmMsg = 'Tandai transaksi ini SUDAH DIKIRIM?';
    if (action === 'batal_bayar') confirmMsg = 'Batalkan status pembayaran (set Belum Dibayar)?';
    if (action === 'batal_kirim') confirmMsg = 'Batalkan status pengiriman (set Pending)?';

    Swal.fire({
        title: 'Konfirmasi Aksi',
        text: confirmMsg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal'
    }).then((res) => {
        if (res.isConfirmed) {
            let endpoint = 'proses_modal_action.php';
            fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=${action}&id=${id}&csrf_token=${csrfToken}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal!', data.message, 'error');
                }
            });
        }
    });
}

function submitKwitansiModal(event, id) {
    event.preventDefault();
    const form = document.getElementById('formKwitansiModal');
    const formData = new FormData(form);

    fetch('proses_modal_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('Gagal!', data.message, 'error');
        }
    });
}
</script>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>