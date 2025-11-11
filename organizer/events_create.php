<?php
// C:\wamp64\www\Project-1\organizer\events_create.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// ✅ Organizer access check
$user = current_user();
if (!$user || $user['role_id'] != 2) {
    header("Location: " . BASE_URL . "/?page=login");
    exit;
}

$success = $error = "";

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $date = $_POST['date'] ?? '';
    $end_raw = $_POST['end_at'] ?? '';
    // تحويل التاريخ إلى تنسيق قاعدة البيانات، أو null إذا كان فارغًا
    $end_at = $end_raw ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $end_raw))) : null;
    $location = trim($_POST['location'] ?? '');
    $category = trim($_POST['category'] ?? '');
    // ✅ 1. جلب قيمة السعة الجديدة
    $capacity = intval($_POST['capacity'] ?? 0); 
    
    // التحقق من الحقول الإلزامية
    if ($title && $desc && $date && $location && $capacity >= 0) { // التحقق من السعة كقيمة صالحة
        
        $image_filename = null;
        
        // 🖼️ معالجة تحميل الصورة
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $target_dir = __DIR__ . "/../uploads/events/";
            $image_filename = uniqid('event_') . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $target_file = $target_dir . $image_filename;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $error = lang("❌ Failed to upload image.");
            }
        }
        
        if (empty($error)) {
            try {
                // ✅ 2. تحديث استعلام SQL لإضافة capacity
                // ملاحظة: حالة الموافقة (approval_status) يتم تعيينها على 'pending' افتراضياً
                $sql = "INSERT INTO events (organizer_id, title, description, date, end_at, location, category, capacity, image, approval_status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
                
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([
                    $user['id'],
                    $title,
                    $desc,
                    $date,
                    $end_at, 
                    $location,
                    $category,
                    $capacity, // ✅ تمرير السعة
                    $image_filename,
                ])) {
                    $success = lang("✅ Event created successfully! Waiting for admin approval.");
                } else {
                    $error = lang("❌ Database error: Event could not be created.");
                }
            } catch (PDOException $e) {
                // ⚠️ يمكنك إزالة هذا السطر بعد الاختبار
                error_log("Database Error: " . $e->getMessage()); 
                $error = lang("❌ Database error: ") . $e->getMessage();
            }
        }
    } else {
        $error = lang("⚠️ Please fill all required fields correctly. Capacity must be a number greater than or equal to 0.");
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>" dir="<?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'rtl' : 'ltr'; ?>">

<head>
  <?php include '../header.php'; ?>
  <title><?php echo lang('create_new_event'); ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>

  <section class="container my-5">
    <div class="row justify-content-center">
      <div class="col-lg-8 col-md-10">
        <div class="card shadow-lg border-0 rounded-4">
          <div class="card-header bg-primary text-white text-center rounded-top-4 py-3">
            <h1 class="h3 mb-0"><?php echo lang('create_new_event'); ?></h1>
          </div>
          <div class="card-body p-4">

            <?php if ($success): ?>
              <div class="alert alert-success text-center"><?php echo e($success); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
              <div class="alert alert-danger text-center"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" action="">

              <div class="mb-3">
                <label for="title" class="form-label">
                  <?php echo lang('event_title'); ?> <span class="text-danger">*</span>
                </label>
                <input type="text" name="title" id="title" class="form-control" required value="<?php echo e($_POST['title'] ?? ''); ?>">
              </div>

              <div class="mb-3">
                <label for="description" class="form-label">
                  <?php echo lang('description'); ?> <span class="text-danger">*</span>
                </label>
                <textarea name="description" id="description" class="form-control" rows="4" required><?php echo e($_POST['description'] ?? ''); ?></textarea>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="date" class="form-label">
                    <?php echo lang('start_date'); ?> <span class="text-danger">*</span>
                  </label>
                  <input type="datetime-local" name="date" id="date" class="form-control" required value="<?php echo e($_POST['date'] ?? ''); ?>">
                </div>

                <div class="col-md-6 mb-3">
                  <label for="end_at" class="form-label">
                    <?php echo lang('end_date') . ' (' . lang('optional') . ')'; ?>
                  </label>
                  <!-- end_at is optional, so it is not required -->
                  <input type="datetime-local" name="end_at" id="end_at" class="form-control" value="<?php echo e($_POST['end_at'] ?? ''); ?>">
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="location" class="form-label">
                    <?php echo lang('location'); ?> <span class="text-danger">*</span>
                  </label>
                  <input type="text" name="location" id="location" class="form-control" required value="<?php echo e($_POST['location'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                  <label for="category" class="form-label">
                    <?php echo lang('category'); ?> <span class="text-danger">*</span>
                  </label>
                  <input type="text" name="category" id="category" class="form-control" required value="<?php echo e($_POST['category'] ?? ''); ?>">
                </div>
              </div>

              <!-- ✅ 3. حقل السعة الجديد -->
              <div class="mb-3">
                <label for="capacity" class="form-label">
                    <?php echo lang('event_capacity'); ?> 
                    <span class="text-muted small">(<?php echo lang('use_zero_for_unlimited'); ?>)</span> 
                    <span class="text-danger">*</span>
                </label>
                <input type="number" name="capacity" id="capacity" class="form-control" min="0" required value="<?php echo e($_POST['capacity'] ?? 0); ?>">
              </div>
              
              <div class="mb-4">
                <label for="image" class="form-label">
                  <?php echo lang('event_image') . ' (' . lang('optional') . ')'; ?>
                </label>
                <input type="file" name="image" id="image" class="form-control" accept="image/*">
              </div>

              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-plus-circle"></i>
                <?php echo lang('save_event'); ?>
              </button>
            </form>

            <div class="text-center mt-4">
              <a href="organizer_dashboard.php" class="text-decoration-none">
                <i class="bi bi-arrow-left-circle"></i>
                <?php echo lang('back_to_dashboard'); ?>
              </a>
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include '../footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// ... منطق حذف الفعاليات المنتهية (تم نقله إلى نهاية الملف)
$stmt = $pdo->prepare("DELETE FROM events WHERE end_at IS NOT NULL AND end_at <= NOW()");
$deleted = 0;
if ($stmt->execute()) {
    $deleted = $stmt->rowCount();
}

// ⚠️ يتم الاحتفاظ بمنطق حذف الفعاليات المنتهية هنا، ولكن عادةً يجب أن يكون في مهمة مجدولة
// يمكنك رؤية العدد المحذوف في السجلات إذا كنت بحاجة إليه:
if ($deleted > 0) {
  error_log("Clean-up: Deleted {$deleted} expired events.");
}
?>
