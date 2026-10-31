<?php
declare(strict_types=1);

/**
 * Standard Admin Header Layout Component
 * Variables expected:
 *  - $pageTitle (string): Browser title
 *  - $pageWatermark (string, optional): Watermark text above layout
 */
$pageTitle = $pageTitle ?? 'DepEd ALS - Admin Portal';
$pageWatermark = $pageWatermark ?? null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="assets/admin.css">
  <link rel="stylesheet" href="assets/admin_theme.css">
</head>
<body class="admin-body">

<?php if ($pageWatermark): ?>
  <div class="page-watermark">
    <?= htmlspecialchars($pageWatermark) ?>
  </div>
<?php endif; ?>

<div class="deped-laptop-layout">

