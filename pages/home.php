<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$settings = get_system_settings();
$registration_is_open = ($site_settings['registration_open'] ?? '1') == '1';

$lang = $_SESSION['lang'] ?? 'en';
?>

<!-- CSS already loaded globally via header.php -->

<?php
// Fetch events for Featured/Trending and Upcoming sections
try {
  // Trending: latest events (as a simple heuristic)
  $stmtTrending = $pdo->query("SELECT id, title, date, location, description, image FROM events WHERE approval_status = 'approved' ORDER BY id DESC LIMIT 6");
  $trending = $stmtTrending->fetchAll(PDO::FETCH_ASSOC);

  // Upcoming: future events by soonest date
  $stmtUpcoming = $pdo->query("SELECT id, title, date, location, description, image FROM events WHERE approval_status = 'approved' AND date >= CURDATE() ORDER BY date ASC LIMIT 6");
  $upcoming = $stmtUpcoming->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $trending = [];
  $upcoming = [];
}
?>

<!-- Hero collage with search -->
<section class="home-hero">
  <div class="collage-wrapper">
    <div class="hero-strip" aria-label="Featured imagery">
      <img src="<?php echo BASE_URL; ?>/assets/img/8.jpg" alt="" loading="lazy">
      <img src="<?php echo BASE_URL; ?>/assets/img/10.jpg" alt="" loading="lazy">
      <img src="<?php echo BASE_URL; ?>/assets/img/88.jpg" alt="" loading="lazy">
      <img src="<?php echo BASE_URL; ?>/assets/img/11.jpg" alt="" loading="lazy">
      <img src="<?php echo BASE_URL; ?>/assets/img/666.jpg" alt="" loading="lazy">
    </div>
  </div>
</section>

<!-- Removed diagnostic size overlay script for production uniform grid -->

<!-- Quick links row -->
<section class="quick-links py-2 py-md-3">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-center justify-content-md-between gap-3">
      <a class="ql-item" href="?page=home">
        <i class="bi bi-star-fill text-warning"></i>
        <span><?php echo ($lang=='ar') ? 'فعاليات مميزة' : 'Featured Events'; ?></span>
      </a>
      <a class="ql-item" href="?page=events&today=1">
        <i class="bi bi-calendar2-day"></i>
        <span><?php echo ($lang=='ar') ? 'فعاليات اليوم' : "Today's Events"; ?></span>
      </a>
      <div class="dropdown">
        <button class="ql-item dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-calendar3"></i>
          <span><?php echo ($lang=='ar') ? 'حسب التاريخ' : 'Events By Date'; ?></span>
        </button>
        <div class="dropdown-menu dropdown-menu-light p-3" style="min-width: 260px;">
          <form class="d-flex align-items-center" method="GET" action="<?php echo BASE_URL; ?>/index.php">
            <input type="hidden" name="page" value="events">
            <input type="date" name="date" class="form-control" onchange="this.form.submit()" />
          </form>
        </div>
      </div>
      
      <a class="ql-item" href="?page=events&by=place">
        <i class="bi bi-geo-alt"></i>
        <span><?php echo ($lang=='ar') ? 'الأماكن' : 'Places'; ?></span>
      </a>
    </div>
  </div>
</section>

