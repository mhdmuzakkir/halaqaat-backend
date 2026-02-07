<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$lang = get_language();
$tr = get_translations($lang);

// Get halaqaat for dropdown
$halaqaat = [];
$result = $conn->query("SELECT id, name_ur, gender FROM halaqaat WHERE state = 'active' ORDER BY name_ur");
while ($row = $result->fetch_assoc()) {
  $halaqaat[] = $row;
}

$editMode = false;
$student = [
  'id' => '',
  'halaqa_id' => '',
  'full_name_ur' => '',
  'gender' => 'baneen',
  'shuba' => 'qaida',
  'mumayyaz' => 0,
  'qaida_takhti' => '',
  'surah_current' => ''
];

// Edit mode
if (isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $student = $result->fetch_assoc();
    $editMode = true;
  }
}

// Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'delete') {
    $id = intval($_POST['student_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE students SET status = 'inactive' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: students_manage.php?msg=deleted");
    exit;
  }
  
  $halaqaId = $_POST['halaqa_id'] ?: null;
  $fullNameUr = trim($_POST['full_name_ur'] ?? '');
  $gender = $_POST['gender'] ?? 'baneen';
  $shuba = $_POST['shuba'] ?? 'qaida';
  $mumayyaz = isset($_POST['mumayyaz']) ? 1 : 0;
  $qaidaTakhti = $_POST['qaida_takhti'] ?: null;
  $surahCurrent = $_POST['surah_current'] ?: null;
  
  if (empty($fullNameUr)) {
    $error = $lang === 'ur' ? 'نام ضروری ہے۔' : 'Name is required.';
  } else {
    if ($editMode) {
      $stmt = $conn->prepare("
        UPDATE students 
        SET halaqa_id = ?, full_name_ur = ?, gender = ?, shuba = ?, mumayyaz = ?, qaida_takhti = ?, surah_current = ?
        WHERE id = ?
      ");
      $stmt->bind_param("isssiiii", $halaqaId, $fullNameUr, $gender, $shuba, $mumayyaz, $qaidaTakhti, $surahCurrent, $student['id']);
      
      if ($stmt->execute()) {
        header("Location: students_manage.php?msg=updated");
        exit;
      } else {
        $error = $lang === 'ur' ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
      }
    } else {
      $stmt = $conn->prepare("
        INSERT INTO students (halaqa_id, full_name_ur, gender, shuba, mumayyaz, qaida_takhti, surah_current, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
      ");
      $stmt->bind_param("isssiis", $halaqaId, $fullNameUr, $gender, $shuba, $mumayyaz, $qaidaTakhti, $surahCurrent);
      
      if ($stmt->execute()) {
        header("Location: students_manage.php?msg=created");
        exit;
      } else {
        $error = $lang === 'ur' ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
      }
    }
  }
}

// Fetch all students for list view
$students = [];
if (!$editMode && !isset($_GET['id']) && !isset($_GET['action'])) {
  $query = "
    SELECT s.*, h.name_ur as halaqa_name
    FROM students s
    LEFT JOIN halaqaat h ON s.halaqa_id = h.id
    WHERE s.status = 'active'
    ORDER BY s.full_name_ur
  ";
  $result = $conn->query($query);
  while ($row = $result->fetch_assoc()) {
    $students[] = $row;
  }
}

include __DIR__ . '/includes/header.php';
?>

<?php if (!$editMode && !isset($_GET['id']) && !isset($_GET['action'])): ?>
<!-- List View -->
<div class="sectionHeader">
  <div class="sectionTitle"><?php echo h($tr['nav_students']); ?></div>
  <a href="students_manage.php?action=add" class="pill secondary">
    <i class="bi bi-plus-lg"></i>
    <?php echo h($tr['add_new']); ?>
  </a>
</div>

<div class="searchWrap mb-3" style="max-width: 400px;">
  <i class="bi bi-search"></i>
  <input type="text" id="studentSearch" placeholder="<?php echo h($tr['search']); ?>" />
</div>

<div class="card">
  <div class="cardBody p-0">
    <div class="tableContainer">
      <table class="table mb-0" id="studentsTable">
        <thead>
          <tr>
            <th><?php echo h($tr['name']); ?></th>
            <th><?php echo h($tr['shuba']); ?></th>
            <th><?php echo h($tr['halaqa']); ?></th>
            <th><?php echo h($tr['gender']); ?></th>
            <th><?php echo h($tr['mumayyaz']); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $s): ?>
          <tr class="student-item">
            <td>
              <strong><?php echo h($s['full_name_ur']); ?></strong>
            </td>
            <td><?php echo h($tr[$s['shuba']]); ?></td>
            <td><?php echo h($s['halaqa_name'] ?: '-'); ?></td>
            <td>
              <span class="tag <?php echo $s['gender'] === 'baneen' ? 'blue' : 'pink'; ?>">
                <?php echo h($tr[$s['gender']]); ?>
              </span>
            </td>
            <td>
              <?php if ($s['mumayyaz']): ?>
                <span class="tag green"><i class="bi bi-star-fill"></i></span>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="students_manage.php?id=<?php echo $s['id']; ?>" class="pill" style="padding: 4px 10px;">
                <i class="bi bi-pencil"></i>
              </a>
              <a href="report_student.php?id=<?php echo $s['id']; ?>" class="pill" style="padding: 4px 10px;">
                <i class="bi bi-eye"></i>
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
setupSearch('studentSearch', '#studentsTable', '.student-item', ['td']);
</script>

<?php else: ?>
<!-- Add/Edit Form -->
<div class="card">
  <div class="cardHeader">
    <span><?php echo $editMode ? h($tr['edit']) : h($tr['add_new']); ?> <?php echo h($tr['students']); ?></span>
    <a href="students_manage.php" class="pill"><?php echo h($tr['cancel']); ?></a>
  </div>
  <div class="cardBody">
    <?php if (!empty($error)): ?>
    <div class="msg msgError mb-3"><?php echo h($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
      <div class="row">
        <div class="col-md-12">
          <div class="formGroup">
            <label class="formLabel"><?php echo h($tr['name']); ?> *</label>
            <input type="text" name="full_name_ur" class="formInput" value="<?php echo h($student['full_name_ur']); ?>" required />
          </div>
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-6">
          <div class="formGroup">
            <label class="formLabel"><?php echo h($tr['halaqa']); ?></label>
            <select name="halaqa_id" class="formSelect">
              <option value=""><?php echo $lang === 'ur' ? '-- حلقہ منتخب کریں --' : '-- Select Halaqa --'; ?></option>
              <?php foreach ($halaqaat as $h): ?>
              <option value="<?php echo $h['id']; ?>" <?php echo $student['halaqa_id'] == $h['id'] ? 'selected' : ''; ?>>
                <?php echo h($h['name_ur']); ?> (<?php echo h($tr[$h['gender']]); ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="formGroup">
            <label class="formLabel"><?php echo h($tr['shuba']); ?> *</label>
            <select name="shuba" class="formSelect" required>
              <option value="qaida" <?php echo $student['shuba'] === 'qaida' ? 'selected' : ''; ?>><?php echo h($tr['qaida']); ?></option>
              <option value="nazira" <?php echo $student['shuba'] === 'nazira' ? 'selected' : ''; ?>><?php echo h($tr['nazira']); ?></option>
              <option value="hifz" <?php echo $student['shuba'] === 'hifz' ? 'selected' : ''; ?>><?php echo h($tr['hifz']); ?></option>
            </select>
          </div>
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-4">
          <div class="formGroup">
            <label class="formLabel"><?php echo h($tr['gender']); ?> *</label>
            <select name="gender" class="formSelect" required>
              <option value="baneen" <?php echo $student['gender'] === 'baneen' ? 'selected' : ''; ?>><?php echo h($tr['baneen']); ?></option>
              <option value="banaat" <?php echo $student['gender'] === 'banaat' ? 'selected' : ''; ?>><?php echo h($tr['banaat']); ?></option>
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="formGroup">
            <label class="formLabel"><?php echo h($tr['takhti']); ?></label>
            <input type="number" name="qaida_takhti" class="formInput" value="<?php echo $student['qaida_takhti']; ?>" min="1" max="30" />
          </div>
        </div>
        <div class="col-md-4">
          <div class="formGroup">
            <label class="formLabel"><?php echo h($tr['surah']); ?></label>
            <input type="number" name="surah_current" class="formInput" value="<?php echo $student['surah_current']; ?>" min="1" max="114" />
          </div>
        </div>
      </div>
      
      <div class="formGroup">
        <label class="formCheck" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
          <input type="checkbox" name="mumayyaz" value="1" <?php echo $student['mumayyaz'] ? 'checked' : ''; ?> style="width: 18px; height: 18px;" />
          <span><?php echo h($tr['mumayyaz']); ?></span>
        </label>
      </div>
      
      <div class="mt-3">
        <button type="submit" class="btn btnPrimary">
          <i class="bi bi-check-lg"></i>
          <?php echo h($tr['save']); ?>
        </button>
        <a href="students_manage.php" class="btn" style="background: #e9ecef; color: #333;">
          <?php echo h($tr['cancel']); ?>
        </a>
        <?php if ($editMode): ?>
        <button type="submit" name="action" value="delete" class="btn btnDanger" style="float: right;" onclick="return confirmDelete()">
          <i class="bi bi-trash"></i>
          <?php echo h($tr['delete']); ?>
        </button>
        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
