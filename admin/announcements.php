<?php
/**
 * CampusConnect - Announcements Administration (CRUD)
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

require_role('admin');

$adminId = (int)$_SESSION['user_id'];
$pageTitle = 'Manage Announcements';
$error = '';

// Handle actions (Create, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            $pdo = get_db_connection();

            if ($action === 'create_announcement') {
                $title    = trim($_POST['title'] ?? '');
                $category = trim($_POST['category'] ?? 'General');
                $content  = trim($_POST['content'] ?? '');
                $isPinned = isset($_POST['is_pinned']) ? 1 : 0;

                if (empty($title) || empty($content)) {
                    $error = 'Both announcement title and content are required.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO announcements (title, content, category, is_pinned, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$title, $content, $category, $isPinned, $adminId]);
                    set_flash('success', 'Announcement published successfully.');
                    header('Location: ' . url('admin/announcements.php'));
                    exit;
                }
            } elseif ($action === 'update_announcement') {
                $annId    = (int)($_POST['announcement_id'] ?? 0);
                $title    = trim($_POST['title'] ?? '');
                $category = trim($_POST['category'] ?? 'General');
                $content  = trim($_POST['content'] ?? '');
                $isPinned = isset($_POST['is_pinned']) ? 1 : 0;

                if ($annId <= 0 || empty($title) || empty($content)) {
                    $error = 'All fields are required.';
                } else {
                    $stmt = $pdo->prepare("UPDATE announcements SET title = ?, category = ?, content = ?, is_pinned = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$title, $category, $content, $isPinned, $annId]);
                    set_flash('success', 'Announcement updated successfully.');
                    header('Location: ' . url('admin/announcements.php'));
                    exit;
                }
            } elseif ($action === 'delete_announcement') {
                $annId = (int)($_POST['announcement_id'] ?? 0);
                if ($annId > 0) {
                    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
                    $stmt->execute([$annId]);
                    set_flash('info', 'Announcement deleted.');
                    header('Location: ' . url('admin/announcements.php'));
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log('Announcement admin error: ' . $e->getMessage());
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch announcements
$announcements = [];
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("
        SELECT a.id, a.title, a.content, a.category, a.is_pinned, a.created_at, u.name as author_name
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.id
        ORDER BY a.is_pinned DESC, a.created_at DESC
    ");
    $announcements = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Fetch announcements error: ' . $e->getMessage());
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="portal-container">
  <?php require_once dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <main class="portal-content">
    
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
      <div>
        <h4 class="fw-bold mb-1">Campus Announcements & Circulars</h4>
        <p class="text-muted small mb-0">Publish academic notices, examination alerts, and administrative circulars.</p>
      </div>
      <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAnnModal">
          <i class="bi bi-megaphone me-1"></i>Publish Announcement
        </button>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger py-2 mb-3 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($error) ?>
      </div>
    <?php endif; ?>

    <!-- Announcements Roster -->
    <div class="card card-custom shadow-none border">
      <div class="card-header-clean">
        <span class="text-body"><i class="bi bi-bell-fill text-primary me-2"></i>Published Notices</span>
        <span class="badge bg-secondary-subtle text-secondary"><?= count($announcements) ?> Total</span>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-custom mb-0 align-middle">
            <thead>
              <tr>
                <th>Title & Category</th>
                <th>Content Summary</th>
                <th>Author</th>
                <th>Published</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($announcements)): ?>
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">No announcements published yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                  <tr>
                    <td style="max-width: 250px;">
                      <div class="d-flex align-items-center gap-1 mb-1">
                        <span class="badge <?= category_badge_class($ann['category']) ?>"><?= e($ann['category']) ?></span>
                        <?php if ($ann['is_pinned']): ?>
                          <span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-pin-fill"></i> Pinned</span>
                        <?php endif; ?>
                      </div>
                      <div class="fw-bold text-body text-truncate"><?= e($ann['title']) ?></div>
                    </td>
                    <td style="max-width: 320px;">
                      <p class="text-muted small mb-0 text-truncate"><?= e($ann['content']) ?></p>
                    </td>
                    <td><small class="text-muted"><?= e($ann['author_name'] ?: 'Staff') ?></small></td>
                    <td><small class="text-muted"><?= time_ago($ann['created_at']) ?></small></td>
                    <td class="text-end">
                      <div class="d-inline-flex gap-1">
                        <!-- Edit Button -->
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal-<?= $ann['id'] ?>">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <!-- Delete Form -->
                        <form method="POST" action="<?= url('admin/announcements.php') ?>" class="d-inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="delete_announcement">
                          <input type="hidden" name="announcement_id" value="<?= $ann['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Are you sure you want to delete announcement '<?= e($ann['title']) ?>'?">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>

                  <!-- Edit Modal -->
                  <div class="modal fade" id="editModal-<?= $ann['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content card-custom">
                        <form method="POST" action="<?= url('admin/announcements.php') ?>">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="update_announcement">
                          <input type="hidden" name="announcement_id" value="<?= $ann['id'] ?>">

                          <div class="modal-header border-bottom">
                            <h5 class="modal-title fw-bold">Edit Announcement</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>

                          <div class="modal-body">
                            <div class="mb-3">
                              <label class="form-label">Title *</label>
                              <input type="text" class="form-control" name="title" value="<?= e($ann['title']) ?>" required>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">Category *</label>
                              <select class="form-select" name="category">
                                <option value="Academic" <?= ($ann['category'] === 'Academic') ? 'selected' : '' ?>>Academic</option>
                                <option value="Events" <?= ($ann['category'] === 'Events') ? 'selected' : '' ?>>Events</option>
                                <option value="Facilities" <?= ($ann['category'] === 'Facilities') ? 'selected' : '' ?>>Facilities</option>
                                <option value="General" <?= ($ann['category'] === 'General') ? 'selected' : '' ?>>General</option>
                              </select>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">Content *</label>
                              <textarea class="form-control" name="content" rows="4" required><?= e($ann['content']) ?></textarea>
                            </div>

                            <div class="form-check">
                              <input class="form-check-input" type="checkbox" name="is_pinned" id="pin-<?= $ann['id'] ?>" value="1" <?= $ann['is_pinned'] ? 'checked' : '' ?>>
                              <label class="form-check-label" for="pin-<?= $ann['id'] ?>">Pin notice to top of notice boards</label>
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

<!-- Create Announcement Modal -->
<div class="modal fade" id="createAnnModal" tabindex="-1" aria-labelledby="createAnnModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card-custom">
      <form method="POST" action="<?= url('admin/announcements.php') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_announcement">

        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold" id="createAnnModalLabel">Create Announcement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Announcement Title *</label>
            <input type="text" class="form-control" name="title" required placeholder="e.g. Midterm Examination Timetable">
          </div>

          <div class="mb-3">
            <label class="form-label">Category *</label>
            <select class="form-select" name="category" required>
              <option value="Academic">Academic</option>
              <option value="Events">Campus Events</option>
              <option value="Facilities">Campus Facilities</option>
              <option value="General" selected>General Notice</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Detailed Content *</label>
            <textarea class="form-control" name="content" rows="4" required placeholder="Write the announcement text..."></textarea>
          </div>

          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_pinned" id="create_is_pinned" value="1">
            <label class="form-check-label" for="create_is_pinned">Pin to top of public and student notice boards</label>
          </div>
        </div>

        <div class="modal-footer border-top">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-send me-1"></i>Publish Notice
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
