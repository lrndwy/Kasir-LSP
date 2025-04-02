<?php
session_start();
require_once '../config/database.php';
require_once '../includes/alert.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /kasir_restoran/");
    exit;
}
include '../includes/header.php';

// Hitung total pendapatan hari ini
$stmt = $pdo->query("SELECT SUM(total) as today_income FROM transactions WHERE DATE(transaction_time) = CURDATE()");
$today_income = $stmt->fetch()['today_income'] ?? 0;

// Hitung total pendapatan bulan ini
$stmt = $pdo->query("SELECT SUM(total) as month_income FROM transactions WHERE MONTH(transaction_time) = MONTH(CURRENT_DATE()) AND YEAR(transaction_time) = YEAR(CURRENT_DATE())");
$month_income = $stmt->fetch()['month_income'] ?? 0;

// Ambil statistik menu terlaris
$stmt = $pdo->query("
    SELECT m.name, SUM(oi.quantity) as total_ordered
    FROM order_items oi
    JOIN menu m ON oi.menu_id = m.id
    GROUP BY m.id
    ORDER BY total_ordered DESC
    LIMIT 5
");
$popular_menu = $stmt->fetchAll();

// Ambil statistik transaksi per metode pembayaran
$stmt = $pdo->query("
    SELECT payment_method, COUNT(*) as total
    FROM transactions
    WHERE DATE(transaction_time) = CURDATE()
    GROUP BY payment_method
");
$payment_methods = $stmt->fetchAll();
?>

<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="container-fluid py-4">
        <h2>Dashboard Administrator</h2>
        
        <!-- Kartu Informasi Utama -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total Meja</h5>
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) as total FROM tables");
                        $total_tables = $stmt->fetch()['total'];
                        ?>
                        <h3><?php echo $total_tables; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Total Menu</h5>
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) as total FROM menu");
                        $total_menu = $stmt->fetch()['total'];
                        ?>
                        <h3><?php echo $total_menu; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5>Pesanan Hari Ini</h5>
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE DATE(order_time) = CURDATE()");
                        $total_orders = $stmt->fetch()['total'];
                        ?>
                        <h3><?php echo $total_orders; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5>Pendapatan Hari Ini</h5>
                        <h3>Rp <?php echo number_format($today_income, 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafik dan Statistik -->
        <div class="row mt-4">
            <!-- Menu Terlaris -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Menu Terlaris</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Menu</th>
                                    <th>Total Dipesan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($popular_menu as $menu): ?>
                                <tr>
                                    <td><?php echo $menu['name']; ?></td>
                                    <td><?php echo $menu['total_ordered']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Metode Pembayaran Hari Ini -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Metode Pembayaran Hari Ini</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Metode</th>
                                    <th>Jumlah Transaksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($payment_methods as $method): ?>
                                <tr>
                                    <td><?php echo ucfirst($method['payment_method']); ?></td>
                                    <td><?php echo $method['total']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ringkasan Keuangan -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Ringkasan Keuangan</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <h6>Pendapatan Hari Ini</h6>
                                    <h4>Rp <?php echo number_format($today_income, 0, ',', '.'); ?></h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <h6>Pendapatan Bulan Ini</h6>
                                    <h4>Rp <?php echo number_format($month_income, 0, ',', '.'); ?></h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <h6>Rata-rata Transaksi</h6>
                                    <h4>Rp <?php echo $total_orders > 0 ? number_format($today_income / $total_orders, 0, ',', '.') : 0; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
