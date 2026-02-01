<?php
require_once 'includes/header.php';
requireRole('mumtahin');

$success = '';
$error = '';

// Get all exams for dropdown
$exams = [];
$result = $conn->query("SELECT * FROM exams WHERE status != 'finalized' ORDER BY exam_date DESC");
while ($row = $result->fetch_assoc()) {
    $exams[] = $row;
}

$selectedExam = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : ($exams[0]['id'] ?? 0);
$examDetails = null;
$students = [];
$existingResults = [];

if ($selectedExam) {
    // Get exam details
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->bind_param("i", $selectedExam);
    $stmt->execute();
    $examDetails = $stmt->get_result()->fetch_assoc();
    
    // Get all active students with their halaqa info
    $stmt = $conn->prepare("
        SELECT s.*, h.name as halaqa_name, h.gender as halaqa_gender
        FROM students s
        JOIN halaqaat h ON s.halaqa_id = h.id
        WHERE s.status = 'active'
        ORDER BY h.gender, h.name, s.name
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    // Get existing results for this exam
    $stmt = $conn->prepare("SELECT * FROM exam_results WHERE exam_id = ?");
    $stmt->bind_param("i", $selectedExam);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $existingResults[$row['student_id']] = $row;
    }
}

// Save marks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    $examId = intval($_POST['exam_id']);
    $marksData = $_POST['marks'] ?? [];
    $remarksData = $_POST['remarks'] ?? [];
    
    $stmt = $conn->prepare("
        INSERT INTO exam_results (exam_id, student_id, marks_obtained, max_marks, percentage, remarks, status)
        VALUES (?, ?, ?, ?, ?, ?, 'draft')
        ON DUPLICATE KEY UPDATE
        marks_obtained = VALUES(marks_obtained),
        percentage = VALUES(percentage),
        remarks = VALUES(remarks)
    ");
    
    foreach ($marksData as $studentId => $marks) {
        if ($marks !== '') {
            $marksObtained = floatval($marks);
            $maxMarks = $examDetails['max_marks'];
            $percentage = ($marksObtained / $maxMarks) * 100;
            $remarks = $remarksData[$studentId] ?? '';
            
            $stmt->bind_param("iiddss", $examId, $studentId, $marksObtained, $maxMarks, $percentage, $remarks);
            $stmt->execute();
        }
    }
    
    // Update exam status to ongoing if not already
    $conn->query("UPDATE exams SET status = 'ongoing' WHERE id = $examId AND status = 'upcoming'");
    
    $success = $isRTL ? 'نمبر کامیابی سے محفوظ ہو گئے۔' : 'Marks saved successfully.';
    
    // Refresh existing results
    $stmt = $conn->prepare("SELECT * FROM exam_results WHERE exam_id = ?");
    $stmt->bind_param("i", $selectedExam);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingResults = [];
    while ($row = $result->fetch_assoc()) {
        $existingResults[$row['student_id']] = $row;
    }
}
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="page-title"><?php echo t('marks_entry'); ?></h2>
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
                <div class="col-md-8">
                    <label class="form-label"><?php echo $isRTL ? 'امتحان منتخب کریں' : 'Select Exam'; ?></label>
                    <select class="form-select" name="exam_id" onchange="this.form.submit()">
                        <?php foreach ($exams as $exam): ?>
                        <option value="<?php echo $exam['id']; ?>" <?php echo $selectedExam == $exam['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($exam['title']); ?> (<?php echo date('M d, Y', strtotime($exam['exam_date'])); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <a href="exams_manage.php?action=add" class="btn btn-primary-custom w-100">
                        <i class="bi bi-plus-lg me-2"></i><?php echo $isRTL ? 'نیا امتحان' : 'New Exam'; ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($selectedExam && $examDetails): ?>
    <!-- Exam Info -->
    <div class="alert alert-info mb-4">
        <div class="row">
            <div class="col-md-4">
                <strong><i class="bi bi-file-text me-2"></i><?php echo htmlspecialchars($examDetails['title']); ?></strong>
            </div>
            <div class="col-md-4">
                <i class="bi bi-calendar-event me-2"></i><?php echo date('M d, Y', strtotime($examDetails['exam_date'])); ?>
            </div>
            <div class="col-md-4">
                <i class="bi bi-award me-2"></i><?php echo $isRTL ? 'کل نمبر:' : 'Max Marks:'; ?> <?php echo $examDetails['max_marks']; ?>
            </div>
        </div>
    </div>
    
    <?php if (!empty($students)): ?>
    <!-- Marks Entry Form -->
    <form method="POST" action="">
        <input type="hidden" name="exam_id" value="<?php echo $selectedExam; ?>">
        
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-pencil-square me-2"></i>
                    <?php echo $isRTL ? 'نمبر درج کریں' : 'Enter Marks'; ?>
                </h5>
                <div>
                    <button type="submit" name="save_marks" class="btn btn-primary-custom">
                        <i class="bi bi-check-lg me-2"></i><?php echo t('save'); ?>
                    </button>
                    <a href="exam_finalize.php?exam_id=<?php echo $selectedExam; ?>" class="btn btn-warning ms-2">
                        <i class="bi bi-lock me-2"></i><?php echo t('finalize'); ?>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo t('name'); ?></th>
                                <th><?php echo t('halaqaat'); ?></th>
                                <th><?php echo t('shuba'); ?></th>
                                <th><?php echo $isRTL ? 'نمبر (' . $examDetails['max_marks'] . ')' : 'Marks (' . $examDetails['max_marks'] . ')'; ?></th>
                                <th><?php echo $isRTL ? 'فیصد' : '%'; ?></th>
                                <th><?php echo t('remarks'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $currentGender = '';
                            foreach ($students as $index => $student): 
                                if ($currentGender !== $student['halaqa_gender']):
                                    $currentGender = $student['halaqa_gender'];
                            ?>
                            <tr class="table-light">
                                <td colspan="7" class="fw-bold text-center">
                                    <i class="bi bi-<?php echo $currentGender === 'baneen' ? 'gender-male' : 'gender-female'; ?>"></i>
                                    <?php echo t($currentGender); ?>
                                </td>
                            </tr>
                            <?php endif; 
                                $existing = $existingResults[$student['id']] ?? null;
                                $marks = $existing['marks_obtained'] ?? '';
                                $percentage = $existing['percentage'] ?? 0;
                                $remarks = $existing['remarks'] ?? '';
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($student['name']); ?>
                                    <?php if ($student['is_mumayyiz']): ?>
                                    <span class="badge bg-warning text-dark ms-1"><i class="bi bi-star-fill"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($student['halaqa_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['shuba']); ?></td>
                                <td>
                                    <input type="number" 
                                           class="form-control marks-input" 
                                           name="marks[<?php echo $student['id']; ?>]" 
                                           value="<?php echo $marks; ?>"
                                           min="0" 
                                           max="<?php echo $examDetails['max_marks']; ?>"
                                           step="0.5"
                                           data-max="<?php echo $examDetails['max_marks']; ?>"
                                           data-student="<?php echo $student['id']; ?>"
                                           style="width: 100px;">
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $percentage >= 60 ? 'success' : ($percentage >= 40 ? 'warning text-dark' : 'danger'); ?> percentage-badge" 
                                          id="percentage_<?php echo $student['id']; ?>">
                                        <?php echo $percentage > 0 ? round($percentage, 1) . '%' : '-'; ?>
                                    </span>
                                </td>
                                <td>
                                    <input type="text" 
                                           class="form-control" 
                                           name="remarks[<?php echo $student['id']; ?>]" 
                                           value="<?php echo htmlspecialchars($remarks); ?>"
                                           placeholder="<?php echo $isRTL ? 'تبصرہ' : 'Remark'; ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between">
            <button type="submit" name="save_marks" class="btn btn-primary-custom btn-lg">
                <i class="bi bi-check-lg me-2"></i><?php echo t('save'); ?>
            </button>
            <a href="exam_finalize.php?exam_id=<?php echo $selectedExam; ?>" class="btn btn-warning btn-lg">
                <i class="bi bi-lock me-2"></i><?php echo t('finalize'); ?> <?php echo $isRTL ? 'امتحان' : 'Exam'; ?>
            </a>
        </div>
    </form>
    
    <script>
    // Auto-calculate percentage
    document.querySelectorAll('.marks-input').forEach(input => {
        input.addEventListener('input', function() {
            const maxMarks = parseFloat(this.dataset.max);
            const marks = parseFloat(this.value) || 0;
            const studentId = this.dataset.student;
            const percentage = (marks / maxMarks) * 100;
            
            const badge = document.getElementById('percentage_' + studentId);
            if (marks > 0) {
                badge.textContent = percentage.toFixed(1) + '%';
                badge.className = 'badge percentage-badge bg-' + (percentage >= 60 ? 'success' : (percentage >= 40 ? 'warning text-dark' : 'danger'));
            } else {
                badge.textContent = '-';
                badge.className = 'badge percentage-badge bg-secondary';
            }
        });
    });
    </script>
    
    <?php else: ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php echo $isRTL ? 'کوئی فعال طالب علم نہیں۔' : 'No active students found.'; ?>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill me-2"></i>
        <?php echo $isRTL ? 'براہ کرم امتحان منتخب کریں یا نیا امتحان بنائیں۔' : 'Please select an exam or create a new one.'; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
