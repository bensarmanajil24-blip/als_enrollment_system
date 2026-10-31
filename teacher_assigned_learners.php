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

// Handle Actions (Attendance Record & Safety Archive)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);

    if ($studentId && $studentId > 0) {
        if ($action === 'record_attendance') {
            $notes = trim((string)($_POST['notes'] ?? 'Assigned Learners Sheet'));
            $pdo->prepare('INSERT INTO attendance (student_id, teacher_id, status, notes) VALUES (?, ?, "Present", ?)')
                ->execute([$studentId, $teacher['id'] ?? 1, $notes]);
            $alertMessage = 'Matagumpay na naitala ang Attendance (Present)!';
            $alertType = 'success';
        } elseif ($action === 'archive_student') {
            $pdo->prepare('UPDATE students SET is_archived = 1 WHERE id = ?')->execute([$studentId]);
            $alertMessage = 'Matagumpay na na-archive ang record ng mag-aaral.';
            $alertType = 'success';
        }
    }
}

// Search & Filtering
$searchQuery = trim((string)($_GET['q'] ?? ''));
$filterLs = trim((string)($_GET['ls'] ?? ''));
$filterStatus = trim((string)($_GET['status'] ?? ''));

$whereClauses = ['is_archived = 0'];
$params = [];

// Teacher's assigned strands baseline
$lsConditions = [];
foreach ($lsArray as $lsCode) {
    $lsConditions[] = 'strand LIKE ?';
    $params[] = '%' . $lsCode . '%';
}
if (!empty($lsConditions)) {
    $whereClauses[] = '(' . implode(' OR ', $lsConditions) . ')';
}

if ($searchQuery !== '') {
    $whereClauses[] = '(first_name LIKE ? OR last_name LIKE ? OR student_number LIKE ?)';
    $like = "%{$searchQuery}%";
    array_push($params, $like, $like, $like);
}

if ($filterLs !== '') {
    $whereClauses[] = 'strand LIKE ?';
    $params[] = "%{$filterLs}%";
}

if ($filterStatus !== '') {
    $whereClauses[] = 'status = ?';
    $params[] = $filterStatus;
}

$whereSql = implode(' AND ', $whereClauses);
$stmt = $pdo->prepare("SELECT * FROM students WHERE {$whereSql} ORDER BY status ASC, last_name ASC");
$stmt->execute($params);
$learners = $stmt->fetchAll();

