<?php
// footer.php
if (session_status() === PHP_SESSION_NONE) session_start();
$lang = $_SESSION['lang'] ?? 'en';

$site_settings = get_system_settings();

// ✅ 2. تحديد العنوان بناءً على إعدادات النظام واللغة الحالية
$site_title = ($lang == 'ar') ? ($site_settings['site_title_ar'] ?? 'العنوان الافتراضي') : ($site_settings['site_title_en'] ?? 'Default Title');

?>

<!-- Close main content area started in header.php, then render footer inside #page-container -->
</main>

<footer class="bg-dark text-light mt-0 pt-5 pb-4 border-top border-secondary">
  <div class="container">
    <div class="row text-center text-md-start">
      
      <!-- Column 1: About -->
      <div class="col-md-4 mb-4">
        <h5 class="fw-bold mb-3">
          <i class="bi bi-mortarboard"></i>
          <?php echo e($site_title); ?>
        </h5>
        <p class="text-white small">
          <?php echo ($lang == 'ar') 
            ? 'منصة متكاملة لإدارة فعاليات الجامعة وتنظيمها بطريقة سهلة وسريعة.' 
            : 'A complete system for managing and organizing university events easily and efficiently.'; ?>
        </p>
      </div>

      

      <!-- Column 3: Contact / Social -->
      <div class="col-md-4 mb-4 text-md-end text-center">
        <h6 class="fw-bold mb-3"><?php echo ($lang == 'ar') ? 'تابعنا' : 'Follow Us'; ?></h6>
  <a href="https://www.facebook.com/King.Saud.University" class="text-white fs-5 me-2"><i class="bi bi-facebook"></i></a>
  <a href="https://x.com/gclubsksu?lang=ar" class="text-white fs-5 me-2"><i class="bi bi-twitter-x"></i></a>
  <a href="https://www.instagram.com/dsa_ksu/" class="text-white fs-5 me-2"><i class="bi bi-instagram"></i></a>
  <a href="http://linkedin.com/school/king-saud-university/" class="text-white fs-5 me-2"><i class="bi bi-linkedin"></i></a>

  
      </div>
    </div>

    <hr class="border-secondary my-3">

    <div class="text-center small text-white">
      <?php echo lang('rights_reserved'); ?>
    </div>
  </div>

  <!-- Back to Top Button -->
  <button onclick="window.scrollTo({top: 0, behavior: 'smooth'});" 
          class="btn btn-primary position-fixed rounded-circle shadow" 
          style="bottom: 20px; right: 20px; width: 45px; height: 45px;">
    <i class="bi bi-arrow-up"></i>
  </button>
</footer>

<!-- Close page container -->
</div>

<script src="<?php echo BASE_URL; ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/nav.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/icons.js"></script>
<!-- Removed inline dropdown fallback to avoid double toggling; nav.js handles fallback if Bootstrap missing -->
</body>
</html>
