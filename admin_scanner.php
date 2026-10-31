<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Check role: allow both admin and teacher
$userRole = $_SESSION['user_role'] ?? '';
if (!in_array($userRole, ['admin', 'teacher'], true)) {
    // If not logged in, redirect to login
    header('Location: login.php?msg=auth_required');
    exit;
}

$isAdmin = ($userRole === 'admin');
$currentUserId = (int)($_SESSION['user_id'] ?? 1);
$currentUserName = $_SESSION['user_name'] ?? ($isAdmin ? 'Admin' : 'Instructional Manager');
$currentUserEmail = $_SESSION['user_email'] ?? '';

$actionMessage = '';
$actionStatus = 'success';

// Handle Action (Attendance Recording, Requirements Update, Enrollment Approval)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);

    if ($action === 'update_attendance_status') {
        $attId = filter_input(INPUT_POST, 'attendance_id', FILTER_VALIDATE_INT);
        $newStatus = trim((string)($_POST['status'] ?? 'Present'));
        $newNotes = trim((string)($_POST['notes'] ?? ''));
        if ($attId && in_array($newStatus, ['Present', 'Late', 'Absent', 'Excuse'], true)) {
            $updAtt = $pdo->prepare('UPDATE attendance SET status = ?, notes = ? WHERE id = ?');
            $updAtt->execute([$newStatus, $newNotes, $attId]);
            $actionMessage = "Matagumpay na na-update ang attendance status sa '{$newStatus}'!";
            $actionStatus = 'success';
        }
    } elseif ($studentId && $studentId > 0) {
        if ($action === 'record_attendance') {
            $attStatus = trim((string)($_POST['status'] ?? 'Present'));
            if (!in_array($attStatus, ['Present', 'Late', 'Absent', 'Excuse'], true)) {
                $attStatus = 'Present';
            }
            $attNotes = trim((string)($_POST['notes'] ?? ("QR Station Scanner (" . $attStatus . ")")));
            $attStmt = $pdo->prepare('INSERT INTO attendance (student_id, teacher_id, status, notes) VALUES (?, ?, ?, ?)');
            $attStmt->execute([$studentId, $currentUserId, $attStatus, $attNotes]);
            $actionMessage = "Matagumpay na naitala ang Attendance ({$attStatus}) ng mag-aaral!";
            $actionStatus = 'success';
        } elseif ($action === 'quick_approve_student') {
            $updStmt = $pdo->prepare("UPDATE students SET status = 'Approved', document_status = 'Complete', doc_psa = 1, doc_brgy = 1, doc_card = 1 WHERE id = ?");
            $updStmt->execute([$studentId]);
            
            // Also log attendance
            $pdo->prepare('INSERT INTO attendance (student_id, teacher_id, status, notes) VALUES (?, ?, "Present", "Mabilisang Inaprubahan sa LRN List")')
                ->execute([$studentId, $currentUserId]);

            $actionMessage = 'Matagumpay na naaprubahan ang mag-aaral!';
            $actionStatus = 'success';
        } elseif ($action === 'update_student_status') {
            $newStatus = (string)($_POST['new_status'] ?? 'Pending');
            if (in_array($newStatus, ['Approved', 'Pending', 'Rejected'], true)) {
                $pdo->prepare("UPDATE students SET status = ? WHERE id = ?")->execute([$newStatus, $studentId]);
                $actionMessage = "Na-update ang katayuan ng aplikasyon sa {$newStatus}!";
                $actionStatus = 'success';
            }
        } elseif ($action === 'update_docs') {
            $docPsa = isset($_POST['doc_psa']) ? 1 : 0;
            $docBrgy = isset($_POST['doc_brgy']) ? 1 : 0;
            $docCard = isset($_POST['doc_card']) ? 1 : 0;
            $isComplete = ($docPsa === 1 && $docBrgy === 1 && $docCard === 1);
            $docStatus = $isComplete ? 'Complete' : 'Incomplete';

            $updStmt = $pdo->prepare('UPDATE students SET doc_psa = ?, doc_brgy = ?, doc_card = ?, document_status = ? WHERE id = ?');
            $updStmt->execute([$docPsa, $docBrgy, $docCard, $docStatus, $studentId]);
            $actionMessage = 'Na-update ang listahan ng mga naisumiteng dokumento!';
            $actionStatus = 'success';
        } elseif ($action === 'approve_enrollment') {
            $updStmt = $pdo->prepare("UPDATE students SET status = 'Approved', document_status = 'Complete' WHERE id = ?");
            $updStmt->execute([$studentId]);

            // Also log attendance
            $pdo->prepare('INSERT INTO attendance (student_id, teacher_id, status, notes) VALUES (?, ?, "Present", "QR Verification & Approval")')
                ->execute([$studentId, $currentUserId]);

            $actionMessage = 'Matagumpay na naaprubahan ang enrollment ng mag-aaral!';
            $actionStatus = 'success';
        } elseif ($action === 'set_pending') {
            $pdo->prepare("UPDATE students SET status = 'Pending' WHERE id = ?")->execute([$studentId]);
            $actionMessage = 'Inilipat sa Pending ang katayuan ng aplikasyon.';
            $actionStatus = 'warning';
        }
    }
}

// Fetch all unarchived students for the selection list
$allStudentsStmt = $pdo->query('SELECT * FROM students WHERE is_archived = 0 ORDER BY date_registered DESC');
$allStudents = $allStudentsStmt->fetchAll();

// Determine which student is selected to display
$selectedRef = trim((string)($_GET['ref'] ?? ''));
$selectedId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$selectedStudent = null;

