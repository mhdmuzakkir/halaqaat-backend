<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
if ($_SESSION['role'] !== 'ustaaz' && $_SESSION['role'] !== 'ustadah' && $_SESSION['role'] !== 'admin') {
  header("Location: dashboard_admin.php");
  exit;
}

$teacherId = $_SESSION['user_id'];
$lang = get_language();
$tr = get_translations($lang);

// Get teacher's halaqaat
$halaqaat = [];
if ($_SESSION['role'] === 'admin') {
  $result = $conn->query("SELECT id, name_ur FROM halaqaat WHERE state = 'active' ORDER BY name_ur");
} else {
  $stmt = $conn->prepare("SELECT id, name_ur FROM halaqaat WHERE ustaaz_user_id = ? AND state = 'active' ORDER BY name_ur");
  $stmt->bind_param("i", $teacherId);
  $stmt->execute();
  $result = $stmt->get_result();
}
while ($row = $result->fetch_assoc()) {
  $halaqaat[] = $row;
}

$selectedHalaqa = isset($_GET['halaqa_id']) ? intval($_GET['halaqa_id']) : ($halaqaat[0]['id'] ?? 0);

// Get students with their exam results
$students = [];
if ($selectedHalaqa) {
  $stmt = $conn->prepare("
    SELECT s.*, 
           AVG(er.percentage) as avg_percentage,
           COUNT(er.id) as exams_count,
           MAX(er.percentage) as highest_percentage,
           MIN(er.percentage) as lowest_percentage
    FROM students s
    LEFT JOIN exam_results er ON er.student_id = s.id AND er.status = 'finalized'
    WHERE s.halaqa_id = ? AND s.status = 'active'
    GROUP BY s.id
    ORDER BY s.full_name_ur
  ");
  $stmt->bind_param("i", $selectedHalaqa);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $students[] = $row;
  }
}

// Get exam results for each student
$studentExams = [];
if ($selectedHalaqa) {
  $stmt = $conn->prepare("
    SELECT er.*, e.title as exam_title, e.exam_date, s.id as student_id
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.id
    JOIN students s ON er.student_id = s.id
    WHERE s.halaqa_id = ? AND er.status = 'finalized'
    ORDER BY e.exam_date DESC
  ");
  $stmt->bind_param("i", $selectedHalaqa);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $studentExams[$row['student_id']][] = $row;
  }
}

// Get attendance summary for each student
$attendanceSummary = [];
if ($selectedHalaqa) {
  $stmt = $conn->prepare("
    SELECT s.id,
           COUNT(a.id) as total_days,
           SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days
    FROM students s
    LEFT JOIN attendance a ON a.student_id = s.id
    WHERE s.halaqa_id = ? AND s.status = 'active'
    GROUP BY s.id
  ");
  $stmt->bind_param("i", $selectedHalaqa);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $attendanceSummary[$row['id']] = $row;
  }
}

include __DIR__ . '/includes/header.php';
?>

<!-- Filters -->
<div class="card mb-4">
  <div class="cardBody">
    <form method="GET" action="" class="row g-3">
      <div class="col-md-6">
        <label class="formLabel"><?php echo h($tr['halaqa']); ?></label>
        <select class="formSelect" name="halaqa_id" onchange="this.form.submit()">
          <?php foreach ($halaqaat as $h): ?>
          <option value="<?php echo $h['id']; ?>" <?php echo $selectedHalaqa == $h['id'] ? 'selected' : ''; ?>>
            <?php echo h($h['name_ur']); ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6 d-flex align-items-end">
        <a href="export.php?type=progress&halaqa_id=<?php echo $selectedHalaqa; ?>" class="btn btnPrimary">
          <i class="bi bi-download me-2"></i><?php echo h($tr['export']); ?>
        </a>
      </div>
    </form>
  </div>
</div>

