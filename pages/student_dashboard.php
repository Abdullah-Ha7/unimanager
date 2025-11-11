<?php
// C:\wamp64\www\Project-1\student_dashboard.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$lang = $_SESSION['lang'] ?? 'en';
require_once __DIR__ . "/../lang/$lang.php";

// ✅ Check if student is logged in
$user = current_user();
if (!$user || $user['role_id'] != 3) {
    header("Location: " . BASE_URL . "/?page=login");
    exit;
}

// ⚠️ Cancel booking logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    // يجب استخدام فلترة وتحقق أقوى من booking_id في تطبيق حقيقي
    $booking_id = intval($_POST['booking_id']);
    
    // يجب أن يتم التحقق من أن الفعالية لم تبدأ بعد قبل الإلغاء (لأسباب أمنية)
    
    try {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$booking_id, $user['id']])) {
            $_SESSION['success_message'] = lang('booking_cancel_success');
        } else {
            $_SESSION['error_message'] = lang('booking_cancel_fail');
        }
    } catch (PDOException $e) {
        error_log("Database error during booking cancellation: " . $e->getMessage());
        $_SESSION['error_message'] = lang('database_error_cancellation');
    }

    header("Location: ?page=student_dashboard");
    exit;
}

// ----------------------------------------------------
// ✅ 1. جلب حجوزات الطالب مع تفاصيل الفعالية وحالة التقييم
// ----------------------------------------------------
try {
    // جلب الحجوزات، تفاصيل الفعالية، وحالة التقييم للطالب
    // review_id سيحتوي على قيمة إذا كان الطالب قد قيم الفعالية
    $sql = "
        SELECT 
            b.id AS booking_id,
            e.id AS event_id,
            e.title,
            e.date,
            e.end_at,
            e.location,
            -- جلب معرف التقييم إن وجد
            r.id AS review_id 
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        LEFT JOIN reviews r ON r.event_id = e.id AND r.user_id = b.user_id
        WHERE b.user_id = ?
        ORDER BY e.date DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user['id']]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error fetching bookings: " . $e->getMessage());
    $bookings = [];
    $_SESSION['error_message'] = lang('database_fetch_error');
}

// تعريف نصوص الإلغاء للـ Modal (تستخدم كاحتياطي إن لم تكن موجودة في ملف اللغة)
$cancel_modal_title = $lang == 'ar' ? 'تأكيد الإلغاء' : 'Confirm Cancellation';
$cancel_modal_message = $lang == 'ar' ? 'هل أنت متأكد من إلغاء هذا الحجز؟ لا يمكن التراجع عن هذا الإجراء.' : 'Are you sure you want to cancel this booking? This action cannot be undone.';
$cancel_modal_btn = $lang == 'ar' ? 'نعم، إلغاء الحجز' : 'Yes, Cancel Booking';

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo lang('student_dashboard'); ?></title>
    <!-- استيراد Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- استخدام CSS مخصص للأزرار داخل الجدول -->
    <style>
        /* لضمان تنسيق جيد للأزرار داخل عمود Actions */
        .action-cell .btn {
            /* إضافة هامش بسيط بين الأزرار */
            margin-right: 4px;
            margin-bottom: 4px;
        }
    </style>
</head>