if ($selectedId) {
    foreach ($allStudents as $st) {
        if ((int)$st['id'] === $selectedId) {
            $selectedStudent = $st;
            break;
        }
    }
} elseif ($selectedRef !== '') {
    foreach ($allStudents as $st) {
        if ($st['student_number'] === $selectedRef) {
            $selectedStudent = $st;
            break;
        }
    }
}

// Fallback to the first student
if (!$selectedStudent && !empty($allStudents)) {
    $selectedStudent = $allStudents[0];
}

// Compute documents status for selected student
$docPsa = (int)($selectedStudent['doc_psa'] ?? 0);
$docBrgy = (int)($selectedStudent['doc_brgy'] ?? 0);
$docCard = (int)($selectedStudent['doc_card'] ?? 0);
$docsCount = $docPsa + $docBrgy + $docCard;
$isDocsComplete = ($docsCount === 3);

$missingDocs = [];
if (!$docPsa) $missingDocs[] = 'PSA Birth Cert';
if (!$docBrgy) $missingDocs[] = 'Barangay Clearance';
if (!$docCard) $missingDocs[] = 'Form 137 / Card';

$learnerFullName = $selectedStudent 
    ? trim($selectedStudent['first_name'] . ' ' . ($selectedStudent['middle_name'] ? $selectedStudent['middle_name'] . ' ' : '') . $selectedStudent['last_name']) 
    : 'Walang Mag-aaral';

$learnerAge = 19;
if ($selectedStudent && !empty($selectedStudent['birthdate'])) {
    $bday = new DateTime($selectedStudent['birthdate']);
    $today = new DateTime('today');
    $learnerAge = $bday->diff($today)->y ?: 19;
}

