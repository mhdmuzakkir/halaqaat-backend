<?php
require_once 'includes/config.php';
requireRole('admin');

if (!isset($_GET['id'])) {
    header('Location: halaqaat_list.php');
    exit;
}

$id = intval($_GET['id']);

// Soft delete - update status to inactive
$stmt = $conn->prepare("UPDATE halaqaat SET status = 'inactive' WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header('Location: halaqaat_list.php');
exit;
?>
