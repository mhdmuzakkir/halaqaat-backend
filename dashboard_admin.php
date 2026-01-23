<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// dashboard_admin.php
require_once __DIR__ . '/db.php';

session_name(defined('SESSION_NAME') ? SESSION_NAME : 'kahaf_session');
session_start();

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Language toggle
$allowedLang = ['ur', 'en'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowedLang, true)) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'ur';

$T = [
    'ur' => [
        'app' => 'کہف حلقات',
        'dashboard' => 'ڈیش بورڈ',
        'search' => 'تلاش کریں...',
        'welcome' => 'السلام علیکم!',
        'welcome_sub' => 'کہف حلقات سسٹم میں خوش آمدید',
        'nav_dashboard' => 'ڈیش بورڈ',
        'nav_halqaat' => 'حلقات',
        'nav_students' => 'طلباء',
        'nav_ustaaz' => 'اساتذہ',
        'nav_exams' => 'امتحانات',
        'nav_reports' => 'رپورٹس',
        'nav_settings' => 'ترتیبات',
        'logout' => 'لاگ آؤٹ',
        'switch_en' => 'English',
        'switch_ur' => 'اردو',

        'stat_total_halqaat' => 'کل حلقات',
        'stat_total_students' => 'کل طلباء',
        'stat_total_ustaaz' => 'اساتذہ',
        'stat_upcoming_exams' => 'آنے والے امتحانات',

        'section_upcoming' => 'آنے والے امتحانات',
        'section_gender' => 'جنـس کے مطابق',
        'section_recent_halqaat' => 'حلقات (تازہ)',
        'section_top_students' => 'امتیاز طلباء (نمونہ)',

        'boys' => 'طلباء',
        'girls' => 'طالبات',

        // NEW RULES: session only
        'session' => 'سیشن',
        'subah' => 'صبح',
        'asr' => 'عصر',

        'active' => 'فعال',
        'inactive' => 'غیر فعال',

        'view_all' => 'سب دیکھیں',
        'no_halaqaat' => 'ابھی تک کوئی حلقہ نہیں',
    ],
    'en' => [
        'app' => 'Kahf Halaqat',
        'dashboard' => 'Dashboard',
        'search' => 'Search...',
        'welcome' => 'Assalamu Alaikum!',
        'welcome_sub' => 'Welcome to Kahf Halaqat system',
        'nav_dashboard' => 'Dashboard',
        'nav_halqaat' => 'Halaqaat',
        'nav_students' => 'Students',
        'nav_ustaaz' => 'Ustaaz',
        'nav_exams' => 'Exams',
        'nav_reports' => 'Reports',
        'nav_settings' => 'Settings',
        'logout' => 'Logout',
        'switch_en' => 'English',
        'switch_ur' => 'اردو',

        'stat_total_halqaat' => 'Total Halaqaat',
        'stat_total_students' => 'Total Students',
        'stat_total_ustaaz' => 'Ustaaz',
        'stat_upcoming_exams' => 'Upcoming Exams',

        'section_upcoming' => 'Upcoming Exams',
        'section_gender' => 'By Gender',
        'section_recent_halqaat' => 'Halaqaat (Latest)',
        'section_top_students' => 'Top Students (Sample)',

        'boys' => 'Boys',
        'girls' => 'Girls',

        // NEW RULES: session only
        'session' => 'Session',
        'subah' => 'Subah',
        'asr' => 'Asr',

        'active' => 'Active',
        'inactive' => 'Inactive',

        'view_all' => 'View all',
        'no_halaqaat' => 'No halaqaat yet',
    ]
];

$isRtl = ($lang === 'ur');
$tr = $T[$lang];

$fullName = $_SESSION['full_name'] ?? 'Admin';

// -------------------- DB helpers (PHP 5.6 safe) --------------------
function table_exists($conn, $table) {
    $table = $conn->real_escape_string($table);
    $sql = "SHOW TABLES LIKE '" . $table . "'";
    $res = $conn->query($sql);
    if (!$res) return false;
    return ($res->num_rows > 0);
}

function scalar_int($conn, $sql) {
    $res = $conn->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_row();
    return (int)(isset($row[0]) ? $row[0] : 0);
}

