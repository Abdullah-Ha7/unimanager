<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$user = current_user();

// ✅ Ensure only logged-in students can access
if (!$user || $user['role_id'] != 3) {
    $_SESSION['error_message'] = ($_SESSION['lang'] ?? 'en') == 'ar'
        ? 'الرجاء تسجيل الدخول كطالب للوصول إلى الحجوزات.'
        : 'Please log in as a student to access your bookings.';
    header("Location: ?page=login");
    exit;
}

// ✅ Fetch student's bookings
$stmt = $pdo->prepare("
    SELECT e.id, e.title, e.date, e.location, e.description, e.image, b.booked_at
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.user_id = ?
    ORDER BY b.booked_at DESC
");
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ✅ ربط ملفات CSS -->
<link rel="stylesheet" href="assets/css/style.css">


<section class="py-5 bg-light min-vh-90">
  <div class="container">

    <h2 class="fw-bold text-primary mb-4 text-center">
      <i class="bi bi-ticket-detailed"></i>
      <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'حجوزاتي' : 'My Bookings'; ?>
    </h2>

    <?php if (empty($bookings)): ?>
      <div class="alert alert-info text-center">
        <i class="bi bi-info-circle"></i>
        <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' 
          ? 'لم تقم بحجز أي فعاليات بعد.' 
          : 'You haven’t booked any events yet.'; ?>
      </div>
    <?php else: ?>
      <div class="row">
        <?php foreach ($bookings as $ev): ?>
          <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100 custom-card">
              
              <?php if (!empty($ev['image'])): ?>
                <img src="<?php echo BASE_URL . '/uploads/' . e($ev['image']); ?>" 
                     class="card-img-top event-img">
              <?php else: ?>
                <div class="placeholder-img"></div>
              <?php endif; ?>

              <div class="card-body d-flex flex-column">
                <h5 class="fw-bold text-primary"><?php echo e($ev['title']); ?></h5>
                <p class="text-muted mb-1"><i class="bi bi-calendar-event"></i> <?php echo e($ev['date']); ?></p>
                <p class="text-muted"><i class="bi bi-geo-alt"></i> <?php echo e($ev['location']); ?></p>
                <p class="small flex-grow-1"><?php echo nl2br(substr(e($ev['description']), 0, 100)) . '...'; ?></p>
                
                <div class="mt-auto text-end">
                  <a href="?page=event_detail&id=<?php echo $ev['id']; ?>" 
                     class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-eye"></i>
                    <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'عرض التفاصيل' : 'View Details'; ?>
                  </a>
                </div>
              </div>
            </div>
          </div>

          <form method="POST" action="?page=student_dashboard" class="d-inline">
            <input type="hidden" name="booking_id" value="<?php echo $ev['id']; ?>">
            <button type="submit" name="cancel_booking" 
                    onclick="return confirm('<?php echo ($_SESSION['lang'] == 'ar') ? 'هل أنت متأكد من إلغاء الحجز؟' : 'Are you sure you want to cancel?'; ?>');" 
                    class="btn btn-outline-danger btn-sm">
              <i class="bi bi-x-circle"></i> 
              <?php echo ($_SESSION['lang'] == 'ar') ? 'إلغاء' : 'Cancel'; ?>
            </button>
          </form>

        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>
