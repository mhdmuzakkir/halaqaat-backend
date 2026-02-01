<?php
require_once 'includes/header.php';
requireLogin();

if (!isset($_GET['id'])) {
    header('Location: students_manage.php');
    exit;
}

$studentId = intval($_GET['id']);

// Get student details
$stmt = $conn->prepare("
    SELECT s.*, h.name as halaqa_name, h.gender as halaqa_gender, u.name as teacher_name
    FROM students s
    LEFT JOIN halaqaat h ON s.halaqa_id = h.id
    LEFT JOIN users u ON h.teacher_id = u.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    header('Location: students_manage.php');
    exit;
}

// Get exam results
$examResults = [];
$stmt = $conn->prepare("
    SELECT er.*, e.title as exam_title, e.exam_date, e.max_marks as exam_max_marks, e.passing_marks
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.id
    WHERE er.student_id = ? AND er.status = 'finalized'
    ORDER BY e.exam_date DESC
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $examResults[] = $row;
}

// Get attendance summary
$attendanceSummary = [];
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
        SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_days
    FROM attendance
    WHERE student_id = ?
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$attendanceSummary = $stmt->get_result()->fetch_assoc();

// Calculate statistics
$totalExams = count($examResults);
$avgPercentage = $totalExams > 0 ? array_sum(array_column($examResults, 'percentage')) / $totalExams : 0;
$highestPercentage = $totalExams > 0 ? max(array_column($examResults, 'percentage')) : 0;
$lowestPercentage = $totalExams > 0 ? min(array_column($examResults, 'percentage')) : 0;
$passedExams = count(array_filter($examResults, fn($r) => $r['percentage'] >= $r['passing_marks']));

