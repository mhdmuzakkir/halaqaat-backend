<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('app_name'); ?> - <?php echo t('dashboard'); ?></title>
    
    <!-- Bootstrap CSS -->
    <?php if ($isRTL): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php else: ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #0f2d3d;
            --secondary-color: #aa815e;
            --light-bg: #f8f9fa;
            --border-color: #e0e0e0;
        }
        
        <?php if ($isRTL): ?>
        body {
            font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', 'Urdu Typesetting', serif;
            line-height: 2;
        }
        <?php endif; ?>
        
        body {
            background-color: var(--light-bg);
            min-height: 100vh;
        }
        
        .navbar {
            background-color: var(--primary-color) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            color: #fff !important;
            font-weight: 600;
            font-size: 1.4rem;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            color: var(--secondary-color) !important;
        }
        
        .lang-toggle {
            background: #fff;
            border-radius: 20px;
            padding: 4px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
        }
        
        .lang-toggle a {
            text-decoration: none;
            padding: 2px 8px;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .lang-toggle a.active-ur {
            background-color: var(--secondary-color);
            color: #fff;
        }
        
        .lang-toggle a.active-en {
            background-color: #28a745;
            color: #fff;
        }
        
        .lang-toggle a:not(.active-ur):not(.active-en) {
            color: #666;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon.primary {
            background: rgba(15, 45, 61, 0.1);
            color: var(--primary-color);
        }
        
        .stat-icon.secondary {
            background: rgba(170, 129, 94, 0.1);
            color: var(--secondary-color);
        }
        
        .stat-icon.success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .halaqa-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            border: 1px solid var(--border-color);
        }
        
        .halaqa-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .halaqa-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .halaqa-teacher {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
        
        .tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            margin: 2px;
        }
        
        .tag-gender-baneen {
            background: rgba(15, 45, 61, 0.1);
            color: var(--primary-color);
        }
        
        .tag-gender-banaat {
            background: rgba(233, 30, 99, 0.1);
            color: #e91e63;
        }
        
        .tag-time {
            background: rgba(170, 129, 94, 0.1);
            color: var(--secondary-color);
        }
        
        .tag-count {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .tag-mumayyiz {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .btn-primary-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
        }
        
        .btn-primary-custom:hover {
            background-color: #0a1f2a;
            border-color: #0a1f2a;
        }
        
        .btn-secondary-custom {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: #fff;
        }
        
        .btn-secondary-custom:hover {
            background-color: #8f6d4e;
            border-color: #8f6d4e;
        }
        
        .page-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .greeting {
            color: var(--secondary-color);
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }
        
        .search-box {
            border: 2px solid var(--border-color);
            border-radius: 25px;
            padding: 0.5rem 1rem;
            transition: border-color 0.3s;
        }
        
        .search-box:focus {
            border-color: var(--secondary-color);
            outline: none;
        }
        
        .filter-btn {
            background-color: var(--secondary-color);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1rem;
        }
        
        .filter-btn:hover {
            background-color: #8f6d4e;
        }
        
        .chart-container {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }
        
        .highlight-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1a4a63 100%);
            color: #fff;
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        .highlight-card .highlight-title {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }
        
        .highlight-card .highlight-value {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .mumtaaz-card {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #c49a75 100%);
            color: #fff;
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(170, 129, 94, 0.25);
        }
        
        .table thead th {
            background-color: var(--primary-color);
            color: #fff;
            border: none;
        }
        
        .alert {
            border-radius: 10px;
        }
        
        .sidebar {
            background: #fff;
            min-height: calc(100vh - 56px);
            border-right: 1px solid var(--border-color);
        }
        
        .sidebar .nav-link {
            color: #333 !important;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin: 0.25rem 0;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(170, 129, 94, 0.1);
            color: var(--secondary-color) !important;
        }
        
        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }
        
        <?php if ($isRTL): ?>
        .sidebar .nav-link i {
            margin-right: 0;
            margin-left: 0.5rem;
        }
        <?php endif; ?>
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color) 0%, #1a4a63 100%);
        }
        
        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo h1 {
            color: var(--primary-color);
            font-weight: 700;
        }
        
        .login-logo p {
            color: var(--secondary-color);
        }
        
        .footer {
            background: var(--primary-color);
            color: #fff;
            padding: 1rem 0;
            margin-top: auto;
        }
        
        .dropdown-menu {
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            border: none;
        }
        
        .badge-custom {
            background-color: var(--secondary-color);
            color: #fff;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .progress-bar {
            background-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard_<?php echo $_SESSION['role']; ?>.php">
                <i class="bi bi-moon-stars-fill me-2"></i><?php echo t('app_name'); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (hasRole('admin')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_admin.php"><i class="bi bi-speedometer2"></i> <?php echo t('dashboard'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="halaqaat_list.php"><i class="bi bi-people-fill"></i> <?php echo t('halaqaat'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="students_manage.php"><i class="bi bi-mortarboard-fill"></i> <?php echo t('students'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users_manage.php"><i class="bi bi-person-gear"></i> <?php echo t('users'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="exams_manage.php"><i class="bi bi-file-text-fill"></i> <?php echo t('exams'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports_overview.php"><i class="bi bi-bar-chart-fill"></i> <?php echo t('reports'); ?></a>
                    </li>
                    <?php elseif (hasRole('ustaaz') || hasRole('ustadah')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_teacher.php"><i class="bi bi-speedometer2"></i> <?php echo t('dashboard'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="attendance.php"><i class="bi bi-calendar-check"></i> <?php echo t('attendance'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="students_progress.php"><i class="bi bi-graph-up"></i> <?php echo t('progress'); ?></a>
                    </li>
                    <?php elseif (hasRole('mumtahin')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_mumtahin.php"><i class="bi bi-speedometer2"></i> <?php echo t('dashboard'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="exam_marks_entry.php"><i class="bi bi-pencil-square"></i> <?php echo t('marks_entry'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="exam_finalize.php"><i class="bi bi-lock-fill"></i> <?php echo t('finalize'); ?></a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-3">
                        <div class="lang-toggle">
                            <a href="?lang=ur" class="<?php echo $isRTL ? 'active-ur' : ''; ?>">ار</a>
                            <span>|</span>
                            <a href="?lang=en" class="<?php echo !$isRTL ? 'active-en' : ''; ?>">EN</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['name'] ?? 'User'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> <?php echo t('profile'); ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> <?php echo t('logout'); ?></a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
