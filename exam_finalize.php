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

// Get ongoing exams (exams with draft results)
$exams = [];
$result = $conn->query("
  SELECT e.*, COUNT(er.id) as entries_count
  FROM exams e
  LEFT JOIN exam_results er ON er.exam_id = e.id AND er.status = 'draft'
  WHERE e.status IN ('ongoing', 'upcoming')
  GROUP BY e.id
  HAVING entries_count > 0
  ORDER BY e.exam_date DESC
");
while ($row = $result->fetch_assoc()) {
  $exams[] = $row;
}

$selectedExam = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : ($exams[0]['id'] ?? 0);
$examDetails = null;
$results = [];

if ($selectedExam) {
  // Get exam details
  $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
  $stmt->bind_param("i", $selectedExam);
  $stmt->execute();
  $examDetails = $stmt->get_result()->fetch_assoc();
  
  // Get exam type
  $examTitle = strtolower($examDetails['title'] ?? '');
  $examType = 'qaida';
  if (strpos($examTitle, 'حفظ') !== false || strpos($examTitle, 'hifz') !== false) {
    $examType = 'hifz';
  } elseif (strpos($examTitle, 'ناظرہ') !== false || strpos($examTitle, 'nazira') !== false) {
    $examType = 'nazira';
  }
  
  // Get all results for this exam - grouped by halaqa
  $stmt = $conn->prepare("
    SELECT er.*, s.full_name_ur, s.shuba, s.mumayyaz as student_mumayyaz, h.name_ur as halaqa_name, h.gender as halaqa_gender
    FROM exam_results er
    JOIN students s ON er.student_id = s.id
    JOIN halaqaat h ON s.halaqa_id = h.id
    WHERE er.exam_id = ? AND er.status = 'draft'
    ORDER BY h.gender, h.name_ur, er.percentage DESC
  ");
  $stmt->bind_param("i", $selectedExam);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $results[] = $row;
  }
}

// Finalize exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize_exam'])) {
  $examId = intval($_POST['exam_id']);
  
  // Update all results to finalized
  $stmt = $conn->prepare("UPDATE exam_results SET status = 'finalized' WHERE exam_id = ?");
  $stmt->bind_param("i", $examId);
  
  if ($stmt->execute()) {
    // Update exam status to finalized
    $stmt = $conn->prepare("UPDATE exams SET status = 'finalized' WHERE id = ?");
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    
    header('Location: exam_finalize.php?success=1');
    exit;
  } else {
    $error = $lang === 'ur' ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
  }
}

if (isset($_GET['success'])) {
  $success = $lang === 'ur' ? 'امتحان کامیابی سے حتمی شکل میں آ گیا۔' : 'Exam finalized successfully.';
}

include __DIR__ . '/includes/header.php';
?>

<!-- Exam Selector -->
<div class="card mb-4">
  <div class="cardBody">
    <form method="GET" action="" class="row g-3">
      <div class="col-md-12">
        <label class="formLabel"><?php echo $lang === 'ur' ? 'امتحان منتخب کریں' : 'Select Exam'; ?></label>
        <select class="formSelect" name="exam_id" onchange="this.form.submit()">
          <?php if (empty($exams)): ?>
          <option value=""><?php echo $lang === 'ur' ? 'کوئی حتمی کرنے کے لیے امتحان نہیں' : 'No exams pending finalization'; ?></option>
          <?php else: ?>
          <?php foreach ($exams as $exam): ?>
          <option value="<?php echo $exam['id']; ?>" <?php echo $selectedExam == $exam['id'] ? 'selected' : ''; ?>>
            <?php echo h($exam['title']); ?> (<?php echo date('M d, Y', strtotime($exam['exam_date'])); ?>) - <?php echo $exam['entries_count']; ?> <?php echo $lang === 'ur' ? 'اندراجات' : 'entries'; ?>
          </option>
          <?php endforeach; ?>
          <?php endif; ?>
        </select>
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

<?php if ($selectedExam && $examDetails && !empty($results)): 
  // Determine exam type
  $examTitle = strtolower($examDetails['title'] ?? '');
  $examType = 'qaida';
  if (strpos($examTitle, 'حفظ') !== false || strpos($examTitle, 'hifz') !== false) {
    $examType = 'hifz';
  } elseif (strpos($examTitle, 'ناظرہ') !== false || strpos($examTitle, 'nazira') !== false) {
    $examType = 'nazira';
  }
  
  $maxNisaab = ($examType === 'qaida') ? 70 : 60;
