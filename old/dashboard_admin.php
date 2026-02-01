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
$isRtl = ($lang === 'ur');

$T = [
  'ur' => [
    'app' => 'کہف حلقات',
    'dashboard' => 'ڈیش بورڈ',
    'greet' => 'السلام عليكم ورحمة الله وبركاته',
    'search' => 'تلاش کریں...',
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

    'total_halqaat' => 'کل حلقات',
    'total_students' => 'کل طلبہ',
    'total_mumayyaz' => 'کل ممیّزین',

    'students_by_shuba' => 'طلبہ (شعبہ کے مطابق)',
    'students_by_gender' => 'طلبہ (جنس کے مطابق)',
    'students_by_session' => 'طلبہ (اوقات کے مطابق)',

    'boys' => 'طلباء',
    'girls' => 'طالبات',
    'subah' => 'صبح',
    'asr' => 'عصر',

    'halaqaat' => 'حلقات',
    'top_halaqa_girls' => 'سب سے زیادہ فیصد (بنات)',
    'top_halaqa_boys_subah' => 'سب سے زیادہ فیصد (بنین — صبح)',
    'top_halaqa_boys_asr' => 'سب سے زیادہ فیصد (بنین — عصر)',

    'mumtaaz_talaba' => 'ممتاز طالب علم',
    'score' => 'اسکور',
    'pct' => 'فیصد',
    'students' => 'طلبہ',
    'mumayyaz' => 'ممیّز',

    'no_data' => 'ڈیٹا موجود نہیں',
    'no_shuba' => 'شعبہ کا ڈیٹا موجود نہیں',
    'no_results' => 'نتائج کا ڈیٹا موجود نہیں',
    'view_all' => 'سب دیکھیں',
  ],
  'en' => [
    'app' => 'Kahf Halaqat',
    'dashboard' => 'Dashboard',
    'greet' => 'Assalamu Alaikum wa Rahmatullahi wa Barakatuhu',
    'search' => 'Search...',
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

    'total_halqaat' => 'Total Halaqaat',
    'total_students' => 'Total Students',
    'total_mumayyaz' => 'Total Mumayyizeen',

    'students_by_shuba' => 'Students by Shuba',
    'students_by_gender' => 'Students by Gender',
    'students_by_session' => 'Students by Session',

    'boys' => 'Boys',
    'girls' => 'Girls',
    'subah' => 'Subah',
    'asr' => 'Asr',

    'halaqaat' => 'Halaqaat',
    'top_halaqa_girls' => 'Highest % (Girls)',
    'top_halaqa_boys_subah' => 'Highest % (Boys — Subah)',
    'top_halaqa_boys_asr' => 'Highest % (Boys — Asr)',

    'mumtaaz_talaba' => 'Top Student',
    'score' => 'Score',
    'pct' => 'Percent',
    'students' => 'Students',
    'mumayyaz' => 'Mumayyaz',

    'no_data' => 'No data',
    'no_shuba' => 'No shuba data',
    'no_results' => 'No results data',
    'view_all' => 'View all',
  ]
];
$tr = $T[$lang];

$fullName = $_SESSION['full_name'] ?? 'Admin';

/* -------------------- helpers -------------------- */
function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function table_exists($conn, $table)
{
  $table = $conn->real_escape_string($table);
  $res = $conn->query("SHOW TABLES LIKE '$table'");
  return ($res && $res->num_rows > 0);
}
function column_exists($conn, $table, $col)
{
  $t = $conn->real_escape_string($table);
  $c = $conn->real_escape_string($col);
  $res = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
  return ($res && $res->num_rows > 0);
}
function scalar_int($conn, $sql)
{
  $res = $conn->query($sql);
  if (!$res) return 0;
  $row = $res->fetch_row();
  return (int)($row[0] ?? 0);
}
function scalar_str($conn, $sql)
{
  $res = $conn->query($sql);
  if (!$res) return '';
  $row = $res->fetch_row();
  return (string)($row[0] ?? '');
}
function normalize_gender_val($g)
{
  $g = strtolower(trim((string)$g));
  if (in_array($g, ['girl', 'girls', 'female', 'f'], true)) return 'girl';
  return 'boy';
}
function normalize_session_val($s)
{
  $s = strtolower(trim((string)$s));
  if (in_array($s, ['asr', 'asar', 'a'], true)) return 'asr';
  return 'subah';
}
function pct($num, $den)
{
  if ((int)$den <= 0) return 0;
  return (int)round(((float)$num / (float)$den) * 100.0);
}

