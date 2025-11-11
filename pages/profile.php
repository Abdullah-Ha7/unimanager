<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$user = current_user();
if (!$user) {
    header("Location: ?page=login");
    exit;
}

$message = '';

// handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass1 = $_POST['password'];
    $pass2 = $_POST['confirm_password'];

    // basic validation
    if ($pass1 && $pass1 !== $pass2) {
        $message = "<div class='alert alert-danger'>".
            (($_SESSION['lang'] ?? 'en') == 'ar'
                ? 'كلمات المرور غير متطابقة'
                : 'Passwords do not match') .
            "</div>";
    } else {
        // update query
        $query = "UPDATE users SET name=?, email=?";
        $params = [$name, $email];

        if ($pass1) {
            $hashed = password_hash($pass1, PASSWORD_DEFAULT);
            $query .= ", password=?";
            $params[] = $hashed;
        }
        $query .= " WHERE id=?";
        $params[] = $user['id'];

        $stmt = $pdo->prepare($query);
        if ($stmt->execute($params)) {
            // refresh session info
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
            $stmt->execute([$user['id']]);
            $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

            $message = "<div class='alert alert-success'>".
                (($_SESSION['lang'] ?? 'en') == 'ar'
                    ? 'تم تحديث الملف الشخصي بنجاح'
                    : 'Profile updated successfully!') .
                "</div>";
        } else {
            $message = "<div class='alert alert-danger'>".
                (($_SESSION['lang'] ?? 'en') == 'ar'
                    ? 'فشل في تحديث الملف الشخصي'
                    : 'Profile update failed!') .
                "</div>";
        }
    }
}
?>

<!-- ✅ ربط ملفات CSS -->
<link rel="stylesheet" href="assets/css/profile.css">

<section class="py-5" >
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-7 col-lg-6">
        <div class="card shadow-lg border-0 p-4">
          <div class="card-body">
            <h3 class="text-center fw-bold text-primary mb-4">
              <?php echo (($_SESSION['lang'] ?? 'en') == 'ar') ? 'الملف الشخصي' : 'My Profile'; ?>
            </h3>

            <?php echo $message; ?>

            <form method="POST" action="">
              <div class="mb-3">
                <label class="form-label"><?php echo (($_SESSION['lang'] ?? 'en') == 'ar') ? 'الاسم الكامل' : 'Full Name'; ?></label>
                <input type="text" name="name" class="form-control" required value="<?php echo e($user['name']); ?>">
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo (($_SESSION['lang'] ?? 'en') == 'ar') ? 'البريد الإلكتروني' : 'Email Address'; ?></label>
                <input type="email" name="email" class="form-control" required value="<?php echo e($user['email']); ?>">
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? '(اختياري) كلمة المرور'  : 'Password (optional)'; ?></label>
                <input type="password" id="password" name="password" class="form-control" required>
              <!-- ✅ Password Requirement Checklist -->
                <ul id="password-checklist" class="list-unstyled small mt-2">
                  <li id="len" class="text-danger"><i class="bi bi-x-circle"></i> 
                    <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? '٨ أحرف على الأقل' : 'At least 8 characters'; ?>
                  </li>
                  <li id="upper" class="text-danger"><i class="bi bi-x-circle"></i> 
                    <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'حرف كبير واحد على الأقل' : 'One uppercase letter'; ?>
                  </li>
                  <li id="lower" class="text-danger"><i class="bi bi-x-circle"></i> 
                    <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'حرف صغير واحد على الأقل' : 'One lowercase letter'; ?>
                  </li>
                  <li id="num" class="text-danger"><i class="bi bi-x-circle"></i> 
                    <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'رقم واحد على الأقل' : 'One number'; ?>
                  </li>
                </ul>
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo (($_SESSION['lang'] ?? 'en') == 'ar') ? 'تأكيد كلمة المرور' : 'Confirm Password'; ?></label>
                <input type="password" name="confirm_password" class="form-control" placeholder="********">
              </div>

              
              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-save"></i>
                <?php echo (($_SESSION['lang'] ?? 'en') == 'ar') ? 'تحديث المعلومات' : 'Update Info'; ?>
              </button>
            </form>

            <div class="text-center mt-4">
              <a href="?page=home" class="text-decoration-none">
                <i class="bi bi-arrow-left-circle"></i>
                <?php echo (($_SESSION['lang'] ?? 'en') == 'ar') ? 'عودة إلى الصفحة الرئيسية' : 'Back to Home'; ?>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- ✅ JavaScript for Live Password Check -->
<script>
document.getElementById('password').addEventListener('input', function() {
  const val = this.value;
  const len = document.getElementById('len');
  const upper = document.getElementById('upper');
  const lower = document.getElementById('lower');
  const num = document.getElementById('num');

  // Check length
  if (val.length >= 8) { len.classList.replace('text-danger','text-success'); len.querySelector('i').className = 'bi bi-check-circle'; }
  else { len.classList.replace('text-success','text-danger'); len.querySelector('i').className = 'bi bi-x-circle'; }

  // Uppercase
  if (/[A-Z]/.test(val)) { upper.classList.replace('text-danger','text-success'); upper.querySelector('i').className = 'bi bi-check-circle'; }
  else { upper.classList.replace('text-success','text-danger'); upper.querySelector('i').className = 'bi bi-x-circle'; }

  // Lowercase
  if (/[a-z]/.test(val)) { lower.classList.replace('text-danger','text-success'); lower.querySelector('i').className = 'bi bi-check-circle'; }
  else { lower.classList.replace('text-success','text-danger'); lower.querySelector('i').className = 'bi bi-x-circle'; }

  // Number
  if (/\d/.test(val)) { num.classList.replace('text-danger','text-success'); num.querySelector('i').className = 'bi bi-check-circle'; }
  else { num.classList.replace('text-success','text-danger'); num.querySelector('i').className = 'bi bi-x-circle'; }
});
</script>