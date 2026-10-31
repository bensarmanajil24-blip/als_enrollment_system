<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
$number = $_SESSION['registration_number'] ?? null;
unset($_SESSION['registration_number']);

if (!$number) {
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Naisumite na ang Aplikasyon - ALS Connect</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="site-header">
  <div class="header-inner">
    <a class="site-brand" href="index.php">
      <div class="brand-badge">ALS</div>
      <div>
        <div class="brand-name">ALS Connect</div>
        <span class="brand-tag">Alternative Learning System</span>
      </div>
    </a>
  </div>
</header>

<main class="container narrow">
  <section class="card-panel success-card">
    <div class="success-mark">✓</div>
    
    <span class="eyebrow" style="color:var(--color-fresh-green);">NAISUMITE NA ANG APLIKASYON</span>
    <h1 class="page-heading">Matagumpay na natanggap ang iyong aplikasyon!</h1>
    
    <p class="body-lead" style="max-width:580px;margin:0 auto;">
      Naisumite na ang aplikasyon. Itabi ang example tracking number. Naghihintay pa ito ng review; hindi pa kumpirmadong enrollment.
    </p>

    <!-- Tracking Number Display Box -->
    <div class="tracking-box">
      <div class="tracking-box-label">Iyong Tracking Number</div>
      <div class="tracking-box-number"><?= e($number) ?></div>
      <div class="muted-text" style="margin-top:6px;">Gamitin ang number na ito kapag makikipag-ugnayan sa ALS Coordinator.</div>
    </div>

    <!-- Guidance Banner -->
    <div class="feedback-banner warning" style="text-align:left;max-width:580px;margin:0 auto 28px;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#B07406;flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <div>
        <strong>Kumpirmahin muna ang requirements.</strong>
        <div>Ang pinakamalapit na Community Learning Center (CLC) ang magbibigay ng iskedyul para sa pagpapatunay ng mga dokumento bago ang pormal na pagsisimula ng klase.</div>
      </div>
    </div>

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a class="btn btn-primary" href="index.php" style="min-width:200px;">Bumalik sa Homepage</a>
      <button class="btn btn-secondary" onclick="window.print();">I-print ang Resibo</button>
    </div>
  </section>
</main>

<footer class="site-footer">
  ALS Connect • Alternative Learning System • Hindi opisyal na DepEd portal, policy, o branding guide.
</footer>

</body>
</html>
