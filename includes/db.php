<?php
// Database configuration for Kahaf Halaqaat

// Database credentials - UPDATE THESE FOR YOUR SERVER
define('DB_HOST', 'localhost');
define('DB_USER', 'mdmunazir_linuxproguru');
define('DB_PASS', 'Vikhara@548');
define('DB_NAME', 'mdmunazir_linuxproguru');
define('SESSION_NAME', 'kahaf_session');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to support Urdu/Arabic
$conn->set_charset("utf8mb4");

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
?>
