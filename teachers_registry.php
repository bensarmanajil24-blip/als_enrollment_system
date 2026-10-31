<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

require_admin();

$alertMessage = '';

// Handle Teacher Creation or Bulk LS Reassignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_teacher') {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $contact = trim((string)($_POST['contact'] ?? '0917-000-0000'));
        $center = trim((string)($_POST['center'] ?? 'San Nicolas Community Learning Center'));
        $selectedLs = $_POST['assigned_ls'] ?? [];
        $lsStr = is_array($selectedLs) && !empty($selectedLs) ? implode(', ', $selectedLs) : 'LS1';

        if (!empty($name) && !empty($email)) {
            $passHash = password_hash('teacher2026', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO teachers (name, email, assigned_ls, assigned_center, contact_number) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $email, $lsStr, $center, $contact]);
            $tid = (int)$pdo->lastInsertId();

            // Insert into users
            $uStmt = $pdo->prepare('INSERT INTO users (username, email, password, role, user_ref_id) VALUES (?, ?, ?, "teacher", ?)');
            $uStmt->execute([$email, $email, $passHash, $tid]);

            $alertMessage = "Matagumpay na naidagdag ang Dalubguro na si {$name} na may itinalagang: {$lsStr}!";
        }
    } elseif ($action === 'update_teacher_ls') {
        $tid = (int)($_POST['teacher_id'] ?? 0);
        $selectedLs = $_POST['assigned_ls'] ?? [];
        $lsStr = is_array($selectedLs) && !empty($selectedLs) ? implode(', ', $selectedLs) : 'LS1';
        if ($tid > 0) {
            $stmt = $pdo->prepare('UPDATE teachers SET assigned_ls = ? WHERE id = ?');
            $stmt->execute([$lsStr, $tid]);
            $alertMessage = "Matagumpay na na-update ang mga itinalagang Learning Systems sa guro!";
        }
    } elseif ($action === 'toggle_status') {
        $tid = (int)($_POST['teacher_id'] ?? 0);
        $curStatus = $_POST['current_status'] ?? 'Active';
        $newStatus = ($curStatus === 'Active') ? 'Inactive' : 'Active';
        if ($tid > 0) {
            $stmt = $pdo->prepare('UPDATE teachers SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $tid]);
            $alertMessage = "Na-update ang estado ng Dalubguro bilang {$newStatus}!";
        }
    }
}

