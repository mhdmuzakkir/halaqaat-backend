<?php
require_once 'includes/header.php';
requireLogin();

$success = '';
$error = '';
$editMode = false;
$exam = [
    'id' => '',
    'title' => '',
    'exam_date' => '',
    'max_marks' => 100,
    'passing_marks' => 40,
    'status' => 'upcoming'
];

// Edit mode
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $exam = $result->fetch_assoc();
        $editMode = true;
    }
}

// Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id = intval($_POST['exam_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM exams WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header('Location: exams_manage.php');
        exit;
    }
    
    $title = trim($_POST['title'] ?? '');
    $examDate = $_POST['exam_date'] ?? '';
    $maxMarks = intval($_POST['max_marks'] ?? 100);
    $passingMarks = intval($_POST['passing_marks'] ?? 40);
    
    if (empty($title)) {
        $error = $isRTL ? 'امتحان کا عنوان ضروری ہے۔' : 'Exam title is required.';
    } elseif (empty($examDate)) {
        $error = $isRTL ? 'امتحان کی تاریخ ضروری ہے۔' : 'Exam date is required.';
    } else {
        if ($editMode) {
            $stmt = $conn->prepare("
                UPDATE exams 
                SET title = ?, exam_date = ?, max_marks = ?, passing_marks = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssiii", $title, $examDate, $maxMarks, $passingMarks, $exam['id']);
            
            if ($stmt->execute()) {
                $success = $isRTL ? 'امتحان کامیابی سے اپ ڈیٹ ہو گیا۔' : 'Exam updated successfully.';
            } else {
                $error = $isRTL ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
            }
        } else {
            $stmt = $conn->prepare("
                INSERT INTO exams (title, exam_date, max_marks, passing_marks, status)
                VALUES (?, ?, ?, ?, 'upcoming')
            ");
            $stmt->bind_param("ssii", $title, $examDate, $maxMarks, $passingMarks);
            
            if ($stmt->execute()) {
                $success = $isRTL ? 'امتحان کامیابی سے بنایا گیا۔' : 'Exam created successfully.';
                $exam = [
                    'id' => '',
                    'title' => '',
                    'exam_date' => '',
                    'max_marks' => 100,
                    'passing_marks' => 40,
                    'status' => 'upcoming'
                ];
            } else {
                $error = $isRTL ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
            }
        }
    }
}

// Fetch all exams for list view
$exams = [];
if (!$editMode && !isset($_GET['id'])) {
    $result = $conn->query("SELECT * FROM exams ORDER BY exam_date DESC");
    while ($row = $result->fetch_assoc()) {
        $exams[] = $row;
    }
}
?>

<div class="container py-4">
    <?php if (!$editMode && !isset($_GET['id'])): ?>
    <!-- List View -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title"><?php echo t('exams'); ?></h2>
            <p class="greeting"><?php echo t('assalam_alaikum'); ?></p>
        </div>
        <?php if (hasRole('admin') || hasRole('mumtahin')): ?>
        <a href="exams_manage.php?action=add" class="btn btn-primary-custom">
            <i class="bi bi-plus-lg me-2"></i><?php echo t('add_new'); ?>
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Exams Cards -->
    <div class="row g-4">
        <?php foreach ($exams as $e): 
            $statusClass = $e['status'] === 'finalized' ? 'success' : ($e['status'] === 'ongoing' ? 'warning' : 'info');
            $statusIcon = $e['status'] === 'finalized' ? 'check-circle-fill' : ($e['status'] === 'ongoing' ? 'pencil-square' : 'calendar');
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($e['title']); ?></h5>
                        <span class="badge bg-<?php echo $statusClass; ?>">
                            <i class="bi bi-<?php echo $statusIcon; ?>"></i>
                            <?php echo ucfirst($e['status']); ?>
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <p class="mb-1">
                            <i class="bi bi-calendar-event me-2 text-muted"></i>
                            <?php echo date('M d, Y', strtotime($e['exam_date'])); ?>
                        </p>
                        <p class="mb-1">
                            <i class="bi bi-award me-2 text-muted"></i>
                            <?php echo $e['max_marks']; ?> <?php echo $isRTL ? 'کل نمبر' : 'Max Marks'; ?>
                        </p>
                        <p class="mb-0">
                            <i class="bi bi-check-circle me-2 text-muted"></i>
                            <?php echo $e['passing_marks']; ?> <?php echo $isRTL ? 'پاسنگ نمبر' : 'Passing Marks'; ?>
                        </p>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <?php if (hasRole('admin') || hasRole('mumtahin')): ?>
                        <a href="exams_manage.php?id=<?php echo $e['id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($e['status'] !== 'finalized' && (hasRole('admin') || hasRole('mumtahin'))): ?>
                        <a href="exam_marks_entry.php?exam_id=<?php echo $e['id']; ?>" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-pencil-square"></i> <?php echo t('marks_entry'); ?>
                        </a>
                        <?php endif; ?>
                        <?php if ($e['status'] === 'ongoing' && (hasRole('admin') || hasRole('mumtahin'))): ?>
                        <a href="exam_finalize.php?exam_id=<?php echo $e['id']; ?>" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-lock"></i> <?php echo t('finalize'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($exams)): ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-3 text-muted"><?php echo t('no_data_found'); ?></p>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <!-- Add/Edit Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="mb-4">
                <h2 class="page-title"><?php echo $editMode ? t('edit') : t('add_new'); ?> <?php echo t('exams'); ?></h2>
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
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label"><?php echo $isRTL ? 'امتحان کا عنوان' : 'Exam Title'; ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($exam['title']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><?php echo $isRTL ? 'امتحان کی تاریخ' : 'Exam Date'; ?> <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="exam_date" value="<?php echo $exam['exam_date']; ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><?php echo $isRTL ? 'کل نمبر' : 'Max Marks'; ?></label>
                                <input type="number" class="form-control" name="max_marks" value="<?php echo $exam['max_marks']; ?>" min="1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><?php echo $isRTL ? 'پاسنگ نمبر' : 'Passing Marks'; ?></label>
                                <input type="number" class="form-control" name="passing_marks" value="<?php echo $exam['passing_marks']; ?>" min="1">
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="bi bi-check-lg me-2"></i><?php echo t('save'); ?>
                            </button>
                            <a href="exams_manage.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-2"></i><?php echo t('cancel'); ?>
                            </a>
                            <?php if ($editMode): ?>
                            <button type="submit" name="action" value="delete" class="btn btn-danger ms-auto" onclick="return confirmDelete()">
                                <i class="bi bi-trash me-2"></i><?php echo t('delete'); ?>
                            </button>
                            <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
