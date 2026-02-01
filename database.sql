-- Kahaf Halaqaat Database Schema
-- Islamic Educational Halaqaat Management System

CREATE DATABASE IF NOT EXISTS kahaf_halaqaat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kahaf_halaqaat;

-- Users table (Admin, Mushrif, Ustaaz/Ustadah, Mumtahin)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'mushrif', 'ustaaz', 'ustadah', 'mumtahin') NOT NULL DEFAULT 'ustaaz',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Halaqaat table
CREATE TABLE halaqaat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_ur VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    ustaaz_user_id INT,
    gender ENUM('baneen', 'banaat') NOT NULL DEFAULT 'baneen',
    session ENUM('subah', 'asr') NOT NULL DEFAULT 'subah',
    state ENUM('active', 'paused', 'stopped') DEFAULT 'active',
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ustaaz_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    halaqa_id INT,
    full_name_en VARCHAR(100),
    full_name_ur VARCHAR(100) NOT NULL,
    gender ENUM('baneen', 'banaat') NOT NULL DEFAULT 'baneen',
    shuba ENUM('qaida', 'nazira', 'hifz') DEFAULT 'qaida',
    mumayyaz TINYINT(1) DEFAULT 0,
    qaida_takhti INT DEFAULT NULL,
    surah_current INT DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    removed_type ENUM('left', 'removed', 'changed_halaqa') DEFAULT NULL,
    removed_reason TEXT DEFAULT NULL,
    removed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (halaqa_id) REFERENCES halaqaat(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    halaqa_id INT,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (halaqa_id) REFERENCES halaqaat(id) ON DELETE SET NULL,
    UNIQUE KEY unique_attendance (student_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exams table
CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    exam_date DATE NOT NULL,
    max_marks DECIMAL(10,2) DEFAULT 100,
    passing_marks DECIMAL(10,2) DEFAULT 40,
    status ENUM('upcoming', 'ongoing', 'finalized') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exam Results table
CREATE TABLE exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    marks_obtained DECIMAL(10,2) NOT NULL,
    max_marks DECIMAL(10,2) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    remarks TEXT,
    status ENUM('draft', 'finalized') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_result (exam_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for better performance
CREATE INDEX idx_students_halaqa ON students(halaqa_id);
CREATE INDEX idx_students_status ON students(status);
CREATE INDEX idx_attendance_date ON attendance(date);
CREATE INDEX idx_attendance_student ON attendance(student_id);
CREATE INDEX idx_exam_results_student ON exam_results(student_id);
CREATE INDEX idx_exam_results_exam ON exam_results(exam_id);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_halaqaat_ustaaz ON halaqaat(ustaaz_user_id);
CREATE INDEX idx_halaqaat_gender ON halaqaat(gender);

-- Insert default admin user (password: admin123)
INSERT INTO users (full_name, email, password, role, status) VALUES 
('Admin', 'admin@kahaf.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert sample teachers
INSERT INTO users (full_name, email, password, role, status) VALUES 
('Ustaaz Ahmad', 'ahmad@kahaf.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ustaaz', 'active'),
('Ustadah Fatima', 'fatima@kahaf.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ustadah', 'active'),
('Mumtahin Khalid', 'khalid@kahaf.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mumtahin', 'active');

-- Insert sample halaqaat
INSERT INTO halaqaat (name_ur, name_en, ustaaz_user_id, gender, session, state, location) VALUES 
('حلقہ ناظرہ الف (طلباء)', 'Nazirah A Boys', 2, 'baneen', 'subah', 'active', 'Masjid Main Hall'),
('حلقہ ناظرہ ب (طلباء)', 'Nazirah B Boys', 2, 'baneen', 'asr', 'active', 'Masjid Classroom'),
('حلقہ النور (طالبات)', 'Al-Noor Girls', 3, 'banaat', 'subah', 'active', 'Sisters Section'),
('حلقہ البقرہ (طلباء)', 'Al-Baqarah Boys', 2, 'baneen', 'subah', 'active', 'Main Hall'),
('حلقہ الفاتحہ (طلباء)', 'Al-Fatiha Boys', 2, 'baneen', 'asr', 'active', 'Classroom B');

-- Insert sample students
INSERT INTO students (halaqa_id, full_name_ur, full_name_en, gender, shuba, mumayyaz, status) VALUES 
(1, 'طالب اول', 'Student One', 'baneen', 'hifz', 1, 'active'),
(1, 'طالب دوم', 'Student Two', 'baneen', 'nazira', 0, 'active'),
(1, 'طالب سوم', 'Student Three', 'baneen', 'hifz', 0, 'active'),
(2, 'طالب چہارم', 'Student Four', 'baneen', 'qaida', 0, 'active'),
(2, 'طالب پنجم', 'Student Five', 'baneen', 'nazira', 1, 'active'),
(3, 'طالبہ اولی', 'Student Six', 'banaat', 'hifz', 1, 'active'),
(3, 'طالبہ دومی', 'Student Seven', 'banaat', 'nazira', 0, 'active'),
(3, 'طالبہ سومی', 'Student Eight', 'banaat', 'qaida', 0, 'active'),
(4, 'طالب ہشتم', 'Student Nine', 'baneen', 'hifz', 0, 'active'),
(4, 'طالب نہم', 'Student Ten', 'baneen', 'nazira', 1, 'active'),
(5, 'طالب دہم', 'Student Eleven', 'baneen', 'qaida', 0, 'active'),
(5, 'طالب یازدہم', 'Student Twelve', 'baneen', 'hifz', 0, 'active');

-- Insert sample exams
INSERT INTO exams (title, exam_date, max_marks, passing_marks, status) VALUES 
('سہ ماہی امتحان', '2024-03-20', 100, 40, 'finalized'),
('ماہانہ امتحان', '2024-03-25', 50, 20, 'upcoming');
