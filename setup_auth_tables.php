<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

echo "Running Auth Schema Setup...\n";

// 1. Ensure admins table and columns
$pdo->exec("
CREATE TABLE IF NOT EXISTS admins (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(150) NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
");

// Check if email column exists in admins
$adminCols = $pdo->query("SHOW COLUMNS FROM admins LIKE 'email'")->fetchAll();
if (empty($adminCols)) {
    $pdo->exec("ALTER TABLE admins ADD COLUMN email VARCHAR(150) NULL UNIQUE AFTER username");
    echo "Added 'email' column to admins table.\n";
}

// 2. Ensure teachers table
$pdo->exec("
CREATE TABLE IF NOT EXISTS teachers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  strand VARCHAR(100) DEFAULT 'LS4 - Life and Career Skills',
  assigned_center VARCHAR(200) DEFAULT 'San Nicolas Community Learning Center',
  status ENUM('Active', 'Inactive') DEFAULT 'Active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
");
echo "Ensured teachers table exists.\n";

// 3. Ensure students table has email and password columns
$studentEmailCols = $pdo->query("SHOW COLUMNS FROM students LIKE 'email'")->fetchAll();
if (empty($studentEmailCols)) {
    $pdo->exec("ALTER TABLE students ADD COLUMN email VARCHAR(150) NULL AFTER contact_number");
    echo "Added 'email' column to students table.\n";
}

$studentPasswordCols = $pdo->query("SHOW COLUMNS FROM students LIKE 'password'")->fetchAll();
if (empty($studentPasswordCols)) {
    $pdo->exec("ALTER TABLE students ADD COLUMN password VARCHAR(255) NULL AFTER email");
    echo "Added 'password' column to students table.\n";
}

// Check schedule and assigned_center in students
$schedCols = $pdo->query("SHOW COLUMNS FROM students LIKE 'schedule'")->fetchAll();
if (empty($schedCols)) {
    $pdo->exec("ALTER TABLE students ADD COLUMN schedule VARCHAR(50) DEFAULT 'Weekend Classes'");
    echo "Added 'schedule' column to students table.\n";
}
$centerCols = $pdo->query("SHOW COLUMNS FROM students LIKE 'assigned_center'")->fetchAll();
if (empty($centerCols)) {
    $pdo->exec("ALTER TABLE students ADD COLUMN assigned_center VARCHAR(200) DEFAULT 'San Nicolas Community Learning Center'");
    echo "Added 'assigned_center' column to students table.\n";
}

// 4. Seed Default Admin Accounts
$adminHash = password_hash('admin2026', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
$stmt->execute(['admin', 'admin@deped.gov.ph']);
if (!$stmt->fetch()) {
    $ins = $pdo->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
    $ins->execute(['admin', 'admin@deped.gov.ph', $adminHash]);
    echo "Seeded admin account (admin@deped.gov.ph / admin2026).\n";
} else {
    $pdo->prepare("UPDATE admins SET email = 'admin@deped.gov.ph', password = ? WHERE username = 'admin'")->execute([$adminHash]);
    echo "Updated admin account (admin@deped.gov.ph / admin2026).\n";
}

$stmt->execute(['alex', 'alex.cruz@deped.gov.ph']);
if (!$stmt->fetch()) {
    $ins = $pdo->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
    $ins->execute(['alex', 'alex.cruz@deped.gov.ph', $adminHash]);
    echo "Seeded alex account (alex.cruz@deped.gov.ph / admin2026).\n";
} else {
    $pdo->prepare("UPDATE admins SET email = 'alex.cruz@deped.gov.ph', password = ? WHERE username = 'alex'")->execute([$adminHash]);
    echo "Updated alex account (alex.cruz@deped.gov.ph / admin2026).\n";
}

// 5. Seed Default Instructional Manager (IM) Accounts
$teacherHash = password_hash('teacher2026', PASSWORD_BCRYPT);
$imCheck = $pdo->prepare("SELECT id FROM teachers WHERE email = ?");
$imCheck->execute(['elena.reyes@deped.gov.ph']);
if (!$imCheck->fetch()) {
    $insIm = $pdo->prepare("INSERT INTO teachers (full_name, email, password, strand, assigned_center) VALUES (?, ?, ?, ?, ?)");
    $insIm->execute([
        'Teacher Elena Reyes',
        'elena.reyes@deped.gov.ph',
        $teacherHash,
        'LS4 - Life and Career Skills',
        'San Nicolas Community Learning Center'
    ]);
    echo "Seeded IM account (elena.reyes@deped.gov.ph / teacher2026).\n";
} else {
    $pdo->prepare("UPDATE teachers SET password = ? WHERE email = ?")->execute([$teacherHash, 'elena.reyes@deped.gov.ph']);
    echo "Updated IM account Elena Reyes.\n";
}

$imCheck->execute(['ramon.santos@deped.gov.ph']);
if (!$imCheck->fetch()) {
    $insIm = $pdo->prepare("INSERT INTO teachers (full_name, email, password, strand, assigned_center) VALUES (?, ?, ?, ?, ?)");
    $insIm->execute([
        'Teacher Ramon Santos',
        'ramon.santos@deped.gov.ph',
        $teacherHash,
        'LS1 - Communication Skills',
        'San Nicolas Community Learning Center'
    ]);
    echo "Seeded IM account (ramon.santos@deped.gov.ph / teacher2026).\n";
} else {
    $pdo->prepare("UPDATE teachers SET password = ? WHERE email = ?")->execute([$teacherHash, 'ramon.santos@deped.gov.ph']);
    echo "Updated IM account Ramon Santos.\n";
}

// 6. Seed / Update Demo Students with Passwords
$studentHash = password_hash('student2026', PASSWORD_BCRYPT);

// Update Maria Isabel Reyes with email and password
$mCheck = $pdo->prepare("SELECT id FROM students WHERE student_number = ?");
$mCheck->execute(['ALS-DEMO-2026-0142']);
$mStudent = $mCheck->fetch();
if ($mStudent) {
    $pdo->prepare("UPDATE students SET email = 'maria.reyes@example.com', password = ? WHERE id = ?")
        ->execute([$studentHash, $mStudent['id']]);
    echo "Updated student Maria Isabel Reyes (maria.reyes@example.com / ALS-DEMO-2026-0142 / student2026).\n";
} else {
    $insStudent = $pdo->prepare("
        INSERT INTO students (student_number, first_name, middle_name, last_name, birthdate, gender, address, contact_number, email, password, learner_status, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insStudent->execute([
        'ALS-DEMO-2026-0142',
        'Maria Isabel',
        '',
        'Reyes',
        '2008-04-15',
        'Female',
        'Brgy. San Nicolas, Pasig City',
        '09171234567',
        'maria.reyes@example.com',
        $studentHash,
        'Junior High School',
        'Approved'
    ]);
    echo "Seeded student Maria Isabel Reyes.\n";
}

// Update other students with password if blank
$pdo->prepare("UPDATE students SET password = ? WHERE password IS NULL OR password = ''")->execute([$studentHash]);
echo "Ensured all students have default passwords set to student2026.\n";

echo "Auth Schema Setup Completed Successfully!\n";
