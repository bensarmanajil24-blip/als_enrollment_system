<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

require_admin();

// Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = $pdo->query('SELECT student_number, first_name, middle_name, last_name, birthdate, gender, address, contact_number, email, strand, learner_status, status, date_registered FROM students ORDER BY date_registered DESC')->fetchAll();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="DepEd_ALS_Masterlist_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['LRN', 'First Name', 'Middle Name', 'Last Name', 'Birthdate', 'Gender', 'Address', 'Contact', 'Email', 'Strand', 'Program', 'Status', 'Date Registered']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['student_number'], $r['first_name'], $r['middle_name'], $r['last_name'],
            $r['birthdate'], $r['gender'], $r['address'], $r['contact_number'], $r['email'],
            $r['strand'], $r['learner_status'], $r['status'], $r['date_registered']
        ]);
    }
    fclose($out);
    exit;
}

// Search and Filters
$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$strandFilter = trim((string)($_GET['strand'] ?? ''));

$sql = 'SELECT * FROM students WHERE is_archived = 0';
$params = [];
if ($search !== '') {
    $sql .= ' AND (student_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR address LIKE ?)';
    $like = "%{$search}%";
    array_push($params, $like, $like, $like, $like);
}
if ($statusFilter !== '') {
    $sql .= ' AND status = ?';
    $params[] = $statusFilter;
}
if ($strandFilter !== '') {
    $sql .= ' AND strand LIKE ?';
    $params[] = "%{$strandFilter}%";
}
$sql .= ' ORDER BY date_registered DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$totalCount = count($students);
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DepEd ALS - Student Masterlist</title>
  <link rel="stylesheet" href="assets/admin.css">
  <style>
    .filter-bar-card {
      background: #FFFFFF;
      border: 1px solid var(--deped-border-subtle);
      border-radius: 12px;
      padding: 16px 20px;
      margin-bottom: 20px;
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
    }
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
      <a href="admin_enrollment_strands.php" class="deped-nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        <span>Enrollment & Strands</span>
      </a>
      <a href="teachers_registry.php" class="deped-nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
        <span>Teachers Registry</span>
      </a>
      <a href="student_masterlist.php" class="deped-nav-link active">
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

    <header class="deped-top-header">
      <div style="font-weight:750;font-size:14px;color:var(--deped-text-dark);">
        DepEd ALS Student Masterlist (Opisyal na Talaan)
      </div>
      <div class="header-user-profile">
        <a href="student_masterlist.php?export=csv" class="toolbar-btn green">
          📊 I-export sa Excel / CSV
        </a>
        <button type="button" class="toolbar-btn blue" onclick="window.print()">
          🖨️ I-print ang Masterlist
        </button>
      </div>
    </header>

    <main class="deped-workspace-container">

      <!-- Filter Card -->
      <form method="get" action="student_masterlist.php" class="filter-bar-card">
        <div style="display:flex;gap:10px;align-items:center;flex:1;min-width:280px;">
          <input type="text" name="q" class="compact-form-input" style="margin-bottom:0;" placeholder="Maghanap ayon sa LRN, Pangalan, o Tirahan..." value="<?= e($search) ?>">
          <button type="submit" class="toolbar-btn blue" style="white-space:nowrap;">🔍 Hanapin</button>
        </div>

        <div style="display:flex;gap:8px;align-items:center;">
          <select name="status" class="compact-form-input" style="width:140px;margin-bottom:0;" onchange="this.form.submit()">
            <option value="">Lahat ng Katayuan</option>
            <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Aprubado</option>
            <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Naghihintay</option>
            <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : '' ?>>Tinanggihan</option>
          </select>

          <select name="strand" class="compact-form-input" style="width:160px;margin-bottom:0;" onchange="this.form.submit()">
            <option value="">Lahat ng Strands</option>
            <option value="LS1" <?= $strandFilter === 'LS1' ? 'selected' : '' ?>>LS1: Comm Skills</option>
            <option value="LS2" <?= $strandFilter === 'LS2' ? 'selected' : '' ?>>LS2: Scientific</option>
            <option value="LS3" <?= $strandFilter === 'LS3' ? 'selected' : '' ?>>LS3: Math</option>
            <option value="LS4" <?= $strandFilter === 'LS4' ? 'selected' : '' ?>>LS4: Life & Career</option>
            <option value="LS5" <?= $strandFilter === 'LS5' ? 'selected' : '' ?>>LS5: Self & Society</option>
            <option value="LS6" <?= $strandFilter === 'LS6' ? 'selected' : '' ?>>LS6: Digital Lit</option>
          </select>

          <a href="student_masterlist.php" class="toolbar-btn">I-reset</a>
        </div>
      </form>

      <!-- Masterlist Table -->
      <div class="deped-panel-card">
        <div class="panel-header-row">
          <div class="panel-title-text">
            <h3>Kabuuang Talaan ng mga Mag-aaral (Masterlist)</h3>
            <p>Kumpletong tala ng mga nakarehistro sa Alternative Learning System.</p>
          </div>
          <span class="stat-box-sub-pill green"><?= $totalCount ?> Mag-aaral</span>
        </div>

        <table class="deped-full-table">
          <thead>
            <tr>
              <th>LRN / PANGALAN</th>
              <th>PROGRAMA / STRAND</th>
              <th>TIRAHAN / CONTACT</th>
              <th>DOKUMENTO</th>
              <th>KATAYUAN</th>
              <th style="text-align:right;">PROFILE</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $s): 
              $name = trim($s['first_name'] . ' ' . $s['last_name']);
              $inits = strtoupper(substr($s['first_name'], 0, 1) . substr($s['last_name'], 0, 1));
            ?>
              <tr>
                <td>
                  <div class="learner-cell-flex">
                    <div class="learner-avatar-sq"><?= e($inits) ?></div>
                    <div class="learner-name-col">
                      <strong><?= e($name) ?></strong>
                      <span>LRN: <?= e($s['student_number']) ?> &bull; <?= (int)($s['age'] ?? 19) ?> anyos (<?= e($s['civil_status'] ?? 'Single') ?>)</span>
                    </div>
                  </div>
                </td>
                <td>
                  <strong style="font-size:12px;color:var(--deped-navy-dark);display:block;"><?= e($s['strand']) ?></strong>
                  <span style="font-size:11px;color:var(--deped-text-muted);"><?= e($s['learner_status']) ?></span>
                </td>
                <td>
                  <span style="font-size:11.5px;color:var(--deped-text-dark);display:block;"><?= e($s['address']) ?></span>
                  <span style="font-size:10.5px;color:var(--deped-text-muted);"><?= e($s['contact_number']) ?></span>
                </td>
                <td>
                  <div style="font-size:10.5px;">
                    <span style="color:<?= !empty($s['doc_psa']) ? '#15803D' : '#B91C1C' ?>;">PSA: <?= !empty($s['doc_psa']) ? '✓' : '✗' ?></span> •
                    <span style="color:<?= !empty($s['doc_brgy']) ? '#15803D' : '#B91C1C' ?>;">Brgy: <?= !empty($s['doc_brgy']) ? '✓' : '✗' ?></span> •
                    <span style="color:<?= !empty($s['doc_card']) ? '#15803D' : '#B91C1C' ?>;">Card: <?= !empty($s['doc_card']) ? '✓' : '✗' ?></span>
                  </div>
                </td>
                <td>
                  <span class="status-badge-custom <?= strtolower($s['status']) ?>">
                    ● <?= e($s['status']) ?>
                  </span>
                </td>
                <td style="text-align:right;">
                  <a href="student_view.php?id=<?= (int)$s['id'] ?>" class="toolbar-btn" style="padding:4px 8px;font-size:11px;">
                    Tingnan 📄
                  </a>
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

