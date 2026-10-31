<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$ref = trim((string)($_GET['ref'] ?? ''));

// Fetch student record by tracking number or get latest
$student = null;
if ($ref !== '') {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE student_number = ? LIMIT 1');
    $stmt->execute([$ref]);
    $student = $stmt->fetch();
}

if (!$student) {
    // Fallback: fetch latest enrolled student
    $stmt = $pdo->query('SELECT * FROM students ORDER BY id DESC LIMIT 1');
    $student = $stmt->fetch();
}

$trackingRef = $student ? $student['student_number'] : 'ALS-2025-MNL-8492';
$fullName = $student ? trim($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']) : 'Juan M. Dela Cruz';
$program = $student ? ($student['learner_status'] === 'Elementary' ? 'ALS Elementary (Basic Literacy)' : 'ALS Junior High School (JHS)') : 'ALS Junior High School (JHS)';
$assignedCenter = $student['assigned_center'] ?? 'San Nicolas Community Learning Center';
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>DEPED ALS • Qr Pass And Status</title>
  <link rel="stylesheet" href="assets/mobile_app.css">
  <style>
    /* Print styling */
    @media print {
      body { background: #fff; }
      .deped-mobile-wrap { box-shadow: none; max-width: 100%; padding-bottom: 0; }
      .deped-bottom-navbar, .deped-app-header, .header-right-actions, .btn-deped-primary, .btn-copy-code { display: none !important; }
    }
  </style>
</head>
<body class="deped-mobile-body">

<div class="deped-mobile-wrap">

  <!-- Top App Header -->
  <header class="deped-app-header">
    <a href="index.php" class="header-left-brand">
      <div class="header-brand-logo">ALS</div>
      <div style="font-size:13px;font-weight:750;">ALS Connect</div>
    </a>

    <div class="header-center-title">
      <div class="header-dept-tag">DEPED ALS</div>
      <div class="header-screen-title">Qr Pass And Status</div>
    </div>

    <div class="header-right-actions">
      <a href="index.php#faqs" class="header-icon-btn" title="Tulong">?</a>
      <a href="admin_login.php" class="header-icon-btn" title="Admin">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      </a>
    </div>
  </header>

  <!-- Content Area -->
  <main class="deped-content">

    <!-- Green Success Status Card -->
    <div class="deped-top-banner green">
      <div class="banner-badge-icon" style="background:var(--deped-green);">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
      </div>
      <div>
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;">
          <span style="background:#B8DEC7;color:var(--deped-green);font-size:10px;font-weight:800;padding:2px 8px;border-radius:999px;">
            ✓ Rehistrado Ngayon
          </span>
        </div>
        <h2 class="banner-title" style="font-size:15px;color:var(--deped-green);">Matagumpay na Naisumite ang Iyong Rehistro!</h2>
        <p class="banner-sub" style="color:#2f5940;">
          Handa na ang iyong ALS Digital Pass para sa agarang beripikasyon sa learning center.
        </p>
      </div>
    </div>

    <!-- Official DepEd QR Pass Card (Screen 2) -->
    <div class="deped-pass-card" id="printableQrPass">
      
      <!-- Blue Header -->
      <div class="pass-card-top-navy">
        <div class="pass-card-badge-row">
          <div style="display:flex;align-items:center;gap:6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            DEPED ALS OPISYAL NA PASS
          </div>
          <span>Taong Panuruan 2025</span>
        </div>

        <div style="font-size:11px;color:#A9C4E0;text-transform:uppercase;letter-spacing:0.04em;">
          Tracking Reference Number
        </div>

        <div class="pass-tracking-row">
          <span class="pass-tracking-code" id="trackingRefText"><?= e($trackingRef) ?></span>
          <button type="button" class="btn-copy-code" onclick="copyRefCode()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
            <span id="copyBtnLabel">Kopyahin</span>
          </button>
        </div>
      </div>

      <!-- Card Body -->
      <div class="pass-card-body">
        
        <div class="pass-learner-header">
          <span style="font-size:11px;color:var(--deped-slate);text-transform:uppercase;font-weight:600;">Pangalan ng Mag-aaral</span>
          <h3 style="font-size:18px;margin:2px 0 0 0;color:var(--deped-navy);"><?= e($fullName) ?></h3>
        </div>

        <!-- Meta Pills -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
          <div class="pass-meta-pill-card" style="margin-bottom:0;">
            <div style="width:26px;height:26px;border-radius:6px;background:#EBF3FC;color:var(--deped-blue);display:grid;place-items:center;flex-shrink:0;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
            </div>
            <div>
              <span style="font-size:9.5px;color:var(--deped-slate);display:block;text-transform:uppercase;">Programa</span>
              <strong style="font-size:11.5px;color:var(--deped-navy);"><?= e($program) ?></strong>
            </div>
          </div>

          <div class="pass-meta-pill-card" style="margin-bottom:0;">
            <div style="width:26px;height:26px;border-radius:6px;background:#FEF3C7;color:#92400E;display:grid;place-items:center;flex-shrink:0;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div>
              <span style="font-size:9.5px;color:var(--deped-slate);display:block;text-transform:uppercase;">Edad / Katayuan</span>
              <strong style="font-size:11.5px;color:var(--deped-navy);"><?= (int)($student['age'] ?? 19) ?> anyos &bull; <?= e($student['civil_status'] ?? 'Single') ?></strong>
            </div>
          </div>
        </div>

        <div class="pass-meta-pill-card">
          <div style="width:28px;height:28px;border-radius:6px;background:#F0FDF4;color:var(--deped-green);display:grid;place-items:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
          </div>
          <div>
            <span style="font-size:10.5px;color:var(--deped-slate);display:block;text-transform:uppercase;">Assigned Center</span>
            <strong style="font-size:12.5px;color:var(--deped-navy);"><?= e($assignedCenter) ?></strong>
          </div>
        </div>

        <!-- QR Code Frame (Encodes Full Name strictly) -->
        <div class="qr-frame-box">
          <div class="qr-corner-bracket tl"></div>
          <div class="qr-corner-bracket tr"></div>
          <div class="qr-corner-bracket bl"></div>
          <div class="qr-corner-bracket br"></div>

          <!-- Dynamic QR Code Image (Encodes Full Name) with SVG Fallback -->
          <div id="qrCanvasWrap" style="text-align:center;">
            <img 
              src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&amp;margin=4&amp;data=<?= urlencode($fullName) ?>" 
              alt="QR Code ni <?= e($fullName) ?>" 
              width="160" 
              height="160" 
              style="display:block;margin:0 auto;border-radius:6px;background:#fff;"
            >
          </div>
        </div>

        <div style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--deped-green);margin-bottom:12px;">
          <span style="width:6px;height:6px;border-radius:50%;background:var(--deped-green);"></span>
          Aktibong QR Pass • Handa nang i-scan
        </div>

        <div style="font-size:13px;font-weight:700;color:var(--deped-navy);margin-bottom:4px;">
          I-pakita ito sa ALS Teacher o ALS Center Coordinator
        </div>
        <div style="font-size:11.5px;color:var(--deped-slate);line-height:1.4;">
          Walang kailangang i-print; sapat na ang kopya sa telepono.
        </div>

      </div>

      <!-- Action Buttons -->
      <div style="padding:0 20px 20px;display:flex;flex-direction:column;gap:10px;">
        <button type="button" class="btn-deped-primary" onclick="window.print()">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          I-save ang QR Image / Screenshot
        </button>

        <button type="button" class="schedule-toggle-btn" style="background:#EBF3FC;border-color:#C4DDF8;color:var(--deped-blue);padding:13px;" onclick="sharePass()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
          I-padala sa Messenger / SMS
        </button>
      </div>

    </div>

    <!-- Section: Mga Susunod na Hakbang (Screen 2) -->
    <div style="background:#ffffff;border:1px solid var(--deped-border);border-radius:14px;padding:18px;margin-bottom:20px;">
      <div style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:750;color:var(--deped-navy);margin-bottom:14px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--deped-blue);"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
        Mga Susunod na Hakbang
      </div>

      <div style="display:flex;flex-direction:column;gap:12px;">
        <div style="display:flex;align-items:flex-start;gap:10px;">
          <div style="width:24px;height:24px;border-radius:50%;background:var(--deped-green-light);color:var(--deped-green);font-size:11px;font-weight:800;display:grid;place-items:center;flex-shrink:0;">1</div>
          <div style="font-size:12.5px;color:var(--deped-slate);line-height:1.45;">
            <strong>I-screenshot o i-save ang QR code</strong> na ito upang may kopya ka kahit offline o walang data.
          </div>
        </div>

        <div style="display:flex;align-items:flex-start;gap:10px;">
          <div style="width:24px;height:24px;border-radius:50%;background:var(--deped-green-light);color:var(--deped-green);font-size:11px;font-weight:800;display:grid;place-items:center;flex-shrink:0;">2</div>
          <div style="font-size:12.5px;color:var(--deped-slate);line-height:1.45;">
            <strong>Dalhin ang 1 valid ID o PSA birth certificate</strong> (kung meron) sa takdang petsa ng oryentasyon sa San Nicolas CLC.
          </div>
        </div>

        <div style="display:flex;align-items:flex-start;gap:10px;">
          <div style="width:24px;height:24px;border-radius:50%;background:var(--deped-green-light);color:var(--deped-green);font-size:11px;font-weight:800;display:grid;place-items:center;flex-shrink:0;">3</div>
          <div style="font-size:12.5px;color:var(--deped-slate);line-height:1.45;">
            <strong>I-scan lamang ito ng guro</strong> para sa instant verification nang walang mahabang pila!
          </div>
        </div>
      </div>
    </div>

    <!-- Help Callout -->
    <div style="background:#F4F7FB;border:1px solid var(--deped-border);border-radius:12px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
      <div>
        <div style="font-size:12px;font-weight:700;color:var(--deped-navy);">May katanungan tungkol sa pass?</div>
        <div style="font-size:11.5px;color:var(--deped-slate);">Kausapin ang San Nicolas ALS Facilitator</div>
      </div>
      <a href="tel:09178234412" style="width:36px;height:36px;border-radius:50%;background:#ffffff;border:1px solid var(--deped-border);color:var(--deped-navy);display:grid;place-items:center;text-decoration:none;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
      </a>
    </div>

  </main>

  <!-- Bottom Navigation Bar (4 Tabs) -->
  <nav class="deped-bottom-navbar">
    <a href="index.php" class="deped-nav-tab">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
      <span>Hub</span>
    </a>
    <a href="student_enroll.php" class="deped-nav-tab">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
      <span>Enroll</span>
    </a>
    <a href="qr_pass.php" class="deped-nav-tab active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
      <span>Pass</span>
    </a>
    <a href="admin_scanner.php" class="deped-nav-tab">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path></svg>
      <span>Scanner</span>
    </a>
  </nav>

</div>

<script>
function copyRefCode() {
  const code = document.getElementById('trackingRefText').innerText;
  navigator.clipboard.writeText(code).then(() => {
    document.getElementById('copyBtnLabel').innerText = 'Na-kopya!';
    setTimeout(() => {
      document.getElementById('copyBtnLabel').innerText = 'Kopyahin';
    }, 2000);
  });
}

function sharePass() {
  const code = document.getElementById('trackingRefText').innerText;
  if (navigator.share) {
    navigator.share({
      title: 'Aking ALS Digital QR Pass',
      text: 'Narito ang aking DepEd ALS Enrollment Pass: ' + code,
      url: window.location.href,
    });
  } else {
    copyRefCode();
    alert('Na-kopya na ang link ng iyong QR Pass!');
  }
}
</script>

</body>
</html>

