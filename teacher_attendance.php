<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

require_teacher();

$teacherId = (int)($_SESSION['teacher_id'] ?? 0);
$teacher = null;
if ($teacherId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM teachers WHERE id = ?');
    $stmt->execute([$teacherId]);
    $teacher = $stmt->fetch();
}

if (!$teacher) {
    $email = $_SESSION['user_email'] ?? 'elena.reyes@deped.gov.ph';
    $stmt = $pdo->prepare('SELECT * FROM teachers WHERE email = ?');
    $stmt->execute([$email]);
    $teacher = $stmt->fetch();
}

$teacherName = $teacher['name'] ?? ($_SESSION['user_name'] ?? 'Teacher Elena Reyes');
$assignedLs = $teacher['assigned_ls'] ?? 'LS1, LS4, LS6';
$assignedCenter = $teacher['assigned_center'] ?? 'San Nicolas Community Learning Center';
$lsArray = array_filter(array_map('trim', explode(',', $assignedLs)));

$alertMessage = '';
$alertType = 'success';

// ==========================================
// HANDLE CRUD OPERATIONS (CREATE, UPDATE, DELETE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    // 1. CREATE: Manual Attendance Entry
    if ($action === 'manual_attendance' || $action === 'create_attendance') {
        $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $status = trim((string)($_POST['status'] ?? 'Present'));
        $notes = trim((string)($_POST['notes'] ?? 'Regular CLC Session'));
        $customDate = trim((string)($_POST['scan_date'] ?? date('Y-m-d H:i:s')));

        if (!in_array($status, ['Present', 'Late', 'Absent', 'Excuse'], true)) {
            $status = 'Present';
        }

        if ($studentId && $studentId > 0) {
            $stmt = $pdo->prepare('INSERT INTO attendance (student_id, teacher_id, status, notes, scan_date) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$studentId, $teacher['id'] ?? 1, $status, $notes, $customDate]);
            $alertMessage = "Matagumpay na naitala ang bagong attendance entry ({$status}) para sa mag-aaral!";
            $alertType = 'success';
        } else {
            $alertMessage = 'Pakisuyong pumili ng mag-aaral.';
            $alertType = 'danger';
        }
    }

    // 2. UPDATE: Edit Attendance Entry
    elseif ($action === 'update_attendance') {
        $attId = filter_input(INPUT_POST, 'attendance_id', FILTER_VALIDATE_INT);
        $status = trim((string)($_POST['status'] ?? 'Present'));
        $notes = trim((string)($_POST['notes'] ?? ''));
        $scanDate = trim((string)($_POST['scan_date'] ?? ''));

        if (!in_array($status, ['Present', 'Late', 'Absent', 'Excuse'], true)) {
            $status = 'Present';
        }

        if ($attId && $attId > 0) {
            if ($scanDate !== '') {
                $upd = $pdo->prepare('UPDATE attendance SET status = ?, notes = ?, scan_date = ? WHERE id = ?');
                $upd->execute([$status, $notes, $scanDate, $attId]);
            } else {
                $upd = $pdo->prepare('UPDATE attendance SET status = ?, notes = ? WHERE id = ?');
                $upd->execute([$status, $notes, $attId]);
            }
            $alertMessage = "Matagumpay na na-update ang attendance record!";
            $alertType = 'success';
        }
    }

    // 3. DELETE: Delete Attendance Entry
    elseif ($action === 'delete_attendance') {
        $attId = filter_input(INPUT_POST, 'attendance_id', FILTER_VALIDATE_INT);
        if ($attId && $attId > 0) {
            $pdo->prepare('DELETE FROM attendance WHERE id = ?')->execute([$attId]);
            $alertMessage = "Matagumpay na nabura ang attendance entry.";
            $alertType = 'success';
        }
    }
}

// Fetch all assigned students for the manual logging dropdown
$whereClauses = ['is_archived = 0'];
$params = [];
$lsConds = [];
foreach ($lsArray as $lsCode) {
    $lsConds[] = 'strand LIKE ?';
    $params[] = '%' . $lsCode . '%';
}
if (!empty($lsConds)) {
    $whereClauses[] = '(' . implode(' OR ', $lsConds) . ')';
}
$assignedStudents = $pdo->prepare("SELECT id, first_name, last_name, student_number, strand FROM students WHERE " . implode(' AND ', $whereClauses) . " ORDER BY last_name ASC");
$assignedStudents->execute($params);
$studentOptions = $assignedStudents->fetchAll();

