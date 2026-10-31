<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Escape HTML output securely.
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate or get current CSRF token.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden CSRF input field.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify CSRF token from POST requests.
 */
function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        exit('Invalid request token (CSRF). Pakisubukang i-refresh ang pahina at magsumite muli.');
    }
}

/**
 * Get current authenticated user's role: 'admin', 'teacher', or 'student'.
 */
function current_user_role(): ?string {
    if (!empty($_SESSION['user_role'])) {
        $r = (string)$_SESSION['user_role'];
        return ($r === 'im') ? 'teacher' : $r;
    }
    // Backward compatibility for existing admin sessions
    if (!empty($_SESSION['admin_id'])) {
        $_SESSION['user_role'] = 'admin';
        return 'admin';
    }
    return null;
}

/**
 * Check if a user is currently logged in.
 */
function is_logged_in(): bool {
    return current_user_role() !== null;
}

/**
 * Get the target dashboard URL for a specific role.
 */
function get_role_dashboard(string $role): string {
    $r = ($role === 'im') ? 'teacher' : $role;
    return match ($r) {
        'admin' => 'admin_dashboard.php',
        'teacher' => 'teacher_dashboard.php',
        'student' => 'student_dashboard.php',
        default => 'login.php'
    };
}

/**
 * Redirect an already logged-in user to their respective dashboard.
 */
function redirect_if_logged_in(): void {
    $role = current_user_role();
    if ($role !== null) {
        header('Location: ' . get_role_dashboard($role));
        exit;
    }
}

/**
 * Enforce that the user is logged in as an Administrator / Coordinator.
 * Redirects unauthorized users to their own dashboard or to the admin login.
 */
function require_admin(): void {
    $role = current_user_role();
    if ($role === null) {
        header('Location: login.php?role=admin&msg=auth_required');
        exit;
    }
    if ($role === 'teacher') {
        header('Location: teacher_dashboard.php?error=unauthorized_admin');
        exit;
    }
    if ($role === 'student') {
        header('Location: student_dashboard.php?error=unauthorized_admin');
        exit;
    }
}

/**
 * Enforce that the user is logged in as an Instructional Manager (IM / Teacher).
 * Administrators are permitted access for oversight.
 */
function require_teacher(): void {
    $role = current_user_role();
    if ($role === null) {
        header('Location: login.php?role=teacher&msg=auth_required');
        exit;
    }
    if ($role === 'student') {
        header('Location: student_dashboard.php?error=unauthorized_teacher');
        exit;
    }
}

function require_im(): void {
    require_teacher();
}

/**
 * Enforce that the user is logged in as an ALS Student / Learner.
 * Administrators and Teachers are permitted access for testing and inspection.
 */
function require_student(): void {
    $role = current_user_role();
    if ($role === null) {
        header('Location: login.php?role=student&msg=auth_required');
        exit;
    }
}

/**
 * Active Sidebar Item Helper
 */
function is_active_nav(string $path): string {
    $curr = basename($_SERVER['PHP_SELF'] ?? '');
    return ($curr === $path) ? 'active' : '';
}

/**
 * Automatically generate sequential ID number from 2026-000-001 up to 2026-000-040.
 */
function get_next_sequential_student_id(PDO $pdo): string {
    $stmt = $pdo->query("SELECT student_number FROM students WHERE student_number LIKE '2026-000-%' ORDER BY student_number DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last) {
        $parts = explode('-', (string)$last);
        $seq = (int)end($parts);
        $next = $seq + 1;
    } else {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
        $next = max(1, $count + 1);
    }
    return sprintf('2026-000-%03d', min($next, 40));
}

/**
 * Automatically send login credentials via Email Notification to a newly created Student or Teacher account.
 * Also logs the dispatch into the `email_notifications` table for auditing and inspection.
 */
