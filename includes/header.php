<?php
// Header template for Kahaf Halaqaat
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Get language
$lang = get_language();
$tr = get_translations($lang);
$isRTL = $lang === 'ur';

// Check login
$isLoggedIn = !empty($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? '';
$userName = $_SESSION['user_name'] ?? '';

// Get current page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Base URL
$baseUrl = '';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo h($tr['app']); ?> — <?php echo h($tr['dashboard']); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Noto+Nastaliq+Urdu:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css" />
</head>
<body>
<?php if ($isLoggedIn): ?>
<div class="layout" id="layout">
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div>
        <div class="name"><?php echo h($tr['app']); ?></div>
        <div class="sub"><?php echo h($tr['dashboard']); ?></div>
      </div>
      <button class="sideToggle" id="sideToggle" title="<?php echo $isRTL ? 'بند کریں' : 'Collapse'; ?>">↔</button>
    </div>

    <!-- Navigation based on role -->
    <nav class="nav">
      <a href="dashboard_admin.php" class="dash <?php echo $currentPage === 'dashboard_admin' ? 'active' : ''; ?>">
        <span class="txt"><?php echo h($tr['nav_dashboard']); ?></span>
      </a>
      
      <?php if ($userRole === 'admin' || $userRole === 'mushrif'): ?>
      <a href="halaqaat_list.php" class="halaqa <?php echo in_array($currentPage, ['halaqaat_list', 'halaqaat_manage']) ? 'active' : ''; ?>">
        <span class="txt"><?php echo h($tr['nav_halaqaat']); ?></span>
      </a>
      <a href="students_manage.php" class="students <?php echo $currentPage === 'students_manage' ? 'active' : ''; ?>">
        <span class="txt"><?php echo h($tr['nav_students']); ?></span>
      </a>
      <a href="users_manage.php" class="ustaaz <?php echo $currentPage === 'users_manage' ? 'active' : ''; ?>">
        <span class="txt"><?php echo h($tr['nav_ustaaz']); ?></span>
      </a>
      <a href="exams_manage.php" class="exams <?php echo in_array($currentPage, ['exams_manage', 'exam_marks_entry', 'exam_finalize']) ? 'active' : ''; ?>">
        <span class="txt"><?php echo h($tr['nav_exams']); ?></span>
      </a>
      <a href="reports_overview.php" class="reports <?php echo $currentPage === 'reports_overview' ? 'active' : ''; ?>">
        <span class="txt"><?php echo h($tr['nav_reports']); ?></span>
      </a>
      <?php endif; ?>
      
      <?php if ($userRole === 'ustaaz' || $userRole === 'ustadah'): ?>
      <a href="attendance.php" class="attendance <?php echo $currentPage === 'attendance' ? 'active' : ''; ?>">
        <span class="txt"><?php echo h($tr['attendance']); ?></span>
      </a>
      <a href="students_progress.php" class="progress <?php echo $currentPage === 'students_progress' ? 'active' : ''; ?>">
        <span class="txt"><?php echo h($tr['progress']); ?></span>
      </a>
      <?php endif; ?>
      
      <?php if ($userRole === 'mumtahin'): ?>
      <a href="exam_marks_entry.php" class="exams <?php echo $currentPage === 'exam_marks_entry' ? 'active' : ''; ?>">
        <span class="txt"><?php echo h($tr['nav_exams']); ?></span>
      </a>
      <a href="exam_finalize.php" class="reports <?php echo $currentPage === 'exam_finalize' ? 'active' : ''; ?>">
        <span class="txt"><?php echo h($tr['finalize']); ?></span>
      </a>
      <?php endif; ?>
    </nav>

    <div class="sidebarBottom">
      <a class="pillSide" href="?lang=<?php echo $lang === 'ur' ? 'en' : 'ur'; ?>">
        <?php echo $lang === 'ur' ? 'EN | اردو' : 'Urdu | EN'; ?>
      </a>
      <a class="pillSide logout" href="logout.php"><?php echo h($tr['logout']); ?></a>
    </div>
  </aside>

  <!-- Mobile Overlay -->
  <div class="overlay" id="overlay"></div>

  <!-- Main Content -->
  <main class="main">
    <!-- Mobile Menu Button -->
    <button class="menuBtn" id="menuBtn">☰</button>
    
    <!-- Topbar -->
    <div class="topbar">
      <div class="hero">
        <div class="heroTitle"><?php echo h($tr['greeting']); ?></div>
        <div class="heroSub"><?php echo h($userName); ?></div>
      </div>
    </div>
<?php else: ?>
<!-- Not logged in - just show content without sidebar -->
<main class="main loginBody">
<?php endif; ?>
