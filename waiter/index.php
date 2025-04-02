<?php
session_start();
require_once '../config/database.php';
require_once '../includes/alert.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'waiter') {
    header("Location: /kasir_restoran/");
    exit;
}
include '../includes/header.php';

// Hitung pesanan aktif hari ini
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_orders 
    FROM orders 
    WHERE waiter_id = ? 
    AND DATE(order_time) = CURDATE()
");
$stmt->execute([$_SESSION['user_id']]);
$total_orders = $stmt->fetch()['total_orders'];

// Ambil status meja
$stmt = $pdo->query("
    SELECT status, COUNT(*) as total
    FROM tables
    GROUP BY status
");
$table_stats = $stmt->fetchAll();

// Ambil pesanan aktif
$stmt = $pdo->prepare("
    SELECT o.*, t.table_number,
    (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id) as total
    FROM orders o 
    JOIN tables t ON o.table_id = t.id 
    WHERE o.waiter_id = ? 
    AND o.status = 'pending'
    ORDER BY o.order_time DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$active_orders = $stmt->fetchAll();
?>

<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="container-fluid py-4">
        <h2>Dashboard Waiter</h2>
        
        <!-- Kartu Informasi -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total Pesanan Hari Ini</h5>
                        <h3><?php echo $total_orders; ?></h3>
                    </div>
                </div>
            </div>
            <?php foreach ($table_stats as $stat): ?>
            <div class="col-md-4">
                <div class="card <?php echo $stat['status'] == 'available' ? 'bg-success' : 'bg-warning'; ?> text-white">
                    <div class="card-body">
                        <h5>Meja <?php echo $stat['status'] == 'available' ? 'Tersedia' : 'Terisi'; ?></h5>
                        <h3><?php echo $stat['total']; ?></h3>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pesanan Aktif -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Pesanan Aktif</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No. Meja</th>
                                <th>Waktu Pesan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_orders as $order): ?>
                            <tr>
                                <td><?php echo $order['table_number']; ?></td>
                                <td><?php echo date('H:i', strtotime($order['order_time'])); ?></td>
                                <td>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></td>
                                <td><span class="badge bg-warning">Pending</span></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($active_orders)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada pesanan aktif</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Pesanan -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="orderDetails">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function viewOrder(orderId) {
    fetch(`/kasir_restoran/api/orders/get_order.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayOrderDetails(data.data);
                new bootstrap.Modal(document.getElementById('orderModal')).show();
            }
        });
}

function displayOrderDetails(order) {
    let html = `
        <div class="mb-3">
            <table class="table table-sm">
                <tr>
                    <td>No. Meja</td>
                    <td>: ${order.table_number}</td>
                </tr>
                <tr>
                    <td>Waktu Pesan</td>
                    <td>: ${new Date(order.order_time).toLocaleString('id-ID')}</td>
                </tr>
            </table>
        </div>
        <div class="mb-3">
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
        </div>
    `;
    
    document.getElementById('orderDetails').innerHTML = html;
}

// Refresh halaman setiap 30 detik
setTimeout(function() {
    location.reload();
}, 30000);
</script>
