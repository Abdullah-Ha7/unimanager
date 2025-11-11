<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// ✅ Step 1: Get token
$token = $_GET['token'] ?? null;
if (!$token) {
    $_SESSION['error_message'] = "Invalid reset link.";
    header("Location: ?page=forgot");
    exit;
}

// ✅ Step 2: Check token
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND created_at >= NOW() - INTERVAL 1 HOUR");
$stmt->execute([$token]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
    $_SESSION['error_message'] = "This link has expired or is invalid.";
    header("Location: ?page=forgot");
    exit;
}

// ✅ Step 3: Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    $hasUpper = preg_match('@[A-Z]@', $password);
    $hasLower = preg_match('@[a-z]@', $password);
    $hasNumber = preg_match('@[0-9]@', $password);

    if ($password !== $confirm) {
        $error = ($_SESSION['lang'] == 'ar') ? 'كلمتا المرور غير متطابقتين.' : 'Passwords do not match.';
    } elseif (!$hasUpper || !$hasLower || !$hasNumber || strlen($password) < 8) {
        $error = ($_SESSION['lang'] == 'ar')
            ? 'يجب أن تحتوي كلمة المرور على 8 أحرف على الأقل، وحرف كبير، وحرف صغير، ورقم.'
            : 'Password must contain at least 8 characters, one uppercase letter, one lowercase letter, and one number.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $reset['user_id']]);
        $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);

        $_SESSION['success_message'] = ($_SESSION['lang'] == 'ar')
            ? 'تم إعادة تعيين كلمة المرور بنجاح! يمكنك تسجيل الدخول الآن.'
            : 'Password reset successfully! You can now log in.';
        header("Location: ?page=login");
        exit;
    }
}
?>

<!-- ✅ ربط ملفات CSS -->
<link rel="stylesheet" href="assets/css/reset_password.css">

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
  <meta charset="UTF-8">
  <title><?php echo ($_SESSION['lang'] == 'ar') ? 'إعادة تعيين كلمة المرور' : 'Reset Password'; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<section class="reset-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg border-0 p-4">
          <div class="card-body">
            <h3 class="text-center fw-bold mb-4 text-primary">
              <?php echo ($_SESSION['lang'] == 'ar') ? 'إعادة تعيين كلمة المرور' : 'Reset Password'; ?>
            </h3>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
              <div class="mb-3">
                <label class="form-label">
                  <?php echo ($_SESSION['lang'] == 'ar') ? 'كلمة المرور الجديدة' : 'New Password'; ?>
                </label>
                <input type="password" id="password" name="password" class="form-control" required>
                <ul class="requirement-list mt-2" id="password-rules">
                  <li id="length" class="invalid"><?php echo ($_SESSION['lang'] == 'ar') ? '٨ أحرف على الأقل' : 'At least 8 characters'; ?></li>
                  <li id="uppercase" class="invalid"><?php echo ($_SESSION['lang'] == 'ar') ? 'حرف كبير واحد على الأقل' : 'One uppercase letter'; ?></li>
                  <li id="lowercase" class="invalid"><?php echo ($_SESSION['lang'] == 'ar') ? 'حرف صغير واحد على الأقل' : 'One lowercase letter'; ?></li>
                  <li id="number" class="invalid"><?php echo ($_SESSION['lang'] == 'ar') ? 'رقم واحد على الأقل' : 'One number'; ?></li>
                </ul>
              </div>

              <div class="mb-3">
                <label class="form-label">
                  <?php echo ($_SESSION['lang'] == 'ar') ? 'تأكيد كلمة المرور' : 'Confirm Password'; ?>
                </label>
                <input type="password" name="confirm_password" class="form-control" required>
              </div>

              <button type="submit" class="btn btn-primary w-100 mt-3">
                <i class="bi bi-check-circle"></i>
                <?php echo ($_SESSION['lang'] == 'ar') ? 'إعادة تعيين' : 'Reset Password'; ?>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  const password = document.getElementById('password');
  const rules = {
    length: document.getElementById('length'),
    uppercase: document.getElementById('uppercase'),
    lowercase: document.getElementById('lowercase'),
    number: document.getElementById('number')
  };

  password.addEventListener('input', () => {
    const val = password.value;
    rules.length.className = val.length >= 8 ? 'valid' : 'invalid';
    rules.uppercase.className = /[A-Z]/.test(val) ? 'valid' : 'invalid';
    rules.lowercase.className = /[a-z]/.test(val) ? 'valid' : 'invalid';
    rules.number.className = /[0-9]/.test(val) ? 'valid' : 'invalid';
  });
</script>

</body>
</html>
