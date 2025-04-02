<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $id = $_GET['id'];
        
        $stmt = $pdo->prepare("SELECT * FROM menu WHERE id = ?");
        $stmt->execute([$id]);
        $menu = $stmt->fetch();

        echo json_encode(['success' => true, 'data' => $menu]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil data menu']);
    }
}
?> 