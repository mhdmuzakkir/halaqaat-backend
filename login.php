<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
  header("Location: dashboard_admin.php");
  exit;
}

$lang = get_language();
$tr = get_translations($lang);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  
  if (empty($email) || empty($password)) {
    $error = $lang === 'ur' ? 'براہ کرم ای میل اور پاس ورڈ درج کریں۔' : 'Please enter email and password.';
  } else {
    $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ? AND status = 'active'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
      $user = $result->fetch_assoc();
      if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        header("Location: dashboard_admin.php");
        exit;
      } else {
        $error = $lang === 'ur' ? 'غلط پاس ورڈ۔' : 'Invalid password.';
      }
    } else {
      $error = $lang === 'ur' ? 'صارف نہیں ملا۔' : 'User not found.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ur' ? 'rtl' : 'ltr'; ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo h($tr['app']); ?> — <?php echo h($tr['login']); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Noto+Nastaliq+Urdu:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body class="loginBody">
  <div class="loginCard">
    <div class="loginLogo">
      <div class="loginLogoIcon">🌙</div>
      <div class="loginLogoTitle"><?php echo h($tr['app']); ?></div>
      <div class="loginLogoSub"><?php echo h($tr['greeting']); ?></div>
    </div>
    
    <!-- Language Toggle -->
    <div class="langToggle">
      <a href="?lang=ur" class="<?php echo $lang === 'ur' ? 'active' : ''; ?>">اردو</a>
      <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
    </div>
    
    <?php if ($error): ?>
    <div class="msg msgError mb2"><?php echo h($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
      <div class="formGroup">
        <label class="formLabel"><?php echo h($tr['email']); ?></label>
        <input type="email" name="email" class="formInput" placeholder="admin@kahaf.com" required />
      </div>
      
      <div class="formGroup">
        <label class="formLabel"><?php echo h($tr['password']); ?></label>
        <input type="password" name="password" class="formInput" placeholder="••••••••" required />
      </div>
      
      <button type="submit" class="btn btnPrimary" style="width: 100%;">
        <i class="bi bi-box-arrow-in-right"></i>
        <?php echo h($tr['login']); ?>
      </button>
    </form>
    
    <div class="textCenter mt2" style="font-size: 12px; color: #666;">
      <?php echo $lang === 'ur' ? 'ڈیفالٹ: admin@kahaf.com / admin123' : 'Default: admin@kahaf.com / admin123'; ?>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
