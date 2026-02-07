<?php
// exam_finalize.php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
if (($_SESSION['role'] ?? '') !== 'admin' && ($_SESSION['role'] ?? '') !== 'mumtahin') {
  header("Location: dashboard_admin.php");
  exit;
}

$lang = get_language();
$tr = get_translations($lang);

$success = '';
$error  = '';

// -----------------------------
// Finalize exam (POST) - SAFE
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize_exam'])) {
  if (!ob_get_level()) {
    ob_start();
  } // prevent "headers already sent" issues

  $examId = intval($_POST['exam_id'] ?? 0);

  if ($examId <= 0) {
    $error = $lang === 'ur' ? 'غلط امتحان منتخب ہوا ہے۔' : 'Invalid exam selected.';
  } else {
    try {
      $conn->begin_transaction();

      // Finalize ONLY draft rows
      $stmt = $conn->prepare("UPDATE exam_results SET status='finalized' WHERE exam_id=? AND status='draft'");
      $stmt->bind_param("i", $examId);
      $stmt->execute();
      $stmt->close();

      // Finalize exam itself (only if not already finalized)
      $stmt = $conn->prepare("UPDATE exams SET status='finalized' WHERE id=? AND status<>'finalized'");
      $stmt->bind_param("i", $examId);
      $stmt->execute();
      $stmt->close();

      $conn->commit();

      header('Location: exam_finalize.php?success=1');
      exit;
    } catch (Throwable $e) {
      try {
        $conn->rollback();
      } catch (Throwable $t) { /* ignore */
      }
      $error = $lang === 'ur' ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
      // If you want to debug, temporarily use:
      // $error = $e->getMessage();
    }
  }
}

if (isset($_GET['success'])) {
  $success = $lang === 'ur' ? 'امتحان کامیابی سے حتمی شکل میں آ گیا۔' : 'Exam finalized successfully.';
}