<?php if ($selectedHalaqa && !empty($students)): ?>
<!-- Students Progress Cards -->
<div class="row g-4">
  <?php foreach ($students as $student): 
    $avgPercentage = $student['avg_percentage'] ?? 0;
    $examsCount = $student['exams_count'] ?? 0;
    $attendance = $attendanceSummary[$student['id']] ?? null;
    $attendancePercentage = ($attendance && $attendance['total_days'] > 0) 
      ? round(($attendance['present_days'] / $attendance['total_days']) * 100, 1) 
      : 0;
  ?>
  <div class="col-md-6 col-lg-4">
    <div class="card h-100">
      <div class="cardBody">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <h5 class="card-title mb-1"><?php echo h($student['full_name_ur']); ?></h5>
            <small class="text-muted"><?php echo h($tr[$student['shuba']]); ?></small>
          </div>
          <?php if ($student['mumayyaz']): ?>
            <span class="tag green"><i class="bi bi-star-fill"></i> <?php echo h($tr['mumayyaz']); ?></span>
          <?php endif; ?>
        </div>
        
        <!-- Exam Progress -->
        <?php if ($examsCount > 0): ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <small><?php echo $lang === 'ur' ? 'امتحانی کارکردگی' : 'Exam Performance'; ?></small>
            <small class="fw-bold"><?php echo round($avgPercentage, 1); ?>%</small>
          </div>
          <div class="progressBar">
            <div class="progressFill <?php echo $avgPercentage >= 80 ? 'green' : ($avgPercentage >= 60 ? 'orange' : 'pink'); ?>" 
                 style="width: <?php echo $avgPercentage; ?>%"></div>
          </div>
          <small class="text-muted">
            <?php echo $examsCount; ?> <?php echo $lang === 'ur' ? 'امتحانات' : 'exams'; ?> | 
            <?php echo $lang === 'ur' ? 'اعلیٰ:' : 'High:'; ?> <?php echo round($student['highest_percentage'], 1); ?>% | 
            <?php echo $lang === 'ur' ? 'ادنیٰ:' : 'Low:'; ?> <?php echo round($student['lowest_percentage'], 1); ?>%
          </small>
        </div>
        <?php endif; ?>
        
        <!-- Attendance Progress -->
        <?php if ($attendance && $attendance['total_days'] > 0): ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <small><?php echo $lang === 'ur' ? 'حاضری' : 'Attendance'; ?></small>
            <small class="fw-bold"><?php echo $attendancePercentage; ?>%</small>
          </div>
          <div class="progressBar">
            <div class="progressFill" style="width: <?php echo $attendancePercentage; ?>%; background: #17a2b8;"></div>
          </div>
          <small class="text-muted">
            <?php echo $attendance['present_days']; ?>/<?php echo $attendance['total_days']; ?> <?php echo $lang === 'ur' ? 'دن' : 'days'; ?>
          </small>
        </div>
        <?php endif; ?>
        
        <!-- Recent Exam Results -->
        <?php if (isset($studentExams[$student['id']])): 
          $recentExams = array_slice($studentExams[$student['id']], 0, 3);
        ?>
        <div class="mt-3 pt-3" style="border-top: 1px solid var(--border);">
          <small class="text-muted d-block mb-2"><?php echo $lang === 'ur' ? 'حالیہ امتحانات:' : 'Recent Exams:'; ?></small>
          <?php foreach ($recentExams as $exam): ?>
          <div class="d-flex justify-content-between align-items-center mb-1">
            <small><?php echo h($exam['exam_title']); ?></small>
            <span class="tag <?php echo $exam['percentage'] >= 60 ? 'green' : ($exam['percentage'] >= 40 ? 'orange' : 'pink'); ?>">
              <?php echo $exam['marks_obtained']; ?>/<?php echo $exam['max_marks']; ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="mt-3">
          <a href="report_student.php?id=<?php echo $student['id']; ?>" class="btn btnPrimary w-100" style="padding: 8px;">
            <i class="bi bi-eye me-2"></i><?php echo $lang === 'ur' ? 'مکمل رپورٹ' : 'Full Report'; ?>
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Summary Table -->
<div class="card mt-4">
  <div class="cardHeader">
    <span><i class="bi bi-table me-2"></i><?php echo $lang === 'ur' ? 'مکمل خلاصہ' : 'Complete Summary'; ?></span>
  </div>
  <div class="cardBody p-0">
    <div class="tableContainer">
      <table class="table mb-0" id="progressTable">
        <thead>
          <tr>
            <th>#</th>
            <th><?php echo h($tr['name']); ?></th>
            <th><?php echo h($tr['shuba']); ?></th>
            <th><?php echo $lang === 'ur' ? 'امتحانات' : 'Exams'; ?></th>
            <th><?php echo $lang === 'ur' ? 'اوسط %' : 'Avg %'; ?></th>
            <th><?php echo $lang === 'ur' ? 'حاضری %' : 'Attendance %'; ?></th>
            <th><?php echo h($tr['mumayyaz']); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $index => $student): 
            $avgPercentage = $student['avg_percentage'] ?? 0;
            $examsCount = $student['exams_count'] ?? 0;
            $attendance = $attendanceSummary[$student['id']] ?? null;
            $attendancePercentage = ($attendance && $attendance['total_days'] > 0) 
              ? round(($attendance['present_days'] / $attendance['total_days']) * 100, 1) 
              : 0;
          ?>
          <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo h($student['full_name_ur']); ?></td>
            <td><?php echo h($tr[$student['shuba']]); ?></td>
            <td><?php echo $examsCount; ?></td>
            <td>
              <?php if ($examsCount > 0): ?>
                <span class="tag <?php echo $avgPercentage >= 80 ? 'green' : ($avgPercentage >= 60 ? 'orange' : 'pink'); ?>">
                  <?php echo round($avgPercentage, 1); ?>%
                </span>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($attendance && $attendance['total_days'] > 0): ?>
                <span class="tag" style="background: #17a2b8; color: #fff;"><?php echo $attendancePercentage; ?>%</span>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($student['mumayyaz']): ?>
                <span class="tag green"><i class="bi bi-star-fill"></i></span>
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

<?php elseif ($selectedHalaqa): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle-fill me-2"></i>
  <?php echo $lang === 'ur' ? 'اس حلقہ میں کوئی طالب علم نہیں۔' : 'No students in this halaqa.'; ?>
</div>
<?php else: ?>
<div class="alert alert-warning">
  <i class="bi bi-exclamation-triangle-fill me-2"></i>
  <?php echo $lang === 'ur' ? 'آپ کو ابھی تک کوئی حلقہ تفویض نہیں کیا گیا۔' : 'No halaqaat have been assigned to you yet.'; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