$totalAttendanceDays = $attendanceSummary['total_days'] ?? 0;
$attendancePercentage = $totalAttendanceDays > 0 
    ? round(($attendanceSummary['present_days'] / $totalAttendanceDays) * 100, 1) 
    : 0;
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="page-title"><?php echo htmlspecialchars($student['name']); ?></h2>
            <p class="greeting"><?php echo t('assalam_alaikum'); ?></p>
        </div>
        <div class="text-end">
            <a href="export.php?type=student&id=<?php echo $studentId; ?>" class="btn btn-success mb-2">
                <i class="bi bi-download me-2"></i><?php echo t('export'); ?>
            </a>
            <br>
            <button onclick="printPage()" class="btn btn-outline-primary">
                <i class="bi bi-printer me-2"></i><?php echo t('print'); ?>
            </button>
        </div>
    </div>
    
    <!-- Student Info Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <small class="text-muted d-block"><?php echo $isRTL ? 'رول نمبر' : 'Roll Number'; ?></small>
                    <strong><?php echo htmlspecialchars($student['roll_number'] ?: '-'); ?></strong>
                </div>
                <div class="col-md-3 mb-3">
                    <small class="text-muted d-block"><?php echo t('shuba'); ?></small>
                    <strong><?php echo htmlspecialchars($student['shuba']); ?></strong>
                </div>
                <div class="col-md-3 mb-3">
                    <small class="text-muted d-block"><?php echo t('halaqaat'); ?></small>
                    <strong><?php echo htmlspecialchars($student['halaqa_name'] ?: '-'); ?></strong>
                </div>
                <div class="col-md-3 mb-3">
                    <small class="text-muted d-block"><?php echo t('teacher'); ?></small>
                    <strong><?php echo htmlspecialchars($student['teacher_name'] ?: '-'); ?></strong>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-3 mb-3">
                    <small class="text-muted d-block"><?php echo $isRTL ? 'والد کا نام' : 'Father Name'; ?></small>
                    <strong><?php echo htmlspecialchars($student['father_name'] ?: '-'); ?></strong>
                </div>
                <div class="col-md-3 mb-3">
                    <small class="text-muted d-block"><?php echo $isRTL ? 'رابطہ' : 'Contact'; ?></small>
                    <strong><?php echo htmlspecialchars($student['contact_number'] ?: '-'); ?></strong>
                </div>
                <div class="col-md-3 mb-3">
                    <small class="text-muted d-block"><?php echo $isRTL ? 'شمولیت' : 'Joined'; ?></small>
                    <strong><?php echo $student['join_date'] ? date('M d, Y', strtotime($student['join_date'])) : '-'; ?></strong>
                </div>
                <div class="col-md-3 mb-3">
                    <?php if ($student['is_mumayyiz']): ?>
                    <span class="badge bg-warning text-dark fs-6"><i class="bi bi-star-fill me-1"></i> <?php echo t('mumayyiz'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-icon primary mx-auto">
                    <i class="bi bi-file-text-fill"></i>
                </div>
                <div class="stat-number"><?php echo $totalExams; ?></div>
                <div class="stat-label"><?php echo $isRTL ? 'امتحانات' : 'Exams'; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-icon secondary mx-auto">
                    <i class="bi bi-percent"></i>
                </div>
                <div class="stat-number"><?php echo round($avgPercentage, 1); ?>%</div>
                <div class="stat-label"><?php echo $isRTL ? 'اوسط' : 'Average'; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-icon success mx-auto">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-number"><?php echo $passedExams; ?>/<?php echo $totalExams; ?></div>
                <div class="stat-label"><?php echo $isRTL ? 'پاس' : 'Passed'; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-icon mx-auto" style="background: rgba(23, 162, 184, 0.1); color: #17a2b8;">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-number"><?php echo $attendancePercentage; ?>%</div>
                <div class="stat-label"><?php echo $isRTL ? 'حاضری' : 'Attendance'; ?></div>
            </div>
        </div>
    </div>
    
    <!-- Exam Results -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0"><i class="bi bi-file-text-fill me-2"></i><?php echo $isRTL ? 'امتحانی نتائج' : 'Exam Results'; ?></h5>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($examResults)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo $isRTL ? 'امتحان' : 'Exam'; ?></th>
                            <th><?php echo t('date'); ?></th>
                            <th><?php echo $isRTL ? 'نمبر' : 'Marks'; ?></th>
                            <th><?php echo $isRTL ? 'فیصد' : 'Percentage'; ?></th>
                            <th><?php echo $isRTL ? 'حالت' : 'Status'; ?></th>
                            <th><?php echo t('remarks'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($examResults as $index => $result): 
                            $isPassed = $result['percentage'] >= $result['passing_marks'];
                        ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($result['exam_date'])); ?></td>
                            <td><?php echo $result['marks_obtained']; ?>/<?php echo $result['exam_max_marks']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $isPassed ? 'success' : 'danger'; ?>">
                                    <?php echo round($result['percentage'], 1); ?>%
                                </span>
                            </td>
                            <td>
                                <?php if ($isPassed): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> <?php echo $isRTL ? 'پاس' : 'Pass'; ?></span>
                                <?php else: ?>
                                <span class="badge bg-danger"><i class="bi bi-x-circle"></i> <?php echo $isRTL ? 'فیل' : 'Fail'; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($result['remarks'] ?: '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Performance Chart -->
            <div class="p-4">
                <h6 class="mb-3"><?php echo $isRTL ? 'کارکردگی کا گراف' : 'Performance Chart'; ?></h6>
                <canvas id="performanceChart" height="100"></canvas>
            </div>
            
            <script>
            const ctx = document.getElementById('performanceChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_map(fn($r) => date('M d', strtotime($r['exam_date'])), array_reverse($examResults))); ?>,
                    datasets: [{
                        label: '<?php echo $isRTL ? "فیصد" : "Percentage"; ?>',
                        data: <?php echo json_encode(array_map(fn($r) => round($r['percentage'], 1), array_reverse($examResults))); ?>,
                        borderColor: '#aa815e',
                        backgroundColor: 'rgba(170, 129, 94, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
            </script>
            
            <?php else: ?>
            <div class="text-center py-4">
                <p class="text-muted mb-0"><?php echo $isRTL ? 'کوئی امتحانی نتیجہ نہیں۔' : 'No exam results found.'; ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Attendance Summary -->
    <?php if ($totalAttendanceDays > 0): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i><?php echo $isRTL ? 'حاضری کا خلاصہ' : 'Attendance Summary'; ?></h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-success mb-1"><?php echo $attendanceSummary['present_days']; ?></h4>
                        <small class="text-muted"><?php echo t('present'); ?></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-danger mb-1"><?php echo $attendanceSummary['absent_days']; ?></h4>
                        <small class="text-muted"><?php echo t('absent'); ?></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-warning mb-1"><?php echo $attendanceSummary['late_days']; ?></h4>
                        <small class="text-muted"><?php echo t('late'); ?></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-info mb-1"><?php echo $attendanceSummary['excused_days']; ?></h4>
                        <small class="text-muted"><?php echo t('excused'); ?></small>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <div class="d-flex justify-content-between mb-2">
                    <span><?php echo $isRTL ? 'حاضری کی شرح' : 'Attendance Rate'; ?></span>
                    <strong><?php echo $attendancePercentage; ?>%</strong>
                </div>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar bg-info" style="width: <?php echo $attendancePercentage; ?>%">
                        <?php echo $attendancePercentage; ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
