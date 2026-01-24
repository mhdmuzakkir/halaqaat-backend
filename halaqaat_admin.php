<?php
// halaqaat_admin.php  (UPDATED per your UI rules)
// CHANGES DONE:
// 1) Tags are now small rounded rectangles (NOT circles)
// 2) Halaqa name bigger + Ustaaz name directly under title
// 3) Language toggle is a single pill: "ار | EN" (green active for EN, secondary active for UR)
// 4) Total Halaqaat pill = green, Total Students pill = secondary; filter bar bg = primary
// 5) Search is REAL-TIME (JS filters cards as you type). Server search still supported with ?q= but not required.

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
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowedLang, true)) $_SESSION['lang'] = $_GET['lang'];
$lang  = $_SESSION['lang'] ?? 'ur';
$isRtl = ($lang === 'ur');

$T = [
  'ur' => [
    'app' => 'کہف حلقات',
    'page' => 'حلقات',
    'search' => 'حلقہ کا نام تلاش کریں...',
    'logout' => 'لاگ آؤٹ',

    'nav_dashboard' => 'ڈیش بورڈ',
    'nav_halqaat' => 'حلقات',
    'nav_students' => 'طلباء',
    'nav_ustaaz' => 'اساتذہ',
    'nav_exams' => 'امتحانات',
    'nav_reports' => 'رپورٹس',
    'nav_settings' => 'ترتیبات',

    'add_halaqa' => 'نیا حلقہ',
    'add_new' => 'نیا حلقہ شامل کریں',
    'name_ur' => 'حلقہ نام (اردو)',
    'gender' => 'گروپ',
    'boy' => 'بنین',
    'girl' => 'بنات',
    'session' => 'وقت',
    'subah' => 'صبح',
    'asr' => 'عصر',
    'status' => 'اسٹیٹس',
    'active' => 'فعال',
    'paused' => 'وقفہ',
    'stopped' => 'بند',

    'save' => 'محفوظ کریں',
    'created' => 'نئی حلقہ شامل ہوگئی ✅',
    'err_required' => 'حلقہ کا اردو نام لازمی ہے۔',
    'err_db' => 'ڈیٹا محفوظ نہیں ہوا۔',

    'filters' => 'فلٹرز',
    'filter_gender' => 'گروپ',
    'filter_session' => 'سیشن',
    'filter_status' => 'اسٹیٹس',
    'all' => 'سب',
    'sort_by' => 'ترتیب',
    'sort_name' => 'نام',
    'sort_new' => 'نیا پہلے',
    'sort_students' => 'طلبہ',

    'total_halqaat' => 'کل حلقات',
    'total_students' => 'کل طلبہ',
    'students' => 'طلبہ',
    'ustaaz' => 'استاد',
    'mumayyaz' => 'ممیّز طلبہ',
    'open' => 'کھولیں',
    'no_data' => 'ابھی تک کوئی حلقہ موجود نہیں۔',
    'results' => 'نتائج',
  ],
  'en' => [
    'app' => 'Kahf Halaqat',
    'page' => 'Halaqaat',
    'search' => 'Search halaqa name...',
    'logout' => 'Logout',

    'nav_dashboard' => 'Dashboard',
    'nav_halqaat' => 'Halaqaat',
    'nav_students' => 'Students',
    'nav_ustaaz' => 'Ustaaz',
    'nav_exams' => 'Exams',
    'nav_reports' => 'Reports',
    'nav_settings' => 'Settings',

    'add_halaqa' => 'Add Halaqa',
    'add_new' => 'Add New Halaqa',
    'name_ur' => 'Halaqa Name (Urdu)',
    'gender' => 'Group',
    'boy' => 'Baneen',
    'girl' => 'Banaat',
    'session' => 'Session',
    'subah' => 'Subah',
    'asr' => 'Asr',
    'status' => 'Status',
    'active' => 'Active',
    'paused' => 'Paused',
    'stopped' => 'Stopped',

    'save' => 'Save',
    'created' => 'Halaqa created ✅',
    'err_required' => 'Urdu name is required.',
    'err_db' => 'Could not save data.',

    'filters' => 'Filters',
    'filter_gender' => 'Group',
    'filter_session' => 'Session',
    'filter_status' => 'Status',
    'all' => 'All',
    'sort_by' => 'Sort',
    'sort_name' => 'Name',
    'sort_new' => 'Newest',
    'sort_students' => 'Students',

    'total_halqaat' => 'Total Halaqaat',
    'total_students' => 'Total Students',
    'students' => 'Students',
    'ustaaz' => 'Ustaaz',
    'mumayyaz' => 'Mumayyaz',
    'open' => 'Open',
    'no_data' => 'No halaqa yet.',
    'results' => 'Results',
  ],
];
$tr = $T[$lang];

