<?php
// C:\wamp64\www\Project-1\admin\admin_dashboard.php

// ✅ يجب تعديل مسار تضمين الملفات للخروج من مجلد admin والدخول إلى المجلد الرئيسي
require_once __DIR__ . '/../config.php'; 
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../header.php';

$user = current_user();
$lang = $_SESSION['lang'] ?? 'en';

// ✅ التحقق من صلاحية الوصول: يجب أن يكون مسؤولاً (role_id = 1)
if (!$user || $user['role_id'] != 1) {
    $_SESSION['error_message'] = ($lang == 'ar') 
        ? 'غير مصرح لك بالوصول إلى لوحة تحكم المسؤول.' 
        : 'You are not authorized to access the Administrator Dashboard.';
    header("Location: " . BASE_URL . "/?page=login");
    exit;
}

?>

<section class="py-5" style="min-height:90vh;">
  <div class="container">
    <h2 class="fw-bold text-primary mb-5 text-center">
      <i class="bi bi-shield-lock"></i> 
      <?php echo ($lang == 'ar') ? 'لوحة تحكم المسؤول' : 'Administrator Dashboard'; ?>
    </h2>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success text-center"><?php echo e($_SESSION['success_message']); ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="row justify-content-center">

      <div class="col-md-4 mb-4">
        <div class="card shadow h-100 text-center border-info">
          <div class="card-body">
            <i class="bi bi-person-rolodex display-4 text-info"></i>
            <h5 class="card-title mt-3 fw-bold"><?php echo ($lang == 'ar') ? 'إدارة المستخدمين والأدوار' : 'Manage Users & Roles'; ?></h5>
            <p class="card-text text-muted"><?php echo ($lang == 'ar') ? 'تعديل أدوار المستخدمين وصلاحياتهم.' : 'Modify user roles and access permissions.'; ?></p>
            <a href="?page=manage_users" class="btn btn-outline-info w-100 mt-2">
                <?php echo ($lang == 'ar') ? 'انتقال' : 'Go to Management'; ?>
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-4 mb-4">
        <div class="card shadow h-100 text-center border-success">
          <div class="card-body">
            <i class="bi bi-calendar-check display-4 text-success"></i>
            <h5 class="card-title mt-3 fw-bold"><?php echo ($lang == 'ar') ? 'إدارة جميع الفعاليات' : 'Manage All Events'; ?></h5>
            <p class="card-text text-muted"><?php echo ($lang == 'ar') ? 'إنشاء، تعديل، أو حذف أي فعالية.' : 'Create, edit, or delete any event.'; ?></p>
            <a href="?page=manage_all_events" class="btn btn-outline-success w-100 mt-2">
                <?php echo ($lang == 'ar') ? 'انتقال' : 'Go to Management'; ?>
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-4 mb-4">
        <div class="card shadow h-100 text-center border-warning">
          <div class="card-body">
            <i class="bi bi-check2-square display-4 text-warning"></i>
            <h5 class="card-title mt-3 fw-bold"><?php echo ($lang == 'ar') ? 'مراجعة واعتماد الفعاليات' : 'Review & Approve Events'; ?></h5>
            <p class="card-text text-muted"><?php echo ($lang == 'ar') ? 'مراجعة واعتماد فعاليات المنظمين.' : 'Review and approve events submitted by organizers.'; ?></p>
            <a href="?page=approve_events" class="btn btn-outline-warning w-100 mt-2">
                <?php echo ($lang == 'ar') ? 'انتقال' : 'Go to Approval'; ?>
            </a>
          </div>
        </div>
      </div>
      
      <div class="col-md-4 mb-4">
        <div class="card shadow h-100 text-center border-danger">
          <div class="card-body">
            <i class="bi bi-gear display-4 text-danger"></i>
            <h5 class="card-title mt-3 fw-bold"><?php echo ($lang == 'ar') ? 'إعدادات النظام' : 'System Configuration'; ?></h5>
            <p class="card-text text-muted"><?php echo ($lang == 'ar') ? 'تعديل إعدادات النظام العامة.' : 'Change global system settings.'; ?></p>
            <a href="?page=system_config" class="btn btn-outline-danger w-100 mt-2">
                <?php echo ($lang == 'ar') ? 'انتقال' : 'Go to Settings'; ?>
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>