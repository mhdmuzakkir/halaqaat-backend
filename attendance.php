<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
if ($_SESSION['role'] !== 'ustaaz' && $_SESSION['role'] !== 'ustadah' && $_SESSION['role'] !== 'admin') {
  header("Location: dashboard_admin.php");
  exit;
}

$teacherId = $_SESSION['user_id'];
$lang = get_language();
$tr = get_translations($lang);

$success = '';
$error = '';

// Get teacher's halaqaat
$halaqaat = [];
if ($_SESSION['role'] === 'admin') {
  $result = $conn->query("SELECT id, name_ur, gender FROM halaqaat WHERE state = 'active' ORDER BY name_ur");
} else {
  $stmt = $conn->prepare("SELECT id, name_ur, gender FROM halaqaat WHERE ustaaz_user_id = ? AND state = 'active' ORDER BY name_ur");
  $stmt->bind_param("i", $teacherId);
  $stmt->execute();
  $result = $stmt->get_result();
}
while ($row = $result->fetch_assoc()) {
  $halaqaat[] = $row;
}

$selectedHalaqa = isset($_GET['halaqa_id']) ? intval($_GET['halaqa_id']) : ($halaqaat[0]['id'] ?? 0);
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Get students for selected halaqa
$students = [];
if ($selectedHalaqa) {
  $stmt = $conn->prepare("SELECT * FROM students WHERE halaqa_id = ? AND status = 'active' ORDER BY full_name_ur");
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
  
  $success = $lang === 'ur' ? 'حاضری کامیابی سے محفوظ ہو گئی۔' : 'Attendance saved successfully.';
  
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

include __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?>
<div class="msg mb-3"><?php echo h($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="msg msgError mb-3"><?php echo h($error); ?></div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
  <div class="cardBody">
    <form method="GET" action="" class="row g-3">
      <div class="col-md-5">
        <label class="formLabel"><?php echo h($tr['halaqa']); ?></label>
        <select class="formSelect" name="halaqa_id" onchange="this.form.submit()">
          <?php foreach ($halaqaat as $h): ?>
          <option value="<?php echo $h['id']; ?>" <?php echo $selectedHalaqa == $h['id'] ? 'selected' : ''; ?>>
            <?php echo h($h['name_ur']); ?> (<?php echo h($tr[$h['gender']]); ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-5">
        <label class="formLabel"><?php echo h($tr['date']); ?></label>
        <input type="date" class="formInput" name="date" value="<?php echo $selectedDate; ?>" onchange="this.form.submit()" />
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <a href="attendance.php" class="btn" style="background: #e9ecef; color: #333; width: 100%;">
          <i class="bi bi-arrow-clockwise"></i> <?php echo $lang === 'ur' ? 'ری سیٹ' : 'Reset'; ?>
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
  
  <div class="card mb-4">
    <div class="cardHeader d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="bi bi-calendar-check me-2"></i>
        <?php echo date('F d, Y', strtotime($selectedDate)); ?>
      </h5>
      <button type="submit" name="save_attendance" class="btn btnPrimary">
        <i class="bi bi-check-lg"></i> <?php echo h($tr['save']); ?>
      </button>
    </div>
    <div class="cardBody p-0">
      <div class="tableContainer">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th><?php echo h($tr['name']); ?></th>
              <th><?php echo h($tr['shuba']); ?></th>
              <th><?php echo h($tr['mumayyaz']); ?></th>
              <th><?php echo h($tr['attendance']); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $index => $student): 
              $currentStatus = $attendance[$student['id']] ?? 'present';
            ?>
            <tr>
              <td><?php echo $index + 1; ?></td>
              <td><?php echo h($student['full_name_ur']); ?></td>
              <td><?php echo h($tr[$student['shuba']]); ?></td>
              <td>
                <?php if ($student['mumayyaz']): ?>
                  <span class="tag green"><i class="bi bi-star-fill"></i></span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="btn-group" role="group">
                  <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" id="present_<?php echo $student['id']; ?>" value="present" <?php echo $currentStatus === 'present' ? 'checked' : ''; ?>>
                  <label class="btn btn-outline-success" for="present_<?php echo $student['id']; ?>">
                    <i class="bi bi-check-circle"></i> <?php echo h($tr['present']); ?>
                  </label>
                  
                  <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" id="absent_<?php echo $student['id']; ?>" value="absent" <?php echo $currentStatus === 'absent' ? 'checked' : ''; ?>>
                  <label class="btn btn-outline-danger" for="absent_<?php echo $student['id']; ?>">
                    <i class="bi bi-x-circle"></i> <?php echo h($tr['absent']); ?>
                  </label>
                  
                  <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" id="late_<?php echo $student['id']; ?>" value="late" <?php echo $currentStatus === 'late' ? 'checked' : ''; ?>>
                  <label class="btn btn-outline-warning" for="late_<?php echo $student['id']; ?>">
                    <i class="bi bi-clock"></i> <?php echo h($tr['late']); ?>
                  </label>
                  
                  <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" id="excused_<?php echo $student['id']; ?>" value="excused" <?php echo $currentStatus === 'excused' ? 'checked' : ''; ?>>
                  <label class="btn btn-outline-info" for="excused_<?php echo $student['id']; ?>">
                    <i class="bi bi-file-text"></i> <?php echo h($tr['excused']); ?>
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
<div class="card">
  <div class="cardHeader">
    <span><i class="bi bi-calendar-month me-2"></i><?php echo $lang === 'ur' ? 'ماہانہ خلاصہ' : 'Monthly Summary'; ?> (<?php echo date('F Y', strtotime($selectedDate)); ?>)</span>
  </div>
  <div class="cardBody">
    <?php if (!empty($attendanceSummary)): ?>
    <div class="tableContainer">
      <table class="table table-sm">
        <thead>
          <tr>
            <th><?php echo h($tr['date']); ?></th>
            <th><?php echo $lang === 'ur' ? 'کل طلباء' : 'Total Students'; ?></th>
            <th><?php echo h($tr['present']); ?></th>
            <th><?php echo $lang === 'ur' ? 'غیر حاضر' : 'Absent'; ?></th>
            <th><?php echo $lang === 'ur' ? 'حاضری %' : 'Attendance %'; ?></th>
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
              <span class="tag <?php echo $percentage >= 80 ? 'green' : ($percentage >= 60 ? 'orange' : 'pink'); ?>">
                <?php echo $percentage; ?>%
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <p class="text-muted text-center mb-0"><?php echo $lang === 'ur' ? 'اس مہینے کوئی حاضری ریکارڈ نہیں۔' : 'No attendance records for this month.'; ?></p>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($selectedHalaqa): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle-fill me-2"></i>
  <?php echo $lang === 'ur' ? 'اس حلقہ میں کوئی طالب علم نہیں۔' : 'No students in this halaqa.'; ?>
</div>
<?php else: ?>
<div class="alert alert-warning">
  <i class="bi bi-exclamation-triangle-fill me-2"></i>
  <?php echo $lang === 'ur' ? 'آپ کو ابھی تک کوئی حلقہ تفویض نہیں کیا گیا۔' : 'No halaqaat have been assigned to you yet.'; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
