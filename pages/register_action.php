<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=register");
    exit;
}

$name     = trim($_POST['name'] ?? '');
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

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $_SESSION['error_message'] = ($_SESSION['lang'] == 'ar') ? 'البريد الإلكتروني موجود مسبقا ' :'This email is already registered.';
    header("Location: ?page=register");
    exit;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (name,email,password,role_id) VALUES (?,?,?,?)");
if ($stmt->execute([$name, $email, $hashed, $role_id])) {
    $_SESSION['success_message'] =($_SESSION['lang'] == 'ar') ? ' . تم التسجيل بنجاح! يمكنك الآن تسجيل الدخول' : 'Registration successful! You can now log in.';
    header("Location: ?page=login");
    exit;
} else {
    $_SESSION['error_message'] = ($_SESSION['lang'] == 'ar') ? ' . فشل التسجيل. يُرجى المحاولة مرة أخرى' : 'Registration failed. Please try again.';
    header("Location: ?page=register");
    exit;
}

