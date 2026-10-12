<?php
/**
 * CampusConnect - Study Resource Library Administration (CRUD)
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

require_role('admin');

$adminId = (int)$_SESSION['user_id'];
$pageTitle = 'Manage Study Resources';
$error = '';

// Handle actions (Upload, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            $pdo = get_db_connection();

            if ($action === 'upload_resource') {
                $title       = trim($_POST['title'] ?? '');
                $subject     = trim($_POST['subject'] ?? '');
                $description = trim($_POST['description'] ?? '');

                if (empty($title) || empty($subject)) {
                    $error = 'Title and subject are required.';
                } elseif (empty($_FILES['resource_file']['name']) || $_FILES['resource_file']['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Please select a valid document file to upload.';
                } else {
                    $file = $_FILES['resource_file'];
                    $maxBytes = 15 * 1024 * 1024; // 15MB

                    if ($file['size'] > $maxBytes) {
                        $error = 'The uploaded file exceeds the 15MB limit.';
                    } else {
                        $allowedExts = ['pdf', 'docx', 'pptx', 'txt', 'zip', 'rar'];
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                        if (!in_array($ext, $allowedExts)) {
                            $error = 'Extension .' . htmlspecialchars($ext, ENT_QUOTES, 'UTF-8') . ' is not permitted. Only PDF, DOCX, PPTX, TXT, and ZIP are allowed.';
                        } else {
                            $uploadDir = dirname(__DIR__) . '/uploads/resources';
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }

                            $uniqueName = 'res_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                            $destination = $uploadDir . '/' . $uniqueName;

                            if (move_uploaded_file($file['tmp_name'], $destination)) {
                                $relativePath = 'uploads/resources/' . $uniqueName;
                                $mime = mime_content_type($destination) ?: 'application/octet-stream';
                                $fileSize = (int)$file['size'];
                                $origName = basename($file['name']);

                                $stmt = $pdo->prepare("
                                    INSERT INTO resources (title, subject, description, file_path, file_name, file_size, file_type, uploaded_by, created_at)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                                ");
                                $stmt->execute([$title, $subject, $description, $relativePath, $origName, $fileSize, $mime, $adminId]);

                                set_flash('success', 'Resource file "' . $origName . '" uploaded successfully.');
                                header('Location: ' . url('admin/resources.php'));
                                exit;
                            } else {
                                $error = 'Failed to move uploaded file to campus storage.';
                            }
                        }
                    }
                }
            } elseif ($action === 'update_resource') {
                $resId       = (int)($_POST['resource_id'] ?? 0);
                $title       = trim($_POST['title'] ?? '');
                $subject     = trim($_POST['subject'] ?? '');
                $description = trim($_POST['description'] ?? '');

                if ($resId <= 0 || empty($title) || empty($subject)) {
                    $error = 'Title and subject are required.';
                } else {
                    $stmt = $pdo->prepare("UPDATE resources SET title = ?, subject = ?, description = ? WHERE id = ?");
                    $stmt->execute([$title, $subject, $description, $resId]);
                    set_flash('success', 'Resource details updated successfully.');
                    header('Location: ' . url('admin/resources.php'));
                    exit;
                }
            } elseif ($action === 'delete_resource') {
                $resId = (int)($_POST['resource_id'] ?? 0);
                if ($resId > 0) {
                    $stmtSelect = $pdo->prepare("SELECT file_path FROM resources WHERE id = ?");
                    $stmtSelect->execute([$resId]);
                    $res = $stmtSelect->fetch();

                    if ($res) {
                        // Delete file on disk if exists
                        $diskPath = dirname(__DIR__) . '/' . ltrim($res['file_path'], '/');
                        if (file_exists($diskPath)) {
                            @unlink($diskPath);
                        }

                        $stmtDel = $pdo->prepare("DELETE FROM resources WHERE id = ?");
                        $stmtDel->execute([$resId]);
                        set_flash('info', 'Resource removed from library.');
                        header('Location: ' . url('admin/resources.php'));
                        exit;
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Resource admin error: ' . $e->getMessage());
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch all resources
$resources = [];
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT id, title, subject, description, file_path, file_name, file_size, file_type, created_at FROM resources ORDER BY created_at DESC");
    $resources = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Fetch resources error: ' . $e->getMessage());
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="portal-container">
  <?php require_once dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <main class="portal-content">
    
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
      <div>
        <h4 class="fw-bold mb-1">Study Resource Management</h4>
        <p class="text-muted small mb-0">Upload syllabus notes, reference guides, and code cheat sheets for student access.</p>
      </div>
      <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadResourceModal">
          <i class="bi bi-cloud-upload me-1"></i>Upload New Material
        </button>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger py-2 mb-3 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($error) ?>
      </div>
    <?php endif; ?>

    <!-- Resources Table -->
    <div class="card card-custom shadow-none border">
      <div class="card-header-clean">
        <span class="text-body"><i class="bi bi-folder-fill text-primary me-2"></i>Resource Catalog</span>
        <span class="badge bg-secondary-subtle text-secondary"><?= count($resources) ?> Documents</span>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-custom mb-0 align-middle">
            <thead>
              <tr>
                <th>Document Details</th>
                <th>Subject</th>
                <th>Size</th>
                <th>Uploaded</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($resources)): ?>
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">No study materials found in database.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($resources as $res): ?>
                  <tr>
                    <td style="max-width: 300px;">
                      <div class="fw-bold text-body text-truncate"><?= e($res['title']) ?></div>
                      <small class="text-muted d-block text-truncate"><?= e($res['file_name']) ?></small>
                      <?php if ($res['description']): ?>
                        <small class="text-muted d-block text-truncate" style="font-size: 0.78rem;"><?= e($res['description']) ?></small>
                      <?php endif; ?>
                    </td>
                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= e($res['subject']) ?></span></td>
                    <td><small class="text-muted"><?= format_bytes((int)$res['file_size']) ?></small></td>
                    <td><small class="text-muted"><?= time_ago($res['created_at']) ?></small></td>
                    <td class="text-end">
                      <div class="d-inline-flex gap-1">
                        <a href="<?= url('download.php?id=' . $res['id']) ?>" class="btn btn-sm btn-outline-primary" title="Download">
                          <i class="bi bi-download"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editResModal-<?= $res['id'] ?>">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="<?= url('admin/resources.php') ?>" class="d-inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="delete_resource">
                          <input type="hidden" name="resource_id" value="<?= $res['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Are you sure you want to permanently delete '<?= e($res['title']) ?>'?">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>

                  <!-- Edit Resource Modal -->
                  <div class="modal fade" id="editResModal-<?= $res['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content card-custom">
                        <form method="POST" action="<?= url('admin/resources.php') ?>">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="update_resource">
                          <input type="hidden" name="resource_id" value="<?= $res['id'] ?>">

                          <div class="modal-header border-bottom">
                            <h5 class="modal-title fw-bold">Edit Resource Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>

                          <div class="modal-body">
                            <div class="mb-3">
                              <label class="form-label">Title *</label>
                              <input type="text" class="form-control" name="title" value="<?= e($res['title']) ?>" required>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">Subject *</label>
                              <input type="text" class="form-control" name="subject" value="<?= e($res['subject']) ?>" required>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">Description</label>
                              <textarea class="form-control" name="description" rows="3"><?= e($res['description']) ?></textarea>
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

<!-- Upload Resource Modal -->
<div class="modal fade" id="uploadResourceModal" tabindex="-1" aria-labelledby="uploadResourceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card-custom">
      <form method="POST" action="<?= url('admin/resources.php') ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload_resource">

        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold" id="uploadResourceModalLabel">Upload Study Material</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Resource Title *</label>
            <input type="text" class="form-control" name="title" required placeholder="e.g. PHP 8 PDO Architecture Slides">
          </div>

          <div class="mb-3">
            <label class="form-label">Academic Subject / Course *</label>
            <input type="text" class="form-control" name="subject" required placeholder="e.g. Web Technology">
          </div>

          <div class="mb-3">
            <label class="form-label">Select File *</label>
            <input type="file" class="form-control" name="resource_file" required>
            <div class="form-text small">Accepted formats: PDF, DOCX, PPTX, TXT, ZIP (Max 15MB).</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Description (Optional)</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Provide syllabus context, module reference, or lecture summary..."></textarea>
          </div>
        </div>

        <div class="modal-footer border-top">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-cloud-upload me-1"></i>Upload Material
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
