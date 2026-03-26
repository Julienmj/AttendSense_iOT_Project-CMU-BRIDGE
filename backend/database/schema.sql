-- AttendSense Database Schema
-- MySQL 8.0+ compatible

-- Create database
CREATE DATABASE IF NOT EXISTS attendsense CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE attendsense;

-- Classes table
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    active BOOLEAN DEFAULT TRUE,
    INDEX idx_course_code (course_code),
    INDEX idx_active (active)
);

-- Students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    device_name VARCHAR(255),
    mac_address VARCHAR(17) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    INDEX idx_class_id (class_id),
    INDEX idx_student_id (student_id),
    INDEX idx_mac_address (mac_address),
    INDEX idx_active (active)
);

-- Sessions table
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    checkin_window_minutes INT DEFAULT 15,
    status ENUM('upcoming', 'active', 'completed', 'cancelled') DEFAULT 'upcoming',
    present_count INT DEFAULT 0,
    total_students INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    start_timestamp TIMESTAMP NULL,
    end_timestamp TIMESTAMP NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    INDEX idx_class_id (class_id),
    INDEX idx_date (date),
    INDEX idx_status (status),
    INDEX idx_active_session (status, date)
);

-- Attendance records table
CREATE TABLE attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('present', 'absent', 'late') DEFAULT 'present',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    detected_at TIMESTAMP NULL,
    rssi INT NULL,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session_student (session_id, student_id),
    INDEX idx_session_id (session_id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status),
    INDEX idx_timestamp (timestamp)
);

-- Detected devices table (for real-time scanning)
CREATE TABLE detected_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    mac_address VARCHAR(17) NOT NULL,
    student_id INT NULL,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rssi INT NULL,
    processed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_mac_address (mac_address),
    INDEX idx_detected_at (detected_at),
    INDEX idx_processed (processed)
);

-- Users table (for admin/teacher accounts)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'assistant') DEFAULT 'teacher',
    full_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password_hash, role, full_name) VALUES 
('admin', 'admin@attendsense.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator');

-- Create views for common queries

-- View for class statistics
CREATE VIEW class_statistics AS
SELECT 
    c.id,
    c.name,
    c.course_code,
    COUNT(s.id) as total_students,
    COUNT(CASE WHEN s.active = TRUE THEN 1 END) as active_students,
    COUNT(DISTINCT ses.id) as total_sessions,
    ROUND(AVG(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) * 100, 2) as average_attendance_rate
FROM classes c
LEFT JOIN students s ON c.id = s.class_id
LEFT JOIN sessions ses ON c.id = ses.class_id
LEFT JOIN attendance_records ar ON ses.id = ar.session_id
GROUP BY c.id, c.name, c.course_code;

-- View for session attendance summary
CREATE VIEW session_attendance_summary AS
SELECT 
    ses.id,
    ses.name,
    ses.date,
    ses.start_time,
    ses.end_time,
    ses.status,
    c.name as class_name,
    c.course_code,
    COUNT(s.id) as total_students,
    COUNT(ar.id) as marked_attendance,
    COUNT(CASE WHEN ar.status = 'present' THEN 1 END) as present_count,
    COUNT(CASE WHEN ar.status = 'absent' THEN 1 END) as absent_count,
    COUNT(CASE WHEN ar.status = 'late' THEN 1 END) as late_count,
    ROUND(COUNT(CASE WHEN ar.status = 'present' THEN 1 END) / COUNT(s.id) * 100, 2) as attendance_rate
FROM sessions ses
JOIN classes c ON ses.class_id = c.id
LEFT JOIN students s ON c.id = s.class_id AND s.active = TRUE
LEFT JOIN attendance_records ar ON ses.id = ar.session_id
GROUP BY ses.id, ses.name, ses.date, ses.start_time, ses.end_time, ses.status, c.name, c.course_code;

-- Stored procedures for common operations

DELIMITER //