?>
<!-- Exam Info -->
<div class="card mb-4" style="background: var(--secondary); color: #fff;">
  <div class="cardBody">
    <div class="row align-items-center">
      <div class="col-md-4">
        <strong><i class="bi bi-file-text me-2"></i><?php echo h($examDetails['title']); ?></strong>
      </div>
      <div class="col-md-4">
        <i class="bi bi-calendar-event me-2"></i><?php echo date('M d, Y', strtotime($examDetails['exam_date'])); ?>
      </div>
      <div class="col-md-4 text-md-end">
        <span class="tag" style="background: #fff; color: var(--secondary);">
          <i class="bi bi-exclamation-triangle-fill me-1"></i>
          <?php echo $lang === 'ur' ? 'حتمی شکل کے لیے تیار' : 'Ready for Finalization'; ?>
        </span>
      </div>
    </div>
  </div>
</div>

<!-- Results Summary -->
<?php
$totalStudents = count($results);
$passedStudents = count(array_filter($results, fn($r) => $r['percentage'] >= 40));
$failedStudents = $totalStudents - $passedStudents;
$avgPercentage = array_sum(array_column($results, 'percentage')) / $totalStudents;
$mumtaazStudents = count(array_filter($results, fn($r) => $r['percentage'] >= 85));
?>

<div class="row g-4 mb-4">
  <div class="col-md-3">
    <div class="statCard text-center">
      <div class="statIcon primary mx-auto"><i class="bi bi-people-fill"></i></div>
      <div class="statValue"><?php echo $totalStudents; ?></div>
      <div class="statLabel"><?php echo $lang === 'ur' ? 'کل طلباء' : 'Total Students'; ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="statCard text-center" style="background: var(--primary); color: #fff;">
      <div class="statIcon mx-auto" style="background: rgba(255,255,255,0.2);"><i class="bi bi-trophy-fill"></i></div>
      <div class="statValue"><?php echo $mumtaazStudents; ?></div>
      <div class="statLabel"><?php echo $lang === 'ur' ? 'ممتاز' : 'Mumtaaz'; ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="statCard text-center" style="background: var(--secondary); color: #fff;">
      <div class="statIcon mx-auto" style="background: rgba(255,255,255,0.2);"><i class="bi bi-check-circle-fill"></i></div>
      <div class="statValue"><?php echo $passedStudents; ?></div>
      <div class="statLabel"><?php echo $lang === 'ur' ? 'پاس' : 'Passed'; ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="statCard text-center" style="background: var(--accent); color: #fff;">
      <div class="statIcon mx-auto" style="background: rgba(255,255,255,0.2);"><i class="bi bi-percent"></i></div>
      <div class="statValue"><?php echo round($avgPercentage, 1); ?>%</div>
      <div class="statLabel"><?php echo $lang === 'ur' ? 'اوسط' : 'Average'; ?></div>
    </div>
  </div>
</div>

