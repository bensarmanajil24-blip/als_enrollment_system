<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$errors = [];
$old = [
    'full_name' => '',
    'birthdate' => '',
    'contact_number' => '',
    'address' => '',
    'learner_status' => 'Junior High School',
    'schedule' => 'Weekend Classes'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old['full_name'] = trim((string)($_POST['full_name'] ?? ''));
    $old['birthdate'] = trim((string)($_POST['birthdate'] ?? ''));
    $old['contact_number'] = trim((string)($_POST['contact_number'] ?? ''));
    $old['address'] = trim((string)($_POST['address'] ?? ''));
    $old['learner_status'] = trim((string)($_POST['learner_status'] ?? 'Junior High School'));
    $old['schedule'] = trim((string)($_POST['schedule'] ?? 'Weekend Classes'));

    if (empty($old['full_name'])) {
        $errors[] = 'Ilagay ang iyong buong pangalan.';
    }
    if (empty($old['birthdate'])) {
        $errors[] = 'Piliin ang petsa ng kapanganakan.';
    }
    if (empty($old['contact_number'])) {
        $errors[] = 'Ilagay ang iyong numero ng telepono.';
    }
    if (empty($old['address'])) {
        $errors[] = 'Ilagay ang iyong tirahan, barangay at lungsod.';
    }

    if (empty($errors)) {
        // Parse names from full_name (e.g. "Juan Santos Dela Cruz" or "Dela Cruz, Juan Santos")
        $nameParts = preg_split('/\s+/', $old['full_name']);
        $firstName = $nameParts[0] ?? 'Learner';
        $lastName = count($nameParts) > 1 ? end($nameParts) : 'Santos';
        $middleName = count($nameParts) > 2 ? $nameParts[1] : '';

        // Generate DepEd QR Pass tracking reference: ALS-2025-MNL-XXXX
        $randomCode = rand(1000, 9999);
        $studentNumber = "ALS-2025-MNL-{$randomCode}";

        // Ensure unique tracking reference
        $stmtCheck = $pdo->prepare('SELECT id FROM students WHERE student_number = ?');
        $stmtCheck->execute([$studentNumber]);
        if ($stmtCheck->fetch()) {
            $studentNumber = "ALS-2025-MNL-" . rand(1000, 9999);
        }

        $formattedContact = '+63 ' . ltrim($old['contact_number'], '0');

        $defPassword = password_hash('student2026', PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO students
                (student_number, first_name, middle_name, last_name, birthdate, gender, address, contact_number, password, learner_status, schedule, assigned_center)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $studentNumber,
                $firstName,
                $middleName,
                $lastName,
                $old['birthdate'],
                'Prefer not to say',
                $old['address'],
                $formattedContact,
                $defPassword,
                $old['learner_status'] === 'Elementary' ? 'Elementary' : 'Junior High School',
                $old['schedule'],
                'San Nicolas Community Learning Center'
            ]);
        } catch (PDOException $e) {
            // Fallback for standard columns
            $stmt = $pdo->prepare(
                'INSERT INTO students
                (student_number, first_name, middle_name, last_name, birthdate, gender, address, contact_number, password, learner_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $studentNumber,
                $firstName,
                $middleName,
                $lastName,
                $old['birthdate'],
                'Prefer not to say',
                $old['address'],
                $formattedContact,
                $defPassword,
                $old['learner_status'] === 'Elementary' ? 'Elementary' : 'Junior High School',
            ]);
        }

        header("Location: qr_pass.php?ref=" . urlencode($studentNumber));
        exit;
    }
}
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>DEPED ALS • Student Enrollment</title>
  <link rel="stylesheet" href="assets/mobile_app.css">
</head>
<body class="deped-mobile-body">

