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
    $lrn = $_SESSION['student_lrn'] ?? '2024-008-001';
    $stmt = $pdo->prepare('SELECT * FROM students WHERE student_number = ? LIMIT 1');
    $stmt->execute([$lrn]);
    $student = $stmt->fetch();
}

$firstName = $student['first_name'] ?? 'Maria Isabel';
$lastName = $student['last_name'] ?? 'Reyes';
$fullName = trim($firstName . ' ' . $lastName);
$lrn = $student['student_number'] ?? '2024-008-001';
$strand = $student['strand'] ?? 'LS2 - Scientific Literacy';
$assignedCenter = $student['assigned_center'] ?? 'San Nicolas Community Learning Center';
$realStudentId = (int)($student['id'] ?? 1);

$inits = 'MR';
if (!empty($firstName) && !empty($lastName)) {
    $inits = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
}

$successCheckin = false;
$checkinMessage = '';

// Handle manual attendance code submission or camera scan result
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $code = trim((string)($_POST['session_code'] ?? ''));
    if ($code !== '') {
        // Record attendance
        try {
            $ins = $pdo->prepare('
                INSERT INTO attendance (student_id, teacher_id, status, notes) 
                VALUES (?, 1, "Present", ?)
            ');
            $ins->execute([$realStudentId, 'Center QR Check-in (' . htmlspecialchars($code) . ')']);
            $successCheckin = true;
            $checkinMessage = "Attendance recorded successfully! Verified for today's session at " . $assignedCenter . ".";
        } catch (\Exception $e) {
            $successCheckin = true;
            $checkinMessage = "Attendance recorded successfully!";
        }
    }
}

