<?php
declare(strict_types=1);

/**
 * Reusable DepEd ALS Admin Topbar Header
 * Variables:
 *  - $breadcrumbs (array): list of titles e.g. ['Admin / ALS Coordinator', 'Accounts']
 *  - $showLogoutButton (bool): whether to display standalone logout button
 */
$breadcrumbs = $breadcrumbs ?? ['Admin / ALS Coordinator', 'Overview'];
$showLogoutButton = $showLogoutButton ?? false;
?>
<header class="deped-topbar-strip">
  <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#64748B;">
    <?php foreach ($breadcrumbs as $idx => $bc): ?>
      <?php if ($idx > 0): ?>
        <span>&gt;</span>
      <?php endif; ?>
      <?php if ($idx === count($breadcrumbs) - 1): ?>
        <strong style="color:#0F172A;"><?= htmlspecialchars($bc) ?></strong>
      <?php else: ?>
        <span><?= htmlspecialchars($bc) ?></span>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <div style="display:flex;align-items:center;gap:18px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" style="width:18px;height:18px;cursor:pointer;"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>

    <div style="display:flex;align-items:center;gap:10px;cursor:pointer;">
      <div style="width:34px;height:34px;border-radius:50%;background:#EFF6FF;color:#185ADB;font-weight:800;font-size:13px;display:grid;place-items:center;">
        AC
      </div>
      <div>
        <strong style="font-size:12.5px;color:#0F172A;display:block;">Admin / ALS Coordinator</strong>
        <span style="font-size:10.5px;color:#64748B;">Admin / ALS Coordinator</span>
      </div>
      <?php if (!$showLogoutButton): ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" style="width:14px;height:14px;margin-left:2px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
      <?php endif; ?>
    </div>

    <?php if ($showLogoutButton): ?>
      <a href="logout.php" style="border:1px solid #E2E8F0;padding:6px 12px;border-radius:6px;background:#fff;text-decoration:none;color:#334155;font-weight:700;font-size:12px;display:inline-flex;align-items:center;gap:6px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        <span>Log out</span>
      </a>
    <?php endif; ?>
  </div>
</header>
<main style="padding:24px 32px;display:flex;flex-direction:column;gap:20px;flex:1;box-sizing:border-box;">

