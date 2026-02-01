<?php
require_once 'includes/header.php';
requireLogin();

$success = '';
$error = '';
$userId = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        if (empty($name)) {
            $error = $isRTL ? 'نام ضروری ہے۔' : 'Name is required.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $phone, $address, $userId);
            
            if ($stmt->execute()) {
                $_SESSION['name'] = $name;
                $success = $isRTL ? 'پروفائل کامیابی سے اپ ڈیٹ ہو گئی۔' : 'Profile updated successfully.';
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                $error = $isRTL ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = $isRTL ? 'تمام پاس ورڈ فیلڈز ضروری ہیں۔' : 'All password fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = $isRTL ? 'نئے پاس ورڈ مماثل نہیں ہیں۔' : 'New passwords do not match.';
        } elseif (strlen($newPassword) < 6) {
            $error = $isRTL ? 'پاس ورڈ کم از کم 6 حروف کا ہونا چاہیے۔' : 'Password must be at least 6 characters.';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $error = $isRTL ? 'موجودہ پاس ورڈ غلط ہے۔' : 'Current password is incorrect.';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $userId);
            
            if ($stmt->execute()) {
                $success = $isRTL ? 'پاس ورڈ کامیابی سے تبدیل ہو گیا۔' : 'Password changed successfully.';
            } else {
                $error = $isRTL ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
            }
        }
    }
}
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-person-circle" style="font-size: 5rem; color: var(--primary-color);"></i>
                    </div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
                    <p class="text-muted mb-2"><?php echo ucfirst($user['role']); ?></p>
                    <span class="badge bg-success"><?php echo t('active'); ?></span>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-envelope me-2"></i><?php echo t('email'); ?></span>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-telephone me-2"></i><?php echo t('phone'); ?></span>
                        <span><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-geo-alt me-2"></i><?php echo t('address'); ?></span>
                        <span><?php echo htmlspecialchars($user['address'] ?: '-'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-calendar me-2"></i><?php echo $isRTL ? 'شمولیت' : 'Joined'; ?></span>
                        <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="col-lg-8">
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Update Profile -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-person-gear me-2"></i><?php echo t('update_profile'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('name'); ?></label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('email'); ?></label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('phone'); ?></label>
                                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('role'); ?></label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('address'); ?></label>
                            <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="bi bi-check-lg me-2"></i><?php echo t('save'); ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i><?php echo t('change_password'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><?php echo t('current_password'); ?></label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><?php echo t('new_password'); ?></label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><?php echo t('confirm_password'); ?></label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary-custom">
                            <i class="bi bi-key me-2"></i><?php echo t('change_password'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
