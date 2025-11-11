<?php
// C:\wamp64\www\Project-2\pages\event_detail.php

// ... (جميع الأكواد في بداية الملف يجب أن تبقى كما هي)
// ... (التحقق من $event و جلب بيانات الحجز للمستخدم الحالي يجب أن يبقى كما هو)

// =========================================================
// ✅ جلب التقييمات والردود
// =========================================================
$reviews_sql = "
    SELECT r.*, u.name AS reviewer_name, u.role_id 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.event_id = ? 
    ORDER BY r.review_at DESC
";
$reviews_stmt = $pdo->prepare($reviews_sql);
$reviews_stmt->execute([$id]);
$reviews = $reviews_stmt->fetchAll();

// =========================================================
// (بقية الكود الخاص بالـ HTML والـ body... حتى نصل لقسم التقييمات)
// =========================================================

// =========================================================
// ✅ 7. قسم التقييمات (Reviews Section) - تحديث الرابط والرد
// =========================================================
?>

<!-- ... (الأجزاء السابقة من صفحة تفاصيل الفعالية) -->

<div class="row mt-5">
    <div class="col-12">
        <h3 class="mb-4"><i class="bi bi-chat-left-text me-2"></i> <?php echo lang('reviews'); ?> (<?php echo count($reviews); ?>)</h3>

        <!-- نموذج إضافة تقييم جديد (يجب أن يكون موجودًا بالفعل) -->
        <?php if ($user && $can_review && !$has_reviewed): ?>
        <div class="card p-4 mb-4 shadow-sm border-info rounded-3">
            <h5 class="card-title text-info mb-3"><?php echo lang('write_your_review'); ?></h5>
            <!-- ... (هنا يتم تضمين نموذج التقييم POST) ... -->
        </div>
        <?php elseif ($user && $has_reviewed): ?>
            <div class="alert alert-warning rounded-3" role="alert">
                <i class="bi bi-info-circle me-2"></i> <?php echo lang('already_reviewed'); ?>.
            </div>
        <?php elseif ($user && $is_organizer): ?>
            <!-- المنظم لا يمكنه تقييم فعاليته -->
        <?php elseif ($user && !$can_review): ?>
            <div class="alert alert-secondary rounded-3" role="alert">
                <i class="bi bi-info-circle me-2"></i> <?php echo lang('book_to_review'); ?>
            </div>
        <?php elseif (!$user): ?>
            <div class="alert alert-info rounded-3" role="alert">
                <i class="bi bi-info-circle me-2"></i> <?php echo lang('login_to_review'); ?>
            </div>
        <?php endif; ?>

        <!-- عرض قائمة التقييمات -->
        <?php if (empty($reviews)): ?>
            <p class="text-muted text-center"><?php echo lang('no_reviews_yet'); ?></p>
        <?php endif; ?>

        <?php foreach ($reviews as $review): ?>
        <div class="card mb-3 shadow-sm rounded-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h6 class="mb-0 text-primary">
                            <i class="bi bi-person-circle me-1"></i> <?php echo e($review['reviewer_name']); ?>
                            <?php if ($review['role_id'] == 2): ?>
                                <span class="badge bg-secondary ms-2"><?php echo lang('organizer'); ?></span>
                            <?php endif; ?>
                        </h6>
                        <small class="text-muted"><?php echo format_date_relative($review['review_at'], $lang); ?></small>
                    </div>
                    <div>
                        <?php 
                        // دالة لعرض النجوم (يجب أن تكون معرفة في ملف functions.php أو أعلى الصفحة)
                        // نستخدمها هنا للتكرار فقط، الأفضل تعريفها في function.php
                        function display_stars_detail($rating) {
                            $output = '';
                            for ($i = 1; $i <= 5; $i++) {
                                $output .= ($i <= $rating) ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-muted"></i>';
                            }
                            return $output;
                        }
                        echo display_stars_detail($review['rating']); 
                        ?>
                    </div>
                </div>
                
                <!-- نص التقييم -->
                <p class="card-text text-break border-bottom pb-3 mb-3"><?php echo nl2br(e($review['comment'])); ?></p>
                
                <!-- ✅ منطق عرض الرد وزر الرد -->
                <?php if (!empty($review['reply_text'])): ?>
                    <!-- عرض الرد إذا كان موجوداً -->
                    <div class="alert alert-light border-start border-primary border-4 p-3 mt-3 mb-0">
                        <h6 class="text-primary mb-1"><i class="bi bi-patch-check-fill me-1"></i> <?php echo lang('organizer_reply'); ?></h6>
                        <p class="mb-0 text-break"><?php echo nl2br(e($review['reply_text'])); ?></p>
                        <small class="text-muted fst-italic float-end"><?php echo lang('at'); ?> <?php echo format_date_relative($review['reply_at'], $lang); ?></small>
                    </div>
                <?php elseif ($user && $is_organizer && $user['id'] == $event['organizer_id']): ?>
                    <!-- عرض زر الرد للمنظم مالك الفعالية فقط ولم يقم بالرد بعد -->
                    <div class="d-flex justify-content-end">
                        <a href="?page=reply_review&review_id=<?php echo $review['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                            <i class="bi bi-reply-fill me-1"></i> <?php echo lang('reply_to_review'); ?>
                        </a>
                    </div>
                <?php endif; ?>
                <!-- نهاية منطق عرض الرد وزر الرد -->

            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>

<!-- ... (بقية الكود الخاص بنهاية صفحة تفاصيل الفعالية) -->
