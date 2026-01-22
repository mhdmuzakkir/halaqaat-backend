<?php
// student_info.php (FULL) - Student info editor (shuba + mumayyaz + progress)
// Same login rules + Urdu/English toggle + RTL/LTR + Noto Nastaliq

require_once __DIR__ . '/db.php';

session_name(defined('SESSION_NAME') ? SESSION_NAME : 'kahaf_session');
session_start();

if (empty($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (($_SESSION['role'] ?? '') !== 'admin') { header("Location: dashboard.php"); exit; }

// Language toggle
$allowedLang = ['ur','en'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowedLang, true)) {
  $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'ur';
$isRtl = ($lang === 'ur');

$T = [
  'ur' => [
    'app' => 'کہف حلقات',
    'page' => 'طالبعلم معلومات',
    'nav_dashboard' => 'ڈیش بورڈ',
    'nav_halqaat' => 'حلقات',
    'nav_students' => 'طلبہ',
    'logout' => 'لاگ آؤٹ',
    'switch_en' => 'English',
    'switch_ur' => 'اردو',

    'back_halaqa' => 'واپس حلقہ',
    'student' => 'طالبعلم',
    'save' => 'محفوظ کریں',
    'saved' => 'معلومات محفوظ ہوگئیں ✅',
    'err_db' => 'محفوظ نہیں ہوا۔',
    'err_notfound' => 'ریکارڈ نہیں ملا۔',

    'name' => 'نام',
    'gender' => 'جنس',
    'boy' => 'طالب علم',
    'girl' => 'طالبہ',

    'shuba' => 'شعبہ',
    'qaida' => 'قائدہ',
    'nazira' => 'ناظرہ',
    'hifz' => 'حفظ',

    'mumayyaz' => 'ممیز',
    'yes' => 'ہاں',
    'no' => 'نہیں',

    'progress' => 'پیش رفت',
    'takhti' => 'تختی (1 تا 28)',
    'surah' => 'سورۃ',

    'required' => 'یہ لازمی ہے۔',
  ],
  'en' => [
    'app' => 'Kahf Halaqat',
    'page' => 'Student Information',
    'nav_dashboard' => 'Dashboard',
    'nav_halqaat' => 'Halaqaat',
    'nav_students' => 'Students',
    'logout' => 'Logout',
    'switch_en' => 'English',
    'switch_ur' => 'اردو',

    'back_halaqa' => 'Back to Halaqa',
    'student' => 'Student',
    'save' => 'Save',
    'saved' => 'Saved ✅',
    'err_db' => 'Could not save.',
    'err_notfound' => 'Record not found.',

    'name' => 'Name',
    'gender' => 'Gender',
    'boy' => 'Boy',
    'girl' => 'Girl',

    'shuba' => 'Shuba',
    'qaida' => 'Qaida',
    'nazira' => 'Nazira',
    'hifz' => 'Hifz',

    'mumayyaz' => 'Mumayyaz',
    'yes' => 'Yes',
    'no' => 'No',

    'progress' => 'Progress',
    'takhti' => 'Takhti (1 to 28)',
    'surah' => 'Surah',

    'required' => 'Required.',
  ],
];
$tr = $T[$lang];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($student_id <= 0) { header("Location: halaqaat_admin.php"); exit; }

$msg = '';
$err = '';

// Surah list (1..114) – display names in Arabic (works for Urdu+English UI)
$surahs = [
  1=>"Al-Fatiha",2=>"Al-Baqara",3=>"Aal-E-Imran",4=>"An-Nisa",5=>"Al-Ma'idah",6=>"Al-An'am",7=>"Al-A'raf",8=>"Al-Anfal",
  9=>"At-Tawbah",10=>"Yunus",11=>"Hud",12=>"Yusuf",13=>"Ar-Ra'd",14=>"Ibrahim",15=>"Al-Hijr",16=>"An-Nahl",
  17=>"Al-Isra",18=>"Al-Kahf",19=>"Maryam",20=>"Taha",21=>"Al-Anbiya",22=>"Al-Hajj",23=>"Al-Mu'minun",24=>"An-Nur",
  25=>"Al-Furqan",26=>"Ash-Shu'ara",27=>"An-Naml",28=>"Al-Qasas",29=>"Al-Ankabut",30=>"Ar-Rum",31=>"Luqman",32=>"As-Sajdah",
  33=>"Al-Ahzab",34=>"Saba",35=>"Fatir",36=>"Ya-Sin",37=>"As-Saffat",38=>"Sad",39=>"Az-Zumar",40=>"Ghafir",
  41=>"Fussilat",42=>"Ash-Shura",43=>"Az-Zukhruf",44=>"Ad-Dukhan",45=>"Al-Jathiyah",46=>"Al-Ahqaf",47=>"Muhammad",48=>"Al-Fath",
  49=>"Al-Hujurat",50=>"Qaf",51=>"Adh-Dhariyat",52=>"At-Tur",53=>"An-Najm",54=>"Al-Qamar",55=>"Ar-Rahman",56=>"Al-Waqi'ah",
  57=>"Al-Hadid",58=>"Al-Mujadila",59=>"Al-Hashr",60=>"Al-Mumtahanah",61=>"As-Saf",62=>"Al-Jumu'ah",63=>"Al-Munafiqun",64=>"At-Taghabun",
  65=>"At-Talaq",66=>"At-Tahrim",67=>"Al-Mulk",68=>"Al-Qalam",69=>"Al-Haqqah",70=>"Al-Ma'arij",71=>"Nuh",72=>"Al-Jinn",
  73=>"Al-Muzzammil",74=>"Al-Muddaththir",75=>"Al-Qiyamah",76=>"Al-Insan",77=>"Al-Mursalat",78=>"An-Naba",79=>"An-Nazi'at",80=>"Abasa",
  81=>"At-Takwir",82=>"Al-Infitar",83=>"Al-Mutaffifin",84=>"Al-Inshiqaq",85=>"Al-Buruj",86=>"At-Tariq",87=>"Al-A'la",88=>"Al-Ghashiyah",
  89=>"Al-Fajr",90=>"Al-Balad",91=>"Ash-Shams",92=>"Al-Layl",93=>"Ad-Duha",94=>"Ash-Sharh",95=>"At-Tin",96=>"Al-Alaq",
  97=>"Al-Qadr",98=>"Al-Bayyinah",99=>"Az-Zalzalah",100=>"Al-Adiyat",101=>"Al-Qari'ah",102=>"At-Takathur",103=>"Al-Asr",104=>"Al-Humazah",
  105=>"Al-Fil",106=>"Quraysh",107=>"Al-Ma'un",108=>"Al-Kawthar",109=>"Al-Kafirun",110=>"An-Nasr",111=>"Al-Masad",112=>"Al-Ikhlas",
  113=>"Al-Falaq",114=>"An-Nas"
];

// Fetch student + halaqa
$sql = "
  SELECT s.*, h.id AS halaqa_id, h.name_ur AS halaqa_name_ur, h.name_en AS halaqa_name_en
  FROM students s
  INNER JOIN halaqaat h ON h.id = s.halaqa_id
  WHERE s.id=?
  LIMIT 1
";
$st = $conn->prepare($sql);
$st->bind_param("i", $student_id);
$st->execute();
$student = $st->get_result()->fetch_assoc();
$st->close();

if (!$student) {
  http_response_code(404);
  echo h($tr['err_notfound']);
  exit;
}

$halaqa_id = (int)$student['halaqa_id'];
$halaqaName = $isRtl ? ($student['halaqa_name_ur'] ?: $student['halaqa_name_en']) : ($student['halaqa_name_en'] ?: $student['halaqa_name_ur']);

function norm_shuba($s){
  $s = strtolower(trim((string)$s));
  if (in_array($s, ['qaida','nazira','hifz'], true)) return $s;
  return 'qaida';
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $shuba = norm_shuba($_POST['shuba'] ?? 'qaida');
  $mumayyaz = !empty($_POST['mumayyaz']) ? 1 : 0;

  $qaida_takhti = isset($_POST['qaida_takhti']) ? (int)$_POST['qaida_takhti'] : 0;
  $surah_current = isset($_POST['surah_current']) ? (int)$_POST['surah_current'] : 0;

  // Enforce rule:
  // - qaida => takhti required 1..28, surah NULL
  // - hifz/nazira => surah required 1..114, takhti NULL
  $qaida_takhti_db = null;
  $surah_current_db = null;

  if ($shuba === 'qaida') {
    if ($qaida_takhti < 1) $qaida_takhti = 1;
    if ($qaida_takhti > 28) $qaida_takhti = 28;
    $qaida_takhti_db = $qaida_takhti;
    $surah_current_db = null;
  } else {
    if ($surah_current < 1) $surah_current = 1;
    if ($surah_current > 114) $surah_current = 114;
    $surah_current_db = $surah_current;
    $qaida_takhti_db = null;
  }

  $upd = $conn->prepare("
    UPDATE students
    SET shuba=?,
        mumayyaz=?,
        qaida_takhti=?,
        surah_current=?
    WHERE id=?
    LIMIT 1
  ");
  if (!$upd) {
    $err = $tr['err_db'];
  } else {
    // bind null safely
    // i: mumayyaz, id; (qaida_takhti, surah_current) can be null => use "i" but pass null (mysqli allows null)
    $upd->bind_param(
      "siiii",
      $shuba,
      $mumayyaz,
      $qaida_takhti_db,
      $surah_current_db,
      $student_id
    );
    if ($upd->execute()) {
      $msg = $tr['saved'];
      // refresh data
      $st = $conn->prepare($sql);
      $st->bind_param("i", $student_id);
      $st->execute();
      $student = $st->get_result()->fetch_assoc();
      $st->close();
    } else {
      $err = $tr['err_db'];
    }
    $upd->close();
  }
}

// UI values
$studentName = $isRtl ? ($student['full_name_ur'] ?: $student['full_name_en']) : ($student['full_name_en'] ?: $student['full_name_ur']);
$gender = strtolower((string)($student['gender'] ?? 'boy'));
$genderLabel = ($gender === 'girl') ? $tr['girl'] : $tr['boy'];

$shuba = norm_shuba($student['shuba'] ?? 'qaida');
$mumayyaz = (int)($student['mumayyaz'] ?? 0);
$qaida_takhti = (int)($student['qaida_takhti'] ?? 1);
$surah_current = (int)($student['surah_current'] ?? 1);
?>
<!doctype html>
<html lang="<?php echo h($lang); ?>" dir="<?php echo $isRtl ? 'rtl' : 'ltr'; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
  <title><?php echo h($tr['page']); ?> — <?php echo h($tr['app']); ?></title>

  <style>
    /* fonts */
    html[lang="en"] body, html[lang="en"] input, html[lang="en"] select, html[lang="en"] button, html[lang="en"] table, html[lang="en"] th, html[lang="en"] td,
    html[lang="en"] .sidebar, html[lang="en"] .main, html[lang="en"] .pill, html[lang="en"] .nav a { font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif !important; }
    html[lang="ur"] body, html[lang="ur"] input, html[lang="ur"] select, html[lang="ur"] button, html[lang="ur"] table, html[lang="ur"] th, html[lang="ur"] td,
    html[lang="ur"] .sidebar, html[lang="ur"] .main, html[lang="ur"] .pill, html[lang="ur"] .nav a { font-family:'Noto Nastaliq Urdu', serif !important; }

    :root{
      --primary:#3e846a; --secondary:#b18f6e; --accent:#444444; --bg:#f6f2ee; --border:#e7ddd4;
      --sidebar:#3b3b3b; --sidebar2:#2f2f2f;
    }
    *{box-sizing:border-box}
    body{ margin:0; background:var(--bg); color:var(--accent); }
    .layout{ min-height:100vh; display:grid; grid-template-columns: 280px 1fr; }
    .layout.collapsed{ grid-template-columns: 90px 1fr; }
    .layout.collapsed .brand .name,.layout.collapsed .brand .sub,.layout.collapsed .nav a span.txt,.layout.collapsed .sidebarBottom{ display:none; }
    .layout.collapsed .nav a{ justify-content:center; padding:12px; }

    .sidebar{ background:linear-gradient(180deg, var(--sidebar), var(--sidebar2)); color:#fff; padding:16px; position:sticky; top:0; height:100vh; overflow:auto; }
    .brand{ display:flex; align-items:center; justify-content:space-between; gap:10px; padding:8px 8px 16px; border-bottom:1px solid rgba(255,255,255,.12); margin-bottom:12px; }
    .brand .name{ font-weight:900; font-size:16px; letter-spacing:.4px; font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif; }
    .brand .sub{ font-weight:700; font-size:12px; opacity:.85; font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif; }
    .sideToggle{ border:1px solid rgba(255,255,255,.25); background:rgba(255,255,255,.08); color:#fff; padding:8px 10px; border-radius:12px; font-weight:900; cursor:pointer; font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif; }

    .nav{ display:flex; flex-direction:column; gap:8px; margin-top:10px; }
    .nav a{ text-decoration:none; color:#fff; padding:10px 12px; border-radius:12px; font-weight:800; font-size:13px; display:flex; align-items:center; justify-content:space-between; border:1px solid rgba(255,255,255,.10); padding-left:16px; }
    .nav a.active{ background:rgba(255,255,255,.10); border-color:rgba(255,255,255,.18); }

    .sidebarBottom{ margin-top:14px; padding-top:14px; border-top:1px solid rgba(255,255,255,.12); display:flex; flex-direction:column; gap:10px; }

    .main{ padding:18px; }
    .topbar{ display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
    .titleBox{ background:linear-gradient(90deg, rgba(68,68,68,.92), rgba(68,68,68,.70)); color:#fff; border-radius:16px; padding:16px 18px; border-bottom:4px solid var(--secondary); flex:1; min-width:260px; }
    .titleBox .t{ margin:0; font-size:18px; font-weight:900; }
    .titleBox .s{ margin:6px 0 0; opacity:.92; font-size:13px; line-height:1.7; }

    .controls{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .pill{ text-decoration:none; padding:8px 12px; border-radius:999px; border:1px solid var(--border); background:#fff; color:var(--accent); font-weight:900; font-size:13px; }
    .pill.active{ background: var(--secondary); border-color: var(--secondary); color:#fff; }
    .pill.logout{ background:var(--primary); color:#fff; border-color:var(--primary); }

    .card{ background:#fff; border:1px solid var(--border); border-radius:16px; box-shadow:0 6px 18px rgba(0,0,0,.04); overflow:hidden; }
    .cardHeader{ padding:12px 14px; border-bottom:1px solid var(--border); font-weight:900; font-size:13px; }
    .cardBody{ padding:14px; }

    label{display:block; margin:10px 0 6px; font-weight:800; font-size:14px;}
    input, select{ width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:12px; outline:none; background:#fff; font-size:14px; }
    .row2{ display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
    @media (max-width: 700px){ .row2{ grid-template-columns:1fr; } }

    .btn{ width:100%; margin-top:14px; padding:12px; border:0; border-radius:12px; background:var(--primary); color:#fff; font-weight:900; cursor:pointer; font-size:15px; }

    .msg{ padding:10px 12px; border-radius:12px; background:rgba(62,132,106,.10); border:1px solid rgba(62,132,106,.25); font-weight:900; margin-bottom:12px; }
    .err{ padding:10px 12px; border-radius:12px; background:#fff3f3; border:1px solid #f2c7c7; color:#8a1f1f; font-weight:900; margin-bottom:12px; }
  </style>
</head>
<body>
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
        <a href="dashboard_admin.php"><span class="txt"><?php echo h($tr['nav_dashboard']); ?></span></a>
        <a href="halaqaat_admin.php"><span class="txt"><?php echo h($tr['nav_halqaat']); ?></span></a>
        <a class="active" href="student_info.php?id=<?php echo (int)$student_id; ?>"><span class="txt"><?php echo h($tr['page']); ?></span></a>
      </nav>

      <div class="sidebarBottom">
        <a class="pill logout" style="text-align:center;" href="logout.php"><?php echo h($tr['logout']); ?></a>
      </div>
    </aside>

    <main class="main">
      <div class="topbar">
        <div class="titleBox">
          <h1 class="t"><?php echo h($tr['page']); ?></h1>
          <p class="s">
            <?php echo h($tr['student']); ?>: <?php echo h($studentName); ?> — <?php echo h($tr['gender']); ?>: <?php echo h($genderLabel); ?>
            <br>
            <?php echo h($halaqaName); ?> —
            <a class="pill" href="halaqa_view.php?id=<?php echo (int)$halaqa_id; ?>" style="display:inline-block;"><?php echo h($tr['back_halaqa']); ?></a>
          </p>
        </div>

        <div class="controls">
          <a class="pill <?php echo $lang==='ur' ? 'active' : ''; ?>" href="?id=<?php echo (int)$student_id; ?>&lang=ur"><?php echo h($tr['switch_ur']); ?></a>
          <a class="pill <?php echo $lang==='en' ? 'active' : ''; ?>" href="?id=<?php echo (int)$student_id; ?>&lang=en"><?php echo h($tr['switch_en']); ?></a>
          <a class="pill logout" href="logout.php"><?php echo h($tr['logout']); ?></a>
        </div>
      </div>

      <section class="card">
        <div class="cardHeader"><?php echo h($tr['student']); ?></div>
        <div class="cardBody">

          <?php if ($msg): ?><div class="msg"><?php echo h($msg); ?></div><?php endif; ?>
          <?php if ($err): ?><div class="err"><?php echo h($err); ?></div><?php endif; ?>

          <form method="post" autocomplete="off">
            <div class="row2">
              <div>
                <label><?php echo h($tr['shuba']); ?></label>
                <select name="shuba" id="shuba" onchange="syncProgressUI()">
                  <option value="qaida"  <?php echo $shuba==='qaida'?'selected':''; ?>><?php echo h($tr['qaida']); ?></option>
                  <option value="nazira" <?php echo $shuba==='nazira'?'selected':''; ?>><?php echo h($tr['nazira']); ?></option>
                  <option value="hifz"   <?php echo $shuba==='hifz'?'selected':''; ?>><?php echo h($tr['hifz']); ?></option>
                </select>
              </div>

              <div>
                <label><?php echo h($tr['mumayyaz']); ?></label>
                <select name="mumayyaz">
                  <option value="0" <?php echo $mumayyaz===0?'selected':''; ?>><?php echo h($tr['no']); ?></option>
                  <option value="1" <?php echo $mumayyaz===1?'selected':''; ?>><?php echo h($tr['yes']); ?></option>
                </select>
              </div>
            </div>

            <div class="row2">
              <div id="boxTakhti">
                <label><?php echo h($tr['takhti']); ?></label>
                <select name="qaida_takhti">
                  <?php for ($i=1; $i<=28; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($qaida_takhti===$i)?'selected':''; ?>><?php echo $i; ?></option>
                  <?php endfor; ?>
                </select>
              </div>

              <div id="boxSurah">
                <label><?php echo h($tr['surah']); ?></label>
                <select name="surah_current">
                  <?php foreach ($surahs as $num => $name): ?>
                    <option value="<?php echo (int)$num; ?>" <?php echo ($surah_current===(int)$num)?'selected':''; ?>>
                      <?php echo (int)$num . " — " . h($name); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <button class="btn" type="submit"><?php echo h($tr['save']); ?></button>
          </form>

        </div>
      </section>
    </main>
  </div>

  <script>
    function syncProgressUI(){
      var shuba = document.getElementById('shuba')?.value || 'qaida';
      var boxTakhti = document.getElementById('boxTakhti');
      var boxSurah = document.getElementById('boxSurah');
      if (!boxTakhti || !boxSurah) return;

      if (shuba === 'qaida') {
        boxTakhti.style.display = '';
        boxSurah.style.display = 'none';
      } else {
        boxTakhti.style.display = 'none';
        boxSurah.style.display = '';
      }
    }
    syncProgressUI();

    function isMobile(){ return window.innerWidth <= 980; }
    function toggleSidebar(){
      var layout = document.querySelector('.layout');
      if (!layout) return;
      layout.classList.toggle('collapsed');
    }
  </script>
</body>
</html>
