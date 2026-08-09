-- ========================================================
-- DATABASE SCHEMA: ADAM JAYA ENTERPRISE
-- ========================================================

CREATE DATABASE IF NOT EXISTS `adamjaya_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `adamjaya_db`;

-- 1. TABEL USERS (Pengguna System)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'CEO') NOT NULL DEFAULT 'admin',
  `no_telepon` VARCHAR(20) NULL,
  `email` VARCHAR(100) NULL,
  `lokasi` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Users Default
-- admin / admin123
-- ceo / ceo123
INSERT INTO `users` (`id`, `username`, `password`, `role`, `no_telepon`, `email`, `lokasi`) VALUES
(1, 'admin', '$2y$10$8HNn8ZkBdWfj4WXKyR6Fk.wqC7WOk6xNPnJcSVKFb.BRHm4miUDq2', 'admin', '081234567890', 'admin@adamjaya.com', 'Surakarta, Jawa Tengah'),
(2, 'ceo', '$2y$10$KrVI/RAKo402UoIKLbZU2uiAL9egTwHeG0bvUbFYrxygNPB0rfAPS', 'CEO', '089876543210', 'ceo@adamjaya.com', 'Surakarta, Jawa Tengah')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- 2. TABEL STOK_BARANG (Induk Barang)
CREATE TABLE IF NOT EXISTS `stok_barang` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_barang` VARCHAR(150) NOT NULL,
  `jumlah` INT DEFAULT 0,
  `gambar` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Master Barang Induk
INSERT INTO `stok_barang` (`id`, `nama_barang`, `jumlah`, `gambar`) VALUES
(1, 'Besi Beton Polos', 250, NULL),
(2, 'Semen Gresik 40kg', 180, NULL),
(3, 'Cat Tembok Dulux', 60, NULL)
ON DUPLICATE KEY UPDATE `nama_barang`=`nama_barang`;

-- 3. TABEL JENIS_BARANG (Varian & Stok Detail)
CREATE TABLE IF NOT EXISTS `jenis_barang` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `barang_id` INT NOT NULL,
  `nama_jenis` VARCHAR(150) NOT NULL,
  `stok` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `satuan` VARCHAR(50) NOT NULL DEFAULT 'pcs',
  `harga` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`barang_id`) REFERENCES `stok_barang`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Detail Varian Barang
INSERT INTO `jenis_barang` (`id`, `barang_id`, `nama_jenis`, `stok`, `satuan`, `harga`) VALUES
(1, 1, 'Besi Polos 8mm (12m)', 100.00, 'batang', 55000.00),
(2, 1, 'Besi Polos 10mm (12m)', 150.00, 'batang', 85000.00),
(3, 2, 'Semen PCC 40kg', 180.00, 'sak', 62000.00),
(4, 3, 'Dulux White Super 5kg', 35.00, 'galon', 145000.00),
(5, 3, 'Dulux Grey Super 5kg', 25.00, 'galon', 145000.00)
ON DUPLICATE KEY UPDATE `nama_jenis`=`nama_jenis`;

-- 4. TABEL PENGAJUAN (Header Pembelian / Transaksi Pengadaan)
CREATE TABLE IF NOT EXISTS `pengajuan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `custom_id` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `jenis_pengajuan` VARCHAR(50) NOT NULL DEFAULT 'stok',
  `status_pembayaran` ENUM('belum_dibayar', 'dibayar') NOT NULL DEFAULT 'belum_dibayar',
  `status_pengiriman` ENUM('belum_dikirim', 'sudah_dikirim') NOT NULL DEFAULT 'belum_dikirim',
  `bukti_transfer` VARCHAR(255) NULL,
  `bukti_tunai` VARCHAR(255) NULL,
  `bukti_pembelian` VARCHAR(255) NULL,
  `estimasi_dana` VARCHAR(100) NULL,
  `nama_pembeli` VARCHAR(150) NULL,
  `telepon_pembeli` VARCHAR(50) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. TABEL PENGAJUAN_DETAIL (Item Pembelian)
CREATE TABLE IF NOT EXISTS `pengajuan_detail` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pengajuan_id` INT NOT NULL,
  `is_custom` TINYINT(1) NOT NULL DEFAULT 0,
  `jenis_id` INT NULL,
  `nama_barang` VARCHAR(150) NOT NULL,
  `nama_jenis` VARCHAR(150) NULL,
  `jumlah` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `satuan` VARCHAR(50) NOT NULL DEFAULT 'pcs',
  `harga_satuan` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (`pengajuan_id`) REFERENCES `pengajuan`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`jenis_id`) REFERENCES `jenis_barang`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. TABEL PENGELUARAN_HEADER & PENGELUARAN_DETAIL (Operasional Company)
