<?php
require_once 'includes/header.php';
requireRole('ustaaz');

$teacherId = $_SESSION['user_id'];

// Fetch teacher's halaqaat
$halaqaat = [];
$stmt = $conn->prepare("
    SELECT h.*, 
           COUNT(DISTINCT s.id) as student_count,
           SUM(CASE WHEN s.is_mumayyiz = 1 THEN 1 ELSE 0 END) as mumayyiz_count
    FROM halaqaat h
    LEFT JOIN students s ON s.halaqa_id = h.id AND s.status = 'active'
    WHERE h.teacher_id = ? AND h.status = 'active'
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
$totalMumayyizeen = array_sum(array_column($halaqaat, 'mumayyiz_count'));

// Recent attendance (last 7 days)
$recentAttendance = [];
$stmt = $conn->prepare("
    SELECT a.*, s.name as student_name, h.name as halaqa_name
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN halaqaat h ON s.halaqa_id = h.id
    WHERE h.teacher_id = ? AND a.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY a.date DESC
    LIMIT 10
");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recentAttendance[] = $row;
}

// Upcoming exams
$upcomingExams = [];
$result = $conn->query("
    SELECT * FROM exams 
    WHERE exam_date >= CURDATE() AND status != 'finalized'
    ORDER BY exam_date ASC
    LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    $upcomingExams[] = $row;
}
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="page-title"><?php echo t('dashboard'); ?></h2>
        <p class="greeting"><?php echo t('assalam_alaikum'); ?></p>
    </div>
    
    <!-- Welcome Message -->
    <div class="alert alert-info mb-4">
        <i class="bi bi-person-circle me-2"></i>
        <?php echo $isRTL ? 'خوش آمدید، ' : 'Welcome, '; ?><strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
        <?php echo $isRTL ? '! آپ کے حلقات کی تفصیلات ذیل میں ہیں۔' : '! Here are your halaqaat details.'; ?>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-number"><?php echo count($halaqaat); ?></div>
                <div class="stat-label"><?php echo $isRTL ? 'آپ کے حلقات' : 'Your Halaqaat'; ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon secondary">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div class="stat-number"><?php echo $totalStudents; ?></div>
                <div class="stat-label"><?php echo $isRTL ? 'کل طلباء' : 'Total Students'; ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div class="stat-number"><?php echo $totalMumayyizeen; ?></div>
                <div class="stat-label"><?php echo t('total_mumayyizeen'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- My Halaqaat -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <h5 class="mb-3"><i class="bi bi-people-fill me-2"></i><?php echo $isRTL ? 'میرے حلقات' : 'My Halaqaat'; ?></h5>
        </div>
        <?php foreach ($halaqaat as $halaqa): ?>
        <div class="col-md-6 col-lg-4">
            <div class="halaqa-card">
                <h5 class="halaqa-name"><?php echo htmlspecialchars($halaqa['name']); ?></h5>
                <div class="mb-3">
                    <span class="tag tag-gender-<?php echo $halaqa['gender']; ?>">
                        <?php echo t($halaqa['gender']); ?>
                    </span>
                    <span class="tag tag-time">
                        <?php echo t($halaqa['time_slot']); ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="tag tag-count">
                        <i class="bi bi-people"></i> <?php echo $halaqa['student_count']; ?> <?php echo t('students'); ?>
                    </span>
                    <?php if ($halaqa['mumayyiz_count'] > 0): ?>
                    <span class="tag tag-mumayyiz">
                        <i class="bi bi-star-fill"></i> <?php echo $halaqa['mumayyiz_count']; ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="mt-3 d-grid gap-2">
                    <a href="attendance.php?halaqa_id=<?php echo $halaqa['id']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-calendar-check me-2"></i><?php echo t('attendance'); ?>
                    </a>
                    <a href="students_progress.php?halaqa_id=<?php echo $halaqa['id']; ?>" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-graph-up me-2"></i><?php echo t('progress'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($halaqaat)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php echo $isRTL ? 'آپ کو ابھی تک کوئی حلقہ تفویض نہیں کیا گیا۔' : 'No halaqaat have been assigned to you yet.'; ?>
    </div>
    <?php endif; ?>
    
    <!-- Upcoming Exams -->
    <?php if (!empty($upcomingExams)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3"><i class="bi bi-calendar-event me-2"></i><?php echo $isRTL ? 'آنے والے امتحانات' : 'Upcoming Exams'; ?></h5>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><?php echo $isRTL ? 'امتحان' : 'Exam'; ?></th>
                                    <th><?php echo t('date'); ?></th>
                                    <th><?php echo $isRTL ? 'کل نمبر' : 'Max Marks'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingExams as $exam): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($exam['exam_date'])); ?></td>
                                    <td><?php echo $exam['max_marks']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <h5 class="mb-3"><i class="bi bi-lightning-fill me-2"></i><?php echo $isRTL ? 'فوری عمل' : 'Quick Actions'; ?></h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="attendance.php" class="btn btn-outline-primary w-100 py-3">
                        <i class="bi bi-calendar-check fs-4 d-block mb-2"></i>
                        <?php echo t('attendance'); ?>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="students_progress.php" class="btn btn-outline-success w-100 py-3">
                        <i class="bi bi-graph-up fs-4 d-block mb-2"></i>
                        <?php echo t('progress'); ?>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="reports_overview.php" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-bar-chart-fill fs-4 d-block mb-2"></i>
                        <?php echo t('reports'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
