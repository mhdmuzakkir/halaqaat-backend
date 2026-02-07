<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if (!isset($_GET['id'])) {
  header("Location: students_manage.php");
  exit;
}

$studentId = intval($_GET['id']);
$lang = get_language();
$tr = get_translations($lang);

// Get student details
$stmt = $conn->prepare("
  SELECT s.*, h.name_ur as halaqa_name, h.gender as halaqa_gender, u.full_name as ustaaz_name
  FROM students s
  LEFT JOIN halaqaat h ON s.halaqa_id = h.id
  LEFT JOIN users u ON h.ustaaz_user_id = u.id
  WHERE s.id = ?
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
  header("Location: students_manage.php");
  exit;
}

// Get exam results
$examResults = [];
$stmt = $conn->prepare("
  SELECT er.*, e.title as exam_title, e.exam_date, e.max_marks as exam_max_marks, e.passing_marks
  FROM exam_results er
  JOIN exams e ON er.exam_id = e.id
  WHERE er.student_id = ? AND er.status = 'finalized'
  ORDER BY e.exam_date DESC
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $examResults[] = $row;
}

// Get attendance summary
$attendanceSummary = [];
$stmt = $conn->prepare("
  SELECT 
    COUNT(*) as total_days,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
    SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_days
  FROM attendance
  WHERE student_id = ?
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$attendanceSummary = $stmt->get_result()->fetch_assoc();

// Calculate statistics
$totalExams = count($examResults);
$avgPercentage = $totalExams > 0 ? array_sum(array_column($examResults, 'percentage')) / $totalExams : 0;
$highestPercentage = $totalExams > 0 ? max(array_column($examResults, 'percentage')) : 0;
$lowestPercentage = $totalExams > 0 ? min(array_column($examResults, 'percentage')) : 0;
$passedExams = count(array_filter($examResults, fn($r) => $r['percentage'] >= $r['passing_marks']));

$totalAttendanceDays = $attendanceSummary['total_days'] ?? 0;
$attendancePercentage = $totalAttendanceDays > 0 
  ? round(($attendanceSummary['present_days'] / $totalAttendanceDays) * 100, 1) 
  : 0;

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <h2 class="heroTitle" style="color: var(--accent);"><?php echo h($student['full_name_ur']); ?></h2>
    <?php if ($student['full_name_en']): ?>
      <p class="heroSub" style="color: #666;"><?php echo h($student['full_name_en']); ?></p>
    <?php endif; ?>
  </div>
  <div class="text-end">
    <a href="export.php?type=student&id=<?php echo $studentId; ?>" class="btn btnPrimary mb-2">
      <i class="bi bi-download me-2"></i><?php echo h($tr['export']); ?>
    </a>
    <br>
    <button onclick="printPage()" class="pill">
      <i class="bi bi-printer me-2"></i><?php echo $lang === 'ur' ? 'پرنٹ' : 'Print'; ?>
    </button>
  </div>
</div>

<!-- Student Info Card -->
<div class="card mb-4">
  <div class="cardBody">
    <div class="row">
      <div class="col-md-3 mb-3">
        <small class="text-muted d-block"><?php echo h($tr['shuba']); ?></small>
        <strong><?php echo h($tr[$student['shuba']]); ?></strong>
      </div>
      <div class="col-md-3 mb-3">
        <small class="text-muted d-block"><?php echo h($tr['halaqa']); ?></small>
        <strong><?php echo h($student['halaqa_name'] ?: '-'); ?></strong>
      </div>
      <div class="col-md-3 mb-3">
        <small class="text-muted d-block"><?php echo h($tr['ustaaz']); ?></small>
        <strong><?php echo h($student['ustaaz_name'] ?: '-'); ?></strong>
      </div>
      <div class="col-md-3 mb-3">
        <?php if ($student['mumayyaz']): ?>
          <span class="tag green fs-6"><i class="bi bi-star-fill me-1"></i> <?php echo h($tr['mumayyaz']); ?></span>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($student['qaida_takhti'] || $student['surah_current']): ?>
    <div class="row mt-2">
      <?php if ($student['qaida_takhti']): ?>
      <div class="col-md-3 mb-3">
        <small class="text-muted d-block"><?php echo h($tr['takhti']); ?></small>
        <strong><?php echo $student['qaida_takhti']; ?></strong>
      </div>
      <?php endif; ?>
      <?php if ($student['surah_current']): ?>
      <div class="col-md-3 mb-3">
        <small class="text-muted d-block"><?php echo h($tr['surah']); ?></small>
        <strong><?php echo $student['surah_current']; ?></strong>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
  <div class="col-md-3">
    <div class="statCard text-center">
      <div class="statIcon primary mx-auto"><i class="bi bi-file-text-fill"></i></div>
      <div class="statValue"><?php echo $totalExams; ?></div>
      <div class="statLabel"><?php echo $lang === 'ur' ? 'امتحانات' : 'Exams'; ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="statCard text-center">
      <div class="statIcon secondary mx-auto"><i class="bi bi-percent"></i></div>
      <div class="statValue"><?php echo round($avgPercentage, 1); ?>%</div>
      <div class="statLabel"><?php echo $lang === 'ur' ? 'اوسط' : 'Average'; ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="statCard text-center" style="background: var(--primary); color: #fff;">
      <div class="statIcon mx-auto" style="background: rgba(255,255,255,0.2);"><i class="bi bi-check-circle-fill"></i></div>
      <div class="statValue"><?php echo $passedExams; ?>/<?php echo $totalExams; ?></div>
      <div class="statLabel"><?php echo $lang === 'ur' ? 'پاس' : 'Passed'; ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="statCard text-center">
      <div class="statIcon mx-auto" style="background: rgba(23, 162, 184, 0.2);"><i class="bi bi-calendar-check" style="color: #17a2b8;"></i></div>
      <div class="statValue"><?php echo $attendancePercentage; ?>%</div>
      <div class="statLabel"><?php echo $lang === 'ur' ? 'حاضری' : 'Attendance'; ?></div>
    </div>
  </div>
</div>

<!-- Exam Results -->
<div class="card mb-4">
  <div class="cardHeader">
    <span><i class="bi bi-file-text-fill me-2"></i><?php echo $lang === 'ur' ? 'امتحانی نتائج' : 'Exam Results'; ?></span>
  </div>
  <div class="cardBody p-0">
    <?php if (!empty($examResults)): ?>
    <div class="tableContainer">
      <table class="table mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th><?php echo $lang === 'ur' ? 'امتحان' : 'Exam'; ?></th>
            <th><?php echo h($tr['date']); ?></th>
            <th><?php echo $lang === 'ur' ? 'نمبر' : 'Marks'; ?></th>
            <th><?php echo $lang === 'ur' ? 'فیصد' : 'Percentage'; ?></th>
            <th><?php echo $lang === 'ur' ? 'حالت' : 'Status'; ?></th>
            <th><?php echo h($tr['remarks']); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($examResults as $index => $result): 
            $isPassed = $result['percentage'] >= $result['passing_marks'];
          ?>
          <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo h($result['exam_title']); ?></td>
            <td><?php echo date('M d, Y', strtotime($result['exam_date'])); ?></td>
            <td><?php echo $result['marks_obtained']; ?>/<?php echo $result['exam_max_marks']; ?></td>
            <td>
              <span class="tag <?php echo $isPassed ? 'green' : 'pink'; ?>">
                <?php echo round($result['percentage'], 1); ?>%
              </span>
            </td>
            <td>
              <?php if ($isPassed): ?>
                <span class="tag green"><i class="bi bi-check-circle"></i> <?php echo $lang === 'ur' ? 'پاس' : 'Pass'; ?></span>
              <?php else: ?>
                <span class="tag pink"><i class="bi bi-x-circle"></i> <?php echo $lang === 'ur' ? 'فیل' : 'Fail'; ?></span>
              <?php endif; ?>
            </td>
            <td><?php echo h($result['remarks'] ?: '-'); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    
    <!-- Performance Chart -->
    <div class="p-4">
      <h6 class="mb-3"><?php echo $lang === 'ur' ? 'کارکردگی کا گراف' : 'Performance Chart'; ?></h6>
      <canvas id="performanceChart" height="100"></canvas>
    </div>
    
    <script>
    const ctx = document.getElementById('performanceChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?php echo json_encode(array_map(fn($r) => date('M d', strtotime($r['exam_date'])), array_reverse($examResults))); ?>,
        datasets: [{
          label: '<?php echo $lang === 'ur' ? "فیصد" : "Percentage"; ?>',
          data: <?php echo json_encode(array_map(fn($r) => round($r['percentage'], 1), array_reverse($examResults))); ?>,
          borderColor: '#c9a77c',
          backgroundColor: 'rgba(201, 167, 124, 0.1)',
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 100
          }
        }
      }
    });
    </script>
    
    <?php else: ?>
    <div class="text-center py-4">
      <p class="text-muted mb-0"><?php echo $lang === 'ur' ? 'کوئی امتحانی نتیجہ نہیں۔' : 'No exam results found.'; ?></p>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Attendance Summary -->