function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function table_exists($conn, $table)
{
  $t = $conn->real_escape_string($table);
  $res = $conn->query("SHOW TABLES LIKE '$t'");
  return ($res && $res->num_rows > 0);
}
function column_exists($conn, $table, $col)
{
  $t = $conn->real_escape_string($table);
  $c = $conn->real_escape_string($col);
  $res = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
  return ($res && $res->num_rows > 0);
}

function normalize_gender($g)
{
  $g = strtolower(trim((string)$g));
  return ($g === 'girl' || $g === 'girls') ? 'girl' : 'boy';
}
function normalize_session($s)
{
  $s = strtolower(trim((string)$s));
  return ($s === 'asr' || $s === 'asr') ? 'asr' : 'subah';
}
function normalize_state($s)
{
  $s = strtolower(trim((string)$s));
  if ($s === 'stopped') return 'stopped';
  if ($s === 'paused') return 'paused';
  return 'active';
}

function gender_label($tr, $g)
{
  return normalize_gender($g) === 'girl' ? $tr['girl'] : $tr['boy'];
}
function session_label($tr, $s)
{
  return normalize_session($s) === 'asr' ? $tr['asr'] : $tr['subah'];
}
function state_label($tr, $st)
{
  $st = normalize_state($st);
  if ($st === 'stopped') return $tr['stopped'];
  if ($st === 'paused') return $tr['paused'];
  return $tr['active'];
}

$hasStudentsTable  = table_exists($conn, 'students');
$hasUsersTable     = table_exists($conn, 'users');

$halaqaatHasState  = column_exists($conn, 'halaqaat', 'state');
$halaqaatHasUstaaz = column_exists($conn, 'halaqaat', 'ustaaz_user_id');

$studentsHasHalaqa = $hasStudentsTable && column_exists($conn, 'students', 'halaqa_id');
$studentsHasMum    = $hasStudentsTable && (column_exists($conn, 'students', 'is_mumayyaz') || column_exists($conn, 'students', 'mumayyaz'));
$mumCol = column_exists($conn, 'students', 'is_mumayyaz') ? 'is_mumayyaz' : (column_exists($conn, 'students', 'mumayyaz') ? 'mumayyaz' : null);

// create halaqa
$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__create_halaqa'])) {
  $name_ur = trim($_POST['name_ur'] ?? '');
  $gender  = normalize_gender($_POST['gender'] ?? 'boy');
  $session = normalize_session($_POST['session'] ?? 'subah');
  $state   = normalize_state($_POST['state'] ?? 'active');

  if ($name_ur === '') {
    $err = $tr['err_required'];
  } else {
    if ($halaqaatHasState) {
      $stmt = $conn->prepare("INSERT INTO halaqaat (name_ur, gender, session, state) VALUES (?, ?, ?, ?)");
      if ($stmt) {
        $stmt->bind_param("ssss", $name_ur, $gender, $session, $state);
        if ($stmt->execute()) {
          $msg = $tr['created'];
          $_POST = [];
        } else {
          $err = $tr['err_db'];
        }
        $stmt->close();
      } else $err = $tr['err_db'];
    } else {
      $is_active = ($state === 'active') ? 1 : 0;
      $stmt = $conn->prepare("INSERT INTO halaqaat (name_ur, gender, session, is_active) VALUES (?, ?, ?, ?)");
      if ($stmt) {
        $stmt->bind_param("sssi", $name_ur, $gender, $session, $is_active);
        if ($stmt->execute()) {
          $msg = $tr['created'];
          $_POST = [];
        } else {
          $err = $tr['err_db'];
        }
        $stmt->close();
      } else $err = $tr['err_db'];
    }
  }
}

// filters (server-side sorting only; search is realtime client-side)
$f_gender   = strtolower(trim($_GET['gender'] ?? ''));
$f_session  = strtolower(trim($_GET['session'] ?? ''));
$f_state    = strtolower(trim($_GET['state'] ?? ''));
$sort       = strtolower(trim($_GET['sort'] ?? 'new'));

if ($f_gender !== '' && !in_array($f_gender, ['boy', 'girl'], true)) $f_gender = '';
if ($f_session !== '' && !in_array($f_session, ['subah', 'asr'], true)) $f_session = '';
if ($f_state !== '' && !in_array($f_state, ['active', 'paused', 'stopped'], true)) $f_state = '';
if (!in_array($sort, ['name', 'new', 'students'], true)) $sort = 'new';

$where = [];
$params = [];
$types = '';

