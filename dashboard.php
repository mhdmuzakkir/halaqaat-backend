<?php
// dashboard.php (ROLE ROUTER)
require_once __DIR__ . '/db.php';

session_name(defined('SESSION_NAME') ? SESSION_NAME : 'kahaf_session');
session_start();

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'] ?? 'ustaaz';

switch ($role) {
    case 'admin':
        header("Location: dashboard_admin.php");
        exit;
    case 'mumtahin':
        header("Location: dashboard_mumtahin.php");
        exit;
    case 'mushrif':
        header("Location: dashboard_mushrif.php");
        exit;
    case 'ustaaz':
    default:
        header("Location: dashboard_ustaaz.php");
        exit;
}
