<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$lang = get_language();
$tr = get_translations($lang);

// Fetch summary data for reports
$halaqaatCount = $conn->query("SELECT COUNT(*) as count FROM halaqaat WHERE state = 'active'")->fetch_assoc()['count'];
$studentsCount = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'")->fetch_assoc()['count'];
$examsCount = $conn->query("SELECT COUNT(*) as count FROM exams")->fetch_assoc()['count'];
$teachersCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('ustaaz', 'ustadah') AND status = 'active'")->fetch_assoc()['count'];

// Recent finalized exams
$recentExams = [];
$result = $conn->query("
  SELECT e.*, 
         COUNT(er.id) as results_count,
         AVG(er.percentage) as avg_percentage
  FROM exams e
  LEFT JOIN exam_results er ON er.exam_id = e.id AND er.status = 'finalized'
  WHERE e.status = 'finalized'
  GROUP BY e.id
  ORDER BY e.exam_date DESC
  LIMIT 5
");
while ($row = $result->fetch_assoc()) {
  $recentExams[] = $row;
}

include __DIR__ . '/includes/header.php';
?>

<!-- Stats Grid -->
<div class="statsGrid" style="grid-template-columns: repeat(4, 1fr);">
  <div class="statCard text-center">
    <div class="statIcon primary mx-auto"><i class="bi bi-people-fill"></i></div>
    <div class="statValue"><?php echo $halaqaatCount; ?></div>
    <div class="statLabel"><?php echo h($tr['nav_halaqaat']); ?></div>
  </div>
  <div class="statCard text-center">
    <div class="statIcon secondary mx-auto"><i class="bi bi-mortarboard-fill"></i></div>
    <div class="statValue"><?php echo $studentsCount; ?></div>
    <div class="statLabel"><?php echo h($tr['students']); ?></div>
  </div>
  <div class="statCard text-center">
    <div class="statIcon success mx-auto"><i class="bi bi-file-text-fill"></i></div>
    <div class="statValue"><?php echo $examsCount; ?></div>
    <div class="statLabel"><?php echo h($tr['nav_exams']); ?></div>
  </div>
  <div class="statCard text-center">
    <div class="statIcon mx-auto" style="background: rgba(233, 30, 99, 0.1);"><i class="bi bi-person-badge-fill" style="color: #e91e63;"></i></div>
    <div class="statValue"><?php echo $teachersCount; ?></div>
    <div class="statLabel"><?php echo h($tr['nav_ustaaz']); ?></div>
  </div>
</div>

<!-- Report Links -->
<div class="row g-4 mb-4">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="cardHeader">
        <span><i class="bi bi-building me-2"></i><?php echo $lang === 'ur' ? 'حلقہ کی رپورٹس' : 'Halaqa Reports'; ?></span>
      </div>
      <div class="cardBody">
        <p class="text-muted"><?php echo $lang === 'ur' ? 'ہر حلقہ کی تفصیلی رپورٹ دیکھیں۔' : 'View detailed reports for each halaqa.'; ?></p>
        <a href="halaqaat_list.php" class="btn btnPrimary">
          <i class="bi bi-eye me-2"></i><?php echo $lang === 'ur' ? 'حلقات دیکھیں' : 'View Halaqaat'; ?>
        </a>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="cardHeader">
        <span><i class="bi bi-person-fill me-2"></i><?php echo $lang === 'ur' ? 'طالب علم کی رپورٹس' : 'Student Reports'; ?></span>
      </div>
      <div class="cardBody">
        <p class="text-muted"><?php echo $lang === 'ur' ? 'ہر طالب علم کی تفصیلی رپورٹ دیکھیں۔' : 'View detailed reports for each student.'; ?></p>
        <a href="students_manage.php" class="btn btnSecondary">
          <i class="bi bi-eye me-2"></i><?php echo $lang === 'ur' ? 'طلباء دیکھیں' : 'View Students'; ?>
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Recent Exams -->
<div class="card">
  <div class="cardHeader d-flex justify-content-between align-items-center">
    <span><i class="bi bi-clock-history me-2"></i><?php echo $lang === 'ur' ? 'حالیہ حتمی امتحانات' : 'Recent Finalized Exams'; ?></span>
    <a href="exams_manage.php" class="pill"><?php echo h($tr['view_all']); ?></a>
  </div>
  <div class="cardBody p-0">
    <div class="tableContainer">
      <table class="table mb-0">
        <thead>
          <tr>
            <th><?php echo $lang === 'ur' ? 'امتحان' : 'Exam'; ?></th>
            <th><?php echo h($tr['date']); ?></th>
            <th><?php echo $lang === 'ur' ? 'نتائج' : 'Results'; ?></th>
            <th><?php echo $lang === 'ur' ? 'اوسط فیصد' : 'Avg %'; ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentExams as $exam): ?>
          <tr>
            <td><strong><?php echo h($exam['title']); ?></strong></td>
            <td><?php echo date('M d, Y', strtotime($exam['exam_date'])); ?></td>
            <td><?php echo $exam['results_count']; ?> <?php echo $lang === 'ur' ? 'طلباء' : 'students'; ?></td>
            <td>
              <span class="tag <?php echo ($exam['avg_percentage'] ?? 0) >= 60 ? 'green' : (($exam['avg_percentage'] ?? 0) >= 40 ? 'orange' : 'pink'); ?>">
                <?php echo round($exam['avg_percentage'] ?? 0, 1); ?>%
              </span>
            </td>
            <td>
              <a href="export.php?type=exam&exam_id=<?php echo $exam['id']; ?>" class="pill" style="padding: 4px 10px;">
                <i class="bi bi-download"></i> <?php echo h($tr['export']); ?>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if (empty($recentExams)): ?>
<div class="text-center py-5">
  <i class="bi bi-inbox fs-1 text-muted"></i>
  <p class="mt-3 text-muted"><?php echo $lang === 'ur' ? 'کوئی حتمی امتحان نہیں ملا۔' : 'No finalized exams found.'; ?></p>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
