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
    $date  = $_POST['date'];
    $end_raw = $_POST['end_at'] ?? '';
    $end_at = $end_raw ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $end_raw))) : null;
    $location = trim($_POST['location']);

    if ($title && $desc && $date && $location ) {
        $stmt = $pdo->prepare("UPDATE events SET title=?, description=?, date=?,end_at = ?, location=? WHERE id=? AND organizer_id=?");
        if ($stmt->execute([$title, $desc, $date, $end_at, $location, $event_id, $user['id']])) {
            $_SESSION['success_message'] = $_SESSION['lang'] == 'ar' ? '✅  ! تم التحديث بنجاح ' :'✅ Event updated successfully!';
            header("Location: dashboard.php");
            exit;
        } else {
            $error = $_SESSION['lang'] == 'ar' ? ' ❌ فشل في التحديث , حوال مرة أخرى ' : "❌ Failed to update event. Please try again.";
        }
    } else {
        $error =  $_SESSION['lang'] == 'ar' ? '⚠️ جميع الحقول مطلوبة  ' : "⚠️ All fields are required.";
    }
}
?>

<?php include '../header.php'; ?>

<section class="py-5" style="background: linear-gradient(135deg,#004aad 0%,#007bff 100%); min-height:90vh; display:flex; align-items:center;">
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

            <form method="POST">
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
                <input type="datetime-local" name="date" class="form-control" required value="<?php echo e($event['date']); ?>">
              </div>
              <div class="col-md-6 mb-3">
                    <label class="form-label">
                      <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'نهاية الفعالية (التاريخ والوقت)' : 'End Date & Time'; ?>
                    </label>
                    <input type="datetime-local" name="end_at" class="form-control">
                  </div>

              <div class="mb-3">
                <label class="form-label"><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'الموقع' : 'Location'; ?></label>
                <input type="text" name="location" class="form-control" required value="<?php echo e($event['location']); ?>">
              </div>

              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-save"></i>
                <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'حفظ التغييرات' : 'Save Changes'; ?>
              </button>
            </form>

            <div class="text-center mt-4">
              <a href="dashboard.php" class="text-decoration-none">
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
