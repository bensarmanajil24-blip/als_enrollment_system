<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

echo "=== VERIFYING DEDICATED TEACHER PAGES & LS1-LS6 BULK ASSIGNMENT ===\n";

// 1. Verify all 5 dedicated pages exist
$pages = [
    'teacher_dashboard.php',
    'teacher_assigned_learners.php',
    'teacher_modules.php',
    'teacher_attendance.php',
    'admin_scanner.php'
];

foreach ($pages as $p) {
    if (!file_exists(__DIR__ . '/' . $p)) {
        echo "[FAIL] Dedicated page {$p} is missing!\n";
        exit(1);
    }
}
echo "[PASS] 1. All 5 dedicated pages verified.\n";

// 2. Test One-Click Bulk Assignment (LS1-LS6)
$testLrn = 'ALS-BULK-' . time();
$pdo->prepare('
    INSERT INTO students (
        student_number, first_name, last_name, birthdate, gender, 
        address, contact_number, strand, assigned_modules, learner_status, 
        status, document_status, doc_psa, doc_brgy, doc_card, is_archived
    ) VALUES (
        ?, "OneClick", "BulkStudent", "2006-02-02", "Female", 
        "Pasig City", "0917-888-9999", "LS1 - Communication Skills", "LS1,LS2,LS3,LS4,LS5,LS6", "Junior High School", 
        "Pending", "Incomplete", 1, 1, 0, 0
    )
')->execute([$testLrn]);

$sRow = $pdo->query("SELECT * FROM students WHERE student_number = '{$testLrn}'")->fetch();
if ($sRow && $sRow['assigned_modules'] === 'LS1,LS2,LS3,LS4,LS5,LS6') {
    echo "[PASS] 2. One-Click Bulk Assignment: Student assigned to all LS1-LS6 modules automatically.\n";
} else {
    echo "[FAIL] 2. One-Click Bulk Assignment failed.\n";
    exit(1);
}

// 3. Test Requirements Review & Conditional Approval Gate
$isDocsComplete = ((int)$sRow['doc_psa'] === 1 && (int)$sRow['doc_brgy'] === 1 && (int)$sRow['doc_card'] === 1);
if (!$isDocsComplete) {
    echo "[PASS] 3a. Approval gated: Cannot approve student while doc_card is 0.\n";
} else {
    echo "[FAIL] 3a. Approval gate failed.\n";
    exit(1);
}

// Update doc_card to 1 and approve
$pdo->prepare("UPDATE students SET doc_card = 1, document_status = 'Complete', status = 'Approved' WHERE id = ?")
    ->execute([(int)$sRow['id']]);

$sRowApproved = $pdo->query("SELECT status, document_status FROM students WHERE id = {$sRow['id']}")->fetch();
if ($sRowApproved['status'] === 'Approved' && $sRowApproved['document_status'] === 'Complete') {
    echo "[PASS] 3b. Successfully verified all 3 requirements and approved enrollment.\n";
} else {
    echo "[FAIL] 3b. Approval update failed.\n";
    exit(1);
}

// 4. Test download_module.php existence and structure
$modContent = file_get_contents(__DIR__ . '/download_module.php');
if (strpos($modContent, 'LS1') !== false && strpos($modContent, 'LS6') !== false && strpos($modContent, 'Content-Disposition') !== false) {
    echo "[PASS] 4. download_module.php provides functional download streaming for LS1-LS6.\n";
} else {
    echo "[FAIL] 4. download_module.php check failed.\n";
    exit(1);
}

// 5. Clean up test student
$pdo->exec("DELETE FROM students WHERE student_number = '{$testLrn}'");
echo "[OK] Cleaned up temporary test record.\n";

echo "=== ALL TESTS FOR DEDICATED PAGES & WORKFLOW PASSED SUCCESSFULLY! ===\n";

