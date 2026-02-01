<?php
require_once 'includes/header.php';
requireLogin();

// Fetch summary data for reports
$halaqaatCount = $conn->query("SELECT COUNT(*) as count FROM halaqaat WHERE status = 'active'")->fetch_assoc()['count'];
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
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="page-title"><?php echo t('reports'); ?></h2>
        <p class="greeting"><?php echo t('assalam_alaikum'); ?></p>
    </div>
    
    <!-- Summary Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-icon primary mx-auto">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-number"><?php echo $halaqaatCount; ?></div>
                <div class="stat-label"><?php echo t('halaqaat'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-icon secondary mx-auto">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div class="stat-number"><?php echo $studentsCount; ?></div>
                <div class="stat-label"><?php echo t('students'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-icon success mx-auto">
                    <i class="bi bi-file-text-fill"></i>
                </div>
                <div class="stat-number"><?php echo $examsCount; ?></div>
                <div class="stat-label"><?php echo t('exams'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-icon primary mx-auto" style="background: rgba(233, 30, 99, 0.1); color: #e91e63;">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div class="stat-number"><?php echo $teachersCount; ?></div>
                <div class="stat-label"><?php echo t('teachers'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Report Links -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-building me-2"></i><?php echo $isRTL ? 'حلقہ کی رپورٹس' : 'Halaqa Reports'; ?></h5>
                </div>
                <div class="card-body">
                    <p class="text-muted"><?php echo $isRTL ? 'ہر حلقہ کی تفصیلی رپورٹ دیکھیں۔' : 'View detailed reports for each halaqa.'; ?></p>
                    <a href="halaqaat_list.php" class="btn btn-primary-custom">
                        <i class="bi bi-eye me-2"></i><?php echo $isRTL ? 'حلقات دیکھیں' : 'View Halaqaat'; ?>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-person-fill me-2"></i><?php echo $isRTL ? 'طالب علم کی رپورٹس' : 'Student Reports'; ?></h5>
                </div>
                <div class="card-body">
                    <p class="text-muted"><?php echo $isRTL ? 'ہر طالب علم کی تفصیلی رپورٹ دیکھیں۔' : 'View detailed reports for each student.'; ?></p>
                    <a href="students_manage.php" class="btn btn-secondary-custom">
                        <i class="bi bi-eye me-2"></i><?php echo $isRTL ? 'طلباء دیکھیں' : 'View Students'; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Exams -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i><?php echo $isRTL ? 'حالیہ حتمی امتحانات' : 'Recent Finalized Exams'; ?></h5>
            <a href="exams_manage.php" class="btn btn-sm btn-outline-primary"><?php echo $isRTL ? 'سب دیکھیں' : 'View All'; ?></a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?php echo $isRTL ? 'امتحان' : 'Exam'; ?></th>
                            <th><?php echo t('date'); ?></th>
                            <th><?php echo $isRTL ? 'نتائج' : 'Results'; ?></th>
                            <th><?php echo $isRTL ? 'اوسط فیصد' : 'Avg %'; ?></th>
                            <th><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentExams as $exam): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($exam['title']); ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($exam['exam_date'])); ?></td>
                            <td><?php echo $exam['results_count']; ?> <?php echo $isRTL ? 'طلباء' : 'students'; ?></td>
                            <td>
                                <span class="badge bg-<?php echo ($exam['avg_percentage'] ?? 0) >= 60 ? 'success' : (($exam['avg_percentage'] ?? 0) >= 40 ? 'warning text-dark' : 'danger'); ?>">
                                    <?php echo round($exam['avg_percentage'] ?? 0, 1); ?>%
                                </span>
                            </td>
                            <td>
                                <a href="export.php?type=exam&exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-download"></i> <?php echo t('export'); ?>
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
        <p class="mt-3 text-muted"><?php echo $isRTL ? 'کوئی حتمی امتحان نہیں ملا۔' : 'No finalized exams found.'; ?></p>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
