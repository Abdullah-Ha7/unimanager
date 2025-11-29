<?php
// ✅ 1️⃣ Start session once (before any output)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

// ✅ 2️⃣ Handle language switching
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['en', 'ar'])) {
        $_SESSION['lang'] = $lang;
    }
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
    exit;
}

// ✅ 3️⃣ Default language (English if not set)
if (empty($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// ✅ 4️⃣ Language loader
function lang($key) {
    $lang = $_SESSION['lang'] ?? 'en';
    $langFile = __DIR__ . '/lang/' . $lang . '.php';

    static $dict = [];

    if (!isset($dict[$lang])) {
        if (file_exists($langFile)) {
            $dict[$lang] = include $langFile;
        } else {
            $dict[$lang] = [];
        }
    }

    return $dict[$lang][$key] ?? $key;
}

// ✅ 5️⃣ Escape output (to prevent XSS)
function e($str) {
    // ⬇️ التعديل: تحويل NULL إلى سلسلة نصية فارغة لتجنب أخطاء Deprecated في PHP 8+
    if ($str === null) {
        $str = '';
    }
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// ✅ 6️⃣ User functions
function current_user() {
    global $pdo;

    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    if (!empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['user'] = $user; // cache it
        }
        return $user;
    }

    return null;
}

// ✅ 7️⃣ Role helpers
function is_admin() {
    $u = current_user();
    return $u && $u['role_id'] == 1;
}

function is_organizer() {
    $u = current_user();
    return $u && $u['role_id'] == 2;
}

function is_user() {
    $u = current_user();
    return $u && $u['role_id'] == 3;
}

function get_system_settings() {
    global $pdo;

    // إذا كانت الإعدادات مخزنة في الجلسة، قم بإرجاعها مباشرة
    if (isset($_SESSION['settings'])) {
        return $_SESSION['settings'];
    }

    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // تخزينها في الجلسة
        $_SESSION['settings'] = $db_settings; 
        return $db_settings;

    } catch (PDOException $e) {
        error_log("Failed to fetch settings: " . $e->getMessage());
        return [];
    }
}

// ✅ 8️⃣ Event Cleanup (HACK: Should be run by Cron, but we run it on page load)
function cleanup_expired_events() {
    global $pdo;
    
    // يجب أن نتأكد من أن كائن PDO موجود قبل محاولة استخدامه
    if (!$pdo) {
        error_log("PDO object is not initialized. Cannot run cleanup.");
        return;
    }

    try {
        // الاستعلام لحذف جميع الفعاليات التي مر عليها تاريخ ووقت النهاية (end_date)
        $stmt = $pdo->prepare("DELETE FROM events WHERE end_at < NOW()");
        $stmt->execute();
        
    } catch (PDOException $e) {
        // في حال فشل الاستعلام (غالباً بسبب عدم وجود عمود end_date)، نقوم بتسجيل الخطأ والاستمرار.
        error_log("Database Cleanup Error (Missing end_date or similar): " . $e->getMessage());
    }
}

function getEventBookingStatus(int $event_id, int $capacity): array {
    global $pdo;

    // إذا كانت السعة غير محددة أو صفر، لا يمكن أن تكون ممتلئة
    if ($capacity <= 0) {
        return ['booked_count' => 0, 'is_full' => false];
    }

    // حساب عدد الحجوزات الحالية
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM bookings WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $booked_count = $stmt->fetchColumn();

    $is_full = ($booked_count >= $capacity);

    return [
        'booked_count' => (int)$booked_count,
        'is_full' => $is_full,
        'capacity' => $capacity
    ];
}

function format_date($datetime) {
    // التحقق من أن القيمة ليست فارغة أو تاريخ افتراضي
    if (!$datetime || $datetime === '0000-00-00 00:00:00') {
        return '';
    }

    $lang = $_SESSION['lang'] ?? 'en';

    try {
        // إنشاء كائن DateTime للتعامل مع التاريخ
        $dt = new DateTime($datetime);
    } catch (Exception $e) {
        // في حال فشل تحليل التاريخ، نعيد النص كما هو بعد تنظيفه
        return htmlspecialchars($datetime, ENT_QUOTES, 'UTF-8'); 
    }

    if ($lang === 'ar') {
        // تنسيق عربي مبسط: السنة/الشهر/اليوم الساعة:الدقيقة (صيغة 24 ساعة)
        return $dt->format('M d, Y  h:i A ');
    } else {
        // تنسيق إنجليزي: الشهر اليوم، السنة الساعة:الدقيقة (صيغة AM/PM)
        return $dt->format('M d, Y h:i A');
    }
}

// تنسيق الوقت فقط (ساعة:دقيقة) بدون ثوانٍ
function format_time($datetime) {
    if (!$datetime || $datetime === '0000-00-00 00:00:00') {
        return '';
    }
    try { $dt = new DateTime($datetime); } catch (Exception $e) { return e($datetime); }
    $lang = $_SESSION['lang'] ?? 'en';
    if ($lang === 'ar') {
        return $dt->format('h:i A'); // 24h
    }
    return $dt->format('h:i A'); // 12h with AM/PM
}

// الحفاظ على ترتيب AM/PM في اللغات RTL (العربية) بإجبار اتجاه الوقت على LTR
function format_time_am_pm($datetime) {
    if (!$datetime || $datetime === '0000-00-00 00:00:00') { return ''; }
    try { $dt = new DateTime($datetime); } catch (Exception $e) { return e($datetime); }
    $time = $dt->format('h:i A'); // دائما بنفس التنسيق
    // نلفه داخل span اتجاهه LTR حتى في الصفحات العربية
    return '<span class="time-ltr">' . e($time) . '</span>'; // e لحماية النص
}

function get_system_stats(): array {
    global $pdo;

    $stats = [
        'total_events' => 0,
        'total_users' => 0,
        'total_bookings' => 0,
    ];

    try {
        // 1. إجمالي الفعاليات المعتمدة والمستقبلية فقط
        $stats['total_events'] = (int)$pdo->query("
            SELECT COUNT(id) FROM events WHERE approval_status = 'approved' AND date > NOW()
        ")->fetchColumn();

        // 2. إجمالي المستخدمين (باستثناء الأدمن)
        $stats['total_users'] = (int)$pdo->query("
            SELECT COUNT(id) FROM users WHERE role != 'admin'
        ")->fetchColumn();

        // 3. إجمالي الحجوزات
        $stats['total_bookings'] = (int)$pdo->query("
            SELECT COUNT(id) FROM bookings
        ")->fetchColumn();

    } catch (PDOException $e) {
        error_log("Database error in get_system_stats: " . $e->getMessage());
    }

    return $stats;
}



// تشغيل تنظيف الفعاليات منتهية الصلاحية عند تحميل الملف