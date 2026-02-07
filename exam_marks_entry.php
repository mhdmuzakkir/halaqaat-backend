<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'mumtahin') {
  header("Location: dashboard_admin.php");
  exit;
}

$lang = get_language();
$tr = get_translations($lang);

$success = '';
$error = '';

// Get all exams for dropdown
$exams = [];
$result = $conn->query("SELECT * FROM exams WHERE status != 'finalized' ORDER BY exam_date DESC");
while ($row = $result->fetch_assoc()) {
  $exams[] = $row;
}

$selectedExam = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : ($exams[0]['id'] ?? 0);
$examDetails = null;
$students = [];
$existingResults = [];

if ($selectedExam) {
  // Get exam details
  $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
  $stmt->bind_param("i", $selectedExam);
  $stmt->execute();
  $examDetails = $stmt->get_result()->fetch_assoc();
  
  // Get all active students with their halaqa info
  $stmt = $conn->prepare("
    SELECT s.*, h.name_ur as halaqa_name, h.gender as halaqa_gender
    FROM students s
    JOIN halaqaat h ON s.halaqa_id = h.id
    WHERE s.status = 'active'
    ORDER BY h.gender, h.name_ur, s.full_name_ur
  ");
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $students[] = $row;
  }
  
  // Get existing results for this exam
  $stmt = $conn->prepare("SELECT * FROM exam_results WHERE exam_id = ?");
  $stmt->bind_param("i", $selectedExam);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $existingResults[$row['student_id']] = $row;
  }
}

// Save marks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
  $examId = intval($_POST['exam_id']);
  $marksData = $_POST['marks'] ?? [];
  $remarksData = $_POST['remarks'] ?? [];
  
  $stmt = $conn->prepare("
    INSERT INTO exam_results (exam_id, student_id, marks_obtained, max_marks, percentage, remarks, status)
    VALUES (?, ?, ?, ?, ?, ?, 'draft')
    ON DUPLICATE KEY UPDATE
    marks_obtained = VALUES(marks_obtained),
    percentage = VALUES(percentage),
    remarks = VALUES(remarks)
  ");
  
  foreach ($marksData as $studentId => $marks) {
    if ($marks !== '') {
      $marksObtained = floatval($marks);
      $maxMarks = $examDetails['max_marks'];
      $percentage = ($marksObtained / $maxMarks) * 100;
      $remarks = $remarksData[$studentId] ?? '';
      
      $stmt->bind_param("iiddss", $examId, $studentId, $marksObtained, $maxMarks, $percentage, $remarks);
      $stmt->execute();
    }
  }
  
  // Update exam status to ongoing
  $conn->query("UPDATE exams SET status = 'ongoing' WHERE id = $examId AND status = 'upcoming'");
  
  $success = $lang === 'ur' ? 'نمبر کامیابی سے محفوظ ہو گئے۔' : 'Marks saved successfully.';
  
  // Refresh existing results
  $stmt = $conn->prepare("SELECT * FROM exam_results WHERE exam_id = ?");
  $stmt->bind_param("i", $selectedExam);
  $stmt->execute();
  $result = $stmt->get_result();
  $existingResults = [];
  while ($row = $result->fetch_assoc()) {
    $existingResults[$row['student_id']] = $row;
  }
}

include __DIR__ . '/includes/header.php';
?>

