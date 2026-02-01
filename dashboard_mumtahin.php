<?php
require_once 'includes/header.php';
requireRole('mumtahin');

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

// Total students and halaqaat for context
$totalStudents = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'")->fetch_assoc()['count'];
$totalHalaqaat = $conn->query("SELECT COUNT(*) as count FROM halaqaat WHERE status = 'active'")->fetch_assoc()['count'];
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="page-title"><?php echo t('dashboard'); ?></h2>
        <p class="greeting"><?php echo t('assalam_alaikum'); ?></p>
    </div>
    
    <!-- Welcome Message -->
    <div class="alert alert-info mb-4">
        <i class="bi bi-person-badge-fill me-2"></i>
        <?php echo $isRTL ? 'خوش آمدید، ' : 'Welcome, '; ?><strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
        <?php echo $isRTL ? '! آپ ممتحن کے ڈیش بورڈ پر ہیں۔' : '! You are on the Mumtahin Dashboard.'; ?>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div class="stat-number"><?php echo $examsSummary['upcoming']; ?></div>
                <div class="stat-label"><?php echo $isRTL ? 'آنے والے امتحانات' : 'Upcoming Exams'; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon warning" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <div class="stat-number"><?php echo $examsSummary['ongoing']; ?></div>
                <div class="stat-label"><?php echo $isRTL ? 'جاری امتحانات' : 'Ongoing Exams'; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-number"><?php echo $examsSummary['finalized']; ?></div>
                <div class="stat-label"><?php echo $isRTL ? 'حتمی امتحانات' : 'Finalized Exams'; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon secondary">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div class="stat-number"><?php echo $totalStudents; ?></div>
                <div class="stat-label"><?php echo t('total_students'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Pending Finalization Alert -->
    <?php if (!empty($pendingFinalization)): ?>
    <div class="alert alert-warning mb-4">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong><?php echo $isRTL ? 'توجہ!' : 'Attention!'; ?></strong>
        <?php echo $isRTL ? count($pendingFinalization) . ' امتحانات حتمی شکل کے منتظر ہیں۔' : count($pendingFinalization) . ' exams are pending finalization.'; ?>
    </div>
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <h5 class="mb-3"><i class="bi bi-lightning-fill me-2"></i><?php echo $isRTL ? 'فوری عمل' : 'Quick Actions'; ?></h5>
        </div>
        <div class="col-md-4">
            <a href="exam_marks_entry.php" class="btn btn-outline-success w-100 py-4">
                <i class="bi bi-pencil-square fs-2 d-block mb-2"></i>
                <strong><?php echo t('marks_entry'); ?></strong>
                <small class="d-block text-muted"><?php echo $isRTL ? 'نمبر درج کریں' : 'Enter exam marks'; ?></small>
            </a>
        </div>
        <div class="col-md-4">
            <a href="exam_finalize.php" class="btn btn-outline-warning w-100 py-4">
                <i class="bi bi-lock-fill fs-2 d-block mb-2"></i>
                <strong><?php echo t('finalize'); ?></strong>
                <small class="d-block text-muted"><?php echo $isRTL ? 'امتحانات حتمی کریں' : 'Finalize exam results'; ?></small>
            </a>
        </div>
        <div class="col-md-4">
            <a href="exams_manage.php" class="btn btn-outline-primary w-100 py-4">
                <i class="bi bi-file-text-fill fs-2 d-block mb-2"></i>
                <strong><?php echo t('exams'); ?></strong>
                <small class="d-block text-muted"><?php echo $isRTL ? 'تمام امتحانات' : 'View all exams'; ?></small>
            </a>
        </div>
    </div>
    
    <!-- Recent Exams -->
    <div class="row">
        <div class="col-12">
            <h5 class="mb-3"><i class="bi bi-clock-history me-2"></i><?php echo $isRTL ? 'حالیہ امتحانات' : 'Recent Exams'; ?></h5>
        </div>
        <?php foreach ($recentExams as $exam): 
            $statusClass = $exam['status'] === 'finalized' ? 'success' : ($exam['status'] === 'ongoing' ? 'warning' : 'info');
        ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h6 class="card-title mb-0"><?php echo htmlspecialchars($exam['title']); ?></h6>
                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($exam['status']); ?></span>
                    </div>
                    <p class="mb-1 text-muted">
                        <i class="bi bi-calendar-event me-2"></i>
                        <?php echo date('M d, Y', strtotime($exam['exam_date'])); ?>
                    </p>
                    <p class="mb-2 text-muted">
                        <i class="bi bi-award me-2"></i>
                        <?php echo $exam['max_marks']; ?> <?php echo $isRTL ? 'کل نمبر' : 'Max Marks'; ?>
                    </p>
                    
                    <?php if ($exam['status'] === 'finalized' && $exam['avg_percentage']): ?>
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted"><?php echo $isRTL ? 'اوسط کارکردگی:' : 'Average Performance:'; ?></small>
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1 me-2">
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-<?php echo $exam['avg_percentage'] >= 60 ? 'success' : 'warning'; ?>" 
                                         style="width: <?php echo $exam['avg_percentage']; ?>%"></div>
                                </div>
                            </div>
                            <span class="fw-bold"><?php echo round($exam['avg_percentage'], 1); ?>%</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-3 d-flex gap-2">
                        <?php if ($exam['status'] !== 'finalized'): ?>
                        <a href="exam_marks_entry.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-success flex-fill">
                            <i class="bi bi-pencil-square me-1"></i> <?php echo t('marks_entry'); ?>
                        </a>
                        <?php endif; ?>
                        <?php if ($exam['status'] === 'ongoing'): ?>
                        <a href="exam_finalize.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-warning flex-fill">
                            <i class="bi bi-lock me-1"></i> <?php echo t('finalize'); ?>
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
        <p class="mt-3 text-muted"><?php echo t('no_data_found'); ?></p>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
