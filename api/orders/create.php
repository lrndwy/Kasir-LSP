<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        $table_id = $_POST['table_id'];
        $waiter_id = $_POST['waiter_id'];
        $items = $_POST['items'];

        // Validasi items
        $has_items = false;
        foreach ($items as $menu_id => $quantity) {
            if ($quantity > 0) {
                $has_items = true;
                break;
            }
        }

        if (!$has_items) {
            throw new Exception('Pesanan harus memiliki minimal 1 item');
        }

        // Buat pesanan baru
        $stmt = $pdo->prepare("INSERT INTO orders (table_id, waiter_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$table_id, $waiter_id]);
        $order_id = $pdo->lastInsertId();

        // Update status meja
        $stmt = $pdo->prepare("UPDATE tables SET status = 'occupied' WHERE id = ?");
        $stmt->execute([$table_id]);

        // Tambahkan item pesanan
        $stmt = $pdo->prepare("
            INSERT INTO order_items (order_id, menu_id, quantity, price) 
            SELECT ?, ?, ?, price FROM menu WHERE id = ?
        ");

        foreach ($items as $menu_id => $quantity) {
            if ($quantity > 0) {
                $stmt->execute([$order_id, $menu_id, $quantity, $menu_id]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 