<div class="deped-mobile-wrap">

  <!-- Top App Bar -->
  <header class="deped-app-header">
    <a href="index.php" class="header-left-brand">
      <div class="header-brand-logo">ALS</div>
      <div style="font-size:13px;font-weight:750;">ALS Connect</div>
    </a>

    <div class="header-center-title">
      <div class="header-dept-tag">DEPED ALS</div>
      <div class="header-screen-title">Student Enrollment</div>
    </div>

    <div class="header-right-actions">
      <a href="index.php#faqs" class="header-icon-btn" title="Tulong">?</a>
      <a href="admin_login.php" class="header-icon-btn" title="Admin">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      </a>
    </div>
  </header>

  <!-- Main Content Area -->
  <main class="deped-content">

    <!-- Top Announcement Banner -->
    <div class="deped-top-banner blue">
      <div class="banner-badge-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      </div>
      <div>
        <span class="banner-pill-tag">LIBRE • OPISYAL • MABILIS</span>
        <h2 class="banner-title">ALS Online Rehistro</h2>
        <p class="banner-sub">Bukas para sa lahat ng gustong magpatuloy ng pag-aaral. Magpatala sa loob ng 2 minuto.</p>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;">
        <strong>Pakitama ang sumusunod:</strong>
        <ul style="margin:4px 0 0 16px;">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="student_enroll.php" id="depedEnrollForm">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

      <!-- Field 1: Buong Pangalan -->
      <div class="field-group">
        <div class="field-label-row">
          <label class="field-label" for="fullName">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            Buong Pangalan <span style="color:var(--deped-red);">*</span>
          </label>
          <span class="field-sub-label">Full Name</span>
        </div>
        <input 
          type="text" 
          id="fullName" 
          name="full_name" 
          class="deped-input" 
          required 
          placeholder="Hal. Juan Santos Dela Cruz"
          value="<?= e($old['full_name']) ?>"
        >
        <div class="field-helper">ℹ Isulat ang Apelyido, Unang Pangalan, at Gitnang Pangalan</div>
      </div>

      <!-- Field 2: Petsa ng Kapanganakan -->
      <div class="field-group">
        <div class="field-label-row">
          <label class="field-label" for="birthdate">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            Petsa ng Kapanganakan <span style="color:var(--deped-red);">*</span>
          </label>
          <span class="field-sub-label">Date of Birth</span>
        </div>
        <input 
          type="date" 
          id="birthdate" 
          name="birthdate" 
          class="deped-input" 
          required 
          value="<?= e($old['birthdate']) ?>"
          max="<?= date('Y-m-d') ?>"
        >
        <div class="field-helper">Tinatanggap ang edad 12 pataas para sa ALS programs.</div>
      </div>

      <!-- Field 3: Numero ng Telepono -->
      <div class="field-group">
        <div class="field-label-row">
          <label class="field-label" for="contactNumber">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
            Numero ng Telepono <span style="color:var(--deped-red);">*</span>
          </label>
          <span class="field-sub-label">Mobile Contact</span>
        </div>
        <div class="phone-input-wrap">
          <div class="phone-flag-box">
            <span>🇵🇭</span> +63
          </div>
          <input 
            type="tel" 
            id="contactNumber" 
            name="contact_number" 
            class="deped-input" 
            required 
            placeholder="9171234567" 
            maxlength="10"
            value="<?= e($old['contact_number']) ?>"
          >
        </div>
        <div class="field-helper">Dito ipapadala ang SMS paalala ukol sa orientation at klase.</div>
      </div>

      <!-- Field 4: Tirahan, Barangay at Lungsod -->
      <div class="field-group">
        <div class="field-label-row">
          <label class="field-label" for="address">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
            Tirahan, Barangay at Lungsod <span style="color:var(--deped-red);">*</span>
          </label>
          <span class="field-sub-label">Address</span>
        </div>
        <input 
          type="text" 
          id="address" 
          name="address" 
          class="deped-input" 
          required 
          placeholder="Hal. Brgy. Commonwealth, Quezon City"
          value="<?= e($old['address']) ?>"
        >
        <div class="field-helper">Para sa pagtatalaga sa pinakamalapit na Community Learning Center (CLC).</div>
      </div>

      <!-- Field 5: Piliin ang Antas ng Pag-aaral -->
      <div class="field-group">
        <div class="field-label-row">
          <label class="field-label">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
            Piliin ang Antas ng Pag-aaral <span style="color:var(--deped-red);">*</span>
          </label>
          <span class="field-sub-label">Program</span>
        </div>

        <input type="hidden" name="learner_status" id="programInput" value="<?= e($old['learner_status']) ?>">

        <!-- Option 1: Elementary -->
        <div class="radio-program-card <?= $old['learner_status'] === 'Elementary' ? 'active' : '' ?>" onclick="pickProgram('Elementary')">
          <div class="program-radio-dot"></div>
          <div class="program-card-info">
            <h4>Basic Literacy / Elementary Level</h4>
            <p>Pagbasa, pagsulat, matematika, at pangunahing kaalaman para sa elementarya.</p>
            <span class="program-pill blue">Grade 1 to 6 Equivalence</span>
          </div>
        </div>

        <!-- Option 2: Junior High School -->
        <div class="radio-program-card <?= $old['learner_status'] === 'Junior High School' ? 'active' : '' ?>" onclick="pickProgram('Junior High School')">
          <div class="program-radio-dot"></div>
          <div class="program-card-info">
            <h4>Junior High School (JHS) Level</h4>
            <p>Para sa mga nakapagtapos ng elementarya at nais kumuha ng Junior High certificate.</p>
            <span class="program-pill green">Grade 7 to 10 Equivalence</span>
          </div>
        </div>
      </div>

      <!-- Field 6: Gustong Iskedyul ng Klase -->
      <div class="field-group">
        <div class="field-label-row">
          <label class="field-label">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            Gustong Iskedyul ng Klase <span style="color:var(--deped-red);">*</span>
          </label>
          <span class="field-sub-label">Schedule</span>
        </div>

        <input type="hidden" name="schedule" id="scheduleInput" value="<?= e($old['schedule']) ?>">

        <div class="schedule-toggle-row">
          <button type="button" class="schedule-toggle-btn <?= $old['schedule'] === 'Weekend Classes' ? 'active' : '' ?>" onclick="pickSchedule('Weekend Classes')">
            <span>📇 Sabado / Linggo</span>
            <span class="schedule-toggle-sub">Weekend Classes</span>
          </button>
          <button type="button" class="schedule-toggle-btn <?= $old['schedule'] === 'Weekday Sessions' ? 'active' : '' ?>" onclick="pickSchedule('Weekday Sessions')">
            <span>☀️ Lunes - Biyernes</span>
            <span class="schedule-toggle-sub">Weekday Sessions</span>
          </button>
        </div>
      </div>

      <!-- 100% Libre at Walang Bayad Callout Box -->
      <div class="deped-top-banner green" style="margin-top:20px;margin-bottom:24px;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--deped-green);flex-shrink:0;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
        <div style="font-size:12.5px;color:var(--deped-navy);">
          <strong style="color:var(--deped-green);display:block;margin-bottom:2px;">100% Libre at Walang Bayad</strong>
          Pagkatapos mag-submit, makakakuha ka agad ng opisyal na QR Pass confirmation stub na ipapakita sa iyong guro o ALS Teacher/Facilitator.
        </div>
      </div>

      <!-- Submit CTA Button -->
      <button type="submit" class="btn-deped-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        I-sumite at Kunin ang QR Pass &rarr;
      </button>

    </form>

  </main>

  <!-- Bottom Navigation Bar (4 Tabs) -->
  <nav class="deped-bottom-navbar">
    <a href="index.php" class="deped-nav-tab">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
      <span>Hub</span>
    </a>
    <a href="student_enroll.php" class="deped-nav-tab active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
      <span>Enroll</span>
    </a>
    <a href="qr_pass.php" class="deped-nav-tab">
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
function pickProgram(prog) {
  document.getElementById('programInput').value = prog;
  document.querySelectorAll('.radio-program-card').forEach(c => c.classList.remove('active'));
  event.currentTarget.classList.add('active');
}

function pickSchedule(sched) {
  document.getElementById('scheduleInput').value = sched;
  document.querySelectorAll('.schedule-toggle-btn').forEach(b => b.classList.remove('active'));
  event.currentTarget.classList.add('active');
}
</script>

</body>
</html>

