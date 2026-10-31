<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

// Seamless redirect to Unified Dynamic Role Login
header('Location: login.php?role=admin');
exit;
