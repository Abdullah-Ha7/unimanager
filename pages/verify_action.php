<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/?page=register_verify');
    exit;
}

$lang = $_SESSION['lang'] ?? 'en';

$email = trim($_POST['email'] ?? '');
$code = trim($_POST['code'] ?? '');

if ($email === '' || $code === '') {
    $_SESSION['error_message'] = ($lang === 'ar') ? 'الرجاء إدخال البريد الإلكتروني ورمز التحقق.' : 'Please enter email and verification code.';
    header('Location: ' . BASE_URL . '/?page=register_verify');
    exit;
}

$stmt = $pdo->prepare('SELECT id, role_id, verification_code, email_verified_at FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error_message'] = ($lang === 'ar') ? 'الحساب غير موجود.' : 'Account not found.';
    header('Location: ' . BASE_URL . '/?page=register_verify');
    exit;
}

if (empty($user['verification_code']) || strcasecmp($user['verification_code'], $code) !== 0) {
    $_SESSION['error_message'] = ($lang === 'ar') ? 'رمز التحقق غير صحيح.' : 'Invalid verification code.';
    header('Location: ' . BASE_URL . '/?page=register_verify&email=' . urlencode($email));
    exit;
}

// Mark verified: clear code, set timestamp, activate
$stmt = $pdo->prepare('UPDATE users SET verification_code = NULL, email_verified_at = NOW(), is_active = 1 WHERE id = ?');
$stmt->execute([$user['id']]);

$_SESSION['success_message'] = ($lang === 'ar') ? 'تم التحقق من الحساب بنجاح. يمكنك تسجيل الدخول الآن.' : 'Account verified successfully. You can now log in.';
unset($_SESSION['pending_verification_email']);

header('Location: ' . BASE_URL . '/?page=login');
exit;
?>
