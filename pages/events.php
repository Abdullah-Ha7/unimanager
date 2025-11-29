<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

global $pdo;
$settings = get_system_settings();
$limit = intval($settings['events_per_page'] ?? 9); // الحد الأقصى للعناصر في الصفحة
$current_page = max(1, intval($_GET['page_num'] ?? 1));
$offset = ($current_page - 1) * $limit;

// ✅ Handle search input
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$date = trim($_GET['date'] ?? '');

// ✅ Handle search input and cleanup expired events
$deleted_count = cleanup_expired_events();

$categories = $pdo->query("
  SELECT DISTINCT category 
  FROM events 
  WHERE approval_status = 'approved' AND category IS NOT NULL AND category <> '' 
  ORDER BY category ASC
")->fetchAll(PDO::FETCH_COLUMN);

$query = "SELECT * FROM events WHERE approval_status = 'approved'";
$params = [];

if ($search) {
    $query .= " AND (title LIKE ? OR description LIKE ? OR location LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

if ($category) {
    $query .= " AND category = ?";
    $params[] = $category;
}

// ✅ Filter by exact date if provided (expects YYYY-MM-DD)
if ($date) {
  $query .= " AND date = ?";
  $params[] = $date;
}

$query .= " ORDER BY date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);


// بناء شروط الاستعلام الديناميكية
$conditions = ["e.approval_status = 'approved'"];
$bindParams = [];

if ($search !== '') {
    $conditions[] = "(e.title LIKE :search OR e.description LIKE :search OR e.location LIKE :search)";
    $bindParams[':search'] = "%$search%";
}
if ($category !== '') {
    $conditions[] = "e.category = :category";
    $bindParams[':category'] = $category;
}
$whereClause = implode(' AND ', $conditions);


// إجمالي العناصر (للتقسيم إلى صفحات)
$sqlCount = "SELECT COUNT(e.id) FROM events e JOIN users u ON e.organizer_id = u.id WHERE $whereClause";
$stmt_total = $pdo->prepare($sqlCount);
foreach ($bindParams as $k => $v) {
    $stmt_total->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt_total->execute();
$total_events = (int)$stmt_total->fetchColumn();
$total_pages = ($limit > 0) ? (int)ceil($total_events / $limit) : 1;

// جلب فعاليات الصفحة الحالية
$sqlEvents = "SELECT e.*, u.name AS organizer_name\n             FROM events e\n             JOIN users u ON e.organizer_id = u.id\n             WHERE $whereClause\n             ORDER BY e.date DESC\n             LIMIT :limit OFFSET :offset";
$stmt_events = $pdo->prepare($sqlEvents);
foreach ($bindParams as $k => $v) {
    $stmt_events->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt_events->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt_events->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_events->execute();
$events = $stmt_events->fetchAll(PDO::FETCH_ASSOC);

?>


<!-- ✅ ربط ملفات CSS -->
<!-- CSS already loaded globally via header.php -->



<section class="events-section">
  <div class="container">

    <!-- 🔍 Search & Filter -->
    <div class="card search-card shadow-sm border-0 p-4 mb-5">
      <form method="GET" action="index.php" class="row g-2">
        <input type="hidden" name="page" value="events">
        <div class="col-md-5">
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
        <div class="col-md-3">
          <input type="date" name="date" class="form-control form-control-lg" value="<?php echo e($date); ?>">
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
          <div class="col-12 mb-4">
            <div class="card event-card border-0 shadow-sm h-100">
              <div class="card-body d-flex flex-column">
                <?php if (!empty($ev['image'])): ?>
                  <img src="<?php echo BASE_URL; ?>/uploads/events/<?php echo e($ev['image']); ?>" alt="<?php echo e($ev['title']); ?>" class="event-img mb-3">
                <?php endif; ?>
                <h5 class="fw-bold text-primary mb-2"><?php echo e($ev['title']); ?></h5>
                <p class="text-muted mb-1"><i class="bi bi-calendar-event"></i> <?php echo format_date($ev['start_at']); ?></p>
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
        <?php $isArabic = (($_SESSION['lang'] ?? 'en') === 'ar'); ?>
        <!-- Accessible fallback (hidden) if JS disabled -->
        <p class="visually-hidden">
          <?php echo $isArabic ? 'لم يتم العثور على فعاليات.' : 'No matching events found.'; ?>
        </p>

        <!-- No Results Modal -->
        <div class="modal fade" id="noResultsModal" tabindex="-1" aria-hidden="true" aria-labelledby="noResultsLabel">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="noResultsLabel">
                  <?php echo $isArabic ? 'لا توجد نتائج' : 'No Results'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-calendar-x text-primary fs-3"></i>
                  <p class="mb-0">
                    <?php echo $isArabic ? 'لم يتم العثور على فعاليات مطابقة لبحثك.' : 'No matching events found for your search.'; ?>
                  </p>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">
                  <?php echo $isArabic ? 'حسنًا' : 'OK'; ?>
                </button>
              </div>
            </div>
          </div>
        </div>

        <script>
          document.addEventListener('DOMContentLoaded', function () {
            try {
              var el = document.getElementById('noResultsModal');
              if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var modal = new bootstrap.Modal(el);
                modal.show();
              }
            } catch (e) { /* noop */ }
          });
        </script>
      <?php endif; ?>
    </div>

  </div>
  

<?php if ($total_pages > 1): ?>
      <nav aria-label="Events Pagination" class="mt-4">
        <ul class="pagination justify-content-center">
          <?php 
            $buildLink = function($p) use ($search, $category, $date) {
              $q = [
                'page' => 'events',
                'page_num' => $p,
              ];
              if ($search !== '') $q['search'] = $search;
              if ($category !== '') $q['category'] = $category;
              if ($date !== '') $q['date'] = $date;
              return '?' . http_build_query($q);
            };
            $start = max(1, $current_page - 2);
            $end = min($total_pages, $current_page + 2);
          ?>

          <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $buildLink($current_page - 1); ?>" aria-label="Previous">
              <span aria-hidden="true">&laquo;</span>
            </a>
          </li>

          <?php if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="<?php echo $buildLink(1); ?>">1</a></li>
            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
          <?php endif; ?>

          <?php for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
              <a class="page-link" href="<?php echo $buildLink($i); ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>

          <?php if ($end < $total_pages): ?>
            <?php if ($end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="<?php echo $buildLink($total_pages); ?>"><?php echo $total_pages; ?></a></li>
          <?php endif; ?>

          <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $buildLink($current_page + 1); ?>" aria-label="Next">
              <span aria-hidden="true">&raquo;</span>
            </a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
</section>
