<?php
require_once 'includes/header.php';
requireLogin();

$success = '';
$error = '';
$editMode = false;
$student = [
    'id' => '',
    'name' => '',
    'roll_number' => '',
    'halaqa_id' => '',
    'shuba' => '',
    'gender' => 'baneen',
    'date_of_birth' => '',
    'join_date' => date('Y-m-d'),
    'father_name' => '',
    'contact_number' => '',
    'address' => '',
    'is_mumayyiz' => 0,
    'status' => 'active'
];

// Fetch halaqaat for dropdown
$halaqaat = [];
$result = $conn->query("SELECT id, name, gender FROM halaqaat WHERE status = 'active' ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $halaqaat[] = $row;
}

// Edit mode
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();
        $editMode = true;
    }
}

// Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id = intval($_POST['student_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE students SET status = 'inactive' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header('Location: students_manage.php');
        exit;
    }
    
    $name = trim($_POST['name'] ?? '');
    $rollNumber = trim($_POST['roll_number'] ?? '');
    $halaqaId = $_POST['halaqa_id'] ?: null;
    $shuba = trim($_POST['shuba'] ?? '');
    $gender = $_POST['gender'] ?? 'baneen';
    $dateOfBirth = $_POST['date_of_birth'] ?: null;
    $joinDate = $_POST['join_date'] ?: date('Y-m-d');
    $fatherName = trim($_POST['father_name'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $isMumayyiz = isset($_POST['is_mumayyiz']) ? 1 : 0;
    
    if (empty($name)) {
        $error = $isRTL ? 'نام ضروری ہے۔' : 'Name is required.';
    } elseif (empty($shuba)) {
        $error = $isRTL ? 'شعبہ ضروری ہے۔' : 'Shuba is required.';
    } else {
        if ($editMode) {
            $stmt = $conn->prepare("
                UPDATE students 
                SET name = ?, roll_number = ?, halaqa_id = ?, shuba = ?, gender = ?, 
                    date_of_birth = ?, join_date = ?, father_name = ?, contact_number = ?, 
                    address = ?, is_mumayyiz = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssisssssssii", $name, $rollNumber, $halaqaId, $shuba, $gender, 
                $dateOfBirth, $joinDate, $fatherName, $contactNumber, $address, $isMumayyiz, $student['id']);
            
            if ($stmt->execute()) {
                $success = $isRTL ? 'طالب علم کامیابی سے اپ ڈیٹ ہو گیا۔' : 'Student updated successfully.';
            } else {
                $error = $isRTL ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
            }
        } else {
            $stmt = $conn->prepare("
                INSERT INTO students (name, roll_number, halaqa_id, shuba, gender, date_of_birth, 
                    join_date, father_name, contact_number, address, is_mumayyiz, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->bind_param("ssisssssssi", $name, $rollNumber, $halaqaId, $shuba, $gender, 
                $dateOfBirth, $joinDate, $fatherName, $contactNumber, $address, $isMumayyiz);
            
            if ($stmt->execute()) {
                $success = $isRTL ? 'طالب علم کامیابی سے شامل ہو گیا۔' : 'Student added successfully.';
                $student = [
                    'id' => '',
                    'name' => '',
                    'roll_number' => '',
                    'halaqa_id' => '',
                    'shuba' => '',
                    'gender' => 'baneen',
                    'date_of_birth' => '',
                    'join_date' => date('Y-m-d'),
                    'father_name' => '',
                    'contact_number' => '',
                    'address' => '',
                    'is_mumayyiz' => 0,
                    'status' => 'active'
                ];
            } else {
                $error = $isRTL ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
            }
        }
    }
}

// Fetch all students for list view
$students = [];
if (!$editMode && !isset($_GET['id'])) {
    $query = "
        SELECT s.*, h.name as halaqa_name
        FROM students s
        LEFT JOIN halaqaat h ON s.halaqa_id = h.id
        WHERE s.status = 'active'
        ORDER BY s.name
    ";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>

<div class="container py-4">
    <?php if (!$editMode && !isset($_GET['id'])): ?>
    <!-- List View -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title"><?php echo t('students'); ?></h2>
            <p class="greeting"><?php echo t('assalam_alaikum'); ?></p>
        </div>
        <a href="students_manage.php?action=add" class="btn btn-primary-custom">
            <i class="bi bi-plus-lg me-2"></i><?php echo t('add_new'); ?>
        </a>
    </div>
    
    <!-- Search -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control search-box" id="studentSearch" placeholder="<?php echo $isRTL ? 'طلباء تلاش کریں...' : 'Search students...'; ?>">
            </div>
        </div>
    </div>
    
    <!-- Students Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="studentsTable">
                    <thead>
                        <tr>
                            <th><?php echo t('name'); ?></th>
                            <th><?php echo t('shuba'); ?></th>
                            <th><?php echo t('halaqaat'); ?></th>
                            <th><?php echo t('gender'); ?></th>
                            <th><?php echo t('mumayyiz'); ?></th>
                            <th><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                        <tr class="student-item">
                            <td>
                                <strong><?php echo htmlspecialchars($s['name']); ?></strong>
                                <?php if ($s['roll_number']): ?>
                                <br><small class="text-muted">#<?php echo htmlspecialchars($s['roll_number']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($s['shuba']); ?></td>
                            <td><?php echo htmlspecialchars($s['halaqa_name'] ?: '-'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $s['gender'] === 'baneen' ? 'primary' : 'danger'; ?>">
                                    <?php echo t($s['gender']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($s['is_mumayyiz']): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="students_manage.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="report_student.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-info">
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
    <div class="text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-3 text-muted"><?php echo t('no_data_found'); ?></p>
    </div>
    <?php endif; ?>
    
    <script>
    setupSearch('studentSearch', '#studentsTable', '.student-item', ['td']);
    </script>
    
    <?php else: ?>
    <!-- Add/Edit Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="mb-4">
                <h2 class="page-title"><?php echo $editMode ? t('edit') : t('add_new'); ?> <?php echo t('students'); ?></h2>
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
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo $isRTL ? 'رول نمبر' : 'Roll Number'; ?></label>
                                <input type="text" class="form-control" name="roll_number" value="<?php echo htmlspecialchars($student['roll_number']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('shuba'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="shuba" value="<?php echo htmlspecialchars($student['shuba']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('halaqaat'); ?></label>
                                <select class="form-select" name="halaqa_id">
                                    <option value=""><?php echo $isRTL ? '-- حلقہ منتخب کریں --' : '-- Select Halaqa --'; ?></option>
                                    <?php foreach ($halaqaat as $h): ?>
                                    <option value="<?php echo $h['id']; ?>" <?php echo $student['halaqa_id'] == $h['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($h['name']); ?> (<?php echo t($h['gender']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('gender'); ?></label>
                                <select class="form-select" name="gender">
                                    <option value="baneen" <?php echo $student['gender'] === 'baneen' ? 'selected' : ''; ?>><?php echo t('baneen'); ?></option>
                                    <option value="banaat" <?php echo $student['gender'] === 'banaat' ? 'selected' : ''; ?>><?php echo t('banaat'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo $isRTL ? 'تاریخ پیدائش' : 'Date of Birth'; ?></label>
                                <input type="date" class="form-control" name="date_of_birth" value="<?php echo $student['date_of_birth']; ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo $isRTL ? 'شمولیت کی تاریخ' : 'Join Date'; ?></label>
                                <input type="date" class="form-control" name="join_date" value="<?php echo $student['join_date']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo $isRTL ? 'والد کا نام' : 'Father Name'; ?></label>
                                <input type="text" class="form-control" name="father_name" value="<?php echo htmlspecialchars($student['father_name']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo $isRTL ? 'رابطہ نمبر' : 'Contact Number'; ?></label>
                                <input type="tel" class="form-control" name="contact_number" value="<?php echo htmlspecialchars($student['contact_number']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_mumayyiz" id="is_mumayyiz" <?php echo $student['is_mumayyiz'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_mumayyiz">
                                        <i class="bi bi-star-fill text-warning"></i> <?php echo t('mumayyiz'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label"><?php echo t('address'); ?></label>
                            <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($student['address']); ?></textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="bi bi-check-lg me-2"></i><?php echo t('save'); ?>
                            </button>
                            <a href="students_manage.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-2"></i><?php echo t('cancel'); ?>
                            </a>
                            <?php if ($editMode): ?>
                            <button type="submit" name="action" value="delete" class="btn btn-danger ms-auto" onclick="return confirmDelete()">
                                <i class="bi bi-trash me-2"></i><?php echo t('delete'); ?>
                            </button>
                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
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
