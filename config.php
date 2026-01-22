<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'mdmunazir_linuxproguru');
define('DB_PASS', 'Vikhara@548'); // Change this!
define('DB_NAME', 'mdmunazir_linuxproguru');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");

// Start session
session_start();
?>