<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM menu ORDER BY category, name");
        $menu = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $menu]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil data menu']);
    }
}
?> 