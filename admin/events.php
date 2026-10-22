<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

require_role('admin');

$adminId = (int)$_SESSION['user_id'];
$pageTitle = 'Manage Events';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            $pdo = get_db_connection();

            if ($action === 'create_event') {
                $title       = trim($_POST['title'] ?? '');
                $category    = trim($_POST['category'] ?? 'Academic');
                $eventDate   = trim($_POST['event_date'] ?? '');
                $venue       = trim($_POST['venue'] ?? '');
                $capacity    = (int)($_POST['capacity'] ?? 100);
                $description = trim($_POST['description'] ?? '');

                if (empty($title) || empty($eventDate) || empty($venue)) {
                    $error = 'Title, event date, and venue are required.';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO events (title, description, event_date, venue, capacity, category, created_by, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$title, $description, $eventDate, $venue, $capacity, $category, $adminId]);
                    set_flash('success', 'Event "' . $title . '" scheduled successfully.');
                    header('Location: ' . url('admin/events.php'));
                    exit;
                }
            } elseif ($action === 'update_event') {
                $eventId     = (int)($_POST['event_id'] ?? 0);
                $title       = trim($_POST['title'] ?? '');
                $category    = trim($_POST['category'] ?? 'Academic');
                $eventDate   = trim($_POST['event_date'] ?? '');
                $venue       = trim($_POST['venue'] ?? '');
                $capacity    = (int)($_POST['capacity'] ?? 100);
                $description = trim($_POST['description'] ?? '');

                if ($eventId <= 0 || empty($title) || empty($eventDate) || empty($venue)) {
                    $error = 'All required fields must be supplied.';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE events
                        SET title = ?, category = ?, event_date = ?, venue = ?, capacity = ?, description = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $category, $eventDate, $venue, $capacity, $description, $eventId]);
                    set_flash('success', 'Event details updated.');
                    header('Location: ' . url('admin/events.php'));
                    exit;
                }
            } elseif ($action === 'delete_event') {
                $eventId = (int)($_POST['event_id'] ?? 0);
                if ($eventId > 0) {
                    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
                    $stmt->execute([$eventId]);
                    set_flash('info', 'Event deleted.');
                    header('Location: ' . url('admin/events.php'));
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log('Event admin error: ' . $e->getMessage());
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$events = [];
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("
        SELECT e.id, e.title, e.description, e.event_date, e.venue, e.capacity, e.category, e.created_at,
               COUNT(r.id) as registered_count
        FROM events e
        LEFT JOIN event_registrations r ON e.id = r.event_id
        GROUP BY e.id
        ORDER BY e.event_date DESC
    ");
    $events = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Fetch events error: ' . $e->getMessage());
}

$viewEventId = (int)($_GET['view_roster'] ?? 0);
$roster = [];
$viewEvent = null;

if ($viewEventId > 0) {
    try {
        $pdo = get_db_connection();
        $stmtEvt = $pdo->prepare("SELECT id, title, event_date, venue FROM events WHERE id = ?");
        $stmtEvt->execute([$viewEventId]);
        $viewEvent = $stmtEvt->fetch();

        if ($viewEvent) {
            $stmtRoster = $pdo->prepare("
                SELECT r.registered_at, u.name as student_name, u.email as student_email, u.roll_number, u.department, u.phone
                FROM event_registrations r
                JOIN users u ON r.student_id = u.id
                WHERE r.event_id = ?
                ORDER BY r.registered_at ASC
            ");
            $stmtRoster->execute([$viewEventId]);
            $roster = $stmtRoster->fetchAll();
        }
    } catch (Exception $e) {
        error_log('Roster error: ' . $e->getMessage());
    }
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="portal-container">
  <?php require_once dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <main class="portal-content">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
      <div>
        <h4 class="fw-bold mb-1">Campus Events & Registrations</h4>
        <p class="text-muted small mb-0">Organize hackathons, seminars, and masterclasses, and manage participant rosters.</p>
      </div>
      <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
          <i class="bi bi-calendar-plus me-1"></i>Create New Event
        </button>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger py-2 mb-3 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($viewEvent): ?>
      <div class="card card-custom mb-4 shadow-none border border-primary">
        <div class="card-header-clean bg-primary-subtle">
          <div>
            <span class="badge bg-primary text-white me-2">Registered Attendees</span>
            <strong class="text-primary"><?= e($viewEvent['title']) ?></strong>
            <span class="small text-muted">&bull; <?= format_date($viewEvent['event_date']) ?></span>
          </div>
          <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-printer me-1"></i>Print Roster
            </button>
            <a href="<?= url('admin/events.php') ?>" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-x-lg me-1"></i>Close Roster
            </a>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-custom mb-0 align-middle">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Student Name</th>
                  <th>Roll Number</th>
                  <th>Department</th>
                  <th>Contact</th>
                  <th>Registered At</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($roster)): ?>
                  <tr>
                    <td colspan="6" class="text-center py-4 text-muted">No students have registered for this event yet.</td>
                  </tr>
                <?php else: ?>
                  <?php $idx = 1; foreach ($roster as $person): ?>
                    <tr>
                      <td><?= $idx++ ?></td>
                      <td class="fw-bold text-body"><?= e($person['student_name']) ?></td>
                      <td><code><?= e($person['roll_number']) ?></code></td>
                      <td><span class="badge bg-secondary-subtle text-secondary"><?= e($person['department']) ?></span></td>
                      <td>
                        <small class="text-muted"><?= e($person['email']) ?></small>
                        <?php if ($person['phone']): ?>
                          <small class="d-block text-muted"><?= e($person['phone']) ?></small>
                        <?php endif; ?>
                      </td>
                      <td><small class="text-muted"><?= format_date($person['registered_at'], 'M d, Y h:i A') ?></small></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="card card-custom shadow-none border">
      <div class="card-header-clean">
        <span class="text-body"><i class="bi bi-calendar-event-fill text-primary me-2"></i>Campus Calendar</span>
        <span class="badge bg-secondary-subtle text-secondary"><?= count($events) ?> Events</span>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-custom mb-0 align-middle">
            <thead>
              <tr>
                <th>Event Details</th>
                <th>Category</th>
                <th>Date & Time</th>
                <th>Venue</th>
                <th>Attendance</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($events)): ?>
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">No events currently scheduled.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($events as $evt): ?>
                  <tr>
                    <td style="max-width: 250px;">
                      <div class="fw-bold text-body text-truncate"><?= e($evt['title']) ?></div>
                      <small class="text-muted d-block text-truncate"><?= e($evt['description']) ?></small>
                    </td>
                    <td><span class="badge <?= category_badge_class($evt['category']) ?>"><?= e($evt['category']) ?></span></td>
                    <td><small class="text-muted"><?= format_date($evt['event_date'], 'M d, Y h:i A') ?></small></td>
                    <td><small class="text-body"><?= e($evt['venue']) ?></small></td>
                    <td>
                      <a href="<?= url('admin/events.php?view_roster=' . $evt['id']) ?>" class="btn btn-sm btn-outline-primary py-0 px-2 small">
                        <i class="bi bi-people me-1"></i><?= (int)$evt['registered_count'] ?> / <?= (int)$evt['capacity'] ?>
                      </a>
                    </td>
                    <td class="text-end">
                      <div class="d-inline-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editEvtModal-<?= $evt['id'] ?>">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="<?= url('admin/events.php') ?>" class="d-inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="delete_event">
                          <input type="hidden" name="event_id" value="<?= $evt['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Are you sure you want to delete event '<?= e($evt['title']) ?>'?">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>

                  <div class="modal fade" id="editEvtModal-<?= $evt['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content card-custom">
                        <form method="POST" action="<?= url('admin/events.php') ?>">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="update_event">
                          <input type="hidden" name="event_id" value="<?= $evt['id'] ?>">

                          <div class="modal-header border-bottom">
                            <h5 class="modal-title fw-bold">Edit Event Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>

                          <div class="modal-body">
                            <div class="mb-3">
                              <label class="form-label">Title *</label>
                              <input type="text" class="form-control" name="title" value="<?= e($evt['title']) ?>" required>
                            </div>

                            <div class="row g-2 mb-3">
                              <div class="col-6">
                                <label class="form-label">Category *</label>
                                <select class="form-select" name="category">
                                  <option value="Academic" <?= ($evt['category'] === 'Academic') ? 'selected' : '' ?>>Academic</option>
                                  <option value="Technical" <?= ($evt['category'] === 'Technical') ? 'selected' : '' ?>>Technical</option>
                                  <option value="Career" <?= ($evt['category'] === 'Career') ? 'selected' : '' ?>>Career</option>
                                  <option value="Competition" <?= ($evt['category'] === 'Competition') ? 'selected' : '' ?>>Competition</option>
                                  <option value="Cultural" <?= ($evt['category'] === 'Cultural') ? 'selected' : '' ?>>Cultural</option>
                                </select>
                              </div>
                              <div class="col-6">
                                <label class="form-label">Seat Capacity *</label>
                                <input type="number" class="form-control" name="capacity" value="<?= e($evt['capacity']) ?>" required>
                              </div>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">Date & Time *</label>
                              <input type="datetime-local" class="form-control" name="event_date" value="<?= date('Y-m-d\TH:i', strtotime($evt['event_date'])) ?>" required>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">Venue / Room *</label>
                              <input type="text" class="form-control" name="venue" value="<?= e($evt['venue']) ?>" required>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">Description</label>
                              <textarea class="form-control" name="description" rows="3"><?= e($evt['description']) ?></textarea>
                            </div>
                          </div>

                          <div class="modal-footer border-top">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>

                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </main>
</div>

<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card-custom">
      <form method="POST" action="<?= url('admin/events.php') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_event">

        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold" id="createEventModalLabel">Schedule Campus Event</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Event Title *</label>
            <input type="text" class="form-control" name="title" required placeholder="e.g. CodeSprint 2026 Hackathon">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Category *</label>
              <select class="form-select" name="category" required>
                <option value="Academic">Academic</option>
                <option value="Technical" selected>Technical</option>
                <option value="Career">Career & Placement</option>
                <option value="Competition">Competition</option>
                <option value="Cultural">Cultural</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Participant Capacity *</label>
              <input type="number" class="form-control" name="capacity" value="100" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Date & Time *</label>
            <input type="datetime-local" class="form-control" name="event_date" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Venue / Hall *</label>
            <input type="text" class="form-control" name="venue" required placeholder="e.g. Auditorium Hall B">
          </div>

          <div class="mb-3">
            <label class="form-label">Event Description</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Provide schedule breakdown, eligibility, rules, and speaker notes..."></textarea>
          </div>
        </div>

        <div class="modal-footer border-top">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-calendar-check me-1"></i>Publish Event
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
