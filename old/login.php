<?php
// login.php
require_once __DIR__ . '/db.php';

session_name(defined('SESSION_NAME') ? SESSION_NAME : 'kahaf_session');
session_start();

// -------------------- Language (Urdu + English) --------------------
$allowedLang = ['ur', 'en'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowedLang, true)) {
  $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'ur';

$T = [
  'ur' => [
    'title' => 'لاگ اِن',
    'app' => 'کہف حلقات',
    'username' => 'یوزرنیم',
    'password' => 'پاس ورڈ',
    'login' => 'لاگ اِن کریں',
    'switch_en' => 'English',
    'switch_ur' => 'اردو',
    'invalid' => 'غلط یوزرنیم یا پاس ورڈ',
    'inactive' => 'آپ کا اکاؤنٹ غیر فعال ہے۔ ایڈمن سے رابطہ کریں۔',
    'required' => 'یوزرنیم اور پاس ورڈ لازمی ہیں۔',
    'note' => 'براہِ کرم اپنا یوزرنیم اور پاس ورڈ درج کریں۔',
  ],
  'en' => [
    'title' => 'Login',
    'app' => 'Kahf Halaqat',
    'username' => 'Username',
    'password' => 'Password',
    'login' => 'Sign in',
    'switch_en' => 'English',
    'switch_ur' => 'اردو',
    'invalid' => 'Invalid username or password',
    'inactive' => 'Your account is inactive. Contact admin.',
    'required' => 'Username and password are required.',
    'note' => 'Please enter your username and password.',
  ],
];

$isRtl = ($lang === 'ur');
$tr = $T[$lang];

// If already logged in, go to dashboard
if (!empty($_SESSION['user_id'])) {
  header("Location: dashboard.php");
  exit;
}

// -------------------- Login handling --------------------
$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  if ($username === '' || $password === '') {
    $error = $tr['required'];
  } else {
    $stmt = $conn->prepare("SELECT id, full_name, username, password_hash, role, is_active FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user) {
      $error = $tr['invalid'];
    } elseif ((int)$user['is_active'] !== 1) {
      $error = $tr['inactive'];
    } elseif (!password_verify($password, $user['password_hash'])) {
      $error = $tr['invalid'];
    } else {
      session_regenerate_id(true);
      $_SESSION['user_id'] = (int)$user['id'];
      $_SESSION['full_name'] = $user['full_name'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['lang'] = $lang;

      header("Location: dashboard.php");
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>" dir="<?php echo $isRtl ? 'rtl' : 'ltr'; ?>">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Fonts (FIXED) -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">

  <title><?php echo htmlspecialchars($tr['title']); ?> — <?php echo htmlspecialchars($tr['app']); ?></title>

  <style>
    :root {
      --primary: #0f2d3d;
      --secondary: #aa815e;
      --accent: #444444;
      /* charcoal */
      --bg: #f6f2ee;
      --card: #ffffff;
      --border: #e7ddd4;
    }

    * {
      box-sizing: border-box
    }

    body {
      margin: 0;
      background: var(--bg);
      color: var(--accent);
      font-family: 'Noto Nastaliq Urdu', serif;
      /* Urdu default */
    }

    .wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }

    .card {
      width: 100%;
      max-width: 420px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 14px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, .06);
      overflow: hidden;
    }

    /* HEADER: modern */
    .header {
      padding: 28px 22px;
      background: var(--primary);
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 4px solid var(--secondary);
      font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }

    .brand {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .brand .app {
      font-weight: 900;
      letter-spacing: .6px;
      color: #ffffff;
      font-size: 22px;
      font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }

    .brand .title {
      font-weight: 700;
      color: rgba(255, 255, 255, 0.92);
      font-size: 14px;
      font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }

    .lang {
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .lang a {
      text-decoration: none;
      font-weight: 800;
      font-size: 13px;
      padding: 7px 14px;
      border-radius: 999px;
      border: 1px solid rgba(255, 255, 255, 0.6);
      color: #ffffff;
      background: transparent;
      font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }

    .lang a.active {
      background: var(--secondary);
      border-color: var(--secondary);
      color: #ffffff;
    }

    .body {
      padding: 18px;
    }

    /* Urdu content should look Nastaliq */
    .note {
      margin: 0 0 14px 0;
      color: #666;
      font-size: 14px;
      line-height: 1.9;
    }

    .error {
      margin: 0 0 14px 0;
      padding: 10px 12px;
      border: 1px solid #f2c7c7;
      background: #fff3f3;
      color: #8a1f1f;
      border-radius: 10px;
      font-weight: 700;
      font-size: 13px;
      line-height: 1.9;
    }

    /* Labels in Urdu = Nastaliq, but English mode should be Montserrat */
    html[lang="en"] .note,
    html[lang="en"] .error {
      font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
      line-height: 1.6;
    }

    label {
      display: block;
      font-weight: 800;
      margin: 10px 0 6px;
      font-size: 14px;
    }

    html[lang="en"] label {
      font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
      font-size: 13px;
    }

    /* Inputs: keep modern for both languages (best UX) */
    input {
      width: 100%;
      padding: 11px 12px;
      border: 1px solid var(--border);
      border-radius: 10px;
      font-size: 14px;
      outline: none;
      font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }

    input:focus {
      border-color: rgba(62, 132, 106, .55);
      box-shadow: 0 0 0 4px rgba(62, 132, 106, .12);
    }

    .btn {
      width: 100%;
      margin-top: 16px;
      padding: 12px;
      border: 0;
      border-radius: 12px;
      font-weight: 900;
      font-size: 15px;
      cursor: pointer;
      background: var(--primary);
      color: #fff;
      font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }

    .btn:hover {
      filter: brightness(0.95);
    }
  </style>
</head>

<body>
  <div class="wrap">
    <div class="card">

      <div class="header">
        <div class="brand">
          <div class="app"><?php echo htmlspecialchars($tr['app']); ?></div>
          <div class="title"><?php echo htmlspecialchars($tr['title']); ?></div>
        </div>

        <div class="lang">
          <a class="<?php echo $lang === 'ur' ? 'active' : ''; ?>" href="?lang=ur"><?php echo htmlspecialchars($tr['switch_ur']); ?></a>
          <a class="<?php echo $lang === 'en' ? 'active' : ''; ?>" href="?lang=en"><?php echo htmlspecialchars($tr['switch_en']); ?></a>
        </div>
      </div>

      <div class="body">
        <p class="note"><?php echo htmlspecialchars($tr['note']); ?></p>

        <?php if ($error !== ''): ?>
          <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
          <label for="username"><?php echo htmlspecialchars($tr['username']); ?></label>
          <input id="username" name="username" type="text" value="<?php echo htmlspecialchars($username); ?>" required>

          <label for="password"><?php echo htmlspecialchars($tr['password']); ?></label>
          <input id="password" name="password" type="password" required>

          <button class="btn" type="submit"><?php echo htmlspecialchars($tr['login']); ?></button>
        </form>
      </div>

    </div>
  </div>
</body>

</html>