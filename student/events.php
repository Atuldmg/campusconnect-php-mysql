<?php
/**
 * CampusConnect - Campus Events & Registration Portal
 * Allows students to browse upcoming activities, register, and manage bookings.
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

require_role('student');

$studentId = (int)$_SESSION['user_id'];
$pageTitle = 'Campus Events';
$error = '';

// Handle event registration / cancellation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $eventId = (int)($_POST['event_id'] ?? 0);

        if ($eventId <= 0) {
            $error = 'Invalid event requested.';
        } else {
            try {
                $pdo = get_db_connection();

                if ($action === 'register') {
                    // Check if already registered
                    $checkStmt = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND student_id = ?");
                    $checkStmt->execute([$eventId, $studentId]);
                    if ($checkStmt->fetch()) {
                        set_flash('info', 'You are already registered for this event.');
                    } else {
                        // Check event capacity
                        $capStmt = $pdo->prepare("
                            SELECT e.capacity, COUNT(r.id) as reg_count, e.title 
                            FROM events e 
                            LEFT JOIN event_registrations r ON e.id = r.event_id 
                            WHERE e.id = ? 
                            GROUP BY e.id
                        ");
                        $capStmt->execute([$eventId]);
                        $evtInfo = $capStmt->fetch();

                        if ($evtInfo && $evtInfo['reg_count'] >= $evtInfo['capacity']) {
                            $error = 'Sorry, this event has reached its maximum participant capacity.';
                        } else {
                            $regStmt = $pdo->prepare("INSERT INTO event_registrations (event_id, student_id, registered_at) VALUES (?, ?, NOW())");
                            $regStmt->execute([$eventId, $studentId]);
                            set_flash('success', 'Successfully registered for "' . $evtInfo['title'] . '"!');
                        }
                    }
                } elseif ($action === 'cancel') {
                    $delStmt = $pdo->prepare("DELETE FROM event_registrations WHERE event_id = ? AND student_id = ?");
                    $delStmt->execute([$eventId, $studentId]);
                    set_flash('info', 'Your event registration has been cancelled.');
                }

                header('Location: ' . url('student/events.php'));
                exit;

            } catch (Exception $e) {
                error_log('Event registration error: ' . $e->getMessage());
                $error = 'Database error while updating registration: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all events with registration count and current student status
$events = [];
try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("
        SELECT e.id, e.title, e.description, e.event_date, e.venue, e.capacity, e.category,
               COUNT(r_all.id) as total_registered,
               MAX(CASE WHEN r_student.student_id = ? THEN 1 ELSE 0 END) as is_registered
        FROM events e
        LEFT JOIN event_registrations r_all ON e.id = r_all.event_id
        LEFT JOIN event_registrations r_student ON e.id = r_student.event_id AND r_student.student_id = ?
        GROUP BY e.id
        ORDER BY e.event_date ASC
    ");
    $stmt->execute([$studentId, $studentId]);
    $events = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Fetch events error: ' . $e->getMessage());
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="portal-container">
  <?php require_once dirname(__DIR__) . '/includes/student_sidebar.php'; ?>

  <main class="portal-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold mb-1">Campus Events & Symposiums</h4>
        <p class="text-muted small mb-0">Discover inter-college technical hackathons, guest lectures, and cultural workshops.</p>
      </div>
      <span class="badge bg-secondary-subtle text-secondary px-3 py-2">
        <?= count($events) ?> Upcoming Events
      </span>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger py-2 mb-3 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($error) ?>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <?php if (empty($events)): ?>
        <div class="col-12">
          <div class="card card-custom p-5 text-center text-muted">
            <i class="bi bi-calendar-x display-5 d-block mb-2"></i>
            <h6>No events currently scheduled.</h6>
            <small>Check back regularly for announcements regarding upcoming symposiums.</small>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($events as $evt): ?>
          <?php 
            $isPast = strtotime($evt['event_date']) < time();
            $isRegistered = (bool)$evt['is_registered'];
            $isFull = (int)$evt['total_registered'] >= (int)$evt['capacity'];
          ?>
          <div class="col-12 col-lg-6">
            <div class="card card-custom h-100 shadow-none border">
              <div class="card-header-clean">
                <span class="badge <?= category_badge_class($evt['category']) ?>"><?= e($evt['category']) ?></span>
                <?php if ($isRegistered): ?>
                  <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <i class="bi bi-check2-circle me-1"></i>You are Registered
                  </span>
                <?php elseif ($isPast): ?>
                  <span class="badge bg-secondary-subtle text-secondary">Event Concluded</span>
                <?php elseif ($isFull): ?>
                  <span class="badge bg-danger-subtle text-danger">Capacity Full</span>
                <?php else: ?>
                  <span class="badge bg-primary-subtle text-primary">Open for Registration</span>
                <?php endif; ?>
              </div>

              <div class="card-body p-3 d-flex flex-column justify-content-between">
                <div>
                  <h5 class="fw-bold text-body mb-2"><?= e($evt['title']) ?></h5>
                  <p class="text-muted small mb-3"><?= nl2br(e($evt['description'])) ?></p>

                  <div class="d-flex flex-column gap-1 small text-muted mb-3">
                    <div>
                      <i class="bi bi-calendar3 text-primary me-2"></i>
                      <strong>Date & Time:</strong> <?= format_date($evt['event_date'], 'l, M d, Y — h:i A') ?>
                    </div>
                    <div>
                      <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                      <strong>Venue:</strong> <?= e($evt['venue']) ?>
                    </div>
                    <div>
                      <i class="bi bi-people-fill text-info me-2"></i>
                      <strong>Seats:</strong> <?= (int)$evt['total_registered'] ?> / <?= (int)$evt['capacity'] ?> Registered
                    </div>
                  </div>
                </div>

                <div class="pt-2 border-top">
                  <form method="POST" action="<?= url('student/events.php') ?>" class="d-flex justify-content-end gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="event_id" value="<?= $evt['id'] ?>">

                    <?php if ($isRegistered): ?>
                      <input type="hidden" name="action" value="cancel">
                      <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Do you wish to cancel your registration for this event?">
                        <i class="bi bi-x-circle me-1"></i>Cancel Registration
                      </button>
                    <?php elseif (!$isPast && !$isFull): ?>
                      <input type="hidden" name="action" value="register">
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Register Now
                      </button>
                    <?php else: ?>
                      <button type="button" class="btn btn-sm btn-secondary" disabled>Unavailable</button>
                    <?php endif; ?>
                  </form>
                </div>

              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </main>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
