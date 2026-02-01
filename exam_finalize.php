<?php
require_once 'includes/header.php';
requireRole('mumtahin');

$success = '';
$error = '';

// Get ongoing exams
$exams = [];
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
    $exams[] = $row;
}

$selectedExam = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : ($exams[0]['id'] ?? 0);
$examDetails = null;
$results = [];

if ($selectedExam) {
    // Get exam details
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->bind_param("i", $selectedExam);
    $stmt->execute();
    $examDetails = $stmt->get_result()->fetch_assoc();
    
    // Get all results for this exam
    $stmt = $conn->prepare("
        SELECT er.*, s.name as student_name, s.shuba, h.name as halaqa_name, h.gender as halaqa_gender
        FROM exam_results er
        JOIN students s ON er.student_id = s.id
        JOIN halaqaat h ON s.halaqa_id = h.id
        WHERE er.exam_id = ?
        ORDER BY h.gender, er.percentage DESC
    ");
    $stmt->bind_param("i", $selectedExam);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
}

// Finalize exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize_exam'])) {
    $examId = intval($_POST['exam_id']);
    
    // Update all results to finalized
    $stmt = $conn->prepare("UPDATE exam_results SET status = 'finalized' WHERE exam_id = ?");
    $stmt->bind_param("i", $examId);
    
    if ($stmt->execute()) {
        // Update exam status to finalized
        $stmt = $conn->prepare("UPDATE exams SET status = 'finalized' WHERE id = ?");
        $stmt->bind_param("i", $examId);
        $stmt->execute();
        
        $success = $isRTL ? 'امتحان کامیابی سے حتمی شکل میں آ گیا۔' : 'Exam finalized successfully.';
        
        // Refresh
        header('Location: exam_finalize.php?success=1');
        exit;
    } else {
        $error = $isRTL ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
    }
}