$totalCount = count($learners);
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DepEd ALS - Assigned Learners | Dalubguro Portal</title>
  <link rel="stylesheet" href="assets/admin.css">
  <style>
    .doc-pill-tag {
      display: inline-block;
      font-size: 10px;
      font-weight: 750;
      padding: 2px 6px;
      border-radius: 4px;
      margin-right: 3px;
    }
    .doc-pill-tag.yes {
      background: #DCFCE7;
      color: #15803D;
      border: 1px solid #86EFAC;
    }
    .doc-pill-tag.no {
      background: #FEE2E2;
      color: #B91C1C;
      border: 1px solid #FCA5A5;
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
      <!-- Active Link -->
      <a href="teacher_assigned_learners.php" class="deped-nav-link active" style="background:#107C41;">
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
        <span class="stat-box-sub-pill green">👥 Dedicated Page</span>
        <span>Assigned Learners Masterlist (<?= $totalCount ?> Mag-aaral)</span>
      </div>

      <div class="header-user-profile">
        <a href="teacher_dashboard.php" class="toolbar-btn green" style="background:#107C41;color:#fff;border:none;">
          ➕ + Enroll Student
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

      <!-- Filter Bar -->
      <form method="get" action="teacher_assigned_learners.php" class="filter-bar-row">
        <input 
          type="text" 
          name="q" 
          value="<?= e($searchQuery) ?>" 
          placeholder="Maghanap ng Pangalan o LRN..." 
          class="search-filter-input" 
          style="min-width:240px;flex:1;"
        >

        <select name="ls" class="search-filter-input">
          <option value="">Lahat ng Strands</option>
          <option value="LS1" <?= $filterLs === 'LS1' ? 'selected' : '' ?>>LS1 - Communication Skills</option>
          <option value="LS2" <?= $filterLs === 'LS2' ? 'selected' : '' ?>>LS2 - Scientific Literacy</option>
          <option value="LS3" <?= $filterLs === 'LS3' ? 'selected' : '' ?>>LS3 - Math & Problem Solving</option>
          <option value="LS4" <?= $filterLs === 'LS4' ? 'selected' : '' ?>>LS4 - Life & Career Skills</option>
          <option value="LS5" <?= $filterLs === 'LS5' ? 'selected' : '' ?>>LS5 - Self & Society</option>
          <option value="LS6" <?= $filterLs === 'LS6' ? 'selected' : '' ?>>LS6 - Digital Literacy</option>
        </select>

        <select name="status" class="search-filter-input">
          <option value="">Lahat ng Katayuan</option>
          <option value="Approved" <?= $filterStatus === 'Approved' ? 'selected' : '' ?>>Aprubado (Approved)</option>
          <option value="Pending" <?= $filterStatus === 'Pending' ? 'selected' : '' ?>>Naghihintay (Pending)</option>
        </select>

        <button type="submit" class="toolbar-btn green" style="background:#107C41;color:#fff;border:none;">
          I-filter
        </button>

        <?php if ($searchQuery !== '' || $filterLs !== '' || $filterStatus !== ''): ?>
          <a href="teacher_assigned_learners.php" class="toolbar-btn">I-reset</a>
        <?php endif; ?>
      </form>

      <!-- Full-Page Assigned Learners Table -->
      <div class="deped-panel-card">
        <div class="panel-header-row">
          <div>
            <h3>Talaan ng mga Nakatalagang Mag-aaral (Assigned Learners)</h3>
            <p>Naka-filter batay sa iyong itinalagang Learning Systems (<?= e($assignedLs) ?>).</p>
          </div>
          <a href="admin_scanner.php" class="toolbar-btn blue">
            📷 QR Scanner Hub
          </a>
        </div>

        <table class="deped-full-table">
          <thead>
            <tr>
              <th>MAG-AARAL / LRN</th>
              <th>FOCUS STRAND & ANTAS</th>
              <th>BULK MODULES (LS1-LS6)</th>
              <th>MGA DOKUMENTO</th>
              <th>KATAYUAN</th>
              <th style="text-align:right;">MGA AKSIYON</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($learners)): ?>
              <tr>
                <td colspan="6" style="text-align:center;padding:36px;color:var(--deped-text-muted);">
                  Walang nakitang mag-aaral sa napiling filter.
                </td>
              </tr>
            <?php endif; ?>

            <?php foreach ($learners as $l): 
              $fullName = trim($l['first_name'] . ' ' . $l['last_name']);
              $inits = strtoupper(substr($l['first_name'], 0, 1) . substr($l['last_name'], 0, 1));
              $docPsa = (int)$l['doc_psa'];
              $docBrgy = (int)$l['doc_brgy'];
              $docCard = (int)$l['doc_card'];
              $docsCount = $docPsa + $docBrgy + $docCard;
              $isDocsComplete = ($docsCount === 3);
            ?>
              <tr>
                <td>
                  <div class="learner-cell-flex">
                    <div class="learner-avatar-sq" style="background:#EAF7EE;color:#107C41;">
                      <?= e($inits) ?>
                    </div>
                    <div class="learner-name-col">
                      <strong><?= e($fullName) ?></strong>
                      <span style="font-family:ui-monospace,monospace;">LRN: <?= e($l['student_number']) ?></span>
                      <span style="font-size:10.5px;color:var(--deped-text-muted);"><?= e($l['contact_number']) ?></span>
                    </div>
                  </div>
                </td>
                <td>
                  <strong style="font-size:12px;color:var(--deped-navy-dark);"><?= e($l['strand']) ?></strong>
                  <span style="font-size:10.5px;color:var(--deped-text-muted);display:block;"><?= e($l['learner_status']) ?></span>
                </td>
                <td>
                  <span style="background:#EBF2FD;color:#185ADB;border:1px solid #C7DCFA;padding:3px 8px;border-radius:6px;font-size:10.5px;font-weight:750;">
                    ✓ LS1 - LS6 Buong Pakete
                  </span>
                </td>
                <td>
                  <div style="margin-bottom:3px;">
                    <span class="doc-pill-tag <?= $docPsa ? 'yes' : 'no' ?>">PSA <?= $docPsa ? '✓' : '✗' ?></span>
                    <span class="doc-pill-tag <?= $docBrgy ? 'yes' : 'no' ?>">Brgy <?= $docBrgy ? '✓' : '✗' ?></span>
                    <span class="doc-pill-tag <?= $docCard ? 'yes' : 'no' ?>">Card <?= $docCard ? '✓' : '✗' ?></span>
                  </div>
                  <span style="font-size:10px;font-weight:700;color:<?= $isDocsComplete ? '#107C41' : '#D97706' ?>;">
                    <?= $isDocsComplete ? '✓ Kumpleto (3/3)' : "⚠️ Kulang ({$docsCount}/3)" ?>
                  </span>
                </td>
                <td>
                  <span class="status-badge-custom <?= $l['status'] === 'Approved' ? 'approved' : 'pending' ?>">
                    ● <?= e($l['status']) ?>
                  </span>
                </td>
                <td style="text-align:right;">
                  <div style="display:inline-flex;align-items:center;gap:6px;">
                    <!-- Attendance Present Button -->
                    <form method="post" action="teacher_assigned_learners.php" style="display:inline;">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="record_attendance">
                      <input type="hidden" name="student_id" value="<?= (int)$l['id'] ?>">
                      <button type="submit" class="btn-table-approve" title="Mark Present">
                        ✓ Present
                      </button>
                    </form>

                    <!-- View Profile -->
                    <a href="student_view.php?id=<?= (int)$l['id'] ?>" class="btn-form1-icon" title="Tingnan ang Profile">
                      📄
                    </a>

                    <!-- Archive Record -->
                    <form method="post" action="teacher_assigned_learners.php" style="display:inline;" onsubmit="return confirm('Sigurado ka bang nais mong i-archive si <?= e($fullName) ?>?');">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="archive_student">
                      <input type="hidden" name="student_id" value="<?= (int)$l['id'] ?>">
                      <button type="submit" class="btn-form1-icon" style="color:#C5221F;" title="I-archive">
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

</body>
</html>

