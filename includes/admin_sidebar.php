<?php
declare(strict_types=1);

/**
 * Reusable DepEd ALS Admin Navigation Sidebar
 * Variable expected:
 *  - $activeNav (string): 'overview' | 'students' | 'centers' | 'teachers' | 'enrollment' | 'reports' | 'accounts'
 */
$activeNav = $activeNav ?? 'overview';
?>
<aside class="deped-sidebar-v3">
  <!-- Brand -->
  <a href="admin_dashboard.php" class="deped-brand-strip">
    <div class="deped-brand-icon-box">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:20px;height:20px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
    </div>
    <div>
      <h2 style="font-size:14px;font-weight:800;margin:0;line-height:1.2;color:#FFFFFF;letter-spacing:-0.02em;">DepEd ALS</h2>
      <span style="font-size:9px;font-weight:700;color:#94A3B8;letter-spacing:0.06em;text-transform:uppercase;">ALTERNATIVE LEARNING SYSTEM</span>
    </div>
  </a>

  <!-- Workspace Badge -->
  <div class="deped-workspace-badge">
    <span style="font-size:9.5px;font-weight:750;color:#94A3B8;letter-spacing:0.06em;text-transform:uppercase;display:block;margin-bottom:2px;">YOUR WORKSPACE</span>
    <strong style="font-size:12.5px;color:#FFFFFF;">Admin / ALS Coordinator</strong>
  </div>

  <!-- Navigation Links -->
  <nav style="display:flex;flex-direction:column;gap:3px;">
    <a href="admin_dashboard.php" class="deped-nav-item <?= $activeNav === 'overview' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
      <span>Overview</span>
    </a>

    <a href="student_masterlist.php" class="deped-nav-item <?= $activeNav === 'students' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
      <span>Students</span>
    </a>

    <a href="admin_enrollment_strands.php" class="deped-nav-item <?= $activeNav === 'centers' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M3 21h18M3 7v14M21 7v14M6 11h4M6 15h4M14 11h4M14 15h4M9 3l3-2 3 2"></path></svg>
      <span>Learning centers</span>
    </a>

    <a href="teachers_registry.php" class="deped-nav-item <?= $activeNav === 'teachers' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
      <span>Teachers / IM</span>
    </a>

    <a href="enroll.php" class="deped-nav-item <?= $activeNav === 'enrollment' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
      <span>Enrollment review</span>
    </a>

    <a href="division_reports.php" class="deped-nav-item <?= $activeNav === 'reports' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
      <span>Reports</span>
    </a>

    <a href="admin_dashboard.php#accounts" class="deped-nav-item <?= $activeNav === 'accounts' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
      <span>Accounts</span>
    </a>
  </nav>

  <!-- Community Learning Center Footer Block -->
  <div style="margin-top:auto;padding-top:16px;border-top:1px solid rgba(255, 255, 255, 0.08);">
    <span style="font-size:9.5px;font-weight:750;letter-spacing:0.06em;color:#94A3B8;text-transform:uppercase;display:block;margin-bottom:2px;">COMMUNITY LEARNING CENTER</span>
    <strong style="font-size:12px;color:#FFFFFF;display:block;">Division of Zamboanga City</strong>
    <span style="font-size:11px;color:#64748B;">School year 2026&ndash;2027</span>
  </div>

  <!-- Bottom Links -->
  <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px;">
    <a href="mailto:support@deped.gov.ph" style="color:#94A3B8;text-decoration:none;font-size:12px;display:flex;align-items:center;gap:6px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
      <span>Help &amp; support</span>
    </a>
    <a href="logout.php" style="color:#FCA5A5;text-decoration:none;font-size:12px;display:flex;align-items:center;gap:6px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
      <span>Log out</span>
    </a>
    <span style="font-size:10px;color:#475569;margin-top:4px;">&copy; 2026 Department of Education</span>
  </div>
</aside>
<div style="flex:1;display:flex;flex-direction:column;min-width:0;background:#F8FAFC;min-height:100vh;">

