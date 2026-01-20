<?php
/**
 * Islamic Halqaat Management System
 * Public View - Display All Halqaat
 * 
 * This page displays all Islamic circles with filtering and searching
 * Suitable for viewing by teachers, parents, and public
 */

session_start();

require_once 'config.php';

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$filter_gender = $_GET['gender'] ?? '';

// Build query with search and filter
$query = "SELECT * FROM halqaat WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR ustad LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_gender)) {
    $query .= " AND gender = ?";
    $params[] = $filter_gender;
}

$query .= " ORDER BY id DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $halqaat = $stmt->fetchAll();
} catch (PDOException $e) {
    die('Query failed: ' . $e->getMessage());
}

// Get statistics
$stats = getStatistics($pdo);

// Format statistics with Arabic numerals (optional)
$total_halqaat_display = formatArabicNumber($stats['total_halqaat']);
$total_students_display = formatArabicNumber($stats['total_students']);

?>
<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="اسلامی حلقات کا انتظام - Islamic Halqaat Management System">
    <meta name="keywords" content="halqa, islamic education, quran, memorization">
    <meta name="theme-color" content="#b18f6e">
    <title>اسلامی حلقات - Islamic Halqaat</title>
    
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
    
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
            font-family: 'IBM Plex Sans Arabic', sans-serif;
            background: var(--light-bg);
            min-height: 100vh;
            color: var(--text-dark);
        }
        
        /* Header */
        header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: var(--white);
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 25px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .header-title {
            font-size: 1.8em;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-title .icon {
            font-size: 2em;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .header-actions a {
            color: var(--white);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 0.95em;
        }
        
        .header-actions a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .admin-badge {
            background: var(--warning);
            color: var(--white);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 700;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* Hero Section */
        .hero {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .hero h1 {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .hero p {
            font-size: 1.1em;
            color: var(--text-light);
            margin-bottom: 20px;
        }
        
        /* Stats Section */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: var(--white);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            text-align: center;
            border-right: 6px solid var(--primary-color);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .stat-card-icon {
            font-size: 2.5em;
            margin-bottom: 12px;
        }
        
        .stat-card-number {
            font-size: 2.5em;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .stat-card-label {
            color: var(--text-light);
            font-size: 0.95em;
        }
        
        /* Search & Filter Section */
        .search-filter-section {
            background: var(--white);
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 30px;
        }
        
        .search-filter-group {
            display: grid;
            grid-template-columns: 1fr 200px 100px;
            gap: 15px;
            align-items: end;
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
            font-family: 'IBM Plex Sans Arabic', sans-serif;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(177, 143, 110, 0.1);
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'IBM Plex Sans Arabic', sans-serif;
            font-size: 0.95em;
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
        
        .btn-reset {
            background: #e0e0e0;
            color: var(--text-dark);
        }
        
        .btn-reset:hover {
            background: #d0d0d0;
        }
        
        /* Cards Grid */
        .halqaat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .halqa-card {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s;
            border-right: 5px solid var(--primary-color);
        }
        
        .halqa-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        
        .halqa-card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: var(--white);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .halqa-card-icon {
            font-size: 2em;
        }
        
        .halqa-card-title {
            font-size: 1.3em;
            font-weight: 700;
        }
        
        .halqa-card-body {
            padding: 20px;
        }
        
        .halqa-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: var(--text-light);
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .info-value {
            color: var(--text-dark);
            font-weight: 700;
            font-size: 1em;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 14px;
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
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
        }
        
        .empty-state-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }
        
        .empty-state h2 {
            font-size: 1.5em;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: var(--text-light);
            font-size: 1em;
        }
        
        /* Footer */
        footer {
            background: var(--secondary-color);
            color: var(--white);
            text-align: center;
            padding: 30px;
            margin-top: 50px;
            font-size: 0.95em;
        }
        
        footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        footer a:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-title {
                font-size: 1.3em;
            }
            
            .hero h1 {
                font-size: 1.8em;
            }
            
            .search-filter-group {
                grid-template-columns: 1fr;
            }
            
            .halqaat-grid {
                grid-template-columns: 1fr;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
            
            .header-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .header-actions a {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <h2 class="header-title">
                <span class="icon">📚</span>
                <span>اسلامی حلقات</span>
            </h2>
            <div class="header-actions">
                <?php if (isset($_SESSION['admin']) && $_SESSION['admin']): ?>
                    <span class="admin-badge">⚙️ انتظامیہ موڈ</span>
                    <a href="admin.php">👨‍💼 ڈیش بورڈ</a>
                    <a href="logout.php">🚪 نکلیں</a>
                <?php else: ?>
                    <a href="admin.php">🔐 اندراج</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="container">
        <!-- Hero Section -->
        <div class="hero">
            <h1>📖 اسلامی تعلیم کے حلقات</h1>
            <p>قرآن مجید کی تحفیظ اور اسلامی تعلیم کے لیے وقف کمیونٹی</p>
        </div>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-card-icon">📊</div>
                <div class="stat-card-number"><?= $total_halqaat_display ?></div>
                <div class="stat-card-label">کل حلقات</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">👥</div>
                <div class="stat-card-number"><?= $total_students_display ?></div>
                <div class="stat-card-label">کل طالب علم</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">👨‍🏫</div>
                <div class="stat-card-number">
                    <?php 
                    $teachers = $pdo->query("SELECT COUNT(DISTINCT ustad) as count FROM halqaat WHERE ustad IS NOT NULL AND ustad != ''")->fetch()['count'];
                    echo formatArabicNumber($teachers);
                    ?>
                </div>
                <div class="stat-card-label">اساتذہ</div>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="search-filter-section">
            <form method="GET" class="search-filter-group">
                <div class="form-group">
                    <label for="search">تلاش کریں</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        placeholder="حلقہ یا استاد کا نام درج کریں" 
                        value="<?= sanitize($search) ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="gender">نوع</label>
                    <select id="gender" name="gender">
                        <option value="">تمام</option>
                        <option value="مختلط" <?= $filter_gender === 'مختلط' ? 'selected' : '' ?>>مختلط</option>
                        <option value="بنات" <?= $filter_gender === 'بنات' ? 'selected' : '' ?>>بنات</option>
                        <option value="اولاد" <?= $filter_gender === 'اولاد' ? 'selected' : '' ?>>اولاد</option>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="btn btn-primary">🔍 تلاش</button>
                </div>
            </form>
            <?php if (!empty($search) || !empty($filter_gender)): ?>
                <div style="margin-top: 15px; text-align: center;">
                    <a href="index.php" class="btn btn-reset" style="text-decoration: none; display: inline-block;">
                        ✕ صاف کریں
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Halqaat Grid -->
        <?php if (count($halqaat) > 0): ?>
            <div class="halqaat-grid">
                <?php foreach ($halqaat as $h): ?>
                    <div class="halqa-card">
                        <div class="halqa-card-header">
                            <div class="halqa-card-icon">
                                <?php
                                $gender = $h['gender'];
                                $icon = $gender === 'بنات' ? '👧' : ($gender === 'اولاد' ? '👦' : '👨‍👩‍👧‍👦');
                                echo $icon;
                                ?>
                            </div>
                            <div class="halqa-card-title">
                                <?= sanitize($h['name']) ?>
                            </div>
                        </div>
                        
                        <div class="halqa-card-body">
                            <div class="halqa-info">
                                <div class="info-item">
                                    <span class="info-label">استاد/استانی</span>
                                    <span class="info-value">
                                        <?= !empty($h['ustad']) ? sanitize($h['ustad']) : '—' ?>
                                    </span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">طالب علم</span>
                                    <span class="info-value">
                                        <?= formatArabicNumber($h['students']) ?>
                                    </span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">نوع</span>
                                    <span>
                                        <?php
                                        $gender = $h['gender'];
                                        $badge_class = 'badge-mixed';
                                        if ($gender === 'بنات') $badge_class = 'badge-girls';
                                        elseif ($gender === 'اولاد') $badge_class = 'badge-boys';
                                        ?>
                                        <span class="badge <?= $badge_class ?>">
                                            <?= $gender ?>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h2>کوئی حلقہ نہیں ملا</h2>
                <p>آپ کی تلاش کے معیار سے مماثل کوئی حلقہ دستیاب نہیں ہے</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer>
        <p>© 2024 اسلامی حلقات کا انتظام | Islamic Halqaat Management System</p>
        <p style="margin-top: 10px; font-size: 0.9em;">
            برائے مزید معلومات: <a href="mailto:info@example.com">info@example.com</a>
        </p>
    </footer>

    <script>
        // Simple interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add subtle animations
            const cards = document.querySelectorAll('.halqa-card');
            cards.forEach((card, index) => {
                card.style.animation = `fadeIn 0.6s ease-in-out ${index * 0.1}s both`;
            });
        });
    </script>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</body>
</html>
