<?php
session_start();
if (!isset($_SESSION['admin']) || $_POST['pass'] ?? '' !== 'admin123') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['pass'] ?? '') === 'admin123') {
        $_SESSION['admin'] = true;
    } else {
        echo '<form method="POST"><input type="password" name="pass" placeholder="كلمة السر"><button>دخول</button></form>';
        exit;
    }
}
require_once 'config.php';

$action = $_GET['action'] ?? 'list';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $stmt = $pdo->prepare("INSERT INTO halqaat (name, ustad, students, gender) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['ustad'], (int)$_POST['students'], $_POST['gender']]);
    $message = 'تم الإضافة!';
}

if ($action === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM halqaat WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header('Location: admin.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM halqaat ORDER BY id");
$halqaat = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة الحلقات</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style> /* Same styles as index.php + table {width:100%; border-collapse:collapse;} th,td{padding:10px;border-bottom:1px solid #ddd;} form{display:flex;flex-direction:column;max-width:400px;margin:20px 0;} input,select,button{padding:10px;margin:5px 0;} button{background:#3498db;color:white;border:none;border-radius:5px;cursor:pointer;} .delete{background:#e74c3c;} */ </style>
</head>
<body>
    <h1>لوحة إدارة الحلقات</h1>
    <?php if ($message) echo "<p style='color:green;'>$message</p>"; ?>
    
    <form method="POST">
        <input name="name" placeholder="اسم الحلقة" required>
        <input name="ustad" placeholder="اسم الأستاذ/ة">
        <input name="students" type="number" placeholder="عدد الطلاب" value="14">
        <select name="gender">
            <option value="مختلط">مختلط</option>
            <option value="بنات">بنات</option>
            <option value="أولاد">أولاد</option>
        </select>
        <button>إضافة حلقة</button>
    </form>
    
    <table>
        <thead><tr><th>اسم</th><th>أستاذ</th><th>طلاب</th><th>جنس</th><th>إجراءات</th></tr></thead>
        <tbody>
            <?php foreach ($halqaat as $h): ?>
            <tr>
                <td><?= htmlspecialchars($h['name']) ?></td>
                <td><?= htmlspecialchars($h['ustad']) ?></td>
                <td><?= $h['students'] ?></td>
                <td><?= $h['gender'] ?></td>
                <td>
                    <a href="?action=edit&id=<?= $h['id'] ?>" style="color:#3498db;">تعديل</a>
                    <a href="?action=delete&id=<?= $h['id'] ?>" onclick="return confirm('حذف؟')" class="delete" style="color:#e74c3c;">حذف</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="index.php">العرض الرئيسي</a>
</body>
</html>
