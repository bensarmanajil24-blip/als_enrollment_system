<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

require_teacher();

$teacherId = (int)($_SESSION['teacher_id'] ?? 0);
$teacher = null;
if ($teacherId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM teachers WHERE id = ? LIMIT 1');
    $stmt->execute([$teacherId]);
    $teacher = $stmt->fetch();
}

if (!$teacher && !empty($_SESSION['user_email'])) {
    $stmt = $pdo->prepare('SELECT * FROM teachers WHERE email = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_email']]);
    $teacher = $stmt->fetch();
}

if (!$teacher) {
    $stmt = $pdo->query("SELECT * FROM teachers WHERE email = 'bensar.manajili@deped.gov.ph' OR name LIKE '%Bensar%' LIMIT 1");
    $teacher = $stmt->fetch();
}

$teacherName = $teacher['name'] ?? ($_SESSION['user_name'] ?? 'Bensar S. Manajili');
$teacherEmail = $teacher['email'] ?? ($_SESSION['user_email'] ?? 'bensar.manajili@deped.gov.ph');
$assignedCenter = $teacher['assigned_center'] ?? 'San Nicolas Community Learning Center';
$realTeacherId = (int)($teacher['id'] ?? 1);

// Teacher Initials
$parts = explode(' ', $teacherName);
$initials = strtoupper(substr($parts[0] ?? 'B', 0, 1) . substr(end($parts) ?: 'M', 0, 1));

// First name for greeting
$greetingName = $parts[0] ?? 'Bensar';

