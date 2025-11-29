<?php
// Early action handler for managing users role updates and access guard
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$lang = $_SESSION['lang'] ?? 'en';
$user = current_user();
global $pdo;

// Access guard: only admins can proceed
if (!$user || (int)$user['role_id'] !== 1) {
    $_SESSION['error_message'] = ($lang === 'ar')
        ? 'غير مصرح لك بالوصول إلى إدارة المستخدمين.'
        : 'You are not authorized to access user management.';
    header('Location: ' . BASE_URL . '/?page=login');
    exit;
}

// Handle user deletion (organizer or student)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $target_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    if ($target_user_id <= 0) {
        $_SESSION['error_message'] = ($lang === 'ar') ? 'معرف المستخدم غير صالح.' : 'Invalid user ID.';
        header('Location: ' . BASE_URL . '/?page=manage_users');
        exit;
    }

    if ($target_user_id === (int)$user['id']) {
        $_SESSION['error_message'] = ($lang === 'ar') ? 'لا يمكنك حذف حسابك الإداري.' : 'You cannot delete your own admin account.';
        header('Location: ' . BASE_URL . '/?page=manage_users');
        exit;
    }

    try {
        $pdo->beginTransaction();
        // Fetch role of target user
        $roleStmt = $pdo->prepare('SELECT role_id FROM users WHERE id = ?');
        $roleStmt->execute([$target_user_id]);
        $targetRole = (int)$roleStmt->fetchColumn();

        if (!in_array($targetRole, [2,3], true)) { // only organizer or student
            $pdo->rollBack();
            $_SESSION['error_message'] = ($lang === 'ar') ? 'يمكن حذف المنظمين أو الطلاب فقط.' : 'Only organizer or student accounts can be deleted.';
            header('Location: ' . BASE_URL . '/?page=manage_users');
            exit;
        }

        if ($targetRole === 2) { // Organizer: remove related events, bookings, reviews
            // Get organizer event IDs
            $evStmt = $pdo->prepare('SELECT id FROM events WHERE organizer_id = ?');
            $evStmt->execute([$target_user_id]);
            $eventIds = $evStmt->fetchAll(PDO::FETCH_COLUMN);
            if ($eventIds) {
                $inPlaceholders = implode(',', array_fill(0, count($eventIds), '?'));
                // Delete bookings tied to those events
                $delBkEv = $pdo->prepare("DELETE FROM bookings WHERE event_id IN ($inPlaceholders)");
                $delBkEv->execute($eventIds);
                // Delete reviews tied to those events
                $delRvEv = $pdo->prepare("DELETE FROM reviews WHERE event_id IN ($inPlaceholders)");
                $delRvEv->execute($eventIds);
                // Delete events themselves
                $delEv = $pdo->prepare("DELETE FROM events WHERE id IN ($inPlaceholders)");
                $delEv->execute($eventIds);
            }
        }

        // Delete user's own bookings & reviews (student or organizer)
        $delBkUser = $pdo->prepare('DELETE FROM bookings WHERE user_id = ?');
        $delBkUser->execute([$target_user_id]);
        $delRvUser = $pdo->prepare('DELETE FROM reviews WHERE user_id = ?');
        $delRvUser->execute([$target_user_id]);

        // Finally delete user
        $delUser = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $delUser->execute([$target_user_id]);
        $pdo->commit();

        $_SESSION['success_message'] = ($lang === 'ar') ? 'تم حذف المستخدم وكل بياناته المرتبطة.' : 'User and related data deleted successfully.';
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('User deletion failed: ' . $e->getMessage());
        $_SESSION['error_message'] = ($lang === 'ar') ? 'فشل حذف المستخدم: ' . $e->getMessage() : 'Failed to delete user: ' . $e->getMessage();
    }

    header('Location: ' . BASE_URL . '/?page=manage_users');
    exit;
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $target_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $new_role_id    = isset($_POST['new_role']) ? (int)$_POST['new_role'] : 0;

    if ($target_user_id === (int)$user['id']) {
        $_SESSION['error_message'] = ($lang === 'ar')
            ? 'لا يمكنك تغيير دورك الخاص.'
            : 'You cannot change your own role.';
    } elseif (!in_array($new_role_id, [1, 2, 3], true)) {
        $_SESSION['error_message'] = ($lang === 'ar')
            ? 'دور غير صالح.'
            : 'Invalid role selected.';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE users SET role_id = ? WHERE id = ?');
            $stmt->execute([$new_role_id, $target_user_id]);
            $_SESSION['success_message'] = ($lang === 'ar')
                ? 'تم تحديث دور المستخدم بنجاح.'
                : 'User role updated successfully.';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = ($lang === 'ar')
                ? 'خطأ في قاعدة البيانات: ' . $e->getMessage()
                : 'Database error: ' . $e->getMessage();
        }
    }

    header('Location: ' . BASE_URL . '/?page=manage_users');
    exit;
}

// Fallback: return to manage users if reached without proper POST
header('Location: ' . BASE_URL . '/?page=manage_users');
exit;
?>
