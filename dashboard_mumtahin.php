<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
if ($_SESSION['role'] !== 'mumtahin' && $_SESSION['role'] !== 'admin') {
  header("Location: dashboard_admin.php");
  exit;
}

$lang = get_language();
$tr = get_translations($lang);

// Get exams summary
$examsSummary = [
  'upcoming' => 0,
  'ongoing' => 0,
  'finalized' => 0
];

$result = $conn->query("SELECT status, COUNT(*) as count FROM exams GROUP BY status");
while ($row = $result->fetch_assoc()) {
  $examsSummary[$row['status']] = $row['count'];
}

// Recent exams
$recentExams = [];
$result = $conn->query("
  SELECT e.*, 
         COUNT(er.id) as results_count,
         AVG(er.percentage) as avg_percentage
  FROM exams e
  LEFT JOIN exam_results er ON er.exam_id = e.id AND er.status = 'finalized'
  GROUP BY e.id
  ORDER BY e.exam_date DESC
  LIMIT 6
");
while ($row = $result->fetch_assoc()) {
  $recentExams[] = $row;
}

// Exams needing finalization
$pendingFinalization = [];
$result = $conn->query("
  SELECT e.*, COUNT(er.id) as entries_count
  FROM exams e
  LEFT JOIN exam_results er ON er.exam_id = e.id
  WHERE e.status = 'ongoing'
  GROUP BY e.id
  HAVING entries_count > 0
  ORDER BY e.exam_date DESC
");
while ($row = $result->fetch_assoc()) {
  $pendingFinalization[] = $row;
}

// Total students
$totalStudents = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'")->fetch_assoc()['count'];
$totalHalaqaat = $conn->query("SELECT COUNT(*) as count FROM halaqaat WHERE state = 'active'")->fetch_assoc()['count'];

include __DIR__ . '/includes/header.php';
?>

<!-- Stats Grid -->
<div class="statsGrid">
  <div class="statCard">
    <div class="statIcon" style="background: rgba(23, 162, 184, 0.2);"><i class="bi bi-calendar-event" style="color: #17a2b8;"></i></div>
    <div class="statContent">
      <div class="statLabel"><?php echo $lang === 'ur' ? 'آنے والے امتحانات' : 'Upcoming Exams'; ?></div>
      <div class="statValue"><?php echo $examsSummary['upcoming']; ?></div>
    </div>
  </div>
  
  <div class="statCard" style="background: var(--secondary); color: #fff;">
    <div class="statIcon" style="background: rgba(255,255,255,0.2);"><i class="bi bi-pencil-square"></i></div>
    <div class="statContent">
      <div class="statLabel"><?php echo $lang === 'ur' ? 'جاری امتحانات' : 'Ongoing Exams'; ?></div>
      <div class="statValue"><?php echo $examsSummary['ongoing']; ?></div>
    </div>
  </div>
  
  <div class="statCard green">
    <div class="statIcon"><i class="bi bi-check-circle-fill"></i></div>
    <div class="statContent">
      <div class="statLabel"><?php echo $lang === 'ur' ? 'حتمی امتحانات' : 'Finalized Exams'; ?></div>
      <div class="statValue"><?php echo $examsSummary['finalized']; ?></div>
    </div>
  </div>
  
  <div class="statCard tan">
    <div class="statIcon"><i class="bi bi-mortarboard-fill"></i></div>
    <div class="statContent">
      <div class="statLabel"><?php echo h($tr['total_students']); ?></div>
      <div class="statValue"><?php echo $totalStudents; ?></div>
    </div>
  </div>
</div>

<!-- Pending Finalization Alert -->
<?php if (!empty($pendingFinalization)): ?>
<div class="card mb-4" style="background: var(--secondary); color: #fff;">
  <div class="cardBody d-flex align-items-center">
    <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 24px;"></i>
    <div>
      <strong><?php echo $lang === 'ur' ? 'توجہ!' : 'Attention!'; ?></strong>
      <?php echo $lang === 'ur' ? count($pendingFinalization) . ' امتحانات حتمی شکل کے منتظر ہیں۔' : count($pendingFinalization) . ' exams are pending finalization.'; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="sectionHeader">
  <div class="sectionTitle"><?php echo $lang === 'ur' ? 'فوری عمل' : 'Quick Actions'; ?></div>
</div>

<div class="row g-4 mb-4">
  <div class="col-md-4">
    <a href="exam_marks_entry.php" class="btn btnPrimary w-100 py-4">
      <i class="bi bi-pencil-square fs-2 d-block mb-2"></i>
      <strong><?php echo h($tr['marks']); ?></strong>
      <small class="d-block opacity-75"><?php echo $lang === 'ur' ? 'نمبر درج کریں' : 'Enter exam marks'; ?></small>
    </a>
  </div>
  <div class="col-md-4">
    <a href="exam_finalize.php" class="btn btnSecondary w-100 py-4">
      <i class="bi bi-lock-fill fs-2 d-block mb-2"></i>
      <strong><?php echo h($tr['finalize']); ?></strong>
      <small class="d-block opacity-75"><?php echo $lang === 'ur' ? 'امتحانات حتمی کریں' : 'Finalize exam results'; ?></small>
    </a>
  </div>
  <div class="col-md-4">
    <a href="exams_manage.php" class="btn w-100 py-4" style="background: #e9ecef; color: #333;">
      <i class="bi bi-file-text-fill fs-2 d-block mb-2"></i>
      <strong><?php echo h($tr['nav_exams']); ?></strong>
      <small class="d-block opacity-75"><?php echo $lang === 'ur' ? 'تمام امتحانات' : 'View all exams'; ?></small>
    </a>
  </div>
</div>

<!-- Recent Exams -->
<div class="sectionHeader">
  <div class="sectionTitle"><?php echo $lang === 'ur' ? 'حالیہ امتحانات' : 'Recent Exams'; ?></div>
  <a href="exams_manage.php" class="pill"><?php echo h($tr['view_all']); ?></a>
</div>

<div class="row g-4">
  <?php foreach ($recentExams as $exam): 
    $statusClass = $exam['status'] === 'finalized' ? 'green' : ($exam['status'] === 'ongoing' ? 'orange' : 'blue');
  ?>
  <div class="col-md-6 col-lg-4">
    <div class="card h-100">
      <div class="cardBody">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <h6 class="card-title mb-0"><?php echo h($exam['title']); ?></h6>
          <span class="tag <?php echo $statusClass; ?>">
            <?php echo ucfirst($exam['status']); ?>
          </span>
        </div>
        
        <p class="mb-1 text-muted">
          <i class="bi bi-calendar-event me-2"></i>
          <?php echo date('M d, Y', strtotime($exam['exam_date'])); ?>
        </p>
        <p class="mb-2 text-muted">
          <i class="bi bi-award me-2"></i>
          <?php echo $tr['max_marks']; ?>: <?php echo $exam['max_marks']; ?>
        </p>
        
        <?php if ($exam['status'] === 'finalized' && $exam['avg_percentage']): ?>
        <div class="mt-3 pt-3" style="border-top: 1px solid var(--border);">
          <small class="text-muted"><?php echo $lang === 'ur' ? 'اوسط کارکردگی:' : 'Average Performance:'; ?></small>
          <div class="d-flex align-items-center mt-1">
            <div class="flex-grow-1 me-2">
              <div class="progressBar" style="height: 8px;">
                <div class="progressFill <?php echo $exam['avg_percentage'] >= 60 ? 'green' : 'orange'; ?>" 
                     style="width: <?php echo $exam['avg_percentage']; ?>%"></div>
              </div>
            </div>
            <span class="fw-bold"><?php echo round($exam['avg_percentage'], 1); ?>%</span>
          </div>
        </div>
        <?php endif; ?>
        
        <div class="mt-3 d-flex gap-2">
          <?php if ($exam['status'] !== 'finalized'): ?>
          <a href="exam_marks_entry.php?exam_id=<?php echo $exam['id']; ?>" class="btn btnPrimary flex-fill" style="padding: 6px 12px; font-size: 12px;">
            <i class="bi bi-pencil-square me-1"></i> <?php echo h($tr['marks']); ?>
          </a>
          <?php endif; ?>
          <?php if ($exam['status'] === 'ongoing'): ?>
          <a href="exam_finalize.php?exam_id=<?php echo $exam['id']; ?>" class="btn btnSecondary flex-fill" style="padding: 6px 12px; font-size: 12px;">
            <i class="bi bi-lock me-1"></i> <?php echo h($tr['finalize']); ?>
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if (empty($recentExams)): ?>
<div class="text-center py-5">
  <i class="bi bi-inbox fs-1 text-muted"></i>
  <p class="mt-3 text-muted"><?php echo h($tr['no_data']); ?></p>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
