<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/accounts_service.php';
require_admin();

$alertMessage = '';
$alertType = 'success';

// Handle Actions (Archive / Reactivate)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $accType = (string)($_POST['account_type'] ?? 'student');
    $accId = filter_input(INPUT_POST, 'account_id', FILTER_VALIDATE_INT);

    if ($action === 'archive' && $accId && $accId > 0) {
        archive_account($pdo, $accId, $accType);
        $alertMessage = 'Account has been safely moved to archived records. Historical submissions and attendance remain preserved.';
        $alertType = 'success';
    } elseif ($action === 'reactivate' && $accId && $accId > 0) {
        reactivate_account($pdo, $accId, $accType);
        $alertMessage = 'Account has been reactivated successfully.';
        $alertType = 'success';
    }
}

// Fetch metrics and combined account records via service
$metrics = get_account_metrics($pdo);
$combinedAccounts = get_combined_accounts($pdo, 12);

// Page configuration for layout components
$pageTitle = 'Account Management - DepEd ALS Admin Portal';
$pageWatermark = 'Admin and ALS Coordinator dashboard';
$activeNav = 'overview';
$breadcrumbs = ['Admin / ALS Coordinator', 'Overview'];
$showLogoutButton = false;

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
require_once __DIR__ . '/includes/admin_topbar.php';
?>

<?php if ($alertMessage): ?>
  <div class="alert-box-success" style="<?= $alertType === 'danger' ? 'background:#FEE2E2;color:#B91C1C;border-color:#FCA5A5;' : '' ?>">
    <span><?= htmlspecialchars($alertMessage) ?></span>
    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;font-weight:700;font-size:16px;">&times;</button>
  </div>
<?php endif; ?>

<!-- Header Title & Action Buttons (Image 2) -->
<section style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
  <div>
    <span style="font-size:10.5px;font-weight:800;letter-spacing:0.06em;color:#185ADB;text-transform:uppercase;display:block;margin-bottom:4px;">
      ADMIN PORTAL
    </span>
    <h1 style="font-size:26px;font-weight:850;color:#0F172A;margin:0 0 6px 0;letter-spacing:-0.02em;">
      Account management
    </h1>
    <p style="font-size:13px;color:#64748B;margin:0;">
      Create, read, edit, and archive teacher/IM and student accounts from one place.
    </p>
    <div style="font-size:11.5px;color:#94A3B8;margin-top:10px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      <span>Division of Zamboanga City</span>
      <span style="background:#EFF6FF;color:#185ADB;border:1px solid #BFDBFE;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:750;">
        SY 2026-2027
      </span>
      <span>&bull;</span>
      <span>Monday, October 26, 2026</span>
      <span>&bull;</span>
      <span>Updated just now</span>
    </div>
  </div>

  <!-- Action Buttons -->
  <div style="display:flex;align-items:center;gap:10px;">
    <a href="admin_add_account.php?type=teacher" class="table-action-pill primary" style="padding:9px 16px;font-size:12.5px;font-weight:750;">
      + Add teacher account
    </a>
    <a href="admin_add_account.php?type=student" class="table-action-pill primary" style="padding:9px 16px;font-size:12.5px;font-weight:750;">
      + Add student account
    </a>
  </div>
</section>

