# 📘 DOKUMENTASI LENGKAP APLIKASI ADAM JAYA ENTERPRISE

Aplikasi **Adam Jaya Enterprise** adalah sistem manajemen pengadaan barang (procurement), pengelolaan stok inventaris multi-varian, pencatatan pengeluaran operasional, serta pelaporan keuangan berbasis web.

---

## 📄 DAFTAR ISI
1. [Konsep Utama & Ringkasan Sistem](#1-konsep-utama--ringkasan-sistem)
2. [Arsitektur & Teknologi](#2-arsitektur--teknologi)
3. [Peran Pengguna & Hak Akses (Role-Based Access Control)](#3-peran-pengguna--hak-akses-role-based-access-control)
4. [Modul-Modul Utama Aplikasi](#4-modul-modul-utama-aplikasi)
   - [4.1 Modul Pengadaan Barang & Pembelian](#41-modul-pengadaan-barang--pembelian)
   - [4.2 Modul Manajemen Stok & Inventaris](#42-modul-manajemen-stok--inventaris)
   - [4.3 Modul Pengeluaran Operasional](#43-modul-pengeluaran-operasional)
   - [4.4 Modul Pembayaran](#44-modul-pembayaran)
   - [4.5 Modul Laporan, Statistik & Cetak Struk](#45-modul-laporan-statistik--cetak-struk)
   - [4.6 Modul Manajemen User & Pembeli Favorit](#46-modul-manajemen-user--pembeli-favorit)
5. [Skema & Struktur Database](#5-skema--struktur-database)
6. [Alur Kerja Utama (Workflow & Control Flow)](#6-alur-kerja-utama-workflow--control-flow)
7. [Mekanisme Keamanan & Integritas Data](#7-mekanisme-keamanan--integritas-data)

---

## 1. KONSEP UTAMA & RINGKASAN SISTEM

Aplikasi ini dirancang untuk menyelesaikan kebutuhan operasional perusahaan dalam:

### 1.1 Pengadaan Barang (Pembelian)
- Pencatatan transaksi pembelian barang secara fleksibel
- Mendukung barang reguler (memiliki stok di database)
- Mendukung *custom item* (barang khusus/non-stok)

### 1.2 Manajemen Stok Varian (Master-Detail)
- Pengelompokan barang induk (`stok_barang`) yang memiliki banyak varian/spesifikasi (`jenis_barang`)
- Setiap varian memiliki harga standar, stok, dan satuan (unit, pcs, kg, meter, dll.)

### 1.3 Integrasi Stok Automatis (Atomic Transaction)
- Pengisian/pengeditan pengajuan pembelian secara otomatis menyesuaikan stok database
- Menggunakan *MySQL Transaction Lock* (`FOR UPDATE`) untuk menghindari *race condition* dan selisih stok

### 1.4 Pencatatan Keuangan & Cetak Struk
- Penayangan riwayat transaksi lengkap
- Cetak struk nota fisik/PDF
- Ekspor laporan keuangan ke Excel

---

## 2. ARSITEKTUR & TEKNOLOGI

| Komponen | Teknologi / Library yang Digunakan |
| :--- | :--- |
| **Language & Engine** | PHP 8.x (Native PHP dengan MySQLi Prepared Statements) |
| **Database Engine** | MySQL / MariaDB |
| **Frontend Framework** | HTML5, Modern Vanilla CSS (Design System Tokens, Glassmorphism, Responsive Grid) |
| **UI Components & Icons** | FontAwesome 6, Bootstrap 5 UI Components, SweetAlert2 |
| **Interaktivitas JS** | jQuery (Event Handling, Dynamic DOM Manipulation, Input Masking) |
| **Data Visualization** | Chart.js untuk grafik statistik & tren pengeluaran |

---

## 3. PERAN PENGGUNA & HAK AKSES (ROLE-BASED ACCESS CONTROL)

Sistem menggunakan dua peran utama pada tabel `users`:

```mermaid
graph TD
    User([Pengguna Login]) -->|Role Check| Auth{Cek Role}
    Auth -->|admin| AdminPage[Dashboard Admin - Manajemen Penuh]
    Auth -->|CEO| CEOPage[Dashboard CEO - Monitoring & Laporan]


3.1 Admin (Administrator)
Memiliki akses penuh terhadap seluruh operasional sistem:

Manajemen Transaksi:

Mengelola transaksi pembelian barang (tambah, edit, hapus)

Mengelola data pengeluaran operasional

Mengelola pembayaran dan status pengiriman

Manajemen Data Master:

Mengelola master stok barang (stok_barang.php)

Mengelola varian jenis barang (jenis_barang.php)

Mengelola akun pengguna (daftar_user.php)

Laporan:

Mengakses seluruh laporan dan statistik

3.2 CEO (Executive Management)
Memiliki akses untuk monitoring dan evaluasi:

Dashboard & Monitoring:

Akses executive dashboard (ceo.php)

Melihat ringkasan performa bisnis

Statistik tren pengeluaran

Audit persediaan barang

Laporan:

Laporan keuangan dan transaksi

Grafik dan visualisasi data

Catatan: CEO tidak memiliki akses untuk melakukan perubahan data operasional (read-only)

4. MODUL-MODUL UTAMA APLIKASI
4.1 Modul Pengadaan Barang & Pembelian
4.1.1 Halaman Utama Pengajuan (insert_admin.php)
Daftar semua pengajuan pembelian

Filter berdasarkan:

Bulan dan tahun

Status pembayaran

Status pengiriman

Fitur pencarian pintar berdasarkan ID atau nama pembeli

4.1.2 Form Pembuatan Pengajuan (tambah_pengajuan.php / proses_tambah_pengajuan.php)
Item Reguler:

Memilih barang dari database

Memilih spesifikasi jenis

Menghitung estimasi harga otomatis

Custom Item:

Memungkinkan memasukkan barang diluar database persediaan

Input nama, jenis, harga, dan jumlah custom

Custom Harga:

Memungkinkan pengguna mengganti harga satuan dari harga reguler

Untuk penyesuaian harga khusus

4.1.3 Form Edit Pengajuan (edit_pengajuan.php / proses_edit.php)
Arsitektur per-index input binding (barang_id_N, jenis_id_N, custom_nama_N, dll.)

Mencegah pergeseran indeks array POST

Penyesuaian stok atomis:

Mengembalikan stok barang lama ke database
Memvalidasi ketersediaan stok baru
Memotong stok sesuai jumlah baru secara transaksional
4.2 Modul Manajemen Stok & Inventaris
4.2.1 Master Barang (stok_barang.php & jenis_barang.php)
Pengelolaan master induk barang

Pengelolaan varian detail barang

Setiap varian memiliki:

Stok

Harga standar

Satuan (unit, pcs, kg, meter, roll, set, dll.)

4.2.2 Riwayat Stok (riwayat_stok.php)
Logging audit otomatis

Mencatat setiap perubahan jumlah stok:

Penambahan stok

Pengeditan stok

Penghapusan stok

Transaksi pembelian

4.3 Modul Pengeluaran Operasional
4.3.1 Manajemen Pengeluaran (pengeluaran.php / tambah_pengeluaran.php / edit_pengeluaran.php)
Pencatatan kas keluar operasional perusahaan

Mencakup biaya di luar pembelian barang stok:

Biaya listrik

Sewa

Gaji

Konsumsi

Pemeliharaan

4.3.2 Struktur Data
Menerapkan struktur Header-Detail

pengeluaran_header untuk data utama

pengeluaran_detail untuk rincian item

Mendukung beberapa rincian dalam 1 tanggal transaksi

4.4 Modul Pembayaran
4.4.1 Upload Bukti Pembayaran
upload_bukti_transfer.php: Upload bukti transfer bank

upload_bukti_tunai.php: Upload bukti pembayaran tunai

Manajemen dokumen digital terlampir pada setiap nota pengajuan

4.4.2 Manajemen Status Pembayaran
bayar_metode.php: Update status pembayaran

batal_bayar.php: Pembatalan status pembayaran

Status pembayaran: belum_dibayar ↔ dibayar

Status pengiriman: belum_dikirim ↔ sudah_dikirim

4.5 Modul Laporan, Statistik & Cetak Struk
4.5.1 Detail Pengajuan (get_pengajuan_detail.php)
Modal popup dinamis

Penayangan rincian barang yang dibeli

Tampilan responsif desktop dan mobile

4.5.2 Cetak Struk (get_struk_json.php)
Generator JSON data nota/struk

Untuk pencetakan nota pembelian fisik

4.5.3 Statistik & Visualisasi
statistik.php: Visualisasi data pengeluaran

traffic_penjualan.php: Grafik tren pembelian tahunan/bulanan

4.5.4 Ekspor Data (export_excel.php)
Fitur ekspor laporan pengajuan

Ekspor transaksi ke format spreadsheet (.xlsx/.csv)

4.6 Modul Manajemen User & Pembeli Favorit
4.6.1 Manajemen User (daftar_user.php / tambah_user.php)
Manajemen akun pengguna sistem

Penetapan hak akses (Admin atau CEO)

4.6.2 Pembeli Favorit (favorit_pembeli)
Penyiapan daftar kontak pelanggan/pembeli favorit

Mempermudah pengisian otomatis pada form pengajuan

5. SKEMA & STRUKTUR DATABASE
Aplikasi ini menggunakan database relational MySQL dengan skema sebagai berikut:

erDiagram
    users ||--o{ pengajuan : "mengajukan"
    users ||--o{ riwayat_stok : "mencatat_stok"
    users ||--o{ favorit_pembeli : "memiliki_favorit"
    
    stok_barang ||--|{ jenis_barang : "memiliki_varian"
    jenis_barang ||--o{ pengajuan_detail : "dirujuk_dalam"
    jenis_barang ||--o{ riwayat_stok : "mencatat_riwayat"
    
    pengajuan ||--|{ pengajuan_detail : "berisi_item"
    
    pengeluaran_header ||--|{ pengeluaran_detail : "rincian_pengeluaran"

5.1 Rincian Tabel-Tabel Utama
5.1.1 Tabel users
   Kolom	Tipe	Keterangan
   id	INT (PK)	ID unik pengguna
   username	VARCHAR	Nama pengguna
   password	VARCHAR	Password terenkripsi
   role	ENUM	'admin', 'CEO'
   no_telepon	VARCHAR	Nomor telepon
   email	VARCHAR	Alamat email
   lokasi	VARCHAR	Lokasi/alamat
5.1.2 Tabel stok_barang (Induk Barang)
   Kolom	Tipe	Keterangan
   id	INT (PK)	ID unik barang
   nama_barang	VARCHAR	Nama barang
   jumlah	INT	Total jumlah
   gambar	VARCHAR	Path gambar
5.1.3 Tabel jenis_barang (Varian & Stok Detail)
   Kolom	Tipe	Keterangan
   id	INT (PK)	ID unik varian
   barang_id	INT (FK)	Referensi ke stok_barang.id
   nama_jenis	VARCHAR	Nama jenis/spesifikasi
   stok	DECIMAL(10,2)	Jumlah stok
   satuan	VARCHAR	Satuan (unit, pcs, kg, meter)
   harga	DECIMAL(15,2)	Harga satuan
5.1.4 Tabel pengajuan (Header Pembelian)
   Kolom	Tipe	Keterangan
   id	INT (PK)	ID unik pengajuan
   custom_id	VARCHAR (Unique)	ID Nota Publik
   user_id	INT (FK)	Referensi ke users.id
   jenis_pengajuan	VARCHAR	'stok' / 'non_stok'
   status_pembayaran	ENUM	'belum_dibayar', 'dibayar'
   status_pengiriman	ENUM	'belum_dikirim', 'sudah_dikirim'
   bukti_transfer	VARCHAR	Path bukti transfer
   bukti_tunai	VARCHAR	Path bukti tunai
   bukti_pembelian	VARCHAR	Path bukti pembelian
   estimasi_dana	VARCHAR	Estimasi total dana
   nama_pembeli	VARCHAR	Nama pembeli
   telepon_pembeli	VARCHAR	Telepon pembeli
5.1.5 Tabel pengajuan_detail (Item Pembelian)
   Kolom	Tipe	Keterangan
   id	INT (PK)	ID unik detail
   pengajuan_id	INT (FK)	Referensi ke pengajuan.id
   is_custom	TINYINT	1 custom, 0 reguler
   jenis_id	INT (FK, Nullable)	Referensi ke jenis_barang.id
   nama_barang	VARCHAR	Nama barang
   nama_jenis	VARCHAR	Nama jenis
   jumlah	DECIMAL(10,2)	Jumlah dibeli
   satuan	VARCHAR	Satuan
   harga_satuan	DECIMAL(15,2)	Harga per satuan
5.1.6 Tabel pengeluaran_header & pengeluaran_detail
   Pencatatan transaksi pengeluaran operasional

   Per kategori dan nominal

   Struktur header-detail untuk multiple items

5.1.7 Tabel riwayat_stok
   Kolom	Tipe	Keterangan
   id	INT (PK)	ID unik riwayat
   jenis_id	INT (FK)	Referensi ke jenis_barang.id
   user_id	INT (FK)	Referensi ke users.id
   perubahan	DECIMAL(10,2)	Nilai perubahan
   stok_sebelum	DECIMAL(10,2)	Stok sebelum perubahan
   stok_sesudah	DECIMAL(10,2)	Stok setelah perubahan
   aksi	VARCHAR	'tambah', 'edit', 'hapus'
   keterangan	TEXT	Keterangan perubahan
   tanggal	DATETIME	Waktu perubahan
6. ALUR KERJA UTAMA (WORKFLOW & CONTROL FLOW)
6.1 Alur Proses Pengadaan Barang (Pembelian)
sequenceDiagram
    autonumber
    actor Admin as Admin
    participant Form as Form Pembelian
    participant Proc as Processor
    participant DB as Database MySQL
    
    Admin->>Form: Isi Data Barang, Varian, Jumlah & Harga
    Form->>Proc: Submit Data POST dengan Indexed Inputs (_N)
    Note over Proc: Validasi Input & CSRF Token
    Proc->>DB: Lock Table (`jenis_barang FOR UPDATE`)
    Note over Proc: Hitung Selisih Stok (Net Change)
    Proc->>DB: Revert Stok Lama & Potong Stok Baru
    Proc->>DB: Update Header `pengajuan` & Insert `pengajuan_detail`
    Proc->>DB: Commit Transaction
    Proc->>DB: Insert `riwayat_stok`
    Proc-->>Admin: Redirect dengan Toast Notification
6.2 Alur Manajemen Stok
sequenceDiagram
    autonumber
    actor Admin as Admin
    participant Form as Form Stok
    participant DB as Database MySQL
    
    Admin->>Form: Tambah/Edit Stok Barang
    Form->>DB: Proses Update Stok
    DB->>DB: Simpan Perubahan
    DB->>DB: Catat di `riwayat_stok`
    DB-->>Admin: Tampilkan Notifikasi Sukses
6.3 Alur Pembayaran
sequenceDiagram
    autonumber
    actor Admin as Admin
    participant Form as Form Pembayaran
    participant DB as Database MySQL
    
    Admin->>Form: Upload Bukti Pembayaran
    Form->>DB: Simpan Bukti & Update Status
    DB->>DB: Update `status_pembayaran` = 'dibayar'
    DB->>DB: Update `status_pengiriman` = 'sudah_dikirim'
    DB-->>Admin: Tampilkan Notifikasi Sukses

7.1 Atomic Transaction & Locking Table
Proses pembaruan stok menggunakan mysqli_autocommit($conn, FALSE)

Query SELECT ... FOR UPDATE untuk lock record

Menjamin stok tidak korup jika ada transaksi bersamaan

Mencegah race condition dan deadlock

7.2 Per-Index Input Binding (_N Key Strategy)
Seluruh input form diikat dengan nomor indeks eksplisit

Format: barang_id_0, jenis_id_0, barang_id_1, jenis_id_1

Mencegah masalah pergeseran indeks array POST ([] index shifting)

Menghindari duplikasi data pada aplikasi web native

7.3 Perlindungan CSRF & Sanitasi Input
Setiap perubahan data dilindungi token CSRF

Token unik per session untuk setiap form

Validasi token sebelum proses data

Fungsi Sanitasi:

htmlspecialchars() untuk mencegah XSS

unformatRupiah() untuk membersihkan format mata uang

parseJumlah() untuk validasi angka

Prepared Statements untuk mencegah SQL Injection

7.4 Konsistensi Satuan (Unit Prioritization)
Pengambilan data satuan memprioritaskan kolom pd.satuan

Satuan tersimpan di database selalu konsisten

Mendukung satuan kustom (meter, kg, pcs)

Konsisten di seluruh laporan dan cetakan

7.5 Validasi Data
Validasi sisi client dengan JavaScript

Validasi sisi server dengan PHP

Validasi tipe data dan format

Validasi range nilai (min/max)

Validasi ketersediaan stok sebelum transaksi

7.6 Audit Trail
Semua perubahan stok tercatat di riwayat_stok

Mencatat user, waktu, dan jenis perubahan

Memudahkan tracking dan troubleshooting

Mendukung kepatuhan audit

8. PANDUAN INSTALASI & KONFIGURASI
8.1 Persyaratan Sistem
PHP 8.x atau lebih tinggi

MySQL 5.7 atau MariaDB 10.x

Web Server (Apache/Nginx)

Ekstensi PHP: mysqli, gd, zip, mbstring

8.2 Langkah Instalasi
Clone repository ke direktori web server

Import database dari file database.sql

Konfigurasi koneksi database di config/database.php

Set permission folder uploads/ (777 untuk writable)

Akses aplikasi melalui browser

8.3 Konfigurasi Database
php
define('DB_HOST', 'localhost');
define('DB_USER', 'username');
define('DB_PASS', 'password');
define('DB_NAME', 'adamjaya_db');
9. TROUBLESHOOTING & PEMELIHARAAN
9.1 Masalah Umum
Stok Tidak Update:

Periksa koneksi database

Cek apakah transaction commit berhasil

Verifikasi query FOR UPDATE berjalan

Upload Bukti Gagal:

Periksa permission folder uploads/

Cek ukuran file (maks 2MB)

Verifikasi ekstensi file yang diizinkan

Laporan Tidak Muncul:

Periksa filter tanggal

Cek koneksi database

Verifikasi query laporan

9.2 Backup Database
Backup otomatis setiap hari via cron

Simpan backup di lokasi aman

Restore via phpMyAdmin atau command line

9.3 Monitoring Performa
Pantau query lambat di MySQL

Optimasi indeks database

Cache untuk data statistik
















