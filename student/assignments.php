<?php
/**
 * CampusConnect - Student Assignments & Submissions
 * Displays deadlines, submission status, grade evaluations, and handles assignment upload/submission.
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

require_role('student');

$studentId = (int)$_SESSION['user_id'];
$pageTitle = 'My Course Assignments';
$error = '';
$success = '';

// Handle assignment submission POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_assignment') {
    if (!validate_csrf()) {
        $error = 'Session security token expired. Please reload and try again.';
    } else {
        $assignmentId = (int)($_POST['assignment_id'] ?? 0);
        $notes = trim($_POST['submission_notes'] ?? '');

        if ($assignmentId <= 0) {
            $error = 'Invalid assignment target.';
        } else {
            try {
                $pdo = get_db_connection();

                // Verify assignment exists
                $stmtCheck = $pdo->prepare("SELECT id, title, due_date FROM assignments WHERE id = ?");
                $stmtCheck->execute([$assignmentId]);
                $asg = $stmtCheck->fetch();

                if (!$asg) {
                    $error = 'Selected assignment was not found.';
                } else {
                    $uploadedFilePath = null;
                    $uploadedFileName = null;

                    // Handle optional file upload
                    if (!empty($_FILES['submission_file']['name']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['submission_file'];
                        $maxBytes = 10 * 1024 * 1024; // 10MB

                        if ($file['size'] > $maxBytes) {
                            $error = 'File exceeds the maximum permitted size of 10MB.';
                        } else {
                            $allowedExts = ['pdf', 'docx', 'txt', 'zip', 'rar', 'cpp', 'java', 'py'];
                            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                            if (!in_array($ext, $allowedExts)) {
                                $error = 'File type .' . htmlspecialchars($ext, ENT_QUOTES, 'UTF-8') . ' is not permitted. Please upload a PDF, DOCX, TXT, or ZIP archive.';
                            } else {
                                $uploadDir = dirname(__DIR__) . '/uploads/submissions';
                                if (!is_dir($uploadDir)) {
                                    mkdir($uploadDir, 0755, true);
                                }

                                $uniqueName = 'sub_' . $assignmentId . '_' . $studentId . '_' . time() . '.' . $ext;
                                $targetDest = $uploadDir . '/' . $uniqueName;

                                if (move_uploaded_file($file['tmp_name'], $targetDest)) {
                                    $uploadedFilePath = 'uploads/submissions/' . $uniqueName;
                                    $uploadedFileName = basename($file['name']);
                                } else {
                                    $error = 'Server failed to save uploaded file.';
                                }
                            }
                        }
                    }

                    if (empty($error)) {
                        // Check if previous submission exists
                        $stmtSubCheck = $pdo->prepare("SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
                        $stmtSubCheck->execute([$assignmentId, $studentId]);
                        $prevSub = $stmtSubCheck->fetch();

                        if ($prevSub) {
                            // Update existing submission
                            $sql = "UPDATE assignment_submissions SET submission_notes = ?, status = 'submitted', submitted_at = NOW()";
                            $params = [$notes];

                            if ($uploadedFilePath) {
                                $sql .= ", file_path = ?, file_name = ?";
                                $params[] = $uploadedFilePath;
                                $params[] = $uploadedFileName;
                            }
                            $sql .= " WHERE id = ?";
                            $params[] = $prevSub['id'];

                            $updateStmt = $pdo->prepare($sql);
                            $updateStmt->execute($params);
                            set_flash('success', 'Your submission for "' . $asg['title'] . '" has been updated.');
                        } else {
                            // Insert new submission
                            $insertStmt = $pdo->prepare("
                                INSERT INTO assignment_submissions (assignment_id, student_id, file_path, file_name, submission_notes, status, submitted_at)
                                VALUES (?, ?, ?, ?, ?, 'submitted', NOW())
                            ");
                            $insertStmt->execute([$assignmentId, $studentId, $uploadedFilePath, $uploadedFileName, $notes]);
                            set_flash('success', 'Assignment "' . $asg['title'] . '" submitted successfully!');
                        }

                        header('Location: ' . url('student/assignments.php'));
                        exit;
                    }
                }
            } catch (Exception $e) {
                error_log('Submission error: ' . $e->getMessage());
                $error = 'Database error occurred during submission: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all assignments with student's submission status
$assignments = [];
try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.description, a.subject, a.due_date, a.max_marks,
               s.id as submission_id, s.file_name as submitted_file_name, s.submission_notes,
               s.status as submission_status, s.grade, s.feedback, s.submitted_at
        FROM assignments a
        LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
        ORDER BY a.due_date ASC
    ");
    $stmt->execute([$studentId]);
    $assignments = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Fetch assignments error: ' . $e->getMessage());
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="portal-container">
  <?php require_once dirname(__DIR__) . '/includes/student_sidebar.php'; ?>

  <main class="portal-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold mb-1">Course Assignments & Tasks</h4>
        <p class="text-muted small mb-0">Review submission deadlines, upload solutions, and inspect faculty feedback.</p>
      </div>
      <span class="badge bg-secondary-subtle text-secondary px-3 py-2">
        <?= count($assignments) ?> Total Assignments
      </span>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger py-2 mb-3 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($error) ?>
      </div>
    <?php endif; ?>

    <!-- Assignments List -->
    <div class="row g-3">
      <?php if (empty($assignments)): ?>
        <div class="col-12">
          <div class="card card-custom p-5 text-center text-muted">
            <i class="bi bi-journal-check display-5 d-block mb-2"></i>
            <h6>No assignments found.</h6>
            <small>All caught up! Check back when your instructors post new tasks.</small>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($assignments as $asg): ?>
          <?php 
            $isOverdue = strtotime($asg['due_date']) < time();
            $status = $asg['submission_status'] ?? 'pending';
          ?>
          <div class="col-12">
            <div class="card card-custom shadow-none border">
              <div class="card-body p-4">
                <div class="row g-3 align-items-center">
                  
                  <div class="col-12 col-md-7">
                    <div class="d-flex align-items-center gap-2 mb-1">
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= e($asg['subject']) ?></span>
                      <?php if ($status === 'graded'): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                          <i class="bi bi-check-all me-1"></i>Graded: <?= e($asg['grade']) ?>
                        </span>
                      <?php elseif ($status === 'submitted'): ?>
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                          <i class="bi bi-check2 me-1"></i>Submitted
                        </span>
                      <?php else: ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                          <i class="bi bi-hourglass-split me-1"></i>Pending Submission
                        </span>
                      <?php endif; ?>

                      <?php if ($isOverdue && $status === 'pending'): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Overdue</span>
                      <?php endif; ?>
                    </div>

                    <h5 class="fw-bold text-body mb-2"><?= e($asg['title']) ?></h5>
                    <p class="text-muted small mb-2"><?= nl2br(e($asg['description'])) ?></p>

                    <div class="d-flex gap-3 text-muted small flex-wrap">
                      <span><i class="bi bi-calendar-event me-1"></i><strong>Due:</strong> <?= format_date($asg['due_date'], 'M d, Y h:i A') ?></span>
                      <span><i class="bi bi-award me-1"></i><strong>Max Marks:</strong> <?= e($asg['max_marks']) ?></span>
                      <?php if ($asg['submitted_at']): ?>
                        <span><i class="bi bi-clock-history me-1"></i><strong>Submitted:</strong> <?= time_ago($asg['submitted_at']) ?></span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="col-12 col-md-5 text-md-end border-top border-md-top-0 pt-3 pt-md-0">
                    <?php if ($status === 'graded'): ?>
                      <div class="p-2 border rounded bg-body-tertiary text-start mb-2 small">
                        <strong>Faculty Evaluation:</strong>
                        <div class="text-success fw-bold">Score: <?= e($asg['grade']) ?> / <?= e($asg['max_marks']) ?></div>
                        <?php if ($asg['feedback']): ?>
                          <div class="text-muted mt-1 fst-italic">"<?= e($asg['feedback']) ?>"</div>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>

                    <button 
                      type="button" 
                      class="btn btn-primary" 
                      data-bs-toggle="modal" 
                      data-bs-target="#submitModal-<?= $asg['id'] ?>"
                    >
                      <i class="bi <?= ($status === 'pending') ? 'bi-cloud-arrow-up' : 'bi-pencil-square' ?> me-1"></i>
                      <?= ($status === 'pending') ? 'Submit Assignment' : 'Update Submission' ?>
                    </button>
                  </div>

                </div>
              </div>
            </div>
          </div>

          <!-- Submission Modal -->
          <div class="modal fade" id="submitModal-<?= $asg['id'] ?>" tabindex="-1" aria-labelledby="modalLabel-<?= $asg['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content card-custom">
                <form method="POST" action="<?= url('student/assignments.php') ?>" enctype="multipart/form-data">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="submit_assignment">
                  <input type="hidden" name="assignment_id" value="<?= $asg['id'] ?>">

                  <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalLabel-<?= $asg['id'] ?>">
                      Submit: <?= e($asg['title']) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>

                  <div class="modal-body">
                    <div class="mb-3">
                      <label class="form-label">Submission Notes / Written Answer</label>
                      <textarea class="form-control" name="submission_notes" rows="4" placeholder="Enter answers, references, or GitHub repository links..."><?= e($asg['submission_notes'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Upload Solution File (Optional)</label>
                      <input type="file" class="form-control" name="submission_file">
                      <div class="form-text small">Allowed formats: PDF, DOCX, TXT, ZIP, CPP (Max 10MB).</div>
                      <?php if ($asg['submitted_file_name']): ?>
                        <div class="small text-muted mt-1">
                          Current uploaded file: <code><?= e($asg['submitted_file_name']) ?></code>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-cloud-arrow-up me-1"></i>Confirm Submission
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </main>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
