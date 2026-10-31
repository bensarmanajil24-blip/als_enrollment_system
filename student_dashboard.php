<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

require_student();

$studentId = (int)($_SESSION['student_id'] ?? 0);
$student = null;

if ($studentId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
}

if (!$student) {
    $lrn = $_SESSION['student_lrn'] ?? 'ALS-DEMO-2026-0142';
    $stmt = $pdo->prepare('SELECT * FROM students WHERE student_number = ? LIMIT 1');
    $stmt->execute([$lrn]);
    $student = $stmt->fetch();
}

$fullName = trim(($student['first_name'] ?? 'Maria Isabel') . ' ' . ($student['last_name'] ?? 'Reyes'));
$lrn = $student['student_number'] ?? 'ALS-DEMO-2026-0142';
$program = $student['learner_status'] ?? 'Junior High School';
$strand = $student['strand'] ?? 'LS1 - Communication Skills';
$status = $student['status'] ?? 'Approved';
$assignedCenter = $student['assigned_center'] ?? 'San Nicolas Community Learning Center';
$schedule = $student['schedule'] ?? 'Sabado at Linggo (Weekend Classes)';
$isApproved = $status === 'Approved';
$realStudentId = (int)($student['id'] ?? 1);

// Fetch Personal Attendance History from attendance table
$attStmt = $pdo->prepare('
    SELECT a.*, t.name as teacher_name 
    FROM attendance a 
    LEFT JOIN teachers t ON a.teacher_id = t.id 
    WHERE a.student_id = ? 
    ORDER BY a.scan_date DESC 
    LIMIT 10
');
$attStmt->execute([$realStudentId]);
$attendanceHistory = $attStmt->fetchAll();

$totalPresent = 0;
foreach ($attendanceHistory as $ah) {
    if ($ah['status'] === 'Present') {
        $totalPresent++;
    }
}
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DepEd ALS - Student Portal Dashboard</title>
  <link rel="stylesheet" href="assets/admin.css">
  <link rel="stylesheet" href="assets/admin_theme.css">
  <style>
    .student-pass-card {
      background: linear-gradient(135deg, #0B1E3B 0%, #10356C 60%, #185ADB 100%);
      color: #FFFFFF;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 10px 25px rgba(11, 30, 59, 0.2);
      margin-bottom: 22px;
    }
    .student-qr-container {
      background: #FFFFFF;
      border-radius: 12px;
      padding: 14px;
      display: inline-block;
      text-align: center;
      flex-shrink: 0;
    }
    .progress-bar-wrap {
      background: #E2E8F0;
      height: 10px;
      border-radius: 999px;
      overflow: hidden;
      margin: 8px 0 14px 0;
    }
    .progress-bar-fill {
      background: #107C41;
      height: 100%;
      border-radius: 999px;
      width: 80%;
    }
  </style>
</head>
<body class="admin-body">

<!-- Page Watermark Header -->
<div class="page-watermark">
  Mag-aaral / Student portal dashboard
</div>

<div class="deped-laptop-layout">

  <!-- Left Sidebar (Student Portal Menu) -->
  <aside class="deped-sidebar-v3">
    <a href="student_dashboard.php" class="deped-brand-strip">
      <div class="deped-brand-icon-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:20px;height:20px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
      </div>
      <div>
        <h2 style="font-size:14px;font-weight:800;margin:0;line-height:1.2;color:#FFFFFF;letter-spacing:-0.02em;">DepEd ALS</h2>
        <span style="font-size:9px;font-weight:700;color:#94A3B8;letter-spacing:0.06em;text-transform:uppercase;">ALTERNATIVE LEARNING SYSTEM</span>
      </div>
    </a>

    <!-- Workspace Badge -->
    <div class="deped-workspace-badge">
      <span style="font-size:9.5px;font-weight:750;color:#94A3B8;letter-spacing:0.06em;text-transform:uppercase;display:block;margin-bottom:2px;">YOUR WORKSPACE</span>
      <strong style="font-size:12.5px;color:#FFFFFF;">ALS Student / Learner</strong>
    </div>

    <!-- Sidebar / Navigation Menu -->
    <nav style="display:flex;flex-direction:column;gap:3px;">
      <a href="student_dashboard.php" class="deped-nav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        <span>Dashboard</span>
      </a>
      <a href="#learner-profile" class="deped-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        <span>Learner Profile</span>
      </a>
      <a href="#my-modules" class="deped-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
        <span>My ALS Modules</span>
      </a>
      <a href="#attendance-history" class="deped-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        <span>Attendance History</span>
      </a>
      <a href="#digital-id" class="deped-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        <span>Digital Learner ID</span>
      </a>
    </nav>

    <!-- Sub-box: Community Learning Center -->
    <div style="margin-top:auto;padding-top:16px;border-top:1px solid rgba(255, 255, 255, 0.08);">
      <span style="font-size:9.5px;font-weight:750;letter-spacing:0.06em;color:#94A3B8;text-transform:uppercase;display:block;margin-bottom:2px;">COMMUNITY LEARNING CENTER</span>
      <strong style="font-size:12px;color:#FFFFFF;display:block;"><?= e($assignedCenter) ?></strong>
      <span style="font-size:11px;color:#64748B;">School year 2026&ndash;2027</span>
    </div>

    <!-- Bottom Links -->
    <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px;">
      <a href="mailto:support@deped.gov.ph" style="color:#94A3B8;text-decoration:none;font-size:12px;display:flex;align-items:center;gap:6px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        <span>Help &amp; support</span>
      </a>
      <a href="logout.php" style="color:#FCA5A5;text-decoration:none;font-size:12px;display:flex;align-items:center;gap:6px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        <span>Log out</span>
      </a>
      <span style="font-size:10px;color:#475569;margin-top:4px;">&copy; 2026 Department of Education</span>
    </div>
  </aside>

  <!-- Main Viewport -->
  <div style="flex:1;display:flex;flex-direction:column;min-width:0;background:#F8FAFC;min-height:100vh;">

    <!-- Top Header -->
    <header class="deped-topbar-strip">
      <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#64748B;">
        <span>Student Portal</span>
        <span>&gt;</span>
        <strong style="color:#0F172A;">Overview</strong>
      </div>

      <div style="display:flex;align-items:center;gap:18px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" style="width:18px;height:18px;cursor:pointer;"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>

        <div style="display:flex;align-items:center;gap:10px;cursor:pointer;">
          <div style="width:34px;height:34px;border-radius:50%;background:#EFF6FF;color:#185ADB;font-weight:800;font-size:13px;display:grid;place-items:center;">
            <?= strtoupper(substr($fullName, 0, 2)) ?>
          </div>
          <div>
            <strong style="font-size:12.5px;color:#0F172A;display:block;"><?= e($fullName) ?></strong>
            <span style="font-size:10.5px;color:#64748B;">LRN: <?= e($lrn) ?></span>
          </div>
        </div>

        <a href="logout.php" style="border:1px solid #E2E8F0;padding:6px 12px;border-radius:6px;background:#fff;text-decoration:none;color:#334155;font-weight:700;font-size:12px;display:inline-flex;align-items:center;gap:6px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          <span>Log out</span>
        </a>
      </div>
    </header>

    <main class="deped-workspace-container">

      <!-- Hero Banner -->
      <section class="deped-hero-banner" style="background:linear-gradient(135deg, #10356C 0%, #185ADB 100%);">
        <div class="banner-pill-tags-row">
          <span class="banner-sys-tag green">● <?= $isApproved ? 'Opisyal na Naka-enrol' : 'Naghihintay ng Pagpapatibay' ?></span>
          <span class="banner-sys-tag">📍 <?= e($assignedCenter) ?></span>
          <span class="banner-sys-tag"><?= e($strand) ?></span>
        </div>

        <div class="hero-content-split">
          <div class="hero-text-block">
            <h1>Maligayang Araw, <?= e($fullName) ?>!</h1>
            <p>
              Subaybayan ang iyong mga aralin, personal na talaan ng attendance, at gamitin ang iyong Digital Learner ID sa bawat pagpasok sa Community Learning Center.
            </p>
          </div>

          <div class="hero-actions-block">
            <a href="qr_pass.php" class="btn-hero-white">
              📱 Buksan ang QR Pass
            </a>
            <a href="#my-modules" class="btn-hero-green" style="background:#107C41;">
              📚 Aking mga Modyul
            </a>
          </div>
        </div>
      </section>

      <!-- 2-Column Content -->
      <div class="laptop-two-col-grid">

        <!-- Left Column: Digital Learner ID & Attendance History -->
        <div class="left-wide-column">

          <!-- 1. Digital Learner ID -->
          <div class="student-pass-card" id="digital-id">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;">
              <div>
                <span style="font-size:11px;font-weight:800;letter-spacing:0.06em;color:#94A9C9;text-transform:uppercase;">
                  DEPED ALS DIGITAL LEARNER ID
                </span>
                <h3 style="font-size:20px;font-weight:850;margin:3px 0;"><?= e($fullName) ?></h3>
                <span style="font-size:12px;color:#C2D5F0;">LRN: <strong><?= e($lrn) ?></strong></span>
              </div>
              <span class="stat-box-sub-pill green" style="background:#DCFCE7;color:#15803D;">
                ✓ <?= e($status) ?>
              </span>
            </div>

            <div style="display:flex;gap:20px;align-items:center;background:rgba(255,255,255,0.08);padding:16px;border-radius:12px;">
              <div class="student-qr-container">
                <svg width="105" height="105" viewBox="0 0 120 120">
                  <rect width="120" height="120" fill="#FFFFFF"/>
                  <rect x="10" y="10" width="30" height="30" fill="#0B1E3B"/>
                  <rect x="16" y="16" width="18" height="18" fill="#FFFFFF"/>
                  <rect x="20" y="20" width="10" height="10" fill="#0B1E3B"/>
                  <rect x="80" y="10" width="30" height="30" fill="#0B1E3B"/>
                  <rect x="86" y="16" width="18" height="18" fill="#FFFFFF"/>
                  <rect x="90" y="20" width="10" height="10" fill="#0B1E3B"/>
                  <rect x="10" y="80" width="30" height="30" fill="#0B1E3B"/>
                  <rect x="16" y="86" width="18" height="18" fill="#FFFFFF"/>
                  <rect x="20" y="90" width="10" height="10" fill="#0B1E3B"/>
                  <rect x="52" y="12" width="16" height="8" fill="#0B1E3B"/>
                  <rect x="52" y="28" width="16" height="12" fill="#0B1E3B"/>
                  <rect x="12" y="52" width="12" height="16" fill="#0B1E3B"/>
                  <rect x="36" y="52" width="16" height="16" fill="#0B1E3B"/>
                  <rect x="64" y="52" width="20" height="10" fill="#0B1E3B"/>
                  <rect x="92" y="52" width="16" height="16" fill="#0B1E3B"/>
                  <rect x="52" y="80" width="16" height="28" fill="#0B1E3B"/>
                  <rect x="76" y="80" width="32" height="12" fill="#0B1E3B"/>
                  <rect x="84" y="100" width="24" height="8" fill="#0B1E3B"/>
                  <circle cx="60" cy="60" r="7" fill="#185ADB"/>
                </svg>
              </div>

              <div style="flex:1;">
                <div style="font-size:12px;margin-bottom:4px;">
                  <strong>Kasalukuyang Strand:</strong> <?= e($strand) ?>
                </div>
                <div style="font-size:12px;margin-bottom:4px;">
                  <strong>Sentro ng Pag-aaral:</strong> <?= e($assignedCenter) ?>
                </div>
                <div style="font-size:12px;margin-bottom:12px;">
                  <strong>Iskedyul:</strong> <?= e($schedule) ?>
                </div>
                <a href="qr_pass.php" class="toolbar-btn" style="background:#FFFFFF;color:#0B1E3B;font-weight:750;">
                  📲 Ipakita o I-save ang QR Pass
                </a>
              </div>
            </div>
          </div>

          <!-- 2. Attendance History -->
          <div class="deped-panel-card" id="attendance-history">
            <div class="panel-header-row">
              <div class="panel-title-text">
                <h3>Attendance History (Personal na Talaan ng Pagpasok)</h3>
                <p>Tala ng iyong pag-scan sa learning center gamit ang QR Pass.</p>
              </div>
              <span class="stat-box-sub-pill green"><?= count($attendanceHistory) ?> Naitala</span>
            </div>

            <table class="deped-full-table">
              <thead>
                <tr>
                  <th>PETSA AT ORAS</th>
                  <th>ESTADO</th>
                  <th>DALUBGURO / SENTRO</th>
                  <th>MGA TALA</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($attendanceHistory)): ?>
                  <tr>
                    <td colspan="4" style="text-align:center;padding:22px;color:var(--deped-text-muted);">
                      Wala pang naitalang attendance. I-scan ang iyong QR Pass sa iyong guro sa susunod na klase.
                    </td>
                  </tr>
                <?php endif; ?>

                <?php foreach ($attendanceHistory as $ah): ?>
                  <tr>
                    <td style="font-size:12px;color:var(--deped-navy-dark);font-weight:700;">
                      <?= date('M d, Y • h:i A', strtotime($ah['scan_date'])) ?>
                    </td>
                    <td>
                      <span class="status-badge-custom approved">
                        ● <?= e($ah['status']) ?>
                      </span>
                    </td>
                    <td style="font-size:12px;">
                      <?= e($ah['teacher_name'] ?? 'San Nicolas CLC Station') ?>
                    </td>
                    <td style="font-size:11px;color:var(--deped-text-muted);">
                      <?= e($ah['notes'] ?? 'On-time verification') ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- 3. My ALS Modules -->
          <div class="deped-panel-card" id="my-modules">
            <div class="panel-header-row">
              <div class="panel-title-text">
                <h3>My ALS Modules (Mga Aralin at Gawain sa Anim na Strands)</h3>
                <p>Naka-assign sa iyo ang buong pakete ng LS1 hanggang LS6 modules at activities. Pindutin ang bawat isa para i-download.</p>
              </div>
              <span class="stat-box-sub-pill green" style="background:#DCFCE7;color:#15803D;">✓ 6/6 Strands Active</span>
            </div>

            <div class="strands-six-grid">
              <div class="strand-mini-card">
                <div class="strand-card-top">
                  <div style="display:flex;align-items:center;">
                    <div class="strand-num-pill" style="background:#185ADB;">1</div>
                    <span class="strand-name-title">LS1: Comm Skills</span>
                  </div>
                </div>
                <p style="font-size:11px;color:var(--deped-text-muted);margin:4px 0 6px 0;">Filipino & English Modules</p>
                <div style="display:flex;gap:6px;margin-top:6px;">
                  <a href="download_module.php?ls=LS1" class="toolbar-btn green" style="padding:4px 8px;font-size:11px;background:#107C41;color:#fff;text-decoration:none;border:none;">
                    ⬇ I-download
                  </a>
                </div>
              </div>

              <div class="strand-mini-card">
                <div class="strand-card-top">
                  <div style="display:flex;align-items:center;">
                    <div class="strand-num-pill" style="background:#107C41;">2</div>
                    <span class="strand-name-title">LS2: Scientific Lit</span>
                  </div>
                </div>
                <p style="font-size:11px;color:var(--deped-text-muted);margin:4px 0 6px 0;">Science & Environment</p>
                <div style="display:flex;gap:6px;margin-top:6px;">
                  <a href="download_module.php?ls=LS2" class="toolbar-btn green" style="padding:4px 8px;font-size:11px;background:#107C41;color:#fff;text-decoration:none;border:none;">
                    ⬇ I-download
                  </a>
                </div>
              </div>

              <div class="strand-mini-card">
                <div class="strand-card-top">
                  <div style="display:flex;align-items:center;">
                    <div class="strand-num-pill" style="background:#D97706;">3</div>
                    <span class="strand-name-title">LS3: Math & Problem</span>
                  </div>
                </div>
                <p style="font-size:11px;color:var(--deped-text-muted);margin:4px 0 6px 0;">Praktikal na Matematika</p>
                <div style="display:flex;gap:6px;margin-top:6px;">
                  <a href="download_module.php?ls=LS3" class="toolbar-btn green" style="padding:4px 8px;font-size:11px;background:#107C41;color:#fff;text-decoration:none;border:none;">
                    ⬇ I-download
                  </a>
                </div>
              </div>

              <div class="strand-mini-card">
                <div class="strand-card-top">
                  <div style="display:flex;align-items:center;">
                    <div class="strand-num-pill" style="background:#7C3AED;">4</div>
                    <span class="strand-name-title">LS4: Life & Career</span>
                  </div>
                </div>
                <p style="font-size:11px;color:var(--deped-text-muted);margin:4px 0 6px 0;">Hanapbuhay at Negosyo</p>
                <div style="display:flex;gap:6px;margin-top:6px;">
                  <a href="download_module.php?ls=LS4" class="toolbar-btn green" style="padding:4px 8px;font-size:11px;background:#107C41;color:#fff;text-decoration:none;border:none;">
                    ⬇ I-download
                  </a>
                </div>
              </div>

              <div class="strand-mini-card">
                <div class="strand-card-top">
                  <div style="display:flex;align-items:center;">
                    <div class="strand-num-pill" style="background:#C5221F;">5</div>
                    <span class="strand-name-title">LS5: Self & Society</span>
                  </div>
                </div>
                <p style="font-size:11px;color:var(--deped-text-muted);margin:4px 0 6px 0;">Komunidad at Pamamahala</p>
                <div style="display:flex;gap:6px;margin-top:6px;">
                  <a href="download_module.php?ls=LS5" class="toolbar-btn green" style="padding:4px 8px;font-size:11px;background:#107C41;color:#fff;text-decoration:none;border:none;">
                    ⬇ I-download
                  </a>
                </div>
              </div>

              <div class="strand-mini-card">
                <div class="strand-card-top">
                  <div style="display:flex;align-items:center;">
                    <div class="strand-num-pill" style="background:#0284C7;">6</div>
                    <span class="strand-name-title">LS6: Digital Lit</span>
                  </div>
                </div>
                <p style="font-size:11px;color:var(--deped-text-muted);margin:4px 0 6px 0;">Computer at Ligtas na Internet</p>
                <div style="display:flex;gap:6px;margin-top:6px;">
                  <a href="download_module.php?ls=LS6" class="toolbar-btn green" style="padding:4px 8px;font-size:11px;background:#107C41;color:#fff;text-decoration:none;border:none;">
                    ⬇ I-download
                  </a>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Right Column: Learner Profile & Progress -->
        <div class="right-sidebar-panel">

          <!-- 4. Learner Profile -->
          <div class="deped-panel-card" id="learner-profile" style="padding:18px;">
            <strong style="font-size:13.5px;color:var(--deped-text-dark);display:block;margin-bottom:10px;">
              👤 Impormasyon ng Mag-aaral (Profile)
            </strong>

            <div style="font-size:12px;color:var(--deped-text-muted);line-height:1.6;">
              <p style="margin:4px 0;"><strong>Buong Pangalan:</strong> <br><span style="color:var(--deped-text-dark);font-weight:700;"><?= e($fullName) ?></span></p>
              <p style="margin:4px 0;"><strong>LRN / Student No.:</strong> <br><span style="color:var(--deped-text-dark);font-family:monospace;"><?= e($lrn) ?></span></p>
              <p style="margin:4px 0;"><strong>Programa:</strong> <br><span style="color:var(--deped-text-dark);"><?= e($program) ?></span></p>
              <p style="margin:4px 0;"><strong>Tirahan:</strong> <br><span style="color:var(--deped-text-dark);"><?= e($student['address'] ?? 'San Nicolas, Pasig City') ?></span></p>
              <p style="margin:4px 0;"><strong>Telepono:</strong> <br><span style="color:var(--deped-text-dark);"><?= e($student['contact_number'] ?? '0917-123-4567') ?></span></p>
              <p style="margin:4px 0;"><strong>Email:</strong> <br><span style="color:var(--deped-text-dark);"><?= e($student['email'] ?? 'maria.reyes@example.com') ?></span></p>
            </div>

            <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--deped-border-subtle);">
              <span style="font-size:11px;font-weight:750;color:var(--deped-text-muted);display:block;">
                Pangkalahatang Progress (Modules & FLT)
              </span>
              <div class="progress-bar-wrap">
                <div class="progress-bar-fill"></div>
              </div>
              <span style="font-size:11px;color:#107C41;font-weight:700;">80% Kumpleto • Handa sa A&E Accreditation</span>
            </div>
          </div>

          <!-- Facilitator Contact Reminder -->
          <div class="deped-panel-card" style="padding:18px;">
            <strong style="font-size:13px;display:block;margin-bottom:6px;">👨‍🏫 Iyong Guro sa CLC</strong>
            <p style="font-size:11.5px;color:var(--deped-text-muted);margin-bottom:10px;">
              Teacher Elena Reyes • San Nicolas CLC Coordinator
            </p>
            <a href="tel:09178234412" class="btn-side-full blue">
              📞 Makipag-ugnayan sa Guro
            </a>
          </div>

        </div>

      </div>

      <!-- Page Footer -->
      <footer class="deped-admin-footer">
        <span>DepEd ALS &bull; Learning without limits</span>
        <div>
          <a href="#">Privacy notice</a> &bull; 
          <a href="#">Help center</a>
        </div>
      </footer>

    </main>

  </div>

</div>

</body>
</html>
