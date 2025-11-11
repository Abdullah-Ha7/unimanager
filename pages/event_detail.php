<?php
// C:\wamp64\www\Project-1\pages\event_detail.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$lang = $_SESSION['lang'] ?? 'en';
require_once __DIR__ . "/../lang/$lang.php";

$user = current_user();
$event_id = intval($_GET['id'] ?? 0);

if ($event_id === 0) {
    $_SESSION['error_message'] = lang('no_event_selected');
    header("Location: ?page=events");
    exit;
}

// 1. جلب تفاصيل الفعالية
$stmt = $pdo->prepare("
    SELECT 
        e.*, 
        u.name AS organizer_name,
        (SELECT COUNT(id) FROM bookings WHERE event_id = e.id) AS booked_count
    FROM events e 
    JOIN users u ON e.organizer_id = u.id 
    WHERE e.id = ?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    $_SESSION['error_message'] = lang('event_not_found');
    header("Location: ?page=events");
    exit;
}

// 2. تحديد الأدوار والحالة
$user_id = $user['id'] ?? null;
$user_role = $user['role_id'] ?? null;
$is_organizer = $user_role == 2 && $user_id == $event['organizer_id'];
$is_student = $user_role == 3;
$is_finished = (new DateTime($event['end_at'] ?? $event['date'])) < new DateTime();


// 3. جلب حالة الحجز والتقييم للطالب الحالي (إن وجد)
$is_booked = false;
$has_reviewed = false;
if ($user_id) {
    $stmt_book = $pdo->prepare("SELECT id FROM bookings WHERE event_id = ? AND user_id = ?");
    $stmt_book->execute([$event_id, $user_id]);
    if ($stmt_book->fetch()) {
        $is_booked = true;
    }
    
    $stmt_review = $pdo->prepare("SELECT id FROM reviews WHERE event_id = ? AND user_id = ?");
    $stmt_review->execute([$event_id, $user_id]);
    if ($stmt_review->fetch()) {
        $has_reviewed = true;
    }
}


// 4. منطق الحجز (Post Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_event']) && $user_role == 3) {
    if (!$is_booked) {
        if ($event['capacity'] !== null && $event['booked_count'] >= $event['capacity']) {
            $_SESSION['error_message'] = lang('event_is_full');
        } else {
            $stmt = $pdo->prepare("INSERT INTO bookings (event_id, user_id) VALUES (?, ?)");
            if ($stmt->execute([$event_id, $user_id])) {
                $_SESSION['success_message'] = lang('booking_success');
            } else {
                $_SESSION['error_message'] = lang('booking_fail');
            }
        }
    } else {
        $_SESSION['error_message'] = lang('already_booked');
    }
    header("Location: ?page=event_detail&id=$event_id");
    exit;
}

// 5. منطق رد المنظم على تقييم (مباشر داخل event_detail)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply']) && $is_organizer) {
    $target_review_id = intval($_POST['review_id'] ?? 0);
    $reply_text = trim($_POST['reply_text'] ?? '');

    if ($target_review_id > 0 && !empty($reply_text)) {
        // 1. جلب التقييم للتحقق من أن المنظم الحالي هو منظم الفعالية ولم يتم الرد عليه بعد
        $stmt_check = $pdo->prepare("
            SELECT e.organizer_id, r.reply_text
            FROM reviews r
            JOIN events e ON r.event_id = e.id
            WHERE r.id = ? AND r.event_id = ?
        ");
        $stmt_check->execute([$target_review_id, $event_id]); 
        $target_review = $stmt_check->fetch();

        if ($target_review && $target_review['organizer_id'] == $user_id && empty($target_review['reply_text'])) {
            // 2. التحديث في قاعدة البيانات
            $stmt_update = $pdo->prepare("UPDATE reviews SET reply_text = ?, reply_at = NOW() WHERE id = ?");
            if ($stmt_update->execute([$reply_text, $target_review_id])) {
                $_SESSION['success_message'] = lang('reply_submitted_success');
            } else {
                $_SESSION['error_message'] = lang('reply_submission_failed');
            }
        } else {
            $_SESSION['error_message'] = lang('access_denied_or_already_replied');
        }
    } else {
        $_SESSION['error_message'] = lang('reply_cannot_be_empty');
    }
    header("Location: ?page=event_detail&id=$event_id"); 
    exit;
}


// 6. جلب متوسط التقييمات
$stmt_avg = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as total_reviews FROM reviews WHERE event_id = ?");
$stmt_avg->execute([$event_id]);
$review_stats = $stmt_avg->fetch();

$avg_rating = round($review_stats['avg_rating'] ?? 0, 1);
$total_reviews = $review_stats['total_reviews'] ?? 0;

