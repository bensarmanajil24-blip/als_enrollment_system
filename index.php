<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

// If already authenticated, redirect straight to role dashboard
if (is_logged_in()) {
    redirect_if_logged_in();
}

// Redirect entry point immediately to Unified Role-based Sign In
header('Location: login.php');
exit;
