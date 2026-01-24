<?php
// halaqa_view.php (FULL - dashboard-like sidebar toggle + students add/list + remove/delete + removed section)
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
    'page' => 'حلقہ تفصیل',
    'nav_dashboard' => 'ڈیش بورڈ',
    'nav_halqaat' => 'حلقات',
    'nav_students' => 'طلبہ',
    'logout' => 'لاگ آؤٹ',
    'switch_en' => 'English',
    'switch_ur' => 'اردو',

    'back' => 'واپس حلقات',
    'halaqa' => 'حلقہ',
    'group' => 'گروپ',
    'session' => 'سیشن',
    'status' => 'اسٹیٹس',
    'active' => 'فعال',
    'inactive' => 'غیر فعال',

    // singular
    'boy' => 'طالب علم',
    'girl' => 'طالبہ',

    'subah' => 'صبح',
    'asr' => 'عصر',

    'students' => 'طلبہ',
    'add_student' => 'نیا طالبعلم شامل کریں',
    'full_name_ur' => 'نام (اردو/عربی)',
    'save' => 'محفوظ کریں',

    'student_added' => 'طالبعلم شامل ہوگیا ✅',
    'err_missing' => 'براہ کرم نام درج کریں۔',
    'err_script' => 'صرف اردو/عربی حروف لکھیں (English نہیں)۔',
    'err_db' => 'محفوظ نہیں ہوا۔',
    'no_students' => 'ابھی تک کوئی طالبعلم موجود نہیں۔',

    'tbl_name' => 'نام',
    'tbl_gender' => 'جنس',
    'tbl_status' => 'اسٹیٹس',
    'tbl_created' => 'تاریخ',
    'tbl_actions' => 'ایکشن',

    'remove' => 'ہٹائیں',
    'delete' => 'ڈیلیٹ',
    'restore' => 'بحال کریں',

    'removed_students' => 'ہٹائے گئے طلبہ',
    'removed_type' => 'قسم',
    'removed_reason' => 'وجہ',
    'removed_at' => 'ہٹانے کی تاریخ',

    'type_left' => 'چھوڑ دیا',
    'type_removed' => 'ہٹایا گیا',
    'type_changed' => 'حلقہ تبدیل',
    'remove_confirm' => 'کیا آپ واقعی ہٹانا چاہتے ہیں؟',
    'delete_confirm' => 'کیا آپ واقعی ڈیلیٹ کرنا چاہتے ہیں؟ یہ واپس نہیں آئے گا۔',
    'student_removed' => 'طالبعلم ہٹا دیا گیا ✅',
    'student_deleted' => 'طالبعلم ڈیلیٹ ہوگیا ✅',
    'student_restored' => 'طالبعلم بحال ہوگیا ✅',
  ],
  'en' => [
    'app' => 'Kahf Halaqat',
    'page' => 'Halaqa Details',
    'nav_dashboard' => 'Dashboard',
    'nav_halqaat' => 'Halaqaat',
    'nav_students' => 'Students',
    'logout' => 'Logout',
    'switch_en' => 'English',
    'switch_ur' => 'اردو',

    'back' => 'Back to Halaqaat',
    'halaqa' => 'Halaqa',
    'group' => 'Group',
    'session' => 'Session',
    'status' => 'Status',
    'active' => 'Active',
    'inactive' => 'Inactive',

    // singular
    'boy' => 'Boy',
    'girl' => 'Girl',

    'subah' => 'Subah',
    'asr' => 'Asr',

    'students' => 'Students',
    'add_student' => 'Add Student',
    'full_name_ur' => 'Name (Urdu/Arabic)',
    'save' => 'Save',

    'student_added' => 'Student added ✅',
    'err_missing' => 'Please enter a name.',
    'err_script' => 'Please type only Urdu/Arabic letters (no English).',
    'err_db' => 'Could not save.',
    'no_students' => 'No students yet.',

    'tbl_name' => 'Name',
    'tbl_gender' => 'Gender',
    'tbl_status' => 'Status',
    'tbl_created' => 'Created',
    'tbl_actions' => 'Actions',

    'remove' => 'Remove',
    'delete' => 'Delete',
    'restore' => 'Restore',

    'removed_students' => 'Removed Students',
    'removed_type' => 'Type',
    'removed_reason' => 'Reason',
    'removed_at' => 'Removed At',

    'type_left' => 'Left',
    'type_removed' => 'Removed',
    'type_changed' => 'Changed Halaqa',
    'remove_confirm' => 'Are you sure you want to remove?',
    'delete_confirm' => 'Are you sure you want to delete permanently?',
    'student_removed' => 'Student removed ✅',
    'student_deleted' => 'Student deleted ✅',
    'student_restored' => 'Student restored ✅',
  ],
];