if (isset($_GET['success'])) {
    $success = $isRTL ? 'امتحان کامیابی سے حتمی شکل میں آ گیا۔' : 'Exam finalized successfully.';
}
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="page-title"><?php echo t('finalize'); ?></h2>
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
    
    <!-- Exam Selector -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-12">
                    <label class="form-label"><?php echo $isRTL ? 'امتحان منتخب کریں' : 'Select Exam'; ?></label>
                    <select class="form-select" name="exam_id" onchange="this.form.submit()">
                        <?php if (empty($exams)): ?>
                        <option value=""><?php echo $isRTL ? 'کوئی حتمی کرنے کے لیے امتحان نہیں' : 'No exams pending finalization'; ?></option>
                        <?php else: ?>
                        <?php foreach ($exams as $exam): ?>
                        <option value="<?php echo $exam['id']; ?>" <?php echo $selectedExam == $exam['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($exam['title']); ?> (<?php echo date('M d, Y', strtotime($exam['exam_date'])); ?>) - <?php echo $exam['entries_count']; ?> <?php echo $isRTL ? 'اندراجات' : 'entries'; ?>
                        </option>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($selectedExam && $examDetails && !empty($results)): ?>
    <!-- Exam Info -->
    <div class="alert alert-warning mb-4">
        <div class="row align-items-center">
            <div class="col-md-4">
                <strong><i class="bi bi-file-text me-2"></i><?php echo htmlspecialchars($examDetails['title']); ?></strong>
            </div>
            <div class="col-md-4">
                <i class="bi bi-calendar-event me-2"></i><?php echo date('M d, Y', strtotime($examDetails['exam_date'])); ?>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="badge bg-warning text-dark fs-6">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <?php echo $isRTL ? 'حتمی شکل کے لیے تیار' : 'Ready for Finalization'; ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Results Summary -->
    <?php
    $totalStudents = count($results);
    $passedStudents = count(array_filter($results, fn($r) => $r['percentage'] >= $examDetails['passing_marks']));
    $failedStudents = $totalStudents - $passedStudents;
    $avgPercentage = array_sum(array_column($results, 'percentage')) / $totalStudents;
    ?>
    
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-icon primary mx-auto">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-number"><?php echo $totalStudents; ?></div>
                <div class="stat-label"><?php echo $isRTL ? 'کل طلباء' : 'Total Students'; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-icon success mx-auto">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-number"><?php echo $passedStudents; ?></div>
                <div class="stat-label"><?php echo $isRTL ? 'پاس' : 'Passed'; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-icon mx-auto" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="stat-number"><?php echo $failedStudents; ?></div>
                <div class="stat-label"><?php echo $isRTL ? 'فیل' : 'Failed'; ?></div>
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
    </div>
    
    <!-- Results Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i><?php echo $isRTL ? 'امتحانی نتائج' : 'Exam Results'; ?></h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?php echo $isRTL ? 'درجہ' : 'Rank'; ?></th>
                            <th><?php echo t('name'); ?></th>
                            <th><?php echo t('halaqaat'); ?></th>
                            <th><?php echo t('shuba'); ?></th>
                            <th><?php echo $isRTL ? 'نمبر' : 'Marks'; ?></th>
                            <th><?php echo $isRTL ? 'فیصد' : '%'; ?></th>
                            <th><?php echo $isRTL ? 'حالت' : 'Status'; ?></th>
                            <th><?php echo t('remarks'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        $currentGender = '';
                        foreach ($results as $result): 
                            if ($currentGender !== $result['halaqa_gender']):
                                $currentGender = $result['halaqa_gender'];
                                $rank = 1;
                        ?>
                        <tr class="table-light">
                            <td colspan="8" class="fw-bold text-center">
                                <i class="bi bi-<?php echo $currentGender === 'baneen' ? 'gender-male' : 'gender-female'; ?>"></i>
                                <?php echo t($currentGender); ?>
                            </td>
                        </tr>
                        <?php endif; 
                            $isPassed = $result['percentage'] >= $examDetails['passing_marks'];
                        ?>
                        <tr>
                            <td><span class="badge bg-secondary">#<?php echo $rank++; ?></span></td>
                            <td>
                                <?php echo htmlspecialchars($result['student_name']); ?>
                                <?php if ($rank <= 4): ?>
                                <i class="bi bi-trophy-fill text-warning"></i>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($result['halaqa_name']); ?></td>
                            <td><?php echo htmlspecialchars($result['shuba']); ?></td>
                            <td><?php echo $result['marks_obtained']; ?>/<?php echo $result['max_marks']; ?></td>
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
                            <td><?php echo htmlspecialchars($result['remarks'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Finalize Button -->
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center p-4">
            <i class="bi bi-exclamation-triangle-fill text-warning fs-1 mb-3"></i>
            <h5 class="mb-3"><?php echo $isRTL ? 'کیا آپ اس امتحان کو حتمی شکل دینا چاہتے ہیں؟' : 'Are you sure you want to finalize this exam?'; ?></h5>
            <p class="text-muted mb-4">
                <?php echo $isRTL ? 'ایک بار حتمی شکل میں آنے کے بعد، آپ نمبر میں تبدیلی نہیں کر سکیں گے۔' : 'Once finalized, you will not be able to modify the marks.'; ?>
            </p>
            <form method="POST" action="" onsubmit="return confirm('<?php echo $isRTL ? 'کیا آپ واقعی حتمی شکل دینا چاہتے ہیں؟' : 'Are you sure you want to finalize?'; ?>');">
                <input type="hidden" name="exam_id" value="<?php echo $selectedExam; ?>">
                <button type="submit" name="finalize_exam" class="btn btn-warning btn-lg">
                    <i class="bi bi-lock-fill me-2"></i><?php echo t('finalize'); ?> <?php echo $isRTL ? 'امتحان' : 'Exam'; ?>
                </button>
            </form>
        </div>
    </div>
    
    <?php elseif ($selectedExam): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill me-2"></i>
        <?php echo $isRTL ? 'اس امتحان کے لیے کوئی نمبر درج نہیں کیے گئے۔' : 'No marks have been entered for this exam.'; ?>
        <a href="exam_marks_entry.php?exam_id=<?php echo $selectedExam; ?>" class="alert-link">
            <?php echo $isRTL ? 'نمبر درج کریں' : 'Enter marks'; ?>
        </a>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill me-2"></i>
        <?php echo $isRTL ? 'حتمی شکل کے لیے کوئی امتحان نہیں۔' : 'No exams pending finalization.'; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
