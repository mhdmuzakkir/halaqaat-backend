<?php
require_once 'includes/header.php';
requireRole('admin');

$success = '';
$error = '';
$editMode = false;
$user = [
    'id' => '',
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'role' => 'ustaaz',
    'status' => 'active'
];

// Edit mode
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $editMode = true;
    }
}

// Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id = intval($_POST['user_id'] ?? 0);
        // Don't allow deleting yourself
        if ($id == $_SESSION['user_id']) {
            $error = $isRTL ? 'آپ اپنے آپ کو حذف نہیں کر سکتے۔' : 'You cannot delete yourself.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            header('Location: users_manage.php');
            exit;
        }
    }
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $role = $_POST['role'] ?? 'ustaaz';
    $password = $_POST['password'] ?? '';
    
    if (empty($name)) {
        $error = $isRTL ? 'نام ضروری ہے۔' : 'Name is required.';
    } elseif (empty($email)) {
        $error = $isRTL ? 'ای میل ضروری ہے۔' : 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $isRTL ? 'درست ای میل درج کریں۔' : 'Please enter a valid email.';
    } else {
        // Check email uniqueness
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkId = $editMode ? $user['id'] : 0;
        $checkStmt->bind_param("si", $email, $checkId);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $error = $isRTL ? 'یہ ای میل پہلے سے استعمال میں ہے۔' : 'This email is already in use.';
        } else {
            if ($editMode) {
                if (!empty($password)) {
                    // Update with password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, phone = ?, address = ?, role = ?, password = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param("ssssssi", $name, $email, $phone, $address, $role, $hashedPassword, $user['id']);
                } else {
                    // Update without password
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, phone = ?, address = ?, role = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param("sssssi", $name, $email, $phone, $address, $role, $user['id']);
                }
                
                if ($stmt->execute()) {
                    $success = $isRTL ? 'صارف کامیابی سے اپ ڈیٹ ہو گیا۔' : 'User updated successfully.';
                } else {
                    $error = $isRTL ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
                }
            } else {
                // Create new user
                if (empty($password)) {
                    $error = $isRTL ? 'نئے صارف کے لیے پاس ورڈ ضروری ہے۔' : 'Password is required for new users.';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("
                        INSERT INTO users (name, email, phone, address, role, password, status)
                        VALUES (?, ?, ?, ?, ?, ?, 'active')
                    ");
                    $stmt->bind_param("ssssss", $name, $email, $phone, $address, $role, $hashedPassword);
                    
                    if ($stmt->execute()) {
                        $success = $isRTL ? 'صارف کامیابی سے بنایا گیا۔' : 'User created successfully.';
                        $user = [
                            'id' => '',
                            'name' => '',
                            'email' => '',
                            'phone' => '',
                            'address' => '',
                            'role' => 'ustaaz',
                            'status' => 'active'
                        ];
                    } else {
                        $error = $isRTL ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
                    }
                }
            }
        }
    }
}

// Fetch all users for list view
$users = [];
if (!$editMode && !isset($_GET['id'])) {
    $result = $conn->query("SELECT * FROM users WHERE status = 'active' ORDER BY role, name");
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<div class="container py-4">
    <?php if (!$editMode && !isset($_GET['id'])): ?>
    <!-- List View -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title"><?php echo t('users'); ?></h2>
            <p class="greeting"><?php echo t('assalam_alaikum'); ?></p>
        </div>
        <a href="users_manage.php?action=add" class="btn btn-primary-custom">
            <i class="bi bi-plus-lg me-2"></i><?php echo t('add_new'); ?>
        </a>
    </div>
    
    <!-- Search -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control search-box" id="userSearch" placeholder="<?php echo $isRTL ? 'صارفین تلاش کریں...' : 'Search users...'; ?>">
            </div>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="usersTable">
                    <thead>
                        <tr>
                            <th><?php echo t('name'); ?></th>
                            <th><?php echo t('email'); ?></th>
                            <th><?php echo t('role'); ?></th>
                            <th><?php echo t('phone'); ?></th>
                            <th><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr class="user-item">
                            <td>
                                <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                                <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                <span class="badge bg-info ms-2"><?php echo $isRTL ? 'آپ' : 'You'; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $u['role'] === 'admin' ? 'danger' : 
                                         ($u['role'] === 'mushrif' ? 'warning text-dark' : 
                                         ($u['role'] === 'mumtahin' ? 'info' : 'primary')); 
                                ?>">
                                    <?php echo t($u['role']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($u['phone'] ?: '-'); ?></td>
                            <td>
                                <a href="users_manage.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php if (empty($users)): ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-3 text-muted"><?php echo t('no_data_found'); ?></p>
    </div>
    <?php endif; ?>
    
    <script>
    setupSearch('userSearch', '#usersTable', '.user-item', ['td']);
    </script>
    
    <?php else: ?>
    <!-- Add/Edit Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="mb-4">
                <h2 class="page-title"><?php echo $editMode ? t('edit') : t('add_new'); ?> <?php echo t('users'); ?></h2>
                <p class="greeting"><?php echo t('assalam_alaikum'); ?></p>
            </div>
            
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
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('email'); ?> <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('role'); ?> <span class="text-danger">*</span></label>
                                <select class="form-select" name="role" required>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>><?php echo t('admin'); ?></option>
                                    <option value="mushrif" <?php echo $user['role'] === 'mushrif' ? 'selected' : ''; ?>><?php echo t('mushrif'); ?></option>
                                    <option value="ustaaz" <?php echo $user['role'] === 'ustaaz' ? 'selected' : ''; ?>><?php echo t('ustaaz'); ?></option>
                                    <option value="ustadah" <?php echo $user['role'] === 'ustadah' ? 'selected' : ''; ?>><?php echo t('ustadah'); ?></option>
                                    <option value="mumtahin" <?php echo $user['role'] === 'mumtahin' ? 'selected' : ''; ?>><?php echo t('mumtahin'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('phone'); ?></label>
                                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('address'); ?></label>
                            <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <?php echo t('password'); ?>
                                <?php if ($editMode): ?>
                                <small class="text-muted">(<?php echo $isRTL ? 'خالی چھوڑیں تبدیل کرنے کے لیے' : 'Leave empty to keep unchanged'; ?>)</small>
                                <?php else: ?>
                                <span class="text-danger">*</span>
                                <?php endif; ?>
                            </label>
                            <input type="password" class="form-control" name="password" <?php echo $editMode ? '' : 'required'; ?>>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="bi bi-check-lg me-2"></i><?php echo t('save'); ?>
                            </button>
                            <a href="users_manage.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-2"></i><?php echo t('cancel'); ?>
                            </a>
                            <?php if ($editMode && $user['id'] != $_SESSION['user_id']): ?>
                            <button type="submit" name="action" value="delete" class="btn btn-danger ms-auto" onclick="return confirmDelete()">
                                <i class="bi bi-trash me-2"></i><?php echo t('delete'); ?>
                            </button>
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
