<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $name = $_POST['name'];
        $price = $_POST['price'];
        $category = $_POST['category'];

        $stmt = $pdo->prepare("INSERT INTO menu (name, price, category) VALUES (?, ?, ?)");
        $stmt->execute([$name, $price, $category]);

        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Menu gagal ditambahkan']);
    }
}
?> 