<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../mailer.php';
$__schemaEnsured = false;
try {
    // Ensure users.is_active column exists
    $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    if ($colCheck && !$colCheck->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 0");
    }

    // Ensure email_verifications table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_verifications (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        email VARCHAR(150) NOT NULL,\n        code VARCHAR(20) NOT NULL,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n        UNIQUE KEY (email),\n        INDEX (code)\n    )");

    $__schemaEnsured = true;
} catch (Exception $e) {
    error_log('Schema ensure failed: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=register");
    exit;
}

$name     = trim($_POST['name'] ?? '');
$universityId = trim($_POST['university_id'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';
$role     = $_POST['role'] ?? 'user';

if ($password !== $confirm) {
    $_SESSION['error_message'] = ($_SESSION['lang'] == 'ar') ? 'كلمة السر ليست متشابهة' :'Passwords do not match.';
    header("Location: ?page=register");
    exit;
}

$role_id = ($role === 'organizer') ? 2 : 3;

// University ID required for students
if ($role_id === 3) {
    if ($universityId === '' || $universityId === null) {
        
        header("Location: ?page=register");
        exit;
    }
    // Validate: exactly 9 digits (digits only)
    if (!preg_match('/^\d{9}$/', $universityId)) {
        
        header("Location: ?page=register");
        exit;
    }
}

$stmt = $pdo->prepare("SELECT id, is_active, name FROM users WHERE email = ? OR university_id = ?");
$stmt->execute([$email, $universityId]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);
if ($existing) {
    if ((int)$existing['is_active'] === 1) {
        $_SESSION['error_message'] = ($_SESSION['lang'] == 'ar') ? 'البريد الإلكتروني موجود  أو الرقم الجامعي مجود مسبقا ' : 'This email or university ID is already registered.';
        header("Location: ?page=register");
        exit;
    }

    // User exists but not active: resend verification code
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $vstmt = $pdo->prepare("INSERT INTO email_verifications (email, code) VALUES (?, ?) ON DUPLICATE KEY UPDATE code = VALUES(code), created_at = CURRENT_TIMESTAMP");
    $vstmt->execute([$email, $code]);

    $lang = $_SESSION['lang'] ?? 'en';
    $recipientName = $existing['name'] ?: $name;
    if ($lang == 'ar') {
        $subject = 'كود التحقق';
        $body = "<div style='direction: rtl; text-align: right; font-family: Arial, sans-serif;'>\n                    <h3>كود التحقق</h3>\n                    <p>مرحباً " . htmlspecialchars($recipientName) . ",</p>\n                    <p>هذا هو كود التحقق الخاص بك: <strong>" . $code . "</strong></p>\n                    <p>أدخل هذا الكود لإكمال التسجيل.</p>\n                 </div>";
    } else {
        $subject = 'Verification Code';
        $body = "<div style='font-family: Arial, sans-serif;'>\n                    <h3>Verification Code</h3>\n                    <p>Hello " . htmlspecialchars($recipientName) . ",</p>\n                    <p>Your verification code is: <strong>" . $code . "</strong></p>\n                    <p>Enter this code to complete your registration.</p>\n                 </div>";
    }

    $sent = sendMail($email, $subject, $body);
    $_SESSION['pending_verification_email'] = $email;
    $_SESSION['success_message'] = $sent
        ? (($lang == 'ar') ? 'الحساب موجود ولم يتم التحقق منه — تم إرسال كود جديد.' : 'Account exists but not verified — a new code was sent.')
        : (($lang == 'ar') ? 'الحساب موجود ولم يتم التحقق منه — فشل إرسال البريد.' : 'Account exists but not verified — failed to send email.');

    header("Location: ?page=register_verify");
    exit;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);
// Create user as inactive and send verification code
// Ensure university_id column exists
if (!isset($__schemaEnsured) || $__schemaEnsured === false) {
    try {
        $colCheck2 = $pdo->query("SHOW COLUMNS FROM users LIKE 'university_id'");
        if ($colCheck2 && !$colCheck2->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN university_id VARCHAR(50) NULL DEFAULT NULL, ADD UNIQUE KEY uniq_university_id (university_id)");
        }
    } catch (Exception $e) {
        error_log('Schema ensure university_id failed: ' . $e->getMessage());
    }
}

$stmt = $pdo->prepare("INSERT INTO users (name,email,password,role_id,university_id,is_active) VALUES (?,?,?,?,?,0)");
if ($stmt->execute([$name, $email, $hashed, $role_id, $universityId])) {
    // generate 6-digit numeric code
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    // store verification code (upsert to avoid duplicates)
    $vstmt = $pdo->prepare("INSERT INTO email_verifications (email, code) VALUES (?, ?) ON DUPLICATE KEY UPDATE code = VALUES(code), created_at = CURRENT_TIMESTAMP");
    $vstmt->execute([$email, $code]);

    // send verification code by email (fallback to showing message if mail fails)
    $lang = $_SESSION['lang'] ?? 'en';
    if ($lang == 'ar') {
        $subject = 'كود التحقق';
        $body = "<div style='direction: rtl; text-align: right; font-family: Arial, sans-serif;'>\n                    <h3>كود التحقق</h3>\n                    <p>مرحباً " . htmlspecialchars($name) . ",</p>\n                    <p>هذا هو كود التحقق الخاص بك: <strong>" . $code . "</strong></p>\n                    <p>أدخل هذا الكود لإكمال التسجيل.</p>\n                 </div>";
    } else {
        $subject = 'Verification Code';
        $body = "<div style='font-family: Arial, sans-serif;'>\n                    <h3>Verification Code</h3>\n                    <p>Hello " . htmlspecialchars($name) . ",</p>\n                    <p>Your verification code is: <strong>" . $code . "</strong></p>\n                    <p>Enter this code to complete your registration.</p>\n                 </div>";
    }

    $sent = sendMail($email, $subject, $body);

    // store pending email in session for verification step
    $_SESSION['pending_verification_email'] = $email;

    if ($sent) {
        $_SESSION['success_message'] = ($lang == 'ar') ? 'تم إرسال كود التحقق إلى بريدك الإلكتروني.' : 'A verification code was sent to your email.';
    } else {
        $_SESSION['success_message'] = ($lang == 'ar') ? 'تم إنشاء الحساب. لم نتمكن من إرسال البريد الإلكتروني — تحقق من البريد أو أعد طلب الكود.' : 'Account created. We could not send the email — please check your email or request a new code.';
    }

    header("Location: ?page=register_verify");
    exit;
} else {
    $_SESSION['error_message'] = ($_SESSION['lang'] == 'ar') ? ' . فشل التسجيل. يُرجى المحاولة مرة أخرى' : 'Registration failed. Please try again.';
    header("Location: ?page=register");
    exit;
}

