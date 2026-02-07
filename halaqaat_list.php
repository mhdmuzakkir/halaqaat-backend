<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$lang = get_language();
$tr = get_translations($lang);

// Get all halaqaat
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

// Get counts
$totalHalaqaat = count($halaqaat);
$totalStudents = array_sum(array_column($halaqaat, 'student_count'));

include __DIR__ . '/includes/header.php';
?>

<!-- Stats -->
<div class="statsGrid" style="grid-template-columns: repeat(2, 1fr);">
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
</div>

<!-- Filters -->
<div class="filtersRow">
  <div class="searchWrap" style="flex: 1; max-width: 400px;">
    <i class="bi bi-search"></i>
    <input type="text" id="halaqaSearch" placeholder="<?php echo h($tr['search']); ?>" />
  </div>
  <a href="halaqaat_manage.php" class="pill secondary">
    <i class="bi bi-plus-lg"></i>
    <?php echo h($tr['add_new']); ?>
  </a>
</div>

<!-- Halaqa Cards -->
<div class="halaqaCards" id="halaqaContainer">
  <?php foreach ($halaqaat as $halaqa): ?>
  <div class="halaqaCard halaqa-item">
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
        <i class="bi bi-<?php echo $halaqa['gender'] === 'baneen' ? 'gender-male' : 'gender-female'; ?>"></i>
        <?php echo h($tr[$halaqa['gender']]); ?>
      </span>
      <span class="tag orange">
        <i class="bi bi-clock"></i>
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
      <div>
        <a href="halaqaat_manage.php?id=<?php echo $halaqa['id']; ?>" class="pill" style="padding: 4px 10px; font-size: 11px;">
          <i class="bi bi-pencil"></i>
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<script>
setupSearch('halaqaSearch', '#halaqaContainer', '.halaqa-item', ['.halaqaCardTitle', '.halaqaCardSub']);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
