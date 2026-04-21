<?php
/**
 * CampusConnect - Student Feedback & Grievance Submission
 * Submit issues or suggestions with live character counters and track administrative review status.
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

require_role('student');

$studentId = (int)$_SESSION['user_id'];
$pageTitle = 'Student Feedback';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $subject = trim($_POST['subject'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $message = trim($_POST['message'] ?? '');

        if (empty($subject) || empty($message)) {
            $error = 'Subject line and detailed message are both required.';
        } elseif (strlen($message) < 10) {
            $error = 'Message must be at least 10 characters in length.';
        } elseif (strlen($message) > 1000) {
            $error = 'Message exceeds the 1,000 character maximum.';
        } else {
            try {
                $pdo = get_db_connection();
                $stmt = $pdo->prepare("INSERT INTO feedback (student_id, subject, category, message, status, created_at) VALUES (?, ?, ?, ?, 'new', NOW())");
                $stmt->execute([$studentId, $subject, $category, $message]);

                set_flash('success', 'Your feedback has been submitted to campus administration. Thank you!');
                header('Location: ' . url('student/feedback.php'));
                exit;

            } catch (Exception $e) {
                error_log('Feedback submission error: ' . $e->getMessage());
                $error = 'Database error while saving feedback: ' . $e->getMessage();
            }
        }
    }
}

// Fetch student's prior feedback submissions
$pastFeedback = [];
try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT id, subject, category, message, status, admin_response, created_at FROM feedback WHERE student_id = ? ORDER BY created_at DESC");
    $stmt->execute([$studentId]);
    $pastFeedback = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Fetch feedback error: ' . $e->getMessage());
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="portal-container">
  <?php require_once dirname(__DIR__) . '/includes/student_sidebar.php'; ?>

  <main class="portal-content">
    
    <div class="mb-4">
      <h4 class="fw-bold mb-1">Student Feedback & Inquiries</h4>
      <p class="text-muted small mb-0">Share suggestions, academic feedback, or facility requests directly with department leadership.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger py-2 mb-3 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($error) ?>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      
      <!-- Submission Form -->
      <div class="col-12 col-lg-5">
        <div class="card card-custom p-4 shadow-none border">
          <h5 class="fw-bold mb-3"><i class="bi bi-pencil-square text-primary me-2"></i>New Feedback Message</h5>
          
          <form method="POST" action="<?= url('student/feedback.php') ?>" class="needs-validation" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
              <label for="fb_subject" class="form-label">Subject Line *</label>
              <input type="text" class="form-control" id="fb_subject" name="subject" placeholder="e.g. Lab 2 Monitor Glitch" required>
            </div>

            <div class="mb-3">
              <label for="fb_category" class="form-label">Category *</label>
              <select class="form-select" id="fb_category" name="category" required>
                <option value="Academics">Academics & Curriculum</option>
                <option value="Facilities">Campus Facilities & IT Labs</option>
                <option value="Library">Central Library</option>
                <option value="Student Life">Student Life & Societies</option>
                <option value="General" selected>General Inquiry</option>
              </select>
            </div>

            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="fb_message" class="form-label mb-0">Detailed Message *</label>
                <span class="small text-muted" id="fb-char-counter">0 / 1000 characters</span>
              </div>
              <textarea 
                class="form-control" 
                id="fb_message" 
                name="message" 
                rows="5" 
                maxlength="1000" 
                data-char-count="fb-char-counter"
                placeholder="Explain the suggestion, constructive concern, or inquiry clearly..." 
                required
              ></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">
              <i class="bi bi-send me-1"></i>Submit Feedback
            </button>
          </form>
        </div>
      </div>

      <!-- Past Submissions History -->
      <div class="col-12 col-lg-7">
        <div class="card card-custom h-100 shadow-none border">
          <div class="card-header-clean">
            <span class="text-body"><i class="bi bi-clock-history text-primary me-2"></i>My Submission History</span>
            <span class="badge bg-secondary-subtle text-secondary"><?= count($pastFeedback) ?> Submitted</span>
          </div>

          <div class="card-body p-3">
            <?php if (empty($pastFeedback)): ?>
              <div class="text-center py-5 text-muted small">
                <i class="bi bi-chat-square-dots display-6 d-block mb-2"></i>
                You have not submitted any feedback entries yet.
              </div>
            <?php else: ?>
              <div class="d-flex flex-column gap-3">
                <?php foreach ($pastFeedback as $fb): ?>
                  <div class="p-3 border rounded bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="badge <?= category_badge_class($fb['category']) ?>"><?= e($fb['category']) ?></span>
                      <small class="text-muted"><?= time_ago($fb['created_at']) ?></small>
                    </div>

                    <h6 class="fw-bold mb-1 text-body"><?= e($fb['subject']) ?></h6>
                    <p class="text-muted small mb-2"><?= nl2br(e($fb['message'])) ?></p>

                    <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                      <span class="small">
                        <strong>Status:</strong>
                        <?php if ($fb['status'] === 'resolved'): ?>
                          <span class="badge bg-success-subtle text-success">Resolved</span>
                        <?php elseif ($fb['status'] === 'reviewed'): ?>
                          <span class="badge bg-info-subtle text-info-emphasis">Under Review</span>
                        <?php else: ?>
                          <span class="badge bg-warning-subtle text-warning-emphasis">Pending Review</span>
                        <?php endif; ?>
                      </span>
                    </div>

                    <?php if ($fb['admin_response']): ?>
                      <div class="mt-2 p-2 bg-body border rounded small">
                        <span class="text-primary fw-semibold"><i class="bi bi-reply-fill me-1"></i>Administrator Response:</span>
                        <div class="text-body mt-1"><?= nl2br(e($fb['admin_response'])) ?></div>
                      </div>
                    <?php endif; ?>
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
