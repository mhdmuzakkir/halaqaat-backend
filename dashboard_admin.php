<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
require_role('admin');

$lang = get_language();
$tr = get_translations($lang);

// Get statistics
$totalHalaqaat = scalar_int($conn, "SELECT COUNT(*) FROM halaqaat WHERE state = 'active'");
$totalStudents = scalar_int($conn, "SELECT COUNT(*) FROM students WHERE status = 'active'");
$totalMumayyizeen = scalar_int($conn, "SELECT COUNT(*) FROM students WHERE status = 'active' AND mumayyaz = 1");

// Students by gender
$boysCount = scalar_int($conn, "SELECT COUNT(*) FROM students WHERE status = 'active' AND gender = 'baneen'");
$girlsCount = scalar_int($conn, "SELECT COUNT(*) FROM students WHERE status = 'active' AND gender = 'banaat'");

// Students by shuba
$shubaStats = [];
$result = $conn->query("SELECT shuba, COUNT(*) as count FROM students WHERE status = 'active' GROUP BY shuba");
while ($row = $result->fetch_assoc()) {
  $shubaStats[$row['shuba']] = $row['count'];
}

// Upcoming exams
$upcomingExams = [];
$result = $conn->query("SELECT * FROM exams WHERE status = 'upcoming' AND exam_date >= CURDATE() ORDER BY exam_date ASC LIMIT 5");
while ($row = $result->fetch_assoc()) {
  $upcomingExams[] = $row;
}

