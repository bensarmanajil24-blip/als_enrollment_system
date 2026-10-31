<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/accounts_service.php';
require_admin();

$accType = strtolower(trim((string)($_GET['type'] ?? 'student')));
if ($accType !== 'teacher') {
    $accType = 'student';
}

$alertMessage = '';

// Fetch active teachers for dropdown
$tStmt = $pdo->query('SELECT id, name, email FROM teachers WHERE status = "Active" ORDER BY name ASC');
$activeTeachers = $tStmt->fetchAll();

// Auto sequential LRN/ID
$autoLrn = get_next_sequential_student_id($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $res = create_account($pdo, $_POST);
    if ($res['success']) {
        header("Location: admin_view_account.php?id={$res['id']}&type={$res['type']}&created=1");
        exit;
    } else {
        $alertMessage = $res['error'] ?? 'An error occurred while creating the account.';
    }
}

// Layout configuration
$pageTitle = 'Admin — Add account - DepEd ALS';
$pageWatermark = 'Admin — Add account';
$activeNav = 'accounts';
$breadcrumbs = ['Admin / ALS Coordinator', 'Accounts'];
$showLogoutButton = true;

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
require_once __DIR__ . '/includes/admin_topbar.php';
?>

<?php if ($alertMessage): ?>
  <div class="alert-box-error">
    <div style="width:20px;height:20px;border-radius:50%;background:#FEE2E2;color:#B91C1C;display:grid;place-items:center;font-weight:800;flex-shrink:0;">!</div>
    <span style="font-size:12.5px;color:#991B1B;font-weight:600;"><?= htmlspecialchars($alertMessage) ?></span>
  </div>
<?php endif; ?>

<!-- Header (Image 3) -->
<div>
  <span style="font-size:10px;font-weight:800;letter-spacing:0.06em;color:#185ADB;text-transform:uppercase;display:block;margin-bottom:3px;">
    ADMIN PORTAL / ACCOUNTS
  </span>
  <h1 style="font-size:24px;font-weight:850;color:#0F172A;margin:0 0 4px 0;letter-spacing:-0.02em;">
    Add account
  </h1>
  <p style="font-size:12.5px;color:#64748B;margin:0;">
    Create a teacher/IM or student account. Fields marked * are required.
  </p>
</div>