// Fetch Today's Recent Scanned Attendance Logs
$todayLogsStmt = $pdo->query('
    SELECT a.*, s.first_name, s.last_name, s.student_number, s.strand, s.learner_status
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    ORDER BY a.scan_date DESC
    LIMIT 20
');
$recentScans = $todayLogsStmt->fetchAll();

$totalEnrollees = count($allStudents);
$approvedCount = count(array_filter($allStudents, fn($s) => $s['status'] === 'Approved'));
$pendingCount = count(array_filter($allStudents, fn($s) => $s['status'] === 'Pending'));
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DEPED ALS • Community & QR Scanner</title>
  <link rel="stylesheet" href="assets/admin.css">
  <style>
    /* ========================================================
       LAPTOP FULL VIEWPORT (ZERO WINDOW SCROLL - 100vh)
       ======================================================== */
    html, body.scanner-laptop-body {
      height: 100vh;
      max-height: 100vh;
      margin: 0;
      padding: 0;
      overflow: hidden;
      background-color: var(--deped-bg-main);
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      font-size: 13px;
    }

    .scanner-master-layout {
      display: flex;
      height: 100vh;
      max-height: 100vh;
      overflow: hidden;
    }

    .scanner-main-content-viewport {
      flex: 1;
      display: flex;
      flex-direction: column;
      height: 100vh;
      max-height: 100vh;
      overflow: hidden;
      background: #F4F7FC;
    }

    /* Top Navigation Header inside Viewport */
    .scanner-top-nav {
      height: 60px;
      background: #FFFFFF;
      border-bottom: 1px solid var(--deped-border-subtle);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 24px;
      flex-shrink: 0;
      box-sizing: border-box;
    }

    /* 3-Column Split for Laptop Screen (Fit strictly in 100vh - 60px) */
    .scanner-workspace-grid {
      display: grid;
      grid-template-columns: 320px 1fr 340px;
      gap: 16px;
      padding: 16px 20px;
      height: calc(100vh - 60px);
      max-height: calc(100vh - 60px);
      box-sizing: border-box;
      overflow: hidden;
    }

    /* Card Panels with strictly internal scrolling */
    .scanner-column-card {
      background: #FFFFFF;
      border: 1px solid var(--deped-border-subtle);
      border-radius: 14px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      height: 100%;
      box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    }

    .column-header-pane {
      padding: 14px 16px;
      border-bottom: 1px solid var(--deped-border-subtle);
      background: #FFFFFF;
      flex-shrink: 0;
    }

    .column-scroll-pane {
      flex: 1;
      overflow-y: auto;
      padding: 14px 16px;
    }

    /* Search & Student List */
    .search-scanner-input {
      width: 100%;
      box-sizing: border-box;
      padding: 10px 12px;
      border: 1.5px solid #CBD5E1;
      border-radius: 8px;
      font-size: 12.5px;
      outline: none;
      background: #F8FAFD;
      transition: all 0.15s ease;
    }
    .search-scanner-input:focus {
      border-color: var(--deped-blue-primary);
      background: #FFFFFF;
      box-shadow: 0 0 0 3px rgba(24, 90, 219, 0.12);
    }

    .student-item-btn {
      width: 100%;
      text-align: left;
      background: #FFFFFF;
      border: 1px solid var(--deped-border-subtle);
      border-radius: 10px;
      padding: 10px 12px;
      margin-bottom: 8px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: all 0.15s ease;
      font-family: inherit;
    }
    .student-item-btn:hover {
      background: #F1F5F9;
      border-color: #94A3B8;
    }
    .student-item-btn.selected {
      background: #EBF2FD;
      border-color: var(--deped-blue-primary);
      box-shadow: 0 2px 8px rgba(24, 90, 219, 0.12);
    }

    /* Center Digital ID Card / QR Pass Display */
    .deped-qr-pass-wrapper {
      max-width: 500px;
      margin: 0 auto;
      background: #FFFFFF;
      border-radius: 16px;
      box-shadow: 0 8px 30px rgba(11, 30, 59, 0.08);
      border: 1px solid var(--deped-border-subtle);
      overflow: hidden;
    }

    .pass-header-navy {
      background: linear-gradient(135deg, #07162C 0%, #0B1E3B 100%);
      color: #FFFFFF;
      padding: 14px 18px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 3px solid #F4BB4F;
    }

    .qr-frame-box-large {
      background: #FFFFFF;
      border: 2px dashed #CBD5E1;
      border-radius: 14px;
      padding: 14px;
      display: inline-flex;
      flex-direction: column;
      align-items: center;
      position: relative;
      margin: 12px 0;
    }

    /* Requirements Checklist Pill */
    .checklist-row-box {
      background: #F8FAFD;
      border: 1px solid var(--deped-border-subtle);
      border-radius: 10px;
      padding: 10px 14px;
      margin-bottom: 12px;
    }

    .doc-check-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 6px 0;
      border-bottom: 1px dashed #E2E8F0;
      font-size: 12px;
    }
    .doc-check-item:last-child {
      border-bottom: none;
    }

    /* Progress bar */
    .doc-meter-track {
      height: 7px;
      background: #E2E8F0;
      border-radius: 999px;
      overflow: hidden;
      margin: 6px 0 10px 0;
    }
    .doc-meter-fill {
      height: 100%;
      border-radius: 999px;
      transition: width 0.3s ease;
    }

    /* Action Buttons */
    .btn-action-primary {
      background: #107C41;
      color: #FFFFFF;
      border: none;
      border-radius: 9px;
      padding: 10px 16px;
      font-size: 13px;
      font-weight: 750;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: all 0.15s ease;
      text-decoration: none;
      width: 100%;
      box-sizing: border-box;
    }
    .btn-action-primary:hover {
      background: #0E6B37;
    }
    .btn-action-primary:disabled {
      background: #94A3B8;
      cursor: not-allowed;
      opacity: 0.7;
    }

    .btn-action-secondary {
      background: #FFFFFF;
      color: var(--deped-navy-dark);
      border: 1.5px solid var(--deped-border-subtle);
      border-radius: 9px;
      padding: 9px 14px;
      font-size: 12.5px;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      width: 100%;
      box-sizing: border-box;
      transition: all 0.15s ease;
    }
    .btn-action-secondary:hover {
      background: #F1F5F9;
      border-color: #94A3B8;
    }

    /* Badges */
    .tag-badge-status {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 750;
    }
    .tag-badge-status.approved,
    .tag-badge-status.status-present {
      background: #EAF7EE;
      color: #107C41;
      border: 1px solid #BEE3CA;
    }
    .tag-badge-status.pending,
    .tag-badge-status.status-late {
      background: #FEF3C7;
      color: #92400E;
      border: 1px solid #FDE68A;
    }
    .tag-badge-status.incomplete,
    .tag-badge-status.status-absent {
      background: #FEE2E2;
      color: #991B1B;
      border: 1px solid #FCA5A5;
    }
    .tag-badge-status.status-excuse {
      background: #EBF3FC;
      color: #185ADB;
      border: 1px solid #C4DDF8;
    }

    /* Clickable Recent Scan Item */
    .recent-scan-clickable {
      padding: 10px 12px;
      border-bottom: 1px solid var(--deped-border-subtle);
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      cursor: pointer;
      border-radius: 8px;
      margin: 2px 0;
      transition: all 0.15s ease;
    }
    .recent-scan-clickable:hover {
      background: #F1F5F9;
      transform: translateX(2px);
      box-shadow: 0 2px 6px rgba(15, 23, 42, 0.05);
    }

    /* Modal Backdrop and Box */
    .als-modal-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(11, 30, 59, 0.65);
      backdrop-filter: blur(4px);
      z-index: 9999;
      display: none;
      place-items: center;
      padding: 20px;
      box-sizing: border-box;
    }
    .als-modal-backdrop.active {
      display: grid;
    }
    .als-modal-card {
      background: #FFFFFF;
      width: 100%;
      max-width: 480px;
      border-radius: 16px;
      box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
      overflow: hidden;
      animation: modalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes modalFadeIn {
      from { opacity: 0; transform: scale(0.95) translateY(10px); }
      to { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* Print styles */
    @media print {
      body.scanner-laptop-body { overflow: visible !important; height: auto !important; }
      .scanner-master-layout aside, .scanner-top-nav, .scanner-column-card:first-child, .scanner-column-card:last-child {
        display: none !important;
      }
      .scanner-workspace-grid { display: block !important; padding: 0 !important; }
      .deped-qr-pass-wrapper { box-shadow: none !important; border: 1px solid #000 !important; }
    }
  </style>
</head>
<body class="scanner-laptop-body">

<div class="scanner-master-layout">

  <!-- ==================== SIDEBAR (ROLE-AWARE) ==================== -->
  <aside class="deped-sidebar">
    <a href="<?= $isAdmin ? 'admin_dashboard.php' : 'teacher_dashboard.php' ?>" class="deped-sidebar-brand">
      <div class="deped-brand-logo-icon" style="background:<?= $isAdmin ? '#F4BB4F' : '#107C41' ?>;color:<?= $isAdmin ? '#0B1E3B' : '#fff' ?>;">
        <?= $isAdmin ? '🛡️' : '👨‍🏫' ?>
      </div>
      <div class="deped-brand-text">
        <h2>DepEd ALS</h2>
        <span><?= $isAdmin ? 'ADMINISTRATOR' : 'TEACHER PORTAL' ?></span>
      </div>
    </a>

    <?php if ($isAdmin): ?>
      <div class="sidebar-section-title">ADMIN & COORDINATOR</div>
      <nav class="sidebar-nav-group">
        <a href="admin_dashboard.php" class="deped-nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
          <span>Dashboard</span>
        </a>
        <a href="admin_enrollment_strands.php" class="deped-nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          <span>Enrollment & Strands</span>
        </a>
        <a href="teachers_registry.php" class="deped-nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          <span>Teachers Registry</span>
        </a>
        <a href="student_masterlist.php" class="deped-nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
          <span>Student Masterlist</span>
        </a>
        <a href="division_reports.php" class="deped-nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
          <span>Division Reports</span>
        </a>
        <a href="admin_scanner.php" class="deped-nav-link active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><rect x="7" y="7" width="10" height="10" rx="1"></rect></svg>
          <span>Community QR Scanner</span>
        </a>
      </nav>
    <?php else: ?>
      <div class="sidebar-section-title">INSTRUCTIONAL MANAGER</div>
      <nav class="sidebar-nav-group">
        <a href="teacher_dashboard.php" class="deped-nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
          <span>Dashboard</span>
        </a>
        <a href="teacher_assigned_learners.php" class="deped-nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
          <span>Assigned Learners</span>
        </a>
        <a href="teacher_modules.php" class="deped-nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
          <span>Learning Modules (LS1-LS6)</span>
        </a>
        <a href="teacher_attendance.php" class="deped-nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          <span>Attendance Tracker</span>
        </a>
        <a href="admin_scanner.php" class="deped-nav-link active" style="background:#107C41;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><rect x="7" y="7" width="10" height="10" rx="1"></rect></svg>
          <span>Community QR Scanner</span>
        </a>
      </nav>
    <?php endif; ?>

    <div class="sidebar-footer-info">
      <span><?= e($currentUserName) ?></span>
      <a href="logout.php" style="color:#F87171;text-decoration:none;font-weight:700;">Logout</a>
    </div>
  </aside>

  <!-- ==================== MAIN VIEWPORT ==================== -->
  <div class="scanner-main-content-viewport">

    <!-- Top Navigation Header -->
    <header class="scanner-top-nav">
      <div style="display:flex;align-items:center;gap:12px;">
        <span style="background:<?= $isAdmin ? '#EBF2FD' : '#EAF7EE' ?>;color:<?= $isAdmin ? '#185ADB' : '#107C41' ?>;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:800;">
          <?= $isAdmin ? '🛡️ ALS COORDINATOR' : '👨‍🏫 DALUBGURO / IM' ?>
        </span>
        <h2 style="font-size:15px;font-weight:850;margin:0;color:var(--deped-navy-dark);">
          Community & QR Scanner Station (Laptop View)
        </h2>
      </div>

      <div style="display:flex;align-items:center;gap:14px;">
        <span style="font-size:12px;color:var(--deped-text-muted);">
          Kabuuang Mag-aaral: <strong style="color:var(--deped-navy-dark);"><?= $totalEnrollees ?></strong>
        </span>
        <span style="font-size:12px;color:var(--deped-text-muted);">
          Naaprubahan: <strong style="color:#107C41;"><?= $approvedCount ?></strong>
        </span>
        <span style="font-size:12px;color:var(--deped-text-muted);">
          Nangangailangan ng Review: <strong style="color:#D97706;"><?= $pendingCount ?></strong>
        </span>
        <a href="<?= $isAdmin ? 'admin_dashboard.php' : 'teacher_dashboard.php' ?>" class="toolbar-btn">
          &larr; Bumalik sa Dashboard
        </a>
      </div>
    </header>

    <!-- 3-Column Laptop Grid (Fit within screen height) -->
    <main class="scanner-workspace-grid">

      <!-- ==================== COLUMN 1: MAGHANAP O PUMILI NG MAG-AARAL ==================== -->
      <div class="scanner-column-card">
        <div class="column-header-pane">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <strong style="font-size:13px;color:var(--deped-navy-dark);">🔍 Maghanap / I-scan ang LRN</strong>
            <span class="tag-badge-status approved">Scanner Ready</span>
          </div>
          <p style="font-size:11px;color:var(--deped-text-muted);margin:0 0 10px 0;">
            I-type ang LRN o i-scan gamit ang handheld / external scanner.
          </p>
          <input 
            type="text" 
            id="studentSearchInput" 
            class="search-scanner-input" 
            placeholder="I-type ang LRN o Pangalan..." 
            autofocus 
            oninput="filterStudentList(this.value)"
          >
        </div>

        <div class="column-scroll-pane" id="studentListContainer">
          <?php foreach ($allStudents as $st): 
            $stName = trim($st['first_name'] . ' ' . $st['last_name']);
            $isSelected = ($selectedStudent && (int)$selectedStudent['id'] === (int)$st['id']);
            $isComplete = ((int)$st['doc_psa'] === 1 && (int)$st['doc_brgy'] === 1 && (int)$st['doc_card'] === 1);
          ?>
            <div 
              class="student-item-btn <?= $isSelected ? 'selected' : '' ?>"
              data-search="<?= strtolower($stName . ' ' . $st['student_number'] . ' ' . $st['strand']) ?>"
              onclick="window.location.href='admin_scanner.php?id=<?= (int)$st['id'] ?>'"
            >
              <div style="width:34px;height:34px;border-radius:8px;background:<?= $isSelected ? '#185ADB' : '#E2E8F0' ?>;color:<?= $isSelected ? '#fff' : '#0F172A' ?>;display:grid;place-items:center;font-weight:800;font-size:12px;flex-shrink:0;">
                <?= strtoupper(substr($st['first_name'], 0, 1) . substr($st['last_name'], 0, 1)) ?>
              </div>
              <div style="flex:1;min-width:0;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                  <strong style="font-size:12.5px;color:var(--deped-navy-dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($stName) ?></strong>
                  <span class="tag-badge-status <?= $st['status'] === 'Approved' ? 'approved' : 'pending' ?>" style="font-size:9.5px;">
                    <?= e($st['status']) ?>
                  </span>
                </div>
                <div style="font-size:11px;color:var(--deped-text-muted);font-family:ui-monospace,monospace;">
                  <?= e($st['student_number']) ?>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;">
                  <span style="font-size:10px;color:<?= $isComplete ? '#107C41' : '#D97706' ?>;font-weight:700;">
                    <?= $isComplete ? '✓ Kumpleto Docs' : '⚠️ May Kulang' ?>
                  </span>

                  <!-- Interactive Action Button: Direct Quick Approve / Status Management -->
                  <div onclick="event.stopPropagation();" style="display:flex;gap:4px;align-items:center;">
                    <?php if ($st['status'] !== 'Approved'): ?>
                      <form method="post" action="admin_scanner.php?id=<?= (int)$st['id'] ?>" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="quick_approve_student">
                        <input type="hidden" name="student_id" value="<?= (int)$st['id'] ?>">
                        <button type="submit" style="background:#107C41;color:#FFFFFF;border:none;border-radius:6px;padding:3px 8px;font-size:10.5px;font-weight:750;cursor:pointer;display:inline-flex;align-items:center;gap:3px;" title="I-apruba Agad ang Mag-aaral">
                          ✓ Aprubahan
                        </button>
                      </form>
                    <?php else: ?>
                      <form method="post" action="admin_scanner.php?id=<?= (int)$st['id'] ?>" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="set_pending">
                        <input type="hidden" name="student_id" value="<?= (int)$st['id'] ?>">
                        <button type="submit" style="background:#FFFBEB;color:#92400E;border:1px solid #FDE68A;border-radius:6px;padding:2px 7px;font-size:10px;font-weight:700;cursor:pointer;" title="Ibalik sa Pending">
                          Pending
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ==================== COLUMN 2: DIGITAL ID CARD & QR CODE DISPLAY ==================== -->
      <div class="scanner-column-card">
        <div class="column-header-pane" style="display:flex;justify-content:space-between;align-items:center;">
          <div>
            <strong style="font-size:13.5px;color:var(--deped-navy-dark);">Official Learner Pass & QR Display</strong>
            <span style="font-size:11px;color:var(--deped-text-muted);display:block;">Digital Verification Hub para sa CLC</span>
          </div>
          <button type="button" onclick="window.print()" class="btn-action-secondary" style="width:auto;padding:6px 12px;font-size:11.5px;">
            🖨️ I-print ang Pass
          </button>
        </div>

        <div class="column-scroll-pane">
          <?php if ($actionMessage): ?>
            <div style="background:<?= $actionStatus === 'success' ? '#DCFCE7' : ($actionStatus === 'danger' ? '#FEE2E2' : '#FEF3C7') ?>;border:1px solid <?= $actionStatus === 'success' ? '#86EFAC' : ($actionStatus === 'danger' ? '#FCA5A5' : '#FDE68A') ?>;color:<?= $actionStatus === 'success' ? '#15803D' : ($actionStatus === 'danger' ? '#B91C1C' : '#92400E') ?>;padding:10px 14px;border-radius:10px;font-size:12.5px;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
              <?= $actionStatus === 'success' ? '✓' : '⚠️' ?> <?= e($actionMessage) ?>
            </div>
          <?php endif; ?>

          <?php if ($selectedStudent): ?>
            <div class="deped-qr-pass-wrapper">

              <!-- Blue Top Card Header -->
              <div class="pass-header-navy">
                <div style="display:flex;align-items:center;gap:8px;">
                  <span style="font-size:13px;">🎓</span>
                  <div>
                    <span style="font-size:10px;color:#CBD5E1;font-weight:700;letter-spacing:0.04em;display:block;">DEPED ALS OPISYAL NA PASS</span>
                    <strong style="font-size:11.5px;color:#FFFFFF;">Taong Panuruan 2026</strong>
                  </div>
                </div>
                <span class="tag-badge-status <?= $selectedStudent['status'] === 'Approved' ? 'approved' : 'pending' ?>">
                  ● <?= e($selectedStudent['status']) ?>
                </span>
              </div>

              <div style="padding:18px 20px;text-align:center;">
                <!-- Tracking Number with Copy -->
                <div style="background:#EBF3FC;border:1px solid #C4DDF8;border-radius:8px;padding:8px 12px;display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                  <div style="text-align:left;">
                    <span style="font-size:9.5px;font-weight:800;color:var(--deped-text-muted);display:block;">TRACKING REFERENCE / LRN</span>
                    <strong style="font-size:13.5px;color:var(--deped-navy-dark);font-family:ui-monospace,monospace;" id="selectedLrnDisplay">
                      <?= e($selectedStudent['student_number']) ?>
                    </strong>
                  </div>
                  <button type="button" onclick="copyCurrentLrn()" style="background:#FFFFFF;border:1px solid #CBD5E1;border-radius:6px;padding:4px 10px;font-size:11px;font-weight:750;color:var(--deped-blue-primary);cursor:pointer;">
                    Kopyahin 📋
                  </button>
                </div>

                <!-- Learner Name & Program -->
                <h3 style="font-size:17px;font-weight:850;color:var(--deped-navy-dark);margin:0 0 4px 0;">
                  <?= e($learnerFullName) ?>
                </h3>
                <div style="font-size:12px;color:var(--deped-text-muted);margin-bottom:8px;">
                  <span><?= $learnerAge ?> anyos</span> • 
                  <strong><?= e($selectedStudent['civil_status'] ?? 'Single') ?></strong> • 
                  <strong><?= e($selectedStudent['learner_status']) ?></strong> • 
                  <span><?= e($selectedStudent['strand']) ?></span>
                </div>
                <div style="font-size:11px;color:#64748B;margin-bottom:12px;">
                  📍 <?= e($selectedStudent['assigned_center'] ?? 'San Nicolas Community Learning Center') ?>
                </div>

                <!-- QR Code Display (Encodes strictly the Student's Full Name) -->
                <div class="qr-frame-box-large">
                  <div style="position:absolute;top:6px;left:6px;width:12px;height:12px;border-top:2.5px solid var(--deped-blue-primary);border-left:2.5px solid var(--deped-blue-primary);"></div>
                  <div style="position:absolute;top:6px;right:6px;width:12px;height:12px;border-top:2.5px solid var(--deped-blue-primary);border-right:2.5px solid var(--deped-blue-primary);"></div>
                  <div style="position:absolute;bottom:6px;left:6px;width:12px;height:12px;border-bottom:2.5px solid var(--deped-blue-primary);border-left:2.5px solid var(--deped-blue-primary);"></div>
                  <div style="position:absolute;bottom:6px;right:6px;width:12px;height:12px;border-bottom:2.5px solid var(--deped-blue-primary);border-right:2.5px solid var(--deped-blue-primary);"></div>

                  <!-- QR image encoding strictly the student's full name -->
                  <img 
                    src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&amp;margin=4&amp;data=<?= urlencode($learnerFullName) ?>" 
                    alt="QR Code ni <?= e($learnerFullName) ?>" 
                    width="160" 
                    height="160" 
                    style="display:block;margin:0 auto;border-radius:6px;background:#ffffff;"
                    loading="lazy"
                  >

                  <div style="margin-top:8px;text-align:center;">
                    <span style="font-size:11px;color:#185ADB;font-weight:750;background:#EFF6FF;padding:4px 10px;border-radius:6px;display:inline-block;">
                      🔍 Scannable Name: <strong><?= e($learnerFullName) ?></strong>
                    </span>
                  </div>
                </div>

                <!-- 4 Attendance Status Quick Action Grid (Present, Late, Absent, Excuse) -->
                <div style="background:#F8FAFD;border:1px solid var(--deped-border-subtle);border-radius:12px;padding:12px 14px;margin-bottom:12px;text-align:left;">
                  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <strong style="font-size:12px;color:var(--deped-navy-dark);">Itala ang Attendance</strong>
                    <span style="font-size:10.5px;color:var(--deped-text-muted);">Pumili ng Katayuan (1-Click)</span>
                  </div>
                  <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <!-- Present -->
                    <form method="post" action="admin_scanner.php?id=<?= (int)$selectedStudent['id'] ?>" style="margin:0;">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="record_attendance">
                      <input type="hidden" name="student_id" value="<?= (int)$selectedStudent['id'] ?>">
                      <input type="hidden" name="status" value="Present">
                      <button type="submit" style="width:100%;background:#EAF7EE;color:#107C41;border:1.5px solid #86EFAC;border-radius:8px;padding:8px 10px;font-weight:750;font-size:11.5px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px;">
                        ✓ Present
                      </button>
                    </form>

                    <!-- Late -->
                    <form method="post" action="admin_scanner.php?id=<?= (int)$selectedStudent['id'] ?>" style="margin:0;">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="record_attendance">
                      <input type="hidden" name="student_id" value="<?= (int)$selectedStudent['id'] ?>">
                      <input type="hidden" name="status" value="Late">
                      <button type="submit" style="width:100%;background:#FEF3C7;color:#92400E;border:1.5px solid #FDE68A;border-radius:8px;padding:8px 10px;font-weight:750;font-size:11.5px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px;">
                        ⏱️ Late
                      </button>
                    </form>

                    <!-- Absent -->
                    <form method="post" action="admin_scanner.php?id=<?= (int)$selectedStudent['id'] ?>" style="margin:0;">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="record_attendance">
                      <input type="hidden" name="student_id" value="<?= (int)$selectedStudent['id'] ?>">
                      <input type="hidden" name="status" value="Absent">
                      <button type="submit" style="width:100%;background:#FEE2E2;color:#991B1B;border:1.5px solid #FCA5A5;border-radius:8px;padding:8px 10px;font-weight:750;font-size:11.5px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px;">
                        ✕ Absent
                      </button>
                    </form>

                    <!-- Excuse -->
                    <form method="post" action="admin_scanner.php?id=<?= (int)$selectedStudent['id'] ?>" style="margin:0;">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="record_attendance">
                      <input type="hidden" name="student_id" value="<?= (int)$selectedStudent['id'] ?>">
                      <input type="hidden" name="status" value="Excuse">
                      <button type="submit" style="width:100%;background:#EBF3FC;color:#185ADB;border:1.5px solid #C4DDF8;border-radius:8px;padding:8px 10px;font-weight:750;font-size:11.5px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px;">
                        ✉️ Excuse
                      </button>
                    </form>
                  </div>
                </div>

                <!-- Action Controls (Approval & Profile Link) -->
                <div style="display:flex;flex-direction:column;gap:8px;">
                  <?php if ($selectedStudent['status'] !== 'Approved'): ?>
                    <form method="post" action="admin_scanner.php?id=<?= (int)$selectedStudent['id'] ?>">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="approve_enrollment">
                      <input type="hidden" name="student_id" value="<?= (int)$selectedStudent['id'] ?>">
                      <button type="submit" class="btn-action-primary">
                        ✓ I-apruba ang Enrollment ng Mag-aaral
                      </button>
                    </form>
                  <?php else: ?>
                    <div style="background:#EAF7EE;border:1px solid #BEE3CA;color:#107C41;padding:9px 12px;border-radius:8px;font-weight:750;font-size:12px;display:flex;justify-content:space-between;align-items:center;">
                      <span>✓ Naaprubahan na ang Opisyal na Enrollment</span>
                      <form method="post" action="admin_scanner.php?id=<?= (int)$selectedStudent['id'] ?>" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="set_pending">
                        <input type="hidden" name="student_id" value="<?= (int)$selectedStudent['id'] ?>">
                        <button type="submit" style="background:transparent;border:none;color:#92400E;font-size:11px;font-weight:700;cursor:pointer;text-decoration:underline;">
                          Ibalik sa Pending
                        </button>
                      </form>
                    </div>
                  <?php endif; ?>

                  <a href="student_view.php?id=<?= (int)$selectedStudent['id'] ?>" class="btn-action-secondary">
                    📄 Tingnan ang Buong Profile ng Estudyante
                  </a>
                </div>

              </div>

            </div>
          <?php else: ?>
            <div style="text-align:center;padding:40px 20px;color:var(--deped-text-muted);">
              Pumili ng estudyante sa kaliwang listahan o mag-scan gamit ang handheld scanner.
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ==================== COLUMN 3: KAMAKAILANG NA-SCAN AT ATTENDANCE LOGS ==================== -->
      <div class="scanner-column-card">
        <div class="column-header-pane">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <strong style="font-size:13px;color:var(--deped-navy-dark);">📋 Kamakailang Na-scan</strong>
            <span class="tag-badge-status approved"><?= count($recentScans) ?> Naitala</span>
          </div>
          <span style="font-size:11px;color:var(--deped-text-muted);display:block;margin-top:2px;">
            Real-time attendance logs mula sa QR Station
          </span>
        </div>

        <div class="column-scroll-pane">
          <?php if (empty($recentScans)): ?>
            <div style="text-align:center;padding:30px 14px;color:var(--deped-text-muted);font-size:12px;">
              Wala pang naitalang scan ngayong araw.
            </div>
          <?php else: ?>
            <?php foreach ($recentScans as $sc): 
              $scStatus = $sc['status'] ?? 'Present';
              $badgeClass = 'status-present';
              if ($scStatus === 'Late') $badgeClass = 'status-late';
              elseif ($scStatus === 'Absent') $badgeClass = 'status-absent';
              elseif ($scStatus === 'Excuse') $badgeClass = 'status-excuse';
            ?>
              <div 
                class="recent-scan-clickable"
                onclick="openRecentModal(<?= htmlspecialchars(json_encode([
                  'id' => (int)$sc['id'],
                  'student_id' => (int)$sc['student_id'],
                  'name' => $sc['first_name'] . ' ' . $sc['last_name'],
                  'student_number' => $sc['student_number'],
                  'strand' => $sc['strand'] ?? '',
                  'status' => $scStatus,
                  'scan_date' => date('M d, Y • h:i A', strtotime($sc['scan_date'])),
                  'notes' => $sc['notes'] ?? ''
                ]), ENT_QUOTES, 'UTF-8') ?>)"
                title="I-click upang makita ang detalye o baguhin ang katayuan (Present, Late, Absent, Excuse)"
              >
                <div>
                  <strong style="font-size:12.5px;color:var(--deped-navy-dark);display:block;">
                    <?= e($sc['first_name'] . ' ' . $sc['last_name']) ?>
                  </strong>
                  <span style="font-size:10.5px;color:var(--deped-text-muted);font-family:ui-monospace,monospace;">
                    <?= e($sc['student_number']) ?>
                  </span>
                  <span style="font-size:10px;color:#64748B;display:block;">
                    <?= e($sc['notes'] ?? 'QR Station Scan') ?>
                  </span>
                </div>
                <div style="text-align:right;">
                  <span class="tag-badge-status <?= $badgeClass ?>" style="font-size:9.5px;">
                    ● <?= e($scStatus) ?>
                  </span>
                  <span style="font-size:10px;color:var(--deped-text-muted);display:block;margin-top:2px;">
                    <?= date('h:i A', strtotime($sc['scan_date'])) ?>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </main>

  </div>

</div>

<!-- Modal for viewing & updating Recent Scan Attendance Status -->
<div id="recentDetailModal" class="als-modal-backdrop">
  <div class="als-modal-card">
    <div style="background:linear-gradient(135deg, #07162C 0%, #0B1E3B 100%);color:#fff;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;border-bottom:3px solid #F4BB4F;">
      <div>
        <strong style="font-size:14px;color:#fff;" id="modalStudentName">Pangalan ng Mag-aaral</strong>
        <span style="font-size:11px;color:#CBD5E1;display:block;" id="modalStudentNumber">2026-000-001</span>
      </div>
      <button type="button" onclick="closeRecentModal()" style="background:transparent;border:none;color:#CBD5E1;font-size:22px;cursor:pointer;line-height:1;">&times;</button>
    </div>

    <form method="post" action="admin_scanner.php<?= $selectedStudent ? '?id=' . (int)$selectedStudent['id'] : '' ?>" style="padding:20px;">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="update_attendance_status">
      <input type="hidden" name="attendance_id" id="modalAttendanceId" value="">

      <div style="margin-bottom:14px;background:#F8FAFC;padding:10px 14px;border-radius:10px;border:1px solid #E2E8F0;font-size:12px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
          <span style="color:#64748B;">Petsa at Oras ng Scan:</span>
          <strong style="color:#0F172A;" id="modalScanDate">-</strong>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <span style="color:#64748B;">Kasalukuyang Status:</span>
          <span class="tag-badge-status" id="modalCurrentStatusBadge">Present</span>
        </div>
      </div>

      <div style="margin-bottom:14px;">
        <label style="font-size:12px;font-weight:750;color:var(--deped-navy-dark);display:block;margin-bottom:6px;">
          Pumili ng Katayuan (Present, Late, Absent, Excuse):
        </label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
          <label style="display:flex;align-items:center;gap:6px;border:1.5px solid #86EFAC;background:#EAF7EE;padding:8px 10px;border-radius:8px;cursor:pointer;font-size:12px;font-weight:700;color:#107C41;">
            <input type="radio" name="status" value="Present" id="statusRadioPresent">
            <span>✓ Present</span>
          </label>
          <label style="display:flex;align-items:center;gap:6px;border:1.5px solid #FDE68A;background:#FEF3C7;padding:8px 10px;border-radius:8px;cursor:pointer;font-size:12px;font-weight:700;color:#92400E;">
            <input type="radio" name="status" value="Late" id="statusRadioLate">
            <span>⏱️ Late</span>
          </label>
          <label style="display:flex;align-items:center;gap:6px;border:1.5px solid #FCA5A5;background:#FEE2E2;padding:8px 10px;border-radius:8px;cursor:pointer;font-size:12px;font-weight:700;color:#991B1B;">
            <input type="radio" name="status" value="Absent" id="statusRadioAbsent">
            <span>✕ Absent</span>
          </label>
          <label style="display:flex;align-items:center;gap:6px;border:1.5px solid #C4DDF8;background:#EBF3FC;padding:8px 10px;border-radius:8px;cursor:pointer;font-size:12px;font-weight:700;color:#185ADB;">
            <input type="radio" name="status" value="Excuse" id="statusRadioExcuse">
            <span>✉️ Excuse</span>
          </label>
        </div>
      </div>

      <div style="margin-bottom:16px;">
        <label for="modalNotesInput" style="font-size:12px;font-weight:750;color:var(--deped-navy-dark);display:block;margin-bottom:4px;">
          Puna / Tala (Notes):
        </label>
        <input type="text" name="notes" id="modalNotesInput" class="search-scanner-input" placeholder="Hal: QR Scanner Station o Dahilan kung bakit Excuse...">
      </div>

      <div style="display:flex;gap:10px;justify-content:space-between;align-items:center;">
        <a id="modalViewPassBtn" href="#" class="btn-action-secondary" style="width:auto;padding:8px 14px;font-size:12px;">
          🔍 Buksan ang Pass
        </a>
        <div style="display:flex;gap:8px;">
          <button type="button" onclick="closeRecentModal()" class="btn-action-secondary" style="width:auto;padding:8px 14px;font-size:12px;">
            Kanselahin
          </button>
          <button type="submit" class="btn-action-primary" style="width:auto;padding:8px 16px;font-size:12px;">
            ✓ I-save ang Katayuan
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- JavaScript Controllers -->
<script>
// Filter Student List by Name or LRN
function filterStudentList(query) {
  const q = query.toLowerCase().trim();
  const items = document.querySelectorAll('#studentListContainer .student-item-btn');
  items.forEach(btn => {
    const text = btn.getAttribute('data-search') || '';
    if (text.includes(q)) {
      btn.style.display = 'flex';
    } else {
      btn.style.display = 'none';
    }
  });
}

// Copy LRN to Clipboard
function copyCurrentLrn() {
  const lrn = document.getElementById('selectedLrnDisplay').innerText.trim();
  navigator.clipboard.writeText(lrn).then(() => {
    alert('Na-kopyang LRN: ' + lrn);
  }).catch(() => {
    prompt('Kopyahin ang LRN:', lrn);
  });
}

// Open Recent Scan Modal for Inspection & Status Edit
function openRecentModal(data) {
  document.getElementById('modalAttendanceId').value = data.id;
  document.getElementById('modalStudentName').textContent = data.name;
  document.getElementById('modalStudentNumber').textContent = data.student_number;
  document.getElementById('modalScanDate').textContent = data.scan_date;
  document.getElementById('modalNotesInput').value = data.notes || '';
  document.getElementById('modalViewPassBtn').href = 'admin_scanner.php?id=' + data.student_id;

  const badge = document.getElementById('modalCurrentStatusBadge');
  badge.textContent = data.status;
  badge.className = 'tag-badge-status';
  if (data.status === 'Present') badge.classList.add('status-present');
  else if (data.status === 'Late') badge.classList.add('status-late');
  else if (data.status === 'Absent') badge.classList.add('status-absent');
  else if (data.status === 'Excuse') badge.classList.add('status-excuse');

  // Radio selection
  const radio = document.getElementById('statusRadio' + data.status);
  if (radio) radio.checked = true;

  document.getElementById('recentDetailModal').classList.add('active');
}

function closeRecentModal() {
  document.getElementById('recentDetailModal').classList.remove('active');
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
  const modal = document.getElementById('recentDetailModal');
  if (e.target === modal) {
    closeRecentModal();
  }
});
</script>

</body>
</html>
