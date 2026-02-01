<?php
require_once 'includes/header.php';
requireLogin();

if (!isset($_GET['id'])) {
    header('Location: halaqaat_list.php');
    exit;
}

$halaqaId = intval($_GET['id']);

// Get halaqa details
$stmt = $conn->prepare("
    SELECT h.*, u.name as teacher_name 
    FROM halaqaat h 
    LEFT JOIN users u ON h.teacher_id = u.id 
    WHERE h.id = ?
");
$stmt->bind_param("i", $halaqaId);
$stmt->execute();
$halaqa = $stmt->get_result()->fetch_assoc();

if (!$halaqa) {
    header('Location: halaqaat_list.php');
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
    ORDER BY s.name
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
$mumayyizeenCount = count(array_filter($students, fn($s) => $s['is_mumayyiz']));
$avgPercentage = $totalStudents > 0 ? array_sum(array_column($students, 'avg_percentage')) / $totalStudents : 0;
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="page-title"><?php echo htmlspecialchars($halaqa['name']); ?></h2>
            <p class="greeting"><?php echo t('assalam_alaikum'); ?></p>
        </div>
        <div class="text-end">
            <a href="export.php?type=halaqa&id=<?php echo $halaqaId; ?>" class="btn btn-success mb-2">
                <i class="bi bi-download me-2"></i><?php echo t('export'); ?>
            </a>
            <br>
            <button onclick="printPage()" class="btn btn-outline-primary">
                <i class="bi bi-printer me-2"></i><?php echo t('print'); ?>
            </button>
        </div>
    </div>
    
    <!-- Halaqa Info Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <small class="text-muted d-block"><?php echo t('teacher'); ?></small>
                    <strong><?php echo htmlspecialchars($halaqa['teacher_name'] ?: '-'); ?></strong>
                </div>
                <div class="col-md-3 mb-3">
                    <small class="text-muted d-block"><?php echo t('gender'); ?></small>
                    <span class="badge bg-<?php echo $halaqa['gender'] === 'baneen' ? 'primary' : 'danger'; ?>">
                        <?php echo t($halaqa['gender']); ?>
                    </span>
                </div>
                <div class="col-md-3 mb-3">
                    <small class="text-muted d-block"><?php echo t('time'); ?></small>
                    <span class="badge bg-secondary"><?php echo t($halaqa['time_slot']); ?></span>
                </div>
                <div class="col-md-3 mb-3">
                    <small class="text-muted d-block"><?php echo $isRTL ? 'مقام' : 'Location'; ?></small>
                    <strong><?php echo htmlspecialchars($halaqa['location'] ?: '-'); ?></strong>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-icon primary mx-auto">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-number"><?php echo $totalStudents; ?></div>
                <div class="stat-label"><?php echo t('total_students'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-icon mx-auto" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div class="stat-number"><?php echo $mumayyizeenCount; ?></div>
                <div class="stat-label"><?php echo t('total_mumayyizeen'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-icon secondary mx-auto">
                    <i class="bi bi-percent"></i>
                </div>
                <div class="stat-number"><?php echo round($avgPercentage, 1); ?>%</div>
                <div class="stat-label"><?php echo $isRTL ? 'اوسط فیصد' : 'Avg Percentage'; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-icon success mx-auto">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-number"><?php echo $attendanceSummary['total_days'] ?? 0; ?></div>
                <div class="stat-label"><?php echo $isRTL ? 'حاضری کے دن' : 'Attendance Days'; ?></div>
            </div>
        </div>
    </div>
    
    <!-- Students Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i><?php echo t('students'); ?></h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo t('name'); ?></th>
                            <th><?php echo t('shuba'); ?></th>
                            <th><?php echo $isRTL ? 'امتحانات' : 'Exams'; ?></th>
                            <th><?php echo $isRTL ? 'اوسط %' : 'Avg %'; ?></th>
                            <th><?php echo t('mumayyiz'); ?></th>
                            <th><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $index => $student): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['shuba']); ?></td>
                            <td><?php echo $student['exams_count']; ?></td>
                            <td>
                                <?php if ($student['exams_count'] > 0): ?>
                                <span class="badge bg-<?php echo $student['avg_percentage'] >= 60 ? 'success' : ($student['avg_percentage'] >= 40 ? 'warning text-dark' : 'danger'); ?>">
                                    <?php echo round($student['avg_percentage'], 1); ?>%
                                </span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($student['is_mumayyiz']): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> <?php echo t('mumayyiz'); ?></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="report_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary">
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
        <?php echo $isRTL ? 'اس حلقہ میں کوئی طالب علم نہیں۔' : 'No students in this halaqa.'; ?>
    </div>
    <?php endif; ?>
    
    <!-- Exam Results -->
    <?php if (!empty($examResults)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0"><i class="bi bi-file-text-fill me-2"></i><?php echo $isRTL ? 'امتحانی نتائج' : 'Exam Results'; ?></h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?php echo $isRTL ? 'امتحان' : 'Exam'; ?></th>
                            <th><?php echo t('date'); ?></th>
                            <th><?php echo $isRTL ? 'طلباء' : 'Students'; ?></th>
                            <th><?php echo $isRTL ? 'اوسط %' : 'Avg %'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($examResults as $exam): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($exam['title']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($exam['exam_date'])); ?></td>
                            <td><?php echo $exam['results_count']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $exam['avg_percentage'] >= 60 ? 'success' : ($exam['avg_percentage'] >= 40 ? 'warning text-dark' : 'danger'); ?>">
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
</div>

<?php require_once 'includes/footer.php'; ?>
