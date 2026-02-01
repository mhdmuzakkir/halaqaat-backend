<?php
require_once 'includes/header.php';
requireRole('ustaaz');

$teacherId = $_SESSION['user_id'];
$success = '';
$error = '';

// Get teacher's halaqaat
$halaqaat = [];
$stmt = $conn->prepare("SELECT id, name, gender FROM halaqaat WHERE teacher_id = ? AND status = 'active' ORDER BY name");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $halaqaat[] = $row;
}

$selectedHalaqa = isset($_GET['halaqa_id']) ? intval($_GET['halaqa_id']) : ($halaqaat[0]['id'] ?? 0);
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Get students for selected halaqa
$students = [];
if ($selectedHalaqa) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE halaqa_id = ? AND status = 'active' ORDER BY name");
    $stmt->bind_param("i", $selectedHalaqa);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Get existing attendance for the date
$attendance = [];
if ($selectedHalaqa) {
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE halaqa_id = ? AND date = ?");
    $stmt->bind_param("is", $selectedHalaqa, $selectedDate);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $attendance[$row['student_id']] = $row['status'];
    }
}

// Save attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $halaqaId = intval($_POST['halaqa_id']);
    $date = $_POST['attendance_date'];
    $attendanceData = $_POST['attendance'] ?? [];
    
    // Delete existing attendance for this date
    $stmt = $conn->prepare("DELETE FROM attendance WHERE halaqa_id = ? AND date = ?");
    $stmt->bind_param("is", $halaqaId, $date);
    $stmt->execute();
    
    // Insert new attendance records
    $stmt = $conn->prepare("INSERT INTO attendance (student_id, halaqa_id, date, status) VALUES (?, ?, ?, ?)");
    foreach ($attendanceData as $studentId => $status) {
        $stmt->bind_param("iiss", $studentId, $halaqaId, $date, $status);
        $stmt->execute();
    }
    
    $success = $isRTL ? 'حاضری کامیابی سے محفوظ ہو گئی۔' : 'Attendance saved successfully.';
    
    // Refresh attendance data
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE halaqa_id = ? AND date = ?");
    $stmt->bind_param("is", $halaqaId, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendance = [];
    while ($row = $result->fetch_assoc()) {
        $attendance[$row['student_id']] = $row['status'];
    }
}

// Get attendance summary for the month
$attendanceSummary = [];
if ($selectedHalaqa) {
    $monthStart = date('Y-m-01', strtotime($selectedDate));
    $monthEnd = date('Y-m-t', strtotime($selectedDate));
    
    $stmt = $conn->prepare("
        SELECT date, COUNT(*) as count, 
               SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count
        FROM attendance 
        WHERE halaqa_id = ? AND date BETWEEN ? AND ?
        GROUP BY date
    ");
    $stmt->bind_param("iss", $selectedHalaqa, $monthStart, $monthEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $attendanceSummary[$row['date']] = $row;
    }
}
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="page-title"><?php echo t('attendance'); ?></h2>
        <p class="greeting"><?php echo t('assalam_alaikum'); ?></p>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label"><?php echo t('halaqaat'); ?></label>
                    <select class="form-select" name="halaqa_id" onchange="this.form.submit()">
                        <?php foreach ($halaqaat as $h): ?>
                        <option value="<?php echo $h['id']; ?>" <?php echo $selectedHalaqa == $h['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($h['name']); ?> (<?php echo t($h['gender']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label"><?php echo t('date'); ?></label>
                    <input type="date" class="form-control" name="date" value="<?php echo $selectedDate; ?>" onchange="this.form.submit()">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="attendance.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-clockwise"></i> <?php echo $isRTL ? 'ری سیٹ' : 'Reset'; ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($selectedHalaqa && !empty($students)): ?>
    <!-- Attendance Form -->
    <form method="POST" action="">
        <input type="hidden" name="halaqa_id" value="<?php echo $selectedHalaqa; ?>">
        <input type="hidden" name="attendance_date" value="<?php echo $selectedDate; ?>">
        
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-check me-2"></i>
                    <?php echo date('F d, Y', strtotime($selectedDate)); ?>
                </h5>
                <button type="submit" name="save_attendance" class="btn btn-primary-custom">
                    <i class="bi bi-check-lg me-2"></i><?php echo t('save'); ?>
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo t('name'); ?></th>
                                <th><?php echo t('shuba'); ?></th>
                                <th><?php echo t('mumayyiz'); ?></th>
                                <th><?php echo t('attendance'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student): 
                                $currentStatus = $attendance[$student['id']] ?? 'present';
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['shuba']); ?></td>
                                <td>
                                    <?php if ($student['is_mumayyiz']): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i></span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" id="present_<?php echo $student['id']; ?>" value="present" <?php echo $currentStatus === 'present' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-success" for="present_<?php echo $student['id']; ?>">
                                            <i class="bi bi-check-circle"></i> <?php echo t('present'); ?>
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" id="absent_<?php echo $student['id']; ?>" value="absent" <?php echo $currentStatus === 'absent' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-danger" for="absent_<?php echo $student['id']; ?>">
                                            <i class="bi bi-x-circle"></i> <?php echo t('absent'); ?>
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" id="late_<?php echo $student['id']; ?>" value="late" <?php echo $currentStatus === 'late' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-warning" for="late_<?php echo $student['id']; ?>">
                                            <i class="bi bi-clock"></i> <?php echo t('late'); ?>
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" id="excused_<?php echo $student['id']; ?>" value="excused" <?php echo $currentStatus === 'excused' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-info" for="excused_<?php echo $student['id']; ?>">
                                            <i class="bi bi-file-text"></i> <?php echo t('excused'); ?>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
    
    <!-- Monthly Summary -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0"><i class="bi bi-calendar-month me-2"></i><?php echo $isRTL ? 'ماہانہ خلاصہ' : 'Monthly Summary'; ?> (<?php echo date('F Y', strtotime($selectedDate)); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($attendanceSummary)): ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th><?php echo t('date'); ?></th>
                            <th><?php echo $isRTL ? 'کل طلباء' : 'Total Students'; ?></th>
                            <th><?php echo t('present'); ?></th>
                            <th><?php echo $isRTL ? 'غیر حاضر' : 'Absent'; ?></th>
                            <th><?php echo $isRTL ? 'حاضری %' : 'Attendance %'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendanceSummary as $date => $summary): 
                            $absentCount = $summary['count'] - $summary['present_count'];
                            $percentage = $summary['count'] > 0 ? round(($summary['present_count'] / $summary['count']) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><?php echo date('M d', strtotime($date)); ?></td>
                            <td><?php echo $summary['count']; ?></td>
                            <td class="text-success"><?php echo $summary['present_count']; ?></td>
                            <td class="text-danger"><?php echo $absentCount; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $percentage >= 80 ? 'success' : ($percentage >= 60 ? 'warning text-dark' : 'danger'); ?>">
                                    <?php echo $percentage; ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted text-center mb-0"><?php echo $isRTL ? 'اس مہینے کوئی حاضری ریکارڈ نہیں۔' : 'No attendance records for this month.'; ?></p>
            <?php endif; ?>
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
