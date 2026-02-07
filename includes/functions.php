<?php
// Common functions for Kahaf Halaqaat

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function table_exists($conn, $table) {
    $t = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$t'");
    return ($res && $res->num_rows > 0);
}

function column_exists($conn, $table, $col) {
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($col);
    $res = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
    return ($res && $res->num_rows > 0);
}

function scalar_int($conn, $sql) {
    $res = $conn->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_row();
    return (int)($row[0] ?? 0);
}

function scalar_str($conn, $sql) {
    $res = $conn->query($sql);
    if (!$res) return '';
    $row = $res->fetch_row();
    return (string)($row[0] ?? '');
}

function normalize_gender($g) {
    $g = strtolower(trim((string)$g));
    if (in_array($g, ['girl', 'girls', 'female', 'f', 'banaat'], true)) return 'banaat';
    return 'baneen';
}

function normalize_session($s) {
    $s = strtolower(trim((string)$s));
    if (in_array($s, ['asr', 'asar', 'evening'], true)) return 'asr';
    return 'subah';
}

function gender_label($tr, $g) {
    return normalize_gender($g) === 'banaat' ? $tr['banaat'] : $tr['baneen'];
}

function session_label($tr, $s) {
    return normalize_session($s) === 'asr' ? $tr['asr'] : $tr['subah'];
}

function state_label($tr, $st) {
    $st = strtolower(trim((string)$st));
    if ($st === 'stopped') return $tr['stopped'];
    if ($st === 'paused') return $tr['paused'];
    return $tr['active'];
}

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function require_role($role) {
    require_login();
    if (($_SESSION['role'] ?? '') !== $role) {
        header("Location: dashboard.php");
        exit;
    }
}