$tr = $T[$lang];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** final: boy/girl only */
function normalize_halaqa_gender($g) {
    $g = strtolower(trim((string)$g));
    if ($g === 'girls' || $g === 'girl') return 'girl';
    return 'boy';
}

/** Validate Urdu/Arabic input:
 * - must include at least one Arabic-script char
 * - must NOT include English letters A-Z
 */
function is_valid_urdu_arabic_name($s) {
    $s = trim((string)$s);
    if ($s === '') return false;
    if (preg_match('/[A-Za-z]/', $s)) return false;
    if (!preg_match('/\p{Arabic}/u', $s)) return false;
    return true;
}

$halaqa_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($halaqa_id <= 0) {
    header("Location: halaqaat_admin.php");
    exit;
}

// Fetch halaqa
$stmt = $conn->prepare("SELECT id, name_ur, name_en, gender, session, is_active, created_at FROM halaqaat WHERE id=? LIMIT 1");
$stmt->bind_param("i", $halaqa_id);
$stmt->execute();
$halaqa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$halaqa) {
    http_response_code(404);
    echo "Halaqa not found";
    exit;
}

$halaqaGender = normalize_halaqa_gender($halaqa['gender'] ?? 'boy'); // boy/girl
$isGirlHalaqa = ($halaqaGender === 'girl');

$msg = '';
$err = '';

/** Security: ensure a student id belongs to current halaqa (for remove/delete/restore) */
function student_belongs_to_halaqa(mysqli $conn, int $student_id, int $halaqa_id): bool {
    $st = $conn->prepare("SELECT id FROM students WHERE id=? AND halaqa_id=? LIMIT 1");
    if (!$st) return false;
    $st->bind_param("ii", $student_id, $halaqa_id);
    $st->execute();
    $ok = (bool)$st->get_result()->fetch_assoc();
    $st->close();
    return $ok;
}

/* Handle actions */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ADD STUDENT (Urdu/Arabic only)
    if ($action === 'add_student') {
        $full_name_ur = trim($_POST['full_name_ur'] ?? '');

        if ($full_name_ur === '') {
            $err = $tr['err_missing'];
        } elseif (!is_valid_urdu_arabic_name($full_name_ur)) {
            $err = $tr['err_script'];
        } else {
            // No transliteration/translation: keep EN same as UR for DB NOT NULL
            $full_name_en = $full_name_ur;

            // enforce halaqa gender (no mix)
            $gender = $halaqaGender; // boy/girl
            $status = 'active';

            $st = $conn->prepare("INSERT INTO students (halaqa_id, full_name_en, full_name_ur, gender, status) VALUES (?, ?, ?, ?, ?)");
            if (!$st) {
                $err = $tr['err_db'];
            } else {
                $st->bind_param("issss", $halaqa_id, $full_name_en, $full_name_ur, $gender, $status);
                if ($st->execute()) {
                    $msg = $tr['student_added'];
                    $_POST = [];
                } else {
                    $err = $tr['err_db'];
                }
                $st->close();
            }
        }
    }

    // REMOVE STUDENT (soft)
    if ($action === 'remove_student') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $removed_type = $_POST['removed_type'] ?? 'left';
        $removed_reason = trim($_POST['removed_reason'] ?? '');

        if (!in_array($removed_type, ['left','removed','changed_halaqa'], true)) {
            $removed_type = 'left';
        }

        if ($student_id > 0 && student_belongs_to_halaqa($conn, $student_id, $halaqa_id)) {
            $st = $conn->prepare("
                UPDATE students
                SET status='inactive',
                    removed_type=?,
                    removed_reason=?,
                    removed_at=NOW()
                WHERE id=? AND halaqa_id=?
                LIMIT 1
            ");
            if ($st) {
                $st->bind_param("ssii", $removed_type, $removed_reason, $student_id, $halaqa_id);
                if ($st->execute()) $msg = $tr['student_removed'];
                else $err = $tr['err_db'];
                $st->close();
            } else {
                $err = $tr['err_db'];
            }
        } else {
            $err = $tr['err_db'];
        }
    }

    // RESTORE STUDENT
    if ($action === 'restore_student') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        if ($student_id > 0 && student_belongs_to_halaqa($conn, $student_id, $halaqa_id)) {
            $st = $conn->prepare("
                UPDATE students
                SET status='active',
                    removed_type=NULL,
                    removed_reason=NULL,
                    removed_at=NULL
                WHERE id=? AND halaqa_id=?
                LIMIT 1
            ");
            if ($st) {
                $st->bind_param("ii", $student_id, $halaqa_id);
                if ($st->execute()) $msg = $tr['student_restored'];
                else $err = $tr['err_db'];
                $st->close();
            } else {
                $err = $tr['err_db'];
            }
        } else {
            $err = $tr['err_db'];
        }
    }

    // DELETE STUDENT (hard)
    if ($action === 'delete_student') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        if ($student_id > 0 && student_belongs_to_halaqa($conn, $student_id, $halaqa_id)) {
            $st = $conn->prepare("DELETE FROM students WHERE id=? AND halaqa_id=? LIMIT 1");
            if ($st) {
                $st->bind_param("ii", $student_id, $halaqa_id);
                if ($st->execute()) $msg = $tr['student_deleted'];
                else $err = $tr['err_db'];
                $st->close();
            } else {
                $err = $tr['err_db'];
            }
        } else {
            $err = $tr['err_db'];
        }
    }
}

