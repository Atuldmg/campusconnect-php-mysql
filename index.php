<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Home';

$stats = [
    'students'  => 0,
    'resources' => 0,
    'events'    => 0,
    'assignments' => 0,
];

$recentAnnouncements = [];
$upcomingEvents = [];

try {
    $pdo = get_db_connection();

    $stats['students']  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $stats['resources'] = (int)$pdo->query("SELECT COUNT(*) FROM resources")->fetchColumn();
    $stats['events']    = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= NOW()")->fetchColumn();
    $stats['assignments'] = (int)$pdo->query("SELECT COUNT(*) FROM assignments")->fetchColumn();

    $stmtAnn = $pdo->query("SELECT id, title, content, category, is_pinned, created_at FROM announcements ORDER BY is_pinned DESC, created_at DESC LIMIT 3");
    $recentAnnouncements = $stmtAnn->fetchAll();

    $stmtEvt = $pdo->query("SELECT id, title, description, event_date, venue, category FROM events WHERE event_date >= NOW() ORDER BY event_date ASC LIMIT 3");
    $upcomingEvents = $stmtEvt->fetchAll();

} catch (Exception $e) {
    error_log('Database query error on index.php: ' . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="py-5 bg-body-tertiary border-bottom">
  <div class="container-fluid px-3 px-lg-4">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 mb-3">
          <i class="bi bi-shield-check me-1"></i>Official Campus Academic Portal
        </span>
        <h1 class="display-6 fw-bold text-body mb-3">
          Smart Campus Management & Student Academic Portal
        </h1>
        <p class="lead text-muted mb-4 fs-6">
          CampusConnect brings lecture slides, course materials, assignment deadlines, campus symposiums, and administrative communications into a unified, accessible workspace.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <?php if (is_logged_in()): ?>
            <?php if (has_role('admin')): ?>
              <a href="<?= url('admin/dashboard.php') ?>" class="btn btn-primary px-4 py-2">
                <i class="bi bi-speedometer2 me-1"></i>Open Admin Console
              </a>
            <?php else: ?>
              <a href="<?= url('student/dashboard.php') ?>" class="btn btn-primary px-4 py-2">
                <i class="bi bi-grid-fill me-1"></i>Go to Student Dashboard
              </a>
            <?php endif; ?>
          <?php else: ?>
            <a href="<?= url('login.php') ?>" class="btn btn-primary px-4 py-2">
              <i class="bi bi-box-arrow-in-right me-1"></i>Student & Staff Sign In
            </a>
            <a href="<?= url('register.php') ?>" class="btn btn-outline-secondary px-4 py-2">
              <i class="bi bi-person-plus me-1"></i>Student Registration
            </a>
          <?php endif; ?>
          <a href="<?= url('courses.php') ?>" class="btn btn-outline-primary px-3 py-2">
            <i class="bi bi-filetype-xml me-1"></i>Browse Course XML
          </a>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="row g-3">
          <div class="col-6">
            <div class="card card-custom p-3 text-center">
              <div class="stat-value text-primary"><?= number_format($stats['students']) ?></div>
              <div class="stat-label">Active Students</div>
            </div>
          </div>
          <div class="col-6">
            <div class="card card-custom p-3 text-center">
              <div class="stat-value text-primary"><?= number_format($stats['resources']) ?></div>
              <div class="stat-label">Study Files</div>
            </div>
          </div>
          <div class="col-6">
            <div class="card card-custom p-3 text-center">
              <div class="stat-value text-primary"><?= number_format($stats['events']) ?></div>
              <div class="stat-label">Upcoming Events</div>
            </div>
          </div>
          <div class="col-6">
            <div class="card card-custom p-3 text-center">
              <div class="stat-value text-primary"><?= number_format($stats['assignments']) ?></div>
              <div class="stat-label">Course Tasks</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="container-fluid px-3 px-lg-4 py-5">
  <div class="row g-4">

    <div class="col-lg-7">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">
          <i class="bi bi-megaphone-fill text-primary me-2"></i>Official Announcements
        </h5>
        <span class="small text-muted">Latest circulars</span>
      </div>

      <?php if (empty($recentAnnouncements)): ?>
        <div class="card card-custom p-4 text-center text-muted">
          <p class="mb-0">No active circulars published at this time.</p>
        </div>
      <?php else: ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($recentAnnouncements as $ann): ?>
            <div class="card card-custom shadow-none border">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge <?= category_badge_class($ann['category']) ?>"><?= e($ann['category']) ?></span>
                    <?php if ($ann['is_pinned']): ?>
                      <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                        <i class="bi bi-pin-angle-fill me-1"></i>Pinned
                      </span>
                    <?php endif; ?>
                  </div>
                  <small class="text-muted"><?= time_ago($ann['created_at']) ?></small>
                </div>
                <h6 class="card-title fw-bold text-body mb-2"><?= e($ann['title']) ?></h6>
                <p class="card-text text-muted small mb-0"><?= nl2br(e($ann['content'])) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-5">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">
          <i class="bi bi-calendar-event-fill text-primary me-2"></i>Campus Calendar
        </h5>
        <a href="<?= url('student/events.php') ?>" class="small text-primary text-decoration-none">View All</a>
      </div>

      <?php if (empty($upcomingEvents)): ?>
        <div class="card card-custom p-4 text-center text-muted">
          <p class="mb-0">No upcoming events scheduled right now.</p>
        </div>
      <?php else: ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($upcomingEvents as $evt): ?>
            <div class="card card-custom shadow-none border">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="badge <?= category_badge_class($evt['category']) ?>"><?= e($evt['category']) ?></span>
                  <small class="text-primary fw-medium"><i class="bi bi-clock me-1"></i><?= format_date($evt['event_date'], 'M d, Y h:i A') ?></small>
                </div>
                <h6 class="card-title fw-bold text-body mb-1"><?= e($evt['title']) ?></h6>
                <p class="card-text text-muted small mb-2 text-truncate"><?= e($evt['description']) ?></p>
                <div class="d-flex align-items-center justify-content-between small text-muted border-top pt-2">
                  <span><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= e($evt['venue']) ?></span>
                  <?php if (is_logged_in() && has_role('student')): ?>
                    <a href="<?= url('student/events.php') ?>" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.8rem;">Register</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="card card-custom mt-4 p-3 bg-body-tertiary">
        <h6 class="fw-bold mb-2"><i class="bi bi-lightbulb text-warning me-2"></i>Key Portal Capabilities</h6>
        <ul class="list-unstyled mb-0 small text-muted d-flex flex-column gap-2">
          <li><i class="bi bi-check2-circle text-success me-2"></i><strong>Instant Search:</strong> Asynchronous AJAX material retrieval without page reloads.</li>
          <li><i class="bi bi-check2-circle text-success me-2"></i><strong>Theme Engine:</strong> Clean Light, Dark, and OS System mode support.</li>
          <li><i class="bi bi-check2-circle text-success me-2"></i><strong>Course XML:</strong> Dynamic XML & DTD curriculum parser.</li>
          <li><i class="bi bi-check2-circle text-success me-2"></i><strong>Role Security:</strong> Bcrypt password hashing & session management.</li>
        </ul>
      </div>

    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
