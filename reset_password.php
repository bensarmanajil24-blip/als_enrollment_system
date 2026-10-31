<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$errorMessage = '';
$successMessage = '';
$tokenValid = false;
$resetRecord = null;

if ($token !== '') {
    $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1');
    $stmt->execute([$token]);
    $resetRecord = $stmt->fetch();
    if ($resetRecord) {
        $tokenValid = true;
    } else {
        $errorMessage = 'Ang password reset link ay paso na o hindi wasto. Pakisubukang humiling muli ng bagong link.';
    }
} else {
    $errorMessage = 'Walang natagpuang reset token.';
}

// Handle New Password Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    verify_csrf();
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (strlen($newPassword) < 6) {
        $errorMessage = 'Ang password ay dapat hindi bababa sa 6 na karakter.';
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = 'Hindi tumutugma ang kumpirmasyon ng password.';
    } else {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $email = $resetRecord['email'];
        $role = $resetRecord['role'] ?? 'admin';

        // 1. Update in users table
        $pdo->prepare('UPDATE users SET password = ? WHERE email = ? OR username = ?')->execute([$hashed, $email, $email]);

        // 2. Update in role-specific tables
        if ($role === 'admin') {
            $pdo->prepare('UPDATE admins SET password = ? WHERE email = ? OR username = ?')->execute([$hashed, $email, $email]);
        } elseif ($role === 'teacher') {
            $pdo->prepare('UPDATE teachers SET password = ? WHERE email = ?')->execute([$hashed, $email]);
        } elseif ($role === 'student') {
            $pdo->prepare('UPDATE students SET password = ? WHERE email = ? OR student_number = ?')->execute([$hashed, $email, $email]);
        }

        // 3. Delete used token
        $pdo->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);

        // Redirect to login with success notice
        header('Location: login.php?msg=reset_success&role=' . urlencode($role));
        exit;
    }
}
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>I-reset ang Password | DEPED ALS</title>
  <link rel="stylesheet" href="assets/admin.css">
  <style>
    body.reset-page-body {
      background: linear-gradient(135deg, #07162c 0%, #0c2340 40%, #10356c 70%, #185adb 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      box-sizing: border-box;
      margin: 0;
    }
    .reset-card {
      width: 100%;
      max-width: 460px;
      background: #FFFFFF;
      border-radius: 16px;
      box-shadow: 0 20px 45px rgba(7, 22, 44, 0.4);
      padding: 32px 28px;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .reset-header {
      text-align: center;
      margin-bottom: 24px;
    }
    .reset-header h1 {
      font-size: 20px;
      font-weight: 850;
      color: #0B1E3B;
      margin: 8px 0 4px 0;
    }
    .reset-header p {
      font-size: 12.5px;
      color: #64748B;
      margin: 0;
    }
    .input-wrap {
      position: relative;
      margin-bottom: 16px;
    }
    .input-wrap label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      color: #0B1E3B;
      margin-bottom: 5px;
    }
    .input-field {
      width: 100%;
      padding: 11px 40px 11px 14px;
      border: 1.5px solid #CBD5E1;
      border-radius: 8px;
      font-size: 13px;
      box-sizing: border-box;
      outline: none;
      background: #F8FAFD;
      font-family: inherit;
    }
    .input-field:focus {
      border-color: #185ADB;
      background: #FFFFFF;
      box-shadow: 0 0 0 3px rgba(24, 90, 219, 0.12);
    }
    .eye-toggle-btn {
      position: absolute;
      right: 12px;
      top: 35px;
      background: none;
      border: none;
      cursor: pointer;
      color: #64748B;
      padding: 0;
      display: flex;
      align-items: center;
    }
    .btn-submit-reset {
      width: 100%;
      padding: 12px;
      border-radius: 8px;
      font-size: 13.5px;
      font-weight: 750;
      background: #185ADB;
      color: #FFFFFF;
      border: none;
      cursor: pointer;
      margin-top: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }
    .btn-submit-reset:hover {
      background: #134AB5;
    }
  </style>
</head>
<body class="reset-page-body">

<div class="reset-card">
  <div class="reset-header">
    <div style="width:48px;height:48px;background:#EBF2FD;border-radius:12px;display:grid;place-items:center;color:#185ADB;margin:0 auto 10px;">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
    </div>
    <h1>Lumikha ng Bagong Password</h1>
    <p>Ilagay ang iyong bagong password para sa account: <strong><?= e($resetRecord['email'] ?? '') ?></strong></p>
  </div>

  <?php if ($errorMessage): ?>
    <div style="background:#FEE2E2;border:1px solid #FCA5A5;color:#B91C1C;padding:12px;border-radius:8px;font-size:12px;font-weight:700;margin-bottom:16px;">
      ⚠ <?= e($errorMessage) ?>
    </div>
  <?php endif; ?>

  <?php if ($tokenValid): ?>
    <form method="post" action="reset_password.php">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="token" value="<?= e($token) ?>">

      <div class="input-wrap">
        <label for="new_password">Bagong Password *</label>
        <input type="password" name="new_password" id="new_password" class="input-field" required placeholder="Hindi bababa sa 6 na karakter" minlength="6">
        <button type="button" class="eye-toggle-btn" onclick="togglePassword('new_password', this)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
        </button>
      </div>

      <div class="input-wrap">
        <label for="confirm_password">Kumpirmahin ang Bagong Password *</label>
        <input type="password" name="confirm_password" id="confirm_password" class="input-field" required placeholder="Ulitin ang bagong password" minlength="6">
        <button type="button" class="eye-toggle-btn" onclick="togglePassword('confirm_password', this)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
        </button>
      </div>

      <button type="submit" class="btn-submit-reset">
        I-save at I-update ang Password &rarr;
      </button>
    </form>
  <?php else: ?>
    <div style="text-align:center;margin-top:16px;">
      <a href="login.php" style="color:#185ADB;font-size:13px;font-weight:700;text-decoration:none;">
        ← Bumalik sa Login Page
      </a>
    </div>
  <?php endif; ?>
</div>

<script>
function togglePassword(inputId, btn) {
  const input = document.getElementById(inputId);
  if (input.type === 'password') {
    input.type = 'text';
    btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;
  } else {
    input.type = 'password';
    btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
  }
}
</script>

</body>
</html>

