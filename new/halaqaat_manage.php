<?php
require_once 'includes/header.php';
requireRole('admin');

$success = '';
$error = '';
$editMode = false;
$halaqa = [
    'id' => '',
    'name' => '',
    'teacher_id' => '',
    'gender' => 'baneen',
    'time_slot' => 'subah',
    'location' => '',
    'status' => 'active'
];

// Fetch teachers for dropdown
$teachers = [];
$result = $conn->query("SELECT id, name, role FROM users WHERE role IN ('ustaaz', 'ustadah') AND status = 'active' ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}

// Edit mode
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM halaqaat WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $halaqa = $result->fetch_assoc();
        $editMode = true;
    }
}

// Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $teacherId = $_POST['teacher_id'] ?: null;
    $gender = $_POST['gender'] ?? 'baneen';
    $timeSlot = $_POST['time_slot'] ?? 'subah';
    $location = trim($_POST['location'] ?? '');
    
    if (empty($name)) {
        $error = $isRTL ? 'حلقہ کا نام ضروری ہے۔' : 'Halaqa name is required.';
    } else {
        if ($editMode) {
            // Update existing
            $stmt = $conn->prepare("
                UPDATE halaqaat 
                SET name = ?, teacher_id = ?, gender = ?, time_slot = ?, location = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sisssi", $name, $teacherId, $gender, $timeSlot, $location, $halaqa['id']);
            
            if ($stmt->execute()) {
                $success = $isRTL ? 'حلقہ کامیابی سے اپ ڈیٹ ہو گیا۔' : 'Halaqa updated successfully.';
            } else {
                $error = $isRTL ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
            }
        } else {
            // Create new
            $stmt = $conn->prepare("
                INSERT INTO halaqaat (name, teacher_id, gender, time_slot, location, status)
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            $stmt->bind_param("sisss", $name, $teacherId, $gender, $timeSlot, $location);
            
            if ($stmt->execute()) {
                $success = $isRTL ? 'حلقہ کامیابی سے بنایا گیا۔' : 'Halaqa created successfully.';
                // Reset form
                $halaqa = [
                    'id' => '',
                    'name' => '',
                    'teacher_id' => '',
                    'gender' => 'baneen',
                    'time_slot' => 'subah',
                    'location' => '',
                    'status' => 'active'
                ];
            } else {
                $error = $isRTL ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
            }
        }
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Page Header -->
            <div class="mb-4">
                <h2 class="page-title"><?php echo $editMode ? t('edit') : t('add_new'); ?> <?php echo t('halaqaat'); ?></h2>
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
            
            <!-- Form Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($halaqa['name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('teacher'); ?></label>
                                <select class="form-select" name="teacher_id">
                                    <option value=""><?php echo $isRTL ? '-- استاد منتخب کریں --' : '-- Select Teacher --'; ?></option>
                                    <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>" <?php echo $halaqa['teacher_id'] == $teacher['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($teacher['name']); ?> (<?php echo t($teacher['role']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('gender'); ?> <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="gender_baneen" value="baneen" <?php echo $halaqa['gender'] === 'baneen' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="gender_baneen">
                                            <i class="bi bi-gender-male"></i> <?php echo t('baneen'); ?>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="gender_banaat" value="banaat" <?php echo $halaqa['gender'] === 'banaat' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="gender_banaat">
                                            <i class="bi bi-gender-female"></i> <?php echo t('banaat'); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('time'); ?> <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="time_slot" id="time_subah" value="subah" <?php echo $halaqa['time_slot'] === 'subah' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="time_subah">
                                            <i class="bi bi-sunrise"></i> <?php echo t('subah'); ?>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="time_slot" id="time_asr" value="asr" <?php echo $halaqa['time_slot'] === 'asr' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="time_asr">
                                            <i class="bi bi-sunset"></i> <?php echo t('asr'); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label"><?php echo $isRTL ? 'مقام' : 'Location'; ?></label>
                            <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($halaqa['location'] ?? ''); ?>" placeholder="<?php echo $isRTL ? 'حلقہ کا مقام' : 'Halaqa location'; ?>">
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="bi bi-check-lg me-2"></i><?php echo t('save'); ?>
                            </button>
                            <a href="halaqaat_list.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-2"></i><?php echo t('cancel'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($editMode): ?>
            <!-- Students in this Halaqa -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i><?php echo $isRTL ? 'اس حلقہ کے طلباء' : 'Students in this Halaqa'; ?></h5>
                </div>
                <div class="card-body">
                    <?php
                    $students = [];
                    $stmt = $conn->prepare("SELECT * FROM students WHERE halaqa_id = ? AND status = 'active' ORDER BY name");
                    $stmt->bind_param("i", $halaqa['id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $students[] = $row;
                    }
                    ?>
                    
                    <?php if (!empty($students)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><?php echo t('name'); ?></th>
                                    <th><?php echo t('shuba'); ?></th>
                                    <th><?php echo t('mumayyiz'); ?></th>
                                    <th><?php echo t('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['shuba']); ?></td>
                                    <td>
                                        <?php if ($student['is_mumayyiz']): ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> <?php echo t('mumayyiz'); ?></span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="students_manage.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center mb-0"><?php echo $isRTL ? 'اس حلقہ میں کوئی طالب علم نہیں۔' : 'No students in this halaqa.'; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