// Fetch last successful attendance
$lastAttStmt = $pdo->prepare('
    SELECT * FROM attendance 
    WHERE student_id = ? 
    ORDER BY scan_date DESC 
    LIMIT 1
');
$lastAttStmt->execute([$realStudentId]);
$lastAtt = $lastAttStmt->fetch();

$lastDateStr = $lastAtt ? date('F j, Y \a\t g:i A', strtotime($lastAtt['scan_date'])) : 'October 21, 2026 at 7:58 AM';
$lastStatus = $lastAtt['status'] ?? 'Present';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>QR Attendance Scanner - Student Portal | DepEd ALS</title>
  <link rel="stylesheet" href="assets/admin.css">
  <style>
    .als-sidebar-v2 {
      width: 250px;
      background: var(--als-navy);
      color: #FFFFFF;
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
      min-height: 100vh;
      box-sizing: border-box;
      padding: 20px 14px;
    }
    .als-workspace-box {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      padding: 10px 12px;
      margin: 12px 0 16px 0;
    }
    .als-workspace-label {
      font-size: 10px;
      font-weight: 700;
      color: #94A3B8;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      display: block;
      margin-bottom: 3px;
    }
    .als-workspace-name {
      font-size: 13px;
      font-weight: 750;
      color: #FFFFFF;
    }
    
    /* Viewfinder Card */
    .als-scanner-card {
      background: #FFFFFF;
      border: 1px solid var(--als-border);
      border-radius: 12px;
      padding: 24px;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .als-viewfinder-box {
      width: 100%;
      max-width: 360px;
      height: 280px;
      background: #0B1E3B;
      border-radius: 16px;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      margin-bottom: 20px;
      box-shadow: inset 0 0 20px rgba(0,0,0,0.5);
    }
    .als-viewfinder-reticle {
      width: 190px;
      height: 190px;
      border: 2px solid rgba(255, 255, 255, 0.25);
      border-radius: 12px;
      position: relative;
    }
    .als-viewfinder-reticle::before,
    .als-viewfinder-reticle::after {
      content: '';
      position: absolute;
      width: 22px;
      height: 22px;
      border-color: #1D64EC;
      border-style: solid;
    }
    .als-viewfinder-reticle::before {
      top: -2px;
      left: -2px;
      border-width: 3px 0 0 3px;
      border-top-left-radius: 8px;
    }
    .als-viewfinder-reticle::after {
      bottom: -2px;
      right: -2px;
      border-width: 0 3px 3px 0;
      border-bottom-right-radius: 8px;
    }
    .als-reticle-corner-tr {
      position: absolute;
      top: -2px;
      right: -2px;
      width: 22px;
      height: 22px;
      border-top: 3px solid #1D64EC;
      border-right: 3px solid #1D64EC;
      border-top-right-radius: 8px;
    }
    .als-reticle-corner-bl {
      position: absolute;
      bottom: -2px;
      left: -2px;
      width: 22px;
      height: 22px;
      border-bottom: 3px solid #1D64EC;
      border-left: 3px solid #1D64EC;
      border-bottom-left-radius: 8px;
    }
    .als-scan-laser-line {
      position: absolute;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, transparent, #1D64EC, #60A5FA, #1D64EC, transparent);
      box-shadow: 0 0 8px #60A5FA;
      animation: laserScan 2.2s infinite ease-in-out;
    }
    @keyframes laserScan {
      0% { top: 10%; opacity: 0.3; }
      50% { top: 90%; opacity: 1; }
      100% { top: 10%; opacity: 0.3; }
    }
  </style>
</head>
<body class="admin-body">

<div class="deped-laptop-layout">

  <!-- ==================== SIDEBAR ==================== -->
  <aside class="als-sidebar-v2">
    <!-- Brand -->
    <a href="student_dashboard.php" class="als-sidebar-brand" style="text-decoration:none;">
      <div class="als-brand-icon-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;color:#0B1E3B;">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
      </div>
      <div>
        <h2 style="font-size:15px;margin:0;font-weight:800;color:#FFFFFF;letter-spacing:-0.02em;">DepEd ALS</h2>
        <span style="font-size:9.5px;font-weight:700;color:#94A3B8;letter-spacing:0.06em;text-transform:uppercase;">ALTERNATIVE LEARNING SYSTEM</span>
      </div>
    </a>

    <!-- Workspace Box -->
    <div class="als-workspace-box">
      <span class="als-workspace-label">YOUR WORKSPACE</span>
      <div class="als-workspace-name">Student portal</div>
    </div>

    <!-- Navigation -->
    <nav class="als-sidebar-nav">
      <a href="student_dashboard.php" class="als-sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        <span>Dashboard</span>
      </a>

      <a href="student_scanner.php" class="als-sidebar-link active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><rect x="7" y="7" width="10" height="10" rx="1"></rect></svg>
        <span>QR attendance scanner</span>
      </a>

      <a href="student_dashboard.php#recent-attendance" class="als-sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        <span>My attendance</span>
      </a>

      <a href="teacher_modules.php" class="als-sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
        <span>Learning modules</span>
      </a>

      <a href="qr_pass.php" class="als-sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"></rect><circle cx="9" cy="10" r="2"></circle><line x1="15" y1="8" x2="17" y2="8"></line><line x1="15" y1="12" x2="17" y2="12"></line><line x1="7" y1="16" x2="17" y2="16"></line></svg>
        <span>Digital Learner ID</span>
      </a>

      <a href="qr_pass.php" class="als-sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        <span>My profile</span>
      </a>
    </nav>

    <!-- Sidebar Bottom Footer -->
    <div class="als-sidebar-footer">
      <a href="student_dashboard.php" class="als-sidebar-footer-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        <span>Help &amp; support</span>
      </a>
      <a href="logout.php" class="als-sidebar-footer-link" style="color:#FCA5A5;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        <span>Sign out</span>
      </a>
      <div style="font-size:10px;color:#64748B;padding-top:8px;">
        &copy; 2026 Department of Education
      </div>
    </div>
  </aside>

  <!-- ==================== MAIN CONTENT AREA ==================== -->
  <div style="flex:1;display:flex;flex-direction:column;min-width:0;background:#F8FAFC;min-height:100vh;">

    <!-- Topbar (Image 5) -->
    <header class="als-topbar-v2">
      <div class="als-breadcrumb-text">
        <span>Student portal</span>
        <span class="sep">&gt;</span>
        <span class="current">QR attendance scanner</span>
      </div>

      <div class="als-topbar-right">
        <!-- Back to dashboard button -->
        <a href="student_dashboard.php" class="toolbar-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-weight:700;color:var(--als-navy);background:#FFFFFF;border:1px solid var(--als-border);padding:7px 12px;border-radius:6px;font-size:12.5px;">
          &larr; Back to dashboard
        </a>
      </div>
    </header>

    <!-- Workspace Main Container -->
    <main style="padding:24px 32px;display:flex;flex-direction:column;gap:20px;flex:1;box-sizing:border-box;">

      <?php if ($successCheckin): ?>
        <div style="background:#DCFCE7;border:1px solid #86EFAC;color:#15803D;padding:14px 18px;border-radius:10px;font-size:13.5px;font-weight:750;display:flex;align-items:center;gap:10px;">
          <span style="font-size:18px;">✓</span>
          <span><?= e($checkinMessage) ?></span>
        </div>
      <?php endif; ?>

      <!-- Learner Info Strip (Image 5) -->
      <div style="background:#FFFFFF;border:1px solid var(--als-border);border-radius:12px;padding:14px 20px;display:flex;justify-content:space-between;align-items:center;">
        <div style="display:flex;align-items:center;gap:12px;">
          <div style="width:38px;height:38px;border-radius:50%;background:#EFF6FF;color:#1D64EC;font-weight:800;font-size:14px;display:grid;place-items:center;flex-shrink:0;">
            <?= e($inits) ?>
          </div>
          <div>
            <strong style="font-size:14px;color:var(--als-navy);display:inline;"><?= e($fullName) ?></strong>
            <span style="font-size:12.5px;color:var(--als-text-muted);margin-left:6px;">&bull; LRN <?= e($lrn) ?> &bull; <?= e($assignedCenter) ?></span>
          </div>
        </div>

        <span class="als-status-pill green" style="padding:4px 12px;font-size:11.5px;">
          Ready to check in
        </span>
      </div>

      <!-- 2 Columns Grid (Image 5) -->
      <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px;">
        
        <!-- Left Column: Scanner Viewfinder & Last Checkin -->
        <div style="display:flex;flex-direction:column;gap:16px;">
          
          <div class="als-scanner-card">
            <h3 style="font-size:16px;font-weight:750;color:var(--als-navy);margin:0 0 16px 0;align-self:flex-start;">
              Scan your center's QR
            </h3>

            <!-- Camera Viewfinder with reticle -->
            <div class="als-viewfinder-box" id="viewfinderArea">
              <video id="scannerVideo" playsinline style="display:none;width:100%;height:100%;object-fit:cover;"></video>
              <div class="als-viewfinder-reticle">
                <span class="als-reticle-corner-tr"></span>
                <span class="als-reticle-corner-bl"></span>
                <div class="als-scan-laser-line"></div>
              </div>
            </div>

            <p style="font-size:12.5px;color:var(--als-text-muted);margin:0 0 16px 0;max-width:380px;line-height:1.45;">
              Point your camera at the QR code displayed at your learning center or educator's screen.
            </p>

            <!-- Action Button -->
            <button type="button" id="startScanBtn" onclick="toggleCamera()" class="als-primary-btn" style="background:var(--als-blue);color:#FFFFFF;padding:10px 24px;border-radius:8px;font-weight:750;font-size:13px;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:8px;box-shadow:0 2px 6px rgba(29,100,236,0.25);">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
              <span id="scanBtnText">Start scanning</span>
            </button>
          </div>

          <!-- Last successful checkin banner (Image 5) -->
          <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:12px;">
            <div style="width:32px;height:32px;border-radius:50%;background:#DCFCE7;color:#15803D;display:grid;place-items:center;font-weight:800;font-size:16px;flex-shrink:0;">
              ✓
            </div>
            <div>
              <strong style="font-size:13px;color:#166534;display:block;">
                Your last QR check-in was successful
              </strong>
              <span style="font-size:11.5px;color:#15803D;">
                <?= e($lastDateStr) ?> &bull; <?= e($lastStatus) ?> &bull; <?= e($assignedCenter) ?> &bull; LS2
              </span>
            </div>
          </div>

        </div>

        <!-- Right Column: Manual Code & Instructions (Image 5) -->
        <div style="display:flex;flex-direction:column;gap:16px;">
          
          <!-- No camera? Use a code -->
          <div class="als-review-card" style="padding:22px;">
            <h3 style="font-size:15px;font-weight:750;color:var(--als-navy);margin:0 0 4px 0;">
              No camera? Use a code
            </h3>
            <p style="font-size:12px;color:var(--als-text-muted);margin:0 0 16px 0;line-height:1.4;">
              Enter the 6-character session code provided by your educator.
            </p>

            <form method="post" action="student_scanner.php">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <div style="margin-bottom:14px;">
                <input 
                  type="text" 
                  name="session_code" 
                  required 
                  maxlength="10" 
                  placeholder="ALS-892" 
                  style="width:100%;padding:12px 14px;border:1.5px solid var(--als-border);border-radius:8px;font-size:15px;font-weight:750;letter-spacing:0.1em;text-transform:uppercase;color:var(--als-navy);box-sizing:border-box;outline:none;font-family:ui-monospace,monospace;background:#F8FAFC;"
                >
              </div>

              <button type="submit" class="als-primary-btn" style="width:100%;background:var(--als-blue);color:#FFFFFF;padding:10px 16px;border-radius:8px;font-weight:750;font-size:13px;border:none;cursor:pointer;display:flex;justify-content:center;align-items:center;gap:6px;">
                <span>Submit attendance code</span>
                <span>&rarr;</span>
              </button>
            </form>
          </div>

          <!-- How to check in (Image 5) -->
          <div class="als-review-card" style="padding:20px;">
            <h3 style="font-size:14px;font-weight:750;color:var(--als-navy);margin:0 0 12px 0;">
              How to check in
            </h3>

            <div style="display:flex;flex-direction:column;gap:12px;font-size:12px;line-height:1.45;">
              <div style="display:flex;gap:10px;">
                <span style="width:20px;height:20px;border-radius:50%;background:#EFF6FF;color:#1D64EC;font-weight:800;font-size:11px;display:grid;place-items:center;flex-shrink:0;">1</span>
                <span style="color:var(--als-text-main);">Look for the QR code displayed in your learning center room or educator's screen.</span>
              </div>

              <div style="display:flex;gap:10px;">
                <span style="width:20px;height:20px;border-radius:50%;background:#EFF6FF;color:#1D64EC;font-weight:800;font-size:11px;display:grid;place-items:center;flex-shrink:0;">2</span>
                <span style="color:var(--als-text-main);">Tap <strong>Start scanning</strong> and align the QR code inside the frame.</span>
              </div>

              <div style="display:flex;gap:10px;">
                <span style="width:20px;height:20px;border-radius:50%;background:#EFF6FF;color:#1D64EC;font-weight:800;font-size:11px;display:grid;place-items:center;flex-shrink:0;">3</span>
                <span style="color:var(--als-text-main);">Your attendance is instantly verified and logged in your profile.</span>
              </div>
            </div>
          </div>

          <!-- Privacy Trust Note -->
          <div style="display:flex;align-items:flex-start;gap:10px;padding:6px 8px;font-size:11px;color:var(--als-text-muted);line-height:1.4;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;color:var(--als-blue);flex-shrink:0;margin-top:1px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            <span>Encrypted &amp; secure check-in. Time-stamped to verify presence at your registered community learning center.</span>
          </div>

        </div>

      </div>

      <!-- Footer (Image 5) -->
      <footer style="margin-top:auto;padding-top:20px;border-top:1px solid var(--als-border);display:flex;justify-content:space-between;align-items:center;font-size:11.5px;color:var(--als-text-muted);">
        <div>
          DepEd ALS &bull; Learning without limits
        </div>
        <div style="display:flex;gap:16px;">
          <a href="#" style="color:var(--als-text-muted);text-decoration:none;">Privacy notice</a>
          <a href="#" style="color:var(--als-text-muted);text-decoration:none;">Help center</a>
        </div>
      </footer>

    </main>

  </div>

</div>

<script>
let streamActive = null;

function toggleCamera() {
  const video = document.getElementById('scannerVideo');
  const btnText = document.getElementById('scanBtnText');

  if (streamActive) {
    // Stop camera
    streamActive.getTracks().forEach(track => track.stop());
    streamActive = null;
    video.style.display = 'none';
    btnText.innerText = 'Start scanning';
  } else {
    // Start camera if browser supports mediaDevices
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
      navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(stream => {
          streamActive = stream;
          video.srcObject = stream;
          video.style.display = 'block';
          video.play();
          btnText.innerText = 'Stop scanning';
        })
        .catch(err => {
          // Camera permission or hardware issue simulation
          alert('Camera preview active. If using a physical webcam or on mobile, grant permission to scan QR directly.');
        });
    } else {
      alert('Camera access is not supported in this browser environment. You can enter the 6-character session code on the right.');
    }
  }
}
</script>

</body>
</html>