// 7. جلب التقييمات والردود
$stmt_reviews = $pdo->prepare("
    SELECT 
        r.*, 
        u.name AS reviewer_name,
        u.role_id AS reviewer_role
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.event_id = ?
    ORDER BY r.created_at DESC
");
$stmt_reviews->execute([$event_id]);
$reviews = $stmt_reviews->fetchAll();

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <title><?php echo lang('event_details') . " - " . e($event['title']); ?></title>
    <!-- استيراد Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    <section class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-success text-white text-center rounded-top-4 py-4">
                        <h1 class="h3 mb-0"><?php echo e($event['title']); ?></h1>
                        <p class="mb-0 small"><?php echo lang('organized_by'); ?>: <?php echo e($event['organizer_name']); ?></p>
                    </div>
                    <div class="card-body p-4">

                        <?php if (!empty($_SESSION['success_message'])): ?>
                            <div class="alert alert-success text-center"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>

                        <?php if (!empty($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger text-center"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
                            <?php unset($_SESSION['error_message']); ?>
                        <?php endif; ?>

                        <!-- قسم التفاصيل -->
                        <div class="row mb-4">
                            <div class="col-md-7">
                                <h2 class="h4 text-success mb-3"><?php echo lang('description'); ?></h2>
                                <p class="text-break"><?php echo nl2br(e($event['description'])); ?></p>
                            </div>
                            <div class="col-md-5">
                                <h2 class="h4 text-success mb-3"><?php echo lang('details'); ?></h2>
                                <ul class="list-unstyled">
                                 <?php if ($event['created_at']): ?>   
                                    <li class="mb-2"><i class="bi bi-calendar-check me-2 text-primary"></i> <strong><?php echo lang('date'); ?>:</strong> <?php echo e(format_date($event['created_at'])); ?></li>
                                 <?php endif; ?>
  
                                    <?php if ($event['end_at']): ?>
                                        <li class="mb-2"><i class="bi bi-clock-history me-2 text-primary"></i> <strong><?php echo lang('end_time'); ?>:</strong> <?php echo e(format_date($event['end_at'])); ?></li>
                                    <?php endif; ?>
                                    
                                    <li class="mb-2"><i class="bi bi-geo-alt me-2 text-primary"></i> <strong><?php echo lang('location'); ?>:</strong> <?php echo e($event['location']); ?></li>
                                    <li class="mb-2"><i class="bi bi-tag me-2 text-primary"></i> <strong><?php echo lang('category'); ?>:</strong> <?php echo e($event['category']); ?></li>
                                    
                                    <!-- Capacity والسعة المتبقية -->
                                    <?php if ($event['capacity'] !== null): ?>
                                        <?php 
                                            $remaining = max(0, $event['capacity'] - $event['booked_count']);
                                            $capacity_text = ($event['capacity'] > 0) 
                                                ? e($event['capacity']) . ' (' . lang('remaining') . ': ' . $remaining . ')' 
                                                : lang('unlimited');
                                            $capacity_icon = ($remaining === 0 && $event['capacity'] > 0) ? 'bi-person-fill-x text-danger' : 'bi-people me-2 text-primary';
                                        ?>
                                        <li class="mb-2"><i class="bi <?php echo $capacity_icon; ?>"></i> <strong><?php echo lang('capacity'); ?>:</strong> <?php echo $capacity_text; ?></li>
                                    <?php endif; ?>
                                    
                                </ul>

                                <!-- قسم التقييم الموجز -->
                                <div class="p-3 bg-light rounded shadow-sm mt-3 text-center">
                                    <h5 class="mb-1 text-warning"><i class="bi bi-star-fill"></i> <?php echo $avg_rating; ?> / 5</h5>
                                    <p class="mb-0 small text-muted"><?php echo lang('based_on'); ?> <?php echo $total_reviews; ?> <?php echo lang('reviews'); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- زر الإجراءات (الحجز/الإلغاء/التقييم) -->
                        <div class="d-grid gap-2 d-md-block border-top pt-3 mb-4">
                            <?php if ($is_student): ?>
                                <?php if ($is_booked): ?>
                                    <!-- الطالب حاجز -->
                                    <span class="badge bg-success py-2 px-3 me-2"><i class="bi bi-check-circle me-1"></i> <?php echo lang('you_are_booked'); ?></span>
                                    
                                    <!-- التقييم بمجرد الحجز -->
                                    <?php if (!$has_reviewed): ?>
                                        <a href="?page=reviews&event_id=<?php echo $event_id; ?>" class="btn btn-warning me-2">
                                            <i class="bi bi-star me-1"></i> <?php echo lang('review_event'); ?>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-outline-success me-2" disabled>
                                            <i class="bi bi-star-fill me-1"></i> <?php echo lang('already_reviewed'); ?>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <!-- زر الإلغاء (للفعاليات القادمة فقط) -->
                                    <?php if (!$is_finished): ?>
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancellationModal">
                                            <i class="bi bi-x-circle me-1"></i> <?php echo lang('cancel_booking'); ?>
                                        </button>
                                    <?php endif; ?>

                                <?php else: // الطالب غير حاجز ?>
                                    <!-- تم تحويل هذا الجزء لاستخدام بناء الجملة البديل بالكامل -->
                                    <?php if ($is_finished): ?>
                                        <button class="btn btn-secondary" disabled><?php echo lang('event_finished'); ?></button>
                                    <?php elseif ($event['capacity'] !== null && $event['booked_count'] >= $event['capacity']): ?>
                                        <button class="btn btn-danger" disabled><?php echo lang('event_is_full'); ?></button>
                                    <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <a href="?page=book&id=<?php echo $event_id; ?>"class="btn btn-success btn-lg">
                                            
                                                <i class="bi bi-bookmark-plus me-1"></i> <?php echo lang('book_now'); ?>
                                             </a>
                                        </form>
                                    <?php endif; // Closes if ($is_finished) block ?>

                                <?php endif; // Closes if ($is_booked) ?>
                            <?php endif; // Closes if ($is_student) ?>
                            
                        </div>

                        <!-- قسم التقييمات -->
                        <div class="mt-5 pt-3 border-top">
                            <h2 class="h4 text-primary mb-4"><?php echo lang('reviews_and_feedback'); ?> (<?php echo $total_reviews; ?>)</h2>

                            <?php if (empty($reviews)): ?>
                                <div class="alert alert-info text-center"><?php echo lang('no_reviews_yet'); ?></div>
                            <?php else: ?>
                                <?php foreach ($reviews as $review): ?>
                                    <div class="d-flex mb-4 p-3 border rounded shadow-sm">
                                        <div class="flex-shrink-0 me-3">
                                            <i class="bi bi-person-circle fs-3 text-secondary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1 d-flex justify-content-between align-items-center">
                                                <span><?php echo e($review['reviewer_name']); ?></span>
                                                <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> <?php echo e($review['rating']); ?></span>
                                            </h5>
                                            <p class="text-muted small mb-2">
                                                <i class="bi bi-calendar"></i> <?php echo e(format_date($review['created_at'])); ?>
                                                <?php if ($review['reviewer_role'] == 2): ?>
                                                    <span class="badge bg-primary ms-2"><?php echo lang('organizer'); ?></span>
                                                <?php endif; ?>
                                            </p>
                                            <p class="mb-2 text-break"><?php echo nl2br(e($review['comment'])); ?></p>
                                            
                                            <!-- زر ونموذج الرد (للمنظم فقط) -->
                                            <?php if ($is_organizer && !$review['reply_text']): ?>
                                                <!-- زر التبديل لفتح نموذج الرد -->
                                                <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#replyForm-<?php echo $review['id']; ?>" aria-expanded="false" aria-controls="replyForm-<?php echo $review['id']; ?>">
                                                             <i class="bi bi-reply-fill me-1"></i> <?php echo lang('reply_to_review'); ?>
                                                </button>

                                                <!-- نموذج الرد المخفي -->
                                                <div class="collapse mt-3" id="replyForm-<?php echo $review['id']; ?>">
                                                    <form method="POST">
                                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                        <div class="mb-2">
                                                            <textarea name="reply_text" class="form-control" rows="3" required placeholder="<?php echo lang('reply_placeholder'); ?>"></textarea>
                                                        </div>
                                                        <button type="submit" name="submit_reply" class="btn btn-sm btn-success w-100">
                                                            <i class="bi bi-send-fill me-1"></i> <?php echo lang('submit_reply'); ?>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- عرض رد المنظم -->
                                            <?php if ($review['reply_text']): ?>
                                                <div class="ms-md-4 mt-3 p-3 bg-light rounded border-start border-success border-4">
                                                    <h6 class="text-success mb-1"><?php echo lang('organizer_reply'); ?>:</h6>
                                                    <p class="mb-0 small text-break"><?php echo nl2br(e($review['reply_text'])); ?></p>
                                                    <small class="text-muted d-block mt-1"><?php echo e(format_date($review['reply_at'])); ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="?page=events" class="btn btn-secondary">
                                <i class="bi bi-arrow-left-circle me-2"></i>
                                <?php echo lang('back_to_events'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal for Cancellation Warning -->
    <div class="modal fade" id="cancellationModal" tabindex="-1" aria-labelledby="cancellationModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title" id="cancellationModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo lang('cancel_booking'); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p><?php echo lang('cancellation_must_be_from_dashboard'); ?></p>
            <p class="text-muted small"><?php echo lang('please_visit_your_dashboard'); ?></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo lang('close'); ?></button>
            <a href="?page=dashboard" class="btn btn-primary"><?php echo lang('go_to_dashboard'); ?></a>
          </div>
        </div>
      </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
