<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $table_number = $_POST['table_number'];
        $status = $_POST['status'];

        // Cek apakah nomor meja sudah ada
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tables WHERE table_number = ?");
        $stmt->execute([$table_number]);
        if($stmt->fetch()['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Nomor meja sudah digunakan']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO tables (table_number, status) VALUES (?, ?)");
        $stmt->execute([$table_number, $status]);

        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan meja']);
    }
}
?> 