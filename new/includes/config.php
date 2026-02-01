<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'mdmunazir_linuxproguru');
define('DB_USER', 'mdmunazir_linuxproguru');
define('DB_PASS', 'Vikhara@548');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Language handling
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'] === 'ur' ? 'ur' : 'en';
}
$isRTL = $_SESSION['lang'] === 'ur';

// Translations
$lang = [
    'en' => [
        'app_name' => 'Kahaf Halaqaat',
        'login' => 'Login',
        'logout' => 'Logout',
        'dashboard' => 'Dashboard',
        'halaqaat' => 'Halaqaat',
        'students' => 'Students',
        'teachers' => 'Teachers',
        'exams' => 'Exams',
        'reports' => 'Reports',
        'users' => 'Users',
        'profile' => 'Profile',
        'attendance' => 'Attendance',
        'progress' => 'Progress',
        'marks_entry' => 'Marks Entry',
        'finalize' => 'Finalize',
        'total_halaqaat' => 'Total Halaqaat',
        'total_students' => 'Total Students',
        'total_mumayyizeen' => 'Total Mumayyizeen',
        'students_by_shuba' => 'Students by Shuba',
        'students_by_gender' => 'Students by Gender',
        'highest_percentage' => 'Highest Percentage',
        'mumtaaz_talaba' => 'Mumtaaz Talaba',
        'baneen' => 'Baneen',
        'banaat' => 'Banaat',
        'subah' => 'Subah',
        'asr' => 'Asr',
        'name' => 'Name',
        'teacher' => 'Teacher',
        'gender' => 'Gender',
        'time' => 'Time',
        'actions' => 'Actions',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'add_new' => 'Add New',
        'search' => 'Search',
        'status' => 'Status',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'assalam_alaikum' => 'Assalam Alaikum wa Rahmatullahi wa Barakatuhu',
        'welcome' => 'Welcome',
        'role' => 'Role',
        'admin' => 'Admin',
        'mushrif' => 'Mushrif',
        'ustaaz' => 'Ustaaz',
        'ustadah' => 'Ustadah',
        'mumtahin' => 'Mumtahin',
        'student' => 'Student',
        'email' => 'Email',
        'phone' => 'Phone',
        'address' => 'Address',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'update_profile' => 'Update Profile',
        'change_password' => 'Change Password',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'shuba' => 'Shuba',
        'mumayyiz' => 'Mumayyiz',
        'percentage' => 'Percentage',
        'rank' => 'Rank',
        'remarks' => 'Remarks',
        'submit' => 'Submit',
        'print' => 'Print',
        'export' => 'Export',
        'pdf' => 'PDF',
        'excel' => 'Excel',
        'date' => 'Date',
        'present' => 'Present',
        'absent' => 'Absent',
        'late' => 'Late',
        'excused' => 'Excused',
        'no_data_found' => 'No data found',
        'success' => 'Success',
        'error' => 'Error',
        'warning' => 'Warning',
        'info' => 'Info',
        'are_you_sure' => 'Are you sure?',
        'this_action_cannot_be_undone' => 'This action cannot be undone',
        'yes_delete_it' => 'Yes, delete it',
        'no_keep_it' => 'No, keep it',
    ],
    'ur' => [
        'app_name' => 'کہف حلقات',
        'login' => 'لاگ ان',
        'logout' => 'لاگ آؤٹ',
        'dashboard' => 'ڈیش بورڈ',
        'halaqaat' => 'حلقات',
        'students' => 'طلباء',
        'teachers' => 'اساتذہ',
        'exams' => 'امتحانات',
        'reports' => 'رپورٹس',
        'users' => 'صارفین',
        'profile' => 'پروفائل',
        'attendance' => 'حاضری',
        'progress' => 'ترقی',
        'marks_entry' => 'نمبر درج کریں',
        'finalize' => 'حتمی شکل دیں',
        'total_halaqaat' => 'کل حلقات',
        'total_students' => 'کل طلباء',
        'total_mumayyizeen' => 'کل ممیزین',
        'students_by_shuba' => 'شعبہ کے لحاظ سے طلباء',
        'students_by_gender' => 'جنس کے لحاظ سے طلباء',
        'highest_percentage' => 'سب سے زیادہ فیصد',
        'mumtaaz_talaba' => 'ممتاز طلباء',
        'baneen' => 'بنین',
        'banaat' => 'بنات',
        'subah' => 'صبح',
        'asr' => 'عصر',
        'name' => 'نام',
        'teacher' => 'استاد',
        'gender' => 'جنس',
        'time' => 'وقت',
        'actions' => 'اعمال',
        'save' => 'محفوظ کریں',
        'cancel' => 'منسوخ کریں',
        'edit' => 'ترمیم کریں',
        'delete' => 'حذف کریں',
        'add_new' => 'نیا شامل کریں',
        'search' => 'تلاش کریں',
        'status' => 'حالت',
        'active' => 'فعال',
        'inactive' => 'غیر فعال',
        'assalam_alaikum' => 'السلام علیکم و رحمت اللہ و برکاتہ',
        'welcome' => 'خوش آمدید',
        'role' => 'کردار',
        'admin' => 'منتظم',
        'mushrif' => 'مشرف',
        'ustaaz' => 'استاذ',
        'ustadah' => 'استاذہ',
        'mumtahin' => 'ممتحن',
        'student' => 'طالب علم',
        'email' => 'ای میل',
        'phone' => 'فون',
        'address' => 'پتہ',
        'password' => 'پاس ورڈ',
        'confirm_password' => 'پاس ورڈ کی تصدیق کریں',
        'update_profile' => 'پروفائل اپ ڈیٹ کریں',
        'change_password' => 'پاس ورڈ تبدیل کریں',
        'current_password' => 'موجودہ پاس ورڈ',
        'new_password' => 'نیا پاس ورڈ',
        'shuba' => 'شعبہ',
        'mumayyiz' => 'ممیز',
        'percentage' => 'فیصد',
        'rank' => 'درجہ',
        'remarks' => 'تبصرے',
        'submit' => 'جمع کرائیں',
        'print' => 'پرنٹ کریں',
        'export' => 'برآمد کریں',
        'pdf' => 'پی ڈی ایف',
        'excel' => 'ایکسل',
        'date' => 'تاریخ',
        'present' => 'حاضر',
        'absent' => 'غیر حاضر',
        'late' => 'تاخیر',
        'excused' => 'معذرت',
        'no_data_found' => 'کوئی ڈیٹا نہیں ملا',
        'success' => 'کامیابی',
        'error' => 'خرابی',
        'warning' => 'انتباہ',
        'info' => 'معلومات',
        'are_you_sure' => 'کیا آپ کو یقین ہے؟',
        'this_action_cannot_be_undone' => 'یہ عمل واپس نہیں کیا جا سکتا',
        'yes_delete_it' => 'ہاں، حذف کریں',
        'no_keep_it' => 'نہیں، رکھیں',
    ]
];

function t($key) {
    global $lang;
    $currentLang = $_SESSION['lang'] ?? 'en';
    return $lang[$currentLang][$key] ?? $key;
}

// Check login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Redirect if not specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role) && !hasRole('admin')) {
        header('Location: dashboard_' . $_SESSION['role'] . '.php');
        exit;
    }
}
?>
