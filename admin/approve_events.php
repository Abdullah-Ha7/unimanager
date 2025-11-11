<?php
// C:\wamp64\www\Project-1\admin\approve_events.php

require_once __DIR__ . '/../config.php'; 
require_once __DIR__ . '/../functions.php';

$user = current_user();
$lang = $_SESSION['lang'] ?? 'en';
global $pdo;

// ✅ التحقق من صلاحية المسؤول (role_id = 1)
if (!$user || $user['role_id'] != 1) {
    $_SESSION['error_message'] = ($lang == 'ar') 
        ? 'غير مصرح لك بالوصول إلى صفحة اعتماد الفعاليات.' 
        : 'You are not authorized to access the event approval page.';
    header("Location: " . BASE_URL . "/?page=login");
    exit;
}

// ----------------------------------------
// 1. معالجة طلب الموافقة أو الرفض (POST Request)
// ----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $event_id = intval($_POST['event_id']);
    $action = $_POST['action']; // 'approve' or 'reject'
    
    // تحديد الحالة الجديدة بناءً على الإجراء
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    
    // تحديد رسالة النجاح
    $success_msg = ($action === 'approve') 
        ? (($lang == 'ar') ? 'تم اعتماد الفعالية بنجاح.' : 'Event approved successfully.')
        : (($lang == 'ar') ? 'تم رفض الفعالية بنجاح.' : 'Event rejected successfully.');

    try {
        // تحديث حالة الفعالية في قاعدة البيانات
        $stmt = $pdo->prepare("UPDATE events SET approval_status = ? WHERE id = ? AND approval_status = 'pending'");
        $stmt->execute([$new_status, $event_id]);
        
        // التحقق من أن التحديث تم
        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = $success_msg;
        } else {
            $_SESSION['error_message'] = ($lang == 'ar') ? 'لم يتم تحديث الفعالية. قد تكون قيد المراجعة بالفعل.' : 'Event not updated. It might be already reviewed.';
        }

    } catch (PDOException $e) {
        $_SESSION['error_message'] = ($lang == 'ar') 
            ? 'خطأ في قاعدة البيانات أثناء التحديث: ' . $e->getMessage() 
            : 'Database error during update: ' . $e->getMessage();
    }
    // إعادة توجيه لتنظيف بيانات POST
    header("Location: " . BASE_URL . "/?page=approve_events");
    exit;
}

