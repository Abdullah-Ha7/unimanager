
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../mailer.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lang = $_SESSION['lang'] ?? 'en';
$pending = $_SESSION['pending_verification_email'] ?? '';

// Ensure schema (email_verifications table and users.is_active column)
try {
  $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
  if ($colCheck && !$colCheck->fetch()) {
    $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 0");
  }
  $pdo->exec("CREATE TABLE IF NOT EXISTS email_verifications (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        email VARCHAR(150) NOT NULL,\n        code VARCHAR(20) NOT NULL,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n        UNIQUE KEY (email),\n        INDEX (code)\n    )");
} catch (Exception $e) {
  error_log('Schema ensure in verify failed: ' . $e->getMessage());
}

// If no pending email, redirect to registration
if (empty($pending)) {
    header('Location: ?page=register');
    exit;
}

// Handle POST actions: verify or resend
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'resend') {
        // regenerate code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $vstmt = $pdo->prepare("REPLACE INTO email_verifications (email, code, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
        $vstmt->execute([$pending, $code]);

        if ($lang == 'ar') {
            $subject = 'كود التحقق (إعادة إرسال)';
            $body = "<div style='direction: rtl; text-align: right; font-family: Arial, sans-serif;'>\n                        <h3>كود التحقق</h3>\n                        <p>هذا هو كود التحقق الخاص بك: <strong>" . $code . "</strong></p>\n                     </div>";
        } else {
            $subject = 'Verification Code (Resend)';
            $body = "<div style='font-family: Arial, sans-serif;'>\n                        <h3>Verification Code</h3>\n                        <p>Your verification code is: <strong>" . $code . "</strong></p>\n                     </div>";
        }

        $sent = sendMail($pending, $subject, $body);
        $_SESSION['success_message'] = $sent ? (($lang=='ar')? 'تم إعادة إرسال كود التحقق.' : 'Verification code resent.') : (($lang=='ar')? 'فشل إرسال البريد.' : 'Failed to send email.');
        header('Location: ?page=register_verify');
        exit;
    }

    // Verify action
    $input_code = trim($_POST['code'] ?? '');
    if ($input_code === '') {
        $_SESSION['error_message'] = ($lang == 'ar') ? 'الرجاء إدخال الكود.' : 'Please enter the code.';
        header('Location: ?page=register_verify');
        exit;
    }

    $vstmt = $pdo->prepare("SELECT * FROM email_verifications WHERE email = ? AND code = ?");
    $vstmt->execute([$pending, $input_code]);
    $row = $vstmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $_SESSION['error_message'] = ($lang == 'ar') ? 'الكود غير صحيح.' : 'Invalid code.';
        header('Location: ?page=register_verify');
        exit;
    }

    // check expiration (1 hour)
    $created = strtotime($row['created_at']);
    if ($created === false || (time() - $created) > 3600) {
        $_SESSION['error_message'] = ($lang == 'ar') ? 'انتهى صلاحية الكود. الرجاء إعادة الإرسال.' : 'Code expired. Please resend.';
        header('Location: ?page=register_verify');
        exit;
    }

    // activate user
    $ustmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE email = ?");
    $ustmt->execute([$pending]);
    // delete verification record
    $dstmt = $pdo->prepare("DELETE FROM email_verifications WHERE email = ?");
    $dstmt->execute([$pending]);

    unset($_SESSION['pending_verification_email']);
    $_SESSION['success_message'] = ($lang == 'ar') ? 'تم تفعيل حسابك بنجاح. يمكنك الآن تسجيل الدخول.' : 'Your account has been verified. You can now log in.';
    header('Location: ?page=login');
    exit;
}

// show form
?>

<section class="login-section mt-5 mb-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card login-card shadow-lg border-0 p-4">
          <div class="card-body">
            <h3 class="text-center fw-bold mb-4 text-primary"><?php echo ($lang=='ar')? 'تحقق من بريدك' : 'Verify your account'; ?></h3>

          <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success text-center"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
          <?php endif; ?>

          <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger text-center"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
          <?php endif; ?>

            <p class="text-center mb-3"><?php echo ($lang=='ar')? 'أدخل كود التحقق المرسل إلى بريدك الإلكتروني:' : 'Enter the verification code sent to your email:'; ?></p>

            <form method="POST" action="?page=register_verify">
              <div class="mb-3">
                <label class="form-label"><?php echo ($lang=='ar')? 'كود التحقق' : 'Verification Code'; ?></label>
                <input type="text" name="code" class="form-control" placeholder="123456" required maxlength="6">
              </div>

              <button type="submit" class="btn btn-primary w-100 login-btn">
                <i class="bi bi-shield-check"></i>
                <?php echo ($lang=='ar')? 'تحقق' : 'Verify'; ?>
              </button>
            </form>

            <form method="POST" action="?page=register_verify" class="text-center mt-3">
              <input type="hidden" name="action" value="resend">
              <button type="submit" class="btn btn-link"><?php echo ($lang=='ar')? 'إعادة إرسال الكود' : 'Resend code'; ?></button>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</section>
