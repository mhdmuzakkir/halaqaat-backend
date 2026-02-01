<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    header('Location: dashboard_' . $_SESSION['role'] . '.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = $isRTL ? 'براہ کرم ای میل اور پاس ورڈ درج کریں۔' : 'Please enter email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                header('Location: dashboard_' . $user['role'] . '.php');
                exit;
            } else {
                $error = $isRTL ? 'غلط پاس ورڈ۔' : 'Invalid password.';
            }
        } else {
            $error = $isRTL ? 'صارف نہیں ملا۔' : 'User not found.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('login'); ?> - <?php echo t('app_name'); ?></title>
    
    <?php if ($isRTL): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php else: ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #0f2d3d;
            --secondary-color: #aa815e;
        }
        
        <?php if ($isRTL): ?>
        body {
            font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', 'Urdu Typesetting', serif;
            line-height: 2;
        }
        <?php endif; ?>
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color) 0%, #1a4a63 100%);
            padding: 20px;
        }
        
        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo i {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .login-logo h1 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .login-logo p {
            color: var(--secondary-color);
            font-size: 1.1rem;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(170, 129, 94, 0.25);
        }
        
        .btn-login {
            background-color: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            width: 100%;
        }
        
        .btn-login:hover {
            background-color: #0a1f2a;
        }
        
        .lang-toggle {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 4px 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        
        .lang-toggle a {
            text-decoration: none;
            padding: 4px 12px;
            border-radius: 15px;
            transition: all 0.3s;
        }
        
        .lang-toggle a.active-ur {
            background-color: var(--secondary-color);
            color: #fff;
        }
        
        .lang-toggle a.active-en {
            background-color: #28a745;
            color: #fff;
        }
        
        .lang-toggle a:not(.active-ur):not(.active-en) {
            color: #666;
        }
        
        .input-group-text {
            background: transparent;
            border: 2px solid #e0e0e0;
            border-right: none;
            color: var(--secondary-color);
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <i class="bi bi-moon-stars-fill"></i>
                <h1><?php echo t('app_name'); ?></h1>
                <p><?php echo t('assalam_alaikum'); ?></p>
            </div>
            
            <div class="lang-toggle">
                <a href="?lang=ur" class="<?php echo $isRTL ? 'active-ur' : ''; ?>">ار</a>
                <span>|</span>
                <a href="?lang=en" class="<?php echo !$isRTL ? 'active-en' : ''; ?>">EN</a>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label"><?php echo t('email'); ?></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" name="email" placeholder="<?php echo $isRTL ? 'اپنا ای میل درج کریں' : 'Enter your email'; ?>" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label"><?php echo t('password'); ?></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" placeholder="<?php echo $isRTL ? 'اپنا پاس ورڈ درج کریں' : 'Enter your password'; ?>" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i><?php echo t('login'); ?>
                </button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
