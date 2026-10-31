<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Multi-step session storage
if (!isset($_SESSION['enrollment_data'])) {
    $_SESSION['enrollment_data'] = [
        'first_name' => 'Maria Isabel',
        'middle_name' => 'Santos',
        'last_name' => 'Reyes',
        'birthdate' => '2002-03-14',
        'gender' => 'Female',
        'address' => 'Blk 6 Lot 12, Brgy. Pag-asa, Quezon City, Metro Manila',
        'contact_number' => '0912 345 6789',
        'email' => 'maria.reyes@example.com',
        'learner_status' => $_GET['program'] ?? 'Junior High School',
        'last_school' => 'Pag-asa Community School',
        'last_grade' => 'Grade 6',
        'last_year' => '2014 - 2015',
        'goal' => 'Nais kong makatapos at makapaghanda para sa mas magandang trabaho.'
    ];
}

$step = (int)($_GET['step'] ?? 1);
if ($step < 1 || $step > 3) $step = 1;

$errors = [];

// Handle Step Transitions via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $currentStep = (int)($_POST['current_step'] ?? 1);

    if ($currentStep === 1) {
        $_SESSION['enrollment_data']['first_name'] = trim((string)($_POST['first_name'] ?? ''));
        $_SESSION['enrollment_data']['middle_name'] = trim((string)($_POST['middle_name'] ?? ''));
        $_SESSION['enrollment_data']['last_name'] = trim((string)($_POST['last_name'] ?? ''));
        $_SESSION['enrollment_data']['birthdate'] = trim((string)($_POST['birthdate'] ?? ''));
        $_SESSION['enrollment_data']['gender'] = trim((string)($_POST['gender'] ?? ''));
        $_SESSION['enrollment_data']['address'] = trim((string)($_POST['address'] ?? ''));
        $_SESSION['enrollment_data']['contact_number'] = trim((string)($_POST['contact_number'] ?? ''));
        $_SESSION['enrollment_data']['email'] = trim((string)($_POST['email'] ?? ''));

        if (empty($_SESSION['enrollment_data']['first_name'])) $errors[] = 'Ilagay ang iyong unang pangalan.';
        if (empty($_SESSION['enrollment_data']['last_name'])) $errors[] = 'Ilagay ang iyong apelyido.';
        if (empty($_SESSION['enrollment_data']['birthdate'])) $errors[] = 'Piliin ang iyong petsa ng kapanganakan.';
        if (empty($_SESSION['enrollment_data']['gender'])) $errors[] = 'Pumili ng kasarian.';
        if (empty($_SESSION['enrollment_data']['address'])) $errors[] = 'Ilagay ang iyong kumpletong tirahan.';
        if (empty($_SESSION['enrollment_data']['contact_number'])) $errors[] = 'Ilagay ang iyong contact number.';

        if (empty($errors)) {
            header('Location: enroll.php?step=2');
            exit;
        }
    } elseif ($currentStep === 2) {
        if (isset($_POST['go_back'])) {
            header('Location: enroll.php?step=1');
            exit;
        }

        $_SESSION['enrollment_data']['learner_status'] = trim((string)($_POST['learner_status'] ?? 'Junior High School'));
        $_SESSION['enrollment_data']['last_school'] = trim((string)($_POST['last_school'] ?? ''));
        $_SESSION['enrollment_data']['last_grade'] = trim((string)($_POST['last_grade'] ?? ''));
        $_SESSION['enrollment_data']['last_year'] = trim((string)($_POST['last_year'] ?? ''));
        $_SESSION['enrollment_data']['goal'] = trim((string)($_POST['goal'] ?? ''));

        if (!in_array($_SESSION['enrollment_data']['learner_status'], ['Elementary', 'Junior High School'], true)) {
            $errors[] = 'Pumili ng antas na nais ipagpatuloy (Elementary o Junior High School).';
        }
        if (empty($_SESSION['enrollment_data']['last_school'])) {
            $errors[] = 'Ilagay ang huling paaralang pinasukan.';
        }
        if (empty($_SESSION['enrollment_data']['last_grade'])) {
            $errors[] = 'Piliin ang huling baitang na natapos.';
        }

        if (empty($errors)) {
            header('Location: enroll.php?step=3');
            exit;
        }
    } elseif ($currentStep === 3) {
        if (isset($_POST['go_back'])) {
            header('Location: enroll.php?step=2');
            exit;
        }

        // Final Submission to Database
        $d = $_SESSION['enrollment_data'];

        // Generate student tracking number: ALS-YYYY-XXXX
        do {
            $studentNumber = 'ALS-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $check = $pdo->prepare('SELECT id FROM students WHERE student_number = ?');
            $check->execute([$studentNumber]);
        } while ($check->fetch());

        $defPassword = password_hash('student2026', PASSWORD_BCRYPT);

        // Check if extended columns exist in students table
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO students
                (student_number, first_name, middle_name, last_name, birthdate, gender, address, contact_number, email, password, learner_status, last_school, last_grade, last_year, goal)
                VALUES (:student_number, :first_name, :middle_name, :last_name, :birthdate, :gender, :address, :contact_number, :email, :password, :learner_status, :last_school, :last_grade, :last_year, :goal)'
            );
            $stmt->execute([
                ':student_number' => $studentNumber,
                ':first_name' => $d['first_name'],
                ':middle_name' => $d['middle_name'] ?: null,
                ':last_name' => $d['last_name'],
                ':birthdate' => $d['birthdate'],
                ':gender' => $d['gender'],
                ':address' => $d['address'],
                ':contact_number' => $d['contact_number'],
                ':email' => $d['email'] ?: null,
                ':password' => $defPassword,
                ':learner_status' => $d['learner_status'],
                ':last_school' => $d['last_school'] ?: null,
                ':last_grade' => $d['last_grade'] ?: null,
                ':last_year' => $d['last_year'] ?: null,
                ':goal' => $d['goal'] ?: null,
            ]);
        } catch (PDOException $e) {
            // Fallback for standard columns if table was created with base schema
            $stmt = $pdo->prepare(
                'INSERT INTO students
                (student_number, first_name, middle_name, last_name, birthdate, gender, address, contact_number, password, learner_status)
                VALUES (:student_number, :first_name, :middle_name, :last_name, :birthdate, :gender, :address, :contact_number, :password, :learner_status)'
            );
            $stmt->execute([
                ':student_number' => $studentNumber,
                ':first_name' => $d['first_name'],
                ':middle_name' => $d['middle_name'] ?: null,
                ':last_name' => $d['last_name'],
                ':birthdate' => $d['birthdate'],
                ':gender' => $d['gender'],
                ':address' => $d['address'],
                ':contact_number' => $d['contact_number'],
                ':password' => $defPassword,
                ':learner_status' => $d['learner_status'],
            ]);
        }

        $_SESSION['registration_number'] = $studentNumber;
        unset($_SESSION['enrollment_data']);
        header('Location: success.php');
        exit;
    }
}

