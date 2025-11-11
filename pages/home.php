<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$settings = get_system_settings();
$registration_is_open = ($site_settings['registration_open'] ?? '1') == '1';

$lang = $_SESSION['lang'] ?? 'en';
?>

<!-- ربط ملفات CSS -->
<link rel="stylesheet" href="assets/css/style.css">

<section class="hero-section text-center text-white d-flex align-items-center">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <h1 class="fw-bold mb-4">
          <?php echo ($lang == 'ar') 
            ? 'مرحباً بكم في نظام إدارة فعاليات جامعة الملك سعود' 
            : 'Welcome to King Saud University Event Management System'; ?>
        </h1>

        <p class="lead mb-5">
          <?php echo ($lang == 'ar') 
            ? 'شارك، نظم، واستكشف أحدث الفعاليات الجامعية بسهولة!' 
            : 'Participate, organize, and explore the latest university events with ease!'; ?>
        </p>

        <div class="text-center mt-4">
          <a href="?page=events" class="btn btn-light btn-lg shadow-sm px-4">
            <i class="bi bi-calendar-event"></i>
            <?php echo ($lang == 'ar') ? 'تصفح الفعاليات' : 'Browse Events'; ?>
          </a>

          <?php if ($registration_is_open): ?>
            <?php if (!isset($_SESSION['user_id'])): ?>
              <a href="?page=register" class="btn btn-primary btn-lg">
                <i class="bi bi-person-plus"></i>
                <?php echo ($lang == 'ar') ? 'إنشاء حساب' : 'Create Account'; ?>
              </a>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <hr class="my-5 text-white opacity-50">

        <div class="row text-start mt-4">
          <div class="col-md-4 mb-4">
            <div class="card bg-transparent border-light text-white h-100 shadow-sm">
              <div class="card-body">
                <i class="bi bi-calendar-check display-5"></i>
                <h5 class="fw-bold mt-3">
                  <?php echo ($lang == 'ar') ? 'احجز فعالياتك بسهولة' : 'Book Events Easily'; ?>
                </h5>
                <p class="small opacity-75">
                  <?php echo ($lang == 'ar') 
                    ? 'اختر الفعاليات التي تناسبك وسجل حضورك بسرعة.' 
                    : 'Choose events that fit your interests and book them quickly.'; ?>
                </p>
              </div>
            </div>
          </div>

          <div class="col-md-4 mb-4">
            <div class="card bg-transparent border-light text-white h-100 shadow-sm">
              <div class="card-body">
                <i class="bi bi-people display-5"></i>
                <h5 class="fw-bold mt-3">
                  <?php echo ($lang == 'ar') ? 'نظم فعالياتك الجامعية' : 'Organize University Events'; ?>
                </h5>
                <p class="small opacity-75">
                  <?php echo ($lang == 'ar') 
                    ? 'أنشئ فعاليات جديدة كمنظم وتابع تسجيل المشاركين.' 
                    : 'Create new events as an organizer and track participant registrations.'; ?>
                </p>
              </div>
            </div>
          </div>

          <div class="col-md-4 mb-4">
            <div class="card bg-transparent border-light text-white h-100 shadow-sm">
              <div class="card-body">
                <i class="bi bi-chat-square-quote display-5"></i>
                <h5 class="fw-bold mt-3">
                  <?php echo ($lang == 'ar') ? 'شارك رأيك' : 'Share Your Feedback'; ?>
                </h5>
                <p class="small opacity-75">
                  <?php echo ($lang == 'ar') 
                    ? 'قيّم الفعاليات وساهم في تحسين التجربة للجميع.' 
                    : 'Rate events and help improve the experience for everyone.'; ?>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
