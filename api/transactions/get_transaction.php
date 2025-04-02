<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $transaction_id = $_GET['id'];
        
        // Ambil detail transaksi
        $stmt = $pdo->prepare("
            SELECT t.*, o.table_id, tb.table_number, u.username as waiter_name
            FROM transactions t
            JOIN orders o ON t.order_id = o.id
            JOIN tables tb ON o.table_id = tb.id
            LEFT JOIN users u ON o.waiter_id = u.id
            WHERE t.id = ? AND t.kasir_id = ?
        ");
        $stmt->execute([$transaction_id, $_SESSION['user_id']]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            throw new Exception('Transaksi tidak ditemukan');
        }

        // Ambil item pesanan
        $stmt = $pdo->prepare("
            SELECT oi.*, m.name as menu_name
            FROM order_items oi
            JOIN menu m ON oi.menu_id = m.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$transaction['order_id']]);
        $transaction['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $transaction]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 