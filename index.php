<?php
// C:\wamp64\www\Project-2\index.php

require_once 'config.php';
require_once 'functions.php';

// بدء الجلسة وتحميل إعدادات اللغة
if (session_status() === PHP_SESSION_NONE) session_start();
$lang = $_SESSION['lang'] ?? 'en';
require_once __DIR__ . "/lang/$lang.php";

// ✅ 1. جلب اسم الصفحة
$page = $_GET['page'] ?? 'home';

// =========================================================
// ✅ 2. قائمة صفحات المسؤول (مصفوفة ترابطية: اسم الصفحة => المسار النسبي)
// =========================================================
$admin_pages = [
  'admin_dashboard'     => 'admin/admin_dashboard.php',
  'manage_users'        => 'admin/manage_users.php',
  'manage_all_events'   => 'admin/manage_all_events.php',
  'admin_edit_event'    => 'admin/admin_edit_event.php',     
  'approve_events'      => 'admin/approve_events.php', 
  'admin_delete_event'  => 'admin/admin_delete_event.php',   
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
  'login_action',
  'logout',
  'profile',
  'student_dashboard',
  'reply_review', // الهدف الذي نبحث عنه
  'my_bookings',
  'forgot',
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

?>
