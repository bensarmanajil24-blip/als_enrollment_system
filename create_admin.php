<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Ensure admins table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(80) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
} catch (PDOException $e) {
    // Handled by connection
}

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $username)) {
        $error = 'Ang username ay dapat 3-80 characters (letra, numero, tuldok, underscore, o gitling).';
    } elseif (strlen($password) < 6) {
        $error = 'Gumamit ng password na may hindi bababa sa 6 na characters.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO admins (username, password) VALUES (?, ?)');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            $success = "Matagumpay na nagawa ang admin account para kay: {$username}. Maaari ka nang mag-sign in ngayon!";
        } catch (PDOException $e) {
            $error = 'Hindi nagawa ang account. Maaaring ginagamit na ang username na ito.';
        }
    }
}
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gumawa ng Admin Account - ALS Connect</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body style="display:flex;flex-direction:column;min-height:100vh;">

<header class="site-header">
  <div class="header-inner">
    <a class="site-brand" href="index.php">
      <div class="brand-badge">ALS</div>
      <div>
        <div class="brand-name">ALS Connect</div>
        <span class="brand-tag">Admin Management Portal</span>
      </div>
    </a>
    <a href="admin_login.php" class="nav-link">&larr; Pumunta sa Login</a>
  </div>
</header>

<main class="container narrow" style="flex:1;display:flex;align-items:center;justify-content:center;">
  <div style="max-width:460px;width:100%;">
    
    <div class="card-panel" style="padding: clamp(28px, 5vw, 40px);">
      <span class="eyebrow">SETUP NG ADMINISTRASYON</span>
      <h1 class="page-heading" style="font-size:26px;">Lumikha ng Admin Account</h1>
      <p class="muted-text" style="margin-bottom:20px;">Magrehistro ng bagong tagapamahala para sa enrollment dashboard.</p>

      <?php if ($error): ?>
        <div class="feedback-banner error" role="alert" style="padding:12px 16px;margin-bottom:20px;">
          <span>⚠</span> <?= e($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="feedback-banner success" role="alert" style="padding:14px 16px;margin-bottom:20px;">
          <div>
            <strong>✓ <?= e($success) ?></strong>
            <div style="margin-top:8px;">
              <a href="admin_login.php" class="btn btn-primary" style="min-height:38px;padding:0 16px;font-size:13px;">Pumunta sa Admin Login &rarr;</a>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <form method="post" action="create_admin.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group" style="margin-bottom:18px;">
          <label class="form-label" for="username">Gusto mong Username <span class="required-mark">*</span></label>
          <input 
            type="text" 
            id="username" 
            name="username" 
            class="form-input" 
            required 
            placeholder="hal. admin, alex, o principal"
            minlength="3"
            maxlength="80"
          >
          <div class="input-hint">Kahit anong username (minimum 3 characters).</div>
        </div>

        <div class="form-group" style="margin-bottom:24px;">
          <label class="form-label" for="password">Gusto mong Password <span class="required-mark">*</span></label>
          <input 
            type="password" 
            id="password" 
            name="password" 
            class="form-input" 
            required 
            placeholder="Ipasok ang ligtas na password"
            minlength="6"
          >
          <div class="input-hint">Hindi bababa sa 6 na characters.</div>
        </div>

        <button class="btn btn-primary btn-full" type="submit" style="margin-bottom:14px;">
          Gumawa ng Admin Account
        </button>

        <div style="text-align:center;font-size:13px;">
          <a href="admin_login.php" style="color:var(--color-trust-blue);font-weight:600;text-decoration:none;">
            May account na? Mag-sign in dito &rarr;
          </a>
        </div>
      </form>
    </div>

  </div>
</main>

<footer class="site-footer">
  ALS Connect • One-Time Admin Setup
</footer>

</body>
</html>
