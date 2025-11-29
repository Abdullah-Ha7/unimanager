<?php
// C:\wamp64\www\Project-1\organizer\events_create.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// Ensure session for idempotency token
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// ✅ Organizer access check
$user = current_user();
if (!$user || $user['role_id'] != 2) {
    header("Location: " . BASE_URL . "/?page=login");
    exit;
}

$success = $error = "";

// Post/Redirect/Get success flag
if (isset($_GET['created']) && $_GET['created'] == '1') {
  $success = lang("✅ Event created successfully! Waiting for admin approval.");
}

// Generate / refresh one-time event creation token
if (empty($_SESSION['event_create_token'])) {
  $_SESSION['event_create_token'] = bin2hex(random_bytes(16));
}

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Idempotency / double-submit protection
  $submitted_token = $_POST['event_create_token'] ?? '';
  if ($submitted_token !== ($_SESSION['event_create_token'] ?? null)) {
    $error = lang('⚠️ Invalid or expired submission token. Please reload the page.');
  } else {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $start_at = $_POST['start_at'] ?? '';
    $end_raw = $_POST['end_at'] ?? '';
    // تحويل التاريخ إلى تنسيق قاعدة البيانات، أو null إذا كان فارغًا
    $end_at = $end_raw ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $end_raw))) : null;
    $location = trim($_POST['location'] ?? '');
    $category = trim($_POST['category'] ?? '');
    // ✅ 1. جلب قيمة السعة الجديدة
    $capacity = intval($_POST['capacity'] ?? 0); 
    
    // التحقق من الحقول الإلزامية
    if ($title && $desc && $start_at && $location && $capacity >= 0) { // التحقق من السعة كقيمة صالحة
      // Basic duplicate check (same organizer, title, and date)
      try {
        $dupStmt = $pdo->prepare("SELECT id FROM events WHERE organizer_id = ? AND title = ? AND date = ? LIMIT 1");
        $dupStmt->execute([$user['id'], $title, date('Y-m-d', strtotime(str_replace('T',' ',$start_at)))]);
        if ($dupStmt->fetch()) {
          $error = lang('⚠️ An event with the same title and date already exists.');
        }
      } catch (PDOException $e) {
        error_log('Duplicate check failed: ' . $e->getMessage());
      }
        
        $image_filename = null;
        
    // 🖼️ معالجة تحميل الصورة (تحسين: التحقق من الامتداد + إنشاء المجلد إن لم يوجد)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $target_dir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'events' . DIRECTORY_SEPARATOR;

      // إنشاء المجلد إذا لم يكن موجوداً
      if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0775, true)) {
          $error = lang('❌ Failed to create upload directory.');
        }
      }

      if (empty($error)) {
        $originalName = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed, true)) {
          $error = lang('⚠️ Unsupported image type. Allowed: JPG, PNG, GIF, WEBP.');
        } else {
          $image_filename = uniqid('event_') . '.' . $ext;
          $target_file = $target_dir . $image_filename;
          if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $error = lang('❌ Failed to upload image.');
          }
        }
      }
    }
        
        if (empty($error)) {
            try {
                // ✅ 2. تحديث استعلام SQL لإضافة capacity
                // ملاحظة: حالة الموافقة (approval_status) يتم تعيينها على 'pending' افتراضياً
                $sql = "INSERT INTO events (organizer_id, title, description, start_at, end_at, location, category, capacity, image, approval_status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
                
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([
                    $user['id'],
                    $title,
                    $desc,
                    $start_at,
                    $end_at, 
                    $location,
                    $category,
                    $capacity, // ✅ تمرير السعة
                    $image_filename,
                ])) {
                  // Regenerate token to prevent reuse and redirect (PRG pattern)
                  $_SESSION['event_create_token'] = bin2hex(random_bytes(16));
                  header('Location: ' . BASE_URL . '/organizer/events_create.php?created=1');
                  exit;
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
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>" dir="<?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'rtl' : 'ltr'; ?>">

<head>
  <?php $extra_css = isset($extra_css) && is_array($extra_css) ? $extra_css : []; $extra_css[] = 'events_create'; ?>
  <?php include '../header.php'; ?>
  <title><?php echo lang('create_new_event'); ?></title>
  <!-- Bootstrap Icons loaded globally via header.php -->
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

            <form id="eventCreateForm" method="POST" enctype="multipart/form-data" action="">
              <input type="hidden" name="event_create_token" value="<?php echo e($_SESSION['event_create_token']); ?>">

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
                  <input type="datetime-local" name="start_at" id="start_at" class="form-control" required value="<?php echo e($_POST['start_at'] ?? ''); ?>">
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

              <button type="submit" class="btn btn-primary w-100" id="submitEventBtn">
                <i class="bi bi-plus-circle"></i>
                <?php echo lang('save_event'); ?>
              </button>
            </form>

            <div class="text-center mt-4">
              <a href="<?php echo BASE_URL; ?>/organizer/organizer_dashboard.php" class="text-decoration-none">
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

  <script src="<?php echo BASE_URL; ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
  <script>
    // Client-side double-click protection
    document.addEventListener('DOMContentLoaded', function(){
      const form = document.getElementById('eventCreateForm');
      const btn = document.getElementById('submitEventBtn');
      if(form && btn){
        form.addEventListener('submit', function(){
          btn.disabled = true;
          btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span><?php echo lang('save_event'); ?>';
        });
      }
    });
  </script>
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
