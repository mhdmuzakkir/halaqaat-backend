<?php
// db.php
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    if (APP_DEBUG) {
        die("DB Connection failed: " . htmlspecialchars($e->getMessage()));
    }
    die("DB Connection failed.");
}
