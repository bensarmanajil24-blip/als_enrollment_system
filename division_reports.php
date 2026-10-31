<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

require_admin();

// Compute Division Aggregates
$totalEnrollees = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0")->fetchColumn();
$approved = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND status = 'Approved'")->fetchColumn();
$pending = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND status = 'Pending'")->fetchColumn();
$rejected = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND status = 'Rejected'")->fetchColumn();

// Program Distribution
$elemCount = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND learner_status = 'Elementary'")->fetchColumn();
$jhsCount = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND learner_status = 'Junior High School'")->fetchColumn();

// Strands Counts
$lsCounts = [
    'LS1: Communication Skills' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND strand LIKE '%LS1%'")->fetchColumn(),
    'LS2: Scientific Literacy' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND strand LIKE '%LS2%'")->fetchColumn(),
    'LS3: Math & Problem Solving' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND strand LIKE '%LS3%'")->fetchColumn(),
    'LS4: Life & Career Skills' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND strand LIKE '%LS4%'")->fetchColumn(),
    'LS5: Self & Society' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND strand LIKE '%LS5%'")->fetchColumn(),
    'LS6: Digital Literacy' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND strand LIKE '%LS6%'")->fetchColumn(),
];

// Document Compliance
$completeDocs = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND doc_psa = 1 AND doc_brgy = 1 AND doc_card = 1")->fetchColumn();
$missingDocs = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND (doc_psa = 0 OR doc_brgy = 0 OR doc_card = 0)")->fetchColumn();