<!-- Results Table -->
<div class="card mb-4">
  <div class="cardHeader">
    <span><i class="bi bi-table me-2"></i><?php echo $lang === 'ur' ? 'امتحانی نتائج' : 'Exam Results'; ?></span>
  </div>
  <div class="cardBody p-0">
    <div class="tableContainer" style="overflow-x: auto;">
      <table class="table mb-0" style="min-width: 1000px;">
        <thead>
          <tr style="background: var(--primary); color: #fff;">
            <th rowspan="2"><?php echo $lang === 'ur' ? 'درجہ' : 'Rank'; ?></th>
            <th rowspan="2"><?php echo h($tr['name']); ?></th>
            <th rowspan="2"><?php echo h($tr['halaqa']); ?></th>
            <th rowspan="2"><?php echo h($tr['shuba']); ?></th>
            <th colspan="2" class="text-center"><?php echo h($tr['nisaab']); ?></th>
            <th rowspan="2" class="text-center"><?php echo h($tr['nisaab']); ?><br>(<?php echo $maxNisaab; ?>)</th>
            <?php if ($examType !== 'qaida'): ?>
            <th rowspan="2" class="text-center"><?php echo h($tr['husn_sawt']); ?></th>
            <?php endif; ?>
            <th rowspan="2" class="text-center"><?php echo h($tr['tajweed']); ?></th>
            <th rowspan="2" class="text-center"><?php echo h($tr['izaafat']); ?></th>
            <th rowspan="2" class="text-center"><?php echo h($tr['sulook']); ?></th>
            <th rowspan="2" class="text-center"><?php echo h($tr['total']); ?></th>
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
          $rank = 1;
          $currentHalaqa = '';
          foreach ($results as $result): 
            if ($currentHalaqa !== $result['halaqa_name']):
              $currentHalaqa = $result['halaqa_name'];
              $rank = 1;
          ?>
          <tr style="background: rgba(170, 129, 94, 0.15);">
            <td colspan="16" class="fw-bold" style="padding: 8px 16px;">
              <i class="bi bi-building me-2"></i><?php echo h($currentHalaqa); ?> (<?php echo h($tr[$result['halaqa_gender']]); ?>)
            </td>
          </tr>
          <?php endif; ?>
          <tr>
            <td><span class="tag">#<?php echo $rank++; ?></span></td>
            <td>
              <?php echo h($result['full_name_ur']); ?>
              <?php if ($result['student_mumayyaz']): ?>
                <span style="background: rgba(15,45,61,0.15); color: #0f2d3d; padding: 2px 6px; border-radius: 4px; font-size: 10px;"><i class="bi bi-star-fill"></i></span>
              <?php endif; ?>
            </td>
            <td><?php echo h($result['halaqa_name']); ?></td>
            <td><?php echo h($tr[$result['shuba']]); ?></td>
            <td class="text-center"><?php echo h($result['nisaab_from'] ?: '-'); ?></td>
            <td class="text-center"><?php echo h($result['nisaab_to'] ?: '-'); ?></td>
            <td class="text-center"><?php echo $result['nisaab_marks']; ?></td>
            <?php if ($examType !== 'qaida'): ?>
            <td class="text-center"><?php echo $result['husn_sawt']; ?></td>
            <?php endif; ?>
            <td class="text-center"><?php echo $result['tajweed']; ?></td>
            <td class="text-center"><?php echo $result['izaafat']; ?></td>
            <td class="text-center"><?php echo $result['sulook']; ?></td>
            <td class="text-center fw-bold"><?php echo $result['marks_obtained']; ?></td>
            <td class="text-center">
              <span style="background: rgba(15,45,61,0.1); color: #0f2d3d; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                <?php echo round($result['percentage'], 1); ?>%
              </span>
            </td>
            <td class="text-center fw-bold"><?php echo h($result['taqdeer']); ?></td>
            <td class="text-center">
              <?php if ($result['mumayyaz']): ?>
                <span style="background: rgba(15,45,61,0.15); color: #0f2d3d; padding: 4px 8px; border-radius: 12px; font-size: 11px;"><i class="bi bi-star-fill"></i> <?php echo h($tr['mumayyaz']); ?></span>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Finalize Button -->
<div class="card">
  <div class="cardBody text-center p-4">
    <i class="bi bi-exclamation-triangle-fill" style="color: var(--secondary); font-size: 48px; margin-bottom: 16px; display: block;"></i>
    <h5 class="mb-3"><?php echo $lang === 'ur' ? 'کیا آپ اس امتحان کو حتمی شکل دینا چاہتے ہیں؟' : 'Are you sure you want to finalize this exam?'; ?></h5>
    <p class="text-muted mb-4">
      <?php echo $lang === 'ur' ? 'ایک بار حتمی شکل میں آنے کے بعد، آپ نمبر میں تبدیلی نہیں کر سکیں گے۔' : 'Once finalized, you will not be able to modify the marks.'; ?>
    </p>
    <form method="POST" action="" onsubmit="return confirm('<?php echo $lang === 'ur' ? 'کیا آپ واقعی حتمی شکل دینا چاہتے ہیں؟' : 'Are you sure you want to finalize?'; ?>');">
      <input type="hidden" name="exam_id" value="<?php echo $selectedExam; ?>">
      <button type="submit" name="finalize_exam" class="btn btnSecondary btn-lg">
        <i class="bi bi-lock-fill me-2"></i><?php echo h($tr['finalize']); ?> <?php echo $lang === 'ur' ? 'امتحان' : 'Exam'; ?>
      </button>
    </form>
  </div>
</div>

<?php elseif ($selectedExam): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle-fill me-2"></i>
  <?php echo $lang === 'ur' ? 'اس امتحان کے لیے کوئی نمبر درج نہیں کیے گئے۔' : 'No marks have been entered for this exam.'; ?>
  <a href="exam_marks_entry.php?exam_id=<?php echo $selectedExam; ?>" class="alert-link">
    <?php echo $lang === 'ur' ? 'نمبر درج کریں' : 'Enter marks'; ?>
  </a>
</div>
<?php else: ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle-fill me-2"></i>
  <?php echo $lang === 'ur' ? 'حتمی شکل کے لیے کوئی امتحان نہیں۔' : 'No exams pending finalization.'; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
