<?php
// Early action handler for approving/rejecting events
// Runs before any HTML output (mapped in index.php $action_pages)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$lang = $_SESSION['lang'] ?? 'en';
$user = current_user();
global $pdo;

// Authorization
if (!$user || (int)$user['role_id'] !== 1) {
    $_SESSION['error_message'] = ($lang === 'ar')
        ? 'غير مصرح لك بتنفيذ هذا الإجراء.'
        : 'You are not authorized to perform this action.';
    header('Location: ' . BASE_URL . '/?page=login');
    exit;
}

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action']) || empty($_POST['event_id'])) {
    $_SESSION['error_message'] = ($lang === 'ar')
        ? 'طلب غير صالح.'
        : 'Invalid request.';
    header('Location: ' . BASE_URL . '/?page=admin_dashboard');
    exit;
}

$event_id = (int)$_POST['event_id'];
$action   = $_POST['action']; // 'approve' or 'reject'
$new_status = ($action === 'approve') ? 'approved' : 'rejected';

$success_msg = ($action === 'approve')
    ? (($lang === 'ar') ? 'تم اعتماد الفعالية بنجاح.' : 'Event approved successfully.')
    : (($lang === 'ar') ? 'تم رفض الفعالية بنجاح.' : 'Event rejected successfully.');

try {
    $stmt = $pdo->prepare("UPDATE events SET approval_status = ? WHERE id = ? AND approval_status = 'pending'");
    $stmt->execute([$new_status, $event_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = $success_msg;
    } else {
        // Check current status
        $check = $pdo->prepare('SELECT approval_status FROM events WHERE id = ?');
        $check->execute([$event_id]);
        $current = $check->fetchColumn();
        if ($current === $new_status) {
            $_SESSION['success_message'] = $success_msg; // Already in desired state
        } elseif ($current === false) {
            $_SESSION['error_message'] = ($lang === 'ar')
                ? 'معرف الفعالية غير موجود.'
                : 'Event ID does not exist.';
        } else {
            $_SESSION['error_message'] = ($lang === 'ar')
                ? 'لا يمكن تغيير حالة الفعالية.'
                : 'Event status could not be changed.';
        }
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = ($lang === 'ar')
        ? 'خطأ في قاعدة البيانات: ' . $e->getMessage()
        : 'Database error: ' . $e->getMessage();
}

// After action, go to admin dashboard as requested
header('Location: ' . BASE_URL . '/?page=admin_dashboard');
exit;
