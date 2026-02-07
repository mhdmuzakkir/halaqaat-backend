<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$lang = get_language();
$tr = get_translations($lang);

$editMode = false;
$exam = [
  'id' => '',
  'title' => '',
  'exam_date' => '',
  'max_marks' => 100,
  'passing_marks' => 40,
  'status' => 'upcoming'
];

// Edit mode
if (isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $exam = $result->fetch_assoc();
    $editMode = true;
  }
}

// Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'delete') {
    $id = intval($_POST['exam_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM exams WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: exams_manage.php?msg=deleted");
    exit;
  }
  
  $title = trim($_POST['title'] ?? '');
  $examDate = $_POST['exam_date'] ?? '';
  $maxMarks = floatval($_POST['max_marks'] ?? 100);
  $passingMarks = floatval($_POST['passing_marks'] ?? 40);
  
  if (empty($title)) {
    $error = $lang === 'ur' ? 'امتحان کا عنوان ضروری ہے۔' : 'Exam title is required.';
  } elseif (empty($examDate)) {
    $error = $lang === 'ur' ? 'امتحان کی تاریخ ضروری ہے۔' : 'Exam date is required.';
  } else {
    if ($editMode) {
      $stmt = $conn->prepare("
        UPDATE exams 
        SET title = ?, exam_date = ?, max_marks = ?, passing_marks = ?
        WHERE id = ?
      ");
      $stmt->bind_param("ssdii", $title, $examDate, $maxMarks, $passingMarks, $exam['id']);
      
      if ($stmt->execute()) {
        header("Location: exams_manage.php?msg=updated");
        exit;
      } else {
        $error = $lang === 'ur' ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
      }
    } else {
      $stmt = $conn->prepare("
        INSERT INTO exams (title, exam_date, max_marks, passing_marks, status)
        VALUES (?, ?, ?, ?, 'upcoming')
      ");
      $stmt->bind_param("ssdd", $title, $examDate, $maxMarks, $passingMarks);
      
      if ($stmt->execute()) {
        header("Location: exams_manage.php?msg=created");
        exit;
      } else {
        $error = $lang === 'ur' ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
      }
    }
  }
}

// Fetch all exams for list view
$exams = [];
if (!$editMode && !isset($_GET['id'])) {
  $result = $conn->query("SELECT * FROM exams ORDER BY exam_date DESC");
  while ($row = $result->fetch_assoc()) {
    $exams[] = $row;
  }
}

include __DIR__ . '/includes/header.php';
?>

<?php if (!$editMode && !isset($_GET['id'])): ?>
<!-- List View -->
<div class="sectionHeader">
  <div class="sectionTitle"><?php echo h($tr['nav_exams']); ?></div>
  <a href="exams_manage.php?action=add" class="pill secondary">
    <i class="bi bi-plus-lg"></i>
    <?php echo h($tr['add_new']); ?>
  </a>
</div>

<div class="row g-4">
  <?php foreach ($exams as $e): 
    $statusClass = $e['status'] === 'finalized' ? 'green' : ($e['status'] === 'ongoing' ? 'orange' : 'blue');
    $statusIcon = $e['status'] === 'finalized' ? 'check-circle-fill' : ($e['status'] === 'ongoing' ? 'pencil-square' : 'calendar');
  ?>
  <div class="col-md-6 col-lg-4">
    <div class="card h-100">
      <div class="cardBody">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <h5 class="mb-0"><?php echo h($e['title']); ?></h5>
          <span class="tag <?php echo $statusClass; ?>">
            <i class="bi bi-<?php echo $statusIcon; ?>"></i>
            <?php echo ucfirst($e['status']); ?>
          </span>
        </div>
        
        <p class="mb-1 text-muted">
          <i class="bi bi-calendar-event me-2"></i>
          <?php echo date('M d, Y', strtotime($e['exam_date'])); ?>
        </p>
        <p class="mb-2 text-muted">
          <i class="bi bi-award me-2"></i>
          <?php echo $tr['max_marks']; ?>: <?php echo $e['max_marks']; ?>
        </p>
        
        <div class="d-flex gap-2 mt-3">
          <?php if ($e['status'] !== 'finalized' && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'mumtahin')): ?>
          <a href="exam_marks_entry.php?exam_id=<?php echo $e['id']; ?>" class="btn btnPrimary" style="padding: 6px 12px; font-size: 12px;">
            <i class="bi bi-pencil-square"></i> <?php echo h($tr['marks']); ?>
          </a>
          <?php endif; ?>
          <?php if ($e['status'] === 'ongoing' && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'mumtahin')): ?>
          <a href="exam_finalize.php?exam_id=<?php echo $e['id']; ?>" class="btn btnSecondary" style="padding: 6px 12px; font-size: 12px;">
            <i class="bi bi-lock"></i> <?php echo h($tr['finalize']); ?>
          </a>
          <?php endif; ?>
          <a href="exams_manage.php?id=<?php echo $e['id']; ?>" class="pill" style="padding: 6px 12px; font-size: 12px;">
            <i class="bi bi-pencil"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if (empty($exams)): ?>
<div class="text-center py-5">
  <i class="bi bi-inbox fs-1 text-muted"></i>
  <p class="mt-3 text-muted"><?php echo h($tr['no_data']); ?></p>
</div>
<?php endif; ?>

<?php else: ?>
<!-- Add/Edit Form -->
<div class="card">
  <div class="cardHeader">
    <span><?php echo $editMode ? h($tr['edit']) : h($tr['add_new']); ?> <?php echo h($tr['exam']); ?></span>
    <a href="exams_manage.php" class="pill"><?php echo h($tr['cancel']); ?></a>
  </div>
  <div class="cardBody">
    <?php if (!empty($error)): ?>
    <div class="msg msgError mb2"><?php echo h($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
      <div class="row">
        <div class="col-md-12">
          <div class="formGroup">
            <label class="formLabel"><?php echo $lang === 'ur' ? 'امتحان کا عنوان' : 'Exam Title'; ?> *</label>
            <input type="text" name="title" class="formInput" value="<?php echo h($exam['title']); ?>" required />
          </div>
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-4">
          <div class="formGroup">
            <label class="formLabel"><?php echo $lang === 'ur' ? 'امتحان کی تاریخ' : 'Exam Date'; ?> *</label>
            <input type="date" name="exam_date" class="formInput" value="<?php echo $exam['exam_date']; ?>" required />
          </div>
        </div>
        <div class="col-md-4">
          <div class="formGroup">
            <label class="formLabel"><?php echo h($tr['max_marks']); ?></label>
            <input type="number" name="max_marks" class="formInput" value="<?php echo $exam['max_marks']; ?>" min="1" step="0.5" />
          </div>
        </div>
        <div class="col-md-4">
          <div class="formGroup">
            <label class="formLabel"><?php echo h($tr['passing_marks']); ?></label>
            <input type="number" name="passing_marks" class="formInput" value="<?php echo $exam['passing_marks']; ?>" min="1" step="0.5" />
          </div>
        </div>
      </div>
      
      <div class="mt3">
        <button type="submit" class="btn btnPrimary">
          <i class="bi bi-check-lg"></i>
          <?php echo h($tr['save']); ?>
        </button>
        <a href="exams_manage.php" class="btn" style="background: #e9ecef; color: #333;">
          <?php echo h($tr['cancel']); ?>
        </a>
        <?php if ($editMode): ?>
        <button type="submit" name="action" value="delete" class="btn btnDanger" style="float: right;" onclick="return confirmDelete()">
          <i class="bi bi-trash"></i>
          <?php echo h($tr['delete']); ?>
        </button>
        <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