function send_account_credentials_email(
    PDO $pdo,
    string $recipientName,
    string $recipientEmail,
    string $role,
    string $tempPassword,
    ?string $identifier = null
): array {
    $roleTitle = ($role === 'teacher') ? 'Dalubguro / Instructional Manager (IM)' : 'Mag-aaral / ALS Learner';
    $loginUser = $identifier ?: $recipientEmail;
    $portalUrl = (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : 'http://localhost') . '/als_enrollment_system/login.php?role=' . urlencode($role);

    $subject = "DEPED ALS - Maligayang Pagdating! Ang iyong Login Credentials (" . strtoupper($role) . ")";
    
    $bodyHtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>DepEd ALS Account Credentials</title>
</head>
<body style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;background-color:#F4F7FC;margin:0;padding:24px;color:#0F172A;">
  <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width:600px;margin:0 auto;background:#FFFFFF;border-radius:14px;overflow:hidden;border:1px solid #CBD5E1;box-shadow:0 8px 24px rgba(15,23,42,0.06);">
    <tr>
      <td style="background:linear-gradient(135deg, #07162C 0%, #0B1E3B 100%);padding:24px 30px;border-bottom:4px solid #F4BB4F;text-align:center;">
        <h1 style="color:#FFFFFF;margin:0;font-size:20px;letter-spacing:0.04em;">DEPED ALS &bull; ALTERNATIVE LEARNING SYSTEM</h1>
        <p style="color:#CBD5E1;margin:6px 0 0 0;font-size:12px;">Learning for All &bull; San Nicolas Community Learning Center</p>
      </td>
    </tr>
    <tr>
      <td style="padding:28px 32px;">
        <h2 style="font-size:17px;color:#0B1E3B;margin-top:0;">Magandang araw, {$recipientName}!</h2>
        <p style="font-size:13px;line-height:1.6;color:#475569;">
          Matagumpay na nagawa ang iyong opisyal na account sa <strong>DEPED ALS Enrollment and Learning Management System</strong> bilang <strong>{$roleTitle}</strong>.
        </p>

        <table width="100%" style="background:#F8FAFD;border:1.5px solid #C4DDF8;border-radius:10px;padding:16px 20px;margin:20px 0;">
          <tr>
            <td style="padding-bottom:10px;font-size:11px;color:#64748B;font-weight:700;text-transform:uppercase;">Iyong Login Credentials:</td>
          </tr>
          <tr>
            <td style="font-size:13px;padding:4px 0;"><strong>Email Address / Username:</strong> <span style="font-family:ui-monospace,monospace;color:#185ADB;font-weight:700;">{$loginUser}</span></td>
          </tr>
          <tr>
            <td style="font-size:13px;padding:4px 0;"><strong>Temporary Password:</strong> <span style="font-family:ui-monospace,monospace;background:#FEF3C7;color:#92400E;padding:2px 8px;border-radius:4px;font-weight:800;">{$tempPassword}</span></td>
          </tr>
          <tr>
            <td style="font-size:12.5px;padding:4px 0;"><strong>Role / Portal:</strong> <span style="font-weight:700;color:#107C41;">{$roleTitle}</span></td>
          </tr>
        </table>

        <p style="font-size:12.5px;line-height:1.5;color:#64748B;">
          ⚠️ <em>Mahalagang Paalala:</em> Para sa iyong seguridad, gamitin agad ang temporary password na ito upang mag-log in, at palitan ito sa lalong madaling panahon.
        </p>

        <div style="text-align:center;margin:26px 0 10px 0;">
          <a href="{$portalUrl}" style="background:#185ADB;color:#FFFFFF;padding:12px 28px;text-decoration:none;border-radius:8px;font-weight:750;font-size:13px;display:inline-block;box-shadow:0 4px 12px rgba(24,90,219,0.25);">
            Pumunta sa Login Portal &rarr;
          </a>
        </div>
      </td>
    </tr>
    <tr>
      <td style="background:#F1F5F9;padding:16px 30px;border-top:1px solid #E2E8F0;font-size:11px;color:#64748B;text-align:center;">
        &copy; 2026 DEPED ALS &ndash; Alternative Learning System. Lahat ng karapatan ay nakalaan.<br>
        Ito ay awtomatikong mensahe mula sa sistema. Huwag direktang sumagot dito.
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: DepEd ALS Portal <no-reply@deped.gov.ph>\r\n";
    $headers .= "Reply-To: support@deped.gov.ph\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $mailSent = false;
    try {
        $mailSent = @mail($recipientEmail, $subject, $bodyHtml, $headers);
    } catch (\Throwable $t) {
        $mailSent = false;
    }

    $status = $mailSent ? 'Sent' : 'Logged';

    try {
        $ins = $pdo->prepare('INSERT INTO email_notifications (recipient_name, recipient_email, role, temporary_password, subject, body_html, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $ins->execute([$recipientName, $recipientEmail, $role, $tempPassword, $subject, $bodyHtml, $status]);
    } catch (\Throwable $e) {
    }

    return [
        'success' => true,
        'mail_sent' => $mailSent,
        'status' => $status,
        'subject' => $subject,
        'email' => $recipientEmail,
        'temp_pass' => $tempPassword
    ];
}