// -----------------------------
// Get ongoing exams (safe)
// -----------------------------
$exams = [];
try {
  $result = $conn->query("
    SELECT e.*, COUNT(er.id) AS entries_count
    FROM exams e
    LEFT JOIN exam_results er ON er.exam_id = e.id AND er.status = 'draft'
    WHERE e.status IN ('ongoing', 'upcoming')
    GROUP BY e.id
    HAVING entries_count > 0
    ORDER BY e.exam_date DESC
  ");
  while ($row = $result->fetch_assoc()) {
    $exams[] = $row;
  }
} catch (Throwable $e) {
  $error = $lang === 'ur' ? 'کچھ غلط ہو گیا۔' : 'Something went wrong.';
}

$selectedExam = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : ($exams[0]['id'] ?? 0);
$examDetails  = null;
$results      = [];
$examType     = 'qaida';

if ($selectedExam) {
  // Get exam details
  try {
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->bind_param("i", $selectedExam);
    $stmt->execute();
    $examDetails = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  } catch (Throwable $e) {
    $examDetails = null;
  }

  // Determine exam type from title
  $examTitle = strtolower($examDetails['title'] ?? '');
  $examType = 'qaida';
  if (strpos($examTitle, 'حفظ') !== false || strpos($examTitle, 'hifz') !== false) {
    $examType = 'hifz';
  } elseif (strpos($examTitle, 'ناظرہ') !== false || strpos($examTitle, 'nazira') !== false) {
    $examType = 'nazira';
  }

  // Get all results for this exam - grouped by halaqa
  try {
    $stmt = $conn->prepare("
      SELECT er.*,
             s.full_name_ur,
             s.shuba,
             s.mumayyaz AS student_mumayyaz,
             h.name_ur AS halaqa_name,
             h.gender  AS halaqa_gender
      FROM exam_results er
      JOIN students s ON er.student_id = s.id
      JOIN halaqaat h ON s.halaqa_id = h.id
      WHERE er.exam_id = ? AND er.status = 'draft'
      ORDER BY h.gender, h.name_ur, er.percentage DESC
    ");
    $stmt->bind_param("i", $selectedExam);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $results[] = $row;
    }
    $stmt->close();
  } catch (Throwable $e) {
    $results = [];
  }
}

include __DIR__ . '/includes/header.php';
?>

<!-- Exam Selector -->
<div class="card mb-4">
  <div class="cardBody">
    <form method="GET" action="" class="row g-3">
      <div class="col-md-12">
        <label class="formLabel"><?php echo $lang === 'ur' ? 'امتحان منتخب کریں' : 'Select Exam'; ?></label>
        <select class="formSelect" name="exam_id" onchange="this.form.submit()">
          <?php if (empty($exams)): ?>
            <option value=""><?php echo $lang === 'ur' ? 'کوئی حتمی کرنے کے لیے امتحان نہیں' : 'No exams pending finalization'; ?></option>
          <?php else: ?>
            <?php foreach ($exams as $exam): ?>
              <option value="<?php echo (int)$exam['id']; ?>" <?php echo $selectedExam == $exam['id'] ? 'selected' : ''; ?>>
                <?php echo h($exam['title']); ?>
                (<?php echo date('M d, Y', strtotime($exam['exam_date'])); ?>)
                - <?php echo (int)$exam['entries_count']; ?> <?php echo $lang === 'ur' ? 'اندراجات' : 'entries'; ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>
    </form>
  </div>
</div>

<?php if ($success): ?>
  <div class="msg mb-3"><?php echo h($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="msg msgError mb-3"><?php echo h($error); ?></div>
<?php endif; ?>

<?php if ($selectedExam && $examDetails && !empty($results)):
  $maxNisaab = ($examType === 'qaida') ? 70 : 60;
?>

  <!-- Exam Info -->
  <div class="card mb-4" style="background: var(--secondary); color: #fff;">
    <div class="cardBody">
      <div class="row align-items-center">
        <div class="col-md-4">
          <strong><i class="bi bi-file-text me-2"></i><?php echo h($examDetails['title']); ?></strong>
        </div>
        <div class="col-md-4">
          <i class="bi bi-calendar-event me-2"></i><?php echo date('M d, Y', strtotime($examDetails['exam_date'])); ?>
        </div>
        <div class="col-md-4 text-md-end">
          <span class="tag" style="background: #fff; color: var(--secondary);">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <?php echo $lang === 'ur' ? 'حتمی شکل کے لیے تیار' : 'Ready for Finalization'; ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Results Summary -->
  <?php
  $totalStudents  = count($results);
  $passedStudents = count(array_filter($results, fn($r) => (float)$r['percentage'] >= 40));
  $avgPercentage  = $totalStudents ? (array_sum(array_map(fn($r) => (float)$r['percentage'], $results)) / $totalStudents) : 0;
  $mumtaazStudents = count(array_filter($results, fn($r) => (float)$r['percentage'] >= 85));
  ?>

  <div class="row g-4 mb-4">
    <div class="col-md-3">
      <div class="statCard text-center">
        <div class="statIcon" style="background: rgba(15, 45, 61, 0.2);"><i class="bi bi-people-fill" style="color: #0f2d3d;"></i></div>
        <div class="statValue"><?php echo $totalStudents; ?></div>
        <div class="statLabel"><?php echo $lang === 'ur' ? 'کل طلباء' : 'Total Students'; ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="statCard text-center" style="background: var(--primary); color: #fff;">
        <div class="statIcon mx-auto" style="background: rgba(255,255,255,0.2);"><i class="bi bi-trophy-fill"></i></div>
        <div class="statValue"><?php echo $mumtaazStudents; ?></div>
        <div class="statLabel"><?php echo $lang === 'ur' ? 'ممتاز' : 'Mumtaaz'; ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="statCard text-center" style="background: var(--secondary); color: #fff;">
        <div class="statIcon mx-auto" style="background: rgba(255,255,255,0.2);"><i class="bi bi-check-circle-fill"></i></div>
        <div class="statValue"><?php echo $passedStudents; ?></div>
        <div class="statLabel"><?php echo $lang === 'ur' ? 'پاس' : 'Passed'; ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="statCard text-center" style="background: var(--accent); color: #fff;">
        <div class="statIcon mx-auto" style="background: rgba(255,255,255,0.2);"><i class="bi bi-percent"></i></div>
        <div class="statValue"><?php echo round($avgPercentage, 1); ?>%</div>
        <div class="statLabel"><?php echo $lang === 'ur' ? 'اوسط' : 'Average'; ?></div>
      </div>
    </div>
  </div>

  <!-- Results Table -->
  <div class="card mb-4">
    <div class="cardHeader">
      <span><i class="bi bi-table me-2"></i><?php echo $lang === 'ur' ? 'امتحانی نتائج' : 'Exam Results'; ?></span>
    </div>
    <div class="cardBody p-0">
      <div class="tableContainer" style="overflow-x: auto;">
        <table class="table mb-0" style="min-width: 800px;">
          <thead>
            <tr style="background: var(--primary); color: #fff;">
              <th><?php echo $lang === 'ur' ? 'درجہ' : 'Rank'; ?></th>
              <th><?php echo h($tr['name'] ?? 'Name'); ?></th>
              <th><?php echo h($tr['halaqa'] ?? 'Halaqa'); ?></th>
              <th><?php echo h($tr['shuba'] ?? 'Shuba'); ?></th>
              <th><?php echo $lang === 'ur' ? 'نمبر' : 'Marks'; ?></th>
              <th>%</th>
              <th><?php echo h($tr['taqdeer'] ?? 'Result'); ?></th>
              <th><?php echo h($tr['mumayyaz'] ?? 'Mumayyaz'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php
            $rank = 1;
            $currentHalaqa = '';
            foreach ($results as $r):
              if ($currentHalaqa !== ($r['halaqa_name'] ?? '')):
                $currentHalaqa = $r['halaqa_name'] ?? '';
                $rank = 1;
                $genderLabel = $tr[$r['halaqa_gender']] ?? ($r['halaqa_gender'] ?? '');
            ?>
                <tr style="background: rgba(170, 129, 94, 0.15);">
                  <td colspan="8" class="fw-bold" style="padding: 8px 16px;">
                    <i class="bi bi-building me-2"></i><?php echo h($currentHalaqa); ?> (<?php echo h($genderLabel); ?>)
                  </td>
                </tr>
              <?php
              endif;

              $taqdeer = $r['taqdeer'] ?? $r['remarks'] ?? '-';
              $shubaLabel = $tr[$r['shuba']] ?? ($r['shuba'] ?? '-');
              ?>
              <tr>
                <td><span class="tag">#<?php echo $rank++; ?></span></td>
                <td>
                  <?php echo h($r['full_name_ur'] ?? ''); ?>
                  <?php if (!empty($r['student_mumayyaz'])): ?>
                    <span style="background: rgba(15,45,61,0.15); color: #0f2d3d; padding: 2px 6px; border-radius: 4px; font-size: 10px;"><i class="bi bi-star-fill"></i></span>
                  <?php endif; ?>
                </td>
                <td><?php echo h($r['halaqa_name'] ?? ''); ?></td>
                <td><?php echo h($shubaLabel); ?></td>
                <td class="text-center fw-bold"><?php echo h($r['marks_obtained'] ?? '0'); ?>/<?php echo h($r['max_marks'] ?? '0'); ?></td>
                <td class="text-center">
                  <span style="background: rgba(15,45,61,0.1); color: #0f2d3d; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                    <?php echo round((float)($r['percentage'] ?? 0), 1); ?>%
                  </span>
                </td>
                <td class="text-center fw-bold"><?php echo h($taqdeer); ?></td>
                <td class="text-center">
                  <?php if (!empty($r['mumayyaz'])): ?>
                    <span style="background: rgba(15,45,61,0.15); color: #0f2d3d; padding: 4px 8px; border-radius: 12px; font-size: 11px;"><i class="bi bi-star-fill"></i> <?php echo h($tr['mumayyaz'] ?? 'Mumayyaz'); ?></span>
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

  <!-- Finalize Button -->
  <div class="card">
    <div class="cardBody text-center p-4">
      <i class="bi bi-exclamation-triangle-fill" style="color: var(--secondary); font-size: 48px; margin-bottom: 16px; display: block;"></i>
      <h5 class="mb-3"><?php echo $lang === 'ur' ? 'کیا آپ اس امتحان کو حتمی شکل دینا چاہتے ہیں؟' : 'Are you sure you want to finalize this exam?'; ?></h5>
      <p class="text-muted mb-4">
        <?php echo $lang === 'ur' ? 'ایک بار حتمی شکل میں آنے کے بعد، آپ نمبر میں تبدیلی نہیں کر سکیں گے۔' : 'Once finalized, you will not be able to modify the marks.'; ?>
      </p>
      <form method="POST" action="" onsubmit="return confirm('<?php echo $lang === 'ur' ? 'کیا آپ واقعی حتمی شکل دینا چاہتے ہیں؟' : 'Are you sure you want to finalize?'; ?>');">
        <input type="hidden" name="exam_id" value="<?php echo (int)$selectedExam; ?>">
        <button type="submit" name="finalize_exam" class="btn btnSecondary btn-lg">
          <i class="bi bi-lock-fill me-2"></i><?php echo h($tr['finalize'] ?? 'Finalize'); ?> <?php echo $lang === 'ur' ? 'امتحان' : 'Exam'; ?>
        </button>
      </form>
    </div>
  </div>

<?php elseif ($selectedExam): ?>
  <div class="alert alert-info">
    <i class="bi bi-info-circle-fill me-2"></i>
    <?php echo $lang === 'ur' ? 'اس امتحان کے لیے کوئی نمبر درج نہیں کیے گئے۔' : 'No marks have been entered for this exam.'; ?>
    <a href="exam_marks_entry.php?exam_id=<?php echo (int)$selectedExam; ?>" class="alert-link">
      <?php echo $lang === 'ur' ? 'نمبر درج کریں' : 'Enter marks'; ?>
    </a>
  </div>
<?php else: ?>
  <div class="alert alert-info">
    <i class="bi bi-info-circle-fill me-2"></i>
    <?php echo $lang === 'ur' ? 'حتمی شکل کے لیے کوئی امتحان نہیں۔' : 'No exams pending finalization.'; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>