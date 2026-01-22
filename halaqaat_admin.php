<?php
// halaqaat_admin.php  (FULL - SAME sidebar as dashboard_admin.php + mobile toggle works + Urdu font forced + dropdown fixed)
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
$isRtl = ($lang === 'ur');

$T = [
  'ur' => [
    'app' => 'کہف حلقات',
    'page' => 'حلقات مینجمنٹ',
    'dashboard' => 'ڈیش بورڈ',
    'search' => 'تلاش کریں...',
    'logout' => 'لاگ آؤٹ',
    'switch_en' => 'English',
    'switch_ur' => 'اردو',

    'nav_dashboard' => 'ڈیش بورڈ',
    'nav_halqaat' => 'حلقات',
    'nav_students' => 'طلباء',
    'nav_ustaaz' => 'اساتذہ',
    'nav_exams' => 'امتحانات',
    'nav_reports' => 'رپورٹس',
    'nav_settings' => 'ترتیبات',

    'add_new' => 'نئی حلقہ شامل کریں',
    'list' => 'تمام حلقات',
    'name_ur' => 'حلقہ نام (اردو)',
    'name_en' => 'حلقہ نام (English)',
    'gender' => 'گروپ',
    'boys' => 'طلباء',
    'girls' => 'طالبات',
    'session' => 'سیشن',
    'subah' => 'صبح',
    'asr' => 'عصر',
    'save' => 'محفوظ کریں',
    'status' => 'اسٹیٹس',
    'active' => 'فعال',
    'inactive' => 'غیر فعال',
    'created' => 'نئی حلقہ شامل ہوگئی ✅',
    'err_required' => 'اردو نام لازمی ہے۔',
    'err_db' => 'ڈیٹا محفوظ نہیں ہوا۔',
    'no_data' => 'ابھی تک کوئی ڈیٹا نہیں',

    // NEW
    'students_count' => 'طلبہ',
    'open' => 'کھولیں',
  ],
  'en' => [
    'app' => 'Kahf Halaqat',
    'page' => 'Halaqaat Management',
    'dashboard' => 'Dashboard',
    'search' => 'Search...',
    'logout' => 'Logout',
    'switch_en' => 'English',
    'switch_ur' => 'اردو',

    'nav_dashboard' => 'Dashboard',
    'nav_halqaat' => 'Halaqaat',
    'nav_students' => 'Students',
    'nav_ustaaz' => 'Ustaaz',
    'nav_exams' => 'Exams',
    'nav_reports' => 'Reports',
    'nav_settings' => 'Settings',

    'add_new' => 'Add New Halaqa',
    'list' => 'All Halaqaat',
    'name_ur' => 'Halaqa Name (Urdu)',
    'name_en' => 'Halaqa Name (English)',
    'gender' => 'Group',
    'boys' => 'Boys',
    'girls' => 'Girls',
    'session' => 'Session',
    'subah' => 'Subah',
    'asr' => 'Asr',
    'save' => 'Save',
    'status' => 'Status',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'created' => 'Halaqa created ✅',
    'err_required' => 'Urdu name is required.',
    'err_db' => 'Could not save data.',
    'no_data' => 'No data yet',

    // NEW
    'students_count' => 'Students',
    'open' => 'Open',
  ],
];

$tr = $T[$lang];
$msg = '';
$err = '';

// Normalize halaqa gender to final rule: boy/girl
function normalize_halaqa_gender($g) {
    $g = strtolower(trim((string)$g));
    // accept old values too
    if ($g === 'girls' || $g === 'girl') return 'girl';
    return 'boy'; // default boys
}
function is_girl_group($g) {
    $g = strtolower(trim((string)$g));
    return ($g === 'girl' || $g === 'girls');
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_ur = trim($_POST['name_ur'] ?? '');
    $name_en = trim($_POST['name_en'] ?? '');
    $gender_in  = $_POST['gender'] ?? 'boy';      // can be boys/girls from UI
    $session = $_POST['session'] ?? 'subah';
    $is_active = !empty($_POST['is_active']) ? 1 : 0;

    // enforce final values
    $gender  = normalize_halaqa_gender($gender_in); // boy/girl
    if (!in_array($session, ['subah','asr'], true)) $session = 'subah';

    if ($name_ur === '') {
        $err = $tr['err_required'];
    } else {
        // NOTE: halaqaat table columns: name_ur, name_en, gender, session, is_active
        $stmt = $conn->prepare("INSERT INTO halaqaat (name_ur, name_en, gender, session, is_active) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            $err = $tr['err_db'];
        } else {
            $stmt->bind_param("ssssi", $name_ur, $name_en, $gender, $session, $is_active);
            if ($stmt->execute()) {
                $msg = $tr['created'];
                $_POST = [];
            } else {
                $err = $tr['err_db'];
            }
            $stmt->close();
        }
    }
}

