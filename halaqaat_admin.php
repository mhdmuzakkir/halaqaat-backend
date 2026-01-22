<?php
// halaqaat_admin.php
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
    'back' => 'ڈیش بورڈ',
    'logout' => 'لاگ آؤٹ',
    'switch_en' => 'English',
    'switch_ur' => 'اردو',

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
  ],
  'en' => [
    'app' => 'Kahf Halaqat',
    'page' => 'Halaqaat Management',
    'back' => 'Dashboard',
    'logout' => 'Logout',
    'switch_en' => 'English',
    'switch_ur' => 'اردو',

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
  ],
];

$tr = $T[$lang];
$msg = '';
$err = '';

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_ur = trim($_POST['name_ur'] ?? '');
    $name_en = trim($_POST['name_en'] ?? '');
    $gender  = $_POST['gender'] ?? 'boys';
    $session = $_POST['session'] ?? 'subah';
    $is_active = !empty($_POST['is_active']) ? 1 : 0;

    // Enforce allowed values (safety)
    if (!in_array($gender, ['boys', 'girls'], true)) $gender = 'boys';
    if (!in_array($session, ['subah', 'asr'], true)) $session = 'subah';

    if ($name_ur === '') {
        $err = $tr['err_required'];
    } else {
        $sql = "INSERT INTO halaqaat (name_ur, name_en, gender, session, is_active)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $err = $tr['err_db'];
        } else {
            $stmt->bind_param("ssssi", $name_ur, $name_en, $gender, $session, $is_active);
            if ($stmt->execute()) {
                $msg = $tr['created'];
                $_POST = []; // clear form values after success
            } else {
                $err = $tr['err_db'];
            }
            $stmt->close();
        }
    }
}

