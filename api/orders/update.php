<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        $order_id = $_POST['order_id'];
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

        // Hapus item pesanan yang lama
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);

        // Tambahkan item pesanan yang baru
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