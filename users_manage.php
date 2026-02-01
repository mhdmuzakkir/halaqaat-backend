<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
require_role('admin');

$lang = get_language();
$tr = get_translations($lang);

$editMode = false;
$user = [
  'id' => '',
  'full_name' => '',
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
    if ($id == $_SESSION['user_id']) {
      $error = $lang === 'ur' ? 'آپ اپنے آپ کو حذف نہیں کر سکتے۔' : 'You cannot delete yourself.';
    } else {
      $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      header("Location: users_manage.php?msg=deleted");
      exit;
    }
  }
  
  $fullName = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $role = $_POST['role'] ?? 'ustaaz';
  $password = $_POST['password'] ?? '';
  
  if (empty($fullName)) {
    $error = $lang === 'ur' ? 'نام ضروری ہے۔' : 'Name is required.';
  } elseif (empty($email)) {
    $error = $lang === 'ur' ? 'ای میل ضروری ہے۔' : 'Email is required.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = $lang === 'ur' ? 'درست ای میل درج کریں۔' : 'Please enter a valid email.';
  } else {
    // Check email uniqueness
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkId = $editMode ? $user['id'] : 0;
    $checkStmt->bind_param("si", $email, $checkId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
      $error = $lang === 'ur' ? 'یہ ای میل پہلے سے استعمال میں ہے۔' : 'This email is already in use.';
    } else {
      if ($editMode) {
        if (!empty($password)) {
          $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
          $stmt = $conn->prepare("
            UPDATE users 
            SET full_name = ?, email = ?, phone = ?, address = ?, role = ?, password = ?
            WHERE id = ?
          ");
          $stmt->bind_param("ssssssi", $fullName, $email, $phone, $address, $role, $hashedPassword, $user['id']);
        } else {
          $stmt = $conn->prepare("
            UPDATE users 
            SET full_name = ?, email = ?, phone = ?, address = ?, role = ?
            WHERE id = ?
          ");
          $stmt->bind_param("sssssi", $fullName, $email, $phone, $address, $role, $user['id']);
        }
        
        if ($stmt->execute()) {
          header("Location: users_manage.php?msg=updated");
          exit;
        } else {
          $error = $lang === 'ur' ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
        }
      } else {
        if (empty($password)) {
          $error = $lang === 'ur' ? 'نئے صارف کے لیے پاس ورڈ ضروری ہے۔' : 'Password is required for new users.';
        } else {
          $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
          $stmt = $conn->prepare("
            INSERT INTO users (full_name, email, phone, address, role, password, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
          ");
          $stmt->bind_param("ssssss", $fullName, $email, $phone, $address, $role, $hashedPassword);
          
          if ($stmt->execute()) {
            header("Location: users_manage.php?msg=created");
            exit;
          } else {
            $error = $lang === 'ur' ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
          }
        }
      }
    }
  }
}

// Fetch all users for list view
$users = [];
if (!$editMode && !isset($_GET['id'])) {
  $result = $conn->query("SELECT * FROM users WHERE status = 'active' ORDER BY role, full_name");
  while ($row = $result->fetch_assoc()) {
    $users[] = $row;
  }
}

include __DIR__ . '/includes/header.php';
?>

<?php if (!$editMode && !isset($_GET['id'])): ?>
<!-- List View -->
<div class="sectionHeader">
  <div class="sectionTitle"><?php echo h($tr['nav_ustaaz']); ?></div>
  <a href="users_manage.php?action=add" class="pill secondary">
    <i class="bi bi-plus-lg"></i>
    <?php echo h($tr['add_new']); ?>
  </a>
</div>

<div class="searchWrap mb2" style="max-width: 400px;">
  <i class="bi bi-search"></i>
  <input type="text" id="userSearch" placeholder="<?php echo h($tr['search']); ?>" />
</div>

<div class="card">
  <div class="cardBody p-0">
    <div class="tableContainer">
      <table class="table mb-0" id="usersTable">
        <thead>
          <tr>
            <th><?php echo h($tr['name']); ?></th>
            <th><?php echo h($tr['email']); ?></th>
            <th><?php echo h($tr['role']); ?></th>
            <th><?php echo $lang === 'ur' ? 'فون' : 'Phone'; ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr class="user-item">
            <td>
              <strong><?php echo h($u['full_name']); ?></strong>
              <?php if ($u['id'] == $_SESSION['user_id']): ?>
                <span class="tag green" style="margin-left: 8px;"><?php echo $lang === 'ur' ? 'آپ' : 'You'; ?></span>
              <?php endif; ?>
            </td>
            <td><?php echo h($u['email']); ?></td>
            <td>
              <span class="tag <?php 
                echo $u['role'] === 'admin' ? 'blue' : 
                     ($u['role'] === 'mushrif' ? 'orange' : 
                     ($u['role'] === 'mumtahin' ? 'pink' : 'green')); 
              ?>">
                <?php echo h($tr[$u['role']]); ?>
              </span>
            </td>
            <td><?php echo h($u['phone'] ?: '-'); ?></td>
            <td>
              <a href="users_manage.php?id=<?php echo $u['id']; ?>" class="pill" style="padding: 4px 10px;">
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

<script>
setupSearch('userSearch', '#usersTable', '.user-item', ['td']);
</script>

<?php else: ?>
<!-- Add/Edit Form -->
<div class="card">
  <div class="cardHeader">
    <span><?php echo $editMode ? h($tr['edit']) : h($tr['add_new']); ?> <?php echo h($tr['nav_ustaaz']); ?></span>
    <a href="users_manage.php" class="pill"><?php echo h($tr['cancel']); ?></a>
  </div>
  <div class="cardBody">
    <?php if (!empty($error)): ?>
    <div class="msg msgError mb2"><?php echo h($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
      <div class="row">
        <div class="col-md-6">
          <div class="formGroup">
            <label class="formLabel"><?php echo h($tr['name']); ?> *</label>
            <input type="text" name="full_name" class="formInput" value="<?php echo h($user['full_name']); ?>" required />
          </div>
        </div>
        <div class="col-md-6">
          <div class="formGroup">
            <label class="formLabel"><?php echo h($tr['email']); ?> *</label>
            <input type="email" name="email" class="formInput" value="<?php echo h($user['email']); ?>" required />
          </div>
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-6">
          <div class="formGroup">
            <label class="formLabel"><?php echo h($tr['role']); ?> *</label>
            <select name="role" class="formSelect" required>
              <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>><?php echo h($tr['admin']); ?></option>
              <option value="mushrif" <?php echo $user['role'] === 'mushrif' ? 'selected' : ''; ?>><?php echo h($tr['mushrif']); ?></option>
              <option value="ustaaz" <?php echo $user['role'] === 'ustaaz' ? 'selected' : ''; ?>><?php echo h($tr['ustaaz']); ?></option>
              <option value="ustadah" <?php echo $user['role'] === 'ustadah' ? 'selected' : ''; ?>><?php echo h($tr['ustadah']); ?></option>
              <option value="mumtahin" <?php echo $user['role'] === 'mumtahin' ? 'selected' : ''; ?>><?php echo h($tr['mumtahin']); ?></option>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="formGroup">
            <label class="formLabel"><?php echo $lang === 'ur' ? 'فون' : 'Phone'; ?></label>
            <input type="tel" name="phone" class="formInput" value="<?php echo h($user['phone']); ?>" />
          </div>
        </div>
      </div>
      
      <div class="formGroup">
        <label class="formLabel"><?php echo $lang === 'ur' ? 'پتہ' : 'Address'; ?></label>
        <textarea name="address" class="formInput" rows="2"><?php echo h($user['address']); ?></textarea>
      </div>
      
      <div class="formGroup">
        <label class="formLabel">
          <?php echo h($tr['password']); ?>
          <?php if ($editMode): ?>
            <small class="text-muted">(<?php echo $lang === 'ur' ? 'خالی چھوڑیں تبدیل کرنے کے لیے' : 'Leave empty to keep unchanged'; ?>)</small>
          <?php else: ?>
            <span class="text-danger">*</span>
          <?php endif; ?>
        </label>
        <input type="password" name="password" class="formInput" <?php echo $editMode ? '' : 'required'; ?> />
      </div>
      
      <div class="mt3">
        <button type="submit" class="btn btnPrimary">
          <i class="bi bi-check-lg"></i>
          <?php echo h($tr['save']); ?>
        </button>
        <a href="users_manage.php" class="btn" style="background: #e9ecef; color: #333;">
          <?php echo h($tr['cancel']); ?>
        </a>
        <?php if ($editMode && $user['id'] != $_SESSION['user_id']): ?>
        <button type="submit" name="action" value="delete" class="btn btnDanger" style="float: right;" onclick="return confirmDelete()">
          <i class="bi bi-trash"></i>
          <?php echo h($tr['delete']); ?>
        </button>
        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
