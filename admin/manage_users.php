<?php
// C:\wamp64\www\Project-1\admin\manage_users.php

require_once __DIR__ . '/../config.php'; 
require_once __DIR__ . '/../functions.php';

$user = current_user();
$lang = $_SESSION['lang'] ?? 'en';
global $pdo;

// تعريف الأدوار لاستخدامها في العرض والقائمة المنسدلة
$roles = [
    1 => ($lang == 'ar') ? 'مسؤول (Admin)' : 'Admin',
    2 => ($lang == 'ar') ? 'منظم (Organizer)' : 'Organizer',
    3 => ($lang == 'ar') ? 'طالب/مسجل (Student)' : 'Student'
];

// ✅ التحقق من صلاحية المسؤول (role_id = 1) بدون استخدام header() بعد بدء الإخراج
if (!$user || $user['role_id'] != 1) {
    $msg = ($lang == 'ar') 
        ? 'غير مصرح لك بالوصول إلى إدارة المستخدمين.' 
        : 'You are not authorized to access user management.';
    echo '<section class="py-5"><div class="container">'
       . '<div class="alert alert-danger text-center">' . e($msg) . '</div>'
       . '<div class="text-center"><a class="btn btn-primary" href="' . BASE_URL . '/?page=login">'
       . (($lang=='ar') ? 'تسجيل الدخول' : 'Go to Login')
       . '</a></div></div></section>';
    return; // أوقف العرض هنا لتجنب المزيد من الإخراج
}

// ملاحظة: معالجة POST لنقل/تحديث الأدوار أصبحت ضمن صفحة إجراء مبكر admin_manage_users_action

// ----------------------------------------
// 2. جلب جميع المستخدمين
// ----------------------------------------
try {
    // جلب جميع المستخدمين، مرتبين حسب الدور والاسم
    $stmt = $pdo->prepare("SELECT id, name, email, role_id, created_at FROM users ORDER BY role_id, name");
    $stmt->execute();
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_users = [];
    $_SESSION['error_message'] = ($lang == 'ar') ? 'فشل جلب المستخدمين: ' . $e->getMessage() : 'Failed to fetch users: ' . $e->getMessage();
}

// ----------------------------------------
// 3. العرض في الجدول
// ----------------------------------------
?>

<section class="py-5" style="min-height:90vh;">
    <div class="container">
        <h2 class="fw-bold text-info mb-4 text-center">
            <i class="bi bi-person-rolodex"></i>
            <?php echo ($lang == 'ar') ? 'إدارة المستخدمين والأدوار' : 'Manage Users & Roles'; ?>
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
                <thead class="table-info">
                    <tr>
                        <th>#</th>
                        <th><?php echo ($lang == 'ar') ? 'الاسم' : 'Name'; ?></th>
                        <th><?php echo ($lang == 'ar') ? 'البريد الإلكتروني' : 'Email'; ?></th>
                        <th><?php echo ($lang == 'ar') ? 'الدور الحالي' : 'Current Role'; ?></th>
                        <th style="min-width: 250px;"><?php echo ($lang == 'ar') ? 'تغيير الدور' : 'Change Role'; ?></th>
                        <th><?php echo ($lang == 'ar') ? 'الإجراء' : 'Action'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_users)): ?>
                        <tr><td colspan="5" class="text-center text-muted"><?php echo ($lang == 'ar') ? 'لا يوجد مستخدمون لعرضهم.' : 'No users to display.'; ?></td></tr>
                    <?php endif; ?>
                    
                    <?php $i = 1; foreach ($all_users as $u): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo e($u['name']); ?></td>
                        <td><?php echo e($u['email']); ?></td>
                        <td>
                            <span class="badge 
                                <?php 
                                    if ($u['role_id'] == 1) echo 'bg-danger'; // أحمر للمسؤول
                                    elseif ($u['role_id'] == 2) echo 'bg-warning text-dark'; // أصفر للمنظم
                                    else echo 'bg-primary'; // أزرق للطالب
                                ?>
                            ">
                                <?php echo e($roles[$u['role_id']] ?? 'Unknown'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['id'] == $user['id']): ?>
                                <span class="text-muted small"><?php echo ($lang == 'ar') ? 'لا يمكن التعديل (أنت)' : 'Cannot edit (You)'; ?></span>
                            <?php else: ?>
                                <form method="POST" action="<?php echo BASE_URL; ?>/?page=admin_manage_users_action" class="d-flex align-items-center">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="update_role" value="1">
                                    
                                    <select name="new_role" class="form-select form-select-sm me-2" required>
                                        <?php foreach ($roles as $id => $name): ?>
                                            <option value="<?php echo $id; ?>" 
                                                <?php echo ($u['role_id'] == $id) ? 'selected' : ''; ?>>
                                                <?php echo e($name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    
                                    <button type="submit" class="btn btn-sm btn-info text-white">
                                        <?php echo ($lang == 'ar') ? 'تحديث' : 'Update'; ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                                                <td>
                                                        <?php if ($u['role_id'] != 1): // allow delete for organizer/student only ?>
                                                                <button type="button" 
                                                                                class="btn btn-sm btn-outline-danger delete-user-btn" 
                                                                                data-bs-toggle="modal" 
                                                                                data-bs-target="#deleteUserModal"
                                                                                data-user-id="<?php echo $u['id']; ?>"
                                                                                data-user-name="<?php echo e($u['name']); ?>">
                                                                        <i class="bi bi-trash"></i> <?php echo ($lang=='ar') ? 'حذف' : 'Delete'; ?>
                                                                </button>
                                                        <?php else: ?>
                                                                <span class="text-muted small"><?php echo ($lang=='ar') ? 'محمي' : 'Protected'; ?></span>
                                                        <?php endif; ?>
                                                </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteUserModalLabel"><?php echo ($lang=='ar') ? 'تأكيد حذف المستخدم' : 'Confirm User Deletion'; ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="deleteUserMessage">
                    <?php echo ($lang=='ar') ? 'هل أنت متأكد من حذف هذا المستخدم؟ لا يمكن التراجع عن هذا الإجراء.' : 'Are you sure you want to delete this user? This action cannot be undone.'; ?>
                </p>
                <p class="fw-bold mt-2" id="deleteUserName"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo ($lang=='ar') ? 'إلغاء' : 'Cancel'; ?></button>
                <form method="POST" action="<?php echo BASE_URL; ?>/?page=admin_manage_users_action" class="d-inline" id="deleteUserForm">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <input type="hidden" name="delete_user" value="1">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> <?php echo ($lang=='ar') ? 'نعم، حذف' : 'Yes, Delete'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var modal = document.getElementById('deleteUserModal');
    if(!modal) return;
    modal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if(!button) return;
        var userId = button.getAttribute('data-user-id');
        var userName = button.getAttribute('data-user-name');
        document.getElementById('deleteUserId').value = userId;
        document.getElementById('deleteUserName').innerText = userName;
    });
});
</script>