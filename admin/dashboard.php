<?php
/**
 * CampusConnect - Administrator Master Dashboard
 * Administrative metrics, recent student registrations, grievance overview, and system analytics.
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_role('admin');

$pageTitle = 'Admin Console';

$stats = [
    'students'     => 0,
    'resources'    => 0,
    'assignments'  => 0,
    'events'       => 0,
    'new_feedback' => 0,
];

$recentUsers = [];
$recentFeedback = [];
$recentRegistrations = [];

try {
    $pdo = get_db_connection();

    // 1. Core database statistics
    $stats['students']     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $stats['resources']    = (int)$pdo->query("SELECT COUNT(*) FROM resources")->fetchColumn();
    $stats['assignments']  = (int)$pdo->query("SELECT COUNT(*) FROM assignments")->fetchColumn();
    $stats['events']       = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $stats['new_feedback'] = (int)$pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'new'")->fetchColumn();

    // 2. Recent student registrations
    $stmtUsers = $pdo->query("SELECT id, name, email, department, roll_number, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC LIMIT 5");
    $recentUsers = $stmtUsers->fetchAll();

    // 3. Recent student feedback
    $stmtFb = $pdo->query("
        SELECT f.id, f.subject, f.category, f.status, f.created_at, u.name as student_name
        FROM feedback f
        JOIN users u ON f.student_id = u.id
        ORDER BY f.created_at DESC
        LIMIT 4
    ");
    $recentFeedback = $stmtFb->fetchAll();

    // 4. Recent event sign-ups
    $stmtReg = $pdo->query("
        SELECT r.registered_at, u.name as student_name, e.title as event_title
        FROM event_registrations r
        JOIN users u ON r.student_id = u.id
        JOIN events e ON r.event_id = e.id
        ORDER BY r.registered_at DESC
        LIMIT 4
    ");
    $recentRegistrations = $stmtReg->fetchAll();

} catch (Exception $e) {
    error_log('Admin dashboard error: ' . $e->getMessage());
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="portal-container">
  <?php require_once dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <main class="portal-content">
    
    <!-- Top Greeting -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
      <div>
        <h4 class="fw-bold mb-1">Administrator Control Panel</h4>
        <p class="text-muted small mb-0">System health, active academic data, and institutional operations.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= url('admin/announcements.php') ?>" class="btn btn-sm btn-primary">
          <i class="bi bi-plus-lg me-1"></i>New Announcement
        </a>
        <a href="<?= url('admin/resources.php') ?>" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-upload me-1"></i>Upload Resource
        </a>
      </div>
    </div>

    <!-- 5 Stat Cards Grid -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-4 col-xl">
        <div class="stat-card">
          <div>
            <div class="stat-label">Enrolled Students</div>
            <div class="stat-value text-primary"><?= number_format($stats['students']) ?></div>
            <small class="text-muted">Total registered</small>
          </div>
          <div class="stat-icon-wrapper bg-primary-subtle text-primary">
            <i class="bi bi-people"></i>
          </div>
        </div>
      </div>

      <div class="col-6 col-md-4 col-xl">
        <div class="stat-card">
          <div>
            <div class="stat-label">Study Files</div>
            <div class="stat-value"><?= number_format($stats['resources']) ?></div>
            <small class="text-muted">In digital repository</small>
          </div>
          <div class="stat-icon-wrapper bg-secondary-subtle text-secondary">
            <i class="bi bi-folder"></i>
          </div>
        </div>
      </div>

      <div class="col-6 col-md-4 col-xl">
        <div class="stat-card">
          <div>
            <div class="stat-label">Assignments</div>
            <div class="stat-value"><?= number_format($stats['assignments']) ?></div>
            <small class="text-muted">Active course tasks</small>
          </div>
          <div class="stat-icon-wrapper bg-info-subtle text-info-emphasis">
            <i class="bi bi-journal-text"></i>
          </div>
        </div>
      </div>

      <div class="col-6 col-md-4 col-xl">
        <div class="stat-card">
          <div>
            <div class="stat-label">Campus Events</div>
            <div class="stat-value text-success"><?= number_format($stats['events']) ?></div>
            <small class="text-muted">Scheduled dates</small>
          </div>
          <div class="stat-icon-wrapper bg-success-subtle text-success">
            <i class="bi bi-calendar2-check"></i>
          </div>
</div></div></div><?php include '../includes/footer.php'; ?>