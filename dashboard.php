<?php
// dashboard.php
require_once __DIR__ . '/db.php';

session_name(defined('SESSION_NAME') ? SESSION_NAME : 'kahaf_session');
session_start();

// Must be logged in
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
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
        'welcome' => 'خوش آمدید',
        'role' => 'رول',
        'admin' => 'ایڈمن',
        'ustaaz' => 'استاد/استادہ',
        'mumtahin' => 'ممتحن',
        'mushrif' => 'مشرف',
        'logout' => 'لاگ آؤٹ',
        'switch_en' => 'English',
        'switch_ur' => 'اردو',
        'menu' => 'مینُو',
        'admin_menu' => [
            ['label' => 'یوزرز مینجمنٹ (بعد میں)', 'href' => '#'],
            ['label' => 'حلقات مینجمنٹ (بعد میں)', 'href' => '#'],
            ['label' => 'امتحانات (بعد میں)', 'href' => '#'],
            ['label' => 'رپورٹس (بعد میں)', 'href' => '#'],
        ],
        'ustaaz_menu' => [
            ['label' => 'میری حلقہ (بعد میں)', 'href' => '#'],
            ['label' => 'حاضری (بعد میں)', 'href' => '#'],
            ['label' => 'مارکس (بعد میں)', 'href' => '#'],
        ],
        'mumtahin_menu' => [
            ['label' => 'امتحان انٹری (بعد میں)', 'href' => '#'],
            ['label' => 'نتائج ویریفائی (بعد میں)', 'href' => '#'],
        ],
        'mushrif_menu' => [
            ['label' => 'اپروول / ریویو (بعد میں)', 'href' => '#'],
            ['label' => 'رپورٹس (بعد میں)', 'href' => '#'],
        ],
    ],
    'en' => [
        'app' => 'Kahf Halaqat',
        'dashboard' => 'Dashboard',
        'welcome' => 'Welcome',
        'role' => 'Role',
        'admin' => 'Admin',
        'ustaaz' => 'Ustaaz/Ustadhah',
        'mumtahin' => 'Mumtahin (Examiner)',
        'mushrif' => 'Mushrif (Supervisor)',
        'logout' => 'Logout',
        'switch_en' => 'English',
        'switch_ur' => 'اردو',
        'menu' => 'Menu',
        'admin_menu' => [
            ['label' => 'User Management (later)', 'href' => '#'],
            ['label' => 'Halaqaat Management (later)', 'href' => '#'],
            ['label' => 'Exams (later)', 'href' => '#'],
            ['label' => 'Reports (later)', 'href' => '#'],
        ],
        'ustaaz_menu' => [
            ['label' => 'My Halqa (later)', 'href' => '#'],
            ['label' => 'Attendance (later)', 'href' => '#'],
            ['label' => 'Marks (later)', 'href' => '#'],
        ],
        'mumtahin_menu' => [
            ['label' => 'Exam Entry (later)', 'href' => '#'],
            ['label' => 'Verify Results (later)', 'href' => '#'],
        ],
        'mushrif_menu' => [
            ['label' => 'Review / Approvals (later)', 'href' => '#'],
            ['label' => 'Reports (later)', 'href' => '#'],
        ],
    ],
];

$isRtl = ($lang === 'ur');
$tr = $T[$lang];

$fullName = $_SESSION['full_name'] ?? 'User';
$role = $_SESSION['role'] ?? 'ustaaz';

$roleLabel = $tr[$role] ?? $role;