/* -------------------- tables -------------------- */
$tbl_halqaat  = 'halaqaat';
$tbl_students = 'students';
$tbl_users    = 'users';

/* -------------------- detect columns -------------------- */
$hasHalaqaat  = table_exists($conn, $tbl_halqaat);
$hasStudents  = table_exists($conn, $tbl_students);

$studentsHasHalaqa = $hasStudents && column_exists($conn, $tbl_students, 'halaqa_id');
$mumCol = null;
if ($hasStudents) {
  if (column_exists($conn, $tbl_students, 'is_mumayyaz')) $mumCol = 'is_mumayyaz';
  else if (column_exists($conn, $tbl_students, 'mumayyaz')) $mumCol = 'mumayyaz';
}

/* -------------------- stats -------------------- */
$totalHalaqaat  = $hasHalaqaat ? scalar_int($conn, "SELECT COUNT(*) FROM `$tbl_halqaat`") : 0;
$totalStudents  = $hasStudents ? scalar_int($conn, "SELECT COUNT(*) FROM `$tbl_students`") : 0;
$totalMumayyaz  = ($hasStudents && $mumCol) ? scalar_int($conn, "SELECT COUNT(*) FROM `$tbl_students` WHERE COALESCE(`$mumCol`,0)=1") : 0;

/* -------------------- gender distribution (students table) -------------------- */
$boysCnt = 0;
$girlsCnt = 0;
if ($hasStudents && column_exists($conn, $tbl_students, 'gender')) {
  $boysCnt  = scalar_int($conn, "SELECT COUNT(*) FROM `$tbl_students` WHERE LOWER(gender) IN ('male','boy','boys','m')");
  $girlsCnt = scalar_int($conn, "SELECT COUNT(*) FROM `$tbl_students` WHERE LOWER(gender) IN ('female','girl','girls','f')");
}

/* -------------------- session distribution (students joined to halaqaat if possible) -------------------- */
$subahCnt = 0;
$asrCnt = 0;
if ($hasHalaqaat && $hasStudents && $studentsHasHalaqa && column_exists($conn, $tbl_halqaat, 'session')) {
  $sqlSess = "
        SELECT LOWER(h.session) AS sess, COUNT(s.id) AS cnt
        FROM `$tbl_halqaat` h
        LEFT JOIN `$tbl_students` s ON s.halaqa_id = h.id
        GROUP BY LOWER(h.session)
    ";
  $res = $conn->query($sqlSess);
  if ($res) {
    while ($r = $res->fetch_assoc()) {
      $sess = normalize_session_val($r['sess'] ?? 'subah');
      $cnt  = (int)($r['cnt'] ?? 0);
      if ($sess === 'asr') $asrCnt += $cnt;
      else $subahCnt += $cnt;
    }
    $res->free();
  }
}

/* -------------------- shuba distribution -------------------- */
$shubaCol = null;
$shubaCandidates = ['shuba', 'shobah', 'department', 'division', 'section'];
if ($hasStudents) {
  foreach ($shubaCandidates as $c) {
    if (column_exists($conn, $tbl_students, $c)) {
      $shubaCol = $c;
      break;
    }
  }
}
$shubaRows = [];
if ($hasStudents && $shubaCol) {
  $c = $conn->real_escape_string($shubaCol);
  $sqlSh = "
        SELECT NULLIF(TRIM(`$c`),'') AS shuba, COUNT(*) AS cnt
        FROM `$tbl_students`
        GROUP BY NULLIF(TRIM(`$c`),'')
        HAVING shuba IS NOT NULL
        ORDER BY cnt DESC
        LIMIT 5
    ";
  $res = $conn->query($sqlSh);
  if ($res) {
    while ($r = $res->fetch_assoc()) $shubaRows[] = $r;
    $res->free();
  }
}

