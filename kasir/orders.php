<?php
session_start();
require_once '../config/database.php';
require_once '../includes/alert.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'kasir') {
    header("Location: /kasir_restoran/");
    exit;
}
include '../includes/header.php';
?>

<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="container-fluid py-4">
        <div id="alertPlaceholder" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 90%; width: fit-content;"></div>
        <h2>Daftar Pesanan</h2>
        
        <div class="card mt-4">
            <div class="card-body">
                <table class="table table-striped" id="ordersTable">
                    <thead>
                        <tr>
                            <th>ID Pesanan</th>
                            <th>Nomor Meja</th>
                            <th>Pelayan</th>
                            <th>Waktu Pesan</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT o.*, t.table_number, u.username as waiter_name,
                                 (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id) as total
                                 FROM orders o 
                                 JOIN tables t ON o.table_id = t.id 
                                 LEFT JOIN users u ON o.waiter_id = u.id
                                 WHERE o.status = 'pending'
                                 ORDER BY o.order_time DESC";
                        $stmt = $pdo->query($query);
                        while ($row = $stmt->fetch()) {
                            echo "<tr>";
                            echo "<td>{$row['id']}</td>";
                            echo "<td>{$row['table_number']}</td>";
                            echo "<td>{$row['waiter_name']}</td>";
                            echo "<td>" . date('d/m/Y H:i', strtotime($row['order_time'])) . "</td>";
                            echo "<td>Rp " . number_format($row['total'], 0, ',', '.') . "</td>";
                            echo "<td><span class='badge bg-warning'>Pending</span></td>";
                            echo "<td>
                                    <button class='btn btn-sm btn-primary' onclick='processPayment({$row['id']}, {$row['total']})'>
                                        Proses Pembayaran
                                    </button>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pembayaran -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Proses Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <input type="hidden" id="order_id" name="order_id">
                    <input type="hidden" id="total_amount" name="total_amount">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Detail Pesanan</h6>
                            <div id="orderDetails"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Total Pembayaran</label>
                                <input type="text" class="form-control" id="display_total" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Metode Pembayaran</label>
                                <select class="form-select" name="payment_method" id="payment_method" required>
                                    <option value="cash">Tunai</option>
                                    <option value="debit">Debit</option>
                                    <option value="credit">Kartu Kredit</option>
                                </select>
                            </div>
                            <div class="mb-3" id="cashPaymentSection">
                                <label class="form-label">Jumlah Uang</label>
                                <input type="number" class="form-control" name="cash_amount" id="cash_amount">
                                <div class="mt-2">
                                    <span>Kembalian: </span>
                                    <span id="changeAmount">Rp 0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitPayment()">Proses Pembayaran</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
let currentTotal = 0;

function processPayment(orderId, total) {
    currentTotal = total;
    document.getElementById('order_id').value = orderId;
    document.getElementById('total_amount').value = total;
    document.getElementById('display_total').value = `Rp ${total.toLocaleString('id-ID')}`;
    
    // Ambil detail pesanan
    fetch(`/kasir_restoran/api/orders/get_order.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayOrderDetails(data.data);
                new bootstrap.Modal(document.getElementById('paymentModal')).show();
            } else {
                showAlert('danger', 'Gagal mengambil detail pesanan');
            }
        })
        .catch(error => {
            showAlert('danger', 'Terjadi kesalahan saat mengambil detail pesanan');
        });
}

function displayOrderDetails(order) {
    let html = `
        <table class="table table-sm">
            <tr>
                <td>No. Meja</td>
                <td>: ${order.table_number}</td>
            </tr>
            <tr>
                <td>Waktu Pesan</td>
                <td>: ${new Date(order.order_time).toLocaleString('id-ID')}</td>
            </tr>
            <tr>
                <td>Pelayan</td>
                <td>: ${order.waiter_name}</td>
            </tr>
        </table>
        <h6>Item Pesanan:</h6>
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
    
    order.items.forEach(item => {
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
                    <th>Rp ${parseInt(order.total).toLocaleString('id-ID')}</th>
                </tr>
            </tfoot>
        </table>
    `;
    
    document.getElementById('orderDetails').innerHTML = html;
}

// Event listener untuk metode pembayaran
document.getElementById('payment_method').addEventListener('change', function() {
    const cashSection = document.getElementById('cashPaymentSection');
    cashSection.style.display = this.value === 'cash' ? 'block' : 'none';
});

// Event listener untuk perhitungan kembalian
document.getElementById('cash_amount').addEventListener('input', function() {
    const cashAmount = parseInt(this.value) || 0;
    const change = cashAmount - currentTotal;
    document.getElementById('changeAmount').textContent = `Rp ${change.toLocaleString('id-ID')}`;
});

function submitPayment() {
    const formData = new FormData(document.getElementById('paymentForm'));
    
    // Validasi pembayaran tunai
    if (formData.get('payment_method') === 'cash') {
        const cashAmount = parseInt(formData.get('cash_amount')) || 0;
        if (cashAmount < currentTotal) {
            showAlert('danger', 'Jumlah uang tunai kurang dari total pembayaran');
            return;
        }
    }

    fetch('/kasir_restoran/api/orders/process_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let message = 'Pembayaran berhasil!';
            if (data.change_amount > 0) {
                message += `\nKembalian: Rp ${data.change_amount.toLocaleString('id-ID')}`;
            }
            showAlert('success', message);
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('danger', data.message || 'Gagal memproses pembayaran');
        }
    })
    .catch(error => {
        showAlert('danger', 'Terjadi kesalahan saat memproses pembayaran');
    });
}

// Refresh halaman setiap 30 detik
setTimeout(function() {
    location.reload();
}, 30000);
</script>
