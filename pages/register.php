<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// ✅ Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!-- ✅ ربط ملفات CSS -->
<link rel="stylesheet" href="assets/css/style.css">


<?php if (!empty($_SESSION['success_message'])): ?>
  <div class="alert alert-success text-center shadow-sm mt-4 success-box">
    <i class="bi bi-check-circle-fill me-2"></i>
    <?php echo htmlspecialchars($_SESSION['success_message']); ?>
  </div>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<section class="register-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg border-0 p-4">
          <div class="card-body">
            <h3 class="text-center fw-bold mb-4 text-primary">
              <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'إنشاء حساب جديد' : 'Create Account'; ?>
            </h3>

            <?php if (!empty($_SESSION['error_message'])): ?>
              <div class="alert alert-danger text-center">
                <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
              </div>
            <?php endif; ?>

            <form method="POST" action="?page=register_action">
              <div class="mb-3">
                <label class="form-label"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'الاسم الكامل' : 'Full Name'; ?></label>
                <input type="text" name="name" class="form-control" required>
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'البريد الإلكتروني' : 'Email Address'; ?></label>
                <input type="email" name="email" class="form-control" required placeholder="name@example.com">
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'كلمة المرور' : 'Password'; ?></label>
                <input type="password" id="password" name="password" class="form-control" required>
                
                <ul id="password-checklist" class="list-unstyled small mt-2">
                  <li id="len" class="text-danger"><i class="bi bi-x-circle"></i> <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? '٨ أحرف على الأقل' : 'At least 8 characters'; ?></li>
                  <li id="upper" class="text-danger"><i class="bi bi-x-circle"></i> <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'حرف كبير واحد على الأقل' : 'One uppercase letter'; ?></li>
                  <li id="lower" class="text-danger"><i class="bi bi-x-circle"></i> <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'حرف صغير واحد على الأقل' : 'One lowercase letter'; ?></li>
                  <li id="num" class="text-danger"><i class="bi bi-x-circle"></i> <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'رقم واحد على الأقل' : 'One number'; ?></li>
                </ul>
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'تأكيد كلمة المرور' : 'Confirm Password'; ?></label>
                <input type="password" name="confirm_password" class="form-control" required>
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'الدور' : 'Role'; ?></label>
                <select name="role" class="form-select">
                  <option value="user"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'طالب' : 'Student'; ?></option>
                  <option value="organizer"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'منظم' : 'Organizer'; ?></option>
                </select>
              </div>

              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-person-plus"></i>
                <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'تسجيل' : 'Register'; ?>
              </button>
            </form>

            <p class="text-center mt-3 mb-0">
              <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'هل لديك حساب؟' : 'Already have an account?'; ?>
              <a href="?page=login" class="fw-semibold text-primary">
                <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'تسجيل الدخول' : 'Login'; ?>
              </a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script src="assets/js/password-check.js"></script>
