<?php
$dotenv = parse_ini_file(__DIR__ . '/../.env');

$host = $dotenv['DB_HOST'] ?? 'localhost';
$dbname = $dotenv['DB_NAME'] ?? 'kasir';
$username = $dotenv['DB_USER'] ?? 'root';
$password = $dotenv['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>