function h($s){
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// -------------------- REAL STATS --------------------
$stats = array(
    'halqaat' => 0,
    'students' => 0,
    'ustaaz' => 0,
    'upcoming_exams' => 0,
    'boys' => 0,
    'girls' => 0
);

// ✅ FIX: your table is halaqaat (not halqaat)
$tbl_halqaat = 'halaqaat';
$tbl_students = 'students';
$tbl_users = 'users';
$tbl_exams = 'exams';

if (table_exists($conn, $tbl_halqaat)) {
    $stats['halqaat'] = scalar_int($conn, "SELECT COUNT(*) FROM `$tbl_halqaat`");
}

if (table_exists($conn, $tbl_students)) {
    $stats['students'] = scalar_int($conn, "SELECT COUNT(*) FROM `$tbl_students`");
    $stats['boys']  = scalar_int($conn, "SELECT COUNT(*) FROM `$tbl_students` WHERE LOWER(gender) IN ('male','boy','boys')");
    $stats['girls'] = scalar_int($conn, "SELECT COUNT(*) FROM `$tbl_students` WHERE LOWER(gender) IN ('female','girl','girls')");
}

if (table_exists($conn, $tbl_users)) {
    $stats['ustaaz'] = scalar_int($conn, "SELECT COUNT(*) FROM `$tbl_users` WHERE role='ustaaz' AND is_active=1");
}

if (table_exists($conn, $tbl_exams)) {
    $stats['upcoming_exams'] = scalar_int($conn, "SELECT COUNT(*) FROM `$tbl_exams` WHERE exam_date >= CURDATE()");
} elseif (table_exists($conn, 'exam_sessions')) {
    $stats['upcoming_exams'] = scalar_int($conn, "SELECT COUNT(*) FROM `exam_sessions` WHERE exam_date >= CURDATE()");
}

// -------------------- Latest Halaqaat (from DB) --------------------
$recentHalaqaat = [];
if (table_exists($conn, $tbl_halqaat)) {
    $sql = "SELECT id, name_ur, name_en, gender, session, is_active
            FROM `$tbl_halqaat`
            ORDER BY id DESC
            LIMIT 6";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) $recentHalaqaat[] = $row;
        $res->free();
    }
}

// Sample top students (keep for now)
$sampleTop = [
    ['name_ur'=>'عثمان طارق', 'name_en'=>'Usman Tariq', 'score'=>96],
    ['name_ur'=>'عائشہ ملک', 'name_en'=>'Ayesha Malik', 'score'=>94],
    ['name_ur'=>'عبداللہ خان', 'name_en'=>'Abdullah Khan', 'score'=>91],
];
?>
<!doctype html>
<html lang="<?php echo h($lang); ?>" dir="<?php echo $isRtl ? 'rtl' : 'ltr'; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">

  <title><?php echo h($tr['dashboard']); ?> — <?php echo h($tr['app']); ?></title>

  <style>
/* ✅ Force fonts by language (fix places still not Nastaliq) */
html[lang="ur"] body,
html[lang="ur"] .sidebar,
html[lang="ur"] .main,
html[lang="ur"] .pill,
html[lang="ur"] .nav a,
html[lang="ur"] .cardHeader,
html[lang="ur"] .stat .label,
html[lang="ur"] .stat .val,
html[lang="ur"] input,
html[lang="ur"] select,
html[lang="ur"] option,
html[lang="ur"] button,
html[lang="ur"] table,
html[lang="ur"] th,
html[lang="ur"] td {
  font-family:'Noto Nastaliq Urdu', serif !important;
}

