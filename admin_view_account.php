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
$enrollmentStatus = $account['enrollment_status'];
$lastActive = $account['last_active'];

$parts = explode(' ', $fullName);
$initials = strtoupper(substr($parts[0] ?? 'M', 0, 1) . substr(end($parts) ?: 'R', 0, 1));

// Layout configuration
$pageTitle = 'Admin — View account - DepEd ALS';
$pageWatermark = 'Admin — View account';
$activeNav = 'accounts';
$breadcrumbs = ['Admin / ALS Coordinator', 'Accounts'];
$showLogoutButton = true;

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
require_once __DIR__ . '/includes/admin_topbar.php';
?>

<!-- Header with Action Buttons (Image 4) -->
<div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
  <div>
    <span style="font-size:10px;font-weight:800;letter-spacing:0.06em;color:#185ADB;text-transform:uppercase;display:block;margin-bottom:3px;">
      ADMIN PORTAL / ACCOUNTS / VIEW
    </span>
    <h1 style="font-size:24px;font-weight:850;color:#0F172A;margin:0 0 4px 0;letter-spacing:-0.02em;">
      Account details
    </h1>
    <p style="font-size:12.5px;color:#64748B;margin:0;">
      Read-only learner information and portal access settings.
    </p>
  </div>

  <div style="display:flex;align-items:center;gap:10px;">
    <form method="POST" action="admin_dashboard.php" style="margin:0;" onsubmit="return confirm('Archive this account? Historical records will stay preserved.');">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="archive">
      <input type="hidden" name="account_type" value="<?= htmlspecialchars($type) ?>">
      <input type="hidden" name="account_id" value="<?= (int)$id ?>">
      <button type="submit" class="table-action-pill" style="padding:8px 14px;font-size:12px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line></svg>
        <span>Archive</span>
      </button>
    </form>

    <a href="admin_edit_account.php?id=<?= $id ?>&type=<?= $type ?>" class="table-action-pill primary" style="padding:8px 16px;font-size:12px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
      <span>Edit account</span>
    </a>
  </div>
</div>

<!-- Account Profile Banner Card (Image 4) -->
<div class="view-card-container" style="display:flex;justify-content:space-between;align-items:center;padding:18px 24px;">
  <div style="display:flex;align-items:center;gap:16px;">
    <div style="width:48px;height:48px;border-radius:12px;background:#FEF3C7;color:#D97706;font-weight:850;font-size:16px;display:grid;place-items:center;">
      <?= htmlspecialchars($initials) ?>
    </div>
    <div>
      <h2 style="font-size:18px;font-weight:850;color:#0F172A;margin:0 0 2px 0;">
        <?= htmlspecialchars($fullName) ?>
      </h2>
      <span style="font-size:12px;color:#64748B;">
        <?= $type === 'student' ? 'LRN' : 'ID' ?> <?= htmlspecialchars($identifier) ?> &bull; <?= htmlspecialchars($role) ?>
      </span>
    </div>
  </div>

  <span class="status-badge-pill active" style="font-size:11.5px;padding:4px 12px;">
    <?= htmlspecialchars($status) ?>
  </span>
</div>

<!-- Two Detail Cards Grid (Image 4) -->
<div style="display:grid;grid-template-columns:1.8fr 1fr;gap:20px;align-items:start;">

  <!-- Left: Educational Details Card -->
  <div class="view-card-container">
    <h3 style="font-size:15px;font-weight:800;color:#0F172A;margin:0 0 3px 0;">
      Educational details
    </h3>
    <p style="font-size:12px;color:#64748B;margin:0 0 20px 0;">
      Learner placement for school year 2026&ndash;2027.
    </p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="detail-item-group">
        <span class="detail-item-lbl">Full name</span>
        <div class="detail-item-val"><?= htmlspecialchars($fullName) ?></div>
      </div>

      <div class="detail-item-group">
        <span class="detail-item-lbl"><?= $type === 'student' ? 'Learner reference number' : 'Employee ID' ?></span>
        <div class="detail-item-val"><?= htmlspecialchars($identifier) ?></div>
      </div>

      <div class="detail-item-group">
        <span class="detail-item-lbl">Program</span>
        <div class="detail-item-val"><?= htmlspecialchars($program) ?></div>
      </div>

      <div class="detail-item-group">
        <span class="detail-item-lbl">School year</span>
        <div class="detail-item-val">2026&ndash;2027</div>
      </div>

      <div class="detail-item-group">
        <span class="detail-item-lbl">Learning center</span>
        <div class="detail-item-val"><?= htmlspecialchars($learningCenter) ?></div>
      </div>

      <div class="detail-item-group">
        <span class="detail-item-lbl"><?= $type === 'student' ? 'Assigned IM' : 'Designation' ?></span>
        <div class="detail-item-val"><?= htmlspecialchars($assignedIm) ?></div>
      </div>

      <div class="detail-item-group">
        <span class="detail-item-lbl">Enrollment status</span>
        <div class="detail-item-val"><?= htmlspecialchars($enrollmentStatus) ?></div>
      </div>
    </div>

    <!-- Historical records stay connected note -->
    <div class="alert-box-info" style="margin-top:20px;padding:12px 14px;border-radius:8px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="#185ADB" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;margin-top:2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
      <div>
        <strong style="font-size:11.5px;color:#1E40AF;display:block;margin-bottom:2px;">Historical records stay connected</strong>
        <span style="font-size:11px;color:#1D4ED8;line-height:1.45;">
          Learning progress, submissions, and attendance belong to this learner record. Archiving access does not delete these records.
        </span>
      </div>
    </div>
  </div>

  <!-- Right: Account Access Card -->
  <div class="view-card-container">
    <h3 style="font-size:15px;font-weight:800;color:#0F172A;margin:0 0 3px 0;">
      Account access
    </h3>
    <p style="font-size:12px;color:#64748B;margin:0 0 20px 0;">
      Separate from educational information.
    </p>

    <div class="detail-item-group">
      <span class="detail-item-lbl">Username / email</span>
      <div class="detail-item-val"><?= htmlspecialchars($email) ?></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
      <div>
        <span class="detail-item-lbl">Role</span>
        <div class="detail-item-val"><?= htmlspecialchars($role) ?></div>
      </div>
      <div>
        <span class="detail-item-lbl">Account status</span>
        <div class="detail-item-val"><?= htmlspecialchars($status) ?></div>
      </div>
    </div>

    <div class="detail-item-group">
      <span class="detail-item-lbl">Activation</span>
      <div class="detail-item-val">Email activated</div>
    </div>

    <div class="detail-item-group">
      <span class="detail-item-lbl">Last active</span>
      <div class="detail-item-val" style="font-size:12.5px;font-weight:600;"><?= $lastActive ?></div>
    </div>

    <div style="margin-top:20px;padding-top:14px;border-top:1px solid #F1F5F9;font-size:11px;color:#94A3B8;">
      Passwords are private and are never displayed.
    </div>
    <div style="font-size:11px;color:#64748B;margin-top:6px;">
      Managed by Admin / ALS Coordinator. Student access is limited to their own records.
    </div>
  </div>

</div>

<!-- Back to Accounts Button -->
<div>
  <a href="admin_dashboard.php#accounts" class="table-action-pill" style="padding:9px 18px;font-size:12.5px;">
    &larr; Back to accounts
  </a>
</div>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