// Fetch Active Session
$sessStmt = $pdo->prepare("
    SELECT * FROM attendance_sessions 
    WHERE status = 'active' 
    ORDER BY id ASC 
    LIMIT 1
");
$sessStmt->execute();
$activeSession = $sessStmt->fetch();

$sessionTitle = $activeSession['session_title'] ?? 'Scientific Literacy & Critical Thinking';
$sessionStrand = $activeSession['strand'] ?? 'LS2 • Basic Literacy Program';
$sessionRoom = $activeSession['room'] ?? 'Learning room 1';
$sessionCode = $activeSession['session_code'] ?? 'ALS-892';

// Fetch Learner Masterlist
$assignedLearners = [
    [
        'name' => 'Maria Isabel Reyes',
        'lrn' => '2024-008-001',
        'program' => 'Basic Literacy',
        'attendance' => 'Not checked in',
        'att_class' => 'neutral'
    ],
    [
        'name' => 'Alyssa Joy Mendoza',
        'lrn' => '2026-008-002',
        'program' => 'A&E Junior High',
        'attendance' => 'Present',
        'att_class' => 'present'
    ],
    [
        'name' => 'John Michael Garcia',
        'lrn' => '2026-008-003',
        'program' => 'A&E Elementary',
        'attendance' => 'Present',
        'att_class' => 'present'
    ],
    [
        'name' => 'Fatima Rose Cruz',
        'lrn' => '2026-008-004',
        'program' => 'Basic Literacy',
        'attendance' => 'Not checked in',
        'att_class' => 'neutral'
    ]
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dalubguro and Instructional Manager dashboard - DepEd ALS</title>
  <link rel="stylesheet" href="assets/admin.css">
  <style>
    .deped-sidebar-v3 {
      width: 255px;
      background: #0B1E3B;
      color: #FFFFFF;
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
      min-height: 100vh;
      box-sizing: border-box;
      padding: 22px 14px;
    }
    .deped-brand-strip {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: #FFFFFF;
      margin-bottom: 20px;
      padding: 0 6px;
    }
    .deped-workspace-badge {
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      padding: 10px 12px;
      margin-bottom: 18px;
    }
    .deped-nav-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 12px;
      border-radius: 8px;
      color: #94A3B8;
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      transition: all 0.15s ease;
      margin-bottom: 2px;
    }
    .deped-nav-item:hover { background: rgba(255, 255, 255, 0.05); color: #FFFFFF; }
    .deped-nav-item.active { background: #185ADB; color: #FFFFFF; font-weight: 750; }

    .deped-topbar-strip {
      background: #FFFFFF;
      border-bottom: 1px solid #E2E8F0;
      padding: 12px 32px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .im-stat-card {
      background: #FFFFFF;
      border: 1px solid #E2E8F0;
      border-radius: 12px;
      padding: 18px 20px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      gap: 12px;
    }

    .im-card-panel {
      background: #FFFFFF;
      border: 1px solid #E2E8F0;
      border-radius: 12px;
      padding: 22px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .today-session-card {
      background: #0B1E3B;
      color: #FFFFFF;
      border-radius: 14px;
      padding: 22px;
      box-shadow: 0 4px 15px rgba(11, 30, 59, 0.2);
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .att-pill {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 10px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 750;
    }
    .att-pill.present {
      background: #ECFDF5;
      color: #059669;
      border: 1px solid #A7F3D0;
    }
    .att-pill.neutral {
      background: #F1F5F9;
      color: #64748B;
      border: 1px solid #E2E8F0;
    }

    .material-item-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid #F1F5F9;
      text-decoration: none;
      color: inherit;
    }
    .material-item-row:last-child {
      border-bottom: none;
    }
  </style>
</head>
<body class="admin-body">

<!-- Page Watermark Header (Image 1) -->
<div style="padding:10px 32px;font-size:14px;color:#94A3B8;font-weight:700;letter-spacing:-0.01em;background:#F8FAFC;">
  Dalubguro and Instructional Manager dashboard
</div>

<div class="deped-laptop-layout">

  <!-- ==================== SIDEBAR ==================== -->
  <aside class="deped-sidebar-v3">
    <a href="teacher_dashboard.php" class="deped-brand-strip">
      <div style="width:34px;height:34px;background:#FFFFFF;border-radius:8px;display:grid;place-items:center;color:#0B1E3B;">
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
      <strong style="font-size:12.5px;color:#FFFFFF;">Dalubguro / IM</strong>
    </div>

    <!-- Navigation (Image 1) -->
    <nav style="display:flex;flex-direction:column;gap:3px;">
      <a href="teacher_dashboard.php" class="deped-nav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        <span>Overview</span>
      </a>

      <a href="teacher_assigned_learners.php" class="deped-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
        <span>My learners</span>
      </a>

      <a href="enroll.php" class="deped-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        <span>Enrollment review</span>
      </a>

      <a href="teacher_attendance.php" class="deped-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        <span>Attendance</span>
      </a>

      <a href="teacher_modules.php" class="deped-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
        <span>Learning strands</span>
      </a>

      <a href="teacher_assigned_learners.php#portfolio" class="deped-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
        <span>Portfolio review</span>
      </a>

      <a href="division_reports.php" class="deped-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
        <span>Reports</span>
      </a>
    </nav>

    <!-- Sub-box: Community Learning Center -->
    <div style="margin-top:auto;padding-top:16px;border-top:1px solid rgba(255, 255, 255, 0.08);">
      <span style="font-size:9.5px;font-weight:750;letter-spacing:0.06em;color:#94A3B8;text-transform:uppercase;display:block;margin-bottom:2px;">COMMUNITY LEARNING CENTER</span>
      <strong style="font-size:12px;color:#FFFFFF;display:block;">San Nicolas CLC</strong>
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

  <!-- ==================== MAIN VIEWPORT ==================== -->
  <div style="flex:1;display:flex;flex-direction:column;min-width:0;background:#F8FAFC;min-height:100vh;">

    <!-- Topbar (Image 1) -->
    <header class="deped-topbar-strip">
      <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#64748B;">
        <span>Dalubguro / IM</span>
        <span>&gt;</span>
        <strong style="color:#0F172A;">Overview</strong>
      </div>

      <div style="display:flex;align-items:center;gap:18px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" style="width:18px;height:18px;cursor:pointer;"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>

        <div style="display:flex;align-items:center;gap:10px;cursor:pointer;">
          <div style="width:34px;height:34px;border-radius:50%;background:#ECFDF5;color:#059669;font-weight:800;font-size:13px;display:grid;place-items:center;">
            <?= htmlspecialchars($initials) ?>
          </div>
          <div>
            <strong style="font-size:12.5px;color:#0F172A;display:block;"><?= htmlspecialchars($teacherName) ?></strong>
            <span style="font-size:10.5px;color:#64748B;">Dalubguro / IM</span>
          </div>
          <svg viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" style="width:14px;height:14px;margin-left:2px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </div>

        <a href="logout.php" style="border:1px solid #E2E8F0;padding:6px 12px;border-radius:6px;background:#fff;text-decoration:none;color:#334155;font-weight:700;font-size:12px;display:inline-flex;align-items:center;gap:6px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          <span>Log out</span>
        </a>
      </div>
    </header>

    <!-- Main Workspace Container -->
    <main style="padding:24px 32px;display:flex;flex-direction:column;gap:20px;flex:1;box-sizing:border-box;">

      <!-- Header Title & Action (Image 1) -->
      <section style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
        <div>
          <span style="font-size:10px;font-weight:800;letter-spacing:0.06em;color:#185ADB;text-transform:uppercase;display:block;margin-bottom:4px;">
            SAN NICOLAS COMMUNITY LEARNING CENTER
          </span>
          <h1 style="font-size:26px;font-weight:850;color:#0F172A;margin:0 0 6px 0;letter-spacing:-0.02em;">
            Good morning, <?= htmlspecialchars($greetingName) ?>!
          </h1>
          <p style="font-size:13px;color:#64748B;margin:0;">
            Your learners, sessions, and reviews &mdash; all in one place.
          </p>

          <div style="font-size:11.5px;color:#94A3B8;margin-top:10px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span style="background:#ECFDF5;color:#059669;border:1px solid #A7F3D0;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:750;">
              Dalubguro / Instructional Manager
            </span>
            <span style="color:#64748B;font-weight:600;">SY 2026&ndash;2027</span>
            <span style="margin-left:auto;color:#94A3B8;">Monday, October 26, 2026</span>
          </div>
        </div>

        <button type="button" onclick="alert('Assign Existing Learner modal opened.');" class="table-action-pill" style="padding:9px 18px;font-size:12.5px;font-weight:750;background:#185ADB;color:#FFFFFF;border-color:#185ADB;display:inline-flex;align-items:center;gap:8px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
          <span>+ Assign existing learner</span>
        </button>
      </section>

      <!-- 4 Stat Cards (Image 1) -->
      <section style="display:grid;grid-template-columns:repeat(4, 1fr);gap:16px;">
        <!-- Active learners -->
        <div class="im-stat-card">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:12px;font-weight:700;color:#64748B;">Active learners</span>
            <div style="width:28px;height:28px;border-radius:50%;background:#ECFDF5;color:#059669;display:grid;place-items:center;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
            </div>
          </div>
          <div>
            <div style="font-size:28px;font-weight:850;color:#0F172A;line-height:1;">74</div>
            <div style="font-size:11.5px;color:#059669;font-weight:600;margin-top:6px;">San Nicolas learning community</div>
          </div>
        </div>

        <!-- Pending portfolio review -->
        <div class="im-stat-card">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:12px;font-weight:700;color:#64748B;">Pending portfolio review</span>
            <div style="width:28px;height:28px;border-radius:50%;background:#FEF3C7;color:#D97706;display:grid;place-items:center;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            </div>
          </div>
          <div>
            <div style="font-size:28px;font-weight:850;color:#0F172A;line-height:1;">1</div>
            <div style="font-size:11.5px;color:#D97706;font-weight:600;margin-top:6px;">Ready for your verification</div>
          </div>
        </div>

        <!-- Attendance logs -->
        <div class="im-stat-card">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:12px;font-weight:700;color:#64748B;">Attendance logs</span>
            <div style="width:28px;height:28px;border-radius:50%;background:#EFF6FF;color:#185ADB;display:grid;place-items:center;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
          </div>
          <div>
            <div style="font-size:28px;font-weight:850;color:#0F172A;line-height:1;">5</div>
            <div style="font-size:11.5px;color:#185ADB;font-weight:600;margin-top:6px;">Recent learner check-ins</div>
          </div>
        </div>

        <!-- Learning strands -->
        <div class="im-stat-card">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:12px;font-weight:700;color:#64748B;">Learning strands</span>
            <div style="width:28px;height:28px;border-radius:50%;background:#ECFDF5;color:#059669;display:grid;place-items:center;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
            </div>
          </div>
          <div>
            <div style="font-size:28px;font-weight:850;color:#0F172A;line-height:1;">6 / 6</div>
            <div style="font-size:11.5px;color:#059669;font-weight:600;margin-top:6px;">All strands have learning materials</div>
          </div>
        </div>
      </section>

      <!-- 2-Column Main Section (Image 1) -->
      <div style="display:grid;grid-template-columns:1.8fr 1fr;gap:20px;align-items:start;">

        <!-- ==================== LEFT COLUMN ==================== -->
        <div style="display:flex;flex-direction:column;gap:20px;">

          <!-- Card 1: Enrollment & Portfolio Review (Image 1) -->
          <div class="im-card-panel">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
              <h2 style="font-size:16px;font-weight:800;color:#0F172A;margin:0;">
                Enrollment &amp; portfolio review
              </h2>
              <a href="enroll.php" style="font-size:12px;font-weight:700;color:#185ADB;text-decoration:none;">
                View learner records &rarr;
              </a>
            </div>

            <!-- Single Review Item Box -->
            <div style="border:1px solid #E2E8F0;border-radius:10px;padding:16px;background:#FFFFFF;">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div style="display:flex;align-items:center;gap:12px;">
                  <div style="width:40px;height:40px;border-radius:10px;background:#FEF3C7;color:#D97706;font-weight:850;font-size:14px;display:grid;place-items:center;">
                    JR
                  </div>
                  <div>
                    <strong style="font-size:14px;color:#0F172A;display:block;">Jose Antonio Ramos</strong>
                    <span style="font-size:12px;color:#64748B;">LRN 2026-008-014 &bull; A&amp;E Junior High School</span>
                  </div>
                </div>

                <span style="background:#FEF3C7;color:#B45309;border:1px solid #FDE68A;font-size:11px;font-weight:750;padding:3px 10px;border-radius:999px;">
                  For review
                </span>
              </div>

              <!-- Checklist Strip -->
              <div style="margin-top:12px;padding:8px 12px;background:#F8FAFC;border:1px dashed #CBD5E1;border-radius:6px;display:flex;align-items:center;gap:8px;font-size:11.5px;color:#475569;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" style="width:14px;height:14px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                <span>PIS uploaded &bull; Report card uploaded &bull; <strong style="color:#B91C1C;">Birth certificate missing</strong></span>
              </div>

              <!-- Sub-strip -->
              <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:10px;border-top:1px solid #F1F5F9;">
                <span style="font-size:11px;color:#94A3B8;">Submitted October 23, 2026</span>
                <a href="admin_view_account.php?id=2&type=student" class="table-action-pill" style="font-size:11.5px;padding:5px 12px;">
                  &rarr; View learner record
                </a>
              </div>
            </div>
          </div>

          <!-- Card 2: Learner Masterlist (Image 1) -->
          <div class="im-card-panel">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
              <h2 style="font-size:16px;font-weight:800;color:#0F172A;margin:0;">
                Learner masterlist
              </h2>
              <a href="teacher_assigned_learners.php" style="font-size:12px;font-weight:700;color:#185ADB;text-decoration:none;">
                View all learner records &rarr;
              </a>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;font-size:12px;color:#64748B;">
              <span>Assigned learners</span>
              <span style="background:#F1F5F9;color:#334155;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;">
                Assigned learners
              </span>
            </div>

            <!-- Masterlist Table -->
            <table class="odb-table">
              <thead>
                <tr>
                  <th>LEARNER RECORD</th>
                  <th>PROGRAM</th>
                  <th>ATTENDANCE</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($assignedLearners as $lr): ?>
                  <tr>
                    <td>
                      <strong style="font-size:13px;color:#0F172A;display:block;"><?= htmlspecialchars($lr['name']) ?></strong>
                      <span style="font-size:11px;color:#64748B;"><?= htmlspecialchars($lr['lrn']) ?></span>
                    </td>
                    <td style="color:#475569;font-weight:600;font-size:12.5px;"><?= htmlspecialchars($lr['program']) ?></td>
                    <td>
                      <span class="att-pill <?= $lr['att_class'] ?>">
                        <?= htmlspecialchars($lr['attendance']) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

        </div>

        <!-- ==================== RIGHT COLUMN ==================== -->
        <div style="display:flex;flex-direction:column;gap:20px;">

          <!-- Card 1: Today's Session (Dark Navy Blue Card) -->
          <div class="today-session-card">
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span style="font-size:10px;font-weight:800;letter-spacing:0.06em;color:#94A3B8;text-transform:uppercase;">
                TODAY'S SESSION
              </span>
              <span style="background:#10B981;color:#FFFFFF;font-size:10.5px;font-weight:750;padding:2px 8px;border-radius:999px;">
                In progress
              </span>
            </div>

            <div>
              <h3 style="font-size:18px;font-weight:850;margin:0 0 6px 0;line-height:1.25;color:#FFFFFF;">
                <?= htmlspecialchars($sessionTitle) ?>
              </h3>
              <span style="font-size:12px;color:#CBD5E1;"><?= htmlspecialchars($sessionStrand) ?></span>
            </div>

            <div style="font-size:12px;color:#94A3B8;display:flex;align-items:center;gap:6px;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              <span>8:00&ndash;10:00 AM &bull; <?= htmlspecialchars($sessionRoom) ?></span>
            </div>

            <a href="teacher_attendance.php?session=<?= urlencode($sessionCode) ?>" style="background:#FFFFFF;color:#0B1E3B;border-radius:8px;padding:10px;text-align:center;font-size:12.5px;font-weight:750;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 2px 6px rgba(0,0,0,0.1);">
              <svg viewBox="0 0 24 24" fill="none" stroke="#0B1E3B" stroke-width="2" style="width:15px;height:15px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
              <span>Manage session attendance</span>
            </a>
          </div>

          <!-- Card 2: Learning Materials (Image 1) -->
          <div class="im-card-panel">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
              <h3 style="font-size:14px;font-weight:800;color:#0F172A;margin:0;">
                Learning materials
              </h3>
              <a href="teacher_modules.php" style="font-size:11.5px;font-weight:700;color:#185ADB;text-decoration:none;">
                View all materials &rarr;
              </a>
            </div>

            <div>
              <a href="teacher_modules.php?strand=LS1" class="material-item-row">
                <div style="display:flex;align-items:center;gap:10px;">
                  <span style="background:#ECFDF5;color:#059669;font-weight:850;font-size:10.5px;padding:3px 7px;border-radius:4px;">LS1</span>
                  <div>
                    <strong style="font-size:12.5px;color:#0F172A;display:block;">Communication Skills</strong>
                    <span style="font-size:11px;color:#64748B;">12 modules</span>
                  </div>
                </div>
                <span style="color:#94A3B8;">&rsaquo;</span>
              </a>

              <a href="teacher_modules.php?strand=LS2" class="material-item-row">
                <div style="display:flex;align-items:center;gap:10px;">
                  <span style="background:#ECFDF5;color:#059669;font-weight:850;font-size:10.5px;padding:3px 7px;border-radius:4px;">LS2</span>
                  <div>
                    <strong style="font-size:12.5px;color:#0F172A;display:block;">Scientific Literacy</strong>
                    <span style="font-size:11px;color:#64748B;">12 modules</span>
                  </div>
                </div>
                <span style="color:#94A3B8;">&rsaquo;</span>
              </a>

              <a href="teacher_modules.php?strand=LS3" class="material-item-row">
                <div style="display:flex;align-items:center;gap:10px;">
                  <span style="background:#ECFDF5;color:#059669;font-weight:850;font-size:10.5px;padding:3px 7px;border-radius:4px;">LS3</span>
                  <div>
                    <strong style="font-size:12.5px;color:#0F172A;display:block;">Mathematical Skills</strong>
                    <span style="font-size:11px;color:#64748B;">9 modules</span>
                  </div>
                </div>
                <span style="color:#94A3B8;">&rsaquo;</span>
              </a>
            </div>
          </div>

          <!-- Card 3: Learners scan session QR in Student Portal -->
          <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:12px;padding:16px;display:flex;align-items:flex-start;gap:12px;">
            <div style="width:34px;height:34px;background:#DBEAFE;border-radius:8px;display:grid;place-items:center;color:#185ADB;flex-shrink:0;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            </div>
            <div>
              <strong style="font-size:12px;color:#1E40AF;display:block;margin-bottom:2px;">
                Learners scan the session QR in Student portal
              </strong>
              <p style="font-size:11px;color:#1D4ED8;line-height:1.45;margin:0;">
                Display the center's session QR. Learners can scan it directly in their Student portal.
              </p>
            </div>
          </div>

        </div>

      </div>

      <!-- Footer (Image 1) -->
      <footer style="margin-top:auto;padding-top:16px;border-top:1px solid #E2E8F0;display:flex;justify-content:space-between;align-items:center;font-size:11px;color:#94A3B8;">
        <div>DepEd ALS &bull; Learning without limits</div>
        <div style="display:flex;gap:14px;">
          <a href="#" style="color:#94A3B8;text-decoration:none;">Privacy notice</a>
          <a href="#" style="color:#94A3B8;text-decoration:none;">Help center</a>
        </div>
      </footer>

    </main>
  </div>
</div>

</body>
</html>