<?php if ($totalAttendanceDays > 0): ?>
<div class="card">
  <div class="cardHeader">
    <span><i class="bi bi-calendar-check me-2"></i><?php echo $lang === 'ur' ? 'حاضری کا خلاصہ' : 'Attendance Summary'; ?></span>
  </div>
  <div class="cardBody">
    <div class="row g-4">
      <div class="col-md-3">
        <div class="text-center p-3" style="background: var(--bg-light); border-radius: 12px;">
          <h4 class="text-success mb-1"><?php echo $attendanceSummary['present_days']; ?></h4>
          <small class="text-muted"><?php echo h($tr['present']); ?></small>
        </div>
      </div>
      <div class="col-md-3">
        <div class="text-center p-3" style="background: var(--bg-light); border-radius: 12px;">
          <h4 class="text-danger mb-1"><?php echo $attendanceSummary['absent_days']; ?></h4>
          <small class="text-muted"><?php echo h($tr['absent']); ?></small>
        </div>
      </div>
      <div class="col-md-3">
        <div class="text-center p-3" style="background: var(--bg-light); border-radius: 12px;">
          <h4 class="text-warning mb-1"><?php echo $attendanceSummary['late_days']; ?></h4>
          <small class="text-muted"><?php echo h($tr['late']); ?></small>
        </div>
      </div>
      <div class="col-md-3">
        <div class="text-center p-3" style="background: var(--bg-light); border-radius: 12px;">
          <h4 class="text-info mb-1"><?php echo $attendanceSummary['excused_days']; ?></h4>
          <small class="text-muted"><?php echo h($tr['excused']); ?></small>
        </div>
      </div>
    </div>
    
    <div class="mt-4">
      <div class="d-flex justify-content-between mb-2">
        <span><?php echo $lang === 'ur' ? 'حاضری کی شرح' : 'Attendance Rate'; ?></span>
        <strong><?php echo $attendancePercentage; ?>%</strong>
      </div>
      <div class="progressBar" style="height: 20px;">
        <div class="progress-fill" style="width: <?php echo $attendancePercentage; ?>%; background: #17a2b8; height: 100%; border-radius: 999px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 12px; font-weight: 700;">
          <?php echo $attendancePercentage; ?>%
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
