<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $id = $_POST['id'];
        $table_number = $_POST['table_number'];
        $status = $_POST['status'];

        // Cek apakah nomor meja sudah ada (kecuali untuk meja yang sedang diedit)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tables WHERE table_number = ? AND id != ?");
        $stmt->execute([$table_number, $id]);
        if($stmt->fetch()['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Nomor meja sudah digunakan']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE tables SET table_number = ?, status = ? WHERE id = ?");
        $stmt->execute([$table_number, $status, $id]);

        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupdate meja']);
    }
}
?> 