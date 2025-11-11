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
    $_SESSION['error_message'] = lang('unauthorized_access');
    header("Location: " . BASE_URL . "/?page=login");
    exit;
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
    $_SESSION['error_message'] = lang('event_not_found');
    header("Location: " . BASE_URL . "/?page=manage_all_events");
    exit;
}

// 3. معالجة إرسال النموذج (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $date = $_POST['date'];
    $location = trim($_POST['location']);
    $status = $_POST['approval_status']; // ✅ حالة الاعتماد

    // التحقق من صلاحية حالة الاعتماد
    $valid_statuses = ['pending', 'approved', 'rejected'];
    if (!in_array($status, $valid_statuses)) {
        $status = $event['approval_status']; // نستخدم القيمة القديمة إذا كانت الحالة غير صالحة
    }

    if ($title && $desc && $date && $location) {
        // تحديث البيانات في قاعدة البيانات
        $updateStmt = $pdo->prepare("
            UPDATE events 
            SET title = ?, description = ?, date = ?, location = ?, approval_status = ?
            WHERE id = ?
        ");
        
        if ($updateStmt->execute([$title, $desc, $date, $location, $status, $event_id])) {
            $_SESSION['success_message'] = lang('event_updated_success');
            header("Location: " . BASE_URL . "/?page=manage_all_events");
            exit;
        } else {
            $error = lang('event_update_failed');
        }
    } else {
        $error = lang('all_fields_required');
    }
}
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

                    <form method="POST">
                        
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

