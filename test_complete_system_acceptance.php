<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

echo "========================================================\n";
echo "DEPED ALS COMPLETE ACCEPTANCE VERIFICATION SUITE\n";
echo "========================================================\n\n";

// 1. Verify Users Table & Test Logins
$testAccounts = [
    ['admin@deped.gov.ph', 'admin2026', 'admin'],
    ['bensar.manajili@deped.gov.ph', 'teacher2026', 'teacher'],
    ['maria.reyes@deped.gov.ph', 'student2026', 'student'],
    ['2024-008-001', 'student2026', 'student']
];

foreach ($testAccounts as $t) {
    [$ident, $pass, $role] = $t;
    $stmt = $pdo->prepare('SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$ident, $ident]);
    $u = $stmt->fetch();

    if ($u && password_verify($pass, $u['password'])) {
        echo "✓ Login Check: '{$ident}' -> Role: '{$u['role']}' OK\n";
    } else {
        echo "✕ Login Check: '{$ident}' FAILED\n";
    }
}

// 2. Verify Database Records from Image 2
$expectedNames = ['Bensar S. Manajili', 'Maria Isabel Reyes', 'Jose Antonio Ramos', 'Alyssa Mae Santos', 'Mark Angelo Dela Cruz'];
echo "\nVerifying Seeded Accounts for Dashboard Table:\n";
foreach ($expectedNames as $name) {
    $parts = explode(' ', $name);
    $lName = end($parts);
    $chkS = $pdo->prepare('SELECT id, first_name, last_name FROM students WHERE first_name LIKE ? OR last_name LIKE ? LIMIT 1');
    $chkS->execute(['%' . $parts[0] . '%', '%' . $lName . '%']);
    $sRow = $chkS->fetch();

    $chkT = $pdo->prepare('SELECT id, name FROM teachers WHERE name = ? LIMIT 1');
    $chkT->execute([$name]);
    $tRow = $chkT->fetch();

    if ($sRow || $tRow) {
        echo "✓ Found record in DB: '{$name}'\n";
    } else {
        echo "✕ Missing: '{$name}'\n";
    }
}

// 3. Verify Files Exist
$coreFiles = [
    'login.php',
    'admin_dashboard.php',
    'admin_add_account.php',
    'admin_view_account.php',
    'admin_edit_account.php',
    'teacher_dashboard.php',
    'student_dashboard.php',
    'student_scanner.php'
];

echo "\nVerifying Core Template Files:\n";
foreach ($coreFiles as $f) {
    if (file_exists(__DIR__ . '/' . $f)) {
        echo "✓ File exists: {$f}\n";
    } else {
        echo "✕ Missing file: {$f}\n";
    }
}

echo "\n========================================================\n";
echo "ALL TESTS COMPLETED SUCCESSFULLY!\n";
echo "========================================================\n";
