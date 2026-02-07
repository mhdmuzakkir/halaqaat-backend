<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
require_role('admin');

$lang = get_language();
$tr = get_translations($lang);

$editMode = false;
$halaqa = [
  'id' => '',
  'name_ur' => '',
  'name_en' => '',
  'ustaaz_user_id' => '',
  'gender' => 'baneen',
  'session' => 'subah',
  'state' => 'active',
  'location' => ''
];

// Get teachers for dropdown
$teachers = [];
$result = $conn->query("SELECT id, full_name, role FROM users WHERE role IN ('ustaaz', 'ustadah') AND status = 'active' ORDER BY full_name");
while ($row = $result->fetch_assoc()) {
  $teachers[] = $row;
}

// Edit mode
if (isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $stmt = $conn->prepare("SELECT * FROM halaqaat WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $halaqa = $result->fetch_assoc();
    $editMode = true;
  }
}

// Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nameUr = trim($_POST['name_ur'] ?? '');
  $nameEn = trim($_POST['name_en'] ?? '');
  $ustaazId = $_POST['ustaaz_user_id'] ?: null;
  $gender = $_POST['gender'] ?? 'baneen';
  $session = $_POST['session'] ?? 'subah';
  $state = $_POST['state'] ?? 'active';
  $location = trim($_POST['location'] ?? '');
  
  if (empty($nameUr)) {
    $error = $lang === 'ur' ? 'حلقہ کا نام ضروری ہے۔' : 'Halaqa name is required.';
  } else {
    if ($editMode) {
      $stmt = $conn->prepare("
        UPDATE halaqaat 
        SET name_ur = ?, name_en = ?, ustaaz_user_id = ?, gender = ?, session = ?, state = ?, location = ?
        WHERE id = ?
      ");
      $stmt->bind_param("ssissssi", $nameUr, $nameEn, $ustaazId, $gender, $session, $state, $location, $halaqa['id']);
      
      if ($stmt->execute()) {
        header("Location: halaqaat_list.php?msg=updated");
        exit;
      } else {
        $error = $lang === 'ur' ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
      }
    } else {
      $stmt = $conn->prepare("
        INSERT INTO halaqaat (name_ur, name_en, ustaaz_user_id, gender, session, state, location)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->bind_param("ssissss", $nameUr, $nameEn, $ustaazId, $gender, $session, $state, $location);
      
      if ($stmt->execute()) {
        header("Location: halaqaat_list.php?msg=created");
        exit;
      } else {
        $error = $lang === 'ur' ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
      }
    }
  }
}

include __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="cardHeader">
    <span><?php echo $editMode ? h($tr['edit']) : h($tr['add_new']); ?> <?php echo h($tr['halaqa']); ?></span>
    <a href="halaqaat_list.php" class="pill"><?php echo h($tr['cancel']); ?></a>
  </div>
  <div class="cardBody">
    <?php if (!empty($error)): ?>
    <div class="msg msgError mb2"><?php echo h($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
      <div class="row">
        <div class="col-md-12">
          <div class="formGroup">
            <label class="formLabel"><?php echo $lang === 'ur' ? 'حلقہ کا نام' : 'Halaqa Name'; ?> *</label>
            <input type="text" name="name_ur" class="formInput" value="<?php echo h($halaqa['name_ur']); ?>" required />
          </div>
        </div>
      </div>
      <input type="hidden" name="name_en" value="" />
      
      <div class="row">
        <div class="col-md-6">
          <div class="formGroup">
            <label class="formLabel"><?php echo h($tr['ustaaz']); ?></label>
            <select name="ustaaz_user_id" class="formSelect">
              <option value=""><?php echo $lang === 'ur' ? '-- استاد منتخب کریں --' : '-- Select Teacher --'; ?></option>
              <?php foreach ($teachers as $teacher): ?>
              <option value="<?php echo $teacher['id']; ?>" <?php echo $halaqa['ustaaz_user_id'] == $teacher['id'] ? 'selected' : ''; ?>>
                <?php echo h($teacher['full_name']); ?> (<?php echo h($tr[$teacher['role']]); ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="formGroup">
            <label class="formLabel"><?php echo $lang === 'ur' ? 'مقام' : 'Location'; ?></label>
            <input type="text" name="location" class="formInput" value="<?php echo h($halaqa['location']); ?>" />
          </div>
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-4">
          <div class="formGroup">
            <label class="formLabel"><?php echo h($tr['gender']); ?> *</label>
            <select name="gender" class="formSelect" required>
              <option value="baneen" <?php echo $halaqa['gender'] === 'baneen' ? 'selected' : ''; ?>><?php echo h($tr['baneen']); ?></option>
              <option value="banaat" <?php echo $halaqa['gender'] === 'banaat' ? 'selected' : ''; ?>><?php echo h($tr['banaat']); ?></option>
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="formGroup">
            <label class="formLabel"><?php echo h($tr['session']); ?> *</label>
            <select name="session" class="formSelect" required>
              <option value="subah" <?php echo $halaqa['session'] === 'subah' ? 'selected' : ''; ?>><?php echo h($tr['subah']); ?></option>
              <option value="asr" <?php echo $halaqa['session'] === 'asr' ? 'selected' : ''; ?>><?php echo h($tr['asr']); ?></option>
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="formGroup">
            <label class="formLabel"><?php echo $lang === 'ur' ? 'حالت' : 'Status'; ?> *</label>
            <select name="state" class="formSelect" required>
              <option value="active" <?php echo $halaqa['state'] === 'active' ? 'selected' : ''; ?>><?php echo h($tr['active']); ?></option>
              <option value="paused" <?php echo $halaqa['state'] === 'paused' ? 'selected' : ''; ?>><?php echo h($tr['paused']); ?></option>
              <option value="stopped" <?php echo $halaqa['state'] === 'stopped' ? 'selected' : ''; ?>><?php echo h($tr['stopped']); ?></option>
            </select>
          </div>
        </div>
      </div>
      
      <div class="mt3">
        <button type="submit" class="btn btnPrimary">
          <i class="bi bi-check-lg"></i>
          <?php echo h($tr['save']); ?>
        </button>
        <a href="halaqaat_list.php" class="btn" style="background: #e9ecef; color: #333;">
          <?php echo h($tr['cancel']); ?>
        </a>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
