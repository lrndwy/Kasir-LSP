<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $price = $_POST['price'];
        $category = $_POST['category'];

        $stmt = $pdo->prepare("UPDATE menu SET name = ?, price = ?, category = ? WHERE id = ?");
        $stmt->execute([$name, $price, $category, $id]);

        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Menu gagal diupdate']);
    }
}
?> 