if ($f_gender !== '') {
  $where[] = "h.gender=?";
  $params[] = $f_gender;
  $types .= 's';
}
if ($f_session !== '') {
  $where[] = "h.session=?";
  $params[] = $f_session;
  $types .= 's';
}
if ($f_state !== '') {
  if ($halaqaatHasState) {
    $where[] = "h.state=?";
    $params[] = $f_state;
    $types .= 's';
  } else {
    if ($f_state === 'active') $where[] = "COALESCE(h.is_active,1)=1";
    else $where[] = "COALESCE(h.is_active,1)=0";
  }
}
$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$orderSql = "ORDER BY h.id DESC";
if ($sort === 'name') $orderSql = "ORDER BY h.name_ur ASC, h.id DESC";
if ($sort === 'students') $orderSql = "ORDER BY students_count DESC, h.id DESC";

$selectStudents = $studentsHasHalaqa ? "COUNT(s.id) AS students_count" : "0 AS students_count";
$selectMum      = ($studentsHasHalaqa && $mumCol) ? "SUM(CASE WHEN COALESCE(s.`$mumCol`,0)=1 THEN 1 ELSE 0 END) AS mumayyaz_count" : "0 AS mumayyaz_count";
$selectState    = $halaqaatHasState ? "h.state" : "CASE WHEN COALESCE(h.is_active,1)=1 THEN 'active' ELSE 'paused' END AS state";
$selectUstaaz   = ($halaqaatHasUstaaz && $hasUsersTable) ? "COALESCE(u.full_name, u.name, u.username, '-') AS ustaaz_name" : "'' AS ustaaz_name";
$joinStudents   = $studentsHasHalaqa ? "LEFT JOIN students s ON s.halaqa_id = h.id" : "";
$joinUstaaz     = ($halaqaatHasUstaaz && $hasUsersTable) ? "LEFT JOIN users u ON u.id = h.ustaaz_user_id" : "";

$sql = "
  SELECT
    h.id, h.name_ur, h.gender, h.session,
    $selectState,
    $selectStudents,
    $selectMum,
    $selectUstaaz
  FROM halaqaat h
  $joinStudents
  $joinUstaaz
  $whereSql
  GROUP BY h.id, h.name_ur, h.gender, h.session, state, ustaaz_name
  $orderSql
";

$halaqaat = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
  if ($types !== '' && $params) $stmt->bind_param($types, ...$params);
  if ($stmt->execute()) {
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $halaqaat[] = $row;
  }
  $stmt->close();
}