// Get halaqaat with student counts
$halaqaat = [];
$result = $conn->query("
  SELECT h.*, u.full_name as ustaaz_name,
         COUNT(DISTINCT s.id) as student_count,
         SUM(CASE WHEN s.mumayyaz = 1 THEN 1 ELSE 0 END) as mumayyaz_count
  FROM halaqaat h
  LEFT JOIN users u ON h.ustaaz_user_id = u.id
  LEFT JOIN students s ON s.halaqa_id = h.id AND s.status = 'active'
  WHERE h.state = 'active'
  GROUP BY h.id
  ORDER BY h.gender, h.session, h.name_ur
");
while ($row = $result->fetch_assoc()) {
  $halaqaat[] = $row;
}

// Top performing halaqa (girls)
$topGirlsHalaqa = null;
$result = $conn->query("
  SELECT h.name_ur, AVG(er.percentage) as avg_percentage
  FROM halaqaat h
  JOIN students s ON s.halaqa_id = h.id AND s.status = 'active'
  JOIN exam_results er ON er.student_id = s.id AND er.status = 'finalized'
  WHERE h.gender = 'banaat' AND h.state = 'active'
  GROUP BY h.id
  ORDER BY avg_percentage DESC
  LIMIT 1
");
if ($result && $result->num_rows > 0) {
  $topGirlsHalaqa = $result->fetch_assoc();
}

// Top performing halaqa (boys - subah)
$topBoysSubahHalaqa = null;
$result = $conn->query("
  SELECT h.name_ur, AVG(er.percentage) as avg_percentage
  FROM halaqaat h
  JOIN students s ON s.halaqa_id = h.id AND s.status = 'active'
  JOIN exam_results er ON er.student_id = s.id AND er.status = 'finalized'
  WHERE h.gender = 'baneen' AND h.session = 'subah' AND h.state = 'active'
  GROUP BY h.id
  ORDER BY avg_percentage DESC
  LIMIT 1
");
if ($result && $result->num_rows > 0) {
  $topBoysSubahHalaqa = $result->fetch_assoc();
}

// Top performing halaqa (boys - asr)
$topBoysAsrHalaqa = null;
$result = $conn->query("
  SELECT h.name_ur, AVG(er.percentage) as avg_percentage
  FROM halaqaat h
  JOIN students s ON s.halaqa_id = h.id AND s.status = 'active'
  JOIN exam_results er ON er.student_id = s.id AND er.status = 'finalized'
  WHERE h.gender = 'baneen' AND h.session = 'asr' AND h.state = 'active'
  GROUP BY h.id
  ORDER BY avg_percentage DESC
  LIMIT 1
");
if ($result && $result->num_rows > 0) {
  $topBoysAsrHalaqa = $result->fetch_assoc();
}

// Mumtaaz Talaba (top student)
$topStudent = null;
$result = $conn->query("
  SELECT s.full_name_ur, h.name_ur as halaqa_name, AVG(er.percentage) as avg_percentage
  FROM students s
  JOIN halaqaat h ON s.halaqa_id = h.id
  JOIN exam_results er ON er.student_id = s.id AND er.status = 'finalized'
  WHERE s.status = 'active'
  GROUP BY s.id
  ORDER BY avg_percentage DESC
  LIMIT 1
");
if ($result && $result->num_rows > 0) {
  $topStudent = $result->fetch_assoc();
}

include __DIR__ . '/includes/header.php';
?>

<!-- Stats Grid -->
<div class="statsGrid">
  <div class="statCard green">
    <div class="statIcon"><i class="bi bi-people-fill"></i></div>
    <div class="statContent">
      <div class="statLabel"><?php echo h($tr['total_halaqaat']); ?></div>
      <div class="statValue"><?php echo $totalHalaqaat; ?></div>
    </div>
  </div>
  
  <div class="statCard tan">
    <div class="statIcon"><i class="bi bi-mortarboard-fill"></i></div>
    <div class="statContent">
      <div class="statLabel"><?php echo h($tr['total_students']); ?></div>
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
  
  <div class="statCard">
    <div class="statIcon" style="background: rgba(23, 162, 184, 0.2);"><i class="bi bi-calendar-event" style="color: #17a2b8;"></i></div>
    <div class="statContent">
      <div class="statLabel"><?php echo h($tr['upcoming_exams']); ?></div>
      <div class="statValue"><?php echo count($upcomingExams); ?></div>
    </div>
  </div>
</div>

<!-- Main Cards Grid -->
<div class="cardsGrid">
  <!-- Upcoming Exams -->
  <div class="card">
    <div class="cardHeader">
      <span><?php echo h($tr['upcoming_exams']); ?></span>
      <a href="exams_manage.php" class="pill"><?php echo h($tr['view_all']); ?></a>
    </div>
    <div class="cardBody">
      <?php if (empty($upcomingExams)): ?>
        <p class="text-muted"><?php echo h($tr['no_data']); ?></p>
      <?php else: ?>
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
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Students by Shuba -->
  <div class="card">
    <div class="cardHeader"><?php echo h($tr['students_by_shuba']); ?></div>
    <div class="cardBody">
      <?php if (empty($shubaStats)): ?>
        <p class="text-muted"><?php echo h($tr['no_data']); ?></p>
      <?php else: 
        $total = array_sum($shubaStats);
        foreach ($shubaStats as $shuba => $count): 
          $percentage = $total > 0 ? round(($count / $total) * 100) : 0;
      ?>
      <div class="progressItem">
        <div class="progressLabel"><?php echo h($tr[$shuba]); ?></div>
        <div class="progressBar">
          <div class="progressFill <?php echo $shuba === 'qaida' ? 'tan' : 'green'; ?>" style="width: <?php echo $percentage; ?>%"></div>
        </div>
        <div class="progressValue"><?php echo $count; ?></div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
  
  <!-- Students by Gender -->
  <div class="card">
    <div class="cardHeader"><?php echo h($tr['students_by_gender']); ?></div>
    <div class="cardBody">
      <div class="genderStats">
        <div class="genderStat">
          <div class="genderValue"><?php echo $boysCount; ?></div>
          <div class="genderLabel"><?php echo h($tr['boys']); ?></div>
        </div>
        <div class="genderDivider"></div>
        <div class="genderStat">
          <div class="genderValue girl"><?php echo $girlsCount; ?></div>
          <div class="genderLabel"><?php echo h($tr['girls']); ?></div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Top Halaqa (Girls) -->
  <div class="card">
    <div class="cardHeader"><?php echo h($tr['top_halaqa_girls']); ?></div>
    <div class="cardBody">
      <?php if ($topGirlsHalaqa): ?>
        <div class="text-center">
          <div style="font-size: 24px; font-weight: 900; color: var(--girl);">
            <?php echo round($topGirlsHalaqa['avg_percentage'], 1); ?>%
          </div>
          <div style="font-size: 14px; margin-top: 8px;">
            <?php echo h($topGirlsHalaqa['name_ur']); ?>
          </div>
        </div>
      <?php else: ?>
        <p class="text-muted"><?php echo h($tr['no_data']); ?></p>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Top Halaqa (Boys - Subah) -->
  <div class="card">
    <div class="cardHeader"><?php echo h($tr['top_halaqa_boys_subah']); ?></div>
    <div class="cardBody">
      <?php if ($topBoysSubahHalaqa): ?>
        <div class="text-center">
          <div style="font-size: 24px; font-weight: 900; color: var(--boy);">
            <?php echo round($topBoysSubahHalaqa['avg_percentage'], 1); ?>%
          </div>
          <div style="font-size: 14px; margin-top: 8px;">
            <?php echo h($topBoysSubahHalaqa['name_ur']); ?>
          </div>
        </div>
      <?php else: ?>
        <p class="text-muted"><?php echo h($tr['no_data']); ?></p>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Top Halaqa (Boys - Asr) -->
  <div class="card">
    <div class="cardHeader"><?php echo h($tr['top_halaqa_boys_asr']); ?></div>
    <div class="cardBody">
      <?php if ($topBoysAsrHalaqa): ?>
        <div class="text-center">
          <div style="font-size: 24px; font-weight: 900; color: var(--boy);">
            <?php echo round($topBoysAsrHalaqa['avg_percentage'], 1); ?>%
          </div>
          <div style="font-size: 14px; margin-top: 8px;">
            <?php echo h($topBoysAsrHalaqa['name_ur']); ?>
          </div>
        </div>
      <?php else: ?>
        <p class="text-muted"><?php echo h($tr['no_data']); ?></p>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Mumtaaz Talaba -->
  <div class="card" style="grid-column: span 2;">
    <div class="cardHeader">
      <span><i class="bi bi-trophy-fill" style="color: #ffc107; margin-right: 8px;"></i><?php echo h($tr['mumtaaz_talaba']); ?></span>
    </div>
    <div class="cardBody">
      <?php if ($topStudent): ?>
        <div class="text-center">
          <div style="font-size: 32px; font-weight: 900; color: var(--primary);">
            <?php echo round($topStudent['avg_percentage'], 1); ?>%
          </div>
          <div style="font-size: 18px; font-weight: 700; margin-top: 8px;">
            <?php echo h($topStudent['full_name_ur']); ?>
          </div>
          <div style="font-size: 14px; color: #666; margin-top: 4px;">
            <?php echo h($topStudent['halaqa_name']); ?>
          </div>
        </div>
      <?php else: ?>
        <p class="text-muted"><?php echo h($tr['no_data']); ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Halaqaat List -->
<div class="sectionHeader">
  <div class="sectionTitle"><?php echo h($tr['nav_halaqaat']); ?></div>
  <a href="halaqaat_list.php" class="pill"><?php echo h($tr['view_all']); ?></a>
</div>

<div class="halaqaCards">
  <?php foreach (array_slice($halaqaat, 0, 4) as $halaqa): ?>
  <div class="halaqaCard">
    <div class="halaqaCardHeader">
      <div>
        <div class="halaqaCardTitle"><?php echo h($halaqa['name_ur']); ?></div>
        <div class="halaqaCardSub">
          <?php echo h($halaqa['ustaaz_name'] ?: ($lang === 'ur' ? 'کوئی استاد تفویض نہیں' : 'No teacher assigned')); ?>
        </div>
      </div>
      <a href="report_halaqa.php?id=<?php echo $halaqa['id']; ?>" class="halaqaCardArrow">
        <i class="bi bi-chevron-right"></i>
      </a>
    </div>
    <div class="halaqaCardTags">
      <span class="tag <?php echo $halaqa['gender'] === 'baneen' ? 'blue' : 'pink'; ?>">
        <?php echo h($tr[$halaqa['gender']]); ?>
      </span>
      <span class="tag orange">
        <?php echo h($tr[$halaqa['session']]); ?>
      </span>
      <?php if ($halaqa['mumayyaz_count'] > 0): ?>
      <span class="tag green">
        <i class="bi bi-star-fill"></i>
        <?php echo $halaqa['mumayyaz_count']; ?> <?php echo h($tr['mumayyaz']); ?>
      </span>
      <?php endif; ?>
    </div>
    <div class="halaqaCardFooter">
      <span><i class="bi bi-people me-1"></i> <?php echo $halaqa['student_count']; ?> <?php echo h($tr['students']); ?></span>
      <span class="tag tan"><?php echo h($tr[$halaqa['state']]); ?></span>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
