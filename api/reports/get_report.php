<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_SESSION['role'] === 'owner') {
    try {
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-6 days'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d');

        // Ambil transaksi
        $query = "
            SELECT 
                t.*,
                u_kasir.username as kasir_name,
                u_waiter.username as waiter_name,
                tb.table_number
            FROM transactions t
            JOIN orders o ON t.order_id = o.id
            JOIN tables tb ON o.table_id = tb.id
            JOIN users u_kasir ON t.kasir_id = u_kasir.id
            LEFT JOIN users u_waiter ON o.waiter_id = u_waiter.id
            WHERE DATE(t.transaction_time) BETWEEN ? AND ?
            ORDER BY t.transaction_time DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Hitung total item terjual
        $stmt = $pdo->prepare("
            SELECT SUM(oi.quantity) as total_items
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN transactions t ON t.order_id = o.id
            WHERE DATE(t.transaction_time) BETWEEN ? AND ?
        ");
        $stmt->execute([$start_date, $end_date]);
        $total_items = $stmt->fetch()['total_items'] ?? 0;

        // Hitung summary
        $summary = [
            'total_transactions' => count($transactions),
            'total_income' => array_sum(array_column($transactions, 'total')),
            'average_transaction' => count($transactions) > 0 ? 
                array_sum(array_column($transactions, 'total')) / count($transactions) : 0,
            'total_items' => $total_items
        ];

        echo json_encode([
            'success' => true,
            'transactions' => $transactions,
            'summary' => $summary
        ]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 