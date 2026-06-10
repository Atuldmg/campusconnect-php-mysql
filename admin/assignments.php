<?php
/**
 * CampusConnect - Assignments & Submissions Administration (CRUD & Grading)
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

require_role('admin');

$adminId = (int)$_SESSION['user_id'];
$pageTitle = 'Manage Assignments';
$error = '';

// Handle actions (Create, Edit, Delete, Grade)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            $pdo = get_db_connection();

            if ($action === 'create_assignment') {
                $title       = trim($_POST['title'] ?? '');
                $subject     = trim($_POST['subject'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $dueDate     = trim($_POST['due_date'] ?? '');
                $maxMarks    = (int)($_POST['max_marks'] ?? 100);

                if (empty($title) || empty($subject) || empty($dueDate)) {
                    $error = 'Title, subject, and due date are required.';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO assignments (title, subject, description, due_date, max_marks, created_by, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$title, $subject, $description, $dueDate, $maxMarks, $adminId]);
                    set_flash('success', 'Assignment "' . $title . '" published.');
                    header('Location: ' . url('admin/assignments.php'));
                    exit;
                }
            } elseif ($action === 'update_assignment') {
                $asgId       = (int)($_POST['assignment_id'] ?? 0);
                $title       = trim($_POST['title'] ?? '');
                $subject     = trim($_POST['subject'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $dueDate     = trim($_POST['due_date'] ?? '');
                $maxMarks    = (int)($_POST['max_marks'] ?? 100);

                if ($asgId <= 0 || empty($title) || empty($dueDate)) {
                    $error = 'All required fields must be supplied.';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE assignments 
                        SET title = ?, subject = ?, description = ?, due_date = ?, max_marks = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $subject, $description, $dueDate, $maxMarks, $asgId]);
                    set_flash('success', 'Assignment updated.');
                    header('Location: ' . url('admin/assignments.php'));
                    exit;
                }
            } elseif ($action === 'delete_assignment') {
                $asgId = (int)($_POST['assignment_id'] ?? 0);
                if ($asgId > 0) {
                    $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
                    $stmt->execute([$asgId]);
                    set_flash('info', 'Assignment deleted.');
                    header('Location: ' . url('admin/assignments.php'));
                    exit;
                }
            } elseif ($action === 'grade_submission') {
                $submissionId = (int)($_POST['submission_id'] ?? 0);
                $grade        = trim($_POST['grade'] ?? '');
                $feedback     = trim($_POST['feedback'] ?? '');

                if ($submissionId <= 0 || empty($grade)) {
                    $error = 'Grade score is required.';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE assignment_submissions 
                        SET grade = ?, feedback = ?, status = 'graded', updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$grade, $feedback, $submissionId]);
                    set_flash('success', 'Student submission graded successfully.');
                    header('Location: ' . url('admin/assignments.php'));
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log('Assignment admin error: ' . $e->getMessage());
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch all assignments with submission count
$assignments = [];
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("
        SELECT a.id, a.title, a.subject, a.description, a.due_date, a.max_marks, a.created_at,
               COUNT(s.id) as total_submissions,
               SUM(CASE WHEN s.status = 'graded' THEN 1 ELSE 0 END) as graded_count
        FROM assignments a
        LEFT JOIN assignment_submissions s ON a.id = s.assignment_id
        GROUP BY a.id
        ORDER BY a.due_date DESC
    ");
    $assignments = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Fetch assignments error: ' . $e->getMessage());
}

// If viewing submissions for a specific assignment
$viewAssignmentId = (int)($_GET['view_submissions'] ?? 0);
$submissions = [];
$viewAssignment = null;

if ($viewAssignmentId > 0) {
    try {
        $pdo = get_db_connection();
        $stmtAsg = $pdo->prepare("SELECT id, title, subject, max_marks FROM assignments WHERE id = ?");
        $stmtAsg->execute([$viewAssignmentId]);
        $viewAssignment = $stmtAsg->fetch();

        if ($viewAssignment) {
            $stmtSubs = $pdo->prepare("
                SELECT s.id, s.student_id, s.file_path, s.file_name, s.submission_notes, s.status, s.grade, s.feedback, s.submitted_at,
                       u.name as student_name, u.email as student_email, u.roll_number, u.department
                FROM assignment_submissions s
                JOIN users u ON s.student_id = u.id
                WHERE s.assignment_id = ?
                ORDER BY s.submitted_at DESC
            ");
            $stmtSubs->execute([$viewAssignmentId]);
            $submissions = $stmtSubs->fetchAll();
        }
    } catch (Exception $e) {
        error_log('Submissions error: ' . $e->getMessage());
    }
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="portal-container">
  <?php require_once dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <main class="portal-content">
    
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
      <div>
        <h4 class="fw-bold mb-1">Assignment & Examination Management</h4>
        <p class="text-muted small mb-0">Create academic tasks, review student deliverables, and submit evaluations.</p>
      </div>
      <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAsgModal">
          <i class="bi bi-journal-plus me-1"></i>Create New Assignment
        </button>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger py-2 mb-3 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($error) ?>
      </div>
    <?php endif; ?>

    <!-- Submissions Detail Sub-view (if triggered) -->
    <?php if ($viewAssignment): ?>
      <div class="card card-custom mb-4 shadow-none border border-primary">
        <div class="card-header-clean bg-primary-subtle">
          <div>
            <span class="badge bg-primary text-white me-2">Submissions Roster</span>
            <strong class="text-primary"><?= e($viewAssignment['title']) ?></strong>
            <span class="small text-muted">(Max: <?= e($viewAssignment['max_marks']) ?> Marks)</span>
          </div>
          <a href="<?= url('admin/assignments.php') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-x-lg me-1"></i>Close Submissions
          </a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-custom mb-0 align-middle">
              <thead>
                <tr>
                  <th>Student Info</th>
                  <th>Submitted Solution</th>
                  <th>Submission Date</th>
                  <th>Status & Grade</th>
                  <th class="text-end">Evaluate</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($submissions)): ?>
                  <tr>
                    <td colspan="5" class="text-center py-4 text-muted">No student submissions received yet for this assignment.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($submissions as $sub): ?>
                    <tr>
                      <td>
                        <div class="fw-bold text-body"><?= e($sub['student_name']) ?></div>
                        <small class="text-muted"><?= e($sub['roll_number']) ?> &bull; <?= e($sub['department']) ?></small>
                      </td>
                      <td style="max-width: 250px;">
                        <?php if ($sub['submission_notes']): ?>
                          <div class="small text-muted text-truncate mb-1"><?= e($sub['submission_notes']) ?></div>
                        <?php endif; ?>
                        <?php if ($sub['file_path']): ?>
                          <a href="<?= url($sub['file_path']) ?>" target="_blank" class="badge bg-secondary-subtle text-secondary text-decoration-none">
                            <i class="bi bi-paperclip me-1"></i><?= e($sub['file_name'] ?: 'Download Solution File') ?>
                          </a>
                        <?php else: ?>
                          <small class="text-muted fst-italic">No file attached</small>
                        <?php endif; ?>
                      </td>
                      <td><small class="text-muted"><?= format_date($sub['submitted_at'], 'M d, Y h:i A') ?></small></td>
                      <td>
                        <?php if ($sub['status'] === 'graded'): ?>
                          <span class="badge bg-success-subtle text-success">Graded: <?= e($sub['grade']) ?></span>
                        <?php else: ?>
                          <span class="badge bg-warning-subtle text-warning-emphasis">Awaiting Review</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#gradeModal-<?= $sub['id'] ?>">
                          <i class="bi bi-award me-1"></i><?= ($sub['status'] === 'graded') ? 'Re-Grade' : 'Grade' ?>
                        </button>
                      </td>
                    </tr>

                    <!-- Grade Modal -->
                    <div class="modal fade" id="gradeModal-<?= $sub['id'] ?>" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content card-custom">
                          <form method="POST" action="<?= url('admin/assignments.php?view_submissions=' . $viewAssignmentId) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="grade_submission">
                            <input type="hidden" name="submission_id" value="<?= $sub['id'] ?>">

                            <div class="modal-header border-bottom">
                              <h5 class="modal-title fw-bold">Evaluate: <?= e($sub['student_name']) ?></h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                              <div class="mb-3">
                                <label class="form-label">Grade / Marks Awarded *</label>
                                <input type="text" class="form-control" name="grade" value="<?= e($sub['grade'] ?? '') ?>" placeholder="e.g. 28/30 or 95" required>
                                <div class="form-text small">Maximum possible: <?= e($viewAssignment['max_marks']) ?> marks.</div>
                              </div>

                              <div class="mb-3">
                                <label class="form-label">Instructor Feedback & Comments</label>
                                <textarea class="form-control" name="feedback" rows="3" placeholder="Provide constructive remarks for the student..."><?= e($sub['feedback'] ?? '') ?></textarea>
                              </div>
                            </div>

                            <div class="modal-footer border-top">
                              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-primary">Save Evaluation</button>
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
    <?php endif; ?>

    <!-- Master Assignments Table -->
    <div class="card card-custom shadow-none border">
      <div class="card-header-clean">
        <span class="text-body"><i class="bi bi-journal-text text-primary me-2"></i>Assignments Roster</span>
        <span class="badge bg-secondary-subtle text-secondary"><?= count($assignments) ?> Assignments</span>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-custom mb-0 align-middle">
            <thead>
              <tr>
                <th>Title & Subject</th>
                <th>Due Date</th>
                <th>Max Marks</th>
                <th>Submissions</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($assignments)): ?>
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">No assignments created yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($assignments as $asg): ?>
                  <tr>
                    <td>
                      <div class="fw-bold text-body"><?= e($asg['title']) ?></div>
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle small"><?= e($asg['subject']) ?></span>
                    </td>
                    <td>
                      <small class="text-muted"><?= format_date($asg['due_date'], 'M d, Y h:i A') ?></small>
                    </td>
                    <td><span class="fw-semibold"><?= e($asg['max_marks']) ?></span></td>
                    <td>
                      <a href="<?= url('admin/assignments.php?view_submissions=' . $asg['id']) ?>" class="btn btn-sm btn-outline-primary py-0 px-2 small">
                        <i class="bi bi-people me-1"></i><?= (int)$asg['total_submissions'] ?> (<?= (int)$asg['graded_count'] ?> Graded)
                      </a>
                    </td>
                    <td class="text-end">
                      <div class="d-inline-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editAsgModal-<?= $asg['id'] ?>">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="<?= url('admin/assignments.php') ?>" class="d-inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="delete_assignment">
                          <input type="hidden" name="assignment_id" value="<?= $asg['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Are you sure you want to delete assignment '<?= e($asg['title']) ?>'?">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>

                  <!-- Edit Assignment Modal -->
                  <div class="modal fade" id="editAsgModal-<?= $asg['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content card-custom">
                        <form method="POST" action="<?= url('admin/assignments.php') ?>">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="update_assignment">
                          <input type="hidden" name="assignment_id" value="<?= $asg['id'] ?>">

                          <div class="modal-header border-bottom">
                            <h5 class="modal-title fw-bold">Edit Assignment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>

                          <div class="modal-body">
                            <div class="mb-3">
                              <label class="form-label">Title *</label>
                              <input type="text" class="form-control" name="title" value="<?= e($asg['title']) ?>" required>
                            </div>

                            <div class="row g-2 mb-3">
                              <div class="col-6">
                                <label class="form-label">Subject *</label>
                                <input type="text" class="form-control" name="subject" value="<?= e($asg['subject']) ?>" required>
                              </div>
                              <div class="col-6">
                                <label class="form-label">Max Marks</label>
                                <input type="number" class="form-control" name="max_marks" value="<?= e($asg['max_marks']) ?>" required>
                              </div>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">Due Date & Time *</label>
                              <input type="datetime-local" class="form-control" name="due_date" value="<?= date('Y-m-d\TH:i', strtotime($asg['due_date'])) ?>" required>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">Description / Instructions</label>
                              <textarea class="form-control" name="description" rows="3"><?= e($asg['description']) ?></textarea>
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

<!-- Create Assignment Modal -->
<div class="modal fade" id="createAsgModal" tabindex="-1" aria-labelledby="createAsgModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card-custom">
      <form method="POST" action="<?= url('admin/assignments.php') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_assignment">

        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold" id="createAsgModalLabel">Create Course Assignment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Assignment Title *</label>
            <input type="text" class="form-control" name="title" required placeholder="e.g. PHP 8 OOP Web Architecture">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Course Subject *</label>
              <input type="text" class="form-control" name="subject" required placeholder="e.g. Web Technology">
            </div>
            <div class="col-6">
              <label class="form-label">Max Marks *</label>
              <input type="number" class="form-control" name="max_marks" value="100" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Due Date & Time *</label>
            <input type="datetime-local" class="form-control" name="due_date" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Detailed Description / Questions</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Provide problem statement, formatting guidelines, and rubric..."></textarea>
          </div>
        </div>

        <div class="modal-footer border-top">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>Publish Assignment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
