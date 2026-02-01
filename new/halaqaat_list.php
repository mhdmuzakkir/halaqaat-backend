<?php
require_once 'includes/header.php';
requireLogin();

// Fetch halaqaat with teacher info and student counts
$halaqaat = [];
$query = "
    SELECT h.*, u.name as teacher_name,
           COUNT(DISTINCT s.id) as student_count,
           SUM(CASE WHEN s.is_mumayyiz = 1 THEN 1 ELSE 0 END) as mumayyiz_count
    FROM halaqaat h
    LEFT JOIN users u ON h.teacher_id = u.id
    LEFT JOIN students s ON s.halaqa_id = h.id AND s.status = 'active'
    WHERE h.status = 'active'
    GROUP BY h.id
    ORDER BY h.gender, h.time_slot, h.name
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $halaqaat[] = $row;
}

// Calculate totals
$totalHalaqaat = count($halaqaat);
$totalStudents = array_sum(array_column($halaqaat, 'student_count'));
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title"><?php echo t('halaqaat'); ?></h2>
            <p class="greeting"><?php echo t('assalam_alaikum'); ?></p>
        </div>
        <?php if (hasRole('admin')): ?>
        <a href="halaqaat_manage.php" class="btn btn-primary-custom">
            <i class="bi bi-plus-lg me-2"></i><?php echo t('add_new'); ?>
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Summary Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body d-flex align-items-center">
                    <i class="bi bi-people-fill fs-1 me-3"></i>
                    <div>
                        <h3 class="mb-0"><?php echo $totalHalaqaat; ?></h3>
                        <small><?php echo t('total_halaqaat'); ?></small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-white" style="background-color: var(--secondary-color);">
                <div class="card-body d-flex align-items-center">
                    <i class="bi bi-mortarboard-fill fs-1 me-3"></i>
                    <div>
                        <h3 class="mb-0"><?php echo $totalStudents; ?></h3>
                        <small><?php echo t('total_students'); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control search-box" id="halaqaSearch" placeholder="<?php echo $isRTL ? 'حلقات تلاش کریں...' : 'Search halaqaat...'; ?>">
            </div>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="filter-btn" onclick="filterHalaqaat('all')"><?php echo $isRTL ? 'سب' : 'All'; ?></button>
            <button class="filter-btn" onclick="filterHalaqaat('baneen')"><?php echo t('baneen'); ?></button>
            <button class="filter-btn" onclick="filterHalaqaat('banaat')"><?php echo t('banaat'); ?></button>
        </div>
    </div>
    
    <!-- Halaqaat Cards -->
    <div class="row g-4" id="halaqaatContainer">
        <?php foreach ($halaqaat as $halaqa): ?>
        <div class="col-md-6 col-lg-4 halaqa-item" data-gender="<?php echo $halaqa['gender']; ?>">
            <div class="halaqa-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="halaqa-name mb-0"><?php echo htmlspecialchars($halaqa['name']); ?></h5>
                    <?php if (hasRole('admin')): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-link text-dark" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="halaqaat_manage.php?id=<?php echo $halaqa['id']; ?>"><i class="bi bi-pencil me-2"></i><?php echo t('edit'); ?></a></li>
                            <li><a class="dropdown-item text-danger" href="halaqaat_delete.php?id=<?php echo $halaqa['id']; ?>" onclick="return confirmDelete()"><i class="bi bi-trash me-2"></i><?php echo t('delete'); ?></a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                
                <p class="halaqa-teacher">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo htmlspecialchars($halaqa['teacher_name'] ?: ($isRTL ? 'کوئی استاد تفویض نہیں' : 'No teacher assigned')); ?>
                </p>
                
                <div class="mb-3">
                    <span class="tag tag-gender-<?php echo $halaqa['gender']; ?>">
                        <i class="bi bi-<?php echo $halaqa['gender'] === 'baneen' ? 'gender-male' : 'gender-female'; ?>"></i>
                        <?php echo t($halaqa['gender']); ?>
                    </span>
                    <span class="tag tag-time">
                        <i class="bi bi-clock"></i>
                        <?php echo t($halaqa['time_slot']); ?>
                    </span>
                </div>
                
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <div>
                        <span class="tag tag-count">
                            <i class="bi bi-people"></i>
                            <?php echo $halaqa['student_count']; ?> <?php echo t('students'); ?>
                        </span>
                    </div>
                    <?php if ($halaqa['mumayyiz_count'] > 0): ?>
                    <div>
                        <span class="tag tag-mumayyiz">
                            <i class="bi bi-star-fill"></i>
                            <?php echo $halaqa['mumayyiz_count']; ?> <?php echo t('mumayyiz'); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="mt-3">
                    <a href="report_halaqa.php?id=<?php echo $halaqa['id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-eye me-2"></i><?php echo $isRTL ? 'رپورٹ دیکھیں' : 'View Report'; ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($halaqaat)): ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-3 text-muted"><?php echo t('no_data_found'); ?></p>
    </div>
    <?php endif; ?>
</div>

<script>
// Setup search
setupSearch('halaqaSearch', '#halaqaatContainer', '.halaqa-item', ['.halaqa-name', '.halaqa-teacher']);

// Filter halaqaat by gender
function filterHalaqaat(gender) {
    const items = document.querySelectorAll('.halaqa-item');
    items.forEach(item => {
        if (gender === 'all' || item.dataset.gender === gender) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