$totalHalaqaat = count($halaqaat);
$totalStudents = 0;
foreach ($halaqaat as $r) $totalStudents += (int)($r['students_count'] ?? 0);
?>
<!doctype html>
<html lang="<?php echo h($lang); ?>" dir="<?php echo $isRtl ? 'rtl' : 'ltr'; ?>">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
  <title><?php echo h($tr['page']); ?> — <?php echo h($tr['app']); ?></title>

  <style>
    html[lang="en"] body,
    html[lang="en"] * {
      font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif !important;
    }

    html[lang="ur"] body,
    html[lang="ur"] * {
      font-family: 'Noto Nastaliq Urdu', serif !important;
    }

    :root {
      --primary: #3e846a;
      --secondary: #b18f6e;
      --accent: #444444;
      --bg: #f6f2ee;
      --card: #ffffff;
      --border: #e7ddd4;
      --sidebar: #3b3b3b;
      --sidebar2: #2f2f2f;

      --boy: #2f6fd6;
      --girl: #d24e8a;
      --subah: #ff8c00;
      --asr: #784614;

      --stopped: #b11c1c;
      --paused: #8a5a00;
    }

    * {
      box-sizing: border-box
    }

    body {
      margin: 0;
      background: var(--bg);
      color: var(--accent);
    }

    .layout {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 280px 1fr;
    }

    .main {
      padding: 18px;
    }

    /* Sidebar (same) */
    .sidebar {
      background: linear-gradient(180deg, var(--sidebar), var(--sidebar2));
      color: #fff;
      padding: 16px;
      position: sticky;
      top: 0;
      height: 100vh;
      overflow: auto;
    }

    .brand {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 8px 8px 16px;
      border-bottom: 1px solid rgba(255, 255, 255, .12);
      margin-bottom: 12px;
    }

    .brand .name {
      font-weight: 900;
      font-size: 16px;
      letter-spacing: .4px;
    }

    .brand .sub {
      font-weight: 700;
      font-size: 12px;
      opacity: .85;
    }

    .sideToggle {
      border: 1px solid rgba(255, 255, 255, .25);
      background: rgba(255, 255, 255, .08);
      color: #fff;
      padding: 8px 10px;
      border-radius: 12px;
      font-weight: 900;
      cursor: pointer;
    }

    .sideToggle:hover {
      background: rgba(255, 255, 255, .12);
    }

    .layout.collapsed {
      grid-template-columns: 90px 1fr;
    }

    .layout.collapsed .brand .name,
    .layout.collapsed .brand .sub,
    .layout.collapsed .nav a span.txt,
    .layout.collapsed .sidebarBottom {
      display: none;
    }

    .layout.collapsed .nav a {
      justify-content: center;
      padding: 12px;
    }

    .nav {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-top: 10px;
    }

    .nav a {
      text-decoration: none;
      color: #fff;
      padding: 10px 12px;
      border-radius: 12px;
      font-weight: 800;
      font-size: 13px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      background: transparent;
      border: 1px solid rgba(255, 255, 255, .10);
      position: relative;
      padding-left: 40px;
    }

    html[dir="rtl"] .nav a {
      padding-left: 12px;
      padding-right: 40px;
    }

    .nav a.active {
      background: rgba(255, 255, 255, .10);
      border-color: rgba(255, 255, 255, .18);
    }

    .nav a:hover {
      background: rgba(255, 255, 255, .08);
    }

    .nav a::before {
      content: '';
      position: absolute;
      width: 16px;
      height: 16px;
      background: rgba(255, 255, 255, .9);
      mask-size: contain;
      mask-repeat: no-repeat;
      mask-position: center;
      -webkit-mask-size: contain;
      -webkit-mask-repeat: no-repeat;
      -webkit-mask-position: center;
    }

    html[dir="ltr"] .nav a::before {
      left: 12px;
    }

    html[dir="rtl"] .nav a::before {
      right: 12px;
    }

    .nav a.dash::before {
      -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>');
    }

    .nav a.halaqa::before {
      -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>');
    }

    .nav a.students::before {
      -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>');
    }

    .nav a.ustaaz::before {
      -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>');
    }

    .nav a.exams::before {
      -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>');
    }

    .nav a.reports::before {
      -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm4 0h14v-2H7v2zm0-4h14v-2H7v2z"/></svg>');
    }

    .nav a.settings::before {
      -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.11-.2-.36-.28-.57-.22l-2.39.96c-.5-.38-1.04-.7-1.64-.94L14.5 2h-5l-.37 2.35c-.6.24-1.14.56-1.64.94l-2.39-.96c-.21-.06-.46.02-.57.22L2.61 7.87c-.11.2-.06.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94L2.73 14.52c-.18.14-.23.41-.12.61l1.92 3.32c.11.2.36.28.57.22l2.39-.96c.5.38 1.04.7 1.64.94L9.5 22h5l.37-2.35c.6-.24 1.14-.56 1.64-.94l2.39.96c.21.06.46-.02.57-.22l1.92-3.32c.11-.2.06-.47-.12-.61l-2.03-1.58z"/></svg>');
    }

    .sidebarBottom {
      margin-top: 14px;
      padding-top: 14px;
      border-top: 1px solid rgba(255, 255, 255, .12);
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .pillSide {
      display: block;
      text-align: center;
      text-decoration: none;
      color: #fff;
      border: 1px solid rgba(255, 255, 255, .25);
      background: rgba(255, 255, 255, .08);
      padding: 10px 12px;
      border-radius: 12px;
      font-weight: 900;
    }

    /* Topbar */
    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 12px;
    }

    .btnAdd {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 14px;
      border-radius: 12px;
      border: 0;
      cursor: pointer;
      background: var(--primary);
      color: #fff;
      font-weight: 900;
      white-space: nowrap;
      box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
    }

    .btnAdd:hover {
      filter: brightness(.96);
    }

    /* Combined language toggle pill: "ار | EN" */
    .langPill {
      display: inline-flex;
      align-items: center;
      overflow: hidden;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: #fff;
      box-shadow: 0 6px 18px rgba(0, 0, 0, .04);
    }

    .langPill a {
      text-decoration: none;
      padding: 10px 12px;
      font-weight: 900;
      font-size: 13px;
      display: inline-block;
      color: var(--accent);
      min-width: 56px;
      text-align: center;
    }

    .langPill .sep {
      padding: 0 4px;
      color: #999;
      font-weight: 900;
    }

    .langPill a.activeEn {
      background: var(--primary);
      color: #fff;
    }

    .langPill a.activeUr {
      background: var(--secondary);
      color: #fff;
    }

    .statsMini {
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
    }

    .badgeMini {
      display: inline-block;
      padding: 10px 12px;
      border-radius: 12px;
      border: 1px solid var(--border);
      font-weight: 900;
      font-size: 13px;
      background: #fff;
      white-space: nowrap;
      box-shadow: 0 6px 18px rgba(0, 0, 0, .04);
    }

    .badgeMini.halqaat {
      background: var(--primary);
      border-color: rgba(62, 132, 106, .40);
      color: #fff;
    }

    .badgeMini.students {
      background: var(--secondary);
      border-color: rgba(177, 143, 110, .42);
      color: #fff;
    }

    .searchWrap {
      flex: 1;
      min-width: 240px;
      display: flex;
      align-items: center;
      gap: 10px;
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 10px 12px;
      box-shadow: 0 6px 18px rgba(0, 0, 0, .04);
    }

    .searchWrap input {
      width: 100%;
      border: 0;
      outline: none;
      background: transparent;
      font-size: 14px;
      color: var(--accent);
    }

    /* Filter bar: PRIMARY background */
    .filtersRow {
      margin-bottom: 14px;
      border-radius: 16px;
      padding: 12px;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
      box-shadow: 0 8px 20px rgba(0, 0, 0, .06);
    }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 10px;
      border-radius: 12px;
      background: rgba(255, 255, 255, .14);
      border: 1px solid rgba(255, 255, 255, .25);
      font-weight: 900;
      font-size: 13px;
      color: #2f2f2f;
      white-space: nowrap;
    }

    .filtersRight {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
    }

    select {
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      padding: 10px 36px 10px 12px;
      border: 1px solid #e0d9d1;
      border-radius: 12px;
      background: rgba(255, 255, 255, .14);
      font-size: 13px;
      font-weight: 900;
      color: #2f2f2f;
      outline: none;
      background-repeat: no-repeat;
      background-size: 14px 14px;
      background-position: calc(100% - 12px) 50%;
      background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><path fill='%23666' d='M7 10l5 5 5-5z'/></svg>");
    }

    html[dir="rtl"] select {
      background-position: 12px 50%;
      padding: 10px 12px 10px 36px;
      text-align: right;
    }

    .msg {
      padding: 10px 12px;
      border-radius: 12px;
      background: rgba(62, 132, 106, .10);
      border: 1px solid rgba(62, 132, 106, .25);
      font-weight: 900;
      margin: 10px 0;
    }

    .err {
      padding: 10px 12px;
      border-radius: 12px;
      background: #fff3f3;
      border: 1px solid #f2c7c7;
      color: #8a1f1f;
      font-weight: 900;
      margin: 10px 0;
    }

    /* Add panel */
    .addPanel {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: 0 6px 18px rgba(0, 0, 0, .04);
      overflow: hidden;
      margin-bottom: 14px;
      display: none;
    }

    .addPanel.open {
      display: block;
    }

    .addHead {
      padding: 12px 14px;
      border-bottom: 1px solid var(--border);
      font-weight: 900;
    }

    .addBody {
      padding: 14px;
    }

    label {
      display: block;
      margin: 10px 0 6px;
      font-weight: 900;
      font-size: 13px;
    }

    input[type="text"] {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--border);
      border-radius: 12px;
      outline: none;
      background: #fff;
      font-size: 14px;
    }

    input:focus,
    select:focus {
      border-color: rgba(62, 132, 106, .55);
      box-shadow: 0 0 0 4px rgba(62, 132, 106, .12);
    }

    .row2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }

    @media(max-width:520px) {
      .row2 {
        grid-template-columns: 1fr;
      }
    }

    .btnSave {
      width: 100%;
      margin-top: 14px;
      padding: 12px;
      border: 0;
      border-radius: 12px;
      background: var(--primary);
      color: #fff;
      font-weight: 900;
      cursor: pointer;
      font-size: 15px;
    }

    /* Cards grid */
    .cards {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 12px;
    }

    @media(max-width:1200px) {
      .cards {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }
    }

    @media(max-width:900px) {
      .cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media(max-width:600px) {
      .cards {
        grid-template-columns: 1fr;
      }
    }

    .card {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: 0 6px 18px rgba(0, 0, 0, .04);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      min-height: 168px;
    }

    .cardTop {
      padding: 14px 14px 10px;
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 10px;
    }

    .titleBlock {
      min-width: 0;
    }

    .cardTitle {
      font-weight: 900;
      font-size: 17px;
      /* bigger */
      line-height: 1.9;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .cardUstaaz {
      margin-top: 4px;
      font-size: 13px;
      color: #666;
      font-weight: 900;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .goBtn {
      border: 0;
      background: transparent;
      cursor: pointer;
      font-size: 20px;
      line-height: 1;
      color: #777;
      margin-top: 4px;
    }

    .cardMid {
      padding: 0 14px 12px;
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      align-items: center;
    }

    /* Tags now small rounded rectangles (like pic) */
    .tag {
      display: inline-block;
      padding: 2px 10px;
      border-radius: 999px;
      /* rectangle-ish */
      border: 1px solid var(--border);
      font-weight: 900;
      font-size: 10px;
      background: #fff;
      white-space: nowrap;
    }

    .tag.boy {
      background: rgba(47, 111, 214, .16);
      border-color: rgba(47, 111, 214, .40);
      color: #1b3f86;
    }

    .tag.girl {
      background: rgba(210, 78, 138, .16);
      border-color: rgba(210, 78, 138, .40);
      color: #7a1f4a;
    }

    .tag.subah {
      background: rgba(255, 140, 0, .18);
      border-color: rgba(255, 140, 0, .45);
      color: #6a3a00;
    }

    .tag.asr {
      background: rgba(234, 238, 31, 0.18);
      border-color: rgba(178, 170, 22, 0.45);
      color: #9f971e;
    }

    .tag.active {
      background: rgba(62, 132, 106, .14);
      border-color: rgba(62, 132, 106, .40);
      color: var(--primary);
    }

    .tag.paused {
      background: rgba(255, 165, 0, .16);
      border-color: rgba(255, 165, 0, .45);
      color: var(--paused);
    }

    .tag.stopped {
      background: rgba(177, 28, 28, .14);
      border-color: rgba(177, 28, 28, .40);
      color: var(--stopped);
    }

    .cardFoot {
      margin-top: auto;
      padding: 12px 14px;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      gap: 10px;
      font-size: 12px;
      color: #666;
      align-items: center;
    }

    .metaRight {
      display: flex;
      gap: 10px;
      align-items: center;
      white-space: nowrap;
    }

    .numPill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 10px;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: #fff;
      font-weight: 900;
      color: var(--accent);
    }

    .openLink {
      text-decoration: none;
    }

    /* realtime results count */
    .resultsLine {
      margin-bottom: 14px;
      border-radius: 16px;
      padding: 12px;
      background: var(--primary);
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      box-shadow: 0 8px 20px rgba(0, 0, 0, .06);
      justify-content: flex-end;
      color: #fff;
    }

    html[dir="rtl"] .resultsLine {
      justify-content: flex-start;
    }

    /* Mobile sidebar */
    .menuBtn {
      display: none;
      background: #3e846a;
      border: 1px solid rgba(255, 255, 255, .6);
      color: #fff;
      padding: 10px 12px;
      border-radius: 12px;
      font-weight: 900;
      font-size: 18px;
      cursor: pointer;
      line-height: 1;
    }

    @media (max-width: 980px) {
      .layout {
        grid-template-columns: 1fr;
      }

      .main {
        padding: 16px;
      }

      .menuBtn {
        display: inline-block;
      }

      .sidebar {
        position: fixed;
        z-index: 50;
        top: 0;
        bottom: 0;
        width: 280px;
        overflow: auto;
        transition: transform .2s ease;
      }

      html[dir="ltr"] .sidebar {
        left: 0;
        transform: translateX(-110%);
      }

      html[dir="rtl"] .sidebar {
        right: 0;
        transform: translateX(110%);
      }

      .sidebar.open {
        transform: translateX(0) !important;
      }

      .overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .35);
        z-index: 40;
      }

      .overlay.show {
        display: block;
      }
    }
  </style>
