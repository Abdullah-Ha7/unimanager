<?php
// C:\wamp64\www\unimanager\admin\system_config.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$lang = $_SESSION['lang'] ?? 'en';
require_once __DIR__ . "/../lang/$lang.php";

$user = current_user();
global $pdo;
$settings = [];
$error = '';
$success = '';

// 1. التحقق من الصلاحية: يجب أن يكون مسؤولاً (role_id = 1)
if (!$user || $user['role_id'] != 1) {
    $_SESSION['error_message'] = lang('unauthorized_access');
    header("Location: " . BASE_URL . "/?page=login");
    exit;
}

// 2. جلب الإعدادات الحالية من قاعدة البيانات
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $settings = $db_settings;
} catch (PDOException $e) {
    error_log("DB Error fetching settings: " . $e->getMessage());
    $error = lang('database_error') . ' ' . lang('settings_fetch_failed');
}

// 3. معالجة إرسال النموذج (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $updates = [];
    $keys_to_update = ['site_title_ar', 'site_title_en', 'admin_email', 'registration_open', 'events_per_page'];

    try {
        // تحديث كل مفتاح إعداد
        foreach ($keys_to_update as $key) {
            $value = trim($_POST[$key] ?? '');
            
            // تحقق بسيط من الصحة
            if ($key == 'registration_open') {
                $value = ($value == '1') ? '1' : '0';
            } elseif ($key == 'events_per_page') {
                $value = intval($value) > 0 ? intval($value) : 10;
            } elseif (empty($value)) {
                // تخطي الحقول الفارغة أو التعامل معها كخطأ حسب المتطلبات
                continue; 
            }

            $updates[] = [$value, $key];
            $settings[$key] = $value; // تحديث المتغير لعرض القيمة الجديدة
        }

        // تنفيذ التحديثات
        $pdo->beginTransaction();
        $updateStmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        
        foreach ($updates as $data) {
            $updateStmt->execute($data);
        }
        $pdo->commit();
        
        $success = lang('settings_updated_success');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("DB Error updating settings: " . $e->getMessage());
        $error = lang('database_error') . ' ' . lang('settings_update_failed');
    }
}
?>


<section class="py-5" style="min-height: 80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg border-0 p-4">
                    <h3 class="text-center fw-bold mb-4 text-secondary">
                        <i class="bi bi-sliders"></i> <?php echo lang('system_configuration'); ?>
                    </h3>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success text-center"><?php echo e($success); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger text-center"><?php echo e($error); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <h5 class="mb-3 text-primary"><?php echo lang('general_settings'); ?></h5>
                        <hr>
                        
                        <div class="mb-3">
                            <label for="site_title_ar" class="form-label"><?php echo lang('site_title_ar'); ?></label>
                            <input type="text" name="site_title_ar" id="site_title_ar" class="form-control" 
                                   value="<?php echo e($settings['site_title_ar'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="site_title_en" class="form-label"><?php echo lang('site_title_en'); ?></label>
                            <input type="text" name="site_title_en" id="site_title_en" class="form-control" 
                                   value="<?php echo e($settings['site_title_en'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="admin_email" class="form-label"><?php echo lang('admin_email'); ?></label>
                            <input type="email" name="admin_email" id="admin_email" class="form-control" 
                                   value="<?php echo e($settings['admin_email'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="events_per_page" class="form-label"><?php echo lang('events_per_page'); ?></label>
                            <input type="number" name="events_per_page" id="events_per_page" class="form-control" 
                                   value="<?php echo e($settings['events_per_page'] ?? '10'); ?>" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block"><?php echo lang('registration_status'); ?></label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="registration_open" id="reg_open_yes" value="1" 
                                       <?php echo (($settings['registration_open'] ?? '1') == '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="reg_open_yes"><?php echo lang('open'); ?></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="registration_open" id="reg_open_no" value="0" 
                                       <?php echo (($settings['registration_open'] ?? '1') == '0') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="reg_open_no"><?php echo lang('closed'); ?></label>
                            </div>
                            
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mt-4">
                            <i class="bi bi-save"></i> <?php echo lang('save_settings'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../footer.php'; ?>