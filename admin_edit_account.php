<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/accounts_service.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;
$type = strtolower(trim((string)($_GET['type'] ?? 'student')));
if ($type !== 'teacher') {
    $type = 'student';
}

$account = get_account_by_id($pdo, $id, $type);
$fullName = $account['full_name'];
$identifier = $account['identifier'];
$email = $account['email'];
$role = $account['role'];
$status = $account['status'];
$program = $account['program'];
$learningCenter = $account['learning_center'];
$assignedIm = $account['assigned_im'];
$previouslyAssignedIm = $account['assigned_im'] ?: 'Bensar S. Manajili';

// Fetch IM Teachers for select dropdown
$tStmt = $pdo->query('SELECT id, name FROM teachers WHERE status = "Active" ORDER BY name ASC');
$imTeachers = $tStmt->fetchAll();

$alertError = '';
$hasValidationError = false;

// Mockup image 5 validation state preview
if (isset($_GET['err']) || isset($_GET['demo_error'])) {
    $alertError = 'Assigned IM is required. Select an IM before saving. Your changes have not been saved.';
    $hasValidationError = true;
    $assignedIm = '';
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $res = update_account($pdo, $id, $type, $_POST);
    if ($res['success']) {
        header("Location: admin_view_account.php?id={$id}&type={$type}&updated=1");
        exit;
    } else {
        $alertError = $res['error'] ?? 'An error occurred while saving changes.';
        $hasValidationError = true;
        $assignedIm = trim((string)($_POST['assigned_im'] ?? ''));
    }
}

// Layout configuration
$pageTitle = 'Admin — Edit account - DepEd ALS';
$pageWatermark = 'Admin — Edit account';
$activeNav = 'accounts';
$breadcrumbs = ['Admin / ALS Coordinator', 'Accounts'];
$showLogoutButton = true;

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
require_once __DIR__ . '/includes/admin_topbar.php';
?>

<!-- Header (Image 5) -->
<div>
  <span style="font-size:10px;font-weight:800;letter-spacing:0.06em;color:#185ADB;text-transform:uppercase;display:block;margin-bottom:3px;">
    ADMIN PORTAL / ACCOUNTS / EDIT
  </span>
  <h1 style="font-size:24px;font-weight:850;color:#0F172A;margin:0 0 4px 0;letter-spacing:-0.02em;">
    Edit account
  </h1>
  <p style="font-size:12.5px;color:#64748B;margin:0;">
    Update <?= htmlspecialchars($fullName) ?>'s details. Fields marked * are required.
  </p>
</div>

<?php if ($alertError): ?>
  <!-- Error Alert Banner (Image 5) -->
  <div class="alert-box-error">
    <div style="width:22px;height:22px;border-radius:50%;background:#FEE2E2;color:#B91C1C;display:grid;place-items:center;font-weight:800;flex-shrink:0;">
      !
    </div>
    <div>
      <strong style="font-size:12.5px;color:#991B1B;display:block;margin-bottom:2px;">Check the required field</strong>
      <span style="font-size:12px;color:#B91C1C;line-height:1.45;">
        <?= htmlspecialchars($alertError) ?>
      </span>
    </div>
  </div>
<?php endif; ?>

