-- Kahaf Halaqaat Database Schema
-- Islamic Educational Halaqaat Management System

CREATE DATABASE IF NOT EXISTS kahaf_halaqaat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kahaf_halaqaat;

-- Users table (Admin, Mushrif, Ustaaz/Ustadah, Mumtahin)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
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
    name VARCHAR(100) NOT NULL,
    teacher_id INT,
    gender ENUM('baneen', 'banaat') NOT NULL DEFAULT 'baneen',
    time_slot ENUM('subah', 'asr') NOT NULL DEFAULT 'subah',
    location VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    roll_number VARCHAR(20),
    halaqa_id INT,
    shuba VARCHAR(50) NOT NULL,
    gender ENUM('baneen', 'banaat') NOT NULL DEFAULT 'baneen',
    date_of_birth DATE,
    join_date DATE DEFAULT CURRENT_DATE,
    father_name VARCHAR(100),
    contact_number VARCHAR(20),
    address TEXT,
    is_mumayyiz TINYINT(1) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
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
CREATE INDEX idx_halaqaat_teacher ON halaqaat(teacher_id);
CREATE INDEX idx_halaqaat_gender ON halaqaat(gender);

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role, status) VALUES 
('Admin', 'admin@kahaf.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Sample data (optional - remove in production)
-- Insert sample teachers
INSERT INTO users (name, email, password, role, status) VALUES 
('Ustaaz Ahmad', 'ahmad@kahaf.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ustaaz', 'active'),
('Ustadah Fatima', 'fatima@kahaf.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ustadah', 'active'),
('Mumtahin Khalid', 'khalid@kahaf.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mumtahin', 'active');

-- Insert sample halaqaat
INSERT INTO halaqaat (name, teacher_id, gender, time_slot, location, status) VALUES 
('Halaqa Baneen - Subah', 2, 'baneen', 'subah', 'Masjid Main Hall', 'active'),
('Halaqa Baneen - Asr', 2, 'baneen', 'asr', 'Masjid Classroom', 'active'),
('Halaqa Banaat - Subah', 3, 'banaat', 'subah', 'Sisters Section', 'active');

-- Insert sample students
INSERT INTO students (name, roll_number, halaqa_id, shuba, gender, father_name, contact_number, is_mumayyiz, status) VALUES 
('Student One', 'S001', 1, 'Hifz', 'baneen', 'Father One', '03001234567', 1, 'active'),
('Student Two', 'S002', 1, 'Nazira', 'baneen', 'Father Two', '03001234568', 0, 'active'),
('Student Three', 'S003', 2, 'Hifz', 'baneen', 'Father Three', '03001234569', 0, 'active'),
('Student Four', 'S004', 3, 'Nazira', 'banaat', 'Father Four', '03001234570', 1, 'active'),
('Student Five', 'S005', 3, 'Hifz', 'banaat', 'Father Five', '03001234571', 0, 'active');
