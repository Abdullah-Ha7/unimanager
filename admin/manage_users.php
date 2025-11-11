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

// ✅ التحقق من صلاحية المسؤول (role_id = 1)
if (!$user || $user['role_id'] != 1) {
    $_SESSION['error_message'] = ($lang == 'ar') 
        ? 'غير مصرح لك بالوصول إلى إدارة المستخدمين.' 
        : 'You are not authorized to access user management.';
    header("Location: " . BASE_URL . "/?page=login");
    exit;
}

// ----------------------------------------
// 1. معالجة طلب تحديث الدور (POST Request)
// ----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $target_user_id = intval($_POST['user_id']);
    $new_role_id = intval($_POST['new_role']);

    // فحص أمني: منع المسؤول من تغيير دوره
    if ($target_user_id === $user['id']) {
        $_SESSION['error_message'] = ($lang == 'ar') ? 'لا يمكنك تغيير دورك الخاص.' : 'You cannot change your own role.';
    } 
    // فحص صلاحية الدور المحدد
    elseif (!in_array($new_role_id, array_keys($roles))) {
        $_SESSION['error_message'] = ($lang == 'ar') ? 'دور غير صالح.' : 'Invalid role selected.';
    } 
    // تنفيذ التحديث
    else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
            $stmt->execute([$new_role_id, $target_user_id]);
            $_SESSION['success_message'] = ($lang == 'ar') ? 'تم تحديث دور المستخدم بنجاح.' : 'User role updated successfully.';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = ($lang == 'ar') ? 'خطأ في قاعدة البيانات: ' . $e->getMessage() : 'Database error: ' . $e->getMessage();
        }
    }
    // إعادة توجيه لتنظيف بيانات POST
    header("Location: " . BASE_URL . "/?page=manage_users");
    exit;
}

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
                                <form method="POST" class="d-flex align-items-center">
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
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>