<!-- 4 Stat Cards (Image 2) -->
<section style="display:grid;grid-template-columns:repeat(4, 1fr);gap:16px;">
  <!-- Total accounts -->
  <div class="deped-stat-card">
    <div class="stat-header-row">
      <span style="font-size:12px;font-weight:700;color:#64748B;">Total accounts</span>
      <div style="width:28px;height:28px;border-radius:50%;background:#EFF6FF;color:#185ADB;display:grid;place-items:center;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
      </div>
    </div>
    <div>
      <div class="stat-number-val"><?= (int)$metrics['total'] ?></div>
      <div class="stat-subtext-note" style="color:#185ADB;">
        <?= (int)$metrics['teacher_count'] ?> teachers / IM &bull; <?= (int)$metrics['student_count'] ?> students
      </div>
    </div>
  </div>

  <!-- Active accounts -->
  <div class="deped-stat-card">
    <div class="stat-header-row">
      <span style="font-size:12px;font-weight:700;color:#64748B;">Active accounts</span>
      <div style="width:28px;height:28px;border-radius:50%;background:#ECFDF5;color:#059669;display:grid;place-items:center;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
      </div>
    </div>
    <div>
      <div class="stat-number-val"><?= (int)$metrics['active'] ?></div>
      <div class="stat-subtext-note">Ready to access the portal</div>
    </div>
  </div>

  <!-- Pending review -->
  <div class="deped-stat-card">
    <div class="stat-header-row">
      <span style="font-size:12px;font-weight:700;color:#64748B;">Pending review</span>
      <div style="width:28px;height:28px;border-radius:50%;background:#FEF3C7;color:#D97706;display:grid;place-items:center;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
      </div>
    </div>
    <div>
      <div class="stat-number-val"><?= (int)$metrics['pending'] ?></div>
      <div class="stat-subtext-note" style="color:#D97706;">Needs admin approval</div>
    </div>
  </div>

  <!-- Archived accounts -->
  <div class="deped-stat-card">
    <div class="stat-header-row">
      <span style="font-size:12px;font-weight:700;color:#64748B;">Archived accounts</span>
      <div style="width:28px;height:28px;border-radius:50%;background:#F1F5F9;color:#64748B;display:grid;place-items:center;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line></svg>
      </div>
    </div>
    <div>
      <div class="stat-number-val"><?= (int)$metrics['archived'] ?></div>
      <div class="stat-subtext-note">Removed from active lists</div>
    </div>
  </div>
</section>

<!-- Account Queue Card (Image 2) -->
<section class="deped-stat-card" style="padding:22px;">
  <div>
    <h2 style="font-size:16px;font-weight:800;color:#0F172A;margin:0 0 4px 0;">Account queue</h2>
    <p style="font-size:12px;color:#64748B;margin:0;">Accounts that need review, editing, or archiving</p>
  </div>

  <div style="display:flex;flex-direction:column;gap:8px;margin-top:6px;">
    <?php foreach (array_slice($combinedAccounts, 0, 5) as $qAcc): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border:1px solid #F1F5F9;border-radius:8px;background:#FDFDFE;">
        <div style="display:flex;align-items:center;gap:14px;min-width:220px;">
          <strong style="font-size:13px;color:#0F172A;"><?= htmlspecialchars($qAcc['name']) ?></strong>
        </div>
        <div style="color:#64748B;font-size:12px;min-width:80px;"><?= htmlspecialchars($qAcc['role']) ?></div>
        <div style="color:#475569;font-size:12px;min-width:140px;"><?= htmlspecialchars($qAcc['learning_center']) ?></div>
        <div>
          <span class="status-badge-pill <?= strtolower($qAcc['status']) ?>">
            <?= htmlspecialchars($qAcc['status']) ?>
          </span>
        </div>
        <div style="color:#94A3B8;font-size:11.5px;min-width:90px;"><?= htmlspecialchars($qAcc['last_active']) ?></div>
        <div style="display:flex;gap:6px;">
          <a href="admin_view_account.php?id=<?= $qAcc['id'] ?>&type=<?= $qAcc['type'] ?>" class="table-action-pill">View</a>
          <a href="admin_edit_account.php?id=<?= $qAcc['id'] ?>&type=<?= $qAcc['type'] ?>" class="table-action-pill">Edit</a>
          <form method="POST" action="admin_dashboard.php" style="display:inline;" onsubmit="return confirm('Archive this account? Historical records will stay preserved.');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="archive">
            <input type="hidden" name="account_type" value="<?= $qAcc['type'] ?>">
            <input type="hidden" name="account_id" value="<?= $qAcc['id'] ?>">
            <button type="submit" class="table-action-pill archive">Archive</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Account Management Table Card (Image 2) -->
