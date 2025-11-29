<?php
// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
// Start output buffering early so later header() calls (e.g. in page scripts like book.php) succeed
if (function_exists('ob_start') && ob_get_level() === 0) {
  ob_start();
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

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo ($lang === 'ar') ? 'rtl' : 'ltr'; ?>">
<head> 
    <!-- ✅ وسم <head> الصحيح -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ✅ 3. استخدام المتغير $site_title في العنوان (الذي يظهر في التبويب) -->
    <title><?php echo e($site_title); ?></title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/variables.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/vendor/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/icons.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
  <?php if (!empty($extra_css) && is_array($extra_css)): ?>
    <?php foreach ($extra_css as $css_name): ?>
      <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/<?php echo htmlspecialchars($css_name, ENT_QUOTES, 'UTF-8'); ?>.css">
    <?php endforeach; ?>
  <?php endif; ?>
</head>
<body>
<div id="page-container">
    <header>
<?php
  // Determine current page and whether it's an auth page early so
  // we can use the result when rendering the navbar classes.
  $currentPage = isset($page) ? $page : ($_GET['page'] ?? 'home');
  $authPages = ['login', 'register', 'login_action', 'register_action'];
  $isAuthPage = in_array($currentPage, $authPages, true);
  // Pages where the search should be hidden
  $hideSearchPages = ['login','login_action','register','register_action','forgot','forgot_action','reset_password'];
  $showNavbarSearch = !in_array($currentPage, $hideSearchPages, true);
  // Admin pages list (for persistent admin dropdown)
  $adminPagesList = ['admin_dashboard','manage_users','manage_all_events','admin_edit_event','approve_events','system_config'];
  $isAdminPage = in_array($currentPage, $adminPagesList, true);

  ?>

  <nav class="navbar navbar-expand-lg navbar-dark navbar-gradient">
        <div class="container">
          <!-- Brand: KSU logo image -->
          <a class="navbar-brand fw-bold d-flex align-items-center" href="<?php echo BASE_URL; ?>">
            <img src="<?php echo BASE_URL; ?>/assets/img/ksu1.png" alt="KSU" class="navbar-logo">
          </a>

          <!-- Moved search next to logo (desktop only) -->
          <?php if ($showNavbarSearch): ?>
            <form class="navbar-search has-icon d-none d-lg-flex align-items-center ms-3" method="GET" action="<?php echo BASE_URL; ?>/index.php">
              <input type="hidden" name="page" value="events">
              <span class="bi bi-search search-icon"></span>
              <input type="text" name="search" class="form-control form-control-sm search-input-icon" 
                placeholder="<?php echo ($lang=='ar') ? 'ابحث في الفعاليات...' : 'Search events...'; ?>" />
            </form>
          <?php endif; ?>

          <!-- Toggle button for mobile -->
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
          </button>

          <!-- Nav Links -->
          <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav align-items-center">

              <!-- Public Links -->
              <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>/?page=home"><?php echo lang('home'); ?></a>
              </li>

              <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>/?page=events"><?php echo lang('events'); ?></a>
              </li>

              <!-- Logged-in Users -->
              <?php if ($user): ?>
                <?php if ($user['role_id'] == 1): // Admin - open only on click ?>
                  <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle " href="#" id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/organizer/organizer_dashboard.php">
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

                  <!-- Profile (visible for all logged-in users) -->
                  <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/?page=profile">
                      <i class="bi bi-person-circle"></i> <?php echo ($lang == 'ar') ? 'ملفي' : 'My Profile'; ?>
                    </a>
                  </li>

                <!-- Logout button (separate item) -->
                <li class="nav-item" >
                  <a class="btn btn-outline-light btn-sm" style="color: red;" href="<?php echo BASE_URL; ?>/?page=logout">
                    <i class="bi bi-box-arrow-right"></i> <?php echo lang('logout'); ?>
                  </a>
                </li>
                <!-- Language switch (separate item) -->
                <li class="nav-item">
                  <a href="?lang=<?php echo ($_SESSION['lang'] ?? 'en') === 'en' ? 'ar' : 'en'; ?>" 
                     class="btn btn-outline-light btn-sm lang-toggle">
                    <?php echo ($_SESSION['lang'] ?? 'en') === 'en' ? 'العربية' : 'English'; ?>
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
                <!-- Language for guests -->
                <li class="nav-item">
                  <a href="?lang=<?php echo ($_SESSION['lang'] ?? 'en') === 'en' ? 'ar' : 'en'; ?>" 
                     class="btn btn-outline-light btn-sm lang-toggle">
                    <?php echo ($_SESSION['lang'] ?? 'en') === 'en' ? 'العربية' : 'English'; ?>
                  </a>
                </li>
              <?php endif; ?>

              <?php /* Search moved up next to logo */ ?>

            </ul>
          </div>
        </div>
      </nav>

      <!-- Removed separate logo bar -->
    </header>

<main>









