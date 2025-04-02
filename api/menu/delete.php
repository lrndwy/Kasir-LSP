<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    
    try {
        // Ganti DELETE menjadi UPDATE untuk soft delete
        $stmt = $pdo->prepare("UPDATE menu SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menghapus menu: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
}
?> 