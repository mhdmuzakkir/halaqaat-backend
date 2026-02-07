<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
if ($_SESSION['role'] !== 'ustaaz' && $_SESSION['role'] !== 'ustadah') {
  header("Location: dashboard_admin.php");
  exit;
}

$teacherId = $_SESSION['user_id'];
$lang = get_language();
$tr = get_translations($lang);

// Fetch teacher's halaqaat
$halaqaat = [];
$stmt = $conn->prepare("
  SELECT h.*, 
         COUNT(DISTINCT s.id) as student_count,
         SUM(CASE WHEN s.mumayyaz = 1 THEN 1 ELSE 0 END) as mumayyaz_count
  FROM halaqaat h
  LEFT JOIN students s ON s.halaqa_id = h.id AND s.status = 'active'
  WHERE h.ustaaz_user_id = ? AND h.state = 'active'
  GROUP BY h.id
");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $halaqaat[] = $row;
}

// Total students
$totalStudents = array_sum(array_column($halaqaat, 'student_count'));
$totalMumayyizeen = array_sum(array_column($halaqaat, 'mumayyaz_count'));

// Upcoming exams
$upcomingExams = [];
$result = $conn->query("SELECT * FROM exams WHERE status = 'upcoming' AND exam_date >= CURDATE() ORDER BY exam_date ASC LIMIT 5");
while ($row = $result->fetch_assoc()) {
  $upcomingExams[] = $row;
}

include __DIR__ . '/includes/header.php';
?>

<!-- Stats Grid -->
<div class="statsGrid" style="grid-template-columns: repeat(3, 1fr);">
  <div class="statCard green">
    <div class="statIcon"><i class="bi bi-people-fill"></i></div>
    <div class="statContent">
      <div class="statLabel"><?php echo $lang === 'ur' ? 'آپ کے حلقات' : 'Your Halaqaat'; ?></div>
      <div class="statValue"><?php echo count($halaqaat); ?></div>
    </div>
  </div>
  
  <div class="statCard tan">
    <div class="statIcon"><i class="bi bi-mortarboard-fill"></i></div>
    <div class="statContent">
      <div class="statLabel"><?php echo $lang === 'ur' ? 'کل طلباء' : 'Total Students'; ?></div>
      <div class="statValue"><?php echo $totalStudents; ?></div>
    </div>
  </div>
  
  <div class="statCard">
    <div class="statIcon" style="background: rgba(255, 193, 7, 0.3);"><i class="bi bi-star-fill" style="color: #ffc107;"></i></div>
    <div class="statContent">
      <div class="statLabel"><?php echo h($tr['total_mumayyizeen']); ?></div>
      <div class="statValue"><?php echo $totalMumayyizeen; ?></div>
    </div>
  </div>
</div>

<!-- My Halaqaat -->
<div class="sectionHeader">
  <div class="sectionTitle"><?php echo $lang === 'ur' ? 'میرے حلقات' : 'My Halaqaat'; ?></div>
</div>

<div class="halaqaCards">
  <?php foreach ($halaqaat as $halaqa): ?>
  <div class="halaqaCard">
    <div class="halaqaCardHeader">
      <div>
        <div class="halaqaCardTitle"><?php echo h($halaqa['name_ur']); ?></div>
        <div class="halaqaCardSub"><?php echo h($tr[$halaqa['gender']]); ?> — <?php echo h($tr[$halaqa['session']]); ?></div>
      </div>
    </div>
    <div class="halaqaCardTags">
      <?php if ($halaqa['mumayyaz_count'] > 0): ?>
      <span class="tag green">
        <i class="bi bi-star-fill"></i>
        <?php echo $halaqa['mumayyaz_count']; ?> <?php echo h($tr['mumayyaz']); ?>
      </span>
      <?php endif; ?>
    </div>
    <div class="halaqaCardFooter">
      <span><i class="bi bi-people me-1"></i> <?php echo $halaqa['student_count']; ?> <?php echo h($tr['students']); ?></span>
      <div class="d-grid gap-2 d-md-flex">
        <a href="attendance.php?halaqa_id=<?php echo $halaqa['id']; ?>" class="btn btnPrimary" style="padding: 4px 10px; font-size: 11px;">
          <i class="bi bi-calendar-check"></i> <?php echo h($tr['attendance']); ?>
        </a>
        <a href="students_progress.php?halaqa_id=<?php echo $halaqa['id']; ?>" class="btn btnSecondary" style="padding: 4px 10px; font-size: 11px;">
          <i class="bi bi-graph-up"></i> <?php echo h($tr['progress']); ?>
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if (empty($halaqaat)): ?>
<div class="alert alert-warning">
  <i class="bi bi-exclamation-triangle-fill me-2"></i>
  <?php echo $lang === 'ur' ? 'آپ کو ابھی تک کوئی حلقہ تفویض نہیں کیا گیا۔' : 'No halaqaat have been assigned to you yet.'; ?>
</div>
<?php endif; ?>

<!-- Upcoming Exams -->
<?php if (!empty($upcomingExams)): ?>
<div class="sectionHeader mt-4">
  <div class="sectionTitle"><?php echo h($tr['upcoming_exams']); ?></div>
</div>

<div class="card">
  <div class="cardBody">
    <div class="examList">
      <?php foreach ($upcomingExams as $exam): 
        $examDate = new DateTime($exam['exam_date']);
      ?>
      <div class="examItem">
        <div class="examDate">
          <span class="examDateDay"><?php echo $examDate->format('d'); ?></span>
          <span><?php echo $examDate->format('M'); ?></span>
        </div>
        <div class="examInfo">
          <div class="examTitle"><?php echo h($exam['title']); ?></div>
          <div class="examMeta"><?php echo $tr['max_marks']; ?>: <?php echo $exam['max_marks']; ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="sectionHeader mt-4">
  <div class="sectionTitle"><?php echo $lang === 'ur' ? 'فوری عمل' : 'Quick Actions'; ?></div>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <a href="attendance.php" class="btn btnPrimary w-100 py-3">
      <i class="bi bi-calendar-check fs-4 d-block mb-2"></i>
      <?php echo h($tr['attendance']); ?>
    </a>
  </div>
  <div class="col-md-4">
    <a href="students_progress.php" class="btn btnSecondary w-100 py-3">
      <i class="bi bi-graph-up fs-4 d-block mb-2"></i>
      <?php echo h($tr['progress']); ?>
    </a>
  </div>
  <div class="col-md-4">
    <a href="reports_overview.php" class="btn w-100 py-3" style="background: #e9ecef; color: #333;">
      <i class="bi bi-bar-chart-fill fs-4 d-block mb-2"></i>
      <?php echo h($tr['nav_reports']); ?>
    </a>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
