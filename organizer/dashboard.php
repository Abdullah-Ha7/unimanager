<?php
// C:\wamp64\www\Project-1\pages\organizer_dashboard.php (أو المسار الصحيح لديك)

require_once __DIR__ . '/../config.php'; 
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../header.php';

$user = current_user();
$lang = $_SESSION['lang'] ?? 'en';
global $pdo;

// ----------------------------------------
// ✅ 1. التحقق من صلاحية المنظم (role_id = 2)
// ----------------------------------------
if (!$user || $user['role_id'] != 2) {
    $_SESSION['error_message'] = ($lang == 'ar') 
        ? 'غير مصرح لك بالوصول إلى لوحة تحكم المنظمين.' 
        : 'You are not authorized to access the Organizer Dashboard.';
    header("Location: " . BASE_URL . "/?page=login");
    exit;
}

// ----------------------------------------
// 2. جلب جميع فعاليات المنظم
// ----------------------------------------
try {
    // جلب جميع الفعاليات التي تطابق user_id المنظم الحالي
    // لا يوجد شرط على approval_status هنا
    $stmt = $pdo->prepare("
        SELECT *
        FROM events
        WHERE organizer_id = ? 
        ORDER BY date DESC
    ");
    $stmt->execute([$user['id']]);
    $organizer_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $organizer_events = [];
    $_SESSION['error_message'] = ($lang == 'ar') 
        ? 'فشل جلب فعالياتك: ' . $e->getMessage() 
        : 'Failed to fetch your events: ' . $e->getMessage();
}

// ----------------------------------------
// 3. العرض في لوحة التحكم
// ----------------------------------------
?>

<section class="py-5" style="min-height:90vh;">
    <div class="container">
        <h2 class="fw-bold text-success mb-4">
            <i class="bi bi-person-workspace"></i>
            <?php echo ($lang == 'ar') ? 'لوحة تحكم المنظم: ' : 'Organizer Dashboard: '; ?><?php echo e($user['name']); ?>
        </h2>
        <div class="mb-4 text-<?php echo ($lang == 'ar') ? 'start' : 'end'; ?>"> 
             <a href="events_create.php" class="btn btn-success btn-lg shadow-sm">
                <i class="bi bi-calendar-plus"></i> 
                <?php echo ($lang == 'ar') ? 'إنشاء فعالية جديدة' : 'Create New Event'; ?>
            </a>
        </div>
        
        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success text-center"><?php echo e($_SESSION['success_message']); ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger text-center"><?php echo e($_SESSION['error_message']); ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <h4 class="mb-3 mt-4"><?php echo ($lang == 'ar') ? 'فعالياتي المُقدمة' : 'My Submitted Events'; ?></h4>
        
        <div class="table-responsive bg-white shadow-sm p-3 rounded">
            <table class="table table-hover align-middle">
                <thead class="table-success">
                    <tr>
                        <th>#</th>
                        <th><?php echo ($lang == 'ar') ? 'العنوان' : 'Title'; ?></th>
                        <th><?php echo ($lang == 'ar') ? 'التاريخ' : 'Date'; ?></th>
                        <th><?php echo ($lang == 'ar') ? 'الحالة' : 'Status'; ?></th>
                        <th><?php echo ($lang == 'ar') ? 'الإجراءات' : 'Actions'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($organizer_events)): ?>
                        <tr><td colspan="5" class="text-center text-muted"><?php echo ($lang == 'ar') ? 'لم تقدم أي فعاليات بعد.' : 'You have not submitted any events yet.'; ?></td></tr>
                    <?php endif; ?>
                    
                    <?php $i = 1; foreach ($organizer_events as $event): ?>
                    <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo e($event['title']); ?></td>
                            <td><?php echo e($event['date']); ?></td>
                            
                            <td>
                                <span class="badge 
                                    <?php 
                                        if ($event['approval_status'] == 'approved') {
                                            echo 'bg-success'; // أخضر للمعتمد
                                        } elseif ($event['approval_status'] == 'rejected') {
                                            echo 'bg-danger'; // أحمر للمرفوض
                                        } else {
                                            echo 'bg-warning text-dark'; // أصفر للمعلق (pending)
                                        }
                                    ?>">
                                    <?php echo e($event['approval_status']); ?>
                                </span>
                            </td>
                        <td>
                            <!-- ✅ التصحيح الحرج: تغيير ? إلى & لربط ID بـ page -->
                            <a href="<?php echo BASE_URL; ?>/?page=event_detail&id=<?php echo $event['id']; ?>" 
                               class="btn btn-sm btn-outline-info me-2" 
                               title="<?php echo ($lang == 'ar') ? 'التفاصيل' : 'Details'; ?>">
                                <i class="bi bi-eye"></i> 
                                
                            </a>
                            <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary"> 
                                <i class="bi bi-pencil"></i>
                                
                                  
                            </a>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteConfirmationModal"
                                    data-event-id="<?php echo $event['id']; ?>"
                                    title="<?php echo ($lang == 'ar') ? 'حذف' : 'Delete'; ?>">
                                <i class="bi bi-trash"></i>
                            </button>


                        </td>

                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
     <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmationModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo ($lang == 'ar') ? 'تأكيد الحذف' : 'Confirm Deletion'; ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="lead">
                        <?php echo ($lang == 'ar') ? 'هل أنت متأكد من حذف هذه الفعالية؟ هذا الإجراء لا يمكن التراجع عنه.' : 'Are you absolutely sure you want to delete this event? This action cannot be undone.'; ?>
                    </p>
                    <p class="text-danger fw-semibold">
                        <?php echo ($lang == 'ar') ? 'سيتم حذف جميع البيانات المتعلقة بهذه الفعالية.' : 'All data associated with this event will be permanently removed.'; ?>
                    </p>
                    <small class="text-muted" id="modal-event-id-display"></small>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?php echo ($lang == 'ar') ? 'إلغاء' : 'Cancel'; ?>
                    </button>
                    <!-- النموذج الذي يرسل طلب POST إلى delete_event.php -->
                    <form id="deleteForm" method="POST" action="delete_event.php"> 
                        <input type="hidden" name="event_id" id="modal-event-id-input">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i> <?php echo ($lang == 'ar') ? 'تأكيد الحذف' : 'Confirm Delete'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
    // ✅ JavaScript لتمرير معرف الفعالية (ID) إلى نافذة Modal
    document.addEventListener('DOMContentLoaded', function () {
        const deleteConfirmationModal = document.getElementById('deleteConfirmationModal');
        if (deleteConfirmationModal) {
            deleteConfirmationModal.addEventListener('show.bs.modal', function (event) {
                // الزر الذي ضغط عليه المستخدم
                const button = event.relatedTarget; 
                // استخراج معرف الفعالية من خاصية data-event-id
                const eventId = button.getAttribute('data-event-id');

                // تحديث حقل الإدخال المخفي في نموذج الحذف
                const modalInput = deleteConfirmationModal.querySelector('#modal-event-id-input');
                modalInput.value = eventId;
                
                // عرض معرف الفعالية للمستخدم (اختياري)
                const modalDisplay = deleteConfirmationModal.querySelector('#modal-event-id-display');
                modalDisplay.textContent = `Event ID: ${eventId}`;
            });
        }
    });
</script>
</section>

<?php require_once __DIR__ . '/../footer.php'; ?>