// Fetch all teachers
$teachers = $pdo->query('SELECT * FROM teachers ORDER BY created_at DESC')->fetchAll();
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DepEd ALS - Teachers Registry & Learning Systems Assignment</title>
  <link rel="stylesheet" href="assets/admin.css">
  <style>
    .ls-badge-tag {
      display: inline-block;
      padding: 2px 7px;
      border-radius: 5px;
      font-size: 10.5px;
      font-weight: 750;
      background: #EBF2FD;
      color: #185ADB;
      margin: 2px;
    }
    .modal-box-card {
      background: #FFFFFF;
      border: 1px solid var(--deped-border-subtle);
      border-radius: 14px;
      padding: 22px;
      margin-bottom: 24px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
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
      <a href="teachers_registry.php" class="deped-nav-link active">
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

    <header class="deped-top-header">
      <div style="font-weight:750;font-size:14px;color:var(--deped-text-dark);">
        Teachers Registry & Learning Systems (LS1–LS6) Assignment
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

      <!-- 2-Column Grid: Left is Teachers Table, Right is Quick Add Teacher -->
      <div class="laptop-two-col-grid">

        <!-- Left: Registry Table -->
        <div class="left-wide-column">
          <div class="deped-panel-card">
            <div class="panel-header-row">
              <div class="panel-title-text">
                <h3>Mga Nakarehistrong Instructional Manager (IM / Dalubguro)</h3>
                <p>Pamahalaan ang mga guro at ang kanilang mga itinalagang Learning Systems (LS1 hanggang LS6).</p>
              </div>
              <span class="stat-box-sub-pill green"><?= count($teachers) ?> Dalubguro</span>
            </div>

            <table class="deped-full-table">
              <thead>
                <tr>
                  <th>PANGALAN / CONTACT</th>
                  <th>EMAIL / USERNAME</th>
                  <th>ITINALAGANG LEARNING SYSTEMS</th>
                  <th>ESTADO</th>
                  <th style="text-align:right;">AKSYON</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($teachers as $t): 
                  $lsArray = array_filter(array_map('trim', explode(',', $t['assigned_ls'] ?? 'LS1')));
                  $isActive = ($t['status'] ?? 'Active') === 'Active';
                ?>
                  <tr>
                    <td>
                      <div class="learner-cell-flex">
                        <div class="learner-avatar-sq" style="background:#EAF7EE;color:#107C41;">👨‍🏫</div>
                        <div class="learner-name-col">
                          <strong><?= e($t['name']) ?></strong>
                          <span><?= e($t['contact_number'] ?? '0917-823-4412') ?> &bull; <?= (int)($t['age'] ?? 32) ?> anyos (<?= e($t['civil_status'] ?? 'Single') ?>)</span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span style="font-size:12px;color:var(--deped-navy-dark);"><?= e($t['email']) ?></span>
                    </td>
                    <td>
                      <div>
                        <?php foreach ($lsArray as $ls): ?>
                          <span class="ls-badge-tag"><?= e($ls) ?></span>
                        <?php endforeach; ?>
                      </div>
                    </td>
                    <td>
                      <span class="status-badge-custom <?= $isActive ? 'approved' : 'rejected' ?>">
                        ● <?= $isActive ? 'Aktibo' : 'Hindi Aktibo' ?>
                      </span>
                    </td>
                    <td style="text-align:right;">
                      <form method="post" action="teachers_registry.php" style="display:inline-flex;gap:4px;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="teacher_id" value="<?= (int)$t['id'] ?>">
                        <input type="hidden" name="current_status" value="<?= e($t['status'] ?? 'Active') ?>">
                        <button type="submit" class="toolbar-btn" style="padding:4px 8px;font-size:11px;">
                          <?= $isActive ? 'I-deactivate' : 'I-activate' ?>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Right: Bulk LS Assignment Form -->
        <div class="right-sidebar-panel">
          <div class="deped-panel-card" style="padding:20px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
              <span style="font-size:18px;">➕</span>
              <div>
                <strong style="font-size:13.5px;color:var(--deped-text-dark);display:block;">Magdagdag ng Dalubguro</strong>
                <span style="font-size:11px;color:var(--deped-text-muted);">Isang bagsakang pagtatalaga ng LS1-LS6</span>
              </div>
            </div>

            <form method="post" action="teachers_registry.php">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="create_teacher">

              <label style="font-size:11px;font-weight:700;color:var(--deped-text-muted);display:block;margin-bottom:3px;">Pangalan ng Dalubguro *</label>
              <input type="text" name="name" class="compact-form-input" placeholder="Hal: Teacher Elena Reyes" required>

              <label style="font-size:11px;font-weight:700;color:var(--deped-text-muted);display:block;margin-bottom:3px;">Email Address *</label>
              <input type="email" name="email" class="compact-form-input" placeholder="elena.reyes@deped.gov.ph" required>

              <label style="font-size:11px;font-weight:700;color:var(--deped-text-muted);display:block;margin-bottom:3px;">Contact Number</label>
              <input type="text" name="contact" class="compact-form-input" placeholder="0917-823-4412" value="0917-823-4412">

              <label style="font-size:11px;font-weight:750;color:var(--deped-navy-dark);display:block;margin:8px 0 4px 0;">
                Italagang Learning Systems (Checkboxes):
              </label>

              <div style="background:#F8FAFD;border:1px solid var(--deped-border-subtle);border-radius:8px;padding:10px;margin-bottom:14px;display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:11px;">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                  <input type="checkbox" name="assigned_ls[]" value="LS1" checked>
                  <strong>LS1:</strong> Comm Skills
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                  <input type="checkbox" name="assigned_ls[]" value="LS2">
                  <strong>LS2:</strong> Scientific
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                  <input type="checkbox" name="assigned_ls[]" value="LS3">
                  <strong>LS3:</strong> Math
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                  <input type="checkbox" name="assigned_ls[]" value="LS4" checked>
                  <strong>LS4:</strong> Life & Career
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                  <input type="checkbox" name="assigned_ls[]" value="LS5">
                  <strong>LS5:</strong> Self & Society
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                  <input type="checkbox" name="assigned_ls[]" value="LS6" checked>
                  <strong>LS6:</strong> Digital Lit
                </label>
              </div>

              <button type="submit" class="btn-side-full green">
                Lumikha ng Account at Italaga ang LS
              </button>
            </form>
          </div>
        </div>

      </div>

    </main>

  </div>

</div>

</body>
</html>

