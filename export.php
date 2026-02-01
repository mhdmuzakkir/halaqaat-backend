<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$type = $_GET['type'] ?? '';

header('Content-Type: text/csv; charset=utf-8');

switch ($type) {
  case 'halaqa':
    $halaqaId = intval($_GET['id'] ?? 0);
    
    // Get halaqa details
    $stmt = $conn->prepare("
      SELECT h.*, u.full_name as ustaaz_name 
      FROM halaqaat h 
      LEFT JOIN users u ON h.ustaaz_user_id = u.id 
      WHERE h.id = ?
    ");
    $stmt->bind_param("i", $halaqaId);
    $stmt->execute();
    $halaqa = $stmt->get_result()->fetch_assoc();
    
    if (!$halaqa) {
      die('Halaqa not found');
    }
    
    header('Content-Disposition: attachment; filename="halaqa_' . $halaqaId . '_report.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Halaqa Info
    fputcsv($output, ['Halaqa Report']);
    fputcsv($output, ['Name', $halaqa['name_ur']]);
    fputcsv($output, ['Teacher', $halaqa['ustaaz_name']]);
    fputcsv($output, ['Gender', $halaqa['gender']]);
    fputcsv($output, ['Session', $halaqa['session']]);
    fputcsv($output, []);
    
    // Students
    fputcsv($output, ['Students']);
    fputcsv($output, ['#', 'Name', 'Shuba', 'Mumayyaz', 'Avg Percentage']);
    
    $stmt = $conn->prepare("
      SELECT s.*, AVG(er.percentage) as avg_percentage
      FROM students s
      LEFT JOIN exam_results er ON er.student_id = s.id AND er.status = 'finalized'
      WHERE s.halaqa_id = ? AND s.status = 'active'
      GROUP BY s.id
      ORDER BY s.full_name_ur
    ");
    $stmt->bind_param("i", $halaqaId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $index = 1;
    while ($student = $result->fetch_assoc()) {
      fputcsv($output, [
        $index++,
        $student['full_name_ur'],
        $student['shuba'],
        $student['mumayyaz'] ? 'Yes' : 'No',
        $student['avg_percentage'] ? round($student['avg_percentage'], 2) . '%' : '-'
      ]);
    }
    
    fclose($output);
    break;
    
  case 'student':
    $studentId = intval($_GET['id'] ?? 0);
    
    // Get student details
    $stmt = $conn->prepare("
      SELECT s.*, h.name_ur as halaqa_name
      FROM students s
      LEFT JOIN halaqaat h ON s.halaqa_id = h.id
      WHERE s.id = ?
    ");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    
    if (!$student) {
      die('Student not found');
    }
    
    header('Content-Disposition: attachment; filename="student_' . $studentId . '_report.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Student Info
    fputcsv($output, ['Student Report']);
    fputcsv($output, ['Name', $student['full_name_ur']]);
    fputcsv($output, ['Halaqa', $student['halaqa_name']]);
    fputcsv($output, ['Shuba', $student['shuba']]);
    fputcsv($output, ['Mumayyaz', $student['mumayyaz'] ? 'Yes' : 'No']);
    fputcsv($output, []);
    
    // Exam Results
    fputcsv($output, ['Exam Results']);
    fputcsv($output, ['Exam', 'Date', 'Marks Obtained', 'Max Marks', 'Percentage', 'Status', 'Remarks']);
    
    $stmt = $conn->prepare("
      SELECT er.*, e.title as exam_title, e.exam_date, e.passing_marks
      FROM exam_results er
      JOIN exams e ON er.exam_id = e.id
      WHERE er.student_id = ? AND er.status = 'finalized'
      ORDER BY e.exam_date DESC
    ");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($exam = $result->fetch_assoc()) {
      $isPassed = $exam['percentage'] >= $exam['passing_marks'];
      fputcsv($output, [
        $exam['exam_title'],
        $exam['exam_date'],
        $exam['marks_obtained'],
        $exam['max_marks'],
        round($exam['percentage'], 2) . '%',
        $isPassed ? 'Pass' : 'Fail',
        $exam['remarks']
      ]);
    }
    
    fclose($output);
    break;
    
  case 'exam':
    $examId = intval($_GET['exam_id'] ?? 0);
    
    // Get exam details
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    $exam = $stmt->get_result()->fetch_assoc();
    
    if (!$exam) {
      die('Exam not found');
    }
    
    header('Content-Disposition: attachment; filename="exam_' . $examId . '_results.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Exam Info
    fputcsv($output, ['Exam Results']);
    fputcsv($output, ['Title', $exam['title']]);
    fputcsv($output, ['Date', $exam['exam_date']]);
    fputcsv($output, ['Max Marks', $exam['max_marks']]);
    fputcsv($output, ['Passing Marks', $exam['passing_marks']]);
    fputcsv($output, []);
    
    // Results
    fputcsv($output, ['Rank', 'Student Name', 'Halaqa', 'Shuba', 'Marks', 'Percentage', 'Status', 'Remarks']);
    
    $stmt = $conn->prepare("
      SELECT er.*, s.full_name_ur, s.shuba, h.name_ur as halaqa_name, h.gender
      FROM exam_results er
      JOIN students s ON er.student_id = s.id
      JOIN halaqaat h ON s.halaqa_id = h.id
      WHERE er.exam_id = ? AND er.status = 'finalized'
      ORDER BY h.gender, er.percentage DESC
    ");
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rank = 1;
    $currentGender = '';
    while ($row = $result->fetch_assoc()) {
      if ($currentGender !== $row['gender']) {
        $currentGender = $row['gender'];
        $rank = 1;
        fputcsv($output, [strtoupper($currentGender)]);
      }
      $isPassed = $row['percentage'] >= $exam['passing_marks'];
      fputcsv($output, [
        $rank++,
        $row['full_name_ur'],
        $row['halaqa_name'],
        $row['shuba'],
        $row['marks_obtained'],
        round($row['percentage'], 2) . '%',
        $isPassed ? 'Pass' : 'Fail',
        $row['remarks']
      ]);
    }
    
    fclose($output);
    break;
    
  case 'progress':
    $halaqaId = intval($_GET['halaqa_id'] ?? 0);
    
    header('Content-Disposition: attachment; filename="progress_report.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['Progress Report']);
    fputcsv($output, ['Generated', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    fputcsv($output, ['Name', 'Shuba', 'Exams', 'Avg %', 'Attendance %', 'Mumayyaz']);
    
    $query = "
      SELECT s.*, 
             AVG(er.percentage) as avg_percentage,
             COUNT(er.id) as exams_count
      FROM students s
      LEFT JOIN exam_results er ON er.student_id = s.id AND er.status = 'finalized'
      WHERE s.status = 'active'
    ";
    if ($halaqaId) {
      $query .= " AND s.halaqa_id = $halaqaId";
    }
    $query .= " GROUP BY s.id ORDER BY s.full_name_ur";
    
    $result = $conn->query($query);
    
    while ($student = $result->fetch_assoc()) {
      // Get attendance
      $stmt = $conn->prepare("
        SELECT 
          COUNT(*) as total_days,
          SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days
        FROM attendance
        WHERE student_id = ?
      ");
      $stmt->bind_param("i", $student['id']);
      $stmt->execute();
      $attendance = $stmt->get_result()->fetch_assoc();
      $attendancePercentage = ($attendance['total_days'] > 0) 
        ? round(($attendance['present_days'] / $attendance['total_days']) * 100, 1) 
        : 0;
      
      fputcsv($output, [
        $student['full_name_ur'],
        $student['shuba'],
        $student['exams_count'],
        $student['avg_percentage'] ? round($student['avg_percentage'], 2) . '%' : '-',
        $attendancePercentage . '%',
        $student['mumayyaz'] ? 'Yes' : 'No'
      ]);
    }
    
    fclose($output);
    break;
    
  default:
    die('Invalid export type');
}
?>