// Pick menu by role
$menu = [];
if ($role === 'admin') $menu = $tr['admin_menu'];
elseif ($role === 'mumtahin') $menu = $tr['mumtahin_menu'];
elseif ($role === 'mushrif') $menu = $tr['mushrif_menu'];
else $menu = $tr['ustaaz_menu'];
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>" dir="<?php echo $isRtl ? 'rtl' : 'ltr'; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">

  <title><?php echo htmlspecialchars($tr['dashboard']); ?> — <?php echo htmlspecialchars($tr['app']); ?></title>

  <style>
    :root{
      --primary:#3e846a;
      --secondary:#b18f6e;
      --accent:#444444;
      --bg:#f6f2ee;
      --card:#ffffff;
      --border:#e7ddd4;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      background:var(--bg);
      color:var(--accent);
      font-family:'Noto Nastaliq Urdu', serif;
    }
    .wrap{min-height:100vh;padding:18px}
    .topbar{
      background:var(--primary);
      color:#fff;
      padding:18px 18px;
      border-radius:14px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      border-bottom:4px solid var(--secondary);
    }
    .brand{
      display:flex;
      flex-direction:column;
      gap:4px;
    }
    .brand .app{
      font-size:22px;
      font-weight:900;
      letter-spacing:.5px;
      color:#fff;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
    .brand .sub{
      font-size:13px;
      font-weight:700;
      color:rgba(255,255,255,.92);
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
    .actions{
      display:flex;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
      justify-content:flex-end;
    }
    .pill{
      text-decoration:none;
      display:inline-block;
      padding:8px 12px;
      border-radius:999px;
      border:1px solid rgba(255,255,255,.6);
      color:#fff;
      font-weight:800;
      font-size:13px;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans_ctx-serif;
      background:transparent;
    }
    .pill.active{
      background:var(--secondary);
      border-color:var(--secondary);
    }
    .pill.logout{
      background:rgba(0,0,0,.18);
      border-color:rgba(255,255,255,.35);
    }

    .grid{
      margin-top:16px;
      display:grid;
      grid-template-columns: 1fr;
      gap:14px;
      max-width:980px;
      margin-left:auto;
      margin-right:auto;
    }
    @media (min-width: 900px){
      .grid{grid-template-columns: 1fr 1fr;}
    }
    .card{
      background:var(--card);
      border:1px solid var(--border);
      border-radius:14px;
      box-shadow: 0 8px 30px rgba(0,0,0,.06);
      overflow:hidden;
    }
    .card h2{
      margin:0;
      padding:14px 16px;
      border-bottom:1px solid var(--border);
      color:var(--accent);
      font-size:14px;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
    .card .content{padding:14px 16px}
    .kvs{
      display:grid;
      grid-template-columns: 140px 1fr;
      gap:10px 12px;
      align-items:start;
      font-size:14px;
    }
    .kvs .k{
      color:#666;
      font-weight:700;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
    .kvs .v{
      font-weight:700;
    }
    .menu{
      display:flex;
      flex-direction:column;
      gap:10px;
    }
    .menu a{
      text-decoration:none;
      padding:12px 12px;
      border-radius:12px;
      border:1px solid var(--border);
      background:#fff;
      color:var(--accent);
      font-weight:800;
      font-size:14px;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
    .menu a:hover{
      border-color:rgba(62,132,106,.35);
      box-shadow:0 0 0 4px rgba(62,132,106,.10);
    }
    .tag{
      display:inline-block;
      padding:4px 10px;
      border-radius:999px;
      background:rgba(177,143,110,.18);
      border:1px solid var(--border);
      font-weight:800;
      font-size:12px;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="brand">
        <div class="app"><?php echo htmlspecialchars($tr['app']); ?></div>
        <div class="sub"><?php echo htmlspecialchars($tr['dashboard']); ?></div>
      </div>

      <div class="actions">
        <a class="pill <?php echo $lang==='ur' ? 'active' : ''; ?>" href="?lang=ur"><?php echo htmlspecialchars($tr['switch_ur']); ?></a>
        <a class="pill <?php echo $lang==='en' ? 'active' : ''; ?>" href="?lang=en"><?php echo htmlspecialchars($tr['switch_en']); ?></a>

        <!-- We'll create logout.php next -->
        <a class="pill logout" href="logout.php"><?php echo htmlspecialchars($tr['logout']); ?></a>
      </div>
    </div>

    <div class="grid">
      <div class="card">
        <h2><?php echo htmlspecialchars($tr['welcome']); ?></h2>
        <div class="content">
          <div class="kvs">
            <div class="k"><?php echo htmlspecialchars($tr['welcome']); ?></div>
            <div class="v"><?php echo htmlspecialchars($fullName); ?></div>

            <div class="k"><?php echo htmlspecialchars($tr['role']); ?></div>
            <div class="v"><span class="tag"><?php echo htmlspecialchars($roleLabel); ?></span></div>
          </div>
        </div>
      </div>

      <div class="card">
        <h2><?php echo htmlspecialchars($tr['menu']); ?></h2>
        <div class="content">
          <div class="menu">
            <?php foreach ($menu as $item): ?>
              <a href="<?php echo htmlspecialchars($item['href']); ?>"><?php echo htmlspecialchars($item['label']); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</body>
</html>
