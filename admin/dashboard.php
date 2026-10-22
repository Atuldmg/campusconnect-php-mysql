<?php

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

    $stats['students']     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $stats['resources']    = (int)$pdo->query("SELECT COUNT(*) FROM resources")->fetchColumn();
    $stats['assignments']  = (int)$pdo->query("SELECT COUNT(*) FROM assignments")->fetchColumn();
    $stats['events']       = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $stats['new_feedback'] = (int)$pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'new'")->fetchColumn();

    $stmtUsers = $pdo->query("SELECT id, name, email, department, roll_number, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC LIMIT 5");
    $recentUsers = $stmtUsers->fetchAll();

    $stmtFb = $pdo->query("
        SELECT f.id, f.subject, f.category, f.status, f.created_at, u.name as student_name
        FROM feedback f
        JOIN users u ON f.student_id = u.id
        ORDER BY f.created_at DESC
        LIMIT 4
    ");
    $recentFeedback = $stmtFb->fetchAll();

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
        </div>
      </div>

      <div class="col-6 col-md-4 col-xl">
        <div class="stat-card">
          <div>
            <div class="stat-label">New Inquiries</div>
            <div class="stat-value text-warning"><?= number_format($stats['new_feedback']) ?></div>
            <small class="text-muted">Pending review</small>
          </div>
          <div class="stat-icon-wrapper bg-warning-subtle text-warning-emphasis">
            <i class="bi bi-chat-left-dots"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">

      <div class="col-12 col-xl-7">
        <div class="card card-custom h-100 shadow-none border">
          <div class="card-header-clean">
            <span class="text-body"><i class="bi bi-people text-primary me-2"></i>Recently Registered Students</span>
            <a href="<?= url('admin/users.php') ?>" class="small text-primary text-decoration-none">Manage All Users</a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-custom mb-0 align-middle">
                <thead>
                  <tr>
                    <th>Student Name</th>
                    <th>Roll Number</th>
                    <th>Department</th>
                    <th>Joined</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recentUsers)): ?>
                    <tr>
                      <td colspan="4" class="text-center py-4 text-muted">No student records found.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($recentUsers as $u): ?>
                      <tr>
                        <td>
                          <div class="fw-bold text-body"><?= e($u['name']) ?></div>
                          <small class="text-muted"><?= e($u['email']) ?></small>
                        </td>
                        <td><code><?= e($u['roll_number'] ?: '—') ?></code></td>
                        <td><span class="badge bg-secondary-subtle text-secondary"><?= e($u['department'] ?: 'General') ?></span></td>
                        <td><small class="text-muted"><?= time_ago($u['created_at']) ?></small></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-5">

        <div class="card card-custom mb-4 shadow-none border">
          <div class="card-header-clean">
            <span class="text-body"><i class="bi bi-chat-quote text-primary me-2"></i>Recent Inquiries</span>
            <a href="<?= url('admin/feedback.php') ?>" class="small text-primary text-decoration-none">Review</a>
          </div>
          <div class="card-body p-3">
            <?php if (empty($recentFeedback)): ?>
              <div class="text-center py-3 text-muted small">No recent feedback submitted.</div>
            <?php else: ?>
              <div class="d-flex flex-column gap-2">
                <?php foreach ($recentFeedback as $fb): ?>
                  <div class="p-2 border rounded bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="fw-bold small text-body"><?= e($fb['subject']) ?></span>
                      <?php if ($fb['status'] === 'new'): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis">New</span>
                      <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary"><?= e($fb['status']) ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                      <span>By <?= e($fb['student_name']) ?></span>
                      <span><?= time_ago($fb['created_at']) ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="card card-custom shadow-none border">
          <div class="card-header-clean">
            <span class="text-body"><i class="bi bi-calendar-check text-primary me-2"></i>Recent Event Registrations</span>
            <a href="<?= url('admin/events.php') ?>" class="small text-primary text-decoration-none">Details</a>
          </div>
          <div class="card-body p-3">
            <?php if (empty($recentRegistrations)): ?>
              <div class="text-center py-3 text-muted small">No event registrations recorded yet.</div>
            <?php else: ?>
              <div class="d-flex flex-column gap-2">
                <?php foreach ($recentRegistrations as $reg): ?>
                  <div class="d-flex justify-content-between align-items-center small p-2 border rounded bg-body-tertiary">
                    <div>
                      <div class="fw-bold text-body"><?= e($reg['student_name']) ?></div>
                      <div class="text-muted"><?= e($reg['event_title']) ?></div>
                    </div>
                    <span class="text-muted flex-shrink-0"><?= time_ago($reg['registered_at']) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>

    </div>

  </main>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
