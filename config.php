<?php
$host = 'localhost'; // Hepsia MySQL
$dbname = 'mdmunazir_linuxproguru'; // Create in Hepsia
$user = 'mdmunazir_linuxproguru'; // Hepsia DB user
$pass = 'Vikhara@548';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die('DB Error: ' . $e->getMessage());  // Shows exact problem
}
?>