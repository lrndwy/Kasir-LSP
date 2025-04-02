<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        $order_id = $_POST['order_id'];
        $payment_method = $_POST['payment_method'];
        $total_amount = $_POST['total_amount'];
        $cash_amount = 0; // nilai default
        $kasir_id = $_SESSION['user_id'];

        // Validasi pembayaran dan set cash_amount
        if ($payment_method == 'cash') {
            $cash_amount = $_POST['cash_amount'] ?? 0;
            if ($cash_amount < $total_amount) {
                throw new Exception('Jumlah uang tunai kurang dari total pembayaran');
            }
        }

        // Insert ke tabel transactions
        $stmt = $pdo->prepare("
            INSERT INTO transactions (order_id, kasir_id, total, payment_method, cash_amount, transaction_time)
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$order_id, $kasir_id, $total_amount, $payment_method, $cash_amount]);

        // Update status pesanan
        $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
        $stmt->execute([$order_id]);

        // Update status meja menjadi available
        $stmt = $pdo->prepare("
            UPDATE tables t
            JOIN orders o ON t.id = o.table_id
            SET t.status = 'available'
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'transaction_id' => $pdo->lastInsertId(),
            'change_amount' => ($payment_method == 'cash') ? $cash_amount - $total_amount : 0
        ]);
    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 