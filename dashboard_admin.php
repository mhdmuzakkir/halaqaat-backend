<?php
// dashboard_admin.php
require_once __DIR__ . '/db.php';

session_name(defined('SESSION_NAME') ? SESSION_NAME : 'kahaf_session');
session_start();

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    // Non-admins go back to router
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
        'section_recent_halqaat' => 'حلقات (نمونہ کارڈز)',
        'section_top_students' => 'امتیاز طلباء (نمونہ)',

        'boys' => 'طلباء',
        'girls' => 'طالبات',
        'hifz' => 'حفظ',
        'nazirah' => 'ناظرہ',
        'qaida' => 'قاعدہ',
        'subah' => 'صبح',
        'asr' => 'عصر',
        'view_all' => 'سب دیکھیں',
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
        'section_recent_halqaat' => 'Halaqaat (Sample Cards)',
        'section_top_students' => 'Top Students (Sample)',

        'boys' => 'Boys',
        'girls' => 'Girls',
        'hifz' => 'Hifz',
        'nazirah' => 'Nazirah',
        'qaida' => 'Qaida',
        'subah' => 'Subah',
        'asr' => 'Asr',
        'view_all' => 'View all',
    ]
];

$isRtl = ($lang === 'ur');
$tr = $T[$lang];

$fullName = $_SESSION['full_name'] ?? 'Admin';

// NOTE: For now these are placeholders.
// Next step we will connect real DB counts (halaqaat, students, etc.)
$stats = [
    'halqaat' => 22,
    'students' => 294,
    'ustaaz' => 24,
    'upcoming_exams' => 2,
];

// Sample cards (we’ll replace with DB later)
$sampleHalqaat = [
    [
        'title_ur' => 'حلقۃ الفاتحہ (طلباء)',
        'title_en' => 'Al-Fatiha Boys',
        'shuba' => 'hifz',
        'gender' => 'boys',
        'count' => 14,
        'ustaaz_ur' => 'محمد احمد',
        'ustaaz_en' => 'Muhammad Ahmed',
        'subah' => '08:00',
        'asr' => '16:30',
    ],
    [
        'title_ur' => 'حلقۃ النور (طالبات)',
        'title_en' => 'Al-Noor Girls',
        'shuba' => 'nazirah',
        'gender' => 'girls',
        'count' => 14,
        'ustaaz_ur' => 'فاطمہ زہرا',
        'ustaaz_en' => 'Fatima Zahra',
        'subah' => '09:00',
        'asr' => '17:00',
    ],
    [
        'title_ur' => 'حلقۃ البقرہ (طلباء)',
        'title_en' => 'Al-Baqarah Boys',
        'shuba' => 'qaida',
        'gender' => 'boys',
        'count' => 12,
        'ustaaz_ur' => 'قاری بلال',
        'ustaaz_en' => 'Qari Bilal',
        'subah' => '07:30',
        'asr' => '16:00',
    ],
];

$sampleTop = [
    ['name_ur'=>'عثمان طارق', 'name_en'=>'Usman Tariq', 'shuba'=>'hifz', 'score'=>96],
    ['name_ur'=>'عائشہ ملک', 'name_en'=>'Ayesha Malik', 'shuba'=>'nazirah', 'score'=>94],
    ['name_ur'=>'عبداللہ خان', 'name_en'=>'Abdullah Khan', 'shuba'=>'hifz', 'score'=>91],
];
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
    /* English mode: use Montserrat everywhere */
