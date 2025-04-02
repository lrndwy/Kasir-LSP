<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    try {
        $id = $_GET['id'];
        
        // Cek apakah meja sedang digunakan dalam pesanan
        $stmt = $pdo->prepare("SELECT COUNT(*) as used FROM orders WHERE table_id = ?");
        $stmt->execute([$id]);
        if($stmt->fetch()['used'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Meja tidak dapat dihapus karena masih digunakan dalam pesanan']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM tables WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus meja']);
    }
}
?> 