<section class="deped-stat-card" id="accounts" style="padding:22px;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div>
      <h2 style="font-size:16px;font-weight:800;color:#0F172A;margin:0 0 4px 0;">Account management</h2>
      <p style="font-size:12px;color:#64748B;margin:0;">
        Manage teacher/IM and student accounts from one place. Search, filter, and review account status without exposing credentials.
      </p>
    </div>

    <!-- Role Filter Tabs -->
    <div style="display:flex;align-items:center;gap:6px;">
      <button type="button" class="table-action-pill primary" onclick="filterByRole('all', this)">All accounts</button>
      <button type="button" class="table-action-pill" onclick="filterByRole('teacher', this)">Teachers / IM</button>
      <button type="button" class="table-action-pill" onclick="filterByRole('student', this)">Students</button>
    </div>
  </div>

  <!-- Search & Filter Controls -->
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:12px 0;">
    <div style="position:relative;flex:1;max-width:380px;">
      <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search name, email, or username" style="width:100%;box-sizing:border-box;padding:8px 12px 8px 32px;border:1px solid #CBD5E1;border-radius:8px;font-size:12px;outline:none;">
      <svg viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2" style="position:absolute;left:10px;top:9px;width:14px;height:14px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
    </div>

    <div style="display:flex;align-items:center;gap:10px;">
      <select id="roleDropdown" onchange="filterTable()" style="padding:7px 12px;border:1px solid #CBD5E1;border-radius:8px;font-size:12px;outline:none;background:#fff;">
        <option value="all">All roles</option>
        <option value="IM">Teacher / IM</option>
        <option value="Student">Student</option>
      </select>

      <button type="button" onclick="exportCsv()" class="table-action-pill" style="padding:7px 12px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        <span>Export CSV</span>
      </button>
    </div>
  </div>

  <!-- Accounts Table -->
  <div style="overflow-x:auto;">
    <table class="odb-table" id="accountsTable">
      <thead>
        <tr>
          <th>ACCOUNT</th>
          <th>ROLE</th>
          <th>LEARNING CENTER</th>
          <th>STATUS</th>
          <th>LAST ACTIVE</th>
          <th>ACTIONS</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($combinedAccounts as $acc): ?>
          <tr data-role="<?= htmlspecialchars($acc['role']) ?>" data-type="<?= htmlspecialchars($acc['type']) ?>">
            <td>
              <strong style="font-size:13px;color:#0F172A;display:block;"><?= htmlspecialchars($acc['name']) ?></strong>
              <span style="font-size:11px;color:#64748B;"><?= htmlspecialchars($acc['email']) ?></span>
            </td>
            <td style="font-weight:600;color:#334155;"><?= htmlspecialchars($acc['role']) ?></td>
            <td style="color:#475569;"><?= htmlspecialchars($acc['learning_center']) ?></td>
            <td>
              <span class="status-badge-pill <?= strtolower($acc['status']) ?>">
                <?= htmlspecialchars($acc['status']) ?>
              </span>
            </td>
            <td style="color:#64748B;font-size:12px;"><?= htmlspecialchars($acc['last_active']) ?></td>
            <td>
              <div style="display:flex;gap:6px;">
                <a href="admin_view_account.php?id=<?= $acc['id'] ?>&type=<?= $acc['type'] ?>" class="table-action-pill">View</a>
                <a href="admin_edit_account.php?id=<?= $acc['id'] ?>&type=<?= $acc['type'] ?>" class="table-action-pill">Edit</a>
                <form method="POST" action="admin_dashboard.php" style="display:inline;" onsubmit="return confirm('Archive this account? Historical records will stay preserved.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="archive">
                  <input type="hidden" name="account_type" value="<?= $acc['type'] ?>">
                  <input type="hidden" name="account_id" value="<?= $acc['id'] ?>">
                  <button type="submit" class="table-action-pill archive">Archive</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination & Count Strip -->
  <div style="display:flex;justify-content:space-between;align-items:center;padding-top:14px;border-top:1px solid #F1F5F9;font-size:11.5px;color:#64748B;">
    <span>Showing <?= count($combinedAccounts) ?> of 12 accounts</span>
    <div style="display:flex;gap:8px;">
      <span style="cursor:pointer;color:#94A3B8;">Previous</span>
      <strong style="color:#185ADB;">1</strong>
      <span style="cursor:pointer;">2</span>
      <span style="cursor:pointer;">3</span>
      <span>...</span>
      <span style="cursor:pointer;">5</span>
      <span style="cursor:pointer;color:#185ADB;">Next</span>
    </div>
  </div>

  <!-- Archival Compliance Warning -->
  <div class="alert-box-warning" style="margin-top:12px;">
    <span style="color:#D97706;font-weight:800;font-size:14px;">!</span>
    <span style="font-size:11.5px;color:#92400E;line-height:1.45;">
      Archive removes an account from active lists while keeping historical records. Delete permanently removes the account and is only available after archiving.
    </span>
  </div>
</section>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