$formData = $_SESSION['enrollment_data'];
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Simulan ang bagong kabanata • ALS Connect</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    /* Multi-step Layout Grid */
    .wizard-layout {
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 36px;
      align-items: flex-start;
      margin-top: 24px;
    }

    /* Left Stepper Sidebar */
    .stepper-panel {
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    .stepper-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .stepper-item {
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }

    .step-badge {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      font-size: 13px;
      font-weight: 750;
      flex-shrink: 0;
      border: 1px solid var(--color-border);
      background: #ffffff;
      color: var(--color-slate);
    }

    .stepper-item.active .step-badge {
      background: var(--color-trust-blue);
      color: #ffffff;
      border-color: var(--color-trust-blue);
    }

    .stepper-item.completed .step-badge {
      background: var(--color-fresh-green-light);
      color: var(--color-fresh-green);
      border-color: #B8DEC7;
    }

    .step-text-title {
      font-size: 13.5px;
      font-weight: 700;
      color: var(--color-deep-navy);
      line-height: 1.3;
      margin-bottom: 2px;
    }

    .stepper-item.active .step-text-title {
      color: var(--color-trust-blue);
    }

    .step-text-status {
      font-size: 11.5px;
      color: var(--color-slate);
    }

    /* Program Selection Cards in Step 2 */
    .program-select-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 16px;
      margin: 12px 0 24px;
    }

    .program-select-card {
      background: #ffffff;
      border: 1.5px solid var(--color-border);
      border-radius: var(--radius-control);
      padding: 20px;
      cursor: pointer;
      position: relative;
      transition: all 0.15s ease;
      display: flex;
      flex-direction: column;
    }

    .program-select-card:hover {
      border-color: var(--color-trust-blue);
    }

    .program-select-card.selected {
      border-color: var(--color-trust-blue);
      background: #F8FAFD;
      box-shadow: 0 0 0 1px var(--color-trust-blue);
    }

    .select-card-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 12px;
    }

    .select-card-icon {
      width: 28px;
      height: 28px;
      color: var(--color-trust-blue);
    }

    .selected-tag {
      font-size: 11px;
      font-weight: 700;
      color: var(--color-trust-blue);
      display: none;
    }

    .program-select-card.selected .selected-tag {
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    @media (max-width: 860px) {
      .wizard-layout {
        grid-template-columns: 1fr;
      }
      .stepper-list {
        flex-direction: row;
        overflow-x: auto;
        padding-bottom: 8px;
      }
      .program-select-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

<header class="site-header">
  <div class="header-inner">
    <a class="site-brand" href="index.php">
      <div class="brand-badge">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
      </div>
      <div>
        <div class="brand-name">ALS Connect</div>
        <span class="brand-tag">PAGKATUTO PARA SA LAHAT</span>
      </div>
    </a>

    <a href="index.php#faqs" class="nav-link" style="display:flex;align-items:center;gap:6px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
      Kailangan ng tulong?
    </a>
  </div>
</header>

<main class="container">

  <!-- Back to Home Link -->
  <div style="margin-bottom:14px;">
    <a href="index.php" style="color:var(--color-slate);text-decoration:none;font-size:13.5px;display:inline-flex;align-items:center;gap:6px;font-weight:600;">
      &larr; Bumalik sa Home
    </a>
  </div>

  <!-- Heading -->
  <div style="margin-bottom:28px;">
    <h1 style="font-size:clamp(28px, 4vw, 36px);letter-spacing:-0.02em;margin-bottom:6px;">Simulan ang bagong kabanata.</h1>
    <p class="muted-text" style="font-size:15px;">Mag-apply sa ALS. Gagabayan ka namin, isang hakbang sa bawat pagkakataon.</p>
  </div>

  <div class="wizard-layout">

    <!-- Stepper Sidebar -->
    <aside class="stepper-panel">
      <div>
        <div style="font-size:11px;font-weight:750;letter-spacing:0.08em;color:var(--color-slate);text-transform:uppercase;margin-bottom:14px;">
          ANG IYONG APLIKASYON
        </div>

        <div class="stepper-list">
          <!-- Step 1 -->
          <div class="stepper-item <?= $step === 1 ? 'active' : ($step > 1 ? 'completed' : '') ?>">
            <div class="step-badge">
              <?= $step > 1 ? '✓' : '1' ?>
            </div>
            <div>
              <div class="step-text-title">Personal na Impormasyon</div>
              <div class="step-text-status"><?= $step === 1 ? 'Kasalukuyan' : ($step > 1 ? 'Natapos na' : 'Susunod') ?></div>
            </div>
          </div>

          <!-- Step 2 -->
          <div class="stepper-item <?= $step === 2 ? 'active' : ($step > 2 ? 'completed' : '') ?>">
            <div class="step-badge">
              <?= $step > 2 ? '✓' : '2' ?>
            </div>
            <div>
              <div class="step-text-title">Pinag-aralan at Antas</div>
              <div class="step-text-status"><?= $step === 2 ? 'Kasalukuyan' : ($step > 2 ? 'Natapos na' : 'Susunod') ?></div>
            </div>
          </div>

          <!-- Step 3 -->
          <div class="stepper-item <?= $step === 3 ? 'active' : '' ?>">
            <div class="step-badge">3</div>
            <div>
              <div class="step-text-title">Dokumento at Pagsumite</div>
              <div class="step-text-status"><?= $step === 3 ? 'Kasalukuyan' : 'Susunod' ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Helper Notice Box -->
      <div class="card-panel" style="padding:18px;background:#F4F7FB;border-color:var(--color-border);margin-bottom:0;">
        <div style="display:flex;align-items:center;gap:8px;font-weight:700;font-size:13px;color:var(--color-deep-navy);margin-bottom:6px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--color-trust-blue);"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
          Kasama mo kami:
        </div>
        <p class="muted-text" style="font-size:12.5px;line-height:1.45;margin:0;">
          Hindi sigurado sa sagot? Maaaring humingi ng gabay sa lokal na ALS coordinator.
        </p>
      </div>
    </aside>

    <!-- Main Step Form Content -->
    <section>
      <?php if (!empty($errors)): ?>
        <div class="feedback-banner error" style="margin-bottom:20px;">
          <div>
            <strong>Pakisuri ang sumusunod:</strong>
            <ul style="margin:4px 0 0 16px;">
              <?php foreach ($errors as $e): ?>
                <li><?= e($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <form method="post" action="enroll.php?step=<?= $step ?>" class="card-panel" style="padding:36px;" id="enrollmentForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="current_step" value="<?= $step ?>">

        <!-- ================= STEP 1: Personal na Impormasyon (Image 4) ================= -->
        <?php if ($step === 1): ?>
          <h2 style="font-size:22px;margin-bottom:6px;">Personal na Impormasyon</h2>
          <p class="muted-text" style="margin-bottom:14px;font-size:13.5px;">
            Ipakilala ang iyong sarili. Gamitin ang impormasyong nasa iyong mga dokumento, kung mayroon. Ang may * ay kailangang sagutan. Ang may 'Opsyonal' ay puwedeng laktawan.
          </p>

          <div style="display:inline-block;background:#EBF3FC;color:var(--color-trust-blue);font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:var(--radius-pill);margin-bottom:24px;">
            HALIMBAWANG DATOS • Kathang-isip ang aplikanteng ipinapakita.
          </div>

          <div class="form-grid">
            <!-- First Name -->
            <div class="form-group">
              <label class="form-label" for="firstName">Unang pangalan <span class="required-mark">*</span></label>
              <input type="text" id="firstName" name="first_name" class="form-input" required value="<?= e($formData['first_name']) ?>" placeholder="hal. Maria Isabel">
            </div>

            <!-- Last Name -->
            <div class="form-group">
              <label class="form-label" for="lastName">Apelyido <span class="required-mark">*</span></label>
              <input type="text" id="lastName" name="last_name" class="form-input" required value="<?= e($formData['last_name']) ?>" placeholder="hal. Reyes">
            </div>

            <!-- Middle Name -->
            <div class="form-group full">
              <label class="form-label" for="middleName">Gitnang pangalan <span class="muted-text" style="font-weight:400;">(Opsyonal)</span></label>
              <input type="text" id="middleName" name="middle_name" class="form-input" value="<?= e($formData['middle_name']) ?>" placeholder="hal. Santos">
              <div class="input-hint">Laktawan kung walang gitnang pangalan.</div>
            </div>

            <!-- Birthdate -->
            <div class="form-group">
              <label class="form-label" for="birthdate">Petsa ng kapanganakan <span class="required-mark">*</span></label>
              <input type="date" id="birthdate" name="birthdate" class="form-input" required value="<?= e($formData['birthdate']) ?>">
              <div class="input-hint">Araw / Buwan / Taon</div>
            </div>

            <!-- Gender -->
            <div class="form-group">
              <label class="form-label" for="gender">Kasarian <span class="required-mark">*</span></label>
              <select id="gender" name="gender" class="form-select" required>
                <option value="">Pumili ng kasarian</option>
                <option value="Female" <?= $formData['gender'] === 'Female' ? 'selected' : '' ?>>Babae</option>
                <option value="Male" <?= $formData['gender'] === 'Male' ? 'selected' : '' ?>>Lalaki</option>
                <option value="Prefer not to say" <?= $formData['gender'] === 'Prefer not to say' ? 'selected' : '' ?>>Mas pinipiling huwag sabihin</option>
              </select>
              <div class="input-hint">Para sa opisyal na rekord.</div>
            </div>

            <!-- Address -->
            <div class="form-group full">
              <label class="form-label" for="address">Kasalukuyang tirahan <span class="required-mark">*</span></label>
              <textarea id="address" name="address" class="form-textarea" rows="2" required placeholder="Blk, Lot, Barangay, Lungsod o Bayan, at Lalawigan"><?= e($formData['address']) ?></textarea>
              <div class="input-hint">Kalye / Sitio, Barangay, Lungsod o Bayan, at Lalawigan.</div>
            </div>

            <!-- Contact Number -->
            <div class="form-group">
              <label class="form-label" for="contactNumber">Contact number <span class="required-mark">*</span></label>
              <input type="tel" id="contactNumber" name="contact_number" class="form-input" required value="<?= e($formData['contact_number']) ?>" placeholder="0912 345 6789">
              <div class="input-hint">Halimbawang numero lamang; hindi aktibong kontak.</div>
            </div>

            <!-- Email Address -->
            <div class="form-group">
              <label class="form-label" for="email">Email address <span class="muted-text" style="font-weight:400;">(Opsyonal)</span></label>
              <input type="email" id="email" name="email" class="form-input" value="<?= e($formData['email']) ?>" placeholder="maria.reyes@example.com">
              <div class="input-hint">Kung mayroon; hindi pormal na ginagamit.</div>
            </div>
          </div>

          <!-- Info Callout -->
          <div class="feedback-banner info" style="margin-top:24px;margin-bottom:28px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            <div style="font-size:13px;color:var(--color-deep-navy);">
              <strong>Ingatan ang iyong personal na datos.</strong> Gagamitin ang contact details para sa aplikasyon at pakikipag-ugnayan ng coordinator. Huwag maglagay ng password o bank details.
            </div>
          </div>

          <div style="display:flex;justify-content:flex-end;">
            <button type="submit" class="btn btn-primary" style="min-width:180px;">
              Magpatuloy &rarr;
            </button>
          </div>

        <!-- ================= STEP 2: Pinag-aralan at Antas (Image 5) ================= -->
        <?php elseif ($step === 2): ?>
          <h2 style="font-size:22px;margin-bottom:6px;">Pinag-aralan at Antas</h2>
          <p class="muted-text" style="margin-bottom:20px;font-size:13.5px;">
            Ibahagi ang iyong huling pag-aaral para mas magabayan ka namin. Ang may * ay kailangang sagutan. Ang may 'Opsyonal' ay puwedeng laktawan.
          </p>

          <label class="form-label" style="margin-bottom:8px;">
            Anong antas ang nais mong ipagpatuloy? <span class="required-mark">*</span>
          </label>

          <!-- Hidden Input for Program Selection -->
          <input type="hidden" name="learner_status" id="learnerStatusInput" value="<?= e($formData['learner_status']) ?>">

          <!-- Visual Selectable Cards (Matching Image 5) -->
          <div class="program-select-grid">
            <!-- Card 1: Elementary -->
            <div class="program-select-card <?= $formData['learner_status'] === 'Elementary' ? 'selected' : '' ?>" onclick="selectProgram('Elementary')">
              <div class="select-card-top">
                <svg class="select-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                <span class="selected-tag">✓ Napili</span>
              </div>
              <strong style="font-size:15px;color:var(--color-deep-navy);margin-bottom:4px;">Elementary</strong>
              <span class="muted-text" style="font-size:12.5px;">Patatagin ang pundasyon ng iyong pagkatuto.</span>
            </div>

            <!-- Card 2: Junior High School -->
            <div class="program-select-card <?= $formData['learner_status'] === 'Junior High School' ? 'selected' : '' ?>" onclick="selectProgram('Junior High School')">
              <div class="select-card-top">
                <svg class="select-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
                <span class="selected-tag">✓ Napili</span>
              </div>
              <strong style="font-size:15px;color:var(--color-deep-navy);margin-bottom:4px;">Junior High School</strong>
              <span class="muted-text" style="font-size:12.5px;">Ipagpatuloy ang pag-aaral sa antas sekondarya.</span>
            </div>
          </div>

          <div class="form-grid">
            <!-- Last School -->
            <div class="form-group full">
              <label class="form-label" for="lastSchool">Huling paaralang pinasukan <span class="required-mark">*</span></label>
              <input type="text" id="lastSchool" name="last_school" class="form-input" required value="<?= e($formData['last_school']) ?>" placeholder="hal. Pag-asa Community School">
              <div class="input-hint">Halimbawang paaralan. Kung hindi maalala, ilagay ang "Hindi maalala".</div>
            </div>

            <!-- Last Grade -->
            <div class="form-group">
              <label class="form-label" for="lastGrade">Huling baitang na natapos <span class="required-mark">*</span></label>
              <select id="lastGrade" name="last_grade" class="form-select" required>
                <option value="">Pumili ng baitang</option>
                <?php foreach (['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'] as $g): ?>
                  <option value="<?= e($g) ?>" <?= $formData['last_grade'] === $g ? 'selected' : '' ?>><?= e($g) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Last Year -->
            <div class="form-group">
              <label class="form-label" for="lastYear">Huling taon ng pag-aaral <span class="muted-text" style="font-weight:400;">(Opsyonal)</span></label>
              <input type="text" id="lastYear" name="last_year" class="form-input" value="<?= e($formData['last_year']) ?>" placeholder="hal. 2014 - 2015">
            </div>

            <!-- Goal -->
            <div class="form-group full">
              <label class="form-label" for="goal">Layunin sa pagbabalik-aral <span class="muted-text" style="font-weight:400;">(Opsyonal)</span></label>
              <textarea id="goal" name="goal" class="form-textarea" rows="2" placeholder="hal. Nais kong makatapos at makapaghanda para sa mas magandang trabaho."><?= e($formData['goal']) ?></textarea>
            </div>
          </div>

          <!-- Info Callout -->
          <div class="feedback-banner info" style="margin-top:24px;margin-bottom:28px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            <div style="font-size:13px;color:var(--color-deep-navy);">
              <strong>Hindi pa sigurado sa antas? Ayos lang.</strong> Paunang pagpili lamang ito. Makukumpirma ng ALS coordinator ang angkop na programa batay sa iyong karanasan at assessment.
            </div>
          </div>

          <div style="display:flex;justify-content:space-between;align-items:center;">
            <button type="submit" name="go_back" value="1" class="btn btn-secondary" style="min-width:140px;">
              Bumalik
            </button>
            <button type="submit" class="btn btn-primary" style="min-width:180px;">
              Magpatuloy &rarr;
            </button>
          </div>

        <!-- ================= STEP 3: Dokumento at Pagsumite ================= -->
        <?php elseif ($step === 3): ?>
          <h2 style="font-size:22px;margin-bottom:6px;">Dokumento at Pagsumite</h2>
          <p class="muted-text" style="margin-bottom:24px;font-size:13.5px;">
            Maaari kang maglakip ng litrato o PDF ng iyong school card o ID (opsyonal). Pagkatapos, i-click ang submit para magkaroon ng opisyal na tracking number.
          </p>

          <!-- Summary Card -->
          <div style="background:#FAFBFD;border:1px solid var(--color-border);border-radius:12px;padding:20px;margin-bottom:24px;">
            <strong style="font-size:14px;color:var(--color-deep-navy);display:block;margin-bottom:12px;">Buod ng Aplikasyon:</strong>
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;font-size:13px;">
              <div><span class="muted-text">Pangalan:</span> <strong><?= e($formData['first_name'] . ' ' . $formData['last_name']) ?></strong></div>
              <div><span class="muted-text">Antas:</span> <strong><?= e($formData['learner_status']) ?></strong></div>
              <div><span class="muted-text">Contact:</span> <strong><?= e($formData['contact_number']) ?></strong></div>
              <div><span class="muted-text">Huling Paaralan:</span> <strong><?= e($formData['last_school'] ?: 'Hindi tinukoy') ?></strong></div>
            </div>
          </div>

          <!-- Document Upload Box -->
          <div class="form-group full" style="margin-bottom:24px;">
            <label class="form-label">
              Supporting document <span class="muted-text" style="font-weight:400;">(Opsyonal sa demo)</span>
            </label>
            <input type="file" id="supportingDoc" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
            
            <div class="file-dropzone" id="fileDropzone" tabindex="0" role="button">
              <svg class="file-dropzone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
              <div style="font-weight:600;color:var(--color-deep-navy);margin-bottom:4px;">Pumili ng file o kumuha ng larawan</div>
              <div class="muted-text">PDF, JPG o PNG • Hanggang 5 MB bawat file sa demo</div>
              <button type="button" class="btn btn-secondary" style="min-height:38px;padding:0 16px;margin-top:12px;font-size:13px;" onclick="document.getElementById('supportingDoc').click();">Pumili ng File</button>
            </div>

            <div id="attachedFileCard" class="attached-file-card" style="display:none;">
              <div class="attached-file-info">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--color-fresh-green);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <div>
                  <div id="attachedFileName" class="attached-file-name">school-record-sample.pdf</div>
                  <div id="attachedFileMeta" class="attached-file-meta">160 KB • Nakakabit • Halimbawang file</div>
                </div>
              </div>
              <button type="button" id="removeFileBtn" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:18px;font-weight:700;">&times;</button>
            </div>
          </div>

          <!-- Tracking Number Preview -->
          <div class="form-group full" style="margin-bottom:28px;">
            <label class="form-label">Tracking number</label>
            <input type="text" class="form-input" disabled value="Lalabas pagkatapos magsumite" style="background:#f1f5f9;color:var(--color-slate);">
            <div class="input-hint">Disabled • Gagawa lang ng number pagkatapos ng submission.</div>
          </div>

          <div style="display:flex;justify-content:space-between;align-items:center;">
            <button type="submit" name="go_back" value="1" class="btn btn-secondary" style="min-width:140px;">
              Bumalik
            </button>
            <button type="submit" class="btn btn-primary" style="min-width:220px;font-size:16px;">
              Isumite ang Aplikasyon &rarr;
            </button>
          </div>
        <?php endif; ?>

      </form>
    </section>

  </div>

</main>

<footer class="site-footer">
  ALS Connect • Alternative Learning System • Konseptong disenyo lamang.
</footer>

<script src="assets/app.js"></script>
<script>
function selectProgram(level) {
  document.getElementById('learnerStatusInput').value = level;
  document.querySelectorAll('.program-select-card').forEach(card => card.classList.remove('selected'));
  event.currentTarget.classList.add('selected');
}
</script>

</body>
</html>