/* -------------------- Top halaqaat by percent (girls / boys-subah / boys-asr) -------------------- */
function top_halaqa_by_filter($conn, $tbl_halqaat, $tbl_students, $mumCol, $whereSql)
{
  if (!$mumCol) return null;
  $sql = "
        SELECT
            h.id, h.name_ur, h.name_en, h.gender, h.session,
            COUNT(s.id) AS students_count,
            SUM(CASE WHEN COALESCE(s.`$mumCol`,0)=1 THEN 1 ELSE 0 END) AS mumayyaz_count
        FROM `$tbl_halqaat` h
        LEFT JOIN `$tbl_students` s ON s.halaqa_id = h.id
        WHERE $whereSql
        GROUP BY h.id, h.name_ur, h.name_en, h.gender, h.session
        HAVING students_count > 0
        ORDER BY (mumayyaz_count / students_count) DESC, students_count DESC, h.id DESC
        LIMIT 1
    ";
  $res = $conn->query($sql);
  if (!$res) return null;
  $row = $res->fetch_assoc();
  $res->free();
  return $row ?: null;
}

$topGirls = null;
$topBoysSubah = null;
$topBoysAsr = null;

if ($hasHalaqaat && $hasStudents && $studentsHasHalaqa && $mumCol) {
  // handle old values like boys/girls
  $girlsWhere = "LOWER(h.gender) IN ('girl','girls','female','f')";
  $boysWhere  = "LOWER(h.gender) IN ('boy','boys','male','m')";

  $topGirls     = top_halaqa_by_filter($conn, $tbl_halqaat, $tbl_students, $mumCol, $girlsWhere);
  $topBoysSubah = top_halaqa_by_filter($conn, $tbl_halqaat, $tbl_students, $mumCol, "$boysWhere AND LOWER(h.session) IN ('subah','morning')");
  $topBoysAsr   = top_halaqa_by_filter($conn, $tbl_halqaat, $tbl_students, $mumCol, "$boysWhere AND LOWER(h.session) IN ('asr','asar')");
}

/* -------------------- Top student (best effort across possible schemas) -------------------- */
$topStudentName = '';
$topStudentScore = '';

if ($hasStudents) {
  // 1) student_results(student_id, score)
  if (table_exists($conn, 'student_results') && column_exists($conn, 'student_results', 'student_id') && column_exists($conn, 'student_results', 'score')) {
    $sql = "
            SELECT COALESCE(s.full_name_ur, s.full_name_en, s.full_name, s.name, '-') AS nm, r.score
            FROM student_results r
            JOIN `$tbl_students` s ON s.id = r.student_id
            ORDER BY r.score DESC
            LIMIT 1
        ";
    $res = $conn->query($sql);
    if ($res && ($r = $res->fetch_assoc())) {
      $topStudentName = (string)$r['nm'];
      $topStudentScore = (string)$r['score'];
    }
    if ($res) $res->free();
  }
  // 2) exam_results(student_id, score/marks)
  if ($topStudentName === '' && table_exists($conn, 'exam_results') && column_exists($conn, 'exam_results', 'student_id')) {
    $scoreCol = column_exists($conn, 'exam_results', 'score') ? 'score' : (column_exists($conn, 'exam_results', 'marks') ? 'marks' : null);
    if ($scoreCol) {
      $sql = "
                SELECT COALESCE(s.full_name_ur, s.full_name_en, s.full_name, s.name, '-') AS nm, r.`$scoreCol` AS sc
                FROM exam_results r
                JOIN `$tbl_students` s ON s.id = r.student_id
                ORDER BY r.`$scoreCol` DESC
                LIMIT 1
            ";
      $res = $conn->query($sql);
      if ($res && ($r = $res->fetch_assoc())) {
        $topStudentName = (string)$r['nm'];
        $topStudentScore = (string)$r['sc'];
      }
      if ($res) $res->free();
    }
  }
  // 3) students(score/marks)
  if ($topStudentName === '' && (column_exists($conn, $tbl_students, 'score') || column_exists($conn, $tbl_students, 'marks'))) {
    $sc = column_exists($conn, $tbl_students, 'score') ? 'score' : 'marks';
    $sql = "
            SELECT COALESCE(full_name_ur, full_name_en, full_name, name, '-') AS nm, `$sc` AS sc
            FROM `$tbl_students`
            WHERE `$sc` IS NOT NULL
            ORDER BY `$sc` DESC
            LIMIT 1
        ";
    $res = $conn->query($sql);
    if ($res && ($r = $res->fetch_assoc())) {
      $topStudentName = (string)$r['nm'];
      $topStudentScore = (string)$r['sc'];
    }
    if ($res) $res->free();
  }
}

