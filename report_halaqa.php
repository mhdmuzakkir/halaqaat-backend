<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if (!isset($_GET['id'])) {
  header("Location: halaqaat_list.php");
  exit;
}

$halaqaId = intval($_GET['id']);
$lang = get_language();
$tr = get_translations($lang);

// Get halaqa details
$stmt = $conn->prepare("
  SELECT h.*, u.full_name as ustaaz_name 
  FROM halaqaat h 
  LEFT JOIN users u ON h.ustaaz_user_id = u.id 
  WHERE h.id = ?
");
$stmt->bind_param("i", $halaqaId);
$stmt->execute();
$halaqa = $stmt->get_result()->fetch_assoc();

if (!$halaqa) {
  header("Location: halaqaat_list.php");
  exit;
}

// Get students
$students = [];
$stmt = $conn->prepare("
  SELECT s.*, 
         AVG(er.percentage) as avg_percentage,
         COUNT(er.id) as exams_count
  FROM students s
  LEFT JOIN exam_results er ON er.student_id = s.id AND er.status = 'finalized'
  WHERE s.halaqa_id = ? AND s.status = 'active'
  GROUP BY s.id
  ORDER BY s.full_name_ur
");
$stmt->bind_param("i", $halaqaId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $students[] = $row;
}

// Get attendance summary
$attendanceSummary = [];
$stmt = $conn->prepare("
  SELECT 
    COUNT(DISTINCT a.date) as total_days,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as total_present,
    COUNT(DISTINCT a.student_id) as students_with_attendance
  FROM attendance a
  JOIN students s ON a.student_id = s.id
  WHERE s.halaqa_id = ?
");
$stmt->bind_param("i", $halaqaId);
$stmt->execute();
$attendanceSummary = $stmt->get_result()->fetch_assoc();

// Get exam results
$examResults = [];
$stmt = $conn->prepare("
  SELECT e.*, AVG(er.percentage) as avg_percentage, COUNT(er.id) as results_count
  FROM exams e
  JOIN exam_results er ON er.exam_id = e.id
  JOIN students s ON er.student_id = s.id
  WHERE s.halaqa_id = ? AND er.status = 'finalized'
  GROUP BY e.id
  ORDER BY e.exam_date DESC
");
$stmt->bind_param("i", $halaqaId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $examResults[] = $row;
}

// Statistics
$totalStudents = count($students);
$mumayyizeenCount = count(array_filter($students, fn($s) => $s['mumayyaz']));
$avgPercentage = $totalStudents > 0 ? array_sum(array_column($students, 'avg_percentage')) / $totalStudents : 0;

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="sectionHeader">
  <div>
    <h2 class="sectionTitle"><?php echo h($halaqa['name_ur']); ?></h2>
    <p style="color: #666; margin: 0;"><?php echo h($tr[$halaqa['gender']]); ?> — <?php echo h($tr[$halaqa['session']]); ?></p>
  </div>
  <div>
    <button onclick="printPage()" class="pill">
      <i class="bi bi-printer me-2"></i><?php echo $lang === 'ur' ? 'پرنٹ' : 'Print'; ?>
    </button>
  </div>
</div>

<!-- Halaqa Info Card -->
<div class="card mb-4">
  <div class="cardBody">
    <div class="row">
      <div class="col-md-3 mb-3">
        <small class="text-muted d-block"><?php echo h($tr['ustaaz']); ?></small>
        <strong><?php echo h($halaqa['ustaaz_name'] ?: '-'); ?></strong>
      </div>
      <div class="col-md-3 mb-3">
        <small class="text-muted d-block"><?php echo h($tr['gender']); ?></small>
        <span class="tag <?php echo $halaqa['gender'] === 'baneen' ? 'blue' : 'pink'; ?>">
          <?php echo h($tr[$halaqa['gender']]); ?>
        </span>
      </div>
      <div class="col-md-3 mb-3">
        <small class="text-muted d-block"><?php echo h($tr['session']); ?></small>
        <span class="tag orange"><?php echo h($tr[$halaqa['session']]); ?></span>
      </div>
      <div class="col-md-3 mb-3">
        <small class="text-muted d-block"><?php echo $lang === 'ur' ? 'مقام' : 'Location'; ?></small>
        <strong><?php echo h($halaqa['location'] ?: '-'); ?></strong>
      </div>
    </div>
  </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
  <div class="col-md-3">
    <div class="statCard text-center">
      <div class="statIcon" style="background: rgba(15, 45, 61, 0.2);"><i class="bi bi-people-fill" style="color: #0f2d3d;"></i></div>
      <div class="statValue"><?php echo $totalStudents; ?></div>
      <div class="statLabel"><?php echo h($tr['total_students']); ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="statCard text-center">
      <div class="statIcon" style="background: rgba(255, 193, 7, 0.3);"><i class="bi bi-star-fill" style="color: #ffc107;"></i></div>
      <div class="statValue"><?php echo $mumayyizeenCount; ?></div>
      <div class="statLabel"><?php echo h($tr['total_mumayyizeen']); ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="statCard text-center">
      <div class="statIcon" style="background: rgba(170, 129, 94, 0.2);"><i class="bi bi-percent" style="color: #aa815e;"></i></div>
      <div class="statValue"><?php echo round($avgPercentage, 1); ?>%</div>
      <div class="statLabel"><?php echo $lang === 'ur' ? 'اوسط فیصد' : 'Avg Percentage'; ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="statCard text-center">
      <div class="statIcon" style="background: rgba(23, 162, 184, 0.2);"><i class="bi bi-calendar-check" style="color: #17a2b8;"></i></div>
      <div class="statValue"><?php echo $attendanceSummary['total_days'] ?? 0; ?></div>
      <div class="statLabel"><?php echo $lang === 'ur' ? 'حاضری کے دن' : 'Attendance Days'; ?></div>
    </div>
  </div>
</div>

<!-- Students Table -->
<div class="card mb-4">
  <div class="cardHeader">
    <span><i class="bi bi-people-fill me-2"></i><?php echo h($tr['students']); ?></span>
  </div>
  <div class="cardBody p-0">
    <div class="tableContainer">
      <table class="table mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th><?php echo h($tr['name']); ?></th>
            <th><?php echo h($tr['shuba']); ?></th>
            <th><?php echo $lang === 'ur' ? 'امتحانات' : 'Exams'; ?></th>
            <th><?php echo $lang === 'ur' ? 'اوسط %' : 'Avg %'; ?></th>
            <th><?php echo h($tr['mumayyaz']); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $index => $student): ?>
          <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo h($student['full_name_ur']); ?></td>
            <td><?php echo h($tr[$student['shuba']]); ?></td>
            <td><?php echo $student['exams_count']; ?></td>
            <td>
              <?php if ($student['exams_count'] > 0): ?>
                <span class="tag <?php echo $student['avg_percentage'] >= 80 ? 'green' : ($student['avg_percentage'] >= 60 ? 'orange' : 'pink'); ?>">
                  <?php echo round($student['avg_percentage'], 1); ?>%
                </span>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($student['mumayyaz']): ?>
                <span class="tag" style="background: rgba(15,45,61,0.15); color: #0f2d3d;"><i class="bi bi-star-fill"></i> <?php echo h($tr['mumayyaz']); ?></span>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="report_student.php?id=<?php echo $student['id']; ?>" class="pill" style="padding: 4px 10px;">
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

<?php if (empty($students)): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle-fill me-2"></i>
  <?php echo $lang === 'ur' ? 'اس حلقہ میں کوئی طالب علم نہیں۔' : 'No students in this halaqa.'; ?>
</div>
<?php endif; ?>

<!-- Exam Results -->
<?php if (!empty($examResults)): ?>
<div class="card">
  <div class="cardHeader">
    <span><i class="bi bi-file-text-fill me-2"></i><?php echo $lang === 'ur' ? 'امتحانی نتائج' : 'Exam Results'; ?></span>
  </div>
  <div class="cardBody p-0">
    <div class="tableContainer">
      <table class="table mb-0">
        <thead>
          <tr>
            <th><?php echo $lang === 'ur' ? 'امتحان' : 'Exam'; ?></th>
            <th><?php echo $lang === 'ur' ? 'تاریخ' : 'Date'; ?></th>
            <th><?php echo $lang === 'ur' ? 'طلباء' : 'Students'; ?></th>
            <th><?php echo $lang === 'ur' ? 'اوسط %' : 'Avg %'; ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($examResults as $exam): ?>
          <tr>
            <td><?php echo h($exam['title']); ?></td>
            <td><?php echo date('M d, Y', strtotime($exam['exam_date'])); ?></td>
            <td><?php echo $exam['results_count']; ?></td>
            <td>
              <span class="tag <?php echo $exam['avg_percentage'] >= 80 ? 'green' : ($exam['avg_percentage'] >= 60 ? 'orange' : 'pink'); ?>">
                <?php echo round($exam['avg_percentage'], 1); ?>%
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
