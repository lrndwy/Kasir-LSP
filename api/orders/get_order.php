<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $order_id = $_GET['id'];
        
        // Ambil detail pesanan
        $stmt = $pdo->prepare("
            SELECT o.*, t.table_number,
            (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id) as total,
            u.username as waiter_name
            FROM orders o 
            JOIN tables t ON o.table_id = t.id
            LEFT JOIN users u ON o.waiter_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        // Ambil item pesanan
        $stmt = $pdo->prepare("
            SELECT oi.*, m.name as menu_name
            FROM order_items oi
            JOIN menu m ON oi.menu_id = m.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $order]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil data pesanan']);
    }
}
?> 