// Language helper
function get_language() {
    $allowedLang = ['ur', 'en'];
    if (isset($_GET['lang']) && in_array($_GET['lang'], $allowedLang, true)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    return $_SESSION['lang'] ?? 'ur';
}

// Translation helper
function get_translations($lang) {
    $T = [
        'ur' => [
            'app' => 'کہف حلقات',
            'dashboard' => 'ڈیش بورڈ',
            'greeting' => 'السلام عليكم ورحمة الله وبركاته',
            'login' => 'لاگ ان',
            'logout' => 'لاگ آؤٹ',
            'email' => 'ای میل',
            'password' => 'پاس ورڈ',
            'submit' => 'جمع کرائیں',
            'search' => 'تلاش کریں...',
            'save' => 'محفوظ کریں',
            'cancel' => 'منسوخ کریں',
            'edit' => 'ترمیم',
            'delete' => 'حذف',
            'add_new' => 'نیا شامل کریں',
            'view_all' => 'سب دیکھیں',
            'no_data' => 'کوئی ڈیٹا نہیں',
            
            // Navigation
            'nav_dashboard' => 'ڈیش بورڈ',
            'nav_halaqaat' => 'حلقات',
            'nav_students' => 'طلباء',
            'nav_management' => 'انتظامیہ',
            'nav_exams' => 'امتحانات',
            'nav_reports' => 'رپورٹس',
            'nav_settings' => 'ترتیبات',
            
            // Roles
            'admin' => 'منتظم',
            'mushrif' => 'مشرف',
            'ustaaz' => 'استاذ',
            'ustadah' => 'استاذہ',
            'mumtahin' => 'ممتحن',
            
            // User management
            'name' => 'نام',
            'role' => 'عہدہ',
            'phone' => 'فون',
            'export' => 'ایکسپورٹ',
            'date' => 'تاریخ',
            
            // Stats
            'total_halaqaat' => 'کل حلقات',
            'total_students' => 'کل طلباء',
            'total_mumayyizeen' => 'کل ممیّزین',
            'students' => 'طلباء',
            
            // Gender/Session
            'baneen' => 'بنین',
            'banaat' => 'بنات',
            'subah' => 'صبح',
            'asr' => 'عصر',
            'boys' => 'طلباء',
            'girls' => 'طالبات',
            
            // Status
            'active' => 'فعال',
            'inactive' => 'غیر فعال',
            'paused' => 'وقفہ',
            'stopped' => 'بند',
            
            // Shuba
            'shuba' => 'شعبہ',
            'qaida' => 'قائدہ',
            'nazira' => 'ناظرہ',
            'hifz' => 'حفظ',
            
            // Mumayyaz
            'mumayyaz' => 'ممیّز',
            'yes' => 'ہاں',
            'no' => 'نہیں',
            
            // Halaqa
            'halaqa' => 'حلقہ',
            'ustaaz' => 'استاد',
            'session' => 'سیشن',
            'group' => 'گروپ',
            'location' => 'مقام',
            
            // Dashboard sections
            'upcoming_exams' => 'آنے والے امتحانات',
            'students_by_shuba' => 'شعبہ کے مطابق طلباء',
            'students_by_gender' => 'جنس کے مطابق طلباء',
            'top_halaqa_girls' => 'سب سے زیادہ فیصد (بنات)',
            'top_halaqa_boys_subah' => 'سب سے زیادہ فیصد (بنین — صبح)',
            'top_halaqa_boys_asr' => 'سب سے زیادہ فیصد (بنین — عصر)',
            'mumtaaz_talaba' => 'ممتاز طالب علم',
            'score' => 'اسکور',
            'percentage' => 'فیصد',
            
            // Attendance
            'attendance' => 'حاضری',
            'present' => 'حاضر',
            'absent' => 'غیر حاضر',
            'late' => 'تاخیر',
            'excused' => 'معذور',
            
            // Exams
            'exam' => 'امتحان',
            'marks' => 'نمبر',
            'max_marks' => 'کل نمبر',
            'passing_marks' => 'پاسنگ نمبر',
            'remarks' => 'تبصرہ',
            'finalize' => 'حتمی کریں',
            
            // Progress
            'progress' => 'پیش رفت',
            'takhti' => 'تختی',
            'surah' => 'سورۃ',
        ],
        'en' => [
            'app' => 'Kahaf Halaqaat',
            'dashboard' => 'Dashboard',
            'greeting' => 'Assalamu Alaikum wa Rahmatullahi wa Barakatuhu',
            'login' => 'Login',
            'logout' => 'Logout',
            'email' => 'Email',
            'password' => 'Password',
            'submit' => 'Submit',
            'search' => 'Search...',
            'save' => 'Save',
            'cancel' => 'Cancel',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'add_new' => 'Add New',
            'view_all' => 'View All',
            'no_data' => 'No data available',
            
            // Navigation
            'nav_dashboard' => 'Dashboard',
            'nav_halaqaat' => 'Halaqaat',
            'nav_students' => 'Students',
            'nav_management' => 'Management',
            'nav_exams' => 'Exams',
            'nav_reports' => 'Reports',
            'nav_settings' => 'Settings',
            
            // Roles
            'admin' => 'Admin',
            'mushrif' => 'Mushrif',
            'ustaaz' => 'Ustaaz',
            'ustadah' => 'Ustadah',
            'mumtahin' => 'Mumtahin',
            
            // User management
            'name' => 'Name',
            'role' => 'Role',
            'phone' => 'Phone',
            'export' => 'Export',
            'date' => 'Date',
            
            // Stats
            'total_halaqaat' => 'Total Halaqaat',
            'total_students' => 'Total Students',
            'total_mumayyizeen' => 'Total Mumayyizeen',
            'students' => 'Students',
            
            // Gender/Session
            'baneen' => 'Baneen',
            'banaat' => 'Banaat',
            'subah' => 'Subah',
            'asr' => 'Asr',
            'boys' => 'Boys',
            'girls' => 'Girls',
            
            // Status
            'active' => 'Active',
            'inactive' => 'Inactive',
            'paused' => 'Paused',
            'stopped' => 'Stopped',
            
            // Shuba
            'shuba' => 'Shuba',
            'qaida' => 'Qaida',
            'nazira' => 'Nazira',
            'hifz' => 'Hifz',
            
            // Mumayyaz
            'mumayyaz' => 'Mumayyaz',
            'yes' => 'Yes',
            'no' => 'No',
            
            // Halaqa
            'halaqa' => 'Halaqa',
            'ustaaz' => 'Ustaaz',
            'session' => 'Session',
            'group' => 'Group',
            'location' => 'Location',
            
            // Dashboard sections
            'upcoming_exams' => 'Upcoming Exams',
            'students_by_shuba' => 'Students by Shuba',
            'students_by_gender' => 'Students by Gender',
            'top_halaqa_girls' => 'Highest % (Girls)',
            'top_halaqa_boys_subah' => 'Highest % (Boys — Subah)',
            'top_halaqa_boys_asr' => 'Highest % (Boys — Asr)',
            'mumtaaz_talaba' => 'Top Student',
            'score' => 'Score',
            'percentage' => 'Percentage',
            
            // Attendance
            'attendance' => 'Attendance',
            'present' => 'Present',
            'absent' => 'Absent',
            'late' => 'Late',
            'excused' => 'Excused',
            
            // Exams
            'exam' => 'Exam',
            'marks' => 'Marks',
            'max_marks' => 'Max Marks',
            'passing_marks' => 'Passing Marks',
            'remarks' => 'Remarks',
            'finalize' => 'Finalize',
            
            // Progress
            'progress' => 'Progress',
            'takhti' => 'Takhti',
            'surah' => 'Surah',
        ]
    ];
    return $T[$lang] ?? $T['ur'];
}
?>
