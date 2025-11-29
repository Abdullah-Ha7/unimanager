<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../mailer.php'; 
require_once __DIR__ . '/../vendor/autoload.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=forgot");
    exit;
}

$language = $_SESSION['lang'] ?? 'en';

$email = trim($_POST['email'] ?? '');
if (empty($email)) {
    $_SESSION['error_message'] = 'Please enter your email address.';
    header("Location: ?page=forgot");
    exit;
}

// Check if user exists
$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error_message'] = 'No account found with that email.';
    header("Location: ?page=forgot");
    exit;
}

// Create reset token
$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Store token
$stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, created_at, expires_at) VALUES (?, ?, NOW(), ?)");
$stmt->execute([$user['id'], $token, $expires_at]);

// Build reset link
$reset_link = BASE_URL . "/?page=reset_password&token=" . urlencode($token);

// Send email via PHPMailer
$mail = new PHPMailer(true);
$email_sent = sendResetPassword(
    $user['email'],    // 1. البريد الإلكتروني (الوسيط $to)
    $user['name'],     // 2. اسم المستخدم (الوسيط $user_name)
    $reset_link,       // 3. رابط إعادة التعيين (الوسيط $reset_link)
    $language          // 4. اللغة (الوسيط $language)
);
    if ($email_sent) {
         $_SESSION['success_message'] = $_SESSION['lang'] == 'ar' ? '  لقد تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني 📧 ' : ' 📧 Password reset link has been sent to your email.';
            header("Location: ?page=forgot");
            exit;

    }  else {
    $_SESSION['error_message'] = "Mailer Error: {$mail->ErrorInfo}";
    header("Location: ?page=forgot");
    exit;
}

   

