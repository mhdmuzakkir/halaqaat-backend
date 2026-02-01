<?php
require_once 'includes/header.php';
requireRole('ustaaz');

$teacherId = $_SESSION['user_id'];

// Get teacher's halaqaat
$halaqaat = [];
$stmt = $conn->prepare("SELECT id, name FROM halaqaat WHERE teacher_id = ? AND status = 'active' ORDER BY name");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $halaqaat[] = $row;
}

$selectedHalaqa = isset($_GET['halaqa_id']) ? intval($_GET['halaqa_id']) : ($halaqaat[0]['id'] ?? 0);

// Get students with their exam results
$students = [];
if ($selectedHalaqa) {
    $stmt = $conn->prepare("
        SELECT s.*, 
               AVG(er.percentage) as avg_percentage,
               COUNT(er.id) as exams_count,
               MAX(er.percentage) as highest_percentage,
               MIN(er.percentage) as lowest_percentage
        FROM students s
        LEFT JOIN exam_results er ON er.student_id = s.id AND er.status = 'finalized'
        WHERE s.halaqa_id = ? AND s.status = 'active'
        GROUP BY s.id
        ORDER BY s.name
    ");
    $stmt->bind_param("i", $selectedHalaqa);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Get exam results for each student
$studentExams = [];
if ($selectedHalaqa) {
    $stmt = $conn->prepare("
        SELECT er.*, e.title as exam_title, e.exam_date, s.id as student_id
        FROM exam_results er
        JOIN exams e ON er.exam_id = e.id
        JOIN students s ON er.student_id = s.id
        WHERE s.halaqa_id = ? AND er.status = 'finalized'
        ORDER BY e.exam_date DESC
    ");
    $stmt->bind_param("i", $selectedHalaqa);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $studentExams[$row['student_id']][] = $row;
    }
}

// Get attendance summary for each student
$attendanceSummary = [];
if ($selectedHalaqa) {
    $stmt = $conn->prepare("
        SELECT s.id,
               COUNT(a.id) as total_days,
               SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days
        FROM students s
        LEFT JOIN attendance a ON a.student_id = s.id
        WHERE s.halaqa_id = ? AND s.status = 'active'
        GROUP BY s.id
    ");
    $stmt->bind_param("i", $selectedHalaqa);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $attendanceSummary[$row['id']] = $row;
    }
}
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="page-title"><?php echo t('progress'); ?></h2>
        <p class="greeting"><?php echo t('assalam_alaikum'); ?></p>
    </div>
    
    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label"><?php echo t('halaqaat'); ?></label>
                    <select class="form-select" name="halaqa_id" onchange="this.form.submit()">
                        <?php foreach ($halaqaat as $h): ?>
                        <option value="<?php echo $h['id']; ?>" <?php echo $selectedHalaqa == $h['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($h['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <a href="export.php?type=progress&halaqa_id=<?php echo $selectedHalaqa; ?>" class="btn btn-success">
                        <i class="bi bi-download me-2"></i><?php echo t('export'); ?> <?php echo t('excel'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($selectedHalaqa && !empty($students)): ?>
    <!-- Students Progress Cards -->
    <div class="row g-4">
        <?php foreach ($students as $student): 
            $avgPercentage = $student['avg_percentage'] ?? 0;
            $examsCount = $student['exams_count'] ?? 0;
            $attendance = $attendanceSummary[$student['id']] ?? null;
            $attendancePercentage = ($attendance && $attendance['total_days'] > 0) 
                ? round(($attendance['present_days'] / $attendance['total_days']) * 100, 1) 
                : 0;
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($student['name']); ?></h5>
                            <small class="text-muted"><?php echo htmlspecialchars($student['shuba']); ?></small>
                        </div>
                        <?php if ($student['is_mumayyiz']): ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> <?php echo t('mumayyiz'); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Exam Progress -->
                    <?php if ($examsCount > 0): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small><?php echo $isRTL ? 'امتحانی کارکردگی' : 'Exam Performance'; ?></small>
                            <small class="fw-bold"><?php echo round($avgPercentage, 1); ?>%</small>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-<?php echo $avgPercentage >= 80 ? 'success' : ($avgPercentage >= 60 ? 'warning' : 'danger'); ?>" 
                                 style="width: <?php echo $avgPercentage; ?>%"></div>
                        </div>
                        <small class="text-muted">
                            <?php echo $examsCount; ?> <?php echo $isRTL ? 'امتحانات' : 'exams'; ?> | 
                            <?php echo $isRTL ? 'اعلیٰ:' : 'High:'; ?> <?php echo round($student['highest_percentage'], 1); ?>% | 
                            <?php echo $isRTL ? 'ادنیٰ:' : 'Low:'; ?> <?php echo round($student['lowest_percentage'], 1); ?>%
                        </small>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Attendance Progress -->
                    <?php if ($attendance && $attendance['total_days'] > 0): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small><?php echo $isRTL ? 'حاضری' : 'Attendance'; ?></small>
                            <small class="fw-bold"><?php echo $attendancePercentage; ?>%</small>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-info" style="width: <?php echo $attendancePercentage; ?>%"></div>
                        </div>
                        <small class="text-muted">
                            <?php echo $attendance['present_days']; ?>/<?php echo $attendance['total_days']; ?> <?php echo $isRTL ? 'دن' : 'days'; ?>
                        </small>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Recent Exam Results -->
                    <?php if (isset($studentExams[$student['id']])): 
                        $recentExams = array_slice($studentExams[$student['id']], 0, 3);
                    ?>
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted d-block mb-2"><?php echo $isRTL ? 'حالیہ امتحانات:' : 'Recent Exams:'; ?></small>
                        <?php foreach ($recentExams as $exam): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small><?php echo htmlspecialchars($exam['exam_title']); ?></small>
                            <span class="badge bg-<?php echo $exam['percentage'] >= 60 ? 'success' : ($exam['percentage'] >= 40 ? 'warning text-dark' : 'danger'); ?>">
                                <?php echo $exam['marks_obtained']; ?>/<?php echo $exam['max_marks']; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="report_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-eye me-2"></i><?php echo $isRTL ? 'مکمل رپورٹ' : 'Full Report'; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Summary Table -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i><?php echo $isRTL ? 'مکمل خلاصہ' : 'Complete Summary'; ?></h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="progressTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo t('name'); ?></th>
                            <th><?php echo t('shuba'); ?></th>
                            <th><?php echo $isRTL ? 'امتحانات' : 'Exams'; ?></th>
                            <th><?php echo $isRTL ? 'اوسط %' : 'Avg %'; ?></th>
                            <th><?php echo $isRTL ? 'حاضری %' : 'Attendance %'; ?></th>
                            <th><?php echo t('mumayyiz'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $index => $student): 
                            $avgPercentage = $student['avg_percentage'] ?? 0;
                            $examsCount = $student['exams_count'] ?? 0;
                            $attendance = $attendanceSummary[$student['id']] ?? null;
                            $attendancePercentage = ($attendance && $attendance['total_days'] > 0) 
                                ? round(($attendance['present_days'] / $attendance['total_days']) * 100, 1) 
                                : 0;
                        ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['shuba']); ?></td>
                            <td><?php echo $examsCount; ?></td>
                            <td>
                                <?php if ($examsCount > 0): ?>
                                <span class="badge bg-<?php echo $avgPercentage >= 80 ? 'success' : ($avgPercentage >= 60 ? 'warning text-dark' : 'danger'); ?>">
                                    <?php echo round($avgPercentage, 1); ?>%
                                </span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($attendance && $attendance['total_days'] > 0): ?>
                                <span class="badge bg-info"><?php echo $attendancePercentage; ?>%</span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($student['is_mumayyiz']): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php elseif ($selectedHalaqa): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill me-2"></i>
        <?php echo $isRTL ? 'اس حلقہ میں کوئی طالب علم نہیں۔' : 'No students in this halaqa.'; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php echo $isRTL ? 'آپ کو ابھی تک کوئی حلقہ تفویض نہیں کیا گیا۔' : 'No halaqaat have been assigned to you yet.'; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
