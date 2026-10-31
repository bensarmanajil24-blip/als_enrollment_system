<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

echo "=== UPGRADING DATABASE SCHEMA TO USER SPECIFICATION ===\n";

// 1. users table
$pdo->exec("
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
");
echo "1. users table ensured.\n";

// Check if user_ref_id or email exists in users
$colsUsers = $pdo->query("SHOW COLUMNS FROM users LIKE 'user_ref_id'")->fetchAll();
if (empty($colsUsers)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN user_ref_id BIGINT UNSIGNED NULL AFTER role");
}
$colsEmail = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'")->fetchAll();
if (empty($colsEmail)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(150) NULL UNIQUE AFTER username");
}

// 2. teachers table
$pdo->exec("
CREATE TABLE IF NOT EXISTS teachers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  assigned_ls TEXT NULL COMMENT 'Comma-separated LS1 to LS6',
  assigned_center VARCHAR(200) DEFAULT 'San Nicolas Community Learning Center',
  contact_number VARCHAR(50) DEFAULT '0917-823-4412',
  status ENUM('Active', 'Inactive') DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
");
echo "2. teachers table ensured.\n";

// Check columns in teachers
$teacherCols = $pdo->query("SHOW COLUMNS FROM teachers LIKE 'assigned_ls'")->fetchAll();
if (empty($teacherCols)) {
    $pdo->exec("ALTER TABLE teachers ADD COLUMN assigned_ls TEXT NULL AFTER email");
}
$teacherColsName = $pdo->query("SHOW COLUMNS FROM teachers LIKE 'name'")->fetchAll();
if (empty($teacherColsName)) {
    $pdo->exec("ALTER TABLE teachers ADD COLUMN name VARCHAR(150) NOT NULL AFTER id");
}

// 3. students table (Enrollees / Masterlist)
$pdo->exec("
CREATE TABLE IF NOT EXISTS students (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_number VARCHAR(50) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  middle_name VARCHAR(100) NULL,
  last_name VARCHAR(100) NOT NULL,
  birthdate DATE NOT NULL,
  gender VARCHAR(20) NOT NULL DEFAULT 'Prefer not to say',
  address TEXT NOT NULL,
  contact_number VARCHAR(50) NOT NULL,
  email VARCHAR(150) NULL,
  password VARCHAR(255) NULL,
  strand VARCHAR(100) NOT NULL DEFAULT 'LS1 - Communication Skills',
  learner_status VARCHAR(100) NOT NULL DEFAULT 'Junior High School',
  status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
  doc_psa TINYINT(1) NOT NULL DEFAULT 0,
  doc_brgy TINYINT(1) NOT NULL DEFAULT 0,
  doc_card TINYINT(1) NOT NULL DEFAULT 0,
  schedule VARCHAR(100) DEFAULT 'Weekend Classes',
  assigned_center VARCHAR(200) DEFAULT 'San Nicolas Community Learning Center',
  date_registered TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_student_name (last_name, first_name),
  INDEX idx_student_status (status),
  INDEX idx_student_number (student_number)
) ENGINE=InnoDB;
");
echo "3. students table ensured.\n";

// Ensure doc columns and strand in students
$studCols = $pdo->query("SHOW COLUMNS FROM students LIKE 'doc_psa'")->fetchAll();
if (empty($studCols)) {
    $pdo->exec("ALTER TABLE students ADD COLUMN doc_psa TINYINT(1) NOT NULL DEFAULT 1 AFTER status,
               ADD COLUMN doc_brgy TINYINT(1) NOT NULL DEFAULT 1 AFTER doc_psa,
               ADD COLUMN doc_card TINYINT(1) NOT NULL DEFAULT 0 AFTER doc_brgy");
}
$studStrandCols = $pdo->query("SHOW COLUMNS FROM students LIKE 'strand'")->fetchAll();
if (empty($studStrandCols)) {
    $pdo->exec("ALTER TABLE students ADD COLUMN strand VARCHAR(100) NOT NULL DEFAULT 'LS1 - Communication Skills' AFTER email");
}

// 4. attendance table
$pdo->exec("
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
");
echo "4. attendance table ensured.\n";

// 5. SEED USERS & ROLES
$adminPass = password_hash('admin2026', PASSWORD_BCRYPT);
$teacherPass = password_hash('teacher2026', PASSWORD_BCRYPT);
$studentPass = password_hash('student2026', PASSWORD_BCRYPT);

// Admin in users
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute(['admin']);
if (!$stmt->fetch()) {
    $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)")
        ->execute(['admin', 'admin@deped.gov.ph', $adminPass, 'admin']);
    echo "Seeded Admin into users.\n";
} else {
    $pdo->prepare("UPDATE users SET password = ?, email = 'admin@deped.gov.ph', role = 'admin' WHERE username = 'admin'")
        ->execute([$adminPass]);
}

// Teachers in teachers & users
$teachersData = [
    [
        'name' => 'Teacher Elena Reyes',
        'email' => 'elena.reyes@deped.gov.ph',
        'assigned_ls' => 'LS1, LS4, LS6',
        'center' => 'San Nicolas Community Learning Center'
    ],
    [
        'name' => 'Teacher Ramon Santos',
        'email' => 'ramon.santos@deped.gov.ph',
        'assigned_ls' => 'LS2, LS3, LS5',
        'center' => 'San Nicolas Community Learning Center'
    ]
];

foreach ($teachersData as $t) {
    $tCheck = $pdo->prepare("SELECT id FROM teachers WHERE email = ?");
    $tCheck->execute([$t['email']]);
    $existing = $tCheck->fetch();
    if (!$existing) {
        $ins = $pdo->prepare("INSERT INTO teachers (name, email, assigned_ls, assigned_center) VALUES (?, ?, ?, ?)");
        $ins->execute([$t['name'], $t['email'], $t['assigned_ls'], $t['center']]);
        $teacherId = (int)$pdo->lastInsertId();
    } else {
        $teacherId = (int)$existing['id'];
        $pdo->prepare("UPDATE teachers SET name = ?, assigned_ls = ? WHERE id = ?")
            ->execute([$t['name'], $t['assigned_ls'], $teacherId]);
    }

    // Sync in users
    $uCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $uCheck->execute([$t['email'], $t['email']]);
    if (!$uCheck->fetch()) {
        $pdo->prepare("INSERT INTO users (username, email, password, role, user_ref_id) VALUES (?, ?, ?, 'teacher', ?)")
            ->execute([$t['email'], $t['email'], $teacherPass, $teacherId]);
    } else {
        $pdo->prepare("UPDATE users SET role = 'teacher', password = ?, user_ref_id = ? WHERE email = ?")
            ->execute([$teacherPass, $teacherId, $t['email']]);
    }
}
echo "Seeded Teachers into teachers and users table.\n";

// Students in students & users
$sampleStudents = [
    [
        'lrn' => 'ALS-DEMO-2026-0142',
        'first_name' => 'Maria Isabel',
        'last_name' => 'Reyes',
        'email' => 'maria.reyes@example.com',
        'strand' => 'LS1 - Communication Skills',
        'learner_status' => 'Junior High School',
        'status' => 'Approved',
        'doc_psa' => 1, 'doc_brgy' => 1, 'doc_card' => 1
    ],
    [
        'lrn' => 'ALS-DEMO-2026-0141',
        'first_name' => 'Carlo',
        'last_name' => 'Mendoza',
        'email' => 'carlo.mendoza@example.com',
        'strand' => 'LS3 - Math & Problem Solving',
        'learner_status' => 'Elementary',
        'status' => 'Pending',
        'doc_psa' => 1, 'doc_brgy' => 0, 'doc_card' => 0
    ],
    [
        'lrn' => 'ALS-DEMO-2026-0140',
        'first_name' => 'Liza',
        'last_name' => 'Bautista',
        'email' => 'liza.bautista@example.com',
        'strand' => 'LS4 - Life & Career Skills',
        'learner_status' => 'Junior High School',
        'status' => 'Approved',
        'doc_psa' => 1, 'doc_brgy' => 1, 'doc_card' => 1
    ],
    [
        'lrn' => 'ALS-DEMO-2026-0139',
        'first_name' => 'Joel',
        'last_name' => 'Ramos',
        'email' => 'joel.ramos@example.com',
        'strand' => 'LS2 - Scientific Literacy',
        'learner_status' => 'Elementary',
        'status' => 'Pending',
        'doc_psa' => 0, 'doc_brgy' => 1, 'doc_card' => 0
    ],
    [
        'lrn' => 'ALS-DEMO-2026-0138',
        'first_name' => 'Ana',
        'last_name' => 'Dela Cruz',
        'email' => 'ana.delacruz@example.com',
        'strand' => 'LS6 - Digital Literacy',
        'learner_status' => 'Junior High School',
        'status' => 'Approved',
        'doc_psa' => 1, 'doc_brgy' => 1, 'doc_card' => 0
    ],
    [
        'lrn' => 'ALS-DEMO-2026-0137',
        'first_name' => 'Paolo',
        'last_name' => 'Garcia',
        'email' => 'paolo.garcia@example.com',
        'strand' => 'LS5 - Self & Society',
        'learner_status' => 'Elementary',
        'status' => 'Rejected',
        'doc_psa' => 0, 'doc_brgy' => 0, 'doc_card' => 0
    ],
];

foreach ($sampleStudents as $st) {
    $sCheck = $pdo->prepare("SELECT id FROM students WHERE student_number = ?");
    $sCheck->execute([$st['lrn']]);
    $sRow = $sCheck->fetch();
    if (!$sRow) {
        $ins = $pdo->prepare("
            INSERT INTO students (student_number, first_name, last_name, birthdate, address, contact_number, email, password, strand, learner_status, status, doc_psa, doc_brgy, doc_card)
            VALUES (?, ?, ?, '2008-01-15', 'San Nicolas, Pasig City', '0917-123-4567', ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $st['lrn'], $st['first_name'], $st['last_name'],
            $st['email'], $studentPass, $st['strand'], $st['learner_status'], $st['status'],
            $st['doc_psa'], $st['doc_brgy'], $st['doc_card']
        ]);
        $studId = (int)$pdo->lastInsertId();
    } else {
        $studId = (int)$sRow['id'];
        $pdo->prepare("UPDATE students SET strand = ?, email = ?, password = ?, doc_psa = ?, doc_brgy = ?, doc_card = ? WHERE id = ?")
            ->execute([$st['strand'], $st['email'], $studentPass, $st['doc_psa'], $st['doc_brgy'], $st['doc_card'], $studId]);
    }

    // Sync in users table for student
    $uCheck = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $uCheck->execute([$st['lrn']]);
    if (!$uCheck->fetch()) {
        $pdo->prepare("INSERT INTO users (username, email, password, role, user_ref_id) VALUES (?, ?, ?, 'student', ?)")
            ->execute([$st['lrn'], $st['email'], $studentPass, $studId]);
    } else {
        $pdo->prepare("UPDATE users SET password = ?, email = ?, user_ref_id = ? WHERE username = ?")
            ->execute([$studentPass, $st['email'], $studId, $st['lrn']]);
    }
}
echo "Seeded Students into students and users table.\n";

// Seed Sample Attendance Records
$pdo->exec("
    INSERT IGNORE INTO attendance (id, student_id, teacher_id, scan_date, status, notes) VALUES
    (1, 1, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Present', 'San Nicolas CLC Station Camera 01'),
    (2, 3, 1, DATE_SUB(NOW(), INTERVAL 2 DAY), 'Present', 'On-time Weekend Session'),
    (3, 5, 2, DATE_SUB(NOW(), INTERVAL 3 DAY), 'Present', 'Digital Literacy Lab Attendance')
");
echo "Seeded Attendance logs.\n";

echo "ALL SCHEMA UPGRADES COMPLETED SUCCESSFULLY!\n";

