<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

echo "Setting up password_resets table and credentials...\n";

// 1. password_resets table
$pdo->exec("
CREATE TABLE IF NOT EXISTS password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  token VARCHAR(100) NOT NULL UNIQUE,
  role VARCHAR(50) NOT NULL DEFAULT 'admin',
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reset_token (token),
  INDEX idx_reset_email (email)
) ENGINE=InnoDB;
");
echo "password_resets table created/verified.\n";

// 2. Add admin@als.local / ALSAdmin@2026! from screenshot 3 if not present
$alsPassHash = password_hash('ALSAdmin@2026!', PASSWORD_BCRYPT);
$check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
$check->execute(['admin@als.local', 'admin@als.local']);
if (!$check->fetch()) {
    $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')")
        ->execute(['admin@als.local', 'admin@als.local', $alsPassHash]);
    echo "Added admin@als.local (password: ALSAdmin@2026!) into users.\n";
} else {
    $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@als.local'")
        ->execute([$alsPassHash]);
}

// In admins table as well
$checkAdmin = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
$checkAdmin->execute(['admin@als.local']);
if (!$checkAdmin->fetch()) {
    $pdo->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)")
        ->execute(['admin_als', 'admin@als.local', $alsPassHash]);
    echo "Added admin@als.local into admins table.\n";
}

echo "Setup completed successfully!\n";

