<?php
// C:\wamp64\www\unimanager\admin\manage_all_events.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$lang = $_SESSION['lang'] ?? 'en';
require_once __DIR__ . "/../lang/$lang.php";

$user = current_user();
global $pdo;

// 1. التحقق من الصلاحية: يجب أن يكون مسؤولاً (role_id = 1)
if (!$user || $user['role_id'] != 1) {
    $_SESSION['error_message'] = lang('unauthorized_access');
    header("Location: " . BASE_URL . "/?page=login");
    exit;
}

// 2. جلب جميع الفعاليات من قاعدة البيانات
try {
    $stmt = $pdo->prepare("
        SELECT e.*, u.name AS organizer_name 
        FROM events e
        LEFT JOIN users u ON e.organizer_id = u.id
        ORDER BY e.created_at DESC
    ");
    $stmt->execute();
    $all_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("DB Error fetching all events for admin: " . $e->getMessage());
    $all_events = [];
    $error = lang('database_error');
}

?>
<?php // Header and opening <body> are handled by index.php -> header.php ?>

<section class="py-5 min-h-60vh">
    <div class="container">
        <h2 class="fw-bold text-center mb-5 text-primary">
            <i class="bi bi-calendar-check"></i> <?php echo lang('manage_all_events'); ?>
        </h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success text-center"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger text-center"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover table-bordered shadow-sm">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th><?php echo lang('event_title'); ?></th>
                        <th><?php echo lang('organizer'); ?></th>
                        <th><?php echo lang('status'); ?></th>
                        <th><?php echo lang('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_events)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted"><?php echo lang('no_events_found'); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php $i = 1; foreach ($all_events as $event): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo e($event['title']); ?></td>
                            <td><?php echo e($event['organizer_name'] ?? lang('not_available')); ?></td>
                            <td>
                                <?php echo display_status_badge($event['approval_status']); ?>
                            </td>
                            <td>
                                <a href="?page=admin_edit_event&id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary me-2" title="<?php echo lang('edit'); ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                
                                <!-- ✅ تم تعديل الزر لفتح Modal التأكيد -->
                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteConfirmationModal" 
                                        data-event-id="<?php echo $event['id']; ?>"
                                        title="<?php echo lang('delete'); ?>">
                                    <i class="bi bi-trash"></i>
                                </button>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ----------------------------------------------------------------- -->
<!-- ✅ Modal لتأكيد الحذف (بديل confirm()) -->
<!-- ----------------------------------------------------------------- -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmationModalLabel"><i class="bi bi-exclamation-triangle me-2"></i> <?php echo e($event['title']); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?php echo lang('confirm_delete_event'); ?></p>
                <div class="alert alert-danger small mb-0 mt-3">
                    <i class="bi bi-info-circle me-1"></i> <?php echo lang('deletion_warning'); ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo lang('cancel'); ?></button>
                
                <!-- النموذج الذي سيتم إرساله عند التأكيد -->
                <form method="POST" action="?page=admin_delete_event" id="deleteEventForm" style="display:inline;">
                    <input type="hidden" name="event_id" id="modal-event-id" value="">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> <?php echo lang('delete_confirm'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ----------------------------------------------------------------- -->
<!-- ✅ Script لنقل معرف الفعالية إلى الـ Modal -->
<!-- ----------------------------------------------------------------- -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteConfirmationModal = document.getElementById('deleteConfirmationModal');
        // الاستماع لحدث فتح الـ Modal
        deleteConfirmationModal.addEventListener('show.bs.modal', function (event) {
            // الزر الذي ضغط عليه المستخدم (زر الحذف)
            const button = event.relatedTarget; 
            // استخراج معرف الحجز من خاصية data-event-id
            const eventId = button.getAttribute('data-event-id');
            
            // تحديث قيمة الحقل المخفي في نموذج الحذف داخل الـ Modal
            const modalEventIdInput = deleteConfirmationModal.querySelector('#modal-event-id');
            modalEventIdInput.value = eventId;
        });
    });
</script>

<?php
// دالة مساعدة لتمثيل حالة الاعتماد بشارة (Badge)
function display_status_badge($status) {
    switch ($status) {
        case 'approved':
            return '<span class="badge bg-success">' . lang('Approved') . '</span>';
        case 'pending':
            return '<span class="badge bg-warning text-dark">' . lang('Pending') . '</span>';
        case 'rejected':
            return '<span class="badge bg-danger">' . lang('Rejected') . '</span>';
        default:
            return '<span class="badge bg-secondary">' . lang('N/A') . '</span>';
    }
}
?>
<?php // Footer and scripts are handled by index.php -> footer.php ?>