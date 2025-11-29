<?php
// Early action handler for admin event edit submissions
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$lang = $_SESSION['lang'] ?? 'en';
$user = current_user();
global $pdo;

// Guard: admin only
if (!$user || (int)$user['role_id'] !== 1) {
    $_SESSION['error_message'] = ($lang === 'ar') ? 'غير مصرح لك بالوصول.' : 'Not authorized.';
    header('Location: ' . BASE_URL . '/?page=login');
    exit;
}

// Require POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/?page=manage_all_events');
    exit;
}

$event_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$title    = trim($_POST['title'] ?? '');
$desc     = trim($_POST['description'] ?? '');
$date     = $_POST['date'] ?? '';
$location = trim($_POST['location'] ?? '');
$status   = $_POST['approval_status'] ?? '';

if (!$event_id || !$title || !$desc || !$date || !$location) {
    $_SESSION['error_message'] = ($lang === 'ar') ? 'الرجاء تعبئة جميع الحقول.' : 'Please fill in all fields.';
    header('Location: ' . BASE_URL . '/?page=admin_edit_event&id=' . $event_id);
    exit;
}

$valid_statuses = ['pending','approved','rejected'];
if (!in_array($status, $valid_statuses, true)) {
    $status = 'pending';
}

try {
    $stmt = $pdo->prepare('UPDATE events SET title = ?, description = ?, date = ?, location = ?, approval_status = ? WHERE id = ?');
    $stmt->execute([$title, $desc, $date, $location, $status, $event_id]);
    $_SESSION['success_message'] = ($lang === 'ar') ? 'تم تحديث الفعالية بنجاح.' : 'Event updated successfully.';
    header('Location: ' . BASE_URL . '/?page=manage_all_events');
    exit;
} catch (PDOException $e) {
    $_SESSION['error_message'] = ($lang === 'ar') ? ('خطأ في قاعدة البيانات: ' . $e->getMessage()) : ('Database error: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/?page=admin_edit_event&id=' . $event_id);
    exit;
}
