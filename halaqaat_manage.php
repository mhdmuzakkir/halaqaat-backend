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

// Get students in this halaqa
$halaqaStudents = [];
if ($editMode) {
  $stmt = $conn->prepare("SELECT id, full_name_ur, shuba, mumayyaz FROM students WHERE halaqa_id = ? AND status = 'active' ORDER BY full_name_ur");
  $stmt->bind_param("i", $halaqa['id']);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $halaqaStudents[] = $row;
  }
}

// Get students without halaqa for adding
$availableStudents = [];
if ($editMode) {
  $stmt = $conn->prepare("SELECT id, full_name_ur, gender, shuba FROM students WHERE (halaqa_id IS NULL OR halaqa_id = 0) AND status = 'active' AND gender = ? ORDER BY full_name_ur");
  $stmt->bind_param("s", $halaqa['gender']);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $availableStudents[] = $row;
  }
}

// Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'save';
  
  // Add students to halaqa
  if ($action === 'add_students' && $editMode) {
    $studentIds = $_POST['student_ids'] ?? [];
    if (!empty($studentIds)) {
      $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
      $stmt = $conn->prepare("UPDATE students SET halaqa_id = ? WHERE id IN ($placeholders)");
      
      // Bind parameters
      $types = 'i' . str_repeat('i', count($studentIds));
      $params = array_merge([$halaqa['id']], $studentIds);
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      
      header("Location: halaqaat_manage.php?id=" . $halaqa['id'] . "&msg=students_added");
      exit;
    }
  }
  
  // Remove student from halaqa
  if ($action === 'remove_student' && $editMode) {
    $studentId = intval($_POST['student_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE students SET halaqa_id = NULL WHERE id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    
    header("Location: halaqaat_manage.php?id=" . $halaqa['id'] . "&msg=student_removed");
    exit;
  }
  
  // Save halaqa
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

<div class="card mb-4">
  <div class="cardHeader">
    <span><?php echo $editMode ? h($tr['edit']) : h($tr['add_new']); ?> <?php echo h($tr['halaqa']); ?></span>
    <a href="halaqaat_list.php" class="pill"><?php echo h($tr['cancel']); ?></a>
  </div>
  <div class="cardBody">
    <?php if (!empty($error)): ?>
    <div class="msg msgError mb-3"><?php echo h($error); ?></div>
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
      
      <div class="mt-3">
        <button type="submit" name="action" value="save" class="btn btnPrimary">
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

<?php if ($editMode): ?>
<!-- Students in this Halaqa -->
<div class="card mb-4">
  <div class="cardHeader">
    <span><i class="bi bi-people-fill me-2"></i><?php echo $lang === 'ur' ? 'حلقہ کے طلباء' : 'Students in Halaqa'; ?> (<?php echo count($halaqaStudents); ?>)</span>
  </div>
  <div class="cardBody p-0">
    <?php if (empty($halaqaStudents)): ?>
      <div class="p-4 text-center text-muted">
        <?php echo $lang === 'ur' ? 'اس حلقہ میں کوئی طالب علم نہیں۔' : 'No students in this halaqa.'; ?>
      </div>
    <?php else: ?>
      <div class="tableContainer">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th><?php echo h($tr['name']); ?></th>
              <th><?php echo h($tr['shuba']); ?></th>
              <th><?php echo h($tr['mumayyaz']); ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($halaqaStudents as $index => $student): ?>
            <tr>
              <td><?php echo $index + 1; ?></td>
              <td><?php echo h($student['full_name_ur']); ?></td>
              <td><?php echo h($tr[$student['shuba']]); ?></td>
              <td>
                <?php if ($student['mumayyaz']): ?>
                  <span style="background: rgba(15,45,61,0.15); color: #0f2d3d; padding: 4px 8px; border-radius: 12px; font-size: 11px;"><i class="bi bi-star-fill"></i></span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td>
                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('<?php echo $lang === 'ur' ? 'کیا آپ اس طالب علم کو حلقہ سے ہٹانا چاہتے ہیں؟' : 'Remove this student from halaqa?'; ?>');">
                  <input type="hidden" name="action" value="remove_student">
                  <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                  <button type="submit" class="btn btnDanger btn-sm">
                    <i class="bi bi-x-lg"></i> <?php echo $lang === 'ur' ? 'ہٹائیں' : 'Remove'; ?>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Add Students to Halaqa -->
<?php if (!empty($availableStudents)): ?>
<div class="card">
  <div class="cardHeader">
    <span><i class="bi bi-person-plus-fill me-2"></i><?php echo $lang === 'ur' ? 'طلباء شامل کریں' : 'Add Students'; ?></span>
  </div>
  <div class="cardBody">
    <form method="POST" action="">
      <input type="hidden" name="action" value="add_students">
      
      <div class="formGroup">
        <label class="formLabel"><?php echo $lang === 'ur' ? 'طلباء منتخب کریں' : 'Select Students'; ?></label>
        <div style="max-height: 300px; overflow-y: auto; border: 1px solid var(--border); border-radius: 12px; padding: 10px;">
          <?php foreach ($availableStudents as $student): ?>
          <label style="display: flex; align-items: center; gap: 10px; padding: 8px; cursor: pointer; border-bottom: 1px solid var(--border);">
            <input type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>" style="width: 18px; height: 18px;">
            <span><?php echo h($student['full_name_ur']); ?> (<?php echo h($tr[$student['shuba']]); ?>)</span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      
      <button type="submit" class="btn btnPrimary">
        <i class="bi bi-plus-lg"></i> <?php echo $lang === 'ur' ? 'منتخب طلباء شامل کریں' : 'Add Selected Students'; ?>
      </button>
    </form>
  </div>
</div>
<?php else: ?>
<div class="card">
  <div class="cardBody text-center p-4 text-muted">
    <i class="bi bi-info-circle-fill me-2"></i>
    <?php echo $lang === 'ur' ? 'شامل کرنے کے لیے کوئی طالب علم دستیاب نہیں۔' : 'No students available to add.'; ?>
  </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
