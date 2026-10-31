CREATE DATABASE IF NOT EXISTS als_enrollment_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE als_enrollment_db;

-- 1. Users Table (Authentication & Role-Based Access)
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(150) NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
  user_ref_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_role (role),
  INDEX idx_user_login (username, email)
) ENGINE=InnoDB;

-- 2. Teachers Table (Instructional Managers)
CREATE TABLE IF NOT EXISTS teachers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  first_name VARCHAR(100) NULL,
  middle_name VARCHAR(100) NULL,
  last_name VARCHAR(100) NULL,
  civil_status VARCHAR(50) DEFAULT 'Single',
  age INT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  assigned_ls TEXT NULL COMMENT 'Comma-separated values para sa LS1, LS2, LS3, LS4, LS5, LS6',
  assigned_center VARCHAR(200) DEFAULT 'San Nicolas Community Learning Center',
  contact_number VARCHAR(50) DEFAULT '0917-823-4412',
  status ENUM('Active', 'Inactive') DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_teacher_email (email)
) ENGINE=InnoDB;

-- 3. Students Table (Enrollees / Masterlist)
CREATE TABLE IF NOT EXISTS students (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_number VARCHAR(50) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  middle_name VARCHAR(100) NULL,
  last_name VARCHAR(100) NOT NULL,
  birthdate DATE NOT NULL,
  age INT NULL,
  gender VARCHAR(20) NOT NULL DEFAULT 'Prefer not to say',
  civil_status VARCHAR(50) DEFAULT 'Single',
  address TEXT NOT NULL,
  contact_number VARCHAR(50) NOT NULL,
  email VARCHAR(150) NULL,
  password VARCHAR(255) NULL,
  strand VARCHAR(100) NOT NULL DEFAULT 'LS1 - Communication Skills',
  assigned_modules VARCHAR(255) NOT NULL DEFAULT 'LS1,LS2,LS3,LS4,LS5,LS6',
  learner_status VARCHAR(100) NOT NULL DEFAULT 'Junior High School',
  status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
  document_status ENUM('Incomplete', 'Complete', 'Under Review') NOT NULL DEFAULT 'Incomplete',
  doc_psa TINYINT(1) NOT NULL DEFAULT 1,
  doc_brgy TINYINT(1) NOT NULL DEFAULT 1,
  doc_card TINYINT(1) NOT NULL DEFAULT 0,
  schedule VARCHAR(100) DEFAULT 'Weekend Classes',
  assigned_center VARCHAR(200) DEFAULT 'San Nicolas Community Learning Center',
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  date_registered TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_student_name (last_name, first_name),
  INDEX idx_student_status (status),
  INDEX idx_student_number (student_number)
) ENGINE=InnoDB;

-- 4. Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  teacher_id BIGINT UNSIGNED NULL,
  scan_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(50) NOT NULL DEFAULT 'Present',
  notes VARCHAR(255) NULL,
  INDEX idx_att_student (student_id),
  INDEX idx_att_date (scan_date)
) ENGINE=InnoDB;

-- 4b. Email Notifications Table (Automated Dispatch Logs)
CREATE TABLE IF NOT EXISTS email_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipient_name VARCHAR(150) NOT NULL,
  recipient_email VARCHAR(150) NOT NULL,
  role ENUM('student', 'teacher', 'admin') NOT NULL,
  temporary_password VARCHAR(100) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body_html TEXT NOT NULL,
  status ENUM('Sent', 'Failed', 'Logged') DEFAULT 'Sent',
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_recipient (recipient_email),
  INDEX idx_email_sent_at (sent_at)
) ENGINE=InnoDB;

-- 5. Seed Default Admin User (Password: admin2026)
INSERT INTO users (id, username, email, password, role) VALUES
(1, 'admin', 'admin@deped.gov.ph', '$2y$10$rL./I9TY.iKni5IO5sSEmeM3Y5O0VlwEDIBPOdwPP00KFQlQy8EQi', 'admin')
ON DUPLICATE KEY UPDATE email=VALUES(email), password=VALUES(password), role='admin';

-- 6. Seed Default Teachers (Password: teacher2026)
INSERT INTO teachers (id, name, email, assigned_ls, assigned_center) VALUES
(1, 'Teacher Elena Reyes', 'elena.reyes@deped.gov.ph', 'LS1, LS4, LS6', 'San Nicolas Community Learning Center'),
(2, 'Teacher Ramon Santos', 'ramon.santos@deped.gov.ph', 'LS2, LS3, LS5', 'San Nicolas Community Learning Center')
ON DUPLICATE KEY UPDATE name=VALUES(name), assigned_ls=VALUES(assigned_ls);

