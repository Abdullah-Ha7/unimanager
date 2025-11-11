<?php
// footer.php
if (session_status() === PHP_SESSION_NONE) session_start();
$lang = $_SESSION['lang'] ?? 'en';

$site_settings = get_system_settings();

// ✅ 2. تحديد العنوان بناءً على إعدادات النظام واللغة الحالية
$site_title = ($lang == 'ar') ? ($site_settings['site_title_ar'] ?? 'العنوان الافتراضي') : ($site_settings['site_title_en'] ?? 'Default Title');

?>


<footer class="bg-dark text-light mt-0 pt-5 pb-4 border-top border-secondary">
  <div class="container">
    <div class="row text-center text-md-start">
      
      <!-- Column 1: About -->
      <div class="col-md-4 mb-4">
        <h5 class="fw-bold mb-3">
          <i class="bi bi-mortarboard"></i>
          <?php echo e($site_title); ?>
        </h5>
        <p class="text-muted small">
          <?php echo ($lang == 'ar') 
            ? 'منصة متكاملة لإدارة فعاليات الجامعة وتنظيمها بطريقة سهلة وسريعة.' 
            : 'A complete system for managing and organizing university events easily and efficiently.'; ?>
        </p>
      </div>

      

      <!-- Column 3: Contact / Social -->
      <div class="col-md-4 mb-4 text-md-end text-center">
        <h6 class="fw-bold mb-3"><?php echo ($lang == 'ar') ? 'تابعنا' : 'Follow Us'; ?></h6>
        <a href="#" class="text-light fs-5 me-2"><i class="bi bi-facebook"></i></a>
        <a href="#" class="text-light fs-5 me-2"><i class="bi bi-twitter-x"></i></a>
        <a href="#" class="text-light fs-5 me-2"><i class="bi bi-instagram"></i></a>
        <a href="#" class="text-light fs-5"><i class="bi bi-linkedin"></i></a>
      </div>
    </div>

    <hr class="border-secondary my-3">

    <div class="text-center small text-muted">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