// Fetch list + students count (single query, no per-row queries)
$halaqaat = [];
$sql = "
  SELECT
    h.id, h.name_ur, h.name_en, h.gender, h.session, h.is_active, h.created_at,
    COUNT(s.id) AS students_count
  FROM halaqaat h
  LEFT JOIN students s ON s.halaqa_id = h.id
  GROUP BY h.id, h.name_ur, h.name_en, h.gender, h.session, h.is_active, h.created_at
  ORDER BY h.id DESC
";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) $halaqaat[] = $row;
    $res->free();
}
?>
<!doctype html>
<html lang="<?php echo h($lang); ?>" dir="<?php echo $isRtl ? 'rtl' : 'ltr'; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">

  <title><?php echo h($tr['page']); ?> — <?php echo h($tr['app']); ?></title>

  <style>
    /* ===== English mode: Montserrat everywhere ===== */
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

    /* ===== Urdu mode: Noto Nastaliq everywhere ===== */
    html[lang="ur"] body,
    html[lang="ur"] .sidebar,
    html[lang="ur"] .main,
    html[lang="ur"] .pill,
    html[lang="ur"] .nav a,
    html[lang="ur"] .cardHeader,
    html[lang="ur"] input,
    html[lang="ur"] select,
    html[lang="ur"] option,
    html[lang="ur"] button,
    html[lang="ur"] table,
    html[lang="ur"] th,
    html[lang="ur"] td {
      font-family:'Noto Nastaliq Urdu', serif !important;
    }

    :root{
      --primary:#3e846a;
      --secondary:#b18f6e;
      --accent:#444444;
      --bg:#f6f2ee;
      --card:#ffffff;
      --border:#e7ddd4;

      --sidebar:#3b3b3b;
      --sidebar2:#2f2f2f;

      --boy:#2f6fd6;
      --girl:#d24e8a;
    }

    *{box-sizing:border-box}
    body{
      margin:0;
      background:var(--bg);
      color:var(--accent);
    }

    /* Layout */
    .layout{
      min-height:100vh;
      display:grid;
      grid-template-columns: 280px 1fr;
    }

    /* Sidebar */
    .sidebar{
      background:linear-gradient(180deg, var(--sidebar), var(--sidebar2));
      color:#fff;
      padding:16px;
      position:sticky;
      top:0;
      height:100vh;
      overflow:auto;
    }
    .brand{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      padding:8px 8px 16px;
      border-bottom:1px solid rgba(255,255,255,.12);
      margin-bottom:12px;
    }
    .brand .name{
      font-weight:900;
      font-size:16px;
      letter-spacing:.4px;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
    .brand .sub{
      font-weight:700;
      font-size:12px;
      opacity:.85;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }

    .sideToggle{
      border:1px solid rgba(255,255,255,.25);
      background:rgba(255,255,255,.08);
      color:#fff;
      padding:8px 10px;
      border-radius:12px;
      font-weight:900;
      cursor:pointer;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
    .sideToggle:hover{ background:rgba(255,255,255,.12); }

    /* PC collapsible sidebar behavior */
    .layout.collapsed{
      grid-template-columns: 90px 1fr;
    }
    .layout.collapsed .brand .name,
    .layout.collapsed .brand .sub,
    .layout.collapsed .nav a span.txt,
    .layout.collapsed .sidebarBottom{
      display:none;
    }
    .layout.collapsed .nav a{
      justify-content:center;
      padding:12px;
    }
    .layout.collapsed .nav a .ico{
      width:22px;
      display:inline-flex;
      justify-content:center;
      align-items:center;
    }
    .nav a{ gap:10px; }

    .nav{
      display:flex;
      flex-direction:column;
      gap:8px;
      margin-top:10px;
    }
    .nav a{
      text-decoration:none;
      color:#fff;
      padding:10px 12px;
      border-radius:12px;
      font-weight:800;
      font-size:13px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      background:transparent;
      border:1px solid rgba(255,255,255,.10);

      position:relative;
      padding-left:40px;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }

    html[dir="rtl"] .nav a{
      padding-left:12px;
      padding-right:40px;
    }

    .nav a.active{
      background:rgba(255,255,255,.10);
      border-color:rgba(255,255,255,.18);
    }
    .nav a:hover{
      background:rgba(255,255,255,.08);
    }

    .sidebarBottom{
      margin-top:14px;
      padding-top:14px;
      border-top:1px solid rgba(255,255,255,.12);
      display:flex;
      flex-direction:column;
      gap:10px;
    }

    /* Sidebar icons */
    .nav a::before{
      content:'';
      position:absolute;
      width:16px;
      height:16px;
      background:rgba(255,255,255,.9);
      mask-size:contain;
      mask-repeat:no-repeat;
      mask-position:center;
      -webkit-mask-size:contain;
      -webkit-mask-repeat:no-repeat;
      -webkit-mask-position:center;
    }
    html[dir="ltr"] .nav a::before{ left:12px; }
    html[dir="rtl"] .nav a::before{ right:12px; }

    .nav a.dash::before{
      -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>');
    }
    .nav a.halaqa::before{
      -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>');
    }
    .nav a.students::before{
      -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>');
    }
    .nav a.ustaaz::before{
      -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>');
    }
    .nav a.exams::before{
      -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>');
    }
    .nav a.reports::before{
      -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm4 0h14v-2H7v2zm0-4h14v-2H7v2z"/></svg>');
    }
    .nav a.settings::before{
      -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.11-.2-.36-.28-.57-.22l-2.39.96c-.5-.38-1.04-.7-1.64-.94L14.5 2h-5l-.37 2.35c-.6.24-1.14.56-1.64.94l-2.39-.96c-.21-.06-.46.02-.57.22L2.61 7.87c-.11.2-.06.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94L2.73 14.52c-.18.14-.23.41-.12.61l1.92 3.32c.11.2.36.28.57.22l2.39-.96c.5.38 1.04.7 1.64.94L9.5 22h5l.37-2.35c.6-.24 1.14-.56 1.64-.94l2.39.96c.21.06.46-.02.57-.22l1.92-3.32c.11-.2.06-.47-.12-.61l-2.03-1.58z"/></svg>');
    }

    /* Main */
    .main{ padding:18px; }

    /* Topbar */
    .topbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      flex-wrap:wrap;
      margin-bottom:14px;
    }
    .titleBox{
      background:linear-gradient(90deg, rgba(68,68,68,.92), rgba(68,68,68,.70));
      color:#fff;
      border-radius:16px;
      padding:16px 18px;
      border-bottom:4px solid var(--secondary);
      flex:1;
      min-width:260px;
    }
    .titleBox .t{ margin:0; font-size:18px; font-weight:900; }
    .titleBox .s{ margin:6px 0 0; opacity:.92; font-size:13px; line-height:1.7; }

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
      color:#fff;
    }
    .pill.logout{
      background:var(--primary);
      color:#fff;
      border-color:var(--primary);
    }

    /* Cards */
    .grid{display:grid; grid-template-columns: 1fr 1fr; gap:12px;}
    @media (max-width: 980px){ .grid{grid-template-columns:1fr;} }

    .card{
      background:#fff;
      border:1px solid var(--border);
      border-radius:16px;
      box-shadow:0 6px 18px rgba(0,0,0,.04);
      overflow:hidden;
    }
    .cardHeader{
      padding:12px 14px;
      border-bottom:1px solid var(--border);
      font-weight:900;
      font-size:13px;
    }
    .cardBody{padding:14px;}

    label{display:block; margin:10px 0 6px; font-weight:800; font-size:14px;}

    input, select{
      width:100%;
      padding:10px 12px;
      border:1px solid var(--border);
      border-radius:12px;
      outline:none;
      background:#fff;
      font-size:14px;
    }

    /* Dropdown arrow + RTL/LTR padding */
    select{
      appearance:none;
      -webkit-appearance:none;
      -moz-appearance:none;
      background-repeat:no-repeat;
      background-size:14px 14px;
      background-position: calc(100% - 12px) 50%;
      padding-right:38px;
      background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><path fill='%23666' d='M7 10l5 5 5-5z'/></svg>");
    }
    html[dir="rtl"] select{
      background-position: 12px 50%;
      padding-right:12px;
      padding-left:38px;
      text-align:right;
    }
    html[dir="ltr"] select{ text-align:left; }

    input:focus, select:focus{
      border-color:rgba(62,132,106,.55);
      box-shadow:0 0 0 4px rgba(62,132,106,.12);
    }

    .row2{display:grid; grid-template-columns: 1fr 1fr; gap:10px;}
    @media (max-width: 520px){ .row2{grid-template-columns:1fr;} }

    .btn{
      width:100%;
      margin-top:14px;
      padding:12px;
      border:0;
      border-radius:12px;
      background:var(--primary);
      color:#fff;
      font-weight:900;
      cursor:pointer;
      font-size:15px;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }

    .msg{
      padding:10px 12px;
      border-radius:12px;
      background:rgba(62,132,106,.10);
      border:1px solid rgba(62,132,106,.25);
      font-weight:900;
      margin-bottom:12px;
    }
    .err{
      padding:10px 12px;
      border-radius:12px;
      background:#fff3f3;
      border:1px solid #f2c7c7;
      color:#8a1f1f;
      font-weight:900;
      margin-bottom:12px;
    }

    table{width:100%; border-collapse:separate; border-spacing:0; font-size:13px;}
    th, td{padding:10px 10px; border-bottom:1px solid var(--border); text-align:start; vertical-align:top;}
    th{font-weight:900; color:#555; background:rgba(177,143,110,.10);}

    .tag{
        /* ===== Tag Colors (requested) ===== */
.tag.boy{
  background: rgba(47,111,214,.18);
  border-color: rgba(47,111,214,.55);
  color: #1b3f86;
}
.tag.girl{
  background: rgba(210,78,138,.18);
  border-color: rgba(210,78,138,.55);
  color: #7a1f4a;
}
.tag.subah{
  background: rgba(255,140,0,.22);
  border-color: rgba(255,140,0,.60);
  color: #6a3a00;
}
.tag.asr{
  background: rgba(120,70,20,.22);
  border-color: rgba(120,70,20,.60);
  color: #4a2a0d;
}
      display:inline-block;
      padding:4px 10px;
      border-radius:999px;
      border:1px solid var(--border);
      font-weight:900;
      font-size:12px;
      background:#fff;
      white-space:nowrap;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
    .tag.primary{background:rgba(62,132,106,.12); border-color:rgba(62,132,106,.35);}
    .tag.secondary{background:rgba(177,143,110,.14); border-color:rgba(177,143,110,.45);}

    .checkRow{display:flex; align-items:center; gap:10px; margin-top:10px;}
    .checkRow input{width:auto; transform:scale(1.1);}

    /* NEW: small action button */
    .btnMini{
      display:inline-block;
      padding:6px 10px;
      border-radius:10px;
      border:1px solid var(--border);
      background:#fff;
      font-weight:900;
      text-decoration:none;
      color:var(--accent);
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
      white-space:nowrap;
    }
    .btnMini:hover{ background:rgba(62,132,106,.08); border-color:rgba(62,132,106,.25); }

    /* Mobile sidebar */
    .menuBtn{
      display:none;
      background:#3e846a;
      border:1px solid rgba(255,255,255,.6);
      color:#ffffff;
      padding:10px 12px;
      border-radius:12px;
      font-weight:900;
      font-size:18px;
      cursor:pointer;
      line-height:1;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
    .menuBtn:focus,
    .menuBtn:active{ outline:none; box-shadow:none; }

    @media (max-width: 980px){
      .layout{grid-template-columns: 1fr;}
      .main{padding:16px;}
      .menuBtn{display:inline-block;}

      .sidebar{
        position:fixed;
        z-index:50;
        top:0; bottom:0;
        width:280px;
        overflow:auto;
        transition:transform .2s ease;
      }

      html[dir="ltr"] .sidebar{
        left:0;
        transform:translateX(-110%);
      }
      html[dir="rtl"] .sidebar{
        right:0;
        transform:translateX(110%);
      }

      .sidebar.open{
        transform:translateX(0) !important;
      }

      .overlay{
        display:none;
        position:fixed;
        inset:0;
        background:rgba(0,0,0,.35);
        z-index:40;
      }
      .overlay.show{display:block;}
    }
  </style>
</head>

<body>
  <div class="overlay" id="overlay" onclick="toggleSidebar(false)"></div>

  <div class="layout">
    <!-- Sidebar (SAME as dashboard_admin.php) -->
    <aside class="sidebar" id="sidebar">
      <div class="brand">
        <div>
          <div class="name"><?php echo h($tr['app']); ?></div>
          <div class="sub"><?php echo h($tr['page']); ?></div>
        </div>
        <button class="sideToggle" type="button" onclick="toggleSidebar()">☰</button>
      </div>

      <nav class="nav">
        <a class="dash" href="dashboard_admin.php"><span class="txt"><?php echo h($tr['nav_dashboard']); ?></span></a>
        <a class="active halaqa" href="halaqaat_admin.php"><span class="txt"><?php echo h($tr['nav_halqaat']); ?></span></a>

        <!-- CHANGED: no href="#" -->
        <a class="students" href="students_admin.php"><span class="txt"><?php echo h($tr['nav_students']); ?></span></a>

        <!-- left as-is for now (modules not created yet) -->
        <a class="ustaaz" href="#"><span class="txt"><?php echo h($tr['nav_ustaaz']); ?></span></a>
        <a class="exams" href="#"><span class="txt"><?php echo h($tr['nav_exams']); ?></span></a>
        <a class="reports" href="#"><span class="txt"><?php echo h($tr['nav_reports']); ?></span></a>
        <a class="settings" href="#"><span class="txt"><?php echo h($tr['nav_settings']); ?></span></a>
      </nav>

      <div class="sidebarBottom">
        <a class="navLink pill logout" style="text-align:center;" href="logout.php"><?php echo h($tr['logout']); ?></a>
      </div>
    </aside>

    <!-- Main -->
    <main class="main">
      <div class="topbar">
        <button class="menuBtn" type="button" onclick="toggleSidebar()">☰</button>

        <div class="titleBox">
          <h1 class="t"><?php echo h($tr['page']); ?></h1>
          <p class="s"><?php echo h($tr['app']); ?></p>
        </div>

        <div class="controls">
          <a class="pill <?php echo $lang==='ur' ? 'active' : ''; ?>" href="?lang=ur"><?php echo h($tr['switch_ur']); ?></a>
          <a class="pill <?php echo $lang==='en' ? 'active' : ''; ?>" href="?lang=en"><?php echo h($tr['switch_en']); ?></a>
          <a class="pill logout" href="logout.php"><?php echo h($tr['logout']); ?></a>
        </div>
      </div>

      <div class="grid">
        <section class="card">
          <div class="cardHeader"><?php echo h($tr['add_new']); ?></div>
          <div class="cardBody">

            <?php if ($msg): ?><div class="msg"><?php echo h($msg); ?></div><?php endif; ?>
            <?php if ($err): ?><div class="err"><?php echo h($err); ?></div><?php endif; ?>

            <form method="post" autocomplete="off">
              <label><?php echo h($tr['name_ur']); ?></label>
              <input name="name_ur" value="<?php echo h($_POST['name_ur'] ?? ''); ?>" required>

              <label><?php echo h($tr['name_en']); ?></label>
              <input name="name_en" value="<?php echo h($_POST['name_en'] ?? ''); ?>">

              <div class="row2">
                <div>
                  <label><?php echo h($tr['gender']); ?></label>

                  <!-- CHANGED: store as boy/girl (still shows Urdu labels) -->
                  <select name="gender">
                    <?php $gSel = normalize_halaqa_gender($_POST['gender'] ?? 'boy'); ?>
                    <option value="boy"  <?php echo ($gSel==='boy')?'selected':'';  ?>><?php echo h($tr['boys']); ?></option>
                    <option value="girl" <?php echo ($gSel==='girl')?'selected':''; ?>><?php echo h($tr['girls']); ?></option>
                  </select>
                </div>

                <div>
                  <label><?php echo h($tr['session']); ?></label>
                  <select name="session">
                    <option value="subah" <?php echo (($_POST['session'] ?? 'subah')==='subah')?'selected':''; ?>><?php echo h($tr['subah']); ?></option>
                    <option value="asr" <?php echo (($_POST['session'] ?? '')==='asr')?'selected':''; ?>><?php echo h($tr['asr']); ?></option>
                  </select>
                </div>
              </div>

              <div class="checkRow">
                <input id="is_active" type="checkbox" name="is_active" value="1" <?php echo !empty($_POST['is_active']) ? 'checked' : ''; ?>>
                <label for="is_active" style="margin:0;"><?php echo h($tr['active']); ?></label>
              </div>

              <button class="btn" type="submit"><?php echo h($tr['save']); ?></button>
            </form>
          </div>
        </section>

        <section class="card">
          <div class="cardHeader"><?php echo h($tr['list']); ?></div>
          <div class="cardBody" style="overflow:auto;">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th><?php echo h($tr['name_ur']); ?></th>
                  <th><?php echo h($tr['gender']); ?></th>
                  <th><?php echo h($tr['session']); ?></th>

                  <!-- NEW -->
                  <th><?php echo h($tr['students_count']); ?></th>
                  <th><?php echo h($tr['open']); ?></th>

                  <th><?php echo h($tr['status']); ?></th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($halaqaat)): ?>
                <tr><td colspan="7" style="color:#777; padding:14px;"><?php echo h($tr['no_data']); ?></td></tr>
              <?php else: ?>
                <?php foreach ($halaqaat as $hrow): ?>
                  <?php
                    $hid = (int)$hrow['id'];
                    $isGirl = is_girl_group($hrow['gender'] ?? '');
                    $studentsCount = (int)($hrow['students_count'] ?? 0);
                  ?>
                  <tr>
                    <td><?php echo $hid; ?></td>
                    <td>
                      <div style="font-weight:900;"><?php echo h($hrow['name_ur']); ?></div>
                      <?php if (!empty($hrow['name_en'])): ?>
                        <div style="font-size:12px; color:#666;"><?php echo h($hrow['name_en']); ?></div>
                      <?php endif; ?>
                    </td>
                   <td>
  <?php if ($isGirl): ?>
    <span class="tag girl"><?php echo h($tr['girls']); ?></span>
  <?php else: ?>
    <span class="tag boy"><?php echo h($tr['boys']); ?></span>
  <?php endif; ?>
</td>
                    <td>
  <?php if (($hrow['session'] ?? '') === 'asr'): ?>
    <span class="tag asr"><?php echo h($tr['asr']); ?></span>
  <?php else: ?>
    <span class="tag subah"><?php echo h($tr['subah']); ?></span>
  <?php endif; ?>
</td>


                    <!-- NEW: students count -->
                    <td><span class="tag"><?php echo $studentsCount; ?></span></td>

                    <!-- NEW: open halaqa in new tab -->
                    <td>
                      <a class="btnMini" href="halaqa_view.php?id=<?php echo $hid; ?>" target="_blank" rel="noopener">
                        <?php echo h($tr['open']); ?>
                      </a>
                    </td>

                    <td>
                      <?php if ((int)$hrow['is_active'] === 1): ?>
                        <span class="tag primary"><?php echo h($tr['active']); ?></span>
                      <?php else: ?>
                        <span class="tag"><?php echo h($tr['inactive']); ?></span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </main>
  </div>

  <script>
    function isMobile() { return window.innerWidth <= 980; }

    function toggleSidebar(forceOpen) {
      var sb = document.getElementById('sidebar');
      var ov = document.getElementById('overlay');
      var layout = document.querySelector('.layout');
      if (!sb || !ov || !layout) return;

      if (isMobile()) {
        var open = typeof forceOpen === 'boolean' ? forceOpen : !sb.classList.contains('open');
        if (open) {
          sb.classList.add('open');
          ov.classList.add('show');
        } else {
          sb.classList.remove('open');
          ov.classList.remove('show');
        }
      } else {
        layout.classList.toggle('collapsed');
        sb.classList.remove('open');
        ov.classList.remove('show');
      }
    }

    window.addEventListener('resize', function () {
      var sb = document.getElementById('sidebar');
      var ov = document.getElementById('overlay');
      var layout = document.querySelector('.layout');
      if (!sb || !ov || !layout) return;

      if (!isMobile()) {
        sb.classList.remove('open');
        ov.classList.remove('show');
      } else {
        layout.classList.remove('collapsed');
      }
    });
  </script>

</body>
</html>
