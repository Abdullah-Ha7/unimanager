<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// NOTE: It is assumed that config.php and functions.php are already required 
// by the main index.php file, but we include them here for safety.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';


// ✅ 1. جلب الإعدادات من قاعدة البيانات (أو الجلسة)
$site_settings = get_system_settings();

$user = current_user();

if (isset($_GET['lang'])) {
    $selected_lang = $_GET['lang'];
    if (in_array($selected_lang, ['en', 'ar'])) {
        $_SESSION['lang'] = $selected_lang;
    }

    // Redirect back to the same page (no reload to home)
    $current_page = strtok($_SERVER["REQUEST_URI"], '?'); // remove query
    header("Location: " . $current_page);
    exit;
}

// Load selected language
$lang = $_SESSION['lang'] ?? 'en';
$lang_file = __DIR__ . "/lang/{$lang}.php";
if (file_exists($lang_file)) {
    $translations = include $lang_file;
} else {
    $translations = include __DIR__ . "/lang/en.php";
}

// ✅ 2. تحديد العنوان بناءً على إعدادات النظام واللغة الحالية
$site_title = ($lang == 'ar') ? ($site_settings['site_title_ar'] ?? 'العنوان الافتراضي') : ($site_settings['site_title_en'] ?? 'Default Title');
$registration_is_open = ($site_settings['registration_open'] ?? '1') == '1';

?>

<!-- ✅ Include Bootstrap CSS and Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">


<!DOCTYPE html>
<head> 
    <!-- ✅ وسم <head> الصحيح -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ✅ 3. استخدام المتغير $site_title في العنوان (الذي يظهر في التبويب) -->
    <title><?php echo e($site_title); ?></title>
</head>
<body>
<div id="page-container">
    <header>
      <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(90deg, #1e2325ff);">
        <div class="container">
          <!-- Brand -->
          <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>">
            <i class="bi bi-mortarboard"></i> 
            <!-- ✅ 4. استخدام المتغير $site_title في العلامة التجارية (شريط التنقل) -->
            <?php echo e($site_title); ?>
          </a>

          <!-- Toggle button for mobile -->
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
          </button>

          <!-- Nav Links -->
          <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">

              <!-- Public Links -->
              <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>/?page=home"><?php echo lang('home'); ?></a>
              </li>

              <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>/?page=events"><?php echo lang('events'); ?></a>
              </li>

              <!-- Logged-in Users -->
              <?php if ($user): ?>
                <?php if ($user['role_id'] == 1): // ✅ Admin ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-info fw-semibold" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                             <i class="bi bi-shield-lock"></i> <?php echo ($lang == 'ar') ? 'لوحة المسؤول' : 'Admin Panel'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="adminDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/?page=admin_dashboard"><?php echo lang('dashboard'); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/?page=manage_users"><?php echo lang('manage_users'); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/?page=manage_all_events"><?php echo lang('manage_all_events'); ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/?page=system_config"><i class="bi bi-gear me-2"></i> <?php echo lang('system_configuration'); ?></a></li>
                        </ul>
                    </li>
                <?php elseif ($user['role_id'] == 2): // Organizer ?>
                  <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/organizer/dashboard.php">
                      <?php echo lang('organizer_dashboard'); ?>
                    </a>
                  </li>
                 <?php elseif ($user['role_id'] == 3): // Student ?>
                  <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/?page=student_dashboard">
                      <?php echo lang('student_dashboard'); ?>
                    </a>
                  </li>
                 <?php endif; ?>

                <!-- Logout -->
                <li class="nav-item">
                  <a class="nav-link text-warning fw-semibold" href="<?php echo BASE_URL; ?>/?page=logout">
                    <i class="bi bi-box-arrow-right"></i> <?php echo lang('logout'); ?>
                  </a>
                </li>

              <?php else: ?>
                <!-- Guest -->
                <li class="nav-item">
                  <a class="nav-link" href="<?php echo BASE_URL; ?>/?page=login"><?php echo lang('login'); ?></a>
                </li>
                <!-- ✅ عرض رابط التسجيل فقط إذا كان مفتوحاً -->
                <?php if ($registration_is_open): ?>
                <li class="nav-item">
                  <a class="nav-link" href="<?php echo BASE_URL; ?>/?page=register"><?php echo lang('register'); ?></a>
                </li>
                <?php endif; ?>
              <?php endif; ?>

              <!-- Language Switch -->
                <li class="nav-item ms-3">
                  <a href="?lang=<?php echo ($_SESSION['lang'] ?? 'en') === 'en' ? 'ar' : 'en'; ?>" 
                    class="btn btn-outline-light btn-sm">
                    <?php echo ($_SESSION['lang'] ?? 'en') === 'en' ? 'العربية' : 'English'; ?>
                  </a>
                </li>

            </ul>
          </div>
        </div>
      </nav>
    </header>

<main>
