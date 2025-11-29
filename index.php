<?php
// C:\wamp64\www\Project-2\index.php

// Enable output buffering early to allow header() redirects from included pages
if (function_exists('ob_start') && !ob_get_level()) {
    ob_start();
}

require_once 'config.php';
require_once 'functions.php';

// بدء الجلسة وتحميل إعدادات اللغة
if (session_status() === PHP_SESSION_NONE) session_start();
$lang = $_SESSION['lang'] ?? 'en';
require_once __DIR__ . "/lang/$lang.php";

// ✅ 1. جلب اسم الصفحة
$page = $_GET['page'] ?? 'home';

// Handle action-style pages BEFORE any output (they may send headers/redirects)
// Use explicit mapping to avoid wrong directory assumptions (admin_delete_event lives in /admin)
$action_pages = [
    'login_action'      => __DIR__ . '/pages/login_action.php',
    'register_action'   => __DIR__ . '/pages/register_action.php',
    'forgot_action'     => __DIR__ . '/pages/forgot_action.php',
    'logout'            => __DIR__ . '/pages/logout.php',
    'admin_delete_event'=> __DIR__ . '/admin/admin_delete_event.php',
    'admin_approve_event'=> __DIR__ . '/admin/admin_approve_event.php',
    'admin_manage_users_action'=> __DIR__ . '/admin/admin_manage_users_action.php',
    'admin_edit_event_action'=> __DIR__ . '/admin/admin_edit_event_action.php',
    // Booking is an action (no UI), run before header output
    'book'              => __DIR__ . '/pages/book.php',
    
];
if (isset($action_pages[$page]) && file_exists($action_pages[$page])) {
    require $action_pages[$page];
    // action pages are self-contained; stop further rendering
    return;
}

// Handle cancellation POST for student dashboard BEFORE header output to avoid 'headers already sent'
if ($page === 'student_dashboard' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $user = current_user();
    if ($user && $user['role_id'] == 3) {
        $booking_id = intval($_POST['booking_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$booking_id, $user['id']])) {
                $_SESSION['success_message'] = lang('booking_cancel_success');
            } else {
                $_SESSION['error_message'] = lang('booking_cancel_failed');
            }
        } catch (PDOException $e) {
            error_log("Database error during booking cancellation: " . $e->getMessage());
            $_SESSION['error_message'] = lang('database_error_cancellation');
        }
    } else {
        $_SESSION['error_message'] = 'Unauthorized cancellation attempt';
    }
    header('Location: ?page=student_dashboard');
    exit;
}

// Run pre-processing for reviews page BEFORE header to allow redirects
if ($page === 'reviews') {
    require __DIR__ . '/pages/reviews_pre.php';
}

// Pre-process event detail before any output (avoid headers already sent)
if ($page === 'event_detail') {
    require __DIR__ . '/pages/event_detail_pre.php';
}

// Prepare page-specific CSS before loading the header
$extra_css = [];
$pageCssFile = __DIR__ . "/assets/css/{$page}.css";
if (file_exists($pageCssFile)) {
    $extra_css[] = $page; // header.php will include /assets/css/{name}.css
}

// =========================================================
// ✅ 2. قائمة صفحات المسؤول (مصفوفة ترابطية: اسم الصفحة => المسار النسبي)
// =========================================================
$admin_pages = [
    'admin_dashboard'     => 'admin/admin_dashboard.php',
    'manage_users'        => 'admin/manage_users.php',
    'manage_all_events'   => 'admin/manage_all_events.php',
    'admin_edit_event'    => 'admin/admin_edit_event.php',     
    'approve_events'      => 'admin/approve_events.php', 
    // removed admin_delete_event (now action page to avoid header sent warnings)
    'system_config'       => 'admin/system_config.php',
];

// =========================================================
// ✅ 3. قائمة الصفحات العادية (مصفوفة بسيطة: تحتوي على الأسماء فقط)
// =========================================================
$allowed_pages_simple = [
  'home',
  'events',
  'event_detail',
  'book', 
  'register',
  'register_action',
  'login',
  'verify_booking',
  'login_action',
  'logout',
  'profile',
  'student_dashboard',
  'reply_review', // الهدف الذي نبحث عنه
  'my_bookings',
  'forgot',
  'register_verify',
  'forgot_action',
  'reset_password',
  'edit_event' , 
  'delete_event',
  'organizer_dashboard',
  'create_event',
  'reviews',
  
];

// ✅ دمج جميع الصفحات المسموحة (لأغراض التحقق)
$allowed_keys = array_merge(array_keys($admin_pages), $allowed_pages_simple);

// ✅ Default page fallback: العودة إلى 'home' إذا كانت الصفحة غير مسموحة
if (!in_array($page, $allowed_keys)) {
    $page = 'home';
}

// =========================================================
// ✅ 4. منطق تحديد المسار الديناميكي المُحسّن
// =========================================================

require_once 'header.php';

if (array_key_exists($page, $admin_pages)) {
    // 1. إذا كانت صفحة إدارة، استخدم المسار المحدد في المصفوفة الترابطية
    $pagePath = __DIR__ . '/' . $admin_pages[$page];
} else {
    // 2. إذا كانت صفحة عادية، افترض أنها في مجلد /pages/
    $pagePath = __DIR__ . "/pages/{$page}.php";
}

// =========================================================
// ✅ 5. التحقق النهائي والإدراج
// =========================================================
if (file_exists($pagePath)) {
    include $pagePath;
} else {
    // إذا لم يتم العثور على الملف، العودة إلى home
    error_log("File not found for page: {$pagePath}");
    include __DIR__ . "/pages/home.php"; 
}

require_once 'footer.php';

// Flush output buffer at the very end
if (function_exists('ob_get_level') && ob_get_level()) {
    ob_end_flush();
}

?>
