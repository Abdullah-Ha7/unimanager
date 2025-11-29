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

// Prevent viewing/booking for non-approved events
if (($event['approval_status'] ?? 'pending') !== 'approved') {
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
    if (($event['approval_status'] ?? 'pending') !== 'approved') {
        $_SESSION['error_message'] = lang('booking_fail');
        header("Location: ?page=events");
        exit;
    }
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
        // 1. التحقق من أن المستخدم منظم الفعالية وأنه لم يتم الرد مسبقًا
        $stmt_check = $pdo->prepare("
            SELECT 
                e.organizer_id,
                EXISTS(SELECT 1 FROM review_replies rr WHERE rr.review_id = r.id) AS has_reply
            FROM reviews r
            JOIN events e ON r.event_id = e.id
            WHERE r.id = ? AND r.event_id = ?
        ");
        $stmt_check->execute([$target_review_id, $event_id]); 
        $target_review = $stmt_check->fetch();

        if ($target_review && $target_review['organizer_id'] == $user_id && !$target_review['has_reply']) {
            // 2. إدراج الرد في جدول review_replies
            $stmt_insert = $pdo->prepare("INSERT INTO review_replies (review_id, organizer_id, reply_text, created_at) VALUES (?, ?, ?, NOW())");
            if ($stmt_insert->execute([$target_review_id, $user_id, $reply_text])) {
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
    if (function_exists('safe_redirect')) { safe_redirect("?page=event_detail&id=$event_id"); } else { header("Location: ?page=event_detail&id=$event_id"); exit; }
}



// 6. جلب متوسط التقييمات
$stmt_avg = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as total_reviews FROM reviews WHERE event_id = ?");
$stmt_avg->execute([$event_id]);
$review_stats = $stmt_avg->fetch();

$avg_rating = round($review_stats['avg_rating'] ?? 0, 1);
$total_reviews = $review_stats['total_reviews'] ?? 0;

// 7. جلب التقييمات والردود
 /* Presentation only; all logic provided by event_detail_pre.php */ ?>

<section class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-success text-white text-center rounded-top-4 py-4">
                    <h1 class="h3 mb-0"><?php echo e($event['title']); ?></h1>
                    <p class="mb-0 small-text-black-50"><?php echo lang('organized_by'); ?>: <?php echo e($event['organizer_name']); ?></p>
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

                    <?php if (!empty($event['image'])): ?>
                        <div class="event-image mb-4 text-center">
                            <img src="<?php echo BASE_URL; ?>/uploads/events/<?php echo e($event['image']); ?>" class="img-fluid rounded" alt="<?php echo e($event['title']); ?>">
                        </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-md-7">
                            <h2 class="h4 text-success mb-3"><?php echo lang('description'); ?></h2>
                            <p class="text-break"><?php echo nl2br(e($event['description'])); ?></p>
                        </div>
                        <div class="col-md-5">
                            <h2 class="h4 text-success mb-3"><?php echo lang('details'); ?></h2>
                            <ul class="list-unstyled">
                                <?php if (!empty($event['start_at'])): ?>
                                    <li class="mb-2"><i class="bi bi-calendar-check me-2 text-primary"></i> <strong><?php echo lang('start_time'); ?>:</strong> <?php echo format_date($event['start_at']); ?></li>
                                <?php endif; ?>
                                <?php if (!empty($event['end_at'])): ?>
                                    <li class="mb-2"><i class="bi bi-clock-history me-2 text-primary"></i> <strong><?php echo lang('end_time'); ?>:</strong> <?php echo format_date($event['end_at']); ?></li>
                                <?php endif; ?>
                                <li class="mb-2"><i class="bi bi-geo-alt me-2 text-primary"></i> <strong><?php echo lang('location'); ?>:</strong> <?php echo e($event['location']); ?></li>
                                <li class="mb-2"><i class="bi bi-tag me-2 text-primary"></i> <strong><?php echo lang('category'); ?>:</strong> <?php echo e($event['category']); ?></li>
                                <?php if (isset($event['capacity']) && $event['capacity'] !== null): ?>
                                    <?php $remaining = max(0, $event['capacity'] - ($event['booked_count'] ?? 0));
                                    $capacity_text = ($event['capacity'] > 0) ? e($event['capacity']) . ' (' . lang('remaining') . ': ' . $remaining . ')' : lang('unlimited');
                                    $capacity_icon = ($remaining === 0 && $event['capacity'] > 0) ? 'bi-person-fill-x text-danger' : 'bi-people me-2 text-primary'; ?>
                                    <li class="mb-2"><i class="bi <?php echo $capacity_icon; ?>"></i> <strong><?php echo lang('capacity'); ?>:</strong> <?php echo $capacity_text; ?></li>
                                <?php endif; ?>
                            </ul>
                            <div class="p-3 bg-light rounded shadow-sm mt-3 text-center">
                                <h5 class="mb-1 text-warning"><i class="bi bi-star-fill"></i> <?php echo $avg_rating; ?> / 5</h5>
                                <p class="mb-0 small text-muted"><?php echo lang('based_on'); ?> <?php echo $total_reviews; ?> <?php echo lang('reviews'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="event-actions d-flex align-items-start gap-3 flex-wrap border-top pt-3 mb-4">
                        <?php if ($is_student): ?>
                            <?php if ($is_booked): ?>
                                <div class="event-primary-action me-2">
                                    <button class="btn btn-success w-100" disabled>
                                        <i class="bi bi-check-circle me-1"></i> <?php echo lang('you_are_booked'); ?>
                                    </button>
                                </div>
                                <div class="event-secondary-actions d-flex flex-column gap-2">
                                    <?php if (!$has_reviewed): ?>
                                        <a href="?page=reviews&event_id=<?php echo $event_id; ?>" class="btn btn-warning w-100">
                                            <i class="bi bi-star me-1"></i> <?php echo lang('review_event'); ?>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-outline-success w-100" disabled>
                                            <i class="bi bi-star-fill me-1"></i> <?php echo lang('already_reviewed'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <?php if ($is_finished): ?>
                                    <button class="btn btn-secondary" disabled><?php echo lang('event_finished'); ?></button>
                                <?php elseif (isset($event['capacity']) && $event['capacity'] !== null && ($event['booked_count'] ?? 0) >= $event['capacity']): ?>
                                    <button class="btn btn-danger" disabled><?php echo lang('event_is_full'); ?></button>
                                <?php else: ?>
                                    <form method="POST" class="d-inline">
                                        <a href="?page=book&id=<?php echo $event_id; ?>"class="btn btn-success btn-lg">
                                            
                                                <i class="bi bi-bookmark-plus me-1"></i> <?php echo lang('book_now'); ?>
                                             </a>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

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
                                            <?php if ($review['reviewer_role'] == 2): ?><span class="badge bg-primary ms-2"><?php echo lang('organizer'); ?></span><?php endif; ?>
                                        </p>
                                        <p class="mb-2 text-break"><?php echo nl2br(e($review['comment'])); ?></p>
                                        <?php if ($is_organizer && empty($review['reply_text'])): ?>
                                            <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#replyForm-<?php echo $review['id']; ?>" aria-expanded="false" aria-controls="replyForm-<?php echo $review['id']; ?>">
                                                <i class="bi bi-reply-fill me-1"></i> <?php echo lang('reply_to_review'); ?>
                                            </button>
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
                                        <?php if (!empty($review['reply_text'])): ?>
                                            <div class="ms-md-4 mt-3 p-3 bg-light rounded border-start border-success border-4">
                                                <h6 class="text-success mb-1"><?php echo lang('organizer_reply'); ?>:</h6>
                                                <p class="mb-0 small text-break"><?php echo nl2br(e($review['reply_text'] ?? '')); ?></p>
                                                <?php if (!empty($review['reply_at'])): ?><small class="text-muted d-block mt-1"><?php echo e(format_date($review['reply_at'])); ?></small><?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="?page=events" class="btn btn-secondary">
                            <i class="bi bi-arrow-left-circle me-2"></i> <?php echo lang('back_to_events'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php /* Bootstrap bundle & global scripts loaded in footer.php */ ?>