/* -------------------- UI helpers for cards -------------------- */
function halaqa_display_name($row, $lang)
{
  $name = '';
  if ($lang === 'en') $name = trim((string)($row['name_en'] ?? ''));
  if ($name === '') $name = trim((string)($row['name_ur'] ?? ''));
  if ($name === '') $name = 'Halaqa #' . (int)($row['id'] ?? 0);
  return $name;
}
function safe_int($v)
{
  return (int)($v ?? 0);
}

$chevSvg = function ($dir) {
  // dir: 'left' or 'right'
  if ($dir === 'left') {
    return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.5 19l-7-7 7-7" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  }
  return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8.5 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
};

?>
<!doctype html>
<html lang="<?php echo h($lang); ?>" dir="<?php echo $isRtl ? 'rtl' : 'ltr'; ?>">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
  <title><?php echo h($tr['dashboard']); ?> — <?php echo h($tr['app']); ?></title>

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
      --primary: #0f2d3d;
      --secondary: #aa815e;
      --accent: #444444;
      --bg: #f6f2ee;
      --card: #ffffff;
      --border: #e7ddd4;

      --boy: #2f6fd6;
      --girl: #d24e8a;

      --sidebar: #3b3b3b;
      --sidebar2: #2f2f2f;
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
      font-family: 'Montserrat', sans-serif !important;
    }

    .brand .sub {
      font-weight: 700;
      font-size: 12px;
      opacity: .85;
      font-family: 'Montserrat', sans-serif !important;
    }

    .sideToggle {
      border: 1px solid rgba(255, 255, 255, .25);
      background: rgba(255, 255, 255, .08);
      color: #fff;
      padding: 8px 10px;
      border-radius: 12px;
      font-weight: 900;
      cursor: pointer;
      font-family: 'Montserrat', sans-serif !important;
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
      font-family: 'Montserrat', sans-serif !important;
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

    .pillSide.logout {
      background: #b11c1c;
      border-color: rgba(255, 255, 255, .25);
    }

    .pillSide.logout:hover {
      filter: brightness(.95);
    }

    .main {
      padding: 18px;
    }

    /* top controls */
    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 12px;
    }

    .controls {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .pill {
      text-decoration: none;
      padding: 8px 12px;
      border-radius: 999px;
      border: 1px solid var(--border);
      background: #fff;
      color: var(--accent);
      font-weight: 900;
      font-size: 13px;
    }

    .pill.active {
      background: var(--secondary);
      border-color: var(--secondary);
      color: #fff;
    }

    .pill.logout {
      background: var(--primary);
      border-color: var(--primary);
      color: #fff;
    }

    /* HERO (primary bg) */
    .hero {
      background: var(--primary);
      color: #fff;
      border-radius: 16px;
      padding: 18px 18px;
      box-shadow: 0 10px 22px rgba(0, 0, 0, .08);
      margin-bottom: 12px;
    }

    .heroTitle {
      font-size: 20px;
      font-weight: 900;
      margin: 0;
      line-height: 1.8;
    }

    .heroSub {
      margin: 6px 0 0 0;
      opacity: .95;
      line-height: 1.9;
      font-weight: 800;
      font-size: 13px;
    }

    /* grids */
    .grid3 {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
      margin-bottom: 12px;
    }

    @media(max-width:1100px) {
      .grid3 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media(max-width:600px) {
      .grid3 {
        grid-template-columns: 1fr;
      }
    }

    .card {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: 0 6px 18px rgba(0, 0, 0, .04);
      overflow: hidden;
    }

    .cardPad {
      padding: 14px;
    }

    .cardHead {
      padding: 12px 14px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      font-weight: 900;
      font-size: 13px;
    }

    /* stat cards */
    .statCard {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .statLabel {
      color: #666;
      font-weight: 900;
      font-size: 13px;
    }

    .statVal {
      font-weight: 900;
      font-size: 28px;
      color: var(--accent);
    }

    .statBar {
      width: 10px;
      align-self: stretch;
      border-radius: 10px;
      background: linear-gradient(180deg, rgba(15, 45, 61, .18), rgba(15, 45, 61, .05));
    }

    .statBar.secondary {
      background: linear-gradient(180deg, rgba(170, 129, 94, .22), rgba(170, 129, 94, .06));
    }

    /* mini chart rows */
    .chartRow {
      display: grid;
      grid-template-columns: 60px 1fr;
      gap: 12px;
      align-items: center;
      margin: 10px 0;
    }

    html[dir="rtl"] .chartRow {
      grid-template-columns: 1fr 60px;
    }

    .chartVal {
      font-weight: 900;
      font-size: 13px;
      color: var(--accent);
      text-align: left;
      font-family: 'Montserrat', sans-serif !important;
    }

    html[dir="rtl"] .chartVal {
      text-align: right;
    }

    .chartLine {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .chartLabel {
      font-weight: 900;
      font-size: 12px;
      color: #666;
      min-width: 72px;
    }

    .chartTrack {
      flex: 1;
      height: 8px;
      border-radius: 999px;
      background: #eee7e1;
      overflow: hidden;
      border: 1px solid rgba(0, 0, 0, .03);
    }

    .chartFill {
      height: 100%;
      width: 0%;
      border-radius: 999px;
    }

    .fillPrimary {
      background: rgba(15, 45, 61, .80);
    }

    .fillSecondary {
      background: rgba(170, 129, 94, .85);
    }

    .fillBoy {
      background: rgba(47, 111, 214, .85);
    }

    .fillGirl {
      background: rgba(210, 78, 138, .85);
    }

    /* section header with chevron */
    .sectionHead {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin: 10px 0 10px;
    }

    .sectionTitle {
      font-weight: 900;
      font-size: 15px;
      color: var(--accent);
    }

    .sectionBtn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 38px;
      height: 38px;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: #fff;
      color: var(--accent);
      text-decoration: none;
    }

    .sectionBtn svg {
      width: 20px;
      height: 20px;
    }

    /* top halaqa cards */
    .topHCard .name {
      font-weight: 900;
      font-size: 14px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .topHCard .meta {
      margin-top: 8px;
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 10px;
      border-radius: 999px;
      border: 1px solid var(--border);
      background: #fff;
      font-weight: 900;
      font-size: 12px;
      color: var(--accent);
      white-space: nowrap;
      font-family: 'Montserrat', sans-serif !important;
    }

    .badge.primary {
      background: rgba(15, 45, 61, .10);
      border-color: rgba(15, 45, 61, .26);
    }

    .badge.secondary {
      background: rgba(170, 129, 94, .12);
      border-color: rgba(170, 129, 94, .32);
    }

    .badge.boy {
      background: rgba(47, 111, 214, .12);
      border-color: rgba(47, 111, 214, .35);
      color: #1c4ea8;
    }

    .badge.girl {
      background: rgba(210, 78, 138, .12);
      border-color: rgba(210, 78, 138, .35);
      color: #9c2e63;
    }

    /* Mobile sidebar */
    .menuBtn {
      display: none;
      background: var(--primary);
      border: 1px solid rgba(255, 255, 255, .6);
      color: #fff;
      padding: 10px 12px;
      border-radius: 12px;
      font-weight: 900;
      font-size: 18px;
      cursor: pointer;
      line-height: 1;
    }

    @media(max-width:980px) {
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
          <div class="sub"><?php echo h($tr['dashboard']); ?></div>
        </div>
        <button class="sideToggle" type="button" onclick="toggleSidebar()">☰</button>
      </div>

      <nav class="nav">
        <a class="active dash" href="dashboard_admin.php"><span class="txt"><?php echo h($tr['nav_dashboard']); ?></span></a>
        <a class="halaqa" href="halaqaat_admin.php"><span class="txt"><?php echo h($tr['nav_halqaat']); ?></span></a>
        <a class="students" href="students_admin.php"><span class="txt"><?php echo h($tr['nav_students']); ?></span></a>
        <a class="ustaaz" href="#"><span class="txt"><?php echo h($tr['nav_ustaaz']); ?></span></a>
        <a class="exams" href="#"><span class="txt"><?php echo h($tr['nav_exams']); ?></span></a>
        <a class="reports" href="#"><span class="txt"><?php echo h($tr['nav_reports']); ?></span></a>
        <a class="settings" href="#"><span class="txt"><?php echo h($tr['nav_settings']); ?></span></a>
      </nav>

      <div class="sidebarBottom">
        <a class="pillSide logout" href="logout.php"><?php echo h($tr['logout']); ?></a>
      </div>
    </aside>

    <main class="main">
      <div class="topbar">
        <button class="menuBtn" type="button" onclick="toggleSidebar()">☰</button>

        <div class="controls">
          <a class="pill <?php echo $lang === 'ur' ? 'active' : ''; ?>" href="?lang=ur"><?php echo h($tr['switch_ur']); ?></a>
          <a class="pill <?php echo $lang === 'en' ? 'active' : ''; ?>" href="?lang=en"><?php echo h($tr['switch_en']); ?></a>
          <a class="pill logout" href="logout.php"><?php echo h($tr['logout']); ?></a>
        </div>
      </div>

      <!-- HERO -->
      <section class="hero">
        <h1 class="heroTitle"><?php echo h($tr['dashboard']); ?></h1>
        <p class="heroSub"><?php echo h($tr['greet']); ?></p>
      </section>

      <!-- STATS (3 cards) -->
      <section class="grid3">
        <div class="card">
          <div class="cardPad statCard">
            <div>
              <div class="statLabel"><?php echo h($tr['total_halqaat']); ?></div>
              <div class="statVal"><?php echo (int)$totalHalaqaat; ?></div>
            </div>
            <div class="statBar"></div>
          </div>
        </div>

        <div class="card">
          <div class="cardPad statCard">
            <div>
              <div class="statLabel"><?php echo h($tr['total_students']); ?></div>
              <div class="statVal"><?php echo (int)$totalStudents; ?></div>
            </div>
            <div class="statBar secondary"></div>
          </div>
        </div>

        <div class="card">
          <div class="cardPad statCard">
            <div>
              <div class="statLabel"><?php echo h($tr['total_mumayyaz']); ?></div>
              <div class="statVal"><?php echo (int)$totalMumayyaz; ?></div>
            </div>
            <div class="statBar"></div>
          </div>
        </div>
      </section>

      <!-- ANALYTICS (3 cards) -->
      <section class="grid3">

        <!-- Students by Shuba -->
        <div class="card">
          <div class="cardHead"><?php echo h($tr['students_by_shuba']); ?></div>
          <div class="cardPad">
            <?php if (!$shubaCol || empty($shubaRows)): ?>
              <div style="color:#777; font-weight:900;"><?php echo h($tr['no_shuba']); ?></div>
            <?php else: ?>
              <?php
              $max = 0;
              foreach ($shubaRows as $r) $max = max($max, (int)($r['cnt'] ?? 0));
              if ($max <= 0) $max = 1;
              ?>
              <?php foreach ($shubaRows as $r): ?>
                <?php
                $label = (string)($r['shuba'] ?? '');
                $cnt   = (int)($r['cnt'] ?? 0);
                $w = (int)round(($cnt / $max) * 100);
                ?>
                <div class="chartRow">
                  <div class="chartVal"><?php echo (int)$cnt; ?></div>
                  <div class="chartLine">
                    <div class="chartLabel"><?php echo h($label); ?></div>
                    <div class="chartTrack">
                      <div class="chartFill fillSecondary" style="width:<?php echo $w; ?>%"></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Students by Gender -->
        <div class="card">
          <div class="cardHead"><?php echo h($tr['students_by_gender']); ?></div>
          <div class="cardPad">
            <?php
            $gMax = max($boysCnt, $girlsCnt);
            if ($gMax <= 0) $gMax = 1;
            $boysW  = (int)round(($boysCnt / $gMax) * 100);
            $girlsW = (int)round(($girlsCnt / $gMax) * 100);
            ?>
            <div class="chartRow">
              <div class="chartVal"><?php echo (int)$boysCnt; ?></div>
              <div class="chartLine">
                <div class="chartLabel"><?php echo h($tr['boys']); ?></div>
                <div class="chartTrack">
                  <div class="chartFill fillBoy" style="width:<?php echo $boysW; ?>%"></div>
                </div>
              </div>
            </div>
            <div class="chartRow">
              <div class="chartVal"><?php echo (int)$girlsCnt; ?></div>
              <div class="chartLine">
                <div class="chartLabel"><?php echo h($tr['girls']); ?></div>
                <div class="chartTrack">
                  <div class="chartFill fillGirl" style="width:<?php echo $girlsW; ?>%"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Students by Session -->
        <div class="card">
          <div class="cardHead"><?php echo h($tr['students_by_session']); ?></div>
          <div class="cardPad">
            <?php
            $sMax = max($subahCnt, $asrCnt);
            if ($sMax <= 0) $sMax = 1;
            $subahW = (int)round(($subahCnt / $sMax) * 100);
            $asrW   = (int)round(($asrCnt / $sMax) * 100);
            ?>
            <div class="chartRow">
              <div class="chartVal"><?php echo (int)$subahCnt; ?></div>
              <div class="chartLine">
                <div class="chartLabel"><?php echo h($tr['subah']); ?></div>
                <div class="chartTrack">
                  <div class="chartFill fillPrimary" style="width:<?php echo $subahW; ?>%"></div>
                </div>
              </div>
            </div>
            <div class="chartRow">
              <div class="chartVal"><?php echo (int)$asrCnt; ?></div>
              <div class="chartLine">
                <div class="chartLabel"><?php echo h($tr['asr']); ?></div>
                <div class="chartTrack">
                  <div class="chartFill fillSecondary" style="width:<?php echo $asrW; ?>%"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </section>

      <!-- HALAQAAT section -->
      <div class="sectionHead">
        <div class="sectionTitle"><?php echo h($tr['halaqaat']); ?></div>
        <a class="sectionBtn" href="halaqaat_admin.php" title="<?php echo h($tr['view_all']); ?>">
          <?php echo $chevSvg($isRtl ? 'left' : 'right'); ?>
        </a>
      </div>

      <section class="grid3">
        <!-- Girls top -->
        <div class="card topHCard">
          <div class="cardHead"><?php echo h($tr['top_halaqa_girls']); ?></div>
          <div class="cardPad">
            <?php if (!$topGirls): ?>
              <div style="color:#777; font-weight:900;"><?php echo h($tr['no_data']); ?></div>
            <?php else: ?>
              <?php
              $name = halaqa_display_name($topGirls, $lang);
              $students = safe_int($topGirls['students_count']);
              $mum = safe_int($topGirls['mumayyaz_count']);
              $p = pct($mum, $students);
              ?>
              <div class="name"><?php echo h($name); ?></div>
              <div class="meta">
                <span class="badge girl"><?php echo h($tr['girls']); ?></span>
                <span class="badge secondary"><?php echo h($tr['students']); ?>: <?php echo (int)$students; ?></span>
                <span class="badge primary"><?php echo h($tr['mumayyaz']); ?>: <?php echo (int)$mum; ?></span>
                <span class="badge secondary"><?php echo h($tr['pct']); ?>: <?php echo (int)$p; ?>%</span>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Boys subah top -->
        <div class="card topHCard">
          <div class="cardHead"><?php echo h($tr['top_halaqa_boys_subah']); ?></div>
          <div class="cardPad">
            <?php if (!$topBoysSubah): ?>
              <div style="color:#777; font-weight:900;"><?php echo h($tr['no_data']); ?></div>
            <?php else: ?>
              <?php
              $name = halaqa_display_name($topBoysSubah, $lang);
              $students = safe_int($topBoysSubah['students_count']);
              $mum = safe_int($topBoysSubah['mumayyaz_count']);
              $p = pct($mum, $students);
              ?>
              <div class="name"><?php echo h($name); ?></div>
              <div class="meta">
                <span class="badge boy"><?php echo h($tr['boys']); ?></span>
                <span class="badge secondary"><?php echo h($tr['subah']); ?></span>
                <span class="badge secondary"><?php echo h($tr['students']); ?>: <?php echo (int)$students; ?></span>
                <span class="badge primary"><?php echo h($tr['mumayyaz']); ?>: <?php echo (int)$mum; ?></span>
                <span class="badge secondary"><?php echo h($tr['pct']); ?>: <?php echo (int)$p; ?>%</span>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Boys asr top -->
        <div class="card topHCard">
          <div class="cardHead"><?php echo h($tr['top_halaqa_boys_asr']); ?></div>
          <div class="cardPad">
            <?php if (!$topBoysAsr): ?>
              <div style="color:#777; font-weight:900;"><?php echo h($tr['no_data']); ?></div>
            <?php else: ?>
              <?php
              $name = halaqa_display_name($topBoysAsr, $lang);
              $students = safe_int($topBoysAsr['students_count']);
              $mum = safe_int($topBoysAsr['mumayyaz_count']);
              $p = pct($mum, $students);
              ?>
              <div class="name"><?php echo h($name); ?></div>
              <div class="meta">
                <span class="badge boy"><?php echo h($tr['boys']); ?></span>
                <span class="badge secondary"><?php echo h($tr['asr']); ?></span>
                <span class="badge secondary"><?php echo h($tr['students']); ?>: <?php echo (int)$students; ?></span>
                <span class="badge primary"><?php echo h($tr['mumayyaz']); ?>: <?php echo (int)$mum; ?></span>
                <span class="badge secondary"><?php echo h($tr['pct']); ?>: <?php echo (int)$p; ?>%</span>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- TOP STUDENT -->
      <div class="sectionHead" style="margin-top:6px;">
        <div class="sectionTitle"><?php echo h($tr['mumtaaz_talaba']); ?></div>
      </div>

      <section class="grid3" style="grid-template-columns: 1fr;">
        <div class="card">
          <div class="cardPad">
            <?php if (trim($topStudentName) === '' || trim($topStudentScore) === ''): ?>
              <div style="color:#777; font-weight:900;"><?php echo h($tr['no_results']); ?></div>
            <?php else: ?>
              <div style="font-weight:900; font-size:15px;"><?php echo h($topStudentName); ?></div>
              <div style="margin-top:8px;">
                <span class="badge primary"><?php echo h($tr['score']); ?>: <?php echo h($topStudentScore); ?></span>
              </div>
            <?php endif; ?>
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
  </script>

</body>

</html>