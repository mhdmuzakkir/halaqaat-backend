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

// Calculate taqdeer based on percentage
function getTaqdeer($percentage, $lang) {
  if ($percentage >= 85) return $lang === 'ur' ? 'ممتاز' : 'Mumtaaz';
  if ($percentage >= 70) return $lang === 'ur' ? 'جيد جدًا' : 'Jayed Jiddan';
  if ($percentage >= 55) return $lang === 'ur' ? 'جيد' : 'Jayed';
  if ($percentage >= 40) return $lang === 'ur' ? 'مقبول' : 'Maqbool';
  return $lang === 'ur' ? 'ضعيف' : 'Zaeef';
}

// Save marks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
  $examId = intval($_POST['exam_id']);
  
  $examTitle = strtolower($examDetails['title'] ?? '');
  $examType = 'qaida';
  if (strpos($examTitle, 'حفظ') !== false || strpos($examTitle, 'hifz') !== false) {
    $examType = 'hifz';
  } elseif (strpos($examTitle, 'ناظرہ') !== false || strpos($examTitle, 'nazira') !== false) {
    $examType = 'nazira';
  }
  
  // Max marks based on exam type
  $maxNisaab = ($examType === 'qaida') ? 70 : 60;
  $maxOthers = 10;
  $maxTotal = 100;
  
  $stmt = $conn->prepare("
    INSERT INTO exam_results 
    (exam_id, student_id, nisaab_from, nisaab_to, nisaab_marks, husn_sawt, tajweed, izaafat, sulook, 
     marks_obtained, max_marks, percentage, taqdeer, status, mumayyaz)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)
    ON DUPLICATE KEY UPDATE
    nisaab_from = VALUES(nisaab_from),
    nisaab_to = VALUES(nisaab_to),
    nisaab_marks = VALUES(nisaab_marks),
    husn_sawt = VALUES(husn_sawt),
    tajweed = VALUES(tajweed),
    izaafat = VALUES(izaafat),
    sulook = VALUES(sulook),
    marks_obtained = VALUES(marks_obtained),
    percentage = VALUES(percentage),
    taqdeer = VALUES(taqdeer),
    mumayyaz = VALUES(mumayyaz)
  ");
  
  foreach ($_POST['students'] as $studentId => $data) {
    $nisaabFrom = $data['nisaab_from'] ?? '';
    $nisaabTo = $data['nisaab_to'] ?? '';
    $nisaabMarks = floatval($data['nisaab_marks'] ?? 0);
    $husnSawt = floatval($data['husn_sawt'] ?? 0);
    $tajweed = floatval($data['tajweed'] ?? 0);
    $izaafat = floatval($data['izaafat'] ?? 0);
    $sulook = floatval($data['sulook'] ?? 0);
    $isMumayyaz = isset($data['mumayyaz']) ? 1 : 0;
    
    $totalMarks = $nisaabMarks + $husnSawt + $tajweed + $izaafat + $sulook;
    $percentage = ($totalMarks / $maxTotal) * 100;
    $taqdeer = getTaqdeer($percentage, $lang);
    
    $stmt->bind_param("iissdddddddsdi", 
      $examId, $studentId, $nisaabFrom, $nisaabTo, $nisaabMarks, $husnSawt, $tajweed, $izaafat, $sulook,
      $totalMarks, $maxTotal, $percentage, $taqdeer, $isMumayyaz
    );
    $stmt->execute();
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
  
  // Max marks based on exam type
  $maxNisaab = ($examType === 'qaida') ? 70 : 60;
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
        <i class="bi bi-award me-2"></i><?php echo $lang === 'ur' ? 'کل نمبر:' : 'Max Marks:'; ?> 100
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
      <div class="tableContainer" style="overflow-x: auto;">
        <table class="table mb-0" style="min-width: 1200px;">
          <thead>
            <tr style="background: var(--primary); color: #fff;">
              <th rowspan="2">#</th>
              <th rowspan="2"><?php echo h($tr['name']); ?></th>
              <th rowspan="2"><?php echo h($tr['shuba']); ?></th>
              <th colspan="2" class="text-center"><?php echo h($tr['nisaab']); ?></th>
              <th rowspan="2" class="text-center"><?php echo h($tr['nisaab']); ?><br><small>(<?php echo $maxNisaab; ?>)</small></th>
              <?php if ($examType !== 'qaida'): ?>
              <th rowspan="2" class="text-center"><?php echo h($tr['husn_sawt']); ?><br><small>(10)</small></th>
              <?php endif; ?>
              <th rowspan="2" class="text-center"><?php echo h($tr['tajweed']); ?><br><small>(10)</small></th>
              <th rowspan="2" class="text-center"><?php echo h($tr['izaafat']); ?><br><small>(10)</small></th>
              <th rowspan="2" class="text-center"><?php echo h($tr['sulook']); ?><br><small>(10)</small></th>
              <th rowspan="2" class="text-center"><?php echo h($tr['total']); ?><br><small>(100)</small></th>
              <th rowspan="2" class="text-center">%</th>
              <th rowspan="2" class="text-center"><?php echo h($tr['taqdeer']); ?></th>
              <th rowspan="2" class="text-center"><?php echo h($tr['mumayyaz']); ?></th>
            </tr>
            <tr style="background: var(--primary-light); color: #fff;">
              <th class="text-center"><?php echo h($tr['from']); ?></th>
              <th class="text-center"><?php echo h($tr['to']); ?></th>
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
              <td colspan="15" class="fw-bold" style="padding: 8px 16px;">
                <i class="bi bi-building me-2"></i><?php echo h($currentHalaqa); ?> (<?php echo h($tr[$student['halaqa_gender']]); ?>)
              </td>
            </tr>
            <?php endif; 
              $existing = $existingResults[$student['id']] ?? null;
              $nisaabFrom = $existing['nisaab_from'] ?? '';
              $nisaabTo = $existing['nisaab_to'] ?? '';
              $nisaabMarks = $existing['nisaab_marks'] ?? '';
              $husnSawt = $existing['husn_sawt'] ?? '';
              $tajweed = $existing['tajweed'] ?? '';
              $izaafat = $existing['izaafat'] ?? '';
              $sulook = $existing['sulook'] ?? '';
              $totalMarks = $existing['marks_obtained'] ?? 0;
              $percentage = $existing['percentage'] ?? 0;
              $taqdeer = $existing['taqdeer'] ?? '';
              $isMumayyaz = $existing['mumayyaz'] ?? 0;
              
              // Nisaab label based on shuba
              $nisaabLabel = $tr['takhti'];
              if ($student['shuba'] === 'hifz' || $student['shuba'] === 'nazira') {
                $nisaabLabel = $tr['surah'];
              }
            ?>
            <tr class="student-row" data-student="<?php echo $student['id']; ?>" data-max-nisaab="<?php echo $maxNisaab; ?>" data-type="<?php echo $examType; ?>">
              <td><?php echo $index + 1; ?></td>
              <td>
                <?php echo h($student['full_name_ur']); ?>
                <?php if ($student['mumayyaz']): ?>
                  <span class="tag" style="background: rgba(15,45,61,0.15); color: #0f2d3d;"><i class="bi bi-star-fill"></i></span>
                <?php endif; ?>
              </td>
              <td><?php echo h($tr[$student['shuba']]); ?></td>
              <td>
                <input type="text" 
                       class="formInput nisaab-from" 
                       name="students[<?php echo $student['id']; ?>][nisaab_from]" 
                       value="<?php echo h($nisaabFrom); ?>"
                       placeholder="<?php echo h($nisaabLabel); ?>"
                       style="width: 80px; font-size: 12px;" />
              </td>
              <td>
                <input type="text" 
                       class="formInput nisaab-to" 
                       name="students[<?php echo $student['id']; ?>][nisaab_to]" 
                       value="<?php echo h($nisaabTo); ?>"
                       placeholder="<?php echo h($nisaabLabel); ?>"
                       style="width: 80px; font-size: 12px;" />
              </td>
              <td>
                <input type="number" 
                       class="formInput marks-input nisaab-marks" 
                       name="students[<?php echo $student['id']; ?>][nisaab_marks]" 
                       value="<?php echo $nisaabMarks; ?>"
                       min="0" 
                       max="<?php echo $maxNisaab; ?>"
                       step="0.5"
                       style="width: 70px;" />
              </td>
              <?php if ($examType !== 'qaida'): ?>
              <td>
                <input type="number" 
                       class="formInput marks-input husn-sawt" 
                       name="students[<?php echo $student['id']; ?>][husn_sawt]" 
                       value="<?php echo $husnSawt; ?>"
                       min="0" 
                       max="10"
                       step="0.5"
                       style="width: 60px;" />
              </td>
              <?php endif; ?>
              <td>
                <input type="number" 
                       class="formInput marks-input tajweed" 
                       name="students[<?php echo $student['id']; ?>][tajweed]" 
                       value="<?php echo $tajweed; ?>"
                       min="0" 
                       max="10"
                       step="0.5"
                       style="width: 60px;" />
              </td>
              <td>
                <input type="number" 
                       class="formInput marks-input izaafat" 
                       name="students[<?php echo $student['id']; ?>][izaafat]" 
                       value="<?php echo $izaafat; ?>"
                       min="0" 
                       max="10"
                       step="0.5"
                       style="width: 60px;" />
              </td>
              <td>
                <input type="number" 
                       class="formInput marks-input sulook" 
                       name="students[<?php echo $student['id']; ?>][sulook]" 
                       value="<?php echo $sulook; ?>"
                       min="0" 
                       max="10"
                       step="0.5"
                       style="width: 60px;" />
              </td>
              <td class="text-center">
                <span class="fw-bold total-marks"><?php echo $totalMarks > 0 ? $totalMarks : '-'; ?></span>
              </td>
              <td class="text-center">
                <span class="tag percentage-badge" style="background: rgba(15,45,61,0.1); color: #0f2d3d;">
                  <?php echo $percentage > 0 ? round($percentage, 1) . '%' : '-'; ?>
                </span>
              </td>
              <td class="text-center">
                <span class="fw-bold taqdeer"><?php echo $taqdeer ? h($taqdeer) : '-'; ?></span>
              </td>
              <td class="text-center">
                <label style="cursor: pointer;">
                  <input type="checkbox" 
                         name="students[<?php echo $student['id']; ?>][mumayyaz]" 
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
document.querySelectorAll('.student-row').forEach(row => {
  const inputs = row.querySelectorAll('.marks-input');
  const maxNisaab = parseFloat(row.dataset.maxNisaab);
  const examType = row.dataset.type;
  
  inputs.forEach(input => {
    input.addEventListener('input', function() {
      // Validate max values
      if (this.classList.contains('nisaab-marks') && parseFloat(this.value) > maxNisaab) {
        this.value = maxNisaab;
      }
      if (!this.classList.contains('nisaab-marks') && parseFloat(this.value) > 10) {
        this.value = 10;
      }
      
      // Calculate total
      const nisaab = parseFloat(row.querySelector('.nisaab-marks').value) || 0;
      const husnSawt = examType !== 'qaida' ? (parseFloat(row.querySelector('.husn-sawt').value) || 0) : 0;
      const tajweed = parseFloat(row.querySelector('.tajweed').value) || 0;
      const izaafat = parseFloat(row.querySelector('.izaafat').value) || 0;
      const sulook = parseFloat(row.querySelector('.sulook').value) || 0;
      
      const total = nisaab + husnSawt + tajweed + izaafat + sulook;
      const percentage = (total / 100) * 100;
      
      // Update total
      row.querySelector('.total-marks').textContent = total > 0 ? total.toFixed(1) : '-';
      
      // Update percentage
      const badge = row.querySelector('.percentage-badge');
      if (total > 0) {
        badge.textContent = percentage.toFixed(1) + '%';
      } else {
        badge.textContent = '-';
      }
      
      // Update taqdeer
      let taqdeer = '-';
      if (total > 0) {
        if (percentage >= 85) taqdeer = '<?php echo $lang === 'ur' ? 'ممتاز' : 'Mumtaaz'; ?>';
        else if (percentage >= 70) taqdeer = '<?php echo $lang === 'ur' ? 'جيد جدًا' : 'Jayed Jiddan'; ?>';
        else if (percentage >= 55) taqdeer = '<?php echo $lang === 'ur' ? 'جيد' : 'Jayed'; ?>';
        else if (percentage >= 40) taqdeer = '<?php echo $lang === 'ur' ? 'مقبول' : 'Maqbool'; ?>';
        else taqdeer = '<?php echo $lang === 'ur' ? 'ضعيف' : 'Zaeef'; ?>';
      }
      row.querySelector('.taqdeer').textContent = taqdeer;
    });
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
