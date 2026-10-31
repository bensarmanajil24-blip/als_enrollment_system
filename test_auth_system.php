<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

echo "=== VERIFYING COMPLETE SYSTEM SPECIFICATION ===\n";

// 1. Verify Users Table (Role-based access)
$adminUser = $pdo->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1")->fetch();
assert($adminUser !== false, 'Admin user should exist in users table');
assert(password_verify('admin2026', $adminUser['password']), 'Admin password should verify');
echo "[PASS] 1. Admin authenticated via users table.\n";

$teacherUser = $pdo->query("SELECT * FROM users WHERE role = 'teacher' LIMIT 1")->fetch();
assert($teacherUser !== false, 'Teacher user should exist in users table');
assert(password_verify('teacher2026', $teacherUser['password']), 'Teacher password should verify');
echo "[PASS] 2. Teacher authenticated via users table.\n";

$studentUser = $pdo->query("SELECT * FROM users WHERE role = 'student' LIMIT 1")->fetch();
assert($studentUser !== false, 'Student user should exist in users table');
assert(password_verify('student2026', $studentUser['password']), 'Student password should verify');
echo "[PASS] 3. Student authenticated via users table.\n";

// 2. Verify Teachers Table with assigned_ls
$teachers = $pdo->query("SELECT * FROM teachers WHERE assigned_ls IS NOT NULL")->fetchAll();
assert(count($teachers) >= 2, 'Teachers with assigned_ls should exist');
echo "[PASS] 4. Teachers table with assigned_ls verified (" . count($teachers) . " teachers found).\n";

// 3. Verify Students Table with strands and documents
$students = $pdo->query("SELECT * FROM students WHERE strand IS NOT NULL")->fetchAll();
assert(count($students) >= 6, 'Students with strand and documents should exist');
echo "[PASS] 5. Students table verified (" . count($students) . " students found).\n";

// 4. Verify Attendance Table
$attendance = $pdo->query("SELECT * FROM attendance")->fetchAll();
assert(count($attendance) >= 1, 'Attendance logs should exist');
echo "[PASS] 6. Attendance table verified (" . count($attendance) . " logs found).\n";

// 5. Verify Role-Based Redirects
assert(get_role_dashboard('admin') === 'admin_dashboard.php', 'Admin routes to admin_dashboard.php');
assert(get_role_dashboard('teacher') === 'teacher_dashboard.php', 'Teacher routes to teacher_dashboard.php');
assert(get_role_dashboard('im') === 'teacher_dashboard.php', 'IM alias routes to teacher_dashboard.php');
assert(get_role_dashboard('student') === 'student_dashboard.php', 'Student routes to student_dashboard.php');
echo "[PASS] 7. Role dashboard mappings verified.\n";

echo "ALL SYSTEM SPECIFICATION TESTS PASSED SUCCESSFULLY!\n";
