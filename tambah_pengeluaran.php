<?php
require_once __DIR__ . '/includes/header.php';
require_admin();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1 text-dark">Pencatatan Pengeluaran Kas Baru</h3>
        <p class="text-muted mb-0">Catat transaksi pengeluaran operasional perusahaan (Multi-Item)</p>
    </div>
    <a href="pengeluaran.php" class="btn btn-secondary-custom">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar
    </a>
</div>

<form action="proses_tambah_pengeluaran.php" method="POST" id="formPengeluaran">
    <?= csrf_field(); ?>

    <!-- Header Transaksi -->
    <div class="glass-card p-4 mb-4">
        <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-calendar-day text-warning me-2"></i> Header Transaksi Pengeluaran</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label text-muted small fw-semibold">TANGGAL TRANSAKSI *</label>
                <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d'); ?>" required>
            </div>
            <div class="col-md-8">
                <label class="form-label text-muted small fw-semibold">KETERANGAN / CATATAN UTAMA</label>
                <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Tagihan Operasional Kantor Bulanan">
            </div>
        </div>
    </div>

    <!-- Items Detail -->
    <div class="glass-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list-check text-warning me-2"></i> Rincian Item Pengeluaran</h5>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="addExpenseRow()">
                <i class="fa-solid fa-plus me-1"></i> Tambah Item
            </button>
        </div>

        <div id="expenseContainer">
            <div class="expense-row p-3 rounded bg-light border mb-3" data-index="0" id="exp_row_0">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label text-muted small fw-semibold">NAMA ITEM PENGELUARAN *</label>
                        <input type="text" name="nama_item_0" class="form-control" placeholder="Contoh: Tagihan PLN Juli" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small fw-semibold">KATEGORI</label>
                        <select name="kategori_0" class="form-select">
                            <option value="Operasional">Operasional</option>
                            <option value="Utility">Utility (Listrik/Air/Internet)</option>
                            <option value="Gaji & Bonus">Gaji & Bonus</option>
                            <option value="Sewa & Fasilitas">Sewa & Fasilitas</option>
                            <option value="Pemeliharaan">Pemeliharaan & Service</option>
                            <option value="Konsumsi & Logistik">Konsumsi & Logistik</option>
                            <option value="Lain-lain">Lain-lain</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted small fw-semibold">JUMLAH (QTY)</label>
                        <input type="number" step="0.01" name="jumlah_0" id="exp_jumlah_0" class="form-control" value="1.00" oninput="calculateExpRow(0)" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted small fw-semibold">SATUAN</label>
                        <input type="text" name="satuan_0" class="form-control" value="unit" required>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-semibold">HARGA SATUAN (RP)</label>
                        <input type="text" name="harga_satuan_0" id="exp_harga_0" class="form-control rupiah-input" placeholder="Rp 0" oninput="calculateExpRow(0)" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-semibold">TOTAL HARGA ITEM</label>
                        <input type="text" id="exp_subtotal_0" class="form-control fw-bold text-danger bg-white" value="Rp 0" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-4">
            <h5 class="fw-bold text-dark mb-0">TOTAL PENGELUARAN KAS:</h5>
            <h4 class="fw-bold text-danger mb-0" id="expGrandTotalDisplay">Rp 0</h4>
        </div>
    </div>

    <div class="text-end mb-5">
        <button type="submit" class="btn btn-primary-custom btn-lg px-5">
            <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Pengeluaran Kas
        </button>
    </div>
</form>

<script>
let expRowIndex = 1;

function calculateExpRow(idx) {
    const qty = parseFloat(document.getElementById(`exp_jumlah_${idx}`).value) || 0;
    const hargaStr = document.getElementById(`exp_harga_${idx}`).value;
    const harga = unformatRupiahJS(hargaStr);
    const total = qty * harga;

    document.getElementById(`exp_subtotal_${idx}`).value = formatRupiahJS(total, 'Rp ');
    calculateExpGrandTotal();
}

function calculateExpGrandTotal() {
    let grandTotal = 0;
    document.querySelectorAll('.expense-row').forEach(row => {
        const idx = row.getAttribute('data-index');
        const qty = parseFloat(document.getElementById(`exp_jumlah_${idx}`).value) || 0;
        const harga = unformatRupiahJS(document.getElementById(`exp_harga_${idx}`).value);
        grandTotal += (qty * harga);
    });
    document.getElementById('expGrandTotalDisplay').innerText = formatRupiahJS(grandTotal, 'Rp ');
}

function addExpenseRow() {
    const idx = expRowIndex++;
    const container = document.getElementById('expenseContainer');

    const html = `
    <div class="expense-row p-3 rounded bg-light border mb-3" data-index="${idx}" id="exp_row_${idx}">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label text-muted small fw-semibold">NAMA ITEM PENGELUARAN *</label>
                <input type="text" name="nama_item_${idx}" class="form-control" placeholder="Nama item..." required>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small fw-semibold">KATEGORI</label>
                <select name="kategori_${idx}" class="form-select">
                    <option value="Operasional">Operasional</option>
                    <option value="Utility">Utility (Listrik/Air/Internet)</option>
                    <option value="Gaji & Bonus">Gaji & Bonus</option>
                    <option value="Sewa & Fasilitas">Sewa & Fasilitas</option>
                    <option value="Pemeliharaan">Pemeliharaan & Service</option>
                    <option value="Konsumsi & Logistik">Konsumsi & Logistik</option>
                    <option value="Lain-lain">Lain-lain</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small fw-semibold">JUMLAH (QTY)</label>
                <input type="number" step="0.01" name="jumlah_${idx}" id="exp_jumlah_${idx}" class="form-control" value="1.00" oninput="calculateExpRow(${idx})" required>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small fw-semibold">SATUAN</label>
                <input type="text" name="satuan_${idx}" class="form-control" value="unit" required>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <label class="form-label text-muted small fw-semibold">HARGA SATUAN (RP)</label>
                <input type="text" name="harga_satuan_${idx}" id="exp_harga_${idx}" class="form-control rupiah-input" placeholder="Rp 0" oninput="calculateExpRow(${idx})" required>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small fw-semibold">TOTAL HARGA ITEM</label>
                <input type="text" id="exp_subtotal_${idx}" class="form-control fw-bold text-danger bg-white" value="Rp 0" readonly>
            </div>
        </div>

        <div class="text-end mt-2">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeExpRow(${idx})">
                <i class="fa-solid fa-trash me-1"></i> Hapus Item Ini
            </button>
        </div>
    </div>`;

    container.insertAdjacentHTML('beforeend', html);
    initRupiahMasking();
}

function removeExpRow(idx) {
    const elem = document.getElementById(`exp_row_${idx}`);
    if (elem) {
        elem.remove();
        calculateExpGrandTotal();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
