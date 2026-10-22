<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_role('student');

$studentId = (int)$_SESSION['user_id'];
$pageTitle = 'Student Dashboard';

$stats = [
    'resources'   => 0,
    'pending_asg' => 0,
    'events_reg'  => 0,
    'announcements' => 0,
];
$upcomingDeadlines = [];
$recentResources = [];
$recentAnnouncements = [];

try {
    $pdo = get_db_connection();

    $stats['resources'] = (int)$pdo->query("SELECT COUNT(*) FROM resources")->fetchColumn();

    $stmtPending = $pdo->prepare("
        SELECT COUNT(*)
        FROM assignments a
        WHERE a.due_date >= NOW()
        AND a.id NOT IN (
            SELECT assignment_id FROM assignment_submissions WHERE student_id = ?
        )
    ");
    $stmtPending->execute([$studentId]);
    $stats['pending_asg'] = (int)$stmtPending->fetchColumn();

    $stmtRegEvt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE student_id = ?");
    $stmtRegEvt->execute([$studentId]);
    $stats['events_reg'] = (int)$stmtRegEvt->fetchColumn();

    $stats['announcements'] = (int)$pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn();

    $stmtAsg = $pdo->prepare("
        SELECT a.id, a.title, a.subject, a.due_date, a.max_marks,
               s.status as submission_status, s.grade
        FROM assignments a
        LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
        ORDER BY a.due_date ASC
        LIMIT 5
    ");
    $stmtAsg->execute([$studentId]);
    $upcomingDeadlines = $stmtAsg->fetchAll();

    $stmtRes = $pdo->query("
        SELECT id, title, subject, file_name, file_size, created_at
        FROM resources
        ORDER BY created_at DESC
        LIMIT 4
    ");
    $recentResources = $stmtRes->fetchAll();

    $stmtAnn = $pdo->query("
        SELECT id, title, content, category, is_pinned, created_at
        FROM announcements
        ORDER BY is_pinned DESC, created_at DESC
        LIMIT 3
    ");
    $recentAnnouncements = $stmtAnn->fetchAll();

} catch (Exception $e) {
    error_log('Student dashboard error: ' . $e->getMessage());
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="portal-container">

  <?php require_once dirname(__DIR__) . '/includes/student_sidebar.php'; ?>

  <main class="portal-content">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
      <div>
        <h4 class="fw-bold mb-1">Welcome back, <?= e($_SESSION['user_name']) ?>!</h4>
        <p class="text-muted small mb-0">
          <i class="bi bi-mortarboard me-1 text-primary"></i><?= e($_SESSION['user_department'] ?: 'Student') ?>
          &bull; Roll No: <code><?= e($_SESSION['user_roll_number'] ?: 'N/A') ?></code>
        </p>
      </div>
      <div>
        <a href="<?= url('student/resources.php') ?>" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-search me-1"></i>Search Materials
        </a>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-6 col-lg-3">
        <a href="<?= url('student/resources.php') ?>" class="stat-card">
          <div class="stat-card-content">
            <div class="stat-label">Study Files</div>
            <div class="stat-value"><?= number_format($stats['resources']) ?></div>
            <small class="stat-desc">In digital library</small>
          </div>
          <div class="stat-icon-wrapper bg-primary-subtle text-primary">
            <i class="bi bi-folder2-open"></i>
          </div>
        </a>
      </div>

      <div class="col-6 col-lg-3">
        <a href="<?= url('student/assignments.php') ?>" class="stat-card">
          <div class="stat-card-content">
            <div class="stat-label">Pending Tasks</div>
            <div class="stat-value text-warning"><?= number_format($stats['pending_asg']) ?></div>
            <small class="stat-desc">Due for submission</small>
          </div>
          <div class="stat-icon-wrapper bg-warning-subtle text-warning-emphasis">
            <i class="bi bi-clock-history"></i>
          </div>
        </a>
      </div>

      <div class="col-6 col-lg-3">
        <a href="<?= url('student/events.php') ?>" class="stat-card">
          <div class="stat-card-content">
            <div class="stat-label">My Events</div>
            <div class="stat-value text-success"><?= number_format($stats['events_reg']) ?></div>
            <small class="stat-desc">Registered items</small>
          </div>
          <div class="stat-icon-wrapper bg-success-subtle text-success">
            <i class="bi bi-calendar2-check"></i>
          </div>
        </a>
      </div>

      <div class="col-6 col-lg-3">
        <a href="#notice-board-card" class="stat-card border-info-subtle">
          <div class="stat-card-content">
            <div class="stat-label text-info-emphasis">Notice Board</div>
            <div class="stat-value text-info"><?= number_format($stats['announcements']) ?></div>
            <small class="stat-desc">Active circulars</small>
          </div>
          <div class="stat-icon-wrapper bg-info-subtle text-info-emphasis">
            <i class="bi bi-megaphone"></i>
          </div>
        </a>
      </div>
    </div>

    <div class="row g-3">

      <div class="col-12 col-xl-7 d-flex flex-column gap-3">

        <div class="card card-custom shadow-none border">
          <div class="card-header-clean py-2 px-3">
            <span class="text-body fw-bold"><i class="bi bi-journal-text text-primary me-2"></i>Assignment Deadlines</span>
            <a href="<?= url('student/assignments.php') ?>" class="small text-primary text-decoration-none">View All</a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-custom table-sm mb-0 align-middle">
                <thead>
                  <tr>
                    <th class="ps-3">Title & Subject</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($upcomingDeadlines)): ?>
                    <tr>
                      <td colspan="4" class="text-center py-4 text-muted">No active assignments assigned.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($upcomingDeadlines as $asg): ?>
                      <?php
                        $isOverdue = strtotime($asg['due_date']) < time();
                        $status = $asg['submission_status'] ?? 'pending';
                      ?>
                      <tr>
                        <td class="ps-3">
                          <div class="fw-bold text-body small text-truncate" style="max-width: 220px;"><?= e($asg['title']) ?></div>
                          <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;"><?= e($asg['subject']) ?></span>
                        </td>
                        <td>
                          <div class="small <?= ($isOverdue && $status === 'pending') ? 'text-danger fw-bold' : 'text-muted' ?>" style="font-size: 0.78rem;">
                            <?= format_date($asg['due_date'], 'M d, Y h:i A') ?>
                          </div>
                          <?php if ($isOverdue && $status === 'pending'): ?>
                            <span class="badge bg-danger-subtle text-danger py-0" style="font-size: 0.68rem;">Overdue</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if ($status === 'graded'): ?>
                            <span class="badge bg-success-subtle text-success py-1" style="font-size: 0.72rem;">Graded: <?= e($asg['grade']) ?></span>
                          <?php elseif ($status === 'submitted'): ?>
                            <span class="badge bg-info-subtle text-info-emphasis py-1" style="font-size: 0.72rem;">Submitted</span>
                          <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis py-1" style="font-size: 0.72rem;">Pending</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end pe-3">
                          <a href="<?= url('student/assignments.php') ?>" class="btn btn-sm btn-outline-primary py-0.5 px-2" style="font-size: 0.75rem;">
                            <?= ($status === 'pending') ? 'Submit' : 'View' ?>
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card card-custom p-3 shadow-none border bg-body-tertiary">
          <div class="d-flex justify-content-between align-items-center mb-2 px-1">
            <span class="fw-bold small text-body"><i class="bi bi-lightning-charge text-warning me-1.5"></i>Quick Actions</span>
            <span class="small text-muted" style="font-size: 0.75rem;">Shortcuts</span>
          </div>
          <div class="row g-2">
            <div class="col-6 col-sm-3">
              <a href="<?= url('student/resources.php') ?>" class="btn btn-sm btn-outline-secondary w-100 text-center py-2 d-flex flex-column align-items-center gap-1">
                <i class="bi bi-search text-primary fs-6"></i>
                <span style="font-size: 0.75rem;">Search Notes</span>
              </a>
            </div>
            <div class="col-6 col-sm-3">
              <a href="<?= url('student/events.php') ?>" class="btn btn-sm btn-outline-secondary w-100 text-center py-2 d-flex flex-column align-items-center gap-1">
                <i class="bi bi-calendar-plus text-success fs-6"></i>
                <span style="font-size: 0.75rem;">Events</span>
              </a>
            </div>
            <div class="col-6 col-sm-3">
              <a href="<?= url('student/feedback.php') ?>" class="btn btn-sm btn-outline-secondary w-100 text-center py-2 d-flex flex-column align-items-center gap-1">
                <i class="bi bi-chat-quote text-info fs-6"></i>
                <span style="font-size: 0.75rem;">Feedback</span>
              </a>
            </div>
            <div class="col-6 col-sm-3">
              <a href="<?= url('courses.php') ?>" class="btn btn-sm btn-outline-secondary w-100 text-center py-2 d-flex flex-column align-items-center gap-1">
                <i class="bi bi-filetype-xml text-danger fs-6"></i>
                <span style="font-size: 0.75rem;">Course XML</span>
              </a>
            </div>
          </div>
          <div class="mt-2.5 pt-2 border-top text-muted d-flex justify-content-between align-items-center" style="font-size: 0.75rem;">
            <span>Need assistance? Contact department coordinator</span>
            <a href="<?= url('student/feedback.php') ?>" class="text-primary text-decoration-none">Help Desk &rarr;</a>
          </div>
        </div>

      </div>

      <div class="col-12 col-xl-5 d-flex flex-column gap-3">

        <div class="card card-custom shadow-none border">
          <div class="card-header-clean py-2 px-3">
            <span class="text-body fw-bold">
              <i class="bi bi-folder2-open text-primary me-2"></i>Study Files
            </span>
            <a href="<?= url('student/resources.php') ?>" class="small text-primary text-decoration-none">Browse All</a>
          </div>
          <div class="card-body p-2.5">
            <?php if (empty($recentResources)): ?>
              <div class="text-center py-3 text-muted small">No study files available.</div>
            <?php else: ?>
              <div class="d-flex flex-column gap-2">
                <?php foreach (array_slice($recentResources, 0, 3) as $res): ?>
                  <div class="d-flex justify-content-between align-items-center p-2 border rounded bg-body-tertiary">
                    <div class="me-2 text-truncate">
                      <div class="fw-bold text-body small text-truncate"><?= e($res['title']) ?></div>
                      <div class="text-muted" style="font-size: 0.73rem;">
                        <span class="badge bg-primary-subtle text-primary me-1 py-0 px-1"><?= e($res['subject']) ?></span>
                        <?= format_bytes((int)$res['file_size']) ?> &bull; <?= time_ago($res['created_at']) ?>
                      </div>
                    </div>
                    <a href="<?= url('download.php?id=' . $res['id']) ?>" class="btn btn-sm btn-outline-primary flex-shrink-0 py-0.5 px-2" title="Download">
                      <i class="bi bi-download"></i>
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="card card-custom shadow-none border" id="notice-board-card">
          <div class="card-header-clean py-2 px-3">
            <div class="d-flex align-items-center gap-2">
              <span class="text-body fw-bold">
                <i class="bi bi-bell-fill text-primary me-2"></i>Notice Board
              </span>
              <span class="badge bg-primary-subtle text-primary py-0.5 px-2" style="font-size: 0.7rem;">Circulars</span>
            </div>
            <span class="small text-muted" style="font-size: 0.75rem;"><?= count($recentAnnouncements) ?> Active</span>
          </div>
          <div class="card-body p-2.5 notice-scroll-container" style="max-height: 230px; overflow-y: auto;">
            <?php if (empty($recentAnnouncements)): ?>
              <div class="text-center py-3 text-muted small">No announcements posted.</div>
            <?php else: ?>
              <div class="d-flex flex-column gap-2">
                <?php foreach ($recentAnnouncements as $ann): ?>
                  <div class="p-2 border rounded bg-body-tertiary <?= $ann['is_pinned'] ? 'border-primary-subtle bg-primary-subtle bg-opacity-10' : '' ?>">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <div class="d-flex align-items-center gap-1">
                        <span class="badge <?= category_badge_class($ann['category']) ?>" style="font-size: 0.68rem;"><?= e($ann['category']) ?></span>
                        <?php if ($ann['is_pinned']): ?>
                          <span class="badge bg-warning-subtle text-warning-emphasis" style="font-size: 0.68rem;"><i class="bi bi-pin-angle-fill me-1"></i>Pinned</span>
                        <?php endif; ?>
                      </div>
                      <small class="text-muted" style="font-size: 0.72rem;"><i class="bi bi-clock me-1"></i><?= time_ago($ann['created_at']) ?></small>
                    </div>
                    <div class="fw-bold text-body small mb-1"><?= e($ann['title']) ?></div>
                    <p class="text-muted small mb-0 line-clamp-2" style="font-size: 0.78rem; line-height: 1.35;"><?= e($ann['content']) ?></p>
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