html[lang="en"] body,
html[lang="en"] .sidebar,
html[lang="en"] .main,
html[lang="en"] .pill,
html[lang="en"] .nav a,
html[lang="en"] .cardHeader,
html[lang="en"] .stat .label,
html[lang="en"] .stat .val,
html[lang="en"] input,
html[lang="en"] select,
html[lang="en"] option,
html[lang="en"] button,
html[lang="en"] table,
html[lang="en"] th,
html[lang="en"] td {
  font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif !important;
}

    :root{
      --primary:#3e846a;
      --secondary:#b18f6e;
      --accent:#444444;
      --bg:#f6f2ee;
      --card:#ffffff;
      --border:#e7ddd4;

      --boy:#2f6fd6;
      --girl:#d24e8a;
    }

    *{box-sizing:border-box}

    body{
      margin:0;
      background:var(--bg);
      color:var(--accent);
    }

    /* keep layout + sidebar columns; sidebar CSS comes from include */
    .main{ padding:18px; }

    .topbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      flex-wrap:wrap;
      margin-bottom:14px;
    }
    .search{
      flex:1;
      min-width:220px;
      max-width:520px;
      display:flex;
      align-items:center;
      gap:10px;
      background:#fff;
      border:1px solid var(--border);
      border-radius:14px;
      padding:10px 12px;
      box-shadow: 0 6px 18px rgba(0,0,0,.04);
    }
    .search input{
      width:100%;
      border:0;
      outline:none;
      font-size:14px;
      background:transparent;
      color:var(--accent);
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
    .controls{
      display:flex;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
    }
    .pill{
      text-decoration:none;
      padding:8px 12px;
      border-radius:999px;
      border:1px solid var(--border);
      background:#fff;
      color:var(--accent);
      font-weight:900;
      font-size:13px;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
    .pill.active{
      background: var(--secondary);
      border-color: var(--secondary);
      color: #ffffff;
    }
    .pill.logout{
      background:var(--primary);
      color:#fff;
      border-color:var(--primary);
    }

    .hero{
      background:linear-gradient(90deg, rgba(68,68,68,.92), rgba(68,68,68,.70));
      color:#fff;
      border-radius:16px;
      padding:20px;
      border-bottom:4px solid var(--secondary);
      margin-bottom:14px;
    }
    .hero h1{ margin:0 0 8px 0; font-size:24px; font-weight:900; }
    .hero p{ margin:0; opacity:.92; line-height:1.8; font-size:14px; }

    .stats{
      display:grid;
      grid-template-columns: repeat(4, minmax(0,1fr));
      gap:12px;
      margin-bottom:14px;
    }
    @media (max-width: 1100px){ .stats{grid-template-columns: repeat(2, minmax(0,1fr));} }
    @media (max-width: 520px){ .stats{grid-template-columns: 1fr;} }

    .stat{
      background:#fff;
      border:1px solid var(--border);
      border-radius:16px;
      padding:14px;
      box-shadow: 0 6px 18px rgba(0,0,0,.04);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      min-height:82px;
    }
    .stat .label{ color:#666; font-weight:800; font-size:13px; }
    .stat .val{ font-size:24px; font-weight:900; color:var(--accent); }
    .stat.primary{border-left:6px solid var(--primary);}
    .stat.secondary{border-left:6px solid var(--secondary);}

    .grid{
      display:grid;
      grid-template-columns: 1.2fr .8fr;
      gap:12px;
    }
    @media (max-width: 980px){ .grid{grid-template-columns: 1fr;} }

    .card{
      background:#fff;
      border:1px solid var(--border);
      border-radius:16px;
      box-shadow: 0 6px 18px rgba(0,0,0,.04);
      overflow:hidden;
    }
    .cardHeader{
      padding:12px 14px;
      border-bottom:1px solid var(--border);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      font-weight:900;
      font-size:13px;
      color:var(--accent);
    }
    .cardBody{padding:14px;}

    .list{ display:flex; flex-direction:column; gap:10px; }
    .row{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      padding:12px;
      border:1px solid var(--border);
      border-radius:14px;
      background:#fff;
    }
    .row .left{ display:flex; flex-direction:column; gap:4px; min-width:0; }
    .row .title{
      font-weight:900;
      font-size:14px;
      color:var(--accent);
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .row .meta{
      font-size:12px;
      color:#666;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
      display:flex;
      gap:8px;
      flex-wrap:wrap;
    }

    .badge{
      display:inline-block;
      padding:4px 10px;
      border-radius:999px;
      font-size:12px;
      font-weight:900;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
      border:1px solid var(--border);
      background:#fff;
      color:var(--accent);
      white-space:nowrap;
    }
    .badge.primary{ background: rgba(62,132,106,.12); border-color: rgba(62,132,106,.35); }
    .badge.secondary{ background: rgba(177,143,110,.14); border-color: rgba(177,143,110,.45); }
    .badge.boy{ background: rgba(47,111,214,.12); border-color: rgba(47,111,214,.35); color: #1c4ea8; }
    .badge.girl{ background: rgba(210,78,138,.12); border-color: rgba(210,78,138,.35); color: #9c2e63; }

    .halaqaGrid{
      display:grid;
      grid-template-columns: repeat(3, minmax(0,1fr));
      gap:12px;
    }
    @media (max-width: 1100px){ .halaqaGrid{grid-template-columns: repeat(2, minmax(0,1fr));} }
    @media (max-width: 600px){ .halaqaGrid{grid-template-columns: 1fr;} }

    .halaqaCard{
      border:1px solid var(--border);
      border-radius:16px;
      padding:14px;
      background:#fff;
      box-shadow: 0 6px 18px rgba(0,0,0,.04);
      display:flex;
      flex-direction:column;
      gap:10px;
      min-height:140px;
    }
    .halaqaTitle{
      font-weight:900;
      font-size:14px;
      color:var(--accent);
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .halaqaMeta{
      display:flex;
      gap:8px;
      flex-wrap:wrap;
      align-items:center;
    }
    .halaqaFoot{
      margin-top:auto;
      display:flex;
      justify-content:space-between;
      gap:10px;
      color:#666;
      font-size:12px;
    }

    @media (max-width: 980px){
      .main{padding:16px;}
    }
  </style>
</head>

<body>

  <div class="layout">
    <?php
      // ✅ sidebar is now included from a separate file
      $active_nav = 'dashboard';
      include __DIR__ . '/includes/sidebar_admin.php';
    ?>

    <!-- Main -->
    <main class="main">
      <div class="topbar">
        <!-- menuBtn class is defined in sidebar include CSS -->
        <button class="menuBtn" type="button" onclick="toggleSidebar()">☰</button>

        <div class="search">
          <input type="text" placeholder="<?php echo h($tr['search']); ?>">
        </div>

        <div class="controls">
          <a class="pill <?php echo $lang==='ur' ? 'active' : ''; ?>" href="?lang=ur"><?php echo h($tr['switch_ur']); ?></a>
          <a class="pill <?php echo $lang==='en' ? 'active' : ''; ?>" href="?lang=en"><?php echo h($tr['switch_en']); ?></a>
          <a class="pill logout" href="logout.php"><?php echo h($tr['logout']); ?></a>
        </div>
      </div>

      <section class="hero">
        <h1><?php echo h($tr['welcome']); ?></h1>
        <p><?php echo h($tr['welcome_sub']); ?> — <?php echo h($fullName); ?></p>
      </section>

      <!-- Stats -->
      <section class="stats">
        <div class="stat secondary">
          <div class="label"><?php echo h($tr['stat_upcoming_exams']); ?></div>
          <div class="val"><?php echo (int)$stats['upcoming_exams']; ?></div>
        </div>
        <div class="stat primary">
          <div class="label"><?php echo h($tr['stat_total_ustaaz']); ?></div>
          <div class="val"><?php echo (int)$stats['ustaaz']; ?></div>
        </div>
        <div class="stat secondary">
          <div class="label"><?php echo h($tr['stat_total_students']); ?></div>
          <div class="val"><?php echo (int)$stats['students']; ?></div>
        </div>
        <div class="stat primary">
          <div class="label"><?php echo h($tr['stat_total_halqaat']); ?></div>
          <div class="val"><?php echo (int)$stats['halqaat']; ?></div>
        </div>
      </section>

      <section class="grid">
        <!-- Left column -->
        <div class="card">
          <div class="cardHeader">
            <span><?php echo h($tr['section_recent_halqaat']); ?></span>
            <a class="badge secondary" href="halaqaat_admin.php" style="text-decoration:none;"><?php echo h($tr['view_all']); ?></a>
          </div>

          <div class="cardBody">
            <?php if (empty($recentHalaqaat)): ?>
              <div style="padding:10px; color:#777;"><?php echo h($tr['no_halaqaat']); ?></div>
            <?php else: ?>
              <div class="halaqaGrid">
                <?php foreach ($recentHalaqaat as $hrow): ?>
                  <?php
                    $g = strtolower(trim($hrow['gender'] ?? 'boys'));
                    if ($g !== 'girls' && $g !== 'boys') $g = 'boys';

                    $sess = strtolower(trim($hrow['session'] ?? 'subah'));
                    if ($sess !== 'asr' && $sess !== 'subah') $sess = 'subah';

                    $title = ($lang === 'ur') ? ($hrow['name_ur'] ?? '') : ($hrow['name_en'] ?? '');
                    $title = trim((string)$title);
                    if ($title === '') $title = trim((string)($hrow['name_ur'] ?? $hrow['name_en'] ?? ('Halaqa #' . (int)$hrow['id'])));
                  ?>
                  <div class="halaqaCard">
                    <div class="halaqaTitle"><?php echo h($title); ?></div>

                    <div class="halaqaMeta">
                      <span class="badge <?php echo ($g === 'girls') ? 'girl' : 'boy'; ?>">
                        <?php echo h(($g === 'girls') ? $tr['girls'] : $tr['boys']); ?>
                      </span>

                      <span class="badge secondary">
                        <?php echo h($sess === 'asr' ? $tr['asr'] : $tr['subah']); ?>
                      </span>

                      <?php if ((int)$hrow['is_active'] === 1): ?>
                        <span class="badge primary"><?php echo h($tr['active']); ?></span>
                      <?php else: ?>
                        <span class="badge secondary"><?php echo h($tr['inactive']); ?></span>
                      <?php endif; ?>
                    </div>

                    <div class="halaqaFoot">
                      <span>#<?php echo (int)$hrow['id']; ?></span>
                      <span><?php echo h($tr['session']); ?>: <?php echo h($sess === 'asr' ? $tr['asr'] : $tr['subah']); ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right column -->
        <div style="display:flex; flex-direction:column; gap:12px;">
          <div class="card">
            <div class="cardHeader">
              <span><?php echo h($tr['section_upcoming']); ?></span>
              <span class="badge secondary"><?php echo h($tr['view_all']); ?></span>
            </div>
            <div class="cardBody">
              <div class="list">
                <div class="row">
                  <div class="left">
                    <div class="title">2026-02-05</div>
                    <div class="meta">
                      <span class="badge primary"><?php echo h($tr['boys']); ?></span>
                      <span class="badge secondary">10:00</span>
                    </div>
                  </div>
                  <span class="badge secondary">+<?php echo (int)$stats['upcoming_exams']; ?></span>
                </div>

                <div class="row">
                  <div class="left">
                    <div class="title">2026-02-08</div>
                    <div class="meta">
                      <span class="badge primary"><?php echo h($tr['girls']); ?></span>
                      <span class="badge secondary">11:00</span>
                    </div>
                  </div>
                  <span class="badge secondary">✓</span>
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="cardHeader">
              <span><?php echo h($tr['section_gender']); ?></span>
            </div>
            <div class="cardBody">
              <div class="list">
                <div class="row">
                  <div class="left">
                    <div class="title"><?php echo h($tr['boys']); ?></div>
                    <div class="meta"><span class="badge boy">Live</span></div>
                  </div>
                  <span class="badge boy"><?php echo (int)$stats['boys']; ?></span>
                </div>
                <div class="row">
                  <div class="left">
                    <div class="title"><?php echo h($tr['girls']); ?></div>
                    <div class="meta"><span class="badge girl">Live</span></div>
                  </div>
                  <span class="badge girl"><?php echo (int)$stats['girls']; ?></span>
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="cardHeader">
              <span><?php echo h($tr['section_top_students']); ?></span>
            </div>
            <div class="cardBody">
              <div class="list">
                <?php foreach ($sampleTop as $i => $s): ?>
                  <div class="row">
                    <div class="left">
                      <div class="title">
                        <?php echo h(($i+1) . '. ' . ($lang==='ur' ? $s['name_ur'] : $s['name_en'])); ?>
                      </div>
                    </div>
                    <span class="badge primary"><?php echo (int)$s['score']; ?>/100</span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

        </div>
      </section>

    </main>
  </div>

</body>
</html>
