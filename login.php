<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// If already authenticated, redirect straight to role dashboard
if (is_logged_in() && !isset($_GET['switch'])) {
    redirect_if_logged_in();
}

$errorMessage = '';
$successMessage = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'auth_required') {
        $errorMessage = 'Please sign in to access your dashboard.';
    } elseif ($_GET['msg'] === 'logged_out') {
        $successMessage = 'You have successfully signed out of the DepEd ALS portal.';
    } elseif ($_GET['msg'] === 'reset_success') {
        $successMessage = 'Your password has been reset successfully! You may now sign in.';
    }
}

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $identifier = trim((string)($_POST['identifier'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($identifier === '' || $password === '') {
        $errorMessage = 'Please enter your username or registered email and password.';
    } else {
        // Query users table
        $stmt = $pdo->prepare('
            SELECT id, username, email, password, role, status, user_ref_id
            FROM users
            WHERE username = ? OR email = ?
            LIMIT 1
        ');
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        // Admin fallback
        if (!$user && in_array($identifier, ['admin', 'admin@deped.gov.ph', 'admin@school.edu.ph'], true)) {
            $admStmt = $pdo->prepare('SELECT id, username, email, password FROM admins LIMIT 1');
            $admStmt->execute();
            $adm = $admStmt->fetch();
            if ($adm && password_verify($password, $adm['password'])) {
                $user = [
                    'id' => 1,
                    'username' => $adm['username'],
                    'email' => $adm['email'] ?: 'admin@deped.gov.ph',
                    'password' => $adm['password'],
                    'role' => 'admin',
                    'status' => 'active',
                    'user_ref_id' => 1
                ];
            }
        }

        // Student fallback by LRN
        if (!$user) {
            $sStmt = $pdo->prepare('SELECT id, student_number, email, password, status FROM students WHERE student_number = ? OR email = ? LIMIT 1');
            $sStmt->execute([$identifier, $identifier]);
            $sRow = $sStmt->fetch();
            if ($sRow && !empty($sRow['password']) && password_verify($password, $sRow['password'])) {
                $user = [
                    'id' => (int)$sRow['id'],
                    'username' => $sRow['student_number'],
                    'email' => $sRow['email'],
                    'password' => $sRow['password'],
                    'role' => 'student',
                    'status' => ($sRow['status'] === 'Approved') ? 'active' : 'pending',
                    'user_ref_id' => (int)$sRow['id']
                ];
            }
        }

        // Teacher fallback by email
        if (!$user) {
            $tStmt = $pdo->prepare('SELECT id, name, email, password, status FROM teachers WHERE email = ? LIMIT 1');
            $tStmt->execute([$identifier]);
            $tRow = $tStmt->fetch();
            if ($tRow && !empty($tRow['password']) && password_verify($password, $tRow['password'])) {
                $user = [
                    'id' => (int)$tRow['id'],
                    'username' => $tRow['email'],
                    'email' => $tRow['email'],
                    'password' => $tRow['password'],
                    'role' => 'teacher',
                    'status' => 'active',
                    'user_ref_id' => (int)$tRow['id']
                ];
            }
        }

        // Authenticate credentials
        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'archived') {
                $errorMessage = 'This account has been archived. Please contact your DepEd ALS administrator to reactivate your portal access.';
            } else {
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_email'] = $user['email'];
                $role = strtolower((string)$user['role']);
                $_SESSION['user_role'] = ($role === 'im') ? 'teacher' : $role;
                $_SESSION['user_ref_id'] = (int)($user['user_ref_id'] ?? $user['id']);

                if ($role === 'teacher' || $role === 'im') {
                    header('Location: teacher_dashboard.php');
                    exit;
                } elseif ($role === 'student') {
                    header('Location: student_dashboard.php');
                    exit;
                } else {
                    $_SESSION['admin_id'] = (int)$user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    header('Location: admin_dashboard.php');
                    exit;
                }
            }
        } else {
            $errorMessage = 'Your login credentials are invalid. Please check your username/email and password, or contact your ALS administrator.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DepEd ALS - Unified Portal Sign In</title>
  <link rel="stylesheet" href="assets/admin.css">
  <link rel="stylesheet" href="assets/login.css">
</head>
<body style="margin:0;padding:0;background:#F8FAFC;font-family:'Inter',system-ui,sans-serif;">

<!-- Page Watermark Header (Image 1) -->
<div style="padding:10px 32px;font-size:14px;color:#94A3B8;font-weight:700;letter-spacing:-0.01em;background:#F8FAFC;">
  Role-based login
</div>

<div class="deped-split-container">

  <!-- Left Hero Pane (Image 1) -->
  <aside class="deped-hero-pane">
    <div class="hero-inner-content">
      <div>
        <!-- Brand Logo & Header -->
        <div class="deped-brand-header">
          <div class="deped-brand-icon-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:22px;height:22px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
          </div>
          <div>
            <h2 style="font-size:16px;font-weight:800;margin:0;line-height:1.2;color:#FFFFFF;letter-spacing:-0.02em;">DepEd ALS</h2>
            <span style="font-size:9.5px;font-weight:700;color:#94A3B8;letter-spacing:0.06em;text-transform:uppercase;">ALTERNATIVE LEARNING SYSTEM</span>
          </div>
        </div>

        <!-- Overline & Main Title -->
        <div style="margin-top:36px;">
          <span style="font-size:10.5px;font-weight:800;letter-spacing:0.08em;color:#60A5FA;text-transform:uppercase;display:block;margin-bottom:8px;">
            DEPARTMENT OF EDUCATION &bull; PHILIPPINES
          </span>
          <h1 style="font-size:30px;font-weight:850;line-height:1.2;margin:0 0 12px 0;letter-spacing:-0.02em;color:#FFFFFF;">
            Welcome to DepEd ALS
          </h1>
          <p style="font-size:13px;color:#CBD5E1;line-height:1.55;margin:0;max-width:440px;">
            One shared login for Admin, IM/Teacher, and Student. Enter your assigned credentials to access your portal.
          </p>
        </div>

        <!-- Classroom Photo Card with Overlay Pill Badge -->
        <div class="hero-photo-card">
          <img src="https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&w=900&q=80" alt="ALS Community Classroom">
          <div class="hero-admin-pill-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="#1D64EC" stroke-width="2.5" style="width:14px;height:14px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
            <span>Admin creates teacher and student accounts</span>
          </div>
        </div>
      </div>

      <!-- Bottom Feature Pillars -->
      <div>
        <div class="hero-pillars-row">
          <div class="hero-pillar-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
            <span>Shared login</span>
          </div>
          <div class="hero-pillar-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
            <span>Admin setup</span>
          </div>
          <div class="hero-pillar-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
            <span>Role-based access</span>
          </div>
        </div>
        <div style="font-size:11px;color:#64748B;margin-top:14px;">
          One portal &bull; Three roles: Admin, IM/Teacher, Student
        </div>
      </div>
    </div>
  </aside>

  <!-- Right Form Pane (Image 1) -->
  <main class="deped-form-pane">
    <div class="form-inner-content">
      <!-- Topbar Strip -->
      <div class="login-topbar-strip">
        <span>DepEd ALS portal</span>
        <a href="mailto:support@deped.gov.ph" style="color:#1D64EC;text-decoration:none;font-weight:600;">Need help?</a>
      </div>

      <!-- Center Login Form Area -->
      <div>
        <h2 style="font-size:24px;font-weight:800;color:#0F172A;margin:0 0 6px 0;letter-spacing:-0.02em;">
          Sign in
        </h2>
        <p style="font-size:12.5px;color:#64748B;margin:0 0 24px 0;line-height:1.45;">
          Use the same login for Admin, IM/Teacher, and Student. Your assigned role opens the matching portal after sign-in.
        </p>

        <?php if ($errorMessage): ?>
          <div style="background:#FEE2E2;border:1px solid #FCA5A5;color:#B91C1C;padding:12px 14px;border-radius:8px;font-size:12px;font-weight:600;margin-bottom:18px;">
            <?= htmlspecialchars($errorMessage) ?>
          </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
          <div style="background:#DCFCE7;border:1px solid #86EFAC;color:#15803D;padding:12px 14px;border-radius:8px;font-size:12px;font-weight:600;margin-bottom:18px;">
            <?= htmlspecialchars($successMessage) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
          <?= csrf_field() ?>

          <!-- Username or email address -->
          <div class="login-input-group">
            <label class="login-input-label">
              <span>Username or email address</span>
            </label>
            <div class="login-input-wrap">
              <svg class="input-icon-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              <input 
                type="text" 
                name="identifier" 
                id="identifierInput"
                required 
                placeholder="Enter your username or email address"
                value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
                autocomplete="username"
              >
            </div>
          </div>

          <!-- Password -->
          <div class="login-input-group">
            <div class="login-input-label">
              <span>Password</span>
              <a href="mailto:admin@deped.gov.ph?subject=Password%20Reset%20Request" style="font-size:11.5px;color:#1D64EC;text-decoration:none;font-weight:600;">
                Forgot password? Contact your ALS admin.
              </a>
            </div>
            <div class="login-input-wrap">
              <svg class="input-icon-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              <input 
                type="password" 
                name="password" 
                id="passwordInput"
                required 
                placeholder="Enter your password"
                autocomplete="current-password"
              >
              <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility()" aria-label="Toggle password visibility">
                <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </button>
            </div>
          </div>

          <!-- Remember Me Checkbox -->
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
            <input type="checkbox" id="keepSignedIn" name="remember_me" style="accent-color:#1D64EC;width:15px;height:15px;cursor:pointer;">
            <label for="keepSignedIn" style="font-size:12px;color:#475569;cursor:pointer;">Keep me signed in on this device</label>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="btn-deped-primary">
            <span>Sign in</span>
            <span>&rarr;</span>
          </button>
        </form>

        <!-- Footnotes -->
        <div style="margin-top:22px;text-align:center;">
          <span style="font-size:12px;color:#64748B;">
            Need an account? <a href="mailto:admin@deped.gov.ph?subject=ALS%20Account%20Creation%20Request" style="color:#1D64EC;font-weight:700;text-decoration:none;">Ask your ALS admin to create your account.</a>
          </span>
        </div>

        <div style="margin-top:14px;display:flex;align-items:center;justify-content:center;gap:6px;font-size:11px;color:#94A3B8;">
          <svg viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2" style="width:13px;height:13px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
          <span>Secure access for DepEd ALS users.</span>
        </div>

        <!-- Quick Demo Autofill Toolbar -->
        <div style="margin-top:20px;padding:10px 12px;background:#F8FAFC;border:1px dashed #CBD5E1;border-radius:8px;">
          <div style="font-size:10px;font-weight:750;letter-spacing:0.06em;color:#64748B;text-transform:uppercase;margin-bottom:6px;">
            DEMO AUTOFILL (TESTING)
          </div>
          <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <button type="button" onclick="fillDemo('admin@deped.gov.ph', 'admin2026')" class="demo-pill-btn">
              Admin
            </button>
            <button type="button" onclick="fillDemo('bensar.manajili@deped.gov.ph', 'teacher2026')" class="demo-pill-btn">
              IM / Teacher (Bensar)
            </button>
            <button type="button" onclick="fillDemo('maria.reyes@deped.gov.ph', 'student2026')" class="demo-pill-btn">
              Student (Maria)
            </button>
          </div>
        </div>
      </div>

      <!-- Footer Links -->
      <footer style="margin-top:24px;display:flex;justify-content:space-between;align-items:center;font-size:11px;color:#94A3B8;border-top:1px solid #F1F5F9;padding-top:14px;">
        <div style="display:flex;gap:12px;">
          <a href="#" style="color:#94A3B8;text-decoration:none;">Privacy</a>
          <a href="#" style="color:#94A3B8;text-decoration:none;">Terms</a>
        </div>
        <div>&copy; 2026 DepEd ALS</div>
      </footer>
    </div>
  </main>

</div>

<script src="assets/accounts.js"></script>
</body>
</html>
