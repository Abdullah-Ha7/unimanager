<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
$lang = $_SESSION['lang'] ?? 'en';
?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;  // ✅ store user data in session

        // Redirect by role
        if ($user['role_id'] == 3) {
            header("Location: " . BASE_URL . "/?page=student_dashboard");
        } elseif ($user['role_id'] == 2) {
            header("Location: " . BASE_URL . "/organizer/dashboard.php");
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