// ----------------------------------------
// 2. جلب الفعاليات التي تنتظر المراجعة فقط
// ----------------------------------------
try {
    // جلب الفعاليات التي حالتها 'pending' فقط
    $stmt = $pdo->prepare("
        SELECT e.*, u.name as organizer_name
        FROM events e
        LEFT JOIN users u ON e.user_id = u.id
        WHERE e.approval_status = 'pending'
        ORDER BY e.created_at ASC
    ");
    $stmt->execute();
    $pending_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pending_events = [];
    $_SESSION['error_message'] = ($lang == 'ar') 
        ? 'فشل جلب الفعاليات قيد الانتظار: ' . $e->getMessage() 
        : 'Failed to fetch pending events: ' . $e->getMessage();
}

// ----------------------------------------
// 3. العرض في الجدول
// ----------------------------------------
?>

<section class="py-5" style="min-height:90vh;">
    <div class="container">
        <h2 class="fw-bold text-warning mb-4 text-center">
            <i class="bi bi-check2-square"></i>
            <?php echo ($lang == 'ar') ? 'مراجعة واعتماد الفعاليات' : 'Review & Approve Events'; ?>
        </h2>

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success text-center"><?php echo e($_SESSION['success_message']); ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger text-center"><?php echo e($_SESSION['error_message']); ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="table-responsive bg-white shadow-sm p-3 rounded">
            <table class="table table-hover align-middle">
                <thead class="table-warning">
                    <tr>
                        <th>#</th>
                        <th><?php echo ($lang == 'ar') ? 'عنوان الفعالية' : 'Event Title'; ?></th>
                        <th><?php echo ($lang == 'ar') ? 'المنظم' : 'Organizer'; ?></th>
                        <th><?php echo ($lang == 'ar') ? 'التاريخ' : 'Date'; ?></th>
                        <th style="min-width: 250px;"><?php echo ($lang == 'ar') ? 'الإجراءات' : 'Actions'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending_events)): ?>
                        <tr><td colspan="5" class="text-center text-muted"><?php echo ($lang == 'ar') ? 'لا توجد فعاليات تنتظر المراجعة حالياً.' : 'No events currently pending review.'; ?></td></tr>
                    <?php endif; ?>
                    
                    <?php $i = 1; foreach ($pending_events as $event): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td>
                            <a href="?page=event_detail&id=<?php echo $event['id']; ?>" class="text-decoration-none fw-semibold">
                                <?php echo e($event['title']); ?>
                            </a>
                        </td>
                        <td><?php echo e($event['organizer_name'] ?? (($lang == 'ar') ? 'غير معروف' : 'Unknown')); ?></td>
                        <td><?php echo e($event['date']); ?></td>
                        <td>
                            <form method="POST" class="d-inline me-2">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <button type="submit" name="action" value="approve" 
                                        class="btn btn-sm btn-success" 
                                        title="<?php echo ($lang == 'ar') ? 'اعتماد ونشر' : 'Approve & Publish'; ?>">
                                    <i class="bi bi-check2"></i> <?php echo ($lang == 'ar') ? 'قبول' : 'Approve'; ?>
                                </button>
                            </form>
                            
                            <button type="button" 
                                    class="btn btn-sm btn-danger btn-reject-modal" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#rejectConfirmModal"
                                    data-event-id="<?php echo $event['id']; ?>"
                                    data-event-title="<?php echo e($event['title']); ?>"
                                    title="<?php echo ($lang == 'ar') ? 'رفض' : 'Reject'; ?>">
                                <i class="bi bi-x-lg"></i> <?php echo ($lang == 'ar') ? 'رفض' : 'Reject'; ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<div class="modal fade" id="rejectConfirmModal" tabindex="-1" aria-labelledby="rejectConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger" id="rejectConfirmModalLabel">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?php echo ($lang == 'ar') ? 'تأكيد الرفض' : 'Confirm Rejection'; ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><?php echo ($lang == 'ar') ? 'هل أنت متأكد من رفض الفعالية' : 'Are you sure you want to reject the event'; ?>:</p>
        <p class="fw-bold text-dark" id="eventTitlePlaceholder"></p>
        <p class="text-muted small"><?php echo ($lang == 'ar') ? 'لن يتمكن المنظم من تعديلها بعد الرفض.' : 'The organizer will not be able to modify it after rejection.'; ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <?php echo ($lang == 'ar') ? 'إلغاء' : 'Cancel'; ?>
        </button>
        <form id="rejectForm" method="POST" class="d-inline">
            <input type="hidden" name="event_id" id="modalEventId">
            <button type="submit" name="action" value="reject" class="btn btn-danger">
                <?php echo ($lang == 'ar') ? 'نعم، قم بالرفض' : 'Yes, Reject'; ?>
            </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- ---------------------------------------- -->
<!-- 5. كود JavaScript لربط الـ Modal -->
<!-- ---------------------------------------- -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rejectConfirmModal = document.getElementById('rejectConfirmModal');
    if (rejectConfirmModal) {
        rejectConfirmModal.addEventListener('show.bs.modal', function (event) {
            // الزر الذي شغل الـ Modal
            const button = event.relatedTarget;
            
            // استخراج البيانات من خصائص البيانات
            const eventId = button.getAttribute('data-event-id');
            const eventTitle = button.getAttribute('data-event-title');
            
            // تحديث العناصر داخل الـ Modal
            const modalEventId = rejectConfirmModal.querySelector('#modalEventId');
            const eventTitlePlaceholder = rejectConfirmModal.querySelector('#eventTitlePlaceholder');
            
            // تعبئة البيانات
            if (modalEventId) {
                modalEventId.value = eventId;
            }
            if (eventTitlePlaceholder) {
                eventTitlePlaceholder.textContent = eventTitle;
            }
        });
    }
});
</script>
