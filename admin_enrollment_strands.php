<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

require_admin();

$alertMessage = '';

// Handle Strand Update or Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'change_strand') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $newStrand = trim((string)($_POST['new_strand'] ?? ''));
        if ($studentId > 0 && !empty($newStrand)) {
            $stmt = $pdo->prepare('UPDATE students SET strand = ? WHERE id = ?');
            $stmt->execute([$newStrand, $studentId]);
            $alertMessage = "Matagumpay na nailipat ang mag-aaral sa {$newStrand}!";
        }
    } elseif ($action === 'update_status') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $newStatus = $_POST['status'] ?? 'Approved';
        if ($studentId > 0 && in_array($newStatus, ['Pending', 'Approved', 'Rejected'], true)) {
            $stmt = $pdo->prepare('UPDATE students SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $studentId]);
            $alertMessage = "Na-update ang katayuan ng mag-aaral bilang {$newStatus}!";
        }
    }
}

// Active Filter for LS1-LS6
$activeLs = trim((string)($_GET['ls'] ?? ''));

$sql = 'SELECT * FROM students WHERE is_archived = 0';
$params = [];
if ($activeLs !== '') {
    $sql .= ' AND strand LIKE ?';
    $params[] = '%' . $activeLs . '%';
}
$sql .= ' ORDER BY date_registered DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Count per strand for quick tabs
$strandCounts = [
    'LS1' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND strand LIKE '%LS1%'")->fetchColumn(),
    'LS2' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND strand LIKE '%LS2%'")->fetchColumn(),
    'LS3' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND strand LIKE '%LS3%'")->fetchColumn(),
    'LS4' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND strand LIKE '%LS4%'")->fetchColumn(),
    'LS5' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND strand LIKE '%LS5%'")->fetchColumn(),
    'LS6' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND strand LIKE '%LS6%'")->fetchColumn(),
];
$totalCount = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0")->fetchColumn();
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DepEd ALS - Enrollment & Learning Strands (LS1–LS6)</title>
  <link rel="stylesheet" href="assets/admin.css">
  <style>
    .ls-filter-tabs {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }
    .ls-tab-pill {
      padding: 8px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 700;
      text-decoration: none;
      background: #FFFFFF;
      border: 1px solid var(--deped-border-subtle);
      color: var(--deped-text-dark);
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .ls-tab-pill.active {
      background: var(--deped-blue-primary);
      color: #FFFFFF;
      border-color: var(--deped-blue-primary);
    }
    .doc-indicator {
      display: inline-flex;
      align-items: center;
      gap: 3px;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 10px;
      font-weight: 700;
    }
    .doc-indicator.ok { background: #DCFCE7; color: #15803D; }
    .doc-indicator.missing { background: #FEE2E2; color: #B91C1C; }
  </style>
</head>
<body class="admin-body">

<div class="deped-laptop-layout">

  <!-- Left Sidebar -->
  <aside class="deped-sidebar">
    <a href="admin_dashboard.php" class="deped-sidebar-brand">
      <div class="deped-brand-logo-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
      </div>
      <div class="deped-brand-text">
        <h2>DepEd ALS</h2>
        <span>CONNECT PORTAL</span>
      </div>
    </a>

    <div class="sidebar-section-title">ADMIN & COORDINATOR</div>
    <nav class="sidebar-nav-group">
      <a href="admin_dashboard.php" class="deped-nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        <span>Dashboard</span>
      </a>
      <a href="admin_enrollment_strands.php" class="deped-nav-link active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        <span>Enrollment & Strands</span>
      </a>
      <a href="teachers_registry.php" class="deped-nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
        <span>Teachers Registry</span>
      </a>
      <a href="student_masterlist.php" class="deped-nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        <span>Student Masterlist</span>
      </a>
      <a href="division_reports.php" class="deped-nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
        <span>Division Reports</span>
      </a>
    </nav>

    <div class="sidebar-footer-info">
      <span>DepEd ALS Admin Portal</span>
      <a href="logout.php" style="color:#F87171;text-decoration:none;font-weight:700;">Logout</a>
    </div>
  </aside>

  <!-- Main Viewport -->
  <div class="deped-main-viewport">

    <!-- Header -->
    <header class="deped-top-header">
      <div style="font-weight:750;font-size:14px;color:var(--deped-text-dark);">
        Pamamahala sa Enrollment & Learning Strands (LS1 hanggang LS6)
      </div>
      <div class="header-user-profile">
        <a href="admin_dashboard.php" class="toolbar-btn">← Bumalik sa Dashboard</a>
      </div>
    </header>

    <main class="deped-workspace-container">

      <?php if ($alertMessage): ?>
        <div style="background:#DCFCE7;border:1px solid #86EFAC;color:#15803D;padding:12px 18px;border-radius:10px;font-size:13px;font-weight:700;margin-bottom:18px;">
          ✓ <?= e($alertMessage) ?>
        </div>
      <?php endif; ?>

      <!-- Filter Tabs para sa Learning Systems LS1 - LS6 -->
      <div class="ls-filter-tabs">
        <a href="admin_enrollment_strands.php" class="ls-tab-pill <?= $activeLs === '' ? 'active' : '' ?>">
          Lahat ng Strands (<?= $totalCount ?>)
        </a>
        <a href="admin_enrollment_strands.php?ls=LS1" class="ls-tab-pill <?= $activeLs === 'LS1' ? 'active' : '' ?>">
          LS1: Comm Skills (<?= $strandCounts['LS1'] ?>)
        </a>
        <a href="admin_enrollment_strands.php?ls=LS2" class="ls-tab-pill <?= $activeLs === 'LS2' ? 'active' : '' ?>">
          LS2: Scientific (<?= $strandCounts['LS2'] ?>)
        </a>
        <a href="admin_enrollment_strands.php?ls=LS3" class="ls-tab-pill <?= $activeLs === 'LS3' ? 'active' : '' ?>">
          LS3: Math (<?= $strandCounts['LS3'] ?>)
        </a>
        <a href="admin_enrollment_strands.php?ls=LS4" class="ls-tab-pill <?= $activeLs === 'LS4' ? 'active' : '' ?>">
          LS4: Life & Career (<?= $strandCounts['LS4'] ?>)
        </a>
        <a href="admin_enrollment_strands.php?ls=LS5" class="ls-tab-pill <?= $activeLs === 'LS5' ? 'active' : '' ?>">
          LS5: Self & Society (<?= $strandCounts['LS5'] ?>)
        </a>
        <a href="admin_enrollment_strands.php?ls=LS6" class="ls-tab-pill <?= $activeLs === 'LS6' ? 'active' : '' ?>">
          LS6: Digital Lit (<?= $strandCounts['LS6'] ?>)
        </a>
      </div>

      <!-- Main Panel Card -->
      <div class="deped-panel-card">
        <div class="panel-header-row">
          <div class="panel-title-text">
            <h3>Talaan ng mga Aplikante ayon sa Learning Strand</h3>
            <p>I-filter, i-verify ang mga kaukulang dokumento, o ilipat ang estudyante sa tamang strand batay sa FLT Score.</p>
          </div>
          <span class="stat-box-sub-pill blue"><?= count($students) ?> Mag-aaral</span>
        </div>

        <table class="deped-full-table">
          <thead>
            <tr>
              <th>MAG-AARAL / LRN</th>
              <th>KASALUKUYANG STRAND</th>
              <th>DOKUMENTO</th>
              <th>KATAYUAN</th>
              <th style="text-align:right;">AKSYON</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($students)): ?>
              <tr>
                <td colspan="5" style="text-align:center;padding:30px;color:var(--deped-text-muted);">
                  Walang estudyante sa kasalukuyang filter.
                </td>
              </tr>
            <?php endif; ?>

            <?php foreach ($students as $s): 
              $fullName = trim($s['first_name'] . ' ' . $s['last_name']);
              $inits = strtoupper(substr($s['first_name'], 0, 1) . substr($s['last_name'], 0, 1));
              $isApproved = $s['status'] === 'Approved';
            ?>
              <tr>
                <td>
                  <div class="learner-cell-flex">
                    <div class="learner-avatar-sq"><?= e($inits) ?></div>
                    <div class="learner-name-col">
                      <strong><?= e($fullName) ?></strong>
                      <span>LRN: <?= e($s['student_number']) ?> • <?= e($s['learner_status']) ?></span>
                    </div>
                  </div>
                </td>
                <td>
                  <!-- Quick Strand Change Dropdown -->
                  <form method="post" action="admin_enrollment_strands.php" style="display:inline-flex;gap:4px;align-items:center;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="change_strand">
                    <input type="hidden" name="student_id" value="<?= (int)$s['id'] ?>">
                    <select name="new_strand" class="compact-form-input" style="width:180px;margin-bottom:0;font-size:11px;" onchange="this.form.submit()">
                      <option value="LS1 - Communication Skills" <?= str_contains($s['strand'], 'LS1') ? 'selected' : '' ?>>LS1: Comm Skills</option>
                      <option value="LS2 - Scientific Literacy" <?= str_contains($s['strand'], 'LS2') ? 'selected' : '' ?>>LS2: Scientific Lit</option>
                      <option value="LS3 - Math & Problem Solving" <?= str_contains($s['strand'], 'LS3') ? 'selected' : '' ?>>LS3: Math & Problem</option>
                      <option value="LS4 - Life & Career Skills" <?= str_contains($s['strand'], 'LS4') ? 'selected' : '' ?>>LS4: Life & Career</option>
                      <option value="LS5 - Self & Society" <?= str_contains($s['strand'], 'LS5') ? 'selected' : '' ?>>LS5: Self & Society</option>
                      <option value="LS6 - Digital Literacy" <?= str_contains($s['strand'], 'LS6') ? 'selected' : '' ?>>LS6: Digital Lit</option>
                    </select>
                  </form>
                </td>
                <td>
                  <div style="display:flex;gap:4px;flex-wrap:wrap;">
                    <span class="doc-indicator <?= !empty($s['doc_psa']) ? 'ok' : 'missing' ?>">
                      <?= !empty($s['doc_psa']) ? '✓ PSA' : '✗ PSA' ?>
                    </span>
                    <span class="doc-indicator <?= !empty($s['doc_brgy']) ? 'ok' : 'missing' ?>">
                      <?= !empty($s['doc_brgy']) ? '✓ Brgy' : '✗ Brgy' ?>
                    </span>
                    <span class="doc-indicator <?= !empty($s['doc_card']) ? 'ok' : 'missing' ?>">
                      <?= !empty($s['doc_card']) ? '✓ Card' : '✗ Card' ?>
                    </span>
                  </div>
                </td>
                <td>
                  <span class="status-badge-custom <?= strtolower($s['status']) ?>">
                    ● <?= e($s['status']) ?>
                  </span>
                </td>
                <td style="text-align:right;">
                  <form method="post" action="admin_enrollment_strands.php" style="display:inline-flex;gap:6px;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="student_id" value="<?= (int)$s['id'] ?>">
                    
                    <?php if (!$isApproved): ?>
                      <button type="submit" name="status" value="Approved" class="btn-table-approve">
                        ✓ Aprubahan
                      </button>
                    <?php else: ?>
                      <button type="submit" name="status" value="Pending" class="toolbar-btn" style="padding:4px 8px;font-size:11px;">
                        Ibalik sa Pending
                      </button>
                    <?php endif; ?>
                    <a href="student_view.php?id=<?= (int)$s['id'] ?>" class="btn-form1-icon" title="Tingnan ang Profile">📄</a>
                  </form>
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