</head>

<body>
  <div class="overlay" id="overlay" onclick="toggleSidebar(false)"></div>

  <div class="layout">
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
        <a class="students" href="students_admin.php"><span class="txt"><?php echo h($tr['nav_students']); ?></span></a>
        <a class="ustaaz" href="#"><span class="txt"><?php echo h($tr['nav_ustaaz']); ?></span></a>
        <a class="exams" href="#"><span class="txt"><?php echo h($tr['nav_exams']); ?></span></a>
        <a class="reports" href="#"><span class="txt"><?php echo h($tr['nav_reports']); ?></span></a>
        <a class="settings" href="#"><span class="txt"><?php echo h($tr['nav_settings']); ?></span></a>
      </nav>

      <div class="sidebarBottom">
        <a class="pillSide" href="logout.php"><?php echo h($tr['logout']); ?></a>
      </div>
    </aside>

    <main class="main">
      <div class="topbar">
        <button class="menuBtn" type="button" onclick="toggleSidebar()">☰</button>

        <button class="btnAdd" type="button" onclick="toggleAddPanel()">
          <span>+</span> <span><?php echo h($tr['add_halaqa']); ?></span>
        </button>

        <!-- REALTIME search (no submit) -->
        <div class="searchWrap">
          <input
            type="text"
            id="searchInput"
            value=""
            placeholder="<?php echo h($tr['search']); ?>"
            autocomplete="off">
        </div>

        <!-- language toggle pill: ار | EN -->
        <div class="langPill" aria-label="Language toggle">
          <a
            href="?lang=ur<?php echo ($f_gender || $f_session || $f_state || $sort) ? '&' . http_build_query(['gender' => $f_gender, 'session' => $f_session, 'state' => $f_state, 'sort' => $sort]) : ''; ?>"
            class="<?php echo $lang === 'ur' ? 'activeUr' : ''; ?>"
            title="Urdu">ار</a>
          <span class="sep">|</span>
          <a
            href="?lang=en<?php echo ($f_gender || $f_session || $f_state || $sort) ? '&' . http_build_query(['gender' => $f_gender, 'session' => $f_session, 'state' => $f_state, 'sort' => $sort]) : ''; ?>"
            class="<?php echo $lang === 'en' ? 'activeEn' : ''; ?>"
            title="English">EN</a>
        </div>

        <div class="statsMini">
          <span class="badgeMini halqaat"><?php echo h($tr['total_halqaat']); ?>: <span id="totalHalaqaat"><?php echo (int)$totalHalaqaat; ?></span></span>
          <span class="badgeMini students"><?php echo h($tr['total_students']); ?>: <span id="totalStudents"><?php echo (int)$totalStudents; ?></span></span>
        </div>
      </div>

      <form class="filtersRow" method="get" action="halaqaat_admin.php">
        <input type="hidden" name="lang" value="<?php echo h($lang); ?>">
        <div class="chip"><?php echo h($tr['filters']); ?></div>

        <div class="filtersRight">
          <select name="gender" onchange="this.form.submit()">
            <option value=""><?php echo h($tr['filter_gender']); ?>: <?php echo h($tr['all']); ?></option>
            <option value="boy" <?php echo $f_gender === 'boy' ? 'selected' : '';  ?>><?php echo h($tr['boy']); ?></option>
            <option value="girl" <?php echo $f_gender === 'girl' ? 'selected' : ''; ?>><?php echo h($tr['girl']); ?></option>
          </select>

          <select name="session" onchange="this.form.submit()">
            <option value=""><?php echo h($tr['filter_session']); ?>: <?php echo h($tr['all']); ?></option>
            <option value="subah" <?php echo $f_session === 'subah' ? 'selected' : ''; ?>><?php echo h($tr['subah']); ?></option>
            <option value="asr" <?php echo $f_session === 'asr' ? 'selected' : '';   ?>><?php echo h($tr['asr']); ?></option>
          </select>

          <select name="state" onchange="this.form.submit()">
            <option value=""><?php echo h($tr['filter_status']); ?>: <?php echo h($tr['all']); ?></option>
            <option value="active" <?php echo $f_state === 'active' ? 'selected' : '';  ?>><?php echo h($tr['active']); ?></option>
            <option value="paused" <?php echo $f_state === 'paused' ? 'selected' : '';  ?>><?php echo h($tr['paused']); ?></option>
            <option value="stopped" <?php echo $f_state === 'stopped' ? 'selected' : ''; ?>><?php echo h($tr['stopped']); ?></option>
          </select>

          <select name="sort" onchange="this.form.submit()">
            <option value="new" <?php echo $sort === 'new' ? 'selected' : ''; ?>><?php echo h($tr['sort_by']); ?>: <?php echo h($tr['sort_new']); ?></option>
            <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>><?php echo h($tr['sort_by']); ?>: <?php echo h($tr['sort_name']); ?></option>
            <option value="students" <?php echo $sort === 'students' ? 'selected' : ''; ?>><?php echo h($tr['sort_by']); ?>: <?php echo h($tr['sort_students']); ?></option>
          </select>
        </div>
      </form>

      <div class="resultsLine">
        <?php echo h($tr['results']); ?>: <span id="resultsCount"><?php echo (int)$totalHalaqaat; ?></span>
      </div>

      <?php if ($msg): ?><div class="msg"><?php echo h($msg); ?></div><?php endif; ?>
      <?php if ($err): ?><div class="err"><?php echo h($err); ?></div><?php endif; ?>

      <div class="addPanel" id="addPanel">
        <div class="addHead"><?php echo h($tr['add_new']); ?></div>
        <div class="addBody">
          <form method="post" autocomplete="off">
            <input type="hidden" name="__create_halaqa" value="1">

            <label><?php echo h($tr['name_ur']); ?></label>
            <input type="text" name="name_ur" value="<?php echo h($_POST['name_ur'] ?? ''); ?>" required>

            <div class="row2">
              <div>
                <label><?php echo h($tr['gender']); ?></label>
                <?php $gSel = normalize_gender($_POST['gender'] ?? 'boy'); ?>
                <select name="gender">
                  <option value="boy" <?php echo $gSel === 'boy' ? 'selected' : '';  ?>><?php echo h($tr['boy']); ?></option>
                  <option value="girl" <?php echo $gSel === 'girl' ? 'selected' : ''; ?>><?php echo h($tr['girl']); ?></option>
                </select>
              </div>

              <div>
                <label><?php echo h($tr['session']); ?></label>
                <?php $sSel = normalize_session($_POST['session'] ?? 'subah'); ?>
                <select name="session">
                  <option value="subah" <?php echo $sSel === 'subah' ? 'selected' : ''; ?>><?php echo h($tr['subah']); ?></option>
                  <option value="asr" <?php echo $sSel === 'asr' ? 'selected' : '';   ?>><?php echo h($tr['asr']); ?></option>
                </select>
              </div>
            </div>

            <label><?php echo h($tr['status']); ?></label>
            <?php $stSel = normalize_state($_POST['state'] ?? 'active'); ?>
            <select name="state">
              <option value="active" <?php echo $stSel === 'active' ? 'selected' : '';  ?>><?php echo h($tr['active']); ?></option>
              <option value="paused" <?php echo $stSel === 'paused' ? 'selected' : '';  ?>><?php echo h($tr['paused']); ?></option>
              <option value="stopped" <?php echo $stSel === 'stopped' ? 'selected' : ''; ?>><?php echo h($tr['stopped']); ?></option>
            </select>

            <button class="btnSave" type="submit"><?php echo h($tr['save']); ?></button>
          </form>
        </div>
      </div>

      <?php if (empty($halaqaat)): ?>
        <div class="card" style="padding:16px; font-weight:900;"><?php echo h($tr['no_data']); ?></div>
      <?php else: ?>
        <div class="cards" id="cardsWrap">
          <?php foreach ($halaqaat as $hrow): ?>
            <?php
            $hid = (int)$hrow['id'];
            $g   = normalize_gender($hrow['gender'] ?? 'boy');
            $ses = normalize_session($hrow['session'] ?? 'subah');
            $st  = normalize_state($hrow['state'] ?? 'active');

            $studentsCount = (int)($hrow['students_count'] ?? 0);
            $mumCount      = (int)($hrow['mumayyaz_count'] ?? 0);
            $ustaazName    = trim((string)($hrow['ustaaz_name'] ?? ''));
            if ($ustaazName === '') $ustaazName = '-';
            $nameUr = (string)($hrow['name_ur'] ?? '');
            ?>
            <div class="card halaqaCard"
              data-name="<?php echo h(mb_strtolower($nameUr, 'UTF-8')); ?>"
              data-students="<?php echo (int)$studentsCount; ?>">
              <div class="cardTop">
                <div class="titleBlock">
                  <div class="cardTitle" title="<?php echo h($nameUr); ?>">
                    <?php echo h($nameUr); ?>
                  </div>
                  <div class="cardUstaaz" title="<?php echo h($ustaazName); ?>">
                    <?php echo h($tr['ustaaz']); ?>: <?php echo h($ustaazName); ?>
                  </div>
                </div>
                <button class="goBtn" type="button" onclick="window.location.href='halaqa_view.php?id=<?php echo $hid; ?>'">›</button>
              </div>

              <div class="cardMid">
                <span class="tag <?php echo $g === 'girl' ? 'girl' : 'boy'; ?>">
                  <?php echo h(gender_label($tr, $g)); ?>
                </span>

                <span class="tag <?php echo $ses === 'asr' ? 'asr' : 'subah'; ?>">
                  <?php echo h(session_label($tr, $ses)); ?>
                </span>

                <span class="tag <?php echo $st; ?>">
                  <?php echo h(state_label($tr, $st)); ?>
                </span>
              </div>

              <div class="cardFoot">
                <div style="font-weight:900;">
                  <?php echo h($tr['mumayyaz']); ?>: <?php echo (int)$mumCount; ?>
                </div>
                <div class="metaRight">
                  <span class="numPill"><?php echo h($tr['students']); ?>: <?php echo (int)$studentsCount; ?></span>
                  <a class="numPill openLink" href="halaqa_view.php?id=<?php echo $hid; ?>"><?php echo h($tr['open']); ?></a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
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
        var open = (typeof forceOpen === 'boolean') ? forceOpen : !sb.classList.contains('open');
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

    window.addEventListener('resize', function() {
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

    function toggleAddPanel() {
      var p = document.getElementById('addPanel');
      if (!p) return;
      p.classList.toggle('open');
      if (p.classList.contains('open')) p.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }

    // ✅ REALTIME SEARCH (client-side) - no Enter needed
    (function() {
      var input = document.getElementById('searchInput');
      var cards = Array.prototype.slice.call(document.querySelectorAll('.halaqaCard'));
      var resultsCount = document.getElementById('resultsCount');

      // totals stay as server totals; resultsCount shows filtered count
      if (!input || cards.length === 0 || !resultsCount) return;

      function norm(s) {
        return (s || '').toString().toLowerCase().trim();
      }

      function apply() {
        var q = norm(input.value);
        var shown = 0;

        for (var i = 0; i < cards.length; i++) {
          var c = cards[i];
          var name = norm(c.getAttribute('data-name'));
          var match = (q === '') ? true : (name.indexOf(q) !== -1);

          c.style.display = match ? '' : 'none';
          if (match) shown++;
        }

        resultsCount.textContent = shown;
      }

      input.addEventListener('input', apply);
      apply();
    })();

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($err !== '')): ?>
        (function() {
          var p = document.getElementById('addPanel');
          if (p) {
            p.classList.add('open');
          }
        })();
    <?php endif; ?>
  </script>
</body>

</html>