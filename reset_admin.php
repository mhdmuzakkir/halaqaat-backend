<?php
// reset_admin.php  (RUN ONCE, THEN DELETE)
require_once __DIR__ . '/db.php';

session_name(defined('SESSION_NAME') ? SESSION_NAME : 'kahaf_session');
session_start();

if (!defined('APP_DEBUG') || !APP_DEBUG) {
    die("Disabled.");
}

// Change these if you want
$username = 'admin';
$newPassword = 'admin';

$newHash = password_hash($newPassword, PASSWORD_BCRYPT);
if ($newHash === false) {
    die("password_hash failed.");
}

$stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = ? LIMIT 1");
$stmt->bind_param("ss", $newHash, $username);
$stmt->execute();

echo "<pre>";
echo "OK ✅ Password reset done\n";
echo "username: {$username}\n";
echo "new hash starts: " . htmlspecialchars(substr($newHash, 0, 30)) . "...\n";
echo "Now DELETE this file: reset_admin.php\n";
echo "</pre>";