<form method="POST" action="admin_edit_account.php?id=<?= $id ?>&type=<?= $type ?>">
  <?= csrf_field() ?>

  <div style="display:grid;grid-template-columns:1.8fr 1fr;gap:20px;align-items:start;">

    <!-- Left Form Card: Learner information (Image 5) -->
    <div class="form-card-container">
      <h2 style="font-size:15px;font-weight:800;color:#0F172A;margin:0 0 4px 0;">
        <?= $type === 'teacher' ? 'Teacher information' : 'Learner information' ?>
      </h2>
      <p style="font-size:12px;color:#64748B;margin:0 0 20px 0;">
        Role: <?= htmlspecialchars($role) ?> &bull; Active account
      </p>

      <!-- Full Name & LRN Grid -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
        <div>
          <label class="field-label">Full name *</label>
          <input type="text" name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? $fullName) ?>" class="field-input">
        </div>
        <div>
          <label class="field-label"><?= $type === 'teacher' ? 'Teacher Employee ID' : 'Learner reference number' ?></label>
          <div style="position:relative;">
            <input type="text" value="<?= htmlspecialchars($identifier) ?>" readonly class="field-input" style="padding-right:32px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" style="position:absolute;right:10px;top:10px;width:15px;height:15px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
          </div>
          <span style="font-size:11px;color:#64748B;display:block;margin-top:4px;">LRN is a protected learner identifier.</span>
        </div>
      </div>

      <!-- Username / Email -->
      <div style="margin-bottom:16px;">
        <label class="field-label">Username / email *</label>
        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? $email) ?>" class="field-input">
      </div>

      <!-- Program & School Year -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
        <div>
          <label class="field-label">Program *</label>
          <select name="program" class="field-input">
            <option value="Basic Literacy Program" <?= $program === 'Basic Literacy Program' ? 'selected' : '' ?>>Basic Literacy Program</option>
            <option value="Elementary" <?= $program === 'Elementary' ? 'selected' : '' ?>>Elementary</option>
            <option value="Junior High School" <?= $program === 'Junior High School' ? 'selected' : '' ?>>Junior High School</option>
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
          <option value="San Nicolas Community Learning Center" <?= $learningCenter === 'San Nicolas Community Learning Center' ? 'selected' : '' ?>>San Nicolas Community Learning Center</option>
          <option value="Sta. Maria CLC" <?= $learningCenter === 'Sta. Maria CLC' ? 'selected' : '' ?>>Sta. Maria CLC</option>
          <option value="Tetuan CLC" <?= $learningCenter === 'Tetuan CLC' ? 'selected' : '' ?>>Tetuan CLC</option>
        </select>
      </div>

      <!-- Assigned IM with validation border (Image 5) -->
      <div style="margin-bottom:16px;">
        <label class="field-label">Assigned IM *</label>
        <select name="assigned_im" class="field-input <?= $hasValidationError ? 'has-error' : '' ?>">
          <option value="">Select an assigned IM</option>
          <?php foreach ($imTeachers as $tch): ?>
            <option value="<?= htmlspecialchars($tch['name']) ?>" <?= $assignedIm === $tch['name'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($tch['name']) ?>
            </option>
          <?php endforeach; ?>
          <?php if (empty($imTeachers)): ?>
            <option value="Bensar S. Manajili" <?= $assignedIm === 'Bensar S. Manajili' ? 'selected' : '' ?>>Bensar S. Manajili</option>
          <?php endif; ?>
        </select>
        <?php if ($hasValidationError): ?>
          <span style="font-size:11px;color:#DC2626;display:block;margin-top:4px;">
            Assigned IM is required. Please select an IM.
          </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right Sidebar Card: Account being edited (Image 5) -->
    <div style="display:flex;flex-direction:column;gap:16px;">
      <div class="form-card-container">
        <h3 style="font-size:14px;font-weight:800;color:#0F172A;margin:0 0 14px 0;">Account being edited</h3>

        <div style="margin-bottom:12px;">
          <span style="font-size:11px;color:#64748B;display:block;">Learner</span>
          <strong style="font-size:13px;color:#0F172A;"><?= htmlspecialchars($fullName) ?></strong>
        </div>

        <div style="margin-bottom:12px;">
          <span style="font-size:11px;color:#64748B;display:block;">Role</span>
          <strong style="font-size:13px;color:#0F172A;"><?= htmlspecialchars($role) ?></strong>
        </div>

        <div style="margin-bottom:16px;">
          <span class="status-badge-pill active">
            <?= htmlspecialchars($status) ?>
          </span>
        </div>

        <div style="border-top:1px solid #F1F5F9;padding-top:14px;margin-bottom:12px;">
          <span style="font-size:11px;color:#64748B;display:block;">Previously assigned IM</span>
          <strong style="font-size:12.5px;color:#0F172A;"><?= htmlspecialchars($previouslyAssignedIm) ?></strong>
        </div>
      </div>

      <!-- Keep access secure note -->
      <div class="alert-box-info">
        <svg viewBox="0 0 24 24" fill="none" stroke="#185ADB" stroke-width="2" style="width:18px;height:18px;flex-shrink:0;margin-top:2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        <div>
          <strong style="font-size:12px;color:#1E40AF;display:block;margin-bottom:2px;">Keep access secure</strong>
          <p style="font-size:11.5px;color:#1D4ED8;line-height:1.45;margin:0;">
            Role and account status are not changed here. No password is visible. Use Archive to disable access.
          </p>
        </div>
      </div>

      <div style="font-size:11px;color:#64748B;line-height:1.45;padding:0 4px;">
        Learning and attendance history stays linked to LRN <?= htmlspecialchars($identifier) ?> when account details change.
      </div>
    </div>

  </div>

  <!-- Bottom Action Bar (Image 5) -->
  <div style="margin-top:20px;padding:16px 20px;background:#FFFFFF;border:1px solid #E2E8F0;border-radius:12px;display:flex;justify-content:space-between;align-items:center;">
    <span style="font-size:12px;color:#DC2626;font-weight:600;">
      <?= $hasValidationError ? '1 required field needs attention' : '' ?>
    </span>
    <div style="display:flex;align-items:center;gap:10px;">
      <a href="admin_view_account.php?id=<?= $id ?>&type=<?= $type ?>" class="table-action-pill" style="padding:9px 18px;font-size:12.5px;">Cancel</a>
      <button type="submit" class="table-action-pill primary" style="padding:9px 20px;font-size:12.5px;">
        Save changes
      </button>
    </div>
  </div>
</form>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