-- Procedure to mark attendance for a session
CREATE PROCEDURE MarkAttendance(
    IN p_session_id INT,
    IN p_student_id INT,
    IN p_status ENUM('present', 'absent', 'late'),
    IN p_detected_at TIMESTAMP,
    IN p_rssi INT
)
BEGIN
    INSERT INTO attendance_records (session_id, student_id, status, detected_at, rssi)
    VALUES (p_session_id, p_student_id, p_status, p_detected_at, p_rssi)
    ON DUPLICATE KEY UPDATE 
    status = VALUES(status),
    detected_at = VALUES(detected_at),
    rssi = VALUES(rssi),
    timestamp = CURRENT_TIMESTAMP;
    
    -- Update session present count
    UPDATE sessions 
    SET present_count = (
        SELECT COUNT(*) 
        FROM attendance_records 
        WHERE session_id = p_session_id AND status = 'present'
    )
    WHERE id = p_session_id;
END//

-- Procedure to create session and mark all students as absent initially
CREATE PROCEDURE CreateSessionWithStudents(
    IN p_class_id INT,
    IN p_name VARCHAR(255),
    IN p_date DATE,
    IN p_start_time TIME,
    IN p_end_time TIME,
    IN p_checkin_window_minutes INT
)
BEGIN
    DECLARE v_session_id INT;
    DECLARE v_student_count INT;
    
    -- Create session
    INSERT INTO sessions (class_id, name, date, start_time, end_time, checkin_window_minutes, total_students)
    VALUES (p_class_id, p_name, p_date, p_start_time, p_end_time, p_checkin_window_minutes, 0);
    
    SET v_session_id = LAST_INSERT_ID();
    
    -- Get student count
    SELECT COUNT(*) INTO v_student_count 
    FROM students 
    WHERE class_id = p_class_id AND active = TRUE;
    
    -- Update session with student count
    UPDATE sessions 
    SET total_students = v_student_count 
    WHERE id = v_session_id;
    
    -- Optionally mark all students as absent initially
    -- INSERT INTO attendance_records (session_id, student_id, status)
    -- SELECT v_session_id, id, 'absent' 
    -- FROM students 
    -- WHERE class_id = p_class_id AND active = TRUE;
    
    SELECT v_session_id as session_id;
END//

DELIMITER ;

-- Triggers for data integrity

-- Trigger to update session total_students when students are added/removed
DELIMITER //
CREATE TRIGGER update_session_student_count_after_student_insert
AFTER INSERT ON students
FOR EACH ROW
BEGIN
    UPDATE sessions 
    SET total_students = (
        SELECT COUNT(*) 
        FROM students 
        WHERE class_id = NEW.class_id AND active = TRUE
    )
    WHERE class_id = NEW.class_id AND status IN ('upcoming', 'active');
END//

CREATE TRIGGER update_session_student_count_after_student_update
AFTER UPDATE ON students
FOR EACH ROW
BEGIN
    IF NEW.active != OLD.active THEN
        UPDATE sessions 
        SET total_students = (
            SELECT COUNT(*) 
            FROM students 
            WHERE class_id = NEW.class_id AND active = TRUE
        )
        WHERE class_id = NEW.class_id AND status IN ('upcoming', 'active');
    END IF;
END//
DELIMITER ;

-- Sample data for testing
INSERT INTO classes (name, course_code, description) VALUES 
('Computer Science 101', 'CS101', 'Introduction to Computer Science'),
('Data Structures', 'CS201', 'Advanced Data Structures and Algorithms'),
('Web Development', 'CS301', 'Modern Web Development Practices');

INSERT INTO students (class_id, name, student_id, email, phone, device_name, mac_address) VALUES 
(1, 'Alice Johnson', 'STU001', 'alice@university.edu', '+1234567890', 'Alice iPhone', 'A4:C1:38:2B:5E:9F'),
(1, 'Bob Smith', 'STU002', 'bob@university.edu', '+1234567891', 'Bob Android', 'B5:D2:49:3C:6F:0A'),
(1, 'Charlie Brown', 'STU003', 'charlie@university.edu', '+1234567892', 'Charlie Laptop', 'C6:E3:5A:4D:7G:1B'),
(2, 'Diana Prince', 'STU004', 'diana@university.edu', '+1234567893', 'Diana iPad', 'D7:F4:6B:5E:8H:2C');

INSERT INTO sessions (class_id, name, date, start_time, end_time, checkin_window_minutes) VALUES 
(1, 'Week 1 Lecture', CURDATE(), '09:00:00', '10:30:00', 15),
(1, 'Week 2 Lecture', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '09:00:00', '10:30:00', 15),
(2, 'Lab Session 1', CURDATE(), '14:00:00', '16:00:00', 30);