<form method="POST" action="admin_add_account.php">
  <?= csrf_field() ?>

  <div style="display:grid;grid-template-columns:1.8fr 1fr;gap:20px;align-items:start;">

    <!-- Left Form Card: Student / Teacher Information -->
    <div class="form-card-container">
      <h2 id="cardHeaderTitle" style="font-size:15px;font-weight:800;color:#0F172A;margin:0 0 4px 0;">
        <?= $accType === 'teacher' ? 'Teacher / IM information' : 'Student information' ?>
      </h2>
      <p style="font-size:12px;color:#64748B;margin:0 0 20px 0;">
        Only an Admin / ALS Coordinator can create portal accounts.
      </p>

      <!-- Account Type Selector -->
      <div style="margin-bottom:18px;">
        <label class="field-label">Account type *</label>
        <div style="display:flex;gap:12px;">
          <label class="type-radio-pill <?= $accType === 'teacher' ? 'selected' : '' ?>" id="radioLabelTeacher">
            <input type="radio" name="account_type" value="teacher" <?= $accType === 'teacher' ? 'checked' : '' ?> onchange="switchAccountType('teacher')" style="accent-color:#185ADB;">
            <span>Teacher / IM</span>
          </label>
          <label class="type-radio-pill <?= $accType === 'student' ? 'selected' : '' ?>" id="radioLabelStudent">
            <input type="radio" name="account_type" value="student" <?= $accType === 'student' ? 'checked' : '' ?> onchange="switchAccountType('student')" style="accent-color:#185ADB;">
            <span>Student</span>
          </label>
        </div>
      </div>

      <!-- Full Name & LRN Grid -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
        <div>
          <label class="field-label">Full name *</label>
          <input type="text" name="full_name" required placeholder="Ana Patricia Santos" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" class="field-input">
        </div>
        <div>
          <label class="field-label" id="lrnLabel">
            <?= $accType === 'teacher' ? 'Teacher Employee ID *' : 'Learner reference number *' ?>
          </label>
          <input type="text" name="lrn_or_id" id="lrnInput" value="<?= htmlspecialchars($_POST['lrn_or_id'] ?? $autoLrn) ?>" class="field-input">
        </div>
      </div>

      <!-- Username / Email -->
      <div style="margin-bottom:16px;">
        <label class="field-label">Username / email *</label>
        <input type="email" name="email" required placeholder="ana.santos@deped.gov.ph" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="field-input">
        <span id="emailHintText" style="font-size:11px;color:#64748B;display:block;margin-top:4px;">
          <?= $accType === 'teacher' ? 'The teacher will use this email to activate and access the IM Teacher portal.' : 'The learner will use this email to activate and access the Student portal.' ?>
        </span>
      </div>

      <!-- Program & School Year -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
        <div>
          <label class="field-label">Program *</label>
          <select name="program" class="field-input">
            <option value="Basic Literacy Program" selected>Basic Literacy Program</option>
            <option value="Elementary">Elementary (A&amp;E)</option>
            <option value="Junior High School">Junior High School (A&amp;E)</option>
          </select>
        </div>
        <div>
          <label class="field-label">School year</label>
          <div style="position:relative;">
            <input type="text" value="2026&ndash;2027" readonly class="field-input" style="padding-right:32px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" style="position:absolute;right:10px;top:10px;width:15px;height:15px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
          </div>
        </div>
      </div>

      <!-- Learning Center -->
      <div style="margin-bottom:16px;">
        <label class="field-label">Learning center *</label>
        <select name="learning_center" class="field-input">
          <option value="San Nicolas Community Learning Center" selected>San Nicolas Community Learning Center</option>
          <option value="Sta. Maria CLC">Sta. Maria CLC</option>
          <option value="Tetuan CLC">Tetuan CLC</option>
        </select>
      </div>

      <!-- Assigned IM -->
      <div style="margin-bottom:16px;">
        <label class="field-label">Assigned IM *</label>
        <select name="assigned_im" class="field-input">
          <?php foreach ($activeTeachers as $tch): ?>
            <option value="<?= htmlspecialchars($tch['name']) ?>">
              <?= htmlspecialchars($tch['name']) ?>
            </option>
          <?php endforeach; ?>
          <?php if (empty($activeTeachers)): ?>
            <option value="Bensar S. Manajili">Bensar S. Manajili</option>
          <?php endif; ?>
        </select>
      </div>
    </div>

    <!-- Right Sidebar Card: Secure Activation -->
    <div style="display:flex;flex-direction:column;gap:16px;">
      <div class="form-card-container">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <h3 style="font-size:14px;font-weight:800;color:#0F172A;margin:0;">Secure activation</h3>
          <span style="background:#EFF6FF;color:#185ADB;font-size:10.5px;font-weight:750;padding:2px 8px;border-radius:4px;">
            Invitation required
          </span>
        </div>

        <label style="display:flex;align-items:flex-start;gap:8px;font-size:12px;font-weight:700;color:#0F172A;cursor:pointer;margin-bottom:6px;">
          <input type="checkbox" name="send_activation" value="1" checked style="accent-color:#185ADB;margin-top:2px;">
          <span>Send activation link by email</span>
        </label>
        <p style="font-size:11.5px;color:#64748B;line-height:1.45;margin:0 0 12px 24px;">
          The learner sets their own password using a single-use link that expires after 48 hours.
        </p>

        <div style="border-top:1px solid #F1F5F9;padding-top:12px;font-size:11px;color:#94A3B8;">
          Initial status: Pending activation. No saved password is displayed or shared.
        </div>
      </div>

      <!-- Info Alert: Student Access Only -->
      <div class="alert-box-info">
        <svg viewBox="0 0 24 24" fill="none" stroke="#185ADB" stroke-width="2" style="width:18px;height:18px;flex-shrink:0;margin-top:2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        <div>
          <strong style="font-size:12px;color:#1E40AF;display:block;margin-bottom:2px;">Student access only</strong>
          <p style="font-size:11.5px;color:#1D4ED8;line-height:1.45;margin:0;">
            Students can access their own modules, submissions, and attendance. They cannot create or manage accounts.
          </p>
        </div>
      </div>
    </div>

  </div>

  <!-- Bottom Action Bar (Image 3) -->
  <div style="margin-top:20px;padding:16px 20px;background:#FFFFFF;border:1px solid #E2E8F0;border-radius:12px;display:flex;justify-content:space-between;align-items:center;">
    <span style="font-size:12px;color:#64748B;">
      Review all details before creating this account.
    </span>
    <div style="display:flex;align-items:center;gap:10px;">
      <a href="admin_dashboard.php#accounts" class="table-action-pill" style="padding:9px 18px;font-size:12.5px;">Cancel</a>
      <button type="submit" class="table-action-pill primary" style="padding:9px 20px;font-size:12.5px;">
        Create account
      </button>
    </div>
  </div>
</form>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
