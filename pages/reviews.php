<?php
// C:\wamp64\www\Project-1\pages\review.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php'; // ✅ تم التأكد من تضمين functions.php

if (session_status() === PHP_SESSION_NONE) session_start();
$lang = $_SESSION['lang'] ?? 'en';
require_once __DIR__ . "/../lang/$lang.php";

// ✅ 1. تحقق من تسجيل دخول الطالب
$user = current_user();
if (!$user || $user['role_id'] != 3) {
    $_SESSION['error_message'] = lang('login_as_student_first');
    header("Location: ?page=login");
    exit;
}

$event_id = intval($_GET['event_id'] ?? 0);
$success = $error = "";

// ✅ 2. جلب تفاصيل الفعالية والتحقق من الحجز وحالة التقييم
if ($event_id > 0) {
    // جلب تفاصيل الفعالية والتحقق من حالة الحجز والتقييم
    $sql = "
        SELECT 
            e.id, 
            e.title, 
            e.date, 
            e.end_at, 
            b.id AS booking_id,
            r.id AS review_id
        FROM events e
        JOIN bookings b ON b.event_id = e.id
        LEFT JOIN reviews r ON r.event_id = e.id AND r.user_id = b.user_id
        WHERE e.id = ? AND b.user_id = ?
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$event_id, $user['id']]);
    $event = $stmt->fetch();

    if (!$event) {
        // إذا لم يتم العثور على الفعالية أو لم يكن الطالب قد حجزها
        $_SESSION['error_message'] = lang('review_event_not_found_or_booked');
        header("Location: ?page=student_dashboard");
        exit;
    }

    // ❌ تم إزالة التحقق من انتهاء التاريخ للسماح بالتقييم المبكر.

    if ($event['review_id']) {
        // إذا كان الطالب قد أجرى تقييمًا بالفعل
        $_SESSION['error_message'] = lang('already_reviewed_event');
        header("Location: ?page=student_dashboard");
        exit;
    }
} else {
    $_SESSION['error_message'] = lang('no_event_selected');
    header("Location: ?page=student_dashboard");
    exit;
}


// ✅ 3. معالجة إرسال التقييم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = lang('rating_must_be_between_1_and_5');
    } elseif (strlen($comment) > 500) {
        $error = lang('comment_too_long');
    }

    if (empty($error)) {
        try {
            // إدراج التقييم الجديد
            $sql = "INSERT INTO reviews (user_id, event_id, rating, comment, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$user['id'], $event_id, $rating, $comment])) {
                $_SESSION['success_message'] = lang('review_submitted_successfully');
            } else {
                $_SESSION['error_message'] = lang('review_submission_failed');
            }
        } catch (PDOException $e) {
            error_log("Review Database Error: " . $e->getMessage());
            $_SESSION['error_message'] = lang('database_error_occurred');
        }
        header("Location: ?page=student_dashboard");
        exit;
    }
}

$event_end_date = new DateTime($event['end_at'] ?? $event['date']);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <title><?php echo lang('review_event'); ?>: <?php echo e($event['title']); ?></title>
    <style>
        /* Star rating (blue theme) */
        .star-rating {
            display: flex;
            /* نجعل الترتيب معكوساً حتى يعمل CSS المركب مع inputs المولدة (5..1) */
            flex-direction: row-reverse;
            justify-content: center;
            gap: 4px;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 2.5rem;
            /* لون افتراضي أفتح لينسجم مع الخلفيات */
            color: #cfe8ff;
            cursor: pointer;
            padding: 0 5px;
            transition: color 0.15s ease, transform 0.08s ease;
            line-height: 1;
            display: inline-block;
        }

        /* عند اختيار قيمة: نجعل النجوم المختارة بلون أزرق واضح */
        .star-rating input:checked ~ label,
        /* بعض البراوزرات تحتاج هذه القاعدة الإضافية لضمان تلوين العناصر المجاورة */
        .star-rating input:checked ~ label ~ label {
            color: #0056b3;
        }

        /* عند التحويم على الحاوية، نجعل جميع النجوم تلميح أزرق فاتح */
        .star-rating:hover label {
            color: #9ed0ff;
        }

        /* عند التحويم فوق نجمة واحدة: نجعلها وأي نجوم "أقل" منها أزرق غامق ومكبر قليلاً */
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #f603a5ff;
            transform: scale(1.06);
        }

        /* دعم لوحة المفاتيح: إبراز النجمة عند التركيز */
        .star-rating label:focus {
            outline: 2px solid rgba(0, 123, 255, 0.25);
            outline-offset: 4px;
        }
    </style>
</head>

<body>

    <section class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-warning text-white text-center rounded-top-4 py-3">
                        <h1 class="h3 mb-0"><?php echo lang('review_event'); ?>: <?php echo e($event['title']); ?></h1>
                    </div>
                    <div class="card-body p-4">

                        <?php if ($error): ?>
                            <div class="alert alert-danger text-center"><?php echo e($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">

                            <div class="mb-4 text-center">
                                <label class="form-label h5 mb-3"><?php echo lang('your_rating'); ?> <span class="text-danger">*</span></label>
                                <div class="star-rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                        <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> <?php echo lang('stars'); ?>">&#9733;</label>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="comment" class="form-label"><?php echo lang('your_comment'); ?> (<?php echo lang('optional'); ?>)</label>
                                <textarea name="comment" id="comment" class="form-control" rows="5" maxlength="500"></textarea>
                                <small class="form-text text-muted"><?php echo lang('max_500_chars'); ?></small>
                            </div>

                            <button type="submit" name="submit_review" class="btn btn-warning w-100">
                                <i class="bi bi-send"></i>
                                <?php echo lang('submit_review'); ?>
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <a href="?page=student_dashboard" class="text-decoration-none">
                                <i class="bi bi-arrow-left-circle"></i>
                                <?php echo lang('back_to_dashboard'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