<!-- Exam Selector -->
<div class="card mb-4">
  <div class="cardBody">
    <form method="GET" action="" class="row g-3 align-items-end">
      <div class="col-md-8">
        <label class="formLabel"><?php echo $lang === 'ur' ? 'امتحان منتخب کریں' : 'Select Exam'; ?></label>
        <select class="formSelect" name="exam_id" onchange="this.form.submit()">
          <?php foreach ($exams as $exam): ?>
          <option value="<?php echo $exam['id']; ?>" <?php echo $selectedExam == $exam['id'] ? 'selected' : ''; ?>>
            <?php echo h($exam['title']); ?> (<?php echo date('M d, Y', strtotime($exam['exam_date'])); ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <a href="exams_manage.php?action=add" class="btn btnPrimary w-100">
          <i class="bi bi-plus-lg"></i> <?php echo $lang === 'ur' ? 'نیا امتحان' : 'New Exam'; ?>
        </a>
      </div>
    </form>
  </div>
</div>

<?php if ($success): ?>
<div class="msg mb-3"><?php echo h($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="msg msgError mb-3"><?php echo h($error); ?></div>
<?php endif; ?>

<?php if ($selectedExam && $examDetails): ?>
<!-- Exam Info -->
<div class="card mb-4" style="background: var(--primary); color: #fff;">
  <div class="cardBody">
    <div class="row">
      <div class="col-md-4">
        <strong><i class="bi bi-file-text me-2"></i><?php echo h($examDetails['title']); ?></strong>
      </div>
      <div class="col-md-4">
        <i class="bi bi-calendar-event me-2"></i><?php echo date('M d, Y', strtotime($examDetails['exam_date'])); ?>
      </div>
      <div class="col-md-4">
        <i class="bi bi-award me-2"></i><?php echo $lang === 'ur' ? 'کل نمبر:' : 'Max Marks:'; ?> <?php echo $examDetails['max_marks']; ?>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($students)): ?>
<!-- Marks Entry Form -->
<form method="POST" action="">
  <input type="hidden" name="exam_id" value="<?php echo $selectedExam; ?>">
  
  <div class="card mb-4">
    <div class="cardHeader d-flex justify-content-between align-items-center">
      <span><i class="bi bi-pencil-square me-2"></i><?php echo h($tr['marks']); ?></span>
      <div>
        <button type="submit" name="save_marks" class="btn btnPrimary">
          <i class="bi bi-check-lg"></i> <?php echo h($tr['save']); ?>
        </button>
        <a href="exam_finalize.php?exam_id=<?php echo $selectedExam; ?>" class="btn btnSecondary ms-2">
          <i class="bi bi-lock"></i> <?php echo h($tr['finalize']); ?>
        </a>
      </div>
    </div>
    <div class="cardBody p-0">
      <div class="tableContainer">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th><?php echo h($tr['name']); ?></th>
              <th><?php echo h($tr['halaqa']); ?></th>
              <th><?php echo h($tr['shuba']); ?></th>
              <th><?php echo $lang === 'ur' ? 'نمبر (' . $examDetails['max_marks'] . ')' : 'Marks (' . $examDetails['max_marks'] . ')'; ?></th>
              <th><?php echo $lang === 'ur' ? 'فیصد' : '%'; ?></th>
              <th><?php echo h($tr['remarks']); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $currentGender = '';
            foreach ($students as $index => $student): 
              if ($currentGender !== $student['halaqa_gender']):
                $currentGender = $student['halaqa_gender'];
            ?>
            <tr style="background: rgba(201, 167, 124, 0.1);">
              <td colspan="7" class="fw-bold text-center">
                <i class="bi bi-<?php echo $currentGender === 'baneen' ? 'gender-male' : 'gender-female'; ?>"></i>
                <?php echo h($tr[$currentGender]); ?>
              </td>
            </tr>
            <?php endif; 
              $existing = $existingResults[$student['id']] ?? null;
              $marks = $existing['marks_obtained'] ?? '';
              $percentage = $existing['percentage'] ?? 0;
              $remarks = $existing['remarks'] ?? '';
            ?>
            <tr>
              <td><?php echo $index + 1; ?></td>
              <td>
                <?php echo h($student['full_name_ur']); ?>
                <?php if ($student['mumayyaz']): ?>
                  <span class="tag green ms-1"><i class="bi bi-star-fill"></i></span>
                <?php endif; ?>
              </td>
              <td><?php echo h($student['halaqa_name']); ?></td>
              <td><?php echo h($tr[$student['shuba']]); ?></td>
              <td>
                <input type="number" 
                       class="formInput marks-input" 
                       name="marks[<?php echo $student['id']; ?>]" 
                       value="<?php echo $marks; ?>"
                       min="0" 
                       max="<?php echo $examDetails['max_marks']; ?>"
                       step="0.5"
                       data-max="<?php echo $examDetails['max_marks']; ?>"
                       data-student="<?php echo $student['id']; ?>"
                       style="width: 100px;" />
              </td>
              <td>
                <span class="tag <?php echo $percentage >= 60 ? 'green' : ($percentage >= 40 ? 'orange' : 'pink'); ?> percentage-badge" 
                      id="percentage_<?php echo $student['id']; ?>">
                  <?php echo $percentage > 0 ? round($percentage, 1) . '%' : '-'; ?>
                </span>
              </td>
              <td>
                <input type="text" 
                       class="formInput" 
                       name="remarks[<?php echo $student['id']; ?>]" 
                       value="<?php echo h($remarks); ?>"
                       placeholder="<?php echo $lang === 'ur' ? 'تبصرہ' : 'Remark'; ?>" />
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <div class="d-flex justify-content-between">
    <button type="submit" name="save_marks" class="btn btnPrimary btn-lg">
      <i class="bi bi-check-lg"></i> <?php echo h($tr['save']); ?>
    </button>
    <a href="exam_finalize.php?exam_id=<?php echo $selectedExam; ?>" class="btn btnSecondary btn-lg">
      <i class="bi bi-lock"></i> <?php echo h($tr['finalize']); ?> <?php echo $lang === 'ur' ? 'امتحان' : 'Exam'; ?>
    </a>
  </div>
</form>

<script>
document.querySelectorAll('.marks-input').forEach(input => {
  input.addEventListener('input', function() {
    const maxMarks = parseFloat(this.dataset.max);
    const marks = parseFloat(this.value) || 0;
    const studentId = this.dataset.student;
    const percentage = (marks / maxMarks) * 100;
    
    const badge = document.getElementById('percentage_' + studentId);
    if (marks > 0) {
      badge.textContent = percentage.toFixed(1) + '%';
      badge.className = 'tag percentage-badge ' + (percentage >= 60 ? 'green' : (percentage >= 40 ? 'orange' : 'pink'));
    } else {
      badge.textContent = '-';
      badge.className = 'tag percentage-badge';
    }
  });
});
</script>

<?php else: ?>
<div class="alert alert-warning">
  <i class="bi bi-exclamation-triangle-fill me-2"></i>
  <?php echo $lang === 'ur' ? 'کوئی فعال طالب علم نہیں۔' : 'No active students found.'; ?>
</div>
<?php endif; ?>

<?php else: ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle-fill me-2"></i>
  <?php echo $lang === 'ur' ? 'براہ کرم امتحان منتخب کریں یا نیا امتحان بنائیں۔' : 'Please select an exam or create a new one.'; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