// ==========================================
// READ: FILTER ATTENDANCE LOGS
// ==========================================
$searchQuery = trim((string)($_GET['q'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$dateFilter = trim((string)($_GET['date'] ?? ''));

$attWhere = ['1=1'];
$attParams = [];

if ($searchQuery !== '') {
    $attWhere[] = '(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_number LIKE ?)';
    $like = "%{$searchQuery}%";
    array_push($attParams, $like, $like, $like);
}

if ($statusFilter !== '') {
    $attWhere[] = 'a.status = ?';
    $attParams[] = $statusFilter;
}

if ($dateFilter !== '') {
    $attWhere[] = 'DATE(a.scan_date) = ?';
    $attParams[] = $dateFilter;
}

$attSql = "
    SELECT a.*, s.first_name, s.last_name, s.student_number, s.strand, s.learner_status
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE " . implode(' AND ', $attWhere) . "
    ORDER BY a.scan_date DESC
    LIMIT 200
";
$attStmt = $pdo->prepare($attSql);
$attStmt->execute($attParams);
$attendanceLogs = $attStmt->fetchAll();

// Aggregates for Present, Late, Absent, Excuse
$countAll = count($attendanceLogs);
$presentCount = count(array_filter($attendanceLogs, fn($a) => $a['status'] === 'Present'));
$lateCount = count(array_filter($attendanceLogs, fn($a) => $a['status'] === 'Late'));
$absentCount = count(array_filter($attendanceLogs, fn($a) => $a['status'] === 'Absent'));
$excuseCount = count(array_filter($attendanceLogs, fn($a) => $a['status'] === 'Excuse' || $a['status'] === 'Excused'));
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DepEd ALS - Attendance Ledger (CRUD) | Dalubguro Portal</title>
  <link rel="stylesheet" href="assets/admin.css">
  <style>
    /* Status Badges */
    .att-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 750;
    }
    .att-badge.present {
      background: #DCFCE7;
      color: #15803D;
      border: 1px solid #86EFAC;
    }
    .att-badge.late {
      background: #FEF3C7;
      color: #B45309;
      border: 1px solid #FDE68A;
    }
    .att-badge.absent {
      background: #FEE2E2;
      color: #B91C1C;
      border: 1px solid #FCA5A5;
    }
    .att-badge.excuse {
      background: #EBF2FD;
      color: #185ADB;
      border: 1px solid #C7DCFA;
    }

    .filter-bar-row {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
      margin-bottom: 16px;
      background: #FFFFFF;
      padding: 12px 16px;
      border-radius: 12px;
      border: 1px solid var(--deped-border-subtle);
    }
    .search-filter-input {
      padding: 8px 12px;
      border: 1.5px solid #CBD5E1;
      border-radius: 8px;
      font-size: 12.5px;
      outline: none;
      background: #F8FAFD;
      font-family: inherit;
    }
    .search-filter-input:focus {
      border-color: #107C41;
      background: #FFFFFF;
    }

    /* Modal for Update / Edit */
    .deped-modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(7, 22, 44, 0.65);
      backdrop-filter: blur(4px);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      padding: 16px;
      box-sizing: border-box;
    }
    .deped-modal-box {
      background: #FFFFFF;
      width: 100%;
      max-width: 480px;
      border-radius: 16px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
      border: 1px solid #E2E8F0;
      overflow: hidden;
      animation: modalFadeIn 0.2s ease-out;
    }
    @keyframes modalFadeIn {
      from { opacity: 0; transform: scale(0.96); }
      to { opacity: 1; transform: scale(1); }
    }
    .deped-modal-header {
      background: linear-gradient(135deg, #07162C 0%, #0B1E3B 100%);
      color: #FFFFFF;
      padding: 16px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2.5px solid #F4BB4F;
    }
    .deped-modal-body {
      padding: 20px 24px;
    }
    .deped-modal-footer {
      padding: 14px 24px;
      background: #F8FAFD;
      border-top: 1px solid #E2E8F0;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
  </style>
</head>
<body class="admin-body">

<div class="deped-laptop-layout">

  <!-- ==================== SIDEBAR ==================== -->
  <aside class="deped-sidebar">
    <a href="teacher_dashboard.php" class="deped-sidebar-brand">
      <div class="deped-brand-logo-icon" style="background:#107C41;color:#fff;">
        👨‍🏫
      </div>
      <div class="deped-brand-text">
        <h2>DepEd ALS</h2>
        <span style="color:#86EFAC;">TEACHER PORTAL</span>
      </div>
    </a>

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
      <!-- Active Attendance Link -->
      <a href="teacher_attendance.php" class="deped-nav-link active" style="background:#107C41;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        <span>Attendance Tracker</span>
      </a>
      <a href="admin_scanner.php" class="deped-nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><rect x="7" y="7" width="10" height="10" rx="1"></rect></svg>
        <span>Community QR Scanner</span>
      </a>
    </nav>

    <div class="sidebar-footer-info">
      <span><?= e($teacherName) ?></span>
      <a href="logout.php" style="color:#F87171;text-decoration:none;font-weight:700;">Logout</a>
    </div>
  </aside>

  <!-- ==================== MAIN VIEWPORT ==================== -->
  <div class="deped-main-viewport">

    <header class="deped-top-header">
      <div style="font-weight:750;font-size:13.5px;color:var(--deped-text-dark);display:flex;align-items:center;gap:8px;">
        <span class="stat-box-sub-pill green">Attendance Ledger</span>
        <span>A total of <?= $countAll ?> recorded logs (<?= $presentCount ?> Present)</span>
      </div>

      <div class="header-user-profile">
        <a href="admin_scanner.php" class="toolbar-btn blue" style="background:#185ADB;color:#fff;border:none;">
          📷 Open the QR Scanner
        </a>
        <div class="user-profile-badge">
          <div class="user-avatar-circle" style="background:#107C41;">IM</div>
          <div class="user-info-text">
            <strong><?= e($teacherName) ?></strong>
            <span><?= e($assignedLs) ?></span>
          </div>
        </div>
        <a href="logout.php" class="toolbar-btn" style="color:#C5221F;">Mag-logout</a>
      </div>
    </header>

    <main class="deped-workspace-container">

      <?php if ($alertMessage): ?>
        <div style="background:<?= $alertType === 'success' ? '#DCFCE7' : '#FEE2E2' ?>;border:1px solid <?= $alertType === 'success' ? '#86EFAC' : '#FCA5A5' ?>;color:<?= $alertType === 'success' ? '#15803D' : '#B91C1C' ?>;padding:12px 18px;border-radius:10px;font-size:13px;font-weight:700;margin-bottom:18px;">
          <?= $alertType === 'success' ? '✓' : '⚠️' ?> <?= e($alertMessage) ?>
        </div>
      <?php endif; ?>

      <!-- Stat Cards for the 4 Statuses: Present, Late, Absent, Excuse -->
      <section class="stat-cards-4col-grid" style="margin-bottom:16px;">
        <div class="deped-stat-box" style="border-top:3px solid #107C41;">
          <div class="stat-box-header">
            <span class="stat-box-title" style="color:#107C41;">PRESENT (PUMASOK)</span>
            <div class="stat-box-icon green">✓</div>
          </div>
          <div class="stat-box-big-num" style="color:#107C41;"><?= $presentCount ?></div>
          <span class="stat-box-sub-pill green">Aktibong Pumasok</span>
        </div>

        <div class="deped-stat-box" style="border-top:3px solid #D97706;">
          <div class="stat-box-header">
            <span class="stat-box-title" style="color:#D97706;">LATE (NAHULI)</span>
            <div class="stat-box-icon amber">⏱️</div>
          </div>
          <div class="stat-box-big-num" style="color:#D97706;"><?= $lateCount ?></div>
          <span class="stat-box-sub-pill amber">Late Arrivals</span>
        </div>

        <div class="deped-stat-box" style="border-top:3px solid #C5221F;">
          <div class="stat-box-header">
            <span class="stat-box-title" style="color:#C5221F;">ABSENT (LIBAN)</span>
            <div class="stat-box-icon red">✗</div>
          </div>
          <div class="stat-box-big-num" style="color:#C5221F;"><?= $absentCount ?></div>
          <span class="stat-box-sub-pill red">Hindi Nakapasok</span>
        </div>

        <div class="deped-stat-box" style="border-top:3px solid #185ADB;">
          <div class="stat-box-header">
            <span class="stat-box-title" style="color:#185ADB;">EXCUSE (MAY PAALAM)</span>
            <div class="stat-box-icon blue">📝</div>
          </div>
          <div class="stat-box-big-num" style="color:#185ADB;"><?= $excuseCount ?></div>
          <span class="stat-box-sub-pill blue">Excused Absence</span>
        </div>
      </section>

      <!-- 1. CREATE: Manual Attendance Logging Form Card -->
      <div class="deped-panel-card" style="padding:16px 20px;margin-bottom:16px;">
        <strong style="font-size:13px;color:var(--deped-navy-dark);display:block;margin-bottom:8px;">
          ➕ Magtala ng Bagong Attendance Entry (Create)
        </strong>
        <form method="post" action="teacher_attendance.php" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="manual_attendance">

          <select name="student_id" required class="search-filter-input" style="min-width:240px;flex:1;">
            <option value="">Pumili ng Mag-aaral...</option>
            <?php foreach ($studentOptions as $s): ?>
              <option value="<?= (int)$s['id'] ?>">
                <?= e($s['last_name'] . ', ' . $s['first_name'] . ' (' . $s['student_number'] . ') - ' . $s['strand']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <!-- 4 Statuses -->
          <select name="status" class="search-filter-input">
            <option value="Present">Present (Pumasok)</option>
            <option value="Late">Late (Nahuli)</option>
            <option value="Absent">Absent (Liban)</option>
            <option value="Excuse">Excuse (May Paalam)</option>
          </select>

          <input type="text" name="notes" placeholder="Notes (Hal: Session Attendance)" class="search-filter-input" style="flex:1;min-width:180px;">

          <button type="submit" class="toolbar-btn green" style="background:#107C41;color:#fff;border:none;font-weight:750;">
            ✓ I-save ang Entry
          </button>
        </form>
      </div>

      <!-- 2. READ: Filter Controls -->
      <form method="get" action="teacher_attendance.php" class="filter-bar-row">
        <input 
          type="text" 
          name="q" 
          value="<?= e($searchQuery) ?>" 
          placeholder="Maghanap ng Pangalan o LRN..." 
          class="search-filter-input" 
          style="min-width:220px;flex:1;"
        >

        <select name="status" class="search-filter-input">
          <option value="">Lahat ng Status</option>
          <option value="Present" <?= $statusFilter === 'Present' ? 'selected' : '' ?>>Present</option>
          <option value="Late" <?= $statusFilter === 'Late' ? 'selected' : '' ?>>Late</option>
          <option value="Absent" <?= $statusFilter === 'Absent' ? 'selected' : '' ?>>Absent</option>
          <option value="Excuse" <?= $statusFilter === 'Excuse' ? 'selected' : '' ?>>Excuse</option>
        </select>

        <input 
          type="date" 
          name="date" 
          value="<?= e($dateFilter) ?>" 
          class="search-filter-input"
        >

        <button type="submit" class="toolbar-btn green" style="background:#107C41;color:#fff;border:none;">
          I-filter
        </button>

        <?php if ($searchQuery !== '' || $statusFilter !== '' || $dateFilter !== ''): ?>
          <a href="teacher_attendance.php" class="toolbar-btn">I-reset</a>
        <?php endif; ?>
      </form>

      <!-- READ, UPDATE, DELETE: Attendance Ledger Table (Matching Pangatlong Larawan) -->
      <div class="deped-panel-card">
        <div class="panel-header-row">
          <div>
            <h3>Attendance Ledger</h3>
            <p>A total of <?= $countAll ?> recorded logs (<?= $presentCount ?> Present, <?= $lateCount ?> Late, <?= $absentCount ?> Absent, <?= $excuseCount ?> Excuse).</p>
          </div>
          <a href="admin_scanner.php" class="toolbar-btn blue">
            📷 Open the QR Scanner
          </a>
        </div>

        <table class="deped-full-table">
          <thead>
            <tr>
              <th>STUDENT</th>
              <th>LRN / STUDENT NUMBER</th>
              <th>STRAND & LEVEL</th>
              <th>DATE AND TIME</th>
              <th>STATUS</th>
              <th>NOTES</th>
              <th style="text-align:right;">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($attendanceLogs)): ?>
              <tr>
                <td colspan="7" style="text-align:center;padding:36px;color:var(--deped-text-muted);">
                  Walang naitalang attendance record.
                </td>
              </tr>
            <?php endif; ?>

            <?php foreach ($attendanceLogs as $att): 
              $stBadgeClass = match (strtolower($att['status'])) {
                'late' => 'late',
                'absent' => 'absent',
                'excuse', 'excused' => 'excuse',
                default => 'present'
              };
            ?>
              <tr>
                <td>
                  <strong><?= e($att['first_name'] . ' ' . $att['last_name']) ?></strong>
                </td>
                <td style="font-family:ui-monospace,monospace;font-size:12px;color:var(--deped-navy-dark);">
                  <?= e($att['student_number']) ?>
                </td>
                <td>
                  <span style="font-size:12px;color:var(--deped-text-dark);"><?= e($att['strand']) ?></span>
                </td>
                <td style="font-size:12px;color:var(--deped-navy-dark);">
                  <?= date('M d, Y • h:i A', strtotime($att['scan_date'])) ?>
                </td>
                <td>
                  <span class="att-badge <?= $stBadgeClass ?>">
                    ● <?= e($att['status']) ?>
                  </span>
                </td>
                <td style="font-size:11.5px;color:var(--deped-text-muted);">
                  <?= e($att['notes'] ?? 'QR Station Scanner (Present)') ?>
                </td>
                <td style="text-align:right;">
                  <div style="display:inline-flex;align-items:center;gap:6px;">
                    <!-- UPDATE BUTTON -->
                    <button 
                      type="button" 
                      class="btn-form1-icon" 
                      title="Baguhin ang Attendance Entry (Update)"
                      onclick='openEditAttModal(<?= json_encode($att) ?>)'
                    >
                      ✏️
                    </button>

                    <!-- DELETE BUTTON -->
                    <form method="post" action="teacher_attendance.php" style="display:inline;" onsubmit="return confirm('Sigurado ka bang nais mong burahin ang attendance log na ito?');">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete_attendance">
                      <input type="hidden" name="attendance_id" value="<?= (int)$att['id'] ?>">
                      <button type="submit" class="btn-form1-icon" style="color:#C5221F;" title="Burahin ang Log (Delete)">
                        🗑️
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </main>

  </div>

</div>

<!-- ==================== MODAL: UPDATE ATTENDANCE ENTRY ==================== -->
<div class="deped-modal-backdrop" id="editAttModal">
  <div class="deped-modal-box">
    <div class="deped-modal-header">
      <div style="display:flex;align-items:center;gap:8px;">
        <span style="font-size:16px;">✏️</span>
        <div>
          <h3 style="font-size:15px;font-weight:800;margin:0;color:#fff;">Baguhin ang Attendance (Update)</h3>
          <span style="font-size:11px;color:#CBD5E1;" id="editModalStudentName">Student Name</span>
        </div>
      </div>
      <button type="button" onclick="closeEditAttModal()" style="background:none;border:none;color:#CBD5E1;font-size:20px;cursor:pointer;">&times;</button>
    </div>

    <form method="post" action="teacher_attendance.php">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="update_attendance">
      <input type="hidden" name="attendance_id" id="editModalAttId" value="0">

      <div class="deped-modal-body">
        
        <div style="margin-bottom:14px;">
          <label style="display:block;font-size:12px;font-weight:750;color:#0B1E3B;margin-bottom:5px;">
            Katayuan (Status) *
          </label>
          <select name="status" id="editModalStatus" class="search-filter-input" style="width:100%;box-sizing:border-box;">
            <option value="Present">Present (Pumasok)</option>
            <option value="Late">Late (Nahuli)</option>
            <option value="Absent">Absent (Liban)</option>
            <option value="Excuse">Excuse (May Paalam)</option>
          </select>
        </div>

        <div style="margin-bottom:14px;">
          <label style="display:block;font-size:12px;font-weight:750;color:#0B1E3B;margin-bottom:5px;">
            Tala / Remarks (Notes)
          </label>
          <input type="text" name="notes" id="editModalNotes" class="search-filter-input" style="width:100%;box-sizing:border-box;">
        </div>

      </div>

      <div class="deped-modal-footer">
        <button type="button" onclick="closeEditAttModal()" class="toolbar-btn">Kanselahin</button>
        <button type="submit" class="toolbar-btn green" style="background:#107C41;color:#fff;border:none;font-weight:750;">
          💾 I-save ang Pagbabago
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditAttModal(att) {
  document.getElementById('editModalAttId').value = att.id;
  document.getElementById('editModalStudentName').innerText = att.first_name + ' ' + att.last_name + ' (' + att.student_number + ')';
  document.getElementById('editModalStatus').value = att.status;
  document.getElementById('editModalNotes').value = att.notes || '';
  document.getElementById('editAttModal').style.display = 'flex';
}

function closeEditAttModal() {
  document.getElementById('editAttModal').style.display = 'none';
}

window.addEventListener('click', function(event) {
  const modal = document.getElementById('editAttModal');
  if (event.target === modal) closeEditAttModal();
});
</script>

</body>
</html>
