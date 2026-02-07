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
  
  // Get exam type from title
  $examTitle = strtolower($examDetails['title'] ?? '');
  $examType = 'qaida';
  if (strpos($examTitle, 'حفظ') !== false || strpos($examTitle, 'hifz') !== false) {
    $examType = 'hifz';
  } elseif (strpos($examTitle, 'ناظرہ') !== false || strpos($examTitle, 'nazira') !== false) {
    $examType = 'nazira';
  }
  
  // Get all active students with their halaqa info - grouped by halaqa, ordered by shuba (hifz first)
  $stmt = $conn->prepare("
    SELECT s.*, h.name_ur as halaqa_name, h.gender as halaqa_gender
    FROM students s
    JOIN halaqaat h ON s.halaqa_id = h.id
    WHERE s.status = 'active'
    ORDER BY h.gender, h.name_ur, 
             FIELD(s.shuba, 'hifz', 'nazira', 'qaida'),
             s.surah_current DESC, s.full_name_ur
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

// Calculate remarks based on percentage
function getRemarks($percentage, $lang) {
  if ($percentage >= 85) return $lang === 'ur' ? 'ممتاز' : 'Mumtaaz';
  if ($percentage >= 70) return $lang === 'ur' ? 'جيد جدًا' : 'Jayed Jiddan';
  if ($percentage >= 55) return $lang === 'ur' ? 'جيد' : 'Jayed';
  if ($percentage >= 40) return $lang === 'ur' ? 'مقبول' : 'Maqbool';
  return $lang === 'ur' ? 'ضعيف' : 'Zaeef';
}

// Save marks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
  $examId = intval($_POST['exam_id']);
  $marksData = $_POST['marks'] ?? [];
  $mumayyazData = $_POST['mumayyaz'] ?? [];
  
  $examTitle = strtolower($examDetails['title'] ?? '');
  $examType = 'qaida';
  if (strpos($examTitle, 'حفظ') !== false || strpos($examTitle, 'hifz') !== false) {
    $examType = 'hifz';
  } elseif (strpos($examTitle, 'ناظرہ') !== false || strpos($examTitle, 'nazira') !== false) {
    $examType = 'nazira';
  }
  
  $stmt = $conn->prepare("
    INSERT INTO exam_results (exam_id, student_id, marks_obtained, max_marks, percentage, remarks, status, mumayyaz)
    VALUES (?, ?, ?, ?, ?, ?, 'draft', ?)
    ON DUPLICATE KEY UPDATE
    marks_obtained = VALUES(marks_obtained),
    percentage = VALUES(percentage),
    remarks = VALUES(remarks),
    mumayyaz = VALUES(mumayyaz)
  ");
  
  foreach ($marksData as $studentId => $marks) {
    if ($marks !== '') {
      $marksObtained = floatval($marks);
      $maxMarks = $examDetails['max_marks'];
      $percentage = ($marksObtained / $maxMarks) * 100;
      $remarks = getRemarks($percentage, $lang);
      $isMumayyaz = isset($mumayyazData[$studentId]) ? 1 : 0;
      
      $stmt->bind_param("iiddssi", $examId, $studentId, $marksObtained, $maxMarks, $percentage, $remarks, $isMumayyaz);
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

<?php if ($selectedExam && $examDetails): 
  // Determine exam type
  $examTitle = strtolower($examDetails['title'] ?? '');
  $examType = 'qaida';
  if (strpos($examTitle, 'حفظ') !== false || strpos($examTitle, 'hifz') !== false) {
    $examType = 'hifz';
  } elseif (strpos($examTitle, 'ناظرہ') !== false || strpos($examTitle, 'nazira') !== false) {
    $examType = 'nazira';
  }
?>
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
    <div class="row mt-2">
      <div class="col-12">
        <small>
          <?php if ($examType === 'qaida'): ?>
            <?php echo $lang === 'ur' ? 'تقسیم: قائدہ (70) + اضافات (10) + تجوید (10) + سلوک (10) = 100' : 'Distribution: Qaida (70) + Izaafat (10) + Tajweed (10) + Sulook (10) = 100'; ?>
          <?php elseif ($examType === 'hifz'): ?>
            <?php echo $lang === 'ur' ? 'تقسیم: حفظ (60) + حسن صوت (10) + تجوید (10) + اضافات (10) + سلوک (10) = 100' : 'Distribution: Hifz (60) + Husn-e-Sawt (10) + Tajweed (10) + Izaafat (10) + Sulook (10) = 100'; ?>
          <?php else: ?>
            <?php echo $lang === 'ur' ? 'تقسیم: ناظرہ (60) + حسن صوت (10) + تجوید (10) + اضافات (10) + سلوک (10) = 100' : 'Distribution: Nazira (60) + Husn-e-Sawt (10) + Tajweed (10) + Izaafat (10) + Sulook (10) = 100'; ?>
          <?php endif; ?>
        </small>
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
              <th><?php echo h($tr['mumayyaz']); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $currentHalaqa = '';
            foreach ($students as $index => $student): 
              if ($currentHalaqa !== $student['halaqa_name']):
                $currentHalaqa = $student['halaqa_name'];
            ?>
            <tr style="background: rgba(170, 129, 94, 0.15);">
              <td colspan="8" class="fw-bold" style="padding: 8px 16px;">
                <i class="bi bi-building me-2"></i><?php echo h($currentHalaqa); ?> (<?php echo h($tr[$student['halaqa_gender']]); ?>)
              </td>
            </tr>
            <?php endif; 
              $existing = $existingResults[$student['id']] ?? null;
              $marks = $existing['marks_obtained'] ?? '';
              $percentage = $existing['percentage'] ?? 0;
              $remarks = $existing['remarks'] ?? '';
              $isMumayyaz = $existing['mumayyaz'] ?? 0;
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
                <span class="tag <?php echo $percentage >= 85 ? 'green' : ($percentage >= 70 ? 'blue' : ($percentage >= 55 ? 'orange' : ($percentage >= 40 ? 'tan' : 'pink'))); ?> percentage-badge" 
                      id="percentage_<?php echo $student['id']; ?>">
                  <?php echo $percentage > 0 ? round($percentage, 1) . '%' : '-'; ?>
                </span>
              </td>
              <td>
                <span id="remarks_<?php echo $student['id']; ?>" class="fw-bold">
                  <?php echo $remarks ? h($remarks) : '-'; ?>
                </span>
              </td>
              <td>
                <label class="formCheck" style="cursor: pointer; display: flex; align-items: center; justify-content: center;">
                  <input type="checkbox" 
                         name="mumayyaz[<?php echo $student['id']; ?>]" 
                         value="1" 
                         <?php echo $isMumayyaz ? 'checked' : ''; ?>
                         style="width: 20px; height: 20px;" />
                </label>
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
    
    // Update percentage badge
    const badge = document.getElementById('percentage_' + studentId);
    if (marks > 0) {
      badge.textContent = percentage.toFixed(1) + '%';
      badge.className = 'tag percentage-badge ' + 
        (percentage >= 85 ? 'green' : 
         percentage >= 70 ? 'blue' : 
         percentage >= 55 ? 'orange' : 
         percentage >= 40 ? 'tan' : 'pink');
    } else {
      badge.textContent = '-';
      badge.className = 'tag percentage-badge';
    }
    
    // Update remarks
    const remarksEl = document.getElementById('remarks_' + studentId);
    let remarks = '-';
    if (marks > 0) {
      if (percentage >= 85) remarks = '<?php echo $lang === 'ur' ? 'ممتاز' : 'Mumtaaz'; ?>';
      else if (percentage >= 70) remarks = '<?php echo $lang === 'ur' ? 'جيد جدًا' : 'Jayed Jiddan'; ?>';
      else if (percentage >= 55) remarks = '<?php echo $lang === 'ur' ? 'جيد' : 'Jayed'; ?>';
      else if (percentage >= 40) remarks = '<?php echo $lang === 'ur' ? 'مقبول' : 'Maqbool'; ?>';
      else remarks = '<?php echo $lang === 'ur' ? 'ضعيف' : 'Zaeef'; ?>';
    }
    remarksEl.textContent = remarks;
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
