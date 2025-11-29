<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
?>


<!-- ✅ ربط ملفات CSS -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/forgot.css">

<section class="py-5" >
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg border-0 p-4">
          <div class="card-body">
            <h3 class="text-center fw-bold mb-4 text-primary">
              <?php echo ($_SESSION['lang'] == 'ar') ? 'استعادة كلمة المرور' : 'Forgot Password'; ?>
            </h3>

            <?php if (!empty($_SESSION['error_message'])): ?>
              <div class="alert alert-danger text-center"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
              <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success_message'])): ?>
              <div class="alert alert-success text-center"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
              <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <form method="POST" action="?page=forgot_action">
              <div class="mb-3">
                <label class="form-label">
                  <?php echo ($_SESSION['lang'] == 'ar') ? 'أدخل بريدك الإلكتروني' : 'Enter your email address'; ?>
                </label>
                <input type="email" name="email" class="form-control" required placeholder="name@example.com">
              </div>

              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-envelope"></i>
                <?php echo ($_SESSION['lang'] == 'ar') ? 'إرسال رابط الاستعادة' : 'Send Reset Link'; ?>
              </button>
            </form>

            <div class="text-center mt-3">
              <a href="?page=login" class="text-decoration-none">
                <i class="bi bi-arrow-left-circle"></i>
                <?php echo ($_SESSION['lang'] == 'ar') ? 'العودة لتسجيل الدخول' : 'Back to Login'; ?>
              </a>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</section>
