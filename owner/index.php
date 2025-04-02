<?php
session_start();
require_once '../config/database.php';
require_once '../includes/alert.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: /kasir_restoran/");
    exit;
}
include '../includes/header.php';

// Default periode (sebulan terakhir)
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-1 month'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Hitung pendapatan hari ini
$stmt = $pdo->query("
    SELECT SUM(total) as today_income 
    FROM transactions 
    WHERE DATE(transaction_time) = CURDATE()
");
$today_income = $stmt->fetch()['today_income'] ?? 0;

// Hitung pendapatan bulan ini
$stmt = $pdo->query("
    SELECT SUM(total) as month_income 
    FROM transactions 
    WHERE MONTH(transaction_time) = MONTH(CURRENT_DATE()) 
    AND YEAR(transaction_time) = YEAR(CURRENT_DATE())
");
$month_income = $stmt->fetch()['month_income'] ?? 0;

// Hitung pendapatan periode
$stmt = $pdo->prepare("
    SELECT SUM(total) as period_income 
    FROM transactions 
    WHERE DATE(transaction_time) BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$period_income = $stmt->fetch()['period_income'] ?? 0;

// Top 5 menu terlaris periode ini
$stmt = $pdo->prepare("
    SELECT m.name, COUNT(oi.id) as total_ordered, SUM(oi.quantity) as total_qty,
           SUM(oi.quantity * oi.price) as total_income
    FROM order_items oi
    JOIN menu m ON oi.menu_id = m.id
    JOIN orders o ON oi.order_id = o.id
    JOIN transactions t ON t.order_id = o.id
    WHERE DATE(t.transaction_time) BETWEEN ? AND ?
    GROUP BY m.id
    ORDER BY total_qty DESC
    LIMIT 5
");
$stmt->execute([$start_date, $end_date]);
$popular_menu = $stmt->fetchAll();

// Performa kasir periode ini
$stmt = $pdo->prepare("
    SELECT u.username, COUNT(t.id) as total_transactions, 
           SUM(t.total) as total_income
    FROM transactions t
    JOIN users u ON t.kasir_id = u.id
    WHERE DATE(t.transaction_time) BETWEEN ? AND ?
    GROUP BY t.kasir_id
    ORDER BY total_income DESC
");
$stmt->execute([$start_date, $end_date]);
$kasir_performance = $stmt->fetchAll();

// Statistik metode pembayaran periode ini
$stmt = $pdo->prepare("
    SELECT payment_method, COUNT(*) as total_transactions, 
           SUM(total) as total_income
    FROM transactions
    WHERE DATE(transaction_time) BETWEEN ? AND ?
    GROUP BY payment_method
");
$stmt->execute([$start_date, $end_date]);
$payment_stats = $stmt->fetchAll();
?>

<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="container-fluid py-4">
        <h2>Dashboard Owner</h2>

        <!-- Filter Periode -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm" class="row g-3" method="GET">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="end_date" name="end_date"
                               value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Ringkasan Pendapatan -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Pendapatan Hari Ini</h5>
                        <h3>Rp <?php echo number_format($today_income, 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Pendapatan Periode Ini</h5>
                        <h3>Rp <?php echo number_format($period_income, 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5>Pendapatan Bulan Ini</h5>
                        <h3>Rp <?php echo number_format($month_income, 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Menu Terlaris -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top 5 Menu Terlaris Bulan Ini</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Menu</th>
                                        <th>Qty Terjual</th>
                                        <th>Total Pendapatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($popular_menu as $menu): ?>
                                    <tr>
                                        <td><?php echo $menu['name']; ?></td>
                                        <td><?php echo $menu['total_qty']; ?></td>
                                        <td>Rp <?php echo number_format($menu['total_income'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performa Kasir -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Performa Kasir Bulan Ini</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kasir</th>
                                        <th>Total Transaksi</th>
                                        <th>Total Pendapatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kasir_performance as $kasir): ?>
                                    <tr>
                                        <td><?php echo $kasir['username']; ?></td>
                                        <td><?php echo $kasir['total_transactions']; ?></td>
                                        <td>Rp <?php echo number_format($kasir['total_income'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistik Pembayaran -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Statistik Metode Pembayaran Bulan Ini</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Metode Pembayaran</th>
                                <th>Jumlah Transaksi</th>
                                <th>Total Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_stats as $stat): ?>
                            <tr>
                                <td><?php echo strtoupper($stat['payment_method']); ?></td>
                                <td><?php echo $stat['total_transactions']; ?></td>
                                <td>Rp <?php echo number_format($stat['total_income'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('filterForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        if (!startDate || !endDate) {
            showAlert('warning', 'Mohon isi periode tanggal terlebih dahulu!');
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            showAlert('error', 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir!');
            return;
        }
        
        this.submit();
    });
});
</script>

<?php include '../includes/footer.php'; ?>
