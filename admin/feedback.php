<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

require_role('admin');

$pageTitle = 'Student Feedback Inquiries';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            $pdo = get_db_connection();

            if ($action === 'respond_feedback') {
                $fbId     = (int)($_POST['feedback_id'] ?? 0);
                $status   = in_array($_POST['status'] ?? '', ['new', 'reviewed', 'resolved']) ? $_POST['status'] : 'reviewed';
                $response = trim($_POST['admin_response'] ?? '');

                if ($fbId <= 0) {
                    $error = 'Invalid feedback target.';
                } else {
                    $stmt = $pdo->prepare("UPDATE feedback SET status = ?, admin_response = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$status, $response, $fbId]);
                    set_flash('success', 'Feedback inquiry updated with administrative response.');
                    header('Location: ' . url('admin/feedback.php'));
                    exit;
                }
            } elseif ($action === 'delete_feedback') {
                $fbId = (int)($_POST['feedback_id'] ?? 0);
                if ($fbId > 0) {
                    $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
                    $stmt->execute([$fbId]);
                    set_flash('info', 'Feedback record deleted.');
                    header('Location: ' . url('admin/feedback.php'));
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log('Feedback admin error: ' . $e->getMessage());
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$statusFilter = trim($_GET['status'] ?? '');

$feedbacks = [];
try {
    $pdo = get_db_connection();
    $sql = "
        SELECT f.id, f.subject, f.category, f.message, f.status, f.admin_response, f.created_at,
               u.name as student_name, u.email as student_email, u.roll_number, u.department
        FROM feedback f
        JOIN users u ON f.student_id = u.id
        WHERE 1=1
    ";
    $params = [];

    if ($statusFilter !== '' && in_array($statusFilter, ['new', 'reviewed', 'resolved'])) {
        $sql .= " AND f.status = ?";
        $params[] = $statusFilter;
    }

    $sql .= " ORDER BY f.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $feedbacks = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Fetch feedback error: ' . $e->getMessage());
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="portal-container">
  <?php require_once dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <main class="portal-content">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
      <div>
        <h4 class="fw-bold mb-1">Student Feedback & Inquiries</h4>
        <p class="text-muted small mb-0">Review student suggestions, technical complaints, and facility requests.</p>
      </div>
      <div>
        <span class="badge bg-secondary-subtle text-secondary px-3 py-2">
          <?= count($feedbacks) ?> Total Messages
        </span>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger py-2 mb-3 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($error) ?>
      </div>
    <?php endif; ?>

    <div class="d-flex gap-2 mb-3">
      <a href="<?= url('admin/feedback.php') ?>" class="btn btn-sm <?= ($statusFilter === '') ? 'btn-primary' : 'btn-outline-secondary' ?>">
        All Inquiries
      </a>
      <a href="<?= url('admin/feedback.php?status=new') ?>" class="btn btn-sm <?= ($statusFilter === 'new') ? 'btn-warning' : 'btn-outline-warning' ?>">
        Pending / New
      </a>
      <a href="<?= url('admin/feedback.php?status=reviewed') ?>" class="btn btn-sm <?= ($statusFilter === 'reviewed') ? 'btn-info' : 'btn-outline-info' ?>">
        Reviewed
      </a>
      <a href="<?= url('admin/feedback.php?status=resolved') ?>" class="btn btn-sm <?= ($statusFilter === 'resolved') ? 'btn-success' : 'btn-outline-success' ?>">
        Resolved
      </a>
    </div>

    <div class="row g-3">
      <?php if (empty($feedbacks)): ?>
        <div class="col-12">
          <div class="card card-custom p-5 text-center text-muted">
            <i class="bi bi-chat-heart display-5 d-block mb-2"></i>
            <h6>No feedback messages found matching the selected filter.</h6>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($feedbacks as $fb): ?>
          <div class="col-12">
            <div class="card card-custom shadow-none border">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                  <div>
                    <span class="badge <?= category_badge_class($fb['category']) ?>"><?= e($fb['category']) ?></span>
                    <?php if ($fb['status'] === 'new'): ?>
                      <span class="badge bg-warning-subtle text-warning-emphasis">New / Unreviewed</span>
                    <?php elseif ($fb['status'] === 'reviewed'): ?>
                      <span class="badge bg-info-subtle text-info-emphasis">Reviewed</span>
                    <?php else: ?>
                      <span class="badge bg-success-subtle text-success">Resolved</span>
                    <?php endif; ?>
                  </div>
                  <small class="text-muted"><?= time_ago($fb['created_at']) ?></small>
                </div>

                <h6 class="fw-bold text-body mb-1"><?= e($fb['subject']) ?></h6>
                <div class="small text-muted mb-2">
                  From: <strong><?= e($fb['student_name']) ?></strong> (<code><?= e($fb['roll_number']) ?></code>)
                  &bull; <?= e($fb['department']) ?>
                </div>

                <div class="p-3 bg-body-tertiary rounded small mb-2 text-body">
                  <?= nl2br(e($fb['message'])) ?>
                </div>

                <?php if ($fb['admin_response']): ?>
                  <div class="p-2 border rounded small bg-body mb-2">
                    <span class="fw-semibold text-primary"><i class="bi bi-reply-fill me-1"></i>Response Sent:</span>
                    <div class="text-muted mt-1"><?= nl2br(e($fb['admin_response'])) ?></div>
                  </div>
                <?php endif; ?>

                <div class="d-flex justify-content-end gap-2 border-top pt-2">
                  <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#respondModal-<?= $fb['id'] ?>">
                    <i class="bi bi-reply me-1"></i><?= $fb['admin_response'] ? 'Edit Response' : 'Reply & Update Status' ?>
                  </button>

                  <form method="POST" action="<?= url('admin/feedback.php') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_feedback">
                    <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Do you wish to delete this feedback message?">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>

              </div>
            </div>
          </div>

          <div class="modal fade" id="respondModal-<?= $fb['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content card-custom">
                <form method="POST" action="<?= url('admin/feedback.php') ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="respond_feedback">
                  <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">

                  <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Address Inquiry: <?= e($fb['student_name']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>

                  <div class="modal-body">
                    <div class="mb-3">
                      <label class="form-label">Update Status *</label>
                      <select class="form-select" name="status">
                        <option value="new" <?= ($fb['status'] === 'new') ? 'selected' : '' ?>>New</option>
                        <option value="reviewed" <?= ($fb['status'] === 'reviewed') ? 'selected' : '' ?>>Reviewed</option>
                        <option value="resolved" <?= ($fb['status'] === 'resolved') ? 'selected' : '' ?>>Resolved</option>
                      </select>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Official Response Note</label>
                      <textarea class="form-control" name="admin_response" rows="4" placeholder="Enter remarks, action taken, or explanation for the student..."><?= e($fb['admin_response'] ?? '') ?></textarea>
                      <div class="form-text small">This response will be visible on the student's portal.</div>
                    </div>
                  </div>

                  <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Response</button>
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