// Total Attendance Scans
$totalScans = (int)$pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
$presentScans = (int)$pdo->query("SELECT COUNT(*) FROM attendance WHERE status = 'Present'")->fetchColumn();
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DepEd ALS - Division Reports & Consolidation</title>
  <link rel="stylesheet" href="assets/admin.css">
  <style>
    .report-breakdown-box {
      background: #FFFFFF;
      border: 1px solid var(--deped-border-subtle);
      border-radius: 12px;
      padding: 18px 20px;
      margin-bottom: 20px;
    }
    .report-bar-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 6px;
      font-size: 12px;
    }
    .report-progress-track {
      background: #E2E8F0;
      height: 8px;
      border-radius: 999px;
      overflow: hidden;
      margin-bottom: 14px;
    }
    .report-progress-fill {
      background: var(--deped-blue-primary);
      height: 100%;
      border-radius: 999px;
    }
    @media print {
      .deped-sidebar, .deped-top-header, .btn-hero-white, .toolbar-btn {
        display: none !important;
      }
      .deped-workspace-container {
        padding: 0 !important;
      }
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
      <a href="student_masterlist.php" class="deped-nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        <span>Student Masterlist</span>
      </a>
      <a href="division_reports.php" class="deped-nav-link active">
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
        DepEd Division Reports & Statistical Consolidation
      </div>
      <div class="header-user-profile">
        <button type="button" class="toolbar-btn blue" onclick="window.print()">
          🖨️ I-print ang Opisyal na Ulat
        </button>
      </div>
    </header>

    <main class="deped-workspace-container">

      <!-- Official Banner -->
      <section class="deped-hero-banner" style="background:#0B1E3B;">
        <div class="banner-pill-tags-row">
          <span class="banner-sys-tag">Department of Education</span>
          <span class="banner-sys-tag green">Schools Division Office</span>
          <span class="banner-sys-tag">DIV-DEPO-7A</span>
        </div>
        <div class="hero-content-split">
          <div class="hero-text-block">
            <h1>Konsolidasyon ng Ulat sa Alternative Learning System</h1>
            <p>
              Ito ang opisyal na buod ng mga talaan ng enrollment, distribusyon sa Learning Strands (LS1–LS6), at katayuan ng attendance para sa Division Focal Person at San Nicolas Community Learning Center.
            </p>
          </div>
          <div class="hero-actions-block">
            <span style="font-size:12px;color:#94A9C9;">Nalathala: <?= date('F d, Y') ?></span>
          </div>
        </div>
      </section>

      <!-- 4 High Level Summary Cards -->
      <section class="stat-cards-4col-grid">
        <div class="deped-stat-box">
          <div class="stat-box-header">
            <span class="stat-box-title">KABUUANG TALAAN</span>
            <div class="stat-box-icon blue">📈</div>
          </div>
          <div class="stat-box-big-num"><?= $totalEnrollees ?></div>
          <span class="stat-box-sub-pill green">100% Aktibong Rehistro</span>
        </div>

        <div class="deped-stat-box">
          <div class="stat-box-header">
            <span class="stat-box-title">NAAPRUBAHAN</span>
            <div class="stat-box-icon green">✓</div>
          </div>
          <div class="stat-box-big-num"><?= $approved ?></div>
          <span class="stat-box-sub-pill green"><?= $totalEnrollees > 0 ? round(($approved/$totalEnrollees)*100) : 0 ?>% Approval Rate</span>
        </div>

        <div class="deped-stat-box">
          <div class="stat-box-header">
            <span class="stat-box-title">KULANG NA DOKUMENTO</span>
            <div class="stat-box-icon red">⚠</div>
          </div>
          <div class="stat-box-big-num"><?= $missingDocs ?></div>
          <span class="stat-box-sub-pill red">Nangangailangan ng PSA / Card</span>
        </div>

        <div class="deped-stat-box">
          <div class="stat-box-header">
            <span class="stat-box-title">ATTENDANCE SCANS</span>
            <div class="stat-box-icon blue">📷</div>
          </div>
          <div class="stat-box-big-num"><?= $totalScans ?></div>
          <span class="stat-box-sub-pill green"><?= $presentScans ?> Naitalang Present</span>
        </div>
      </section>

      <!-- 2-Column Reports -->
      <div class="laptop-two-col-grid">

        <!-- Left: Strands Distribution Bars -->
        <div class="left-wide-column">
          <div class="report-breakdown-box">
            <h3 style="font-size:15px;font-weight:800;margin:0 0 16px 0;color:var(--deped-text-dark);">
              Distribusyon ayon sa Learning Systems (LS1 hanggang LS6)
            </h3>

            <?php foreach ($lsCounts as $strandTitle => $cnt): 
              $pct = $totalEnrollees > 0 ? round(($cnt / $totalEnrollees) * 100) : 0;
            ?>
              <div class="report-bar-row">
                <strong><?= e($strandTitle) ?></strong>
                <span style="font-weight:750;color:var(--deped-blue-primary);"><?= $cnt ?> Mag-aaral (<?= $pct ?>%)</span>
              </div>
              <div class="report-progress-track">
                <div class="report-progress-fill" style="width:<?= $pct ?>%;"></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Right: Program Breakdown & Action Plan -->
        <div class="right-sidebar-panel">

          <div class="report-breakdown-box">
            <h4 style="font-size:13.5px;font-weight:800;margin:0 0 12px 0;">Antas ng Edukasyon</h4>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--deped-border-subtle);font-size:12px;">
              <span>Elementary (Basic Literacy)</span>
              <strong><?= $elemCount ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:12px;">
              <span>Junior High School (JHS)</span>
              <strong><?= $jhsCount ?></strong>
            </div>
          </div>

          <div class="report-breakdown-box" style="background:#F8FAFD;">
            <h4 style="font-size:13.5px;font-weight:800;margin:0 0 8px 0;color:var(--deped-navy-dark);">
              Pirma at Pagpapatibay
            </h4>
            <p style="font-size:11.5px;color:var(--deped-text-muted);margin-bottom:20px;line-height:1.45;">
              Pinatutunayan na ang mga datos sa itaas ay hango sa opisyal na talaan ng Alternative Learning System.
            </p>
            <div style="margin-top:30px;border-top:1px solid #000;padding-top:4px;font-size:11.5px;font-weight:800;">
              ALS Division Focal Person / Coordinator
            </div>
          </div>

        </div>

      </div>

    </main>

  </div>

</div>

</body>
</html>

