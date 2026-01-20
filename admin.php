<?php
session_start();
if (!isset($_SESSION['admin']) || $_POST['pass'] ?? '' !== 'admin123') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['pass'] ?? '') === 'admin123') {
        $_SESSION['admin'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="ar" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>دخول لوحة الإدارة</title>
            <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&family=Noto+Kufi+Arabic:wght@400;700&display=swap" rel="stylesheet">
            <style>
                :root {
                    --primary-color: #b18f6e;
                    --primary-hover: #a07c5e;
                    --secondary-color: #444444;
                    --light-bg: #f8f8f8;
                    --white: #ffffff;
                    --text-dark: #2c2c2c;
                    --text-light: #666666;
                    --error: #c0392b;
                    --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.15);
                }
                
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Tajawal', 'Noto Kufi Arabic', sans-serif;
                    background: linear-gradient(135deg, var(--light-bg), #e8e8e8);
                    min-height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    padding: 20px;
                }
                
                .login-container {
                    background: var(--white);
                    border-radius: 12px;
                    box-shadow: var(--shadow-lg);
                    padding: 50px 40px;
                    max-width: 400px;
                    width: 100%;
                    text-align: center;
                }
                
                .login-header {
                    margin-bottom: 30px;
                }
                
                .login-header h1 {
                    font-size: 2em;
                    color: var(--primary-color);
                    margin-bottom: 10px;
                }
                
                .login-header p {
                    color: var(--text-light);
                    font-size: 0.95em;
                }
                
                .login-form {
                    display: flex;
                    flex-direction: column;
                    gap: 20px;
                }
                
                .form-group {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }
                
                .form-group label {
                    color: var(--text-dark);
                    font-weight: 600;
                    font-size: 0.95em;
                    text-align: right;
                }
                
                .form-group input {
                    padding: 12px 15px;
                    border: 2px solid #e0e0e0;
                    border-radius: 8px;
                    font-size: 1em;
                    font-family: 'Tajawal', sans-serif;
                    transition: border-color 0.3s;
                }
                
                .form-group input:focus {
                    outline: none;
                    border-color: var(--primary-color);
                    box-shadow: 0 0 0 3px rgba(177, 143, 110, 0.1);
                }
                
                .login-button {
                    padding: 13px;
                    background: var(--primary-color);
                    color: var(--white);
                    border: none;
                    border-radius: 8px;
                    font-size: 1.05em;
                    font-weight: 700;
                    cursor: pointer;
                    transition: all 0.3s;
                    font-family: 'Tajawal', sans-serif;
                }
                
                .login-button:hover {
                    background: var(--primary-hover);
                    transform: translateY(-2px);
                    box-shadow: 0 8px 16px rgba(177, 143, 110, 0.3);
                }
                
                .login-button:active {
                    transform: translateY(0);
                }
                
                .icon {
                    font-size: 3em;
                    margin-bottom: 15px;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <div class="login-header">
                    <div class="icon">🔐</div>
                    <h1>دخول الإدارة</h1>
                    <p>أدخل كلمة السر للوصول إلى لوحة التحكم</p>
                </div>
                
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="pass">كلمة السر</label>
                        <input 
                            type="password" 
                            id="pass" 
                            name="pass" 
                            placeholder="أدخل كلمة السر" 
                            required 
                            autofocus
                        >
                    </div>
                    <button type="submit" class="login-button">دخول 🔑</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

require_once 'config.php';

$action = $_GET['action'] ?? 'list';
$message = '';
$message_type = '';

// Add new halqa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && !isset($_POST['edit_id'])) {
    $stmt = $pdo->prepare("INSERT INTO halqaat (name, ustad, students, gender) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['ustad'], (int)$_POST['students'], $_POST['gender']]);
    $message = '✅ تم إضافة الحلقة بنجاح!';
    $message_type = 'success';
}

// Edit halqa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $stmt = $pdo->prepare("UPDATE halqaat SET name=?, ustad=?, students=?, gender=? WHERE id=?");
    $stmt->execute([$_POST['name'], $_POST['ustad'], (int)$_POST['students'], $_POST['gender'], $_POST['edit_id']]);
    $message = '✅ تم التحديث بنجاح!';
    $message_type = 'success';
}

// Delete halqa
if ($action === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM halqaat WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header('Location: admin.php');
    exit;
}

// Fetch all halqaat
$stmt = $pdo->query("SELECT * FROM halqaat ORDER BY id DESC");
$halqaat = $stmt->fetchAll();

// Get halqa for editing
$edit_halqa = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM halqaat WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $edit_halqa = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لوحة إدارة الحلقات</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&family=Noto+Kufi+Arabic:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #b18f6e;
            --primary-hover: #a07c5e;
            --secondary-color: #444444;
            --accent-color: #3e846a;
            --light-bg: #f8f8f8;
            --white: #ffffff;
            --text-dark: #2c2c2c;
            --text-light: #666666;
            --success: #27ae60;
            --warning: #f39c12;
            --error: #c0392b;
            --shadow: 0 0 3px 0 rgba(0, 0, 0, 0.22);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Tajawal', 'Noto Kufi Arabic', sans-serif;
            background: var(--light-bg);
            min-height: 100vh;
            color: var(--text-dark);
        }
        
        /* Header */
        header {
            background: var(--white);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .header-title {
            font-size: 1.8em;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .header-actions a,
        .logout-btn {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            font-size: 0.95em;
        }
        
        .header-actions a:hover,
        .logout-btn:hover {
            color: var(--primary-color);
            background: rgba(177, 143, 110, 0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        h1 {
            text-align: center;
            background: var(--secondary-color);
            color: var(--white);
            padding: 30px 40px;
            border-radius: 12px;
            font-size: 2.2em;
            margin-bottom: 40px;
            box-shadow: var(--shadow-lg);
            font-weight: 700;
            position: relative;
            overflow: hidden;
        }
        
        h1::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        h2 {
            color: var(--primary-color);
            font-size: 1.5em;
            margin-bottom: 20px;
            margin-top: 30px;
        }
        
        /* Message */
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .message.success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .message.error {
            background: rgba(192, 57, 43, 0.1);
            color: var(--error);
            border-left: 4px solid var(--error);
        }
        
        /* Form Styles */
        .form-container {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 40px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.95em;
        }
        
        .form-group input,
        .form-group select {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            font-family: 'Tajawal', sans-serif;
            transition: border-color 0.3s;
            color: var(--text-dark);
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(177, 143, 110, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95em;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Tajawal', sans-serif;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(177, 143, 110, 0.3);
        }
        
        .btn-cancel {
            background: #e0e0e0;
            color: var(--text-dark);
        }
        
        .btn-cancel:hover {
            background: #d0d0d0;
        }
        
        /* Table Styles */
        .table-container {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: var(--secondary-color);
            color: var(--white);
        }
        
        th {
            padding: 18px 15px;
            text-align: right;
            font-weight: 700;
            font-size: 0.95em;
            border-bottom: 2px solid var(--primary-color);
        }
        
        td {
            padding: 16px 15px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 0.95em;
        }
        
        tbody tr {
            transition: background-color 0.3s;
        }
        
        tbody tr:hover {
            background-color: rgba(177, 143, 110, 0.05);
        }
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-edit,
        .btn-delete {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85em;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Tajawal', sans-serif;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-edit {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border: 1px solid #3498db;
        }
        
        .btn-edit:hover {
            background: #3498db;
            color: var(--white);
        }
        
        .btn-delete {
            background: rgba(192, 57, 43, 0.1);
            color: var(--error);
            border: 1px solid var(--error);
        }
        
        .btn-delete:hover {
            background: var(--error);
            color: var(--white);
        }
        
        /* Badge */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge-mixed {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border: 1px solid #3498db;
        }
        
        .badge-girls {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }
        
        .badge-boys {
            background: rgba(39, 174, 96, 0.1);
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 30px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: var(--primary-hover);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }
        
        .empty-state-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--white);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            text-align: center;
            border-right: 4px solid var(--primary-color);
        }
        
        .stat-card-number {
            font-size: 2em;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-card-label {
            color: var(--text-light);
            font-size: 0.9em;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .header-title {
                font-size: 1.4em;
            }
            
            h1 {
                font-size: 1.6em;
                padding: 20px 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-edit,
            .btn-delete {
                width: 100%;
                text-align: center;
            }
            
            table {
                font-size: 0.85em;
            }
            
            th,
            td {
                padding: 12px 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <h2 class="header-title">⚙️ لوحة إدارة الحلقات</h2>
            <div class="header-actions">
                <a href="index.php">👁️ العرض الرئيسي</a>
                <button class="logout-btn" onclick="if(confirm('هل تريد تسجيل الخروج؟')) { window.location='logout.php'; }">🚪 خروج</button>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="container">
        <h1>📋 إدارة الحلقات الإسلامية</h1>
        
        <!-- Message -->
        <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-card-number"><?= count($halqaat) ?></div>
                <div class="stat-card-label">إجمالي الحلقات</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number">
                    <?php
                    $total_students = array_sum(array_map(function($h) { return (int)$h['students']; }, $halqaat));
                    echo $total_students;
                    ?>
                </div>
                <div class="stat-card-label">إجمالي الطلاب</div>
            </div>
        </div>
        
        <!-- Form -->
        <div class="form-container">
            <h2><?= $edit_halqa ? '✏️ تعديل الحلقة' : '➕ إضافة حلقة جديدة' ?></h2>
            
            <form method="POST">
                <?php if ($edit_halqa): ?>
                    <input type="hidden" name="edit_id" value="<?= $edit_halqa['id'] ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">اسم الحلقة *</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            placeholder="أدخل اسم الحلقة" 
                            value="<?= $edit_halqa ? htmlspecialchars($edit_halqa['name']) : '' ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="ustad">اسم الأستاذ/ة</label>
                        <input 
                            type="text" 
                            id="ustad" 
                            name="ustad" 
                            placeholder="أدخل اسم الأستاذ أو الأستاذة" 
                            value="<?= $edit_halqa ? htmlspecialchars($edit_halqa['ustad']) : '' ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="students">عدد الطلاب</label>
                        <input 
                            type="number" 
                            id="students" 
                            name="students" 
                            placeholder="0" 
                            min="0"
                            value="<?= $edit_halqa ? $edit_halqa['students'] : '0' ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">نوع الحلقة</label>
                        <select id="gender" name="gender">
                            <option value="مختلط" <?= $edit_halqa && $edit_halqa['gender'] === 'مختلط' ? 'selected' : '' ?>>مختلط</option>
                            <option value="بنات" <?= $edit_halqa && $edit_halqa['gender'] === 'بنات' ? 'selected' : '' ?>>بنات</option>
                            <option value="أولاد" <?= $edit_halqa && $edit_halqa['gender'] === 'أولاد' ? 'selected' : '' ?>>أولاد</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <?php if ($edit_halqa): ?>
                        <a href="admin.php" class="btn-cancel">إلغاء</a>
                    <?php endif; ?>
                    <button type="submit" class="btn-primary">
                        <?= $edit_halqa ? '💾 تحديث' : '✅ إضافة' ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Table -->
        <h2>📊 قائمة الحلقات</h2>
        
        <?php if (count($halqaat) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>اسم الحلقة</th>
                            <th>الأستاذ/ة</th>
                            <th>عدد الطلاب</th>
                            <th>النوع</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($halqaat as $h): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($h['name']) ?></strong></td>
                                <td><?= htmlspecialchars($h['ustad']) ?: '—' ?></td>
                                <td><strong><?= $h['students'] ?></strong></td>
                                <td>
                                    <?php
                                    $gender = $h['gender'];
                                    $badge_class = 'badge-mixed';
                                    if ($gender === 'بنات') $badge_class = 'badge-girls';
                                    elseif ($gender === 'أولاد') $badge_class = 'badge-boys';
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= $gender ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?action=edit&id=<?= $h['id'] ?>" class="btn-edit">✏️ تعديل</a>
                                        <a href="?action=delete&id=<?= $h['id'] ?>" class="btn-delete" onclick="return confirm('هل تريد حذف هذه الحلقة؟')">🗑️ حذف</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>لا توجد حلقات متاحة حالياً</p>
                <p style="font-size: 0.9em; margin-top: 10px;">ابدأ بإضافة حلقة جديدة من النموذج أعلاه</p>
            </div>
        <?php endif; ?>
        
        <a href="index.php" class="back-link">← العودة إلى الصفحة الرئيسية</a>
    </div>
</body>
</html>