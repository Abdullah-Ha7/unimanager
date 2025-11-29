<?php
// C:\wamp64\www\Project-2\pages\logout.php
// ملف تسجيل الخروج - نظيف وآمن بالنسبة للـ headers

// 0) تفعيل Buffer مبكراً لضمان إمكانية استخدام header() حتى لو طُبع شيء سابقاً
if (function_exists('ob_start') && !ob_get_level()) {
    ob_start();
}

// 1) استدعاء الملفات الأساسية (لا نستدعي header.php هنا)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// 1.1) دالة توجيه آمنة: تنظف جميع الـ buffers قبل إرسال Location
if (!function_exists('safe_redirect')) {
    function safe_redirect($url) {
        if (function_exists('ob_get_level')) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
        header('Location: ' . $url);
        exit;
    }
}

// 2) الاحتفاظ باللغة الحالية
$lang = $_SESSION['lang'] ?? 'en';

// 3) تنظيف الجلسة دون إنهائها بالكامل حتى نتمكن من وضع رسالة نجاح ولغة
session_unset(); // يحذف كل المتغيرات لكن يُبقي الجلسة قائمة
$_SESSION['lang'] = $lang; // إعادة اللغة

// 4) إعداد رسالة النجاح
$_SESSION['success_message'] = ($lang == 'ar')
    ? ' تم تسجيل خروجك بنجاح.'
    : ' You have been successfully logged out.';

// 5) التوجيه إلى صفحة تسجيل الدخول
safe_redirect(BASE_URL . '/?page=login');
// لا يوجد وسم إغلاق ?>