html[lang="en"] body,
html[lang="en"] .sidebar,
html[lang="en"] .main,
html[lang="en"] .pill,
html[lang="en"] .nav a,
html[lang="en"] .cardHeader,
html[lang="en"] .stat .label,
html[lang="en"] .stat .val,
html[lang="en"] input {
  font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
}

    :root{
      --primary:#3e846a;      /* green */
      --secondary:#b18f6e;    /* golden brown */
      --accent:#444444;       /* charcoal */
      --bg:#f6f2ee;
      --card:#ffffff;
      --border:#e7ddd4;

      --boy:#2f6fd6;          /* allowed blue for boys */
      --girl:#d24e8a;         /* pink for girls */

      --sidebar:#3b3b3b;      /* keep sidebar dark (not green/gold) */
      --sidebar2:#2f2f2f;
    }

    *{box-sizing:border-box}

    body{
      margin:0;
      background:var(--bg);
      color:var(--accent);
      font-family:'Noto Nastaliq Urdu', serif;
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
    }
    .brand .sub{
      font-weight:700;
      font-size:12px;
      opacity:.85;
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
.nav a{
  gap:10px;
}

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
    /* Sidebar icons (white, simple, no emoji) */
.nav a{
  position:relative;
  padding-left:40px;
}

html[dir="rtl"] .nav a{
  padding-left:12px;
  padding-right:40px;
}

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

/* LTR icon position */
html[dir="ltr"] .nav a::before{
  left:12px;
}

/* RTL icon position */
html[dir="rtl"] .nav a::before{
  right:12px;
}

/* Individual icons */
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
    .main{
      padding:18px;
    }

    /* Topbar (search + controls) */
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
      background:rgba(177,143,110,.18);
      border-color:rgba(177,143,110,.55);
    }
    .pill.logout{
      background:var(--primary);
      color:#fff;
      border-color:var(--primary);
    }

    /* Hero */
    .hero{
      background:linear-gradient(90deg, rgba(68,68,68,.92), rgba(68,68,68,.70));
      color:#fff;
      border-radius:16px;
      padding:20px;
      border-bottom:4px solid var(--secondary);
      margin-bottom:14px;
      position:relative;
      overflow:hidden;
    }
    .hero h1{
      margin:0 0 8px 0;
      font-size:24px;
      font-weight:900;
    }
    .hero p{
      margin:0;
      opacity:.92;
      line-height:1.8;
      font-size:14px;
    }
    html[lang="en"] .hero h1,
    html[lang="en"] .hero p{
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
      line-height:1.4;
    }

    /* Stat cards */
    .stats{
      display:grid;
      grid-template-columns: repeat(4, minmax(0,1fr));
      gap:12px;
      margin-bottom:14px;
    }
    @media (max-width: 1100px){
      .stats{grid-template-columns: repeat(2, minmax(0,1fr));}
    }
    @media (max-width: 520px){
      .stats{grid-template-columns: 1fr;}
    }
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
    .stat .label{
      color:#666;
      font-weight:800;
      font-size:13px;
    }
    .stat .val{
      font-size:24px;
      font-weight:900;
      color:var(--accent);
    }
    .stat.primary{border-left:6px solid var(--primary);}
    .stat.secondary{border-left:6px solid var(--secondary);}

    /* Content grid */
    .grid{
      display:grid;
      grid-template-columns: 1.2fr .8fr;
      gap:12px;
    }
    @media (max-width: 980px){
      .grid{grid-template-columns: 1fr;}
    }

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

    /* Lists */
    .list{
      display:flex;
      flex-direction:column;
      gap:10px;
    }
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
    .row .left{
      display:flex;
      flex-direction:column;
      gap:4px;
      min-width:0;
    }
    .row .title{
      font-weight:900;
      font-size:14px;
      color:var(--accent);
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    html[lang="en"] .row .title{
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
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

    /* Halaqa cards */
    .halaqaGrid{
      display:grid;
      grid-template-columns: repeat(3, minmax(0,1fr));
      gap:12px;
    }
    @media (max-width: 1100px){
      .halaqaGrid{grid-template-columns: repeat(2, minmax(0,1fr));}
    }
    @media (max-width: 600px){
      .halaqaGrid{grid-template-columns: 1fr;}
    }
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
    html[lang="en"] .halaqaTitle{
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

    /* Mobile sidebar */
    .menuBtn{
      display:none;
      border:1px solid var(--border);
      background:#fff;
      padding:10px 12px;
      border-radius:12px;
      font-weight:900;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
      cursor:pointer;
    }

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

  /* LTR (English): hide to the LEFT */
  html[dir="ltr"] .sidebar{
    left:0;
    transform:translateX(-110%);
  }

  /* RTL (Urdu): hide to the RIGHT */
  html[dir="rtl"] .sidebar{
    right:0;
    transform:translateX(110%);
  }

  /* Open state (both) */
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
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
     <div class="brand">
  <div>
    <div class="name"><?php echo htmlspecialchars($tr['app']); ?></div>
    <div class="sub"><?php echo htmlspecialchars($tr['dashboard']); ?></div>
  </div>
  <button class="sideToggle" type="button" onclick="toggleSidebar()">
    ☰
  </button>
</div>


<nav class="nav">
  <a class="active dash" href="dashboard_admin.php"><span class="txt"><?php echo htmlspecialchars($tr['nav_dashboard']); ?></span></a>
  <a class="halaqa" href="#"><span class="txt"><?php echo htmlspecialchars($tr['nav_halqaat']); ?></span></a>
  <a class="students" href="#"><span class="txt"><?php echo htmlspecialchars($tr['nav_students']); ?></span></a>
  <a class="ustaaz" href="#"><span class="txt"><?php echo htmlspecialchars($tr['nav_ustaaz']); ?></span></a>
  <a class="exams" href="#"><span class="txt"><?php echo htmlspecialchars($tr['nav_exams']); ?></span></a>
  <a class="reports" href="#"><span class="txt"><?php echo htmlspecialchars($tr['nav_reports']); ?></span></a>
  <a class="settings" href="#"><span class="txt"><?php echo htmlspecialchars($tr['nav_settings']); ?></span></a>
</nav>



      <div class="sidebarBottom">
        <a class="navLink pill logout" style="text-align:center;" href="logout.php"><?php echo htmlspecialchars($tr['logout']); ?></a>
      </div>
    </aside>

    <!-- Main -->
    <main class="main">
      <div class="topbar">
<button class="menuBtn" type="button" onclick="toggleSidebar()">☰</button>

        <div class="search">
          <input type="text" placeholder="<?php echo htmlspecialchars($tr['search']); ?>">
        </div>

        <div class="controls">
          <a class="pill <?php echo $lang==='ur' ? 'active' : ''; ?>" href="?lang=ur"><?php echo htmlspecialchars($tr['switch_ur']); ?></a>
          <a class="pill <?php echo $lang==='en' ? 'active' : ''; ?>" href="?lang=en"><?php echo htmlspecialchars($tr['switch_en']); ?></a>
          <a class="pill logout" href="logout.php"><?php echo htmlspecialchars($tr['logout']); ?></a>
        </div>
      </div>

      <section class="hero">
        <h1><?php echo htmlspecialchars($tr['welcome']); ?></h1>
        <p><?php echo htmlspecialchars($tr['welcome_sub']); ?> — <?php echo htmlspecialchars($fullName); ?></p>
      </section>

      <!-- Stats -->
      <section class="stats">
        <div class="stat secondary">
          <div class="label"><?php echo htmlspecialchars($tr['stat_upcoming_exams']); ?></div>
          <div class="val"><?php echo (int)$stats['upcoming_exams']; ?></div>
        </div>
        <div class="stat primary">
          <div class="label"><?php echo htmlspecialchars($tr['stat_total_ustaaz']); ?></div>
          <div class="val"><?php echo (int)$stats['ustaaz']; ?></div>
        </div>
        <div class="stat secondary">
          <div class="label"><?php echo htmlspecialchars($tr['stat_total_students']); ?></div>
          <div class="val"><?php echo (int)$stats['students']; ?></div>
        </div>
        <div class="stat primary">
          <div class="label"><?php echo htmlspecialchars($tr['stat_total_halqaat']); ?></div>
          <div class="val"><?php echo (int)$stats['halqaat']; ?></div>
        </div>
      </section>

      <section class="grid">
        <!-- Left column -->
        <div class="card">
          <div class="cardHeader">
            <span><?php echo htmlspecialchars($tr['section_recent_halqaat']); ?></span>
            <span class="badge secondary"><?php echo htmlspecialchars($tr['view_all']); ?></span>
          </div>
          <div class="cardBody">
            <div class="halaqaGrid">
              <?php foreach ($sampleHalqaat as $h): ?>
                <div class="halaqaCard">
                  <div class="halaqaTitle">
                    <?php echo htmlspecialchars($lang==='ur' ? $h['title_ur'] : $h['title_en']); ?>
                  </div>
                  <div class="halaqaMeta">
                    <span class="badge <?php echo $h['gender']==='boys' ? 'boy' : 'girl'; ?>">
                      <?php echo htmlspecialchars($h['gender']==='boys' ? $tr['boys'] : $tr['girls']); ?>
                    </span>
                    <span class="badge secondary">
                      <?php
                        $sh = $h['shuba'];
                        echo htmlspecialchars($tr[$sh] ?? $sh);
                      ?>
                    </span>
                    <span class="badge primary"><?php echo (int)$h['count']; ?></span>
                  </div>

                  <div class="halaqaFoot">
                    <span><?php echo htmlspecialchars($lang==='ur' ? $h['ustaaz_ur'] : $h['ustaaz_en']); ?></span>
                    <span><?php echo htmlspecialchars($tr['subah']); ?> <?php echo htmlspecialchars($h['subah']); ?> • <?php echo htmlspecialchars($tr['asr']); ?> <?php echo htmlspecialchars($h['asr']); ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Right column -->
        <div style="display:flex; flex-direction:column; gap:12px;">
          <div class="card">
            <div class="cardHeader">
              <span><?php echo htmlspecialchars($tr['section_upcoming']); ?></span>
              <span class="badge secondary"><?php echo htmlspecialchars($tr['view_all']); ?></span>
            </div>
            <div class="cardBody">
              <div class="list">
                <div class="row">
                  <div class="left">
                    <div class="title">2026-02-05 — <?php echo htmlspecialchars($tr['hifz']); ?></div>
                    <div class="meta">
                      <span class="badge primary"><?php echo htmlspecialchars($tr['boys']); ?></span>
                      <span class="badge secondary">10:00</span>
                    </div>
                  </div>
                  <span class="badge secondary">+<?php echo (int)$stats['upcoming_exams']; ?></span>
                </div>

                <div class="row">
                  <div class="left">
                    <div class="title">2026-02-08 — <?php echo htmlspecialchars($tr['nazirah']); ?></div>
                    <div class="meta">
                      <span class="badge primary"><?php echo htmlspecialchars($tr['girls']); ?></span>
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
              <span><?php echo htmlspecialchars($tr['section_gender']); ?></span>
            </div>
            <div class="cardBody">
              <div class="list">
                <div class="row">
                  <div class="left">
                    <div class="title"><?php echo htmlspecialchars($tr['boys']); ?></div>
                    <div class="meta"><span class="badge boy">Sample</span></div>
                  </div>
                  <span class="badge boy">154</span>
                </div>
                <div class="row">
                  <div class="left">
                    <div class="title"><?php echo htmlspecialchars($tr['girls']); ?></div>
                    <div class="meta"><span class="badge girl">Sample</span></div>
                  </div>
                  <span class="badge girl">140</span>
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="cardHeader">
              <span><?php echo htmlspecialchars($tr['section_top_students']); ?></span>
            </div>
            <div class="cardBody">
              <div class="list">
                <?php foreach ($sampleTop as $i => $s): ?>
                  <div class="row">
                    <div class="left">
                      <div class="title">
                        <?php echo htmlspecialchars(($i+1) . '. ' . ($lang==='ur' ? $s['name_ur'] : $s['name_en'])); ?>
                      </div>
                      <div class="meta">
                        <span class="badge secondary"><?php echo htmlspecialchars($tr[$s['shuba']] ?? $s['shuba']); ?></span>
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

 <script>
  function isMobile() {
    return window.innerWidth <= 980;
  }

  function toggleSidebar(forceOpen) {
    var sb = document.getElementById('sidebar');
    var ov = document.getElementById('overlay');
    var layout = document.querySelector('.layout');
    if (!sb || !ov || !layout) return;

    if (isMobile()) {
      // Mobile: slide-in sidebar
      var open = typeof forceOpen === 'boolean' ? forceOpen : !sb.classList.contains('open');
      if (open) {
        sb.classList.add('open');
        ov.classList.add('show');
      } else {
        sb.classList.remove('open');
        ov.classList.remove('show');
      }
    } else {
      // PC: collapse/expand sidebar
      layout.classList.toggle('collapsed');
      // ensure mobile overlay is closed
      sb.classList.remove('open');
      ov.classList.remove('show');
    }
  }

  // close on overlay click
  document.getElementById('overlay')?.addEventListener('click', function () {
    toggleSidebar(false);
  });

  // On resize: reset mobile overlay
  window.addEventListener('resize', function () {
    var sb = document.getElementById('sidebar');
    var ov = document.getElementById('overlay');
    var layout = document.querySelector('.layout');
    if (!sb || !ov || !layout) return;

    if (!isMobile()) {
      sb.classList.remove('open');
      ov.classList.remove('show');
    } else {
      // On mobile, keep PC collapsed state off
      layout.classList.remove('collapsed');
    }
  });
</script>

</body>
</html>