<body>

    <section class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-primary text-white text-center rounded-top-4 py-3">
                        <h1  class="h3 mb-0"><?php echo lang('student_dashboard'); ?></h1>
                        <p  class="mb-0" lang="<?php echo $lang; ?>" dir="<?php echo ($lang === 'ar' ? 'rtl' : 'ltr'); ?>">  <?php echo lang('Hello'); ?> , <?php echo e($user['name']); ?> </p>
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

                        <h2 class="h4 mb-4 text-primary"><i class="bi bi-calendar-check me-2"></i> <?php echo lang('my_current_bookings'); ?></h2>

                        <?php if (!empty($bookings)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>#</th>
                                            <th><?php echo lang('event_title'); ?></th>
                                            <th><?php echo lang('event_date'); ?></th>
                                            <th><?php echo lang('location'); ?></th>
                                            <th><?php echo lang('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $now = new DateTime();
                                        foreach ($bookings as $index => $b): 
                                            // تحديد تاريخ نهاية الفعالية للحالة
                                            $event_end_date = new DateTime($b['end_at'] ?? $b['date']);
                                            $is_finished = $event_end_date < $now;
                                        ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo e($b['title']); ?></td>
                                                <td><?php echo e(format_date($b['date'])); ?></td>
                                                <td><?php echo e($b['location']); ?></td>
                                                
                                                <td class="action-cell text-nowrap">
                                                    
                                                    <!-- ✅ 1. زر تفاصيل الفعالية (تم تصغير النص إلى "عرض") -->
                                                    <a href="?page=event_detail&id=<?php echo $b['event_id']; ?>" class="btn btn-sm btn-primary me-1">
                                                        <i class="bi bi-eye"></i>
                                                        <?php echo $lang == 'ar' ? 'عرض' : lang('view_event'); ?>
                                                    </a>

                                                    <!-- --------------------------------------- -->
                                                    <!-- ✅ 2. منطق التقييم (يظهر إن انتهت الفعالية) -->
                                                    <!-- --------------------------------------- -->
                                                    <?php if ($is_finished): ?>
                                                        <?php if ($b['review_id']): ?>
                                                            <!-- تم تقييمها -->
                                                            <button class="btn btn-sm btn-outline-success" disabled>
                                                                <i class="bi bi-star-fill"></i> 
                                                                <?php echo $lang == 'ar' ? 'تم التقييم' : lang('reviewed'); ?>
                                                            </button>
                                                        <?php else: ?>
                                                            <!-- لم يتم تقييمها بعد -->
                                                            <a href="?page=reviews&event_id=<?php echo $b['event_id']; ?>" class="btn btn-sm btn-warning me-1">
                                                                <i class="bi bi-star"></i> <?php echo $lang == 'ar' ? 'قيّم' : lang('review'); ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    
                                                    <!-- --------------------------------------- -->
                                                    <!-- ✅ 3. زر الإلغاء متاح للفعاليات القادمة فقط - يستخدم Modal -->
                                                    <!-- --------------------------------------- -->
                                                    <?php if (!$is_finished): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#cancelConfirmationModal"
                                                                data-booking-id="<?php echo $b['booking_id']; ?>">
                                                            <i class="bi bi-x-circle"></i> <?php echo $lang == 'ar' ? 'إلغاء' : lang('cancel'); ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center"><?php echo lang('no_bookings_found'); ?></div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <a href="?page=events" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left-circle me-2"></i>
                                <?php echo lang('browse_events'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal for Cancel Confirmation (Recommended replacement for confirm()) -->
    <div class="modal fade" id="cancelConfirmationModal" tabindex="-1" aria-labelledby="cancelConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancelConfirmationModalLabel"><?php echo $cancel_modal_title; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?php echo lang('close'); ?>"></button>
                </div>
                <div class="modal-body">
                    <?php echo $cancel_modal_message; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo lang('close'); ?></button>
                    <!-- Form for actual cancellation submission, uses JavaScript to set the booking_id before submission -->
                    <form id="cancelBookingForm" method="POST" action="?page=student_dashboard" class="d-inline">
                        <input type="hidden" name="booking_id" id="modalBookingId">
                        <button type="submit" name="cancel_booking" class="btn btn-danger">
                            <i class="bi bi-x-circle me-1"></i> <?php echo $cancel_modal_btn; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cancelConfirmationModal = document.getElementById('cancelConfirmationModal');
            const modalBookingIdInput = document.getElementById('modalBookingId');

            // Listen for the modal being shown
            cancelConfirmationModal.addEventListener('show.bs.modal', function (event) {
                // الزر الذي قام بتشغيل الـ Modal
                const button = event.relatedTarget;
                
                // استخراج معرف الحجز من السمة data-booking-id
                const bookingId = button.getAttribute('data-booking-id');
                
                // تحديث قيمة حقل الإدخال المخفي داخل الـ Modal
                modalBookingIdInput.value = bookingId;
            });
        });
    </script>
</body>

</html>
