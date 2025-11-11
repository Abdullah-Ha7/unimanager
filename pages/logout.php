<?php
// C:\wamp64\www\Project-2\pages\logout.php
// ملف تسجيل الخروج - يجب أن يكون نظيفاً تماماً لتجنب مشكلة الـ headers

// **ملاحظة هامة:** يجب أن يتم استدعاء session_start() في config.php أو index.php قبل تضمين هذا الملف.

// 1. استدعاء الملفات الأساسية (نتجنب استدعاء header.php هنا لمنع طباعة HTML)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// 2. تحديث متغير اللغة (قد نحتاجه لرسالة النجاح)
$lang = $_SESSION['lang'] ?? 'en';

// 3. إزالة كل متغيرات الجلسة وتدميرها
$_SESSION = [];
session_destroy();

// 4. إعداد رسالة النجاح (قبل التوجيه)
$_SESSION['success_message'] = ($lang == 'ar') 
    ? '✅ تم تسجيل خروجك بنجاح. مرحباً بك في الصفحة الرئيسية.' 
    : '✅ You have been successfully logged out. Welcome to the home page.';

// 5. التوجيه إلى صفحة تسجيل الدخول أو الصفحة الرئيسية
// هذا يجب أن يكون أول Header يتم إرساله إلى المتصفح.
header("Location: " . BASE_URL . "/?page=login"); 
exit; 
// لا يوجد وسم إغلاق ?>