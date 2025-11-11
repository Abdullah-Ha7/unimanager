<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// ✅ Handle search input
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

// ✅ Fetch categories
$categories = $pdo->query("
    SELECT DISTINCT category 
    FROM events 
    WHERE category IS NOT NULL AND category <> '' 
    ORDER BY category ASC
")->fetchAll(PDO::FETCH_COLUMN);

// ✅ Build query dynamically
$query = "SELECT * FROM events WHERE 1";
$params = [];

if ($search) {
    $query .= " AND (title LIKE ? OR description LIKE ? OR location LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

if ($category) {
    $query .= " AND category = ?";
    $params[] = $category;
}

$query .= " ORDER BY date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!-- ✅ ربط ملفات CSS -->
<link rel="stylesheet" href="assets/css/style.css">



<section class="events-section">
  <div class="container">

    <!-- 🔍 Search & Filter -->
    <div class="card search-card shadow-sm border-0 p-4 mb-5">
      <form method="GET" action="index.php" class="row g-2">
        <input type="hidden" name="page" value="events">
        <div class="col-md-6">
          <input type="text" name="search" class="form-control form-control-lg"
                 placeholder="<?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'ابحث عن فعالية...' : 'Search for events...'; ?>"
                 value="<?php echo e($search); ?>">
        </div>
        <div class="col-md-4">
          <select name="category" class="form-select form-select-lg">
            <option value=""><?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'كل الفئات' : 'All Categories'; ?></option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo e($cat); ?>" <?php echo ($cat == $category) ? 'selected' : ''; ?>>
                <?php echo e($cat); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-grid">
          <button class="btn btn-primary btn-lg search-btn">
            <i class="bi bi-search"></i>
            <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'بحث' : 'Search'; ?>
          </button>
        </div>
      </form>
    </div>

    <!-- 🎟️ Events List -->
    <div class="row">
      <?php if ($events): ?>
        <?php foreach ($events as $ev): ?>
          <div class="col-md-4 mb-4">
            <div class="card event-card border-0 shadow-sm h-100">
              <div class="card-body d-flex flex-column">
                <h5 class="fw-bold text-primary mb-2"><?php echo e($ev['title']); ?></h5>
                <p class="text-muted mb-1"><i class="bi bi-calendar-event"></i> <?php echo e($ev['date']); ?></p>
                <p class="text-muted mb-2"><i class="bi bi-geo-alt"></i> <?php echo e($ev['location']); ?></p>
                <p class="flex-grow-1"><?php echo nl2br(substr(e($ev['description']), 0, 100)) . '...'; ?></p>

                <div class="mt-auto text-end">
                  <a href="?page=event_detail&id=<?php echo $ev['id']; ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-eye"></i>
                    <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'عرض التفاصيل' : 'View Details'; ?>
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="text-center text-muted py-5">
          <i class="bi bi-calendar-x display-3 text-primary"></i>
          <p class="mt-3 fs-5">
            <?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'لم يتم العثور على فعاليات.' : 'No matching events found.'; ?>
          </p>
        </div>
      <?php endif; ?>
    </div>

  </div>
</section>
