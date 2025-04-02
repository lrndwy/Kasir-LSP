<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $id = $_GET['id'];
        
        $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        echo json_encode(['success' => true, 'data' => $user]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil data pengguna']);
    }
}
?> 