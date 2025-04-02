<?php
session_start();
require_once '../config/database.php';
require_once '../includes/alert.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'kasir') {
    header("Location: /kasir_restoran/");
    exit;
}
include '../includes/header.php';

// Hitung total pendapatan hari ini untuk kasir ini
$stmt = $pdo->prepare("SELECT SUM(total) as today_income FROM transactions 
    WHERE kasir_id = ? AND DATE(transaction_time) = CURDATE()");
$stmt->execute([$_SESSION['user_id']]);
$today_income = $stmt->fetch()['today_income'] ?? 0;

// Hitung jumlah transaksi per metode pembayaran hari ini
$stmt = $pdo->prepare("SELECT payment_method, COUNT(*) as total, SUM(total) as amount 
    FROM transactions 
    WHERE kasir_id = ? AND DATE(transaction_time) = CURDATE()
    GROUP BY payment_method");
$stmt->execute([$_SESSION['user_id']]);
$payment_stats = $stmt->fetchAll();

// Ambil pesanan yang pending
$stmt = $pdo->query("
    SELECT o.*, t.table_number,
    (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id) as total
    FROM orders o 
    JOIN tables t ON o.table_id = t.id 
    WHERE o.status = 'pending'
    ORDER BY o.order_time ASC
    LIMIT 5");
$pending_orders = $stmt->fetchAll();
?>

<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="container-fluid py-4">
        <h2>Dashboard Kasir</h2>
        
        <!-- Kartu Informasi Utama -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Pesanan Pending</h5>
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
                        $pending_orders_count = $stmt->fetch()['total'];
                        ?>
                        <h3><?php echo $pending_orders_count; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Transaksi Hari Ini</h5>
                        <?php
                        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM transactions WHERE kasir_id = ? AND DATE(transaction_time) = CURDATE()");
                        $stmt->execute([$_SESSION['user_id']]);
                        $today_transactions = $stmt->fetch()['total'];
                        ?>
                        <h3><?php echo $today_transactions; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5>Total Pendapatan Hari Ini</h5>
                        <h3>Rp <?php echo number_format($today_income, 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pesanan Pending Terbaru -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Pesanan Pending Terbaru</h5>
                            <a href="/kasir_restoran/kasir/orders.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No. Meja</th>
                                        <th>Waktu Pesan</th>
                                        <th>Total</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($pending_orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['table_number']; ?></td>
                                        <td><?php echo date('H:i', strtotime($order['order_time'])); ?></td>
                                        <td>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="processPayment(<?php echo $order['id']; ?>)">
                                                Proses Pembayaran
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($pending_orders)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Tidak ada pesanan pending</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistik Pembayaran -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Statistik Pembayaran Hari Ini</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Metode</th>
                                        <th>Jumlah</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($payment_stats as $stat): ?>
                                    <tr>
                                        <td><?php echo ucfirst($stat['payment_method']); ?></td>
                                        <td><?php echo $stat['total']; ?></td>
                                        <td>Rp <?php echo number_format($stat['amount'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($payment_stats)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Belum ada transaksi</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function processPayment(orderId) {
    window.location.href = `/kasir_restoran/kasir/orders.php?process=${orderId}`;
}

// Refresh halaman setiap 30 detik untuk update data
setTimeout(function() {
    location.reload();
}, 30000);
</script>