CREATE TABLE IF NOT EXISTS `pengeluaran_header` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `custom_id` VARCHAR(50) NOT NULL UNIQUE,
  `tanggal` DATE NOT NULL,
  `total_pengeluaran` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `keterangan` TEXT NULL,
  `user_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pengeluaran_detail` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pengeluaran_id` INT NOT NULL,
  `nama_item` VARCHAR(150) NOT NULL,
  `kategori` VARCHAR(100) NOT NULL DEFAULT 'Operasional',
  `jumlah` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `satuan` VARCHAR(50) NOT NULL DEFAULT 'unit',
  `harga_satuan` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_harga` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (`pengeluaran_id`) REFERENCES `pengeluaran_header`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. TABEL RIWAYAT_STOK (Audit Log Logistik)
CREATE TABLE IF NOT EXISTS `riwayat_stok` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `jenis_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `perubahan` DECIMAL(10,2) NOT NULL,
  `stok_sebelum` DECIMAL(10,2) NOT NULL,
  `stok_sesudah` DECIMAL(10,2) NOT NULL,
  `aksi` VARCHAR(50) NOT NULL,
  `keterangan` TEXT NULL,
  `tanggal` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`jenis_id`) REFERENCES `jenis_barang`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. TABEL FAVORIT_PEMBELI (Kontak Pelanggan)
CREATE TABLE IF NOT EXISTS `favorit_pembeli` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `nama_pembeli` VARCHAR(150) NOT NULL,
  `telepon_pembeli` VARCHAR(50) NULL,
  `alamat_pembeli` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Sample Data Pengajuan Awal
INSERT INTO `pengajuan` (`id`, `custom_id`, `user_id`, `jenis_pengajuan`, `status_pembayaran`, `status_pengiriman`, `estimasi_dana`, `nama_pembeli`, `telepon_pembeli`, `created_at`) VALUES
(1, 'AJ-20260801-001', 1, 'stok', 'dibayar', 'sudah_dikirim', '1700000.00', 'PT Mitra Perkasa', '081122334455', '2026-08-01 10:30:00'),
(2, 'AJ-20260805-002', 1, 'stok', 'belum_dibayar', 'belum_dikirim', '725000.00', 'CV Bangun Jaya', '085678901234', '2026-08-05 14:15:00')
ON DUPLICATE KEY UPDATE `custom_id`=`custom_id`;

INSERT INTO `pengajuan_detail` (`id`, `pengajuan_id`, `is_custom`, `jenis_id`, `nama_barang`, `nama_jenis`, `jumlah`, `satuan`, `harga_satuan`) VALUES
(1, 1, 0, 1, 'Besi Beton Polos', 'Besi Polos 8mm (12m)', 10.00, 'batang', 55000.00),
(2, 1, 0, 2, 'Besi Beton Polos', 'Besi Polos 10mm (12m)', 10.00, 'batang', 85000.00),
(3, 1, 0, 3, 'Semen Gresik 40kg', 'Semen PCC 40kg', 5.00, 'sak', 60000.00),
(4, 2, 0, 4, 'Cat Tembok Dulux', 'Dulux White Super 5kg', 5.00, 'galon', 145000.00)
ON DUPLICATE KEY UPDATE `nama_barang`=`nama_barang`;

-- Seed Sample Pengeluaran
INSERT INTO `pengeluaran_header` (`id`, `custom_id`, `tanggal`, `total_pengeluaran`, `keterangan`, `user_id`, `created_at`) VALUES
(1, 'EXP-20260802-001', '2026-08-02', '1250000.00', 'Tagihan Listrik & Internet Kantor', 1, '2026-08-02 09:00:00')
ON DUPLICATE KEY UPDATE `custom_id`=`custom_id`;

INSERT INTO `pengeluaran_detail` (`id`, `pengeluaran_id`, `nama_item`, `kategori`, `jumlah`, `satuan`, `harga_satuan`, `total_harga`) VALUES
(1, 1, 'Listrik PLN Bulan Juli', 'Utility', 1.00, 'bulan', 850000.00, 850000.00),
(2, 1, 'Internet Biznet 100Mbps', 'Utility', 1.00, 'bulan', 400000.00, 400000.00)
ON DUPLICATE KEY UPDATE `nama_item`=`nama_item`;

-- Seed Sample Pembeli Favorit
INSERT INTO `favorit_pembeli` (`id`, `user_id`, `nama_pembeli`, `telepon_pembeli`, `alamat_pembeli`) VALUES
(1, 1, 'PT Mitra Perkasa', '081122334455', 'Jl. Slamet Riyadi No. 120, Surakarta'),
(2, 1, 'CV Bangun Jaya', '085678901234', 'Jl. Ir. Juanda No. 45, Jebres, Surakarta')
ON DUPLICATE KEY UPDATE `nama_pembeli`=`nama_pembeli`;
