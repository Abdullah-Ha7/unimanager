<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lang = $_SESSION['lang'] ?? 'en';
$user = current_user();

// Access control: allow admin (1), organizer (2), and student (3)
if (!$user || !in_array((int)$user['role_id'], [1,2,3], true)) {
  $_SESSION['error_message'] = ($lang === 'ar') ? 'هذه الصفحة مخصصة للمستخدمين المسجلين.' : 'This page is for registered users only.';
  header('Location: ' . BASE_URL . '/?page=login');
  exit;
}

// Read QR payload (JSON) from `data` or fallback to `booking_id`
$rawData = $_GET['data'] ?? '';
$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : null;
$payload = null;

if (!empty($rawData)) {
    // URL param may be URL-encoded JSON
    $decoded = urldecode($rawData);
    $payload = json_decode($decoded, true);
    if (!is_array($payload)) {
        $_SESSION['error_message'] = ($lang === 'ar') ? 'تنسيق البيانات غير صالح.' : 'Invalid data format.';
        header('Location: ' . BASE_URL . '/?page=events');
        exit;
    }
    // prefer bookingId from payload if present
    if (isset($payload['bookingId'])) {
        $bookingId = (int)$payload['bookingId'];
    }
}

if (!$bookingId) {
    $_SESSION['error_message'] = ($lang === 'ar') ? 'لم يتم توفير رقم الحجز.' : 'No booking ID provided.';
    header('Location: ' . BASE_URL . '/?page=events');
    exit;
}

// Fetch booking, user, event
$stmt = $pdo->prepare('SELECT b.id as booking_id, b.user_id, b.event_id, u.name as user_name, u.email as user_email, e.title as event_title, e.start_at, e.end_at, e.location 
                       FROM bookings b 
                       JOIN users u ON u.id = b.user_id 
                       JOIN events e ON e.id = b.event_id 
                       WHERE b.id = ?');
$stmt->execute([$bookingId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    $_SESSION['error_message'] = ($lang === 'ar') ? 'الحجز غير موجود.' : 'Booking not found.';
    header('Location: ' . BASE_URL . '/?page=events');
    exit;
}

// Page content
?>
<section class="login-section mt-5 mb-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-7">
        <div class="card login-card shadow-lg border-0 p-4">
          <div class="card-body">
            <h3 class="text-center fw-bold mb-4 text-primary">
              <?php echo ($lang==='ar') ? 'التحقق من الحجز' : 'Verify Booking'; ?>
            </h3>

            <?php if (!empty($_SESSION['error_message'])): ?>
              <div class="alert alert-danger text-center">
                <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
              </div>
            <?php endif; ?>

            <div class="alert alert-info">
              <strong><?php echo ($lang==='ar') ? 'رقم الحجز:' : 'Booking ID:'; ?></strong> <?php echo (int)$row['booking_id']; ?><br>
              <strong><?php echo ($lang==='ar') ? 'الطالب:' : 'Student:'; ?></strong> <?php echo htmlspecialchars($row['user_name']); ?> (<?php echo htmlspecialchars($row['user_email']); ?>)<br>
              <strong><?php echo ($lang==='ar') ? 'الفعالية:' : 'Event:'; ?></strong> <?php echo htmlspecialchars($row['event_title']); ?><br>
              <strong><?php echo ($lang==='ar') ? 'تاريخ البدء:' : 'Start:'; ?></strong> <?php echo htmlspecialchars($row['start_at']); ?><br>
              <strong><?php echo ($lang==='ar') ? 'تاريخ الانتهاء:' : 'End:'; ?></strong> <?php echo htmlspecialchars($row['end_at']); ?><br>
              <strong><?php echo ($lang==='ar') ? 'المكان:' : 'Location:'; ?></strong> <?php echo htmlspecialchars($row['location']); ?>
            </div>

            <?php if (is_array($payload)): ?>
            <div class="card p-3 mb-3">
              <h6 class="fw-bold mb-2"><?php echo ($lang==='ar') ? 'بيانات QR المستخرجة:' : 'Extracted QR Data:'; ?></h6>
              <pre class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
            </div>
            <?php endif; ?>

            <div class="text-center">
              <a href="<?php echo BASE_URL; ?>/?page=events" class="btn btn-primary w-100">
                <?php echo ($lang==='ar') ? 'رجوع إلى الفعاليات' : 'Back to Events'; ?>
              </a>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
  </section>