INSERT INTO users (username, email, password, role, user_ref_id) VALUES
('elena.reyes@deped.gov.ph', 'elena.reyes@deped.gov.ph', '$2y$10$U7vA128B46PjJb/9j.jAoeC/cM7W1d15mXb1k7Y/zI5q4i1W7G7.e', 'teacher', 1),
('ramon.santos@deped.gov.ph', 'ramon.santos@deped.gov.ph', '$2y$10$U7vA128B46PjJb/9j.jAoeC/cM7W1d15mXb1k7Y/zI5q4i1W7G7.e', 'teacher', 2)
ON DUPLICATE KEY UPDATE password=VALUES(password), role='teacher';

-- 7. Seed Default Students (Password: student2026)
INSERT INTO students (id, student_number, first_name, middle_name, last_name, birthdate, address, contact_number, email, password, strand, learner_status, status, doc_psa, doc_brgy, doc_card) VALUES
(1, 'ALS-DEMO-2026-0142', 'Maria Isabel', '', 'Reyes', '2008-04-15', 'Brgy. San Nicolas, Pasig City', '0917-123-4567', 'maria.reyes@example.com', '$2y$10$Uj99M0kY8P4F9r1L8d5BKeB9b9aC/1d2k3j4l5m6n7o8p9q0r1s2t', 'LS1 - Communication Skills', 'Junior High School', 'Approved', 1, 1, 1),
(2, 'ALS-DEMO-2026-0141', 'Carlo', '', 'Mendoza', '2009-08-20', 'Brgy. Batasan Hills, Quezon City', '0918-987-6543', 'carlo.mendoza@example.com', '$2y$10$Uj99M0kY8P4F9r1L8d5BKeB9b9aC/1d2k3j4l5m6n7o8p9q0r1s2t', 'LS3 - Math & Problem Solving', 'Elementary', 'Pending', 1, 0, 0),
(3, 'ALS-DEMO-2026-0140', 'Liza', '', 'Bautista', '2007-11-05', 'Brgy. Commonwealth, Quezon City', '0922-334-4556', 'liza.bautista@example.com', '$2y$10$Uj99M0kY8P4F9r1L8d5BKeB9b9aC/1d2k3j4l5m6n7o8p9q0r1s2t', 'LS4 - Life & Career Skills', 'Junior High School', 'Approved', 1, 1, 1),
(4, 'ALS-DEMO-2026-0139', 'Joel', '', 'Ramos', '2010-02-12', 'Brgy. Holy Spirit, Quezon City', '0933-445-5667', 'joel.ramos@example.com', '$2y$10$Uj99M0kY8P4F9r1L8d5BKeB9b9aC/1d2k3j4l5m6n7o8p9q0r1s2t', 'LS2 - Scientific Literacy', 'Elementary', 'Pending', 0, 1, 0),
(5, 'ALS-DEMO-2026-0138', 'Ana', '', 'Dela Cruz', '2008-07-25', 'Brgy. Pasong Tamo, Quezon City', '0945-556-6778', 'ana.delacruz@example.com', '$2y$10$Uj99M0kY8P4F9r1L8d5BKeB9b9aC/1d2k3j4l5m6n7o8p9q0r1s2t', 'LS6 - Digital Literacy', 'Junior High School', 'Approved', 1, 1, 0),
(6, 'ALS-DEMO-2026-0137', 'Paolo', '', 'Garcia', '2009-01-30', 'Brgy. Tandang Sora, Quezon City', '0956-667-7889', 'paolo.garcia@example.com', '$2y$10$Uj99M0kY8P4F9r1L8d5BKeB9b9aC/1d2k3j4l5m6n7o8p9q0r1s2t', 'LS5 - Self & Society', 'Elementary', 'Rejected', 0, 0, 0)
ON DUPLICATE KEY UPDATE strand=VALUES(strand), status=VALUES(status);

INSERT INTO users (username, email, password, role, user_ref_id) VALUES
('ALS-DEMO-2026-0142', 'maria.reyes@example.com', '$2y$10$Uj99M0kY8P4F9r1L8d5BKeB9b9aC/1d2k3j4l5m6n7o8p9q0r1s2t', 'student', 1)
ON DUPLICATE KEY UPDATE password=VALUES(password), role='student';

-- 8. Seed Sample Attendance Logs
INSERT IGNORE INTO attendance (id, student_id, teacher_id, scan_date, status, notes) VALUES
(1, 1, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Present', 'San Nicolas CLC Station Camera 01'),
(2, 3, 1, DATE_SUB(NOW(), INTERVAL 2 DAY), 'Present', 'On-time Weekend Session'),
(3, 5, 2, DATE_SUB(NOW(), INTERVAL 3 DAY), 'Present', 'Digital Literacy Lab Attendance');
