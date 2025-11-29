<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$user = current_user();
if (!$user || $user['role_id'] != 2) {
    header("Location: " . BASE_URL . "/?page=login");
    exit;
}

// ✅ Get Event ID
$event_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
$stmt->execute([$event_id, $user['id']]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "<div class='alert alert-danger text-center mt-5'>Event not found or you don’t have permission to edit it.</div>";
    exit;
}

// ✅ Handle Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $desc  = trim($_POST['description']);
    $start_at  = $_POST['start_at'];
    $end_raw = $_POST['end_at'] ?? '';
    $end_at = $end_raw ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $end_raw))) : null;
    $location = trim($_POST['location']);
  $new_image_filename = null;

  // 🖼️ Optional: process new uploaded image
  if (isset($_FILES['image']) && is_array($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $target_dir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'events' . DIRECTORY_SEPARATOR;
      if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0775, true)) {
          $error = ($_SESSION['lang'] ?? 'en') == 'ar' ? '❌ فشل إنشاء مجلد الرفع.' : '❌ Failed to create upload directory.';
        }
      }
      if (empty($error)) {
        $originalName = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed, true)) {
          $error = ($_SESSION['lang'] ?? 'en') == 'ar' ? '⚠️ نوع الصورة غير مدعوم. مسموح: JPG, PNG, GIF, WEBP.' : '⚠️ Unsupported image type. Allowed: JPG, PNG, GIF, WEBP.';
        } else {
          $new_image_filename = uniqid('event_') . '.' . $ext;
          $target_file = $target_dir . $new_image_filename;
          if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $error = ($_SESSION['lang'] ?? 'en') == 'ar' ? '❌ فشل رفع الصورة.' : '❌ Failed to upload image.';
            $new_image_filename = null;
          }
        }
      }
    } else {
      $error = ($_SESSION['lang'] ?? 'en') == 'ar' ? '❌ حدث خطأ أثناء رفع الصورة.' : '❌ Image upload error.';
    }
  }

    if ($title && $desc && $start_at && $location ) {
      // Build dynamic update query (include image if a new one uploaded)
      $sql = "UPDATE events SET title=?, description=?, start_at=?, end_at = ?, location=?";
      $params = [$title, $desc, $start_at, $end_at, $location];
      if ($new_image_filename !== null) {
        $sql .= ", image=?";
        $params[] = $new_image_filename;
      }
      $sql .= " WHERE id=? AND organizer_id=?";
      $params[] = $event_id;
      $params[] = $user['id'];

      $stmt = $pdo->prepare($sql);
      if ($stmt->execute($params)) {
        // If new image saved, optionally delete old file
        if ($new_image_filename !== null && !empty($event['image'])) {
          $old = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'events' . DIRECTORY_SEPARATOR . $event['image'];
          if (is_file($old)) { @unlink($old); }
        }
            $_SESSION['success_message'] = $_SESSION['lang'] == 'ar' ? '✅  ! تم التحديث بنجاح ' :'✅ Event updated successfully!';
            header("Location: organizer_dashboard.php");
            exit;
        } else {
            $error = $_SESSION['lang'] == 'ar' ? ' ❌ فشل في التحديث , حوال مرة أخرى ' : "❌ Failed to update event. Please try again.";
        }
    } else {
        $error =  $_SESSION['lang'] == 'ar' ? '⚠️ جميع الحقول مطلوبة  ' : "⚠️ All fields are required.";
    }
}
?>

<?php $extra_css = isset($extra_css) && is_array($extra_css) ? $extra_css : []; $extra_css[] = 'edit_event'; include '../header.php'; ?>

<section class="py-5 edit-event-page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-lg border-0 p-4">
          <div class="card-body text-dark">
            <h3 class="text-center fw-bold mb-4 text-primary">
              <i class="bi bi-pencil-square"></i>
              <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'تعديل الفعالية' : 'Edit Event'; ?>
            </h3>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
              <div class="mb-3">
                <label class="form-label"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'عنوان الفعالية' : 'Event Title'; ?></label>
                <input type="text" name="title" class="form-control" required value="<?php echo e($event['title']); ?>">
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'الوصف' : 'Description'; ?></label>
                <textarea name="description" class="form-control" rows="4" required><?php echo e($event['description']); ?></textarea>
              </div>
                  
                 
                  
              <div class="mb-3">
                <label class="form-label"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'التاريخ' : 'Event Date'; ?></label>
                <input type="datetime-local" name="start_at" class="form-control" required value="<?php echo e($event['start_at'] ? date('Y-m-d\TH:i', strtotime($event['start_at'])) : ''); ?>">
              </div>
              <div class="col-md-6 mb-3">
                    <label class="form-label">
                      <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'نهاية الفعالية (التاريخ والوقت)' : 'End Date & Time'; ?>
                    </label>
                    <input type="datetime-local" name="end_at" class="form-control" value="<?php echo e($event['end_at'] ? date('Y-m-d\TH:i', strtotime($event['end_at'])) : ''); ?>">
                  </div>

              <div class="mb-3">
                <label class="form-label"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'الموقع' : 'Location'; ?></label>
                <input type="text" name="location" class="form-control" required value="<?php echo e($event['location']); ?>">
              </div>

              <!-- Current image preview and optional replacement -->
              <div class="mb-3">
                <label class="form-label"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'الصورة الحالية' : 'Current Image'; ?></label>
                <?php if (!empty($event['image'])): ?>
                  <div class="mb-2 text-center">
                    <img src="<?php echo BASE_URL; ?>/uploads/events/<?php echo e($event['image']); ?>" alt="event image" class="img-fluid rounded img-preview">
                  </div>
                <?php else: ?>
                  <p class="text-muted small"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'لا توجد صورة حالياً.' : 'No image currently.'; ?></p>
                <?php endif; ?>
                <label for="image" class="form-label"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'تغيير الصورة (اختياري)' : 'Change Image (optional)'; ?></label>
                <input type="file" name="image" id="image" class="form-control" accept="image/*">
                <div class="form-text"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'الأنواع المسموحة: JPG, PNG, GIF, WEBP' : 'Allowed types: JPG, PNG, GIF, WEBP'; ?></div>
              </div>

              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-save"></i>
                <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'حفظ التغييرات' : 'Save Changes'; ?>
              </button>
            </form>

            <div class="text-center mt-4">
              <a href="organizer_dashboard.php" class="text-decoration-none">
                <i class="bi bi-arrow-left-circle"></i>
                <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'عودة إلى لوحة التحكم' : 'Back to Dashboard'; ?>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include '../footer.php'; ?>
