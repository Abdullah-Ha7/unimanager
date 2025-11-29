<?php
// C:\wamp64\www\unimanager\admin\admin_edit_event.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$lang = $_SESSION['lang'] ?? 'en';
require_once __DIR__ . "/../lang/$lang.php";

$user = current_user();
$event_id = intval($_GET['id'] ?? 0);
global $pdo;

// 1. التحقق من الصلاحية: يجب أن يكون مسؤولاً (role_id = 1)
 
if (!$user || $user['role_id'] != 1 || !$event_id) {
     $msg = lang('unauthorized_access');
     echo '<section class="py-5"><div class="container">'
         . '<div class="alert alert-danger text-center">' . e($msg) . '</div>'
         . '<div class="text-center"><a class="btn btn-primary" href="' . BASE_URL . '/?page=login">'
         . (($lang=='ar') ? 'تسجيل الدخول' : 'Go to Login')
         . '</a></div></div></section>';
     return;
}

// 2. جلب الفعالية
try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $event = null;
    error_log("DB Error fetching event for admin editing: " . $e->getMessage());
}

 
if (!$event) {
     $msg = lang('event_not_found');
     echo '<section class="py-5"><div class="container">'
         . '<div class="alert alert-warning text-center">' . e($msg) . '</div>'
         . '<div class="text-center"><a class="btn btn-outline-primary" href="' . BASE_URL . '/?page=manage_all_events">'
         . (($lang=='ar') ? 'الرجوع لإدارة الفعاليات' : 'Back to Manage Events')
         . '</a></div></div></section>';
     return;
}

// 3. معالجة إرسال النموذج (POST)
// Note: POST handling moved to early action admin_edit_event_action
?>


<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg border-0 p-4">
                    <h3 class="text-center fw-bold mb-4 text-primary">
                        <i class="bi bi-gear"></i> <?php echo lang('edit_event_admin'); ?>
                    </h3>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger text-center"><?php echo e($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo BASE_URL; ?>/?page=admin_edit_event_action">
                        <input type="hidden" name="id" value="<?php echo (int)$event_id; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label"><?php echo lang('event_title'); ?></label>
                            <input type="text" name="title" class="form-control" value="<?php echo e($event['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo lang('description'); ?></label>
                            <textarea name="description" class="form-control" rows="4" required><?php echo e($event['description']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo lang('event_date'); ?></label>
                                <input type="date" name="date" class="form-control" value="<?php echo e($event['date']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo lang('location'); ?></label>
                                <input type="text" name="location" class="form-control" value="<?php echo e($event['location']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo lang('approval_status'); ?></label>
                            <select name="approval_status" class="form-select" required>
                                <option value="pending" <?php echo ($event['approval_status'] == 'pending') ? 'selected' : ''; ?>>
                                    <?php echo lang('pending'); ?>
                                </option>
                                <option value="approved" <?php echo ($event['approval_status'] == 'approved') ? 'selected' : ''; ?>>
                                    <?php echo lang('approved'); ?>
                                </option>
                                <option value="rejected" <?php echo ($event['approval_status'] == 'rejected') ? 'selected' : ''; ?>>
                                    <?php echo lang('rejected'); ?>
                                </option>
                            </select>
                            
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> <?php echo lang('save_changes'); ?>
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <a href="<?php echo BASE_URL . '/?page=manage_all_events'; ?>" class="text-decoration-none">
                            <i class="bi bi-arrow-left-circle"></i> <?php echo lang('back_to_manage_events'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

