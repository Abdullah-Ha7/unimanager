<?php
// ✅ Start session only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Default language (English if none set yet)
if (empty($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// ✅ Base URL (use current host so QR works on mobile)
// This builds BASE_URL from the incoming request host/IP
// Example on LAN: http://192.168.1.50/Project-3
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $scheme . '://' . $host . '/Project-3');
// Secret key for signing/verifying tokens (QR verification)
if (!defined('SECRET_KEY')) {
    define('SECRET_KEY', 'change-this-secret-ksu-2025');
}

// ✅ Database connection
$dbHost = 'localhost';
$dbName = 'unimanager';
$dbUser = 'root';
$dbPass = ''; // Set your MySQL password if needed

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("⚠️ Database connection failed: " . $e->getMessage());
}

// ✅ Email setup (for password recovery / booking confirmation)
define('MAIL_HOST', 'smtp.gmail.com');  // e.g., smtp.gmail.com
define('MAIL_USERNAME', 'ksu19577@gmail.com');  // your real email
define('MAIL_PASSWORD', 'shzdjhztzzigabna');    // Gmail App Password
define('MAIL_FROM', 'ksu19577@gmail.com');
define('MAIL_FROM_NAME', 'University Events');
?>