<!-- Featured Events section -->
<section class="featured-section py-4 py-md-5">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3 mb-md-4">
      <h2 class="section-title mb-0"><?php echo ($lang=='ar') ? 'فعاليات مميزة' : 'Featured Events'; ?></h2>
      <div class="d-flex align-items-center gap-3">
        <div class="tabs small fw-semibold">
          <button class="tab-link active" data-target="#tab-trending"><?php echo ($lang=='ar') ? 'رائج' : 'TRENDING'; ?></button>
          <button class="tab-link" data-target="#tab-upcoming"><?php echo ($lang=='ar') ? 'قادم' : 'UPCOMING'; ?></button>
        </div>
        <div class="dropdown">
          <button class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown">
            <?php echo ($lang=='ar') ? 'تصفية' : 'Filter'; ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="?page=events&category=Workshop">Workshop</a></li>
            <li><a class="dropdown-item" href="?page=events&category=Seminar">Seminar</a></li>
            <li><a class="dropdown-item" href="?page=events&category=Sports">Sports</a></li>
          </ul>
        </div>
      </div>
    </div>

    <div id="tab-trending" class="tab-pane show">
      <div class="row g-4">
        <?php if ($trending): foreach ($trending as $ev): ?>
          <div class="col-12">
            <div class="card event-card border-0 shadow-sm h-100">
                <div class="card-body">
                  <div class="d-flex align-items-start gap-3 event-card-row">
                    <?php if (!empty($ev['image'])): ?>
                      <img src="<?php echo BASE_URL; ?>/uploads/events/<?php echo e($ev['image']); ?>" class="event-img-left" alt="<?php echo e($ev['title']); ?>">
                    <?php endif; ?>
                    <div class="flex-grow-1">
                      <span class="badge bg-success mb-2"><?php echo ($lang=='ar') ? 'مميز' : 'FEATURED'; ?></span>
                      <h5 class="fw-bold text-primary mb-2"><?php echo e($ev['title']); ?></h5>
                      <p class="text-muted mb-1"><i class="bi bi-calendar-event"></i> <?php echo e($ev['date']); ?></p>
                      <p class="text-muted mb-2"><i class="bi bi-geo-alt"></i> <?php echo e($ev['location']); ?></p>
                      <p class="mb-3"><?php echo nl2br(substr(e($ev['description'] ?? ''), 0, 100)) . '...'; ?></p>
                      <div class="mt-2 text-end">
                        <a href="?page=event_detail&id=<?php echo $ev['id']; ?>" class="btn btn-outline-primary btn-sm">
                          <i class="bi bi-eye"></i>
                          <?php echo ($lang=='ar') ? 'عرض التفاصيل' : 'View Details'; ?>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
          </div>
        <?php endforeach; else: ?>
          <div class="col-12 text-center text-muted py-4">
            <i class="bi bi-calendar-x display-5 text-primary"></i>
            <p class="mt-3"><?php echo ($lang=='ar') ? 'لا توجد فعاليات' : 'No events to show.'; ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div id="tab-upcoming" class="tab-pane" style="display:none;">
      <div class="row g-4">
        <?php if ($upcoming): foreach ($upcoming as $ev): ?>
          <div class="col-12">
            <div class="card event-card border-0 shadow-sm h-100">
                <div class="card-body">
                  <div class="d-flex align-items-start gap-3 event-card-row">
                    <?php if (!empty($ev['image'])): ?>
                      <img src="<?php echo BASE_URL; ?>/uploads/events/<?php echo e($ev['image']); ?>" class="event-img-left" alt="<?php echo e($ev['title']); ?>">
                    <?php endif; ?>
                    <div class="flex-grow-1">
                      <span class="badge bg-info mb-2"><?php echo ($lang=='ar') ? 'قادم' : 'UPCOMING'; ?></span>
                      <h5 class="fw-bold text-primary mb-2"><?php echo e($ev['title']); ?></h5>
                      <p class="text-muted mb-1"><i class="bi bi-calendar-event"></i> <?php echo e($ev['date']); ?></p>
                      <p class="text-muted mb-2"><i class="bi bi-geo-alt"></i> <?php echo e($ev['location']); ?></p>
                      <p class="mb-3"><?php echo nl2br(substr(e($ev['description'] ?? ''), 0, 100)) . '...'; ?></p>
                      <div class="mt-2 text-end">
                        <a href="?page=event_detail&id=<?php echo $ev['id']; ?>" class="btn btn-outline-primary btn-sm">
                          <i class="bi bi-eye"></i>
                          <?php echo ($lang=='ar') ? 'عرض التفاصيل' : 'View Details'; ?>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
          </div>
        <?php endforeach; else: ?>
          <div class="col-12 text-center text-muted py-4">
            <i class="bi bi-calendar-x display-5 text-primary"></i>
            <p class="mt-3"><?php echo ($lang=='ar') ? 'لا توجد فعاليات قادمة' : 'No upcoming events.'; ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<script>
  // Simple tabs
  document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('.tabs .tab-link');
    links.forEach(btn => btn.addEventListener('click', () => {
      links.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
      const target = document.querySelector(btn.getAttribute('data-target'));
      if (target) target.style.display = 'block';
    }));
  });
</script>