// Fetch active students
$students_active = [];
$stA = $conn->prepare("
  SELECT id, full_name_en, full_name_ur, gender, status, created_at
  FROM students
  WHERE halaqa_id=? AND status='active'
  ORDER BY id DESC
");
$stA->bind_param("i", $halaqa_id);
$stA->execute();
$rA = $stA->get_result();
while ($row = $rA->fetch_assoc()) $students_active[] = $row;
$stA->close();

// Fetch removed students (inactive OR removed_at)
$students_removed = [];
$stR = $conn->prepare("
  SELECT id, full_name_en, full_name_ur, gender, status, created_at, removed_type, removed_reason, removed_at
  FROM students
  WHERE halaqa_id=? AND (status='inactive' OR removed_at IS NOT NULL)
  ORDER BY removed_at DESC, id DESC
");
$stR->bind_param("i", $halaqa_id);
$stR->execute();
$rR = $stR->get_result();
while ($row = $rR->fetch_assoc()) $students_removed[] = $row;
$stR->close();

// Labels
$halaqaName = $isRtl ? ($halaqa['name_ur'] ?: $halaqa['name_en']) : ($halaqa['name_en'] ?: $halaqa['name_ur']);
$groupLabel = $isGirlHalaqa ? $tr['girl'] : $tr['boy'];
$sessionLabel = (($halaqa['session'] ?? '') === 'asr') ? $tr['asr'] : $tr['subah'];
$statusLabel = ((int)($halaqa['is_active'] ?? 0) === 1) ? $tr['active'] : $tr['inactive'];

function removed_type_label($t, $tr) {
    if ($t === 'removed') return $tr['type_removed'];
    if ($t === 'changed_halaqa') return $tr['type_changed'];
    return $tr['type_left'];
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
    /* ===== Font enforcement ===== */
    html[lang="en"] body,
    html[lang="en"] input, html[lang="en"] select, html[lang="en"] option, html[lang="en"] button,
    html[lang="en"] table, html[lang="en"] th, html[lang="en"] td,
    html[lang="en"] .sidebar, html[lang="en"] .main, html[lang="en"] .pill, html[lang="en"] .nav a{
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif !important;
    }
    html[lang="ur"] body,
    html[lang="ur"] input, html[lang="ur"] select, html[lang="ur"] option, html[lang="ur"] button,
    html[lang="ur"] table, html[lang="ur"] th, html[lang="ur"] td,
    html[lang="ur"] .sidebar, html[lang="ur"] .main, html[lang="ur"] .pill, html[lang="ur"] .nav a{
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

      /* requested */
      --boy:#2f6fd6;     /* blue */
      --girl:#d24e8a;    /* pink */
      --subah:#ff8c00;   /* orange */
      --asr:#6b3f1d;     /* brown */
    }

    *{box-sizing:border-box}
    body{ margin:0; background:var(--bg); color:var(--accent); }
    .layout{ min-height:100vh; display:grid; grid-template-columns: 280px 1fr; }

    /* PC collapse (dashboard style) */
    .layout.collapsed{ grid-template-columns: 90px 1fr; }
    .layout.collapsed .brand .name,
    .layout.collapsed .brand .sub,
    .layout.collapsed .nav a span.txt,
    .layout.collapsed .nav .navDisabled span.txt,
    .layout.collapsed .sidebarBottom{ display:none; }
    .layout.collapsed .nav a,
    .layout.collapsed .nav .navDisabled{ justify-content:center; padding:12px; }

    .sidebar{
      background:linear-gradient(180deg, var(--sidebar), var(--sidebar2));
      color:#fff; padding:16px; position:sticky; top:0; height:100vh; overflow:auto;
    }
    .brand{
      display:flex; align-items:center; justify-content:space-between; gap:10px;
      padding:8px 8px 16px; border-bottom:1px solid rgba(255,255,255,.12); margin-bottom:12px;
    }
    .brand .name{ font-weight:900; font-size:16px; letter-spacing:.4px; font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif; }
    .brand .sub{ font-weight:700; font-size:12px; opacity:.85; font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif; }
    .sideToggle{
      border:1px solid rgba(255,255,255,.25);
      background:rgba(255,255,255,.08);
      color:#fff; padding:8px 10px; border-radius:12px; font-weight:900; cursor:pointer;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
    .sideToggle:hover{ background:rgba(255,255,255,.12); }

    .nav{ display:flex; flex-direction:column; gap:8px; margin-top:10px; }
    .nav a, .nav .navDisabled{
      color:#fff; padding:10px 12px; border-radius:12px; font-weight:800; font-size:13px;
      display:flex; align-items:center; justify-content:space-between; gap:10px;
      border:1px solid rgba(255,255,255,.10);
      position:relative; padding-left:40px;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
      text-decoration:none;
      background:transparent;
    }
    .nav a:hover{ background:rgba(255,255,255,.08); }
    .nav a.active{ background:rgba(255,255,255,.10); border-color:rgba(255,255,255,.18); }
    .nav .navDisabled{ opacity:.45; cursor:not-allowed; }
    html[dir="rtl"] .nav a, html[dir="rtl"] .nav .navDisabled{ padding-left:12px; padding-right:40px; }

    .sidebarBottom{
      margin-top:14px; padding-top:14px; border-top:1px solid rgba(255,255,255,.12);
      display:flex; flex-direction:column; gap:10px;
    }

    .main{ padding:18px; }
    .topbar{
      display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:14px;
    }
    .titleBox{
      background:linear-gradient(90deg, rgba(68,68,68,.92), rgba(68,68,68,.70));
      color:#fff; border-radius:16px; padding:16px 18px; border-bottom:4px solid var(--secondary);
      flex:1; min-width:260px;
    }
    .titleBox .t{ margin:0; font-size:18px; font-weight:900; }
    .titleBox .s{ margin:6px 0 0; opacity:.92; font-size:13px; line-height:1.7; }

    .controls{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .pill{
      text-decoration:none; padding:8px 12px; border-radius:999px; border:1px solid var(--border);
      background:#fff; color:var(--accent); font-weight:900; font-size:13px;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
    .pill.active{ background: var(--secondary); border-color: var(--secondary); color:#fff; }
    .pill.logout{ background:var(--primary); color:#fff; border-color:var(--primary); }

    .grid{display:grid; grid-template-columns: 1fr 1fr; gap:12px;}
    @media (max-width: 980px){ .grid{grid-template-columns:1fr;} }

    .card{
      background:#fff; border:1px solid var(--border); border-radius:16px;
      box-shadow:0 6px 18px rgba(0,0,0,.04); overflow:hidden;
    }
    .cardHeader{ padding:12px 14px; border-bottom:1px solid var(--border); font-weight:900; font-size:13px; }
    .cardBody{padding:14px;}

    label{display:block; margin:10px 0 6px; font-weight:800; font-size:14px;}
    input, select{
      width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:12px;
      outline:none; background:#fff; font-size:14px;
    }
    input:focus, select:focus{
      border-color:rgba(62,132,106,.55);
      box-shadow:0 0 0 4px rgba(62,132,106,.12);
    }

    .btn{
      width:100%; margin-top:14px; padding:12px; border:0; border-radius:12px;
      background:var(--primary); color:#fff; font-weight:900; cursor:pointer; font-size:15px;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }

    .msg{
      padding:10px 12px; border-radius:12px; background:rgba(62,132,106,.10);
      border:1px solid rgba(62,132,106,.25); font-weight:900; margin-bottom:12px;
    }
    .err{
      padding:10px 12px; border-radius:12px; background:#fff3f3;
      border:1px solid #f2c7c7; color:#8a1f1f; font-weight:900; margin-bottom:12px;
    }

    table{width:100%; border-collapse:separate; border-spacing:0; font-size:13px;}
    th, td{padding:10px 10px; border-bottom:1px solid var(--border); text-align:start; vertical-align:top;}
    th{font-weight:900; color:#555; background:rgba(177,143,110,.10);}

    .tag{
      display:inline-block; padding:4px 10px; border-radius:999px;
      border:1px solid var(--border); font-weight:900; font-size:12px; background:#fff; white-space:nowrap;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
    .tag.boy{ background: var(--boy); border-color: var(--boy); color:#fff; }
    .tag.girl{ background: var(--girl); border-color: var(--girl); color:#fff; }
    .tag.subah{ background: var(--subah); border-color: var(--subah); color:#fff; }
    .tag.asr{ background: var(--asr); border-color: var(--asr); color:#fff; }
    .tag.neutral{ background: rgba(0,0,0,.06); border-color: rgba(0,0,0,.12); color:#333; }

    .btnRow{ display:flex; gap:8px; flex-wrap:wrap; }
    .btnMini{
      display:inline-block; padding:6px 10px; border-radius:10px;
      border:1px solid var(--border); background:#fff; font-weight:900;
      text-decoration:none; color:var(--accent);
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
      white-space:nowrap; cursor:pointer;
    }
    .btnMini:hover{ background:rgba(62,132,106,.08); border-color:rgba(62,132,106,.25); }
    .btnMini.danger{ border-color:#f2c7c7; color:#8a1f1f; }
    .btnMini.danger:hover{ background:#fff3f3; }

    .removeBox{
      margin-top:8px; padding:10px; border:1px dashed var(--border); border-radius:12px; background:rgba(0,0,0,.02);
      display:none;
    }
    .removeBox.show{ display:block; }

    /* Mobile sidebar */
    .menuBtn{
      display:none; background:#3e846a; border:1px solid rgba(255,255,255,.6); color:#ffffff;
      padding:10px 12px; border-radius:12px; font-weight:900; font-size:18px; cursor:pointer; line-height:1;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }
    @media (max-width: 980px){
      .layout{grid-template-columns: 1fr;}
      .main{padding:16px;}
      .menuBtn{display:inline-block;}

      .sidebar{
        position:fixed; z-index:50; top:0; bottom:0; width:280px; overflow:auto;
        transition:transform .2s ease;
      }
      html[dir="ltr"] .sidebar{ left:0; transform:translateX(-110%); }
      html[dir="rtl"] .sidebar{ right:0; transform:translateX(110%); }
      .sidebar.open{ transform:translateX(0) !important; }

      .overlay{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:40; }
      .overlay.show{display:block;}
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
        <a class="active" href="halaqa_view.php?id=<?php echo (int)$halaqa_id; ?>"><span class="txt"><?php echo h($tr['page']); ?></span></a>
        <a href="dashboard_admin.php"><span class="txt"><?php echo h($tr['nav_dashboard']); ?></span></a>
        <a href="halaqaat_admin.php"><span class="txt"><?php echo h($tr['nav_halqaat']); ?></span></a>
        <a href="students_admin.php"><span class="txt"><?php echo h($tr['nav_students']); ?></span></a>

        <div class="navDisabled"><span class="txt">Ustaaz</span></div>
        <div class="navDisabled"><span class="txt">Exams</span></div>
        <div class="navDisabled"><span class="txt">Reports</span></div>
        <div class="navDisabled"><span class="txt">Settings</span></div>
      </nav>

      <div class="sidebarBottom">
        <a class="pill logout" style="text-align:center;" href="logout.php"><?php echo h($tr['logout']); ?></a>
      </div>
    </aside>

    <main class="main">
      <div class="topbar">
        <button class="menuBtn" type="button" onclick="toggleSidebar()">☰</button>

        <div class="titleBox">
          <h1 class="t"><?php echo h($halaqaName); ?></h1>
          <p class="s">
            <a class="pill" href="halaqaat_admin.php" style="display:inline-block;"><?php echo h($tr['back']); ?></a>
          </p>
        </div>

        <div class="controls">
          <a class="pill <?php echo $lang==='ur' ? 'active' : ''; ?>" href="?id=<?php echo (int)$halaqa_id; ?>&lang=ur"><?php echo h($tr['switch_ur']); ?></a>
          <a class="pill <?php echo $lang==='en' ? 'active' : ''; ?>" href="?id=<?php echo (int)$halaqa_id; ?>&lang=en"><?php echo h($tr['switch_en']); ?></a>
          <a class="pill logout" href="logout.php"><?php echo h($tr['logout']); ?></a>
        </div>
      </div>

      <div class="grid">
        <!-- Halaqa summary + add student -->
        <section class="card">
          <div class="cardHeader"><?php echo h($tr['halaqa']); ?></div>
          <div class="cardBody">

            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
              <span class="tag <?php echo $isGirlHalaqa ? 'girl' : 'boy'; ?>"><?php echo h($groupLabel); ?></span>
              <span class="tag <?php echo (($halaqa['session'] ?? '') === 'asr') ? 'asr' : 'subah'; ?>"><?php echo h($sessionLabel); ?></span>
              <span class="tag neutral"><?php echo h($statusLabel); ?></span>
              <span class="tag neutral"><?php echo h($tr['students']); ?>: <?php echo (int)count($students_active); ?></span>
            </div>

            <div style="margin-top:12px; font-weight:900;"><?php echo h($tr['add_student']); ?></div>

            <?php if ($msg): ?><div class="msg"><?php echo h($msg); ?></div><?php endif; ?>
            <?php if ($err): ?><div class="err"><?php echo h($err); ?></div><?php endif; ?>

            <form method="post" autocomplete="off">
              <input type="hidden" name="action" value="add_student">

              <label><?php echo h($tr['full_name_ur']); ?></label>
              <input name="full_name_ur" value="<?php echo h($_POST['full_name_ur'] ?? ''); ?>" required>

              <div style="margin-top:10px;">
                <span class="tag <?php echo $isGirlHalaqa ? 'girl' : 'boy'; ?>">
                  <?php echo h($tr['group']); ?>: <?php echo h($groupLabel); ?>
                </span>
              </div>

              <button class="btn" type="submit"><?php echo h($tr['save']); ?></button>
            </form>
          </div>
        </section>

        <!-- Active Students list -->
        <section class="card">
          <div class="cardHeader"><?php echo h($tr['students']); ?></div>
          <div class="cardBody" style="overflow:auto;">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th><?php echo h($tr['tbl_name']); ?></th>
                  <th><?php echo h($tr['tbl_gender']); ?></th>
                  <th><?php echo h($tr['tbl_status']); ?></th>
                  <th><?php echo h($tr['tbl_created']); ?></th>
                  <th><?php echo h($tr['tbl_actions']); ?></th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($students_active)): ?>
                <tr><td colspan="6" style="color:#777; padding:14px;"><?php echo h($tr['no_students']); ?></td></tr>
              <?php else: ?>
                <?php foreach ($students_active as $s): ?>
                  <?php
                    $sid = (int)$s['id'];
                    $sg = strtolower((string)($s['gender'] ?? ''));
                    $isGirlStudent = ($sg === 'girl');
                    $sName = $isRtl ? ($s['full_name_ur'] ?: $s['full_name_en']) : ($s['full_name_en'] ?: $s['full_name_ur']);
                    $sStatus = ($s['status'] ?? 'active') === 'inactive' ? $tr['inactive'] : $tr['active'];
                  ?>
                  <tr>
                    <td><?php echo $sid; ?></td>
                    <td><?php echo h($sName); ?></td>
                    <td>
                      <span class="tag <?php echo $isGirlStudent ? 'girl' : 'boy'; ?>">
                        <?php echo h($isGirlStudent ? $tr['girl'] : $tr['boy']); ?>
                      </span>
                    </td>
                    <td><span class="tag neutral"><?php echo h($sStatus); ?></span></td>
                    <td style="white-space:nowrap;"><?php echo h($s['created_at'] ?? ''); ?></td>

                    <td style="min-width:220px;">
                      
                    <div class="btnRow">
                    <a class="btnMini" href="student_info.php?id=<?php echo $sid; ?>" target="_blank" rel="noopener">
  <?php echo ($lang==='ur' ? 'معلومات' : 'Info'); ?>
</a>    
                    <button class="btnMini" type="button" onclick="toggleRemoveBox(<?php echo $sid; ?>)"><?php echo h($tr['remove']); ?></button>

                        <form method="post" style="display:inline;" onsubmit="return confirm('<?php echo h($tr['delete_confirm']); ?>');">
                          <input type="hidden" name="action" value="delete_student">
                          <input type="hidden" name="student_id" value="<?php echo $sid; ?>">
                          <button class="btnMini danger" type="submit"><?php echo h($tr['delete']); ?></button>
                        </form>
                      </div>

                      <!-- Remove form (soft) -->
                      <div class="removeBox" id="removeBox-<?php echo $sid; ?>">
                        <form method="post" onsubmit="return confirm('<?php echo h($tr['remove_confirm']); ?>');">
                          <input type="hidden" name="action" value="remove_student">
                          <input type="hidden" name="student_id" value="<?php echo $sid; ?>">

                          <label style="margin:8px 0 6px;"><?php echo h($tr['removed_type']); ?></label>
                          <select name="removed_type">
                            <option value="left"><?php echo h($tr['type_left']); ?></option>
                            <option value="removed"><?php echo h($tr['type_removed']); ?></option>
                            <option value="changed_halaqa"><?php echo h($tr['type_changed']); ?></option>
                          </select>

                          <label style="margin:8px 0 6px;"><?php echo h($tr['removed_reason']); ?></label>
                          <input name="removed_reason" placeholder="<?php echo h($tr['removed_reason']); ?>">

                          <div style="margin-top:10px;">
                            <button class="btnMini" type="submit"><?php echo h($tr['remove']); ?></button>
                            <button class="btnMini" type="button" onclick="toggleRemoveBox(<?php echo $sid; ?>)"><?php echo $lang==='ur' ? 'بند کریں' : 'Close'; ?></button>
                          </div>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Removed Students -->
        <section class="card" style="grid-column:1 / -1;">
          <div class="cardHeader"><?php echo h($tr['removed_students']); ?></div>
          <div class="cardBody" style="overflow:auto;">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th><?php echo h($tr['tbl_name']); ?></th>
                  <th><?php echo h($tr['removed_type']); ?></th>
                  <th><?php echo h($tr['removed_reason']); ?></th>
                  <th><?php echo h($tr['removed_at']); ?></th>
                  <th><?php echo h($tr['tbl_actions']); ?></th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($students_removed)): ?>
                <tr><td colspan="6" style="color:#777; padding:14px;"><?php echo $lang==='ur' ? 'ابھی تک کوئی ریکارڈ نہیں' : 'No records yet'; ?></td></tr>
              <?php else: ?>
                <?php foreach ($students_removed as $s): ?>
                  <?php
                    $sid = (int)$s['id'];
                    $sName = $isRtl ? ($s['full_name_ur'] ?: $s['full_name_en']) : ($s['full_name_en'] ?: $s['full_name_ur']);
                    $rt = (string)($s['removed_type'] ?? 'left');
                    $rr = (string)($s['removed_reason'] ?? '');
                    $ra = (string)($s['removed_at'] ?? '');
                  ?>
                  <tr>
                    <td><?php echo $sid; ?></td>
                    <td><?php echo h($sName); ?></td>
                    <td><span class="tag neutral"><?php echo h(removed_type_label($rt, $tr)); ?></span></td>
                    <td><?php echo h($rr); ?></td>
                    <td style="white-space:nowrap;"><?php echo h($ra); ?></td>
                    <td>
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="restore_student">
                        <input type="hidden" name="student_id" value="<?php echo $sid; ?>">
                        <button class="btnMini" type="submit"><?php echo h($tr['restore']); ?></button>
                      </form>
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
        if (open) { sb.classList.add('open'); ov.classList.add('show'); }
        else { sb.classList.remove('open'); ov.classList.remove('show'); }
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

    function toggleRemoveBox(id){
      var el = document.getElementById('removeBox-' + id);
      if (!el) return;
      el.classList.toggle('show');
    }
  </script>
</body>
</html>