// Fetch list
$halaqaat = [];
$res = $conn->query("SELECT id, name_ur, name_en, gender, session, is_active, created_at
                     FROM halaqaat
                     ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) $halaqaat[] = $row;
    $res->free();
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="<?php echo h($lang); ?>" dir="<?php echo $isRtl ? 'rtl' : 'ltr'; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">

  <title><?php echo h($tr['page']); ?> — <?php echo h($tr['app']); ?></title>

  <style>
    :root{
      --primary:#3e846a;
      --secondary:#b18f6e;
      --accent:#444444;
      --bg:#f6f2ee;
      --card:#ffffff;
      --border:#e7ddd4;
      --sidebar:#3b3b3b;
      --sidebar2:#2f2f2f;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      background:var(--bg);
      color:var(--accent);
      font-family:'Noto Nastaliq Urdu', serif;
    }
    html[lang="en"] body{
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }

    .layout{min-height:100vh; display:grid; grid-template-columns: 280px 1fr;}
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
      display:flex; align-items:center; justify-content:space-between; gap:10px;
      padding:8px 8px 16px;
      border-bottom:1px solid rgba(255,255,255,.12);
      margin-bottom:12px;
    }
    .brand .name{font-weight:900; font-size:16px; letter-spacing:.4px; font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;}
    .brand .sub{font-weight:700; font-size:12px; opacity:.85; font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;}

    .nav{display:flex; flex-direction:column; gap:8px; margin-top:10px;}
    .nav a{
      text-decoration:none; color:#fff;
      padding:10px 12px; border-radius:12px;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
      font-weight:800; font-size:13px;
      border:1px solid rgba(255,255,255,.10);
      background:transparent;
      display:block;
    }
    .nav a.active{background:rgba(255,255,255,.10); border-color:rgba(255,255,255,.18);}
    .nav a:hover{background:rgba(255,255,255,.08);}

    .main{padding:18px;}
    .topbar{
      display:flex; align-items:center; justify-content:space-between;
      gap:12px; flex-wrap:wrap; margin-bottom:14px;
    }
    .titleBox{
      background:linear-gradient(90deg, rgba(68,68,68,.92), rgba(68,68,68,.70));
      color:#fff; border-radius:16px;
      padding:16px 18px; border-bottom:4px solid var(--secondary);
      flex:1; min-width:260px;
    }
    .titleBox .t{margin:0; font-size:18px; font-weight:900;}
    .titleBox .s{margin:6px 0 0; opacity:.92; font-size:13px; line-height:1.7;}
    html[lang="en"] .titleBox{font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;}

    .controls{display:flex; gap:10px; flex-wrap:wrap; align-items:center;}
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
    .pill.active{background:var(--secondary); border-color:var(--secondary); color:#fff;}
    .pill.logout{background:var(--primary); border-color:var(--primary); color:#fff;}

    .grid{display:grid; grid-template-columns: 1fr 1fr; gap:12px;}
    @media (max-width: 980px){ .layout{grid-template-columns: 1fr;} .sidebar{display:none;} .grid{grid-template-columns:1fr;} }

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
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
      font-weight:900;
      font-size:13px;
    }
    .cardBody{padding:14px;}

    label{display:block; margin:10px 0 6px; font-weight:800; font-size:14px;}
    html[lang="en"] label{font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif; font-size:13px;}

    input, select{
      width:100%;
      padding:10px 12px;
      border:1px solid var(--border);
      border-radius:12px;
      outline:none;
      background:#fff;
      font-size:14px;
    }
    input:focus, select:focus{border-color:rgba(62,132,106,.55); box-shadow:0 0 0 4px rgba(62,132,106,.12);}

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
    .btn:hover{filter:brightness(.95);}

    .msg{
      padding:10px 12px;
      border-radius:12px;
      background:rgba(62,132,106,.10);
      border:1px solid rgba(62,132,106,.25);
      color:var(--accent);
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
    th{font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif; font-weight:900; color:#555; background:rgba(177,143,110,.10);}
    .tag{
      display:inline-block; padding:4px 10px; border-radius:999px;
      border:1px solid var(--border); font-weight:900; font-size:12px;
      font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
      background:#fff;
      white-space:nowrap;
    }
    .tag.primary{background:rgba(62,132,106,.12); border-color:rgba(62,132,106,.35);}
    .tag.secondary{background:rgba(177,143,110,.14); border-color:rgba(177,143,110,.45);}

    .checkRow{display:flex; align-items:center; gap:10px; margin-top:10px;}
    .checkRow input{width:auto; transform:scale(1.1);}
  </style>
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <div class="brand">
        <div>
          <div class="name"><?php echo h($tr['app']); ?></div>
          <div class="sub"><?php echo h($tr['page']); ?></div>
        </div>
      </div>

      <nav class="nav">
        <a href="dashboard_admin.php"><?php echo h($tr['back']); ?></a>
        <a class="active" href="halaqaat_admin.php"><?php echo h($tr['nav_halqaat'] ?? ($lang==='ur'?'حلقات':'Halaqaat')); ?></a>
        <a href="logout.php"><?php echo h($tr['logout']); ?></a>
      </nav>
    </aside>

    <main class="main">
      <div class="topbar">
        <div class="titleBox">
          <h1 class="t"><?php echo h($tr['page']); ?></h1>
          <p class="s"><?php echo h($tr['app']); ?></p>
        </div>

        <div class="controls">
          <a class="pill <?php echo $lang==='ur'?'active':''; ?>" href="?lang=ur"><?php echo h($tr['switch_ur']); ?></a>
          <a class="pill <?php echo $lang==='en'?'active':''; ?>" href="?lang=en"><?php echo h($tr['switch_en']); ?></a>
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
                  <select name="gender">
                    <option value="boys" <?php echo (($_POST['gender'] ?? 'boys')==='boys')?'selected':''; ?>><?php echo h($tr['boys']); ?></option>
                    <option value="girls" <?php echo (($_POST['gender'] ?? '')==='girls')?'selected':''; ?>><?php echo h($tr['girls']); ?></option>
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
                  <th><?php echo h($tr['status']); ?></th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($halaqaat)): ?>
                <tr><td colspan="5" style="color:#777; padding:14px;"><?php echo h($tr['no_data']); ?></td></tr>
              <?php else: ?>
                <?php foreach ($halaqaat as $hrow): ?>
                  <tr>
                    <td><?php echo (int)$hrow['id']; ?></td>
                    <td>
                      <div style="font-weight:900;"><?php echo h($hrow['name_ur']); ?></div>
                      <?php if (!empty($hrow['name_en'])): ?>
                        <div style="font-size:12px; color:#666; font-family:'Montserrat', system-ui;"><?php echo h($hrow['name_en']); ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (($hrow['gender'] ?? '') === 'girls'): ?>
                        <span class="tag secondary"><?php echo h($tr['girls']); ?></span>
                      <?php else: ?>
                        <span class="tag primary"><?php echo h($tr['boys']); ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (($hrow['session'] ?? '') === 'asr'): ?>
                        <span class="tag secondary"><?php echo h($tr['asr']); ?></span>
                      <?php else: ?>
                        <span class="tag secondary"><?php echo h($tr['subah']); ?></span>
                      <?php endif; ?>
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
</body>
</html>
