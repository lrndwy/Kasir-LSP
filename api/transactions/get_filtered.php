<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $start_date = $_GET['start_date'] ?? date('Y-m-d');
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        $payment_method = $_GET['payment_method'] ?? '';

        // Base query
        $query = "
            SELECT t.*, o.table_id, tb.table_number, 
                   u_waiter.username as waiter_name,
                   o.waiter_id
            FROM transactions t
            JOIN orders o ON t.order_id = o.id
            JOIN tables tb ON o.table_id = tb.id
            LEFT JOIN users u_waiter ON o.waiter_id = u_waiter.id
            WHERE t.kasir_id = ? 
            AND DATE(t.transaction_time) BETWEEN ? AND ?
        ";
        $params = [$_SESSION['user_id'], $start_date, $end_date];

        // Add payment method filter if specified
        if ($payment_method !== '') {
            $query .= " AND t.payment_method = ?";
            $params[] = $payment_method;
        }

        $query .= " ORDER BY t.transaction_time DESC";

        // Get transactions
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate summary
        $summary = [
            'total_transactions' => count($transactions),
            'total_income' => array_sum(array_column($transactions, 'total')),
            'average_transaction' => count($transactions) > 0 ? 
                array_sum(array_column($transactions, 'total')) / count($transactions) : 0
        ];

        echo json_encode([
            'success' => true,
            'data' => $transactions,
            'summary' => $summary
        ]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 