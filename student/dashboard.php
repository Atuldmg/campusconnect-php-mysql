<?php
/**
 * CampusConnect - Student Dashboard
 * Personalized hub displaying academic deadlines, resources, registered events, and circulars.
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_role('student');

$studentId = (int)$_SESSION['user_id'];
$pageTitle = 'Student Dashboard';

// Metrics and list data
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

    // 1. Total available resources
    $stats['resources'] = (int)$pdo->query("SELECT COUNT(*) FROM resources")->fetchColumn();

    // 2. Pending assignments (assignments where due_date >= NOW() and not yet submitted by student)
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

    // 3. Registered events
    $stmtRegEvt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE student_id = ?");
    $stmtRegEvt->execute([$studentId]);
    $stats['events_reg'] = (int)$stmtRegEvt->fetchColumn();

    // 4. Total announcements
    $stats['announcements'] = (int)$pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn();

    // Upcoming assignments with submission status
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

    // Recent resources
    $stmtRes = $pdo->query("
        SELECT id, title, subject, file_name, file_size, created_at 
        FROM resources 
        ORDER BY created_at DESC 
        LIMIT 4
    ");
    $recentResources = $stmtRes->fetchAll();

    // Recent announcements
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
  
  <!-- Left Sidebar Navigation -->
  <?php require_once dirname(__DIR__) . '/includes/student_sidebar.php'; ?>

  <!-- Main Content Area -->
  <main class="portal-content">
    
    <!-- Welcome Header & Profile Summary -->
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

    <!-- 4 Key Statistic Cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div>
            <div class="stat-label">Study Files</div>
            <div class="stat-value"><?= number_format($stats['resources']) ?></div>
            <small class="text-muted">Available in library</small>
          </div>
          <div class="stat-icon-wrapper bg-primary-subtle text-primary">
            <i class="bi bi-folder2-open"></i>
          </div>
        </div>
      </div>

      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div>
            <div class="stat-label">Pending Tasks</div>
            <div class="stat-value text-warning"><?= number_format($stats['pending_asg']) ?></div>
            <small class="text-muted">Due for submission</small>
          </div>
          <div class="stat-icon-wrapper bg-warning-subtle text-warning-emphasis">
            <i class="bi bi-clock-history"></i>
          </div>
        </div>
      </div>

      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div>
            <div class="stat-label">My Events</div>
            <div class="stat-value text-success"><?= number_format($stats['events_reg']) ?></div>
            <small class="text-muted">Registered activities</small>
          </div>
          <div class="stat-icon-wrapper bg-success-subtle text-success">
            <i class="bi bi-calendar2-check"></i>
          </div>
        </div>
      </div>

      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div>
            <div class="stat-label">Notice Board</div>
            <div class="stat-value text-info"><?= number_format($stats['announcements']) ?></div>
            <small class="text-muted">Active notices</small>
          </div>
          <div class="stat-icon-wrapper bg-info-subtle text-info-emphasis">
            <i class="bi bi-megaphone"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Content Row 1: Course Assignments -->
    <div class="row g-4 mb-4">
      <div class="col-12 col-xl-8">
        <div class="card card-custom h-100 shadow-none border">
          <div class="card-header-clean">
            <span class="text-body"><i class="bi bi-journal-text text-primary me-2"></i>Assignment Deadlines</span>
            <a href="<?= url('student/assignments.php') ?>" class="small text-primary text-decoration-none">View All</a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-custom mb-0 align-middle">
                <thead>
                  <tr>
                    <th>Title & Subject</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
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
                        <td>
                          <div class="fw-bold text-body"><?= e($asg['title']) ?></div>
                          <span class="badge bg-secondary-subtle text-secondary small"><?= e($asg['subject']) ?></span>
                        </td>
                        <td>
                          <div class="small <?= ($isOverdue && $status === 'pending') ? 'text-danger fw-bold' : 'text-muted' ?>">
                            <?= format_date($asg['due_date'], 'M d, Y h:i A') ?>
                          </div>
                          <?php if ($isOverdue && $status === 'pending'): ?>
                            <span class="badge bg-danger-subtle text-danger py-0" style="font-size: 0.7rem;">Overdue</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if ($status === 'graded'): ?>
                            <span class="badge bg-success-subtle text-success">Graded: <?= e($asg['grade']) ?></span>
                          <?php elseif ($status === 'submitted'): ?>
                            <span class="badge bg-info-subtle text-info-emphasis">Submitted</span>
                          <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis">Pending</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end">
                          <a href="<?= url('student/assignments.php') ?>" class="btn btn-sm btn-outline-primary py-1 px-2">
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
      </div>

      <!-- Quick Action Shortcuts & Info -->
      <div class="col-12 col-xl-4">
        <div class="card card-custom h-100 p-3 shadow-none border bg-body-tertiary">
          <h6 class="fw-bold mb-3"><i class="bi bi-lightning-charge text-warning me-2"></i>Quick Actions</h6>
          <div class="d-grid gap-2">
            <a href="<?= url('student/resources.php') ?>" class="btn btn-outline-secondary text-start py-2">
              <i class="bi bi-search me-2 text-primary"></i>Search Lecture Slides & Notes
            </a>
            <a href="<?= url('student/events.php') ?>" class="btn btn-outline-secondary text-start py-2">
              <i class="bi bi-calendar-plus me-2 text-success"></i>Register for Campus Events
            </a>
            <a href="<?= url('student/feedback.php') ?>" class="btn btn-outline-secondary text-start py-2">
              <i class="bi bi-chat-quote me-2 text-info"></i>Submit Student Feedback
            </a>
            <a href="<?= url('courses.php') ?>" class="btn btn-outline-secondary text-start py-2">
              <i class="bi bi-filetype-xml me-2 text-danger"></i>Examine Course Catalogue (XML)
            </a>
          </div>

          <div class="mt-4 pt-3 border-top small text-muted">
            <strong>Need Assistance?</strong>
            <p class="mb-0 mt-1">Contact your department coordinator or submit a message via the Feedback module.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Content Row 2: Recent Resources & Announcements -->
    <div class="row g-4">
      
      <!-- Left: Recent Study Materials -->
      <div class="col-12 col-lg-6">
        <div class="card card-custom h-100 shadow-none border">
          <div class="card-header-clean">
            <span class="text-body"><i class="bi bi-file-earmark-arrow-down text-primary me-2"></i>Recently Added Study Resources</span>
            <a href="<?= url('student/resources.php') ?>" class="small text-primary text-decoration-none">Browse All</a>
          </div>
          <div class="card-body p-3">
            <?php if (empty($recentResources)): ?>
              <div class="text-center py-4 text-muted small">No resources available.</div>
            <?php else: ?>
              <div class="d-flex flex-column gap-3">
                <?php foreach ($recentResources as $res): ?>
                  <div class="d-flex justify-content-between align-items-center p-2 border rounded bg-body-tertiary">
                    <div class="me-2 text-truncate">
                      <div class="fw-bold text-body small text-truncate"><?= e($res['title']) ?></div>
                      <div class="text-muted" style="font-size: 0.78rem;">
                        <span class="badge bg-primary-subtle text-primary me-1"><?= e($res['subject']) ?></span>
                        <?= format_bytes((int)$res['file_size']) ?> &bull; <?= time_ago($res['created_at']) ?>
                      </div>
                    </div>
                    <a href="<?= url('download.php?id=' . $res['id']) ?>" class="btn btn-sm btn-outline-primary flex-shrink-0" title="Download">
                      <i class="bi bi-download"></i>
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Right: Announcements -->
      <div class="col-12 col-lg-6">
        <div class="card card-custom h-100 shadow-none border">
          <div class="card-header-clean">
            <span class="text-body"><i class="bi bi-bell text-primary me-2"></i>Notice Board</span>
            <span class="small text-muted">Official circulars</span>
          </div>
          <div class="card-body p-3">
            <?php if (empty($recentAnnouncements)): ?>
              <div class="text-center py-4 text-muted small">No announcements posted.</div>
            <?php else: ?>
              <div class="d-flex flex-column gap-3">
                <?php foreach ($recentAnnouncements as $ann): ?>
                  <div class="border-bottom pb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="badge <?= category_badge_class($ann['category']) ?>"><?= e($ann['category']) ?></span>
                      <small class="text-muted"><?= time_ago($ann['created_at']) ?></small>
                    </div>
                    <div class="fw-bold text-body small mb-1"><?= e($ann['title']) ?></div>
                    <p class="text-muted small mb-0 text-truncate"><?= e($ann['content']) ?></p>
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
