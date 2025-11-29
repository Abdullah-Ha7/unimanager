<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
$lang = $_SESSION['lang'] ?? 'en';
?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Ensure `university_id` column exists to avoid SQL errors on older schemas
    try {
        $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'university_id'");
        if ($colCheck && !$colCheck->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN university_id VARCHAR(50) NULL DEFAULT NULL, ADD UNIQUE KEY uniq_university_id (university_id)");
        }
    } catch (Exception $e) {
        error_log('Ensure university_id failed: ' . $e->getMessage());
    }

    // Allow login via email or university_id
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR university_id = ?");
    $stmt->execute([$email, $email]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Enforce verified accounts: require `is_active` = 1 for non-admins
        $isActive = isset($user['is_active']) ? (int)$user['is_active'] === 1 : false;
        $isAdmin = isset($user['role_id']) && (int)$user['role_id'] === 1;
        if (!$isActive && !$isAdmin) {
            $_SESSION['error_message'] = ($lang === 'ar')
                ? 'لا يمكنك تسجيل الدخول حتى تقوم بتفعيل حسابك عبر البريد الإلكتروني.'
                : 'You cannot log in until you verify your account via email.';
            $_SESSION['pending_verification_email'] = $user['email'];
            header("Location: " . BASE_URL . "?page=register_verify");
            exit;
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;  // ✅ store user data in session

        // Redirect by role
        if ($user['role_id'] == 3) {
            header("Location: " . BASE_URL . "/?page=student_dashboard");
        } elseif ($user['role_id'] == 2) {
            header("Location: " . BASE_URL . "/organizer/organizer_dashboard.php");
        } else {
            header("Location: " . BASE_URL . "/?page=admin_dashboard");
        }
        exit;
    } else {
        $_SESSION['error_message'] =   ($_SESSION['lang'] == 'ar') ? 'خطأ في البريد الإلكتروني أو كلمة المرور' : 'Invalid email or password.' ;
        header("Location: " . BASE_URL . "/?page=login");
        exit;
    }
}
?>
