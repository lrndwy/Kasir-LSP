<?php
session_start();
require_once '../config/database.php';
require_once '../includes/alert.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'kasir') {
    header("Location: /kasir_restoran/");
    exit;
}
include '../includes/header.php';

// Ambil data transaksi default (7 hari terakhir)
$stmt = $pdo->prepare("
    SELECT t.*, o.table_id, tb.table_number, 
           u_waiter.username as waiter_name,
           o.waiter_id
    FROM transactions t
    JOIN orders o ON t.order_id = o.id
    JOIN tables tb ON o.table_id = tb.id
    LEFT JOIN users u_waiter ON o.waiter_id = u_waiter.id
    WHERE t.kasir_id = ? 
    AND DATE(t.transaction_time) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    ORDER BY t.transaction_time DESC
");
$stmt->execute([$_SESSION['user_id']]);
$transactions = $stmt->fetchAll();

// Hitung summary untuk hari ini
$summary = [
    'total_transactions' => count($transactions),
    'total_income' => array_sum(array_column($transactions, 'total')),
    'average_transaction' => count($transactions) > 0 ? 
        array_sum(array_column($transactions, 'total')) / count($transactions) : 0
];
?>

<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="container-fluid py-4">
        <div id="alertPlaceholder" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 90%; width: fit-content;"></div>
        <h2>Riwayat Transaksi</h2>
        
        <!-- Filter Transaksi -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo date('Y-m-d', strtotime('-6 days')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="end_date" name="end_date"
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Metode Pembayaran</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <option value="">Semua</option>
                            <option value="cash">Tunai</option>
                            <option value="debit">Debit</option>
                            <option value="credit">Kartu Kredit</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-primary d-block" onclick="filterTransactions()">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ringkasan Transaksi -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total Transaksi</h5>
                        <h3 id="totalTransactions"><?php echo $summary['total_transactions']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Total Pendapatan</h5>
                        <h3 id="totalIncome">Rp <?php echo number_format($summary['total_income'], 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5>Rata-rata Transaksi</h5>
                        <h3 id="averageTransaction">Rp <?php echo number_format($summary['average_transaction'], 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Transaksi -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="transactionsTable">
                        <thead>
                            <tr>
                                <th>ID Transaksi</th>
                                <th>No. Meja</th>
                                <th>Pelayan</th>
                                <th>Total</th>
                                <th>Metode Pembayaran</th>
                                <th>Waktu Transaksi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsBody">
                            <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo $t['id']; ?></td>
                                <td><?php echo $t['table_number']; ?></td>
                                <td><?php echo $t['waiter_name']; ?></td>
                                <td>Rp <?php echo number_format($t['total'], 0, ',', '.'); ?></td>
                                <td><?php echo strtoupper($t['payment_method']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($t['transaction_time'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewTransaction(<?php echo $t['id']; ?>)">
                                        Detail
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="printReceipt(<?php echo $t['id']; ?>)">
                                        Cetak
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada transaksi</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Transaksi -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="transactionDetails">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="printReceipt()">Cetak Struk</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
let currentTransactionId = null;

function filterTransactions() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const paymentMethod = document.getElementById('payment_method').value;

    fetch(`/kasir_restoran/api/transactions/get_filtered.php?start_date=${startDate}&end_date=${endDate}&payment_method=${paymentMethod}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTransactionTable(data.data);
                updateSummary(data.summary);
                showAlert('success', 'Data transaksi berhasil diperbarui');
            } else {
                showAlert('danger', data.message || 'Gagal memuat data transaksi');
            }
        })
        .catch(error => {
            showAlert('danger', 'Terjadi kesalahan saat memuat data transaksi');
        });
}

function updateTransactionTable(transactions) {
    const tbody = document.getElementById('transactionsBody');
    tbody.innerHTML = '';

    transactions.forEach(t => {
        tbody.innerHTML += `
            <tr>
                <td>${t.id}</td>
                <td>${t.table_number}</td>
                <td>${t.waiter_name}</td>
                <td>Rp ${parseInt(t.total).toLocaleString('id-ID')}</td>
                <td>${t.payment_method.toUpperCase()}</td>
                <td>${new Date(t.transaction_time).toLocaleString('id-ID')}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="viewTransaction(${t.id})">
                        Detail
                    </button>
                </td>
            </tr>
        `;
    });

    if (transactions.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center">Tidak ada transaksi</td>
            </tr>
        `;
    }
}

function updateSummary(summary) {
    document.getElementById('totalTransactions').textContent = summary.total_transactions;
    document.getElementById('totalIncome').textContent = `Rp ${parseInt(summary.total_income).toLocaleString('id-ID')}`;
    document.getElementById('averageTransaction').textContent = `Rp ${parseInt(summary.average_transaction).toLocaleString('id-ID')}`;
}

function viewTransaction(transactionId) {
    currentTransactionId = transactionId;
    fetch(`/kasir_restoran/api/transactions/get_transaction.php?id=${transactionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTransactionDetails(data.data);
                new bootstrap.Modal(document.getElementById('transactionModal')).show();
            } else {
                showAlert('danger', data.message || 'Gagal memuat detail transaksi');
            }
        })
        .catch(error => {
            showAlert('danger', 'Terjadi kesalahan saat memuat detail transaksi');
        });
}

function displayTransactionDetails(transaction) {
    let html = `
        <div class="mb-3">
            <h6>Informasi Transaksi</h6>
            <table class="table table-sm">
                <tr>
                    <td>ID Transaksi</td>
                    <td>: ${transaction.id}</td>
                </tr>
                <tr>
                    <td>No. Meja</td>
                    <td>: ${transaction.table_number}</td>
                </tr>
                <tr>
                    <td>Pelayan</td>
                    <td>: ${transaction.waiter_name}</td>
                </tr>
                <tr>
                    <td>Waktu Transaksi</td>
                    <td>: ${new Date(transaction.transaction_time).toLocaleString('id-ID')}</td>
                </tr>
                <tr>
                    <td>Metode Pembayaran</td>
                    <td>: ${transaction.payment_method.toUpperCase()}</td>
                </tr>
            </table>
        </div>

        <div class="mb-3">
            <h6>Detail Pesanan</h6>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Menu</th>
                        <th>Qty</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
    `;

    transaction.items.forEach(item => {
        html += `
            <tr>
                <td>${item.menu_name}</td>
                <td>${item.quantity}</td>
                <td>Rp ${parseInt(item.price).toLocaleString('id-ID')}</td>
                <td>Rp ${(item.quantity * item.price).toLocaleString('id-ID')}</td>
            </tr>
        `;
    });

    html += `
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">Total</th>
                        <th>Rp ${parseInt(transaction.total).toLocaleString('id-ID')}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;

    document.getElementById('transactionDetails').innerHTML = html;
}

function printReceipt(transactionId) {
    if (transactionId) {
        try {
            window.open(`/kasir_restoran/print_receipt.php?id=${transactionId}`, '_blank');
        } catch (error) {
            showAlert('danger', 'Terjadi kesalahan saat mencetak struk');
        }
    } else {
        showAlert('warning', 'ID transaksi tidak valid');
    }
}

// Load transaksi hari ini saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    filterTransactions();
});
</script>
