<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'kasir') {
    header("Location: /kasir_restoran/");
    exit;
}

if (!isset($_GET['id'])) {
    die('ID Transaksi tidak ditemukan');
}

try {
    // Ambil detail transaksi
    $stmt = $pdo->prepare("
        SELECT t.*, o.table_id, tb.table_number, 
        u_waiter.username as waiter_name,
        u_kasir.username as kasir_name
        FROM transactions t
        JOIN orders o ON t.order_id = o.id
        JOIN tables tb ON o.table_id = tb.id
        LEFT JOIN users u_waiter ON o.waiter_id = u_waiter.id
        LEFT JOIN users u_kasir ON t.kasir_id = u_kasir.id
        WHERE t.id = ? AND t.kasir_id = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        die('Transaksi tidak ditemukan');
    }

    // Ambil item pesanan
    $stmt = $pdo->prepare("
        SELECT oi.*, m.name as menu_name
        FROM order_items oi
        JOIN menu m ON oi.menu_id = m.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$transaction['order_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Struk Pembayaran</title>
    <style>
        @media print {
            body {
                font-family: monospace;
                font-size: 12px;
                margin: 0;
                padding: 10px;
                width: 80mm; /* Ukuran kertas struk thermal */
            }
            .receipt {
                text-align: center;
            }
            .header {
                margin-bottom: 10px;
            }
            .divider {
                border-top: 1px dashed #000;
                margin: 5px 0;
            }
            .item {
                text-align: left;
            }
            .item-detail {
                display: flex;
                justify-content: space-between;
            }
            .total {
                font-weight: bold;
                margin-top: 10px;
            }
            .footer {
                margin-top: 20px;
                font-size: 10px;
            }
            @page {
                margin: 0;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt">
        <div class="header">
            <h2>RESTORAN KAMI</h2>
            <p>Jl. Contoh No. 123, Kota</p>
            <p>Telp: (021) 123456</p>
        </div>

        <div class="divider"></div>

        <div class="transaction-info">
            <p>No. Transaksi: <?php echo str_pad($transaction['id'], 6, '0', STR_PAD_LEFT); ?></p>
            <p>Tanggal: <?php echo date('d/m/Y H:i', strtotime($transaction['transaction_time'])); ?></p>
            <p>Kasir: <?php echo $transaction['kasir_name']; ?></p>
            <p>Meja: <?php echo $transaction['table_number']; ?></p>
            <p>Pelayan: <?php echo $transaction['waiter_name']; ?></p>
        </div>

        <div class="divider"></div>

        <div class="items">
            <?php foreach($items as $item): ?>
            <div class="item">
                <div><?php echo $item['menu_name']; ?></div>
                <div class="item-detail">
                    <span><?php echo $item['quantity'] . ' x ' . number_format($item['price'], 0, ',', '.'); ?></span>
                    <span><?php echo number_format($item['quantity'] * $item['price'], 0, ',', '.'); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="divider"></div>

        <div class="total">
            <div class="item-detail">
                <span>TOTAL</span>
                <span>Rp <?php echo number_format($transaction['total'], 0, ',', '.'); ?></span>
            </div>
            <?php if($transaction['payment_method'] == 'cash'): ?>
            <div class="item-detail">
                <span>TUNAI</span>
                <span>Rp <?php echo number_format($transaction['cash_amount'], 0, ',', '.'); ?></span>
            </div>
            <div class="item-detail">
                <span>KEMBALI</span>
                <span>Rp <?php echo number_format($transaction['cash_amount'] - $transaction['total'], 0, ',', '.'); ?></span>
            </div>
            <?php else: ?>
            <div class="item-detail">
                <span>BAYAR</span>
                <span><?php echo strtoupper($transaction['payment_method']); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="divider"></div>

        <div class="footer">
            <p>Terima Kasih Atas Kunjungan Anda</p>
            <p>Silahkan Datang Kembali</p>
        </div>
    </div>
</body>
</html>
<?php
} catch(PDOException $e) {
    die('Terjadi kesalahan: ' . $e->getMessage());
}
?> 