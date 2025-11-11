<?php
// C:\wamp64\www\unimanager\admin\admin_delete_event.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$lang = $_SESSION['lang'] ?? 'en';
require_once __DIR__ . "/../lang/$lang.php";

$user = current_user();
global $pdo;

// 1. التحقق من الصلاحية (مسؤول: role_id = 1) وطريقة الطلب (POST)
if (!$user || $user['role_id'] != 1 || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = lang('unauthorized_access');
    header("Location: " . BASE_URL . "/?page=login");
    exit;
}

$event_id = intval($_POST['event_id'] ?? 0);

if (!$event_id) {
    $_SESSION['error_message'] = lang('invalid_event_id');
    header("Location: " . BASE_URL . "/?page=manage_all_events");
    exit;
}

// 2. تنفيذ عملية الحذف (المسؤول يحذف أي شيء)
try {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = lang('event_deleted_success');
    } else {
        $_SESSION['error_message'] = lang('event_delete_failed');
    }
    
} catch (PDOException $e) {
    error_log("DB Error deleting event by admin: " . $e->getMessage());
    $_SESSION['error_message'] = lang('database_error');
}

// 3. التوجيه إلى لوحة التحكم
header("Location: " . BASE_URL . "/?page=manage_all_events");
exit;
?>