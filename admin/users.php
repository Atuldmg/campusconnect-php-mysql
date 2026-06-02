<?php
/**
 * CampusConnect - User & Student Administration
 * View, filter, provision, update, and manage student and administrator accounts.
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

require_role('admin');

$currentAdminId = (int)$_SESSION['user_id'];
$pageTitle = 'Manage Users';
$error = '';

// Handle actions (Create, Update Role, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            $pdo = get_db_connection();

            if ($action === 'create_user') {
                $name        = trim($_POST['name'] ?? '');
                $email       = trim($_POST['email'] ?? '');
                $password    = $_POST['password'] ?? '';
                $role        = in_array($_POST['role'] ?? '', ['student', 'admin']) ? $_POST['role'] : 'student';
                $department  = trim($_POST['department'] ?? '');
                $rollNumber  = trim($_POST['roll_number'] ?? '');
                $phone       = trim($_POST['phone'] ?? '');

                if (empty($name) || empty($email) || empty($password)) {
                    $error = 'Name, email, and password are required.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Invalid email address provided.';
                } elseif (strlen($password) < 8) {
                    $error = 'Password must be at least 8 characters.';
                } else {
                    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmtCheck->execute([$email]);
                    if ($stmtCheck->fetch()) {
                        $error = 'A user with this email address already exists.';
                    } else {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmtIns = $pdo->prepare("
                            INSERT INTO users (name, email, password_hash, role, department, roll_number, phone, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmtIns->execute([$name, $email, $hash, $role, $department, $rollNumber, $phone]);
                        set_flash('success', 'User account for "' . $name . '" created successfully.');
                        header('Location: ' . url('admin/users.php'));
                        exit;
                    }
                }
            } elseif ($action === 'delete_user') {
                $targetId = (int)($_POST['user_id'] ?? 0);

                if ($targetId === $currentAdminId) {
                    $error = 'Security safeguard: You cannot delete your own active administrator account.';
                } elseif ($targetId <= 0) {
                    $error = 'Invalid user identifier.';
                } else {
                    $stmtDel = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmtDel->execute([$targetId]);
                    set_flash('info', 'User record deleted successfully.');
                    header('Location: ' . url('admin/users.php'));
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log('User admin error: ' . $e->getMessage());
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch users with filters
$roleFilter = trim($_GET['role'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');

$users = [];
try {
    $pdo = get_db_connection();
    $sql = "SELECT id, name, email, role, department, roll_number, phone, created_at FROM users WHERE 1=1";
    $params = [];

    if ($roleFilter !== '' && in_array($roleFilter, ['student', 'admin'])) {
        $sql .= " AND role = ?";
        $params[] = $roleFilter;
    }

    if ($searchQuery !== '') {
        $sql .= " AND (name LIKE ? OR email LIKE ? OR roll_number LIKE ?)";
        $params[] = '%' . $searchQuery . '%';
        $params[] = '%' . $searchQuery . '%';
        $params[] = '%' . $searchQuery . '%';
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Fetch users error: ' . $e->getMessage());
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="portal-container">
  <?php require_once dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <main class="portal-content">
    
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
      <div>
        <h4 class="fw-bold mb-1">User & Student Management</h4>
        <p class="text-muted small mb-0">Browse and manage student profiles, staff accounts, and role permissions.</p>
      </div>
      <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
          <i class="bi bi-person-plus me-1"></i>Add New User
        </button>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger py-2 mb-3 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($error) ?>
      </div>
    <?php endif; ?>

    <!-- Search & Filter Filterbar -->
    <div class="card card-custom p-3 mb-4 shadow-none border">
      <form method="GET" action="<?= url('admin/users.php') ?>" class="row g-2 align-items-center">
        <div class="col-12 col-md-7">
          <div class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control search-input" name="q" value="<?= e($searchQuery) ?>" placeholder="Search by name, email, or roll number...">
          </div>
        </div>

        <div class="col-12 col-md-3">
          <select class="form-select" name="role">
            <option value="">All Roles</option>
            <option value="student" <?= ($roleFilter === 'student') ? 'selected' : '' ?>>Students Only</option>
            <option value="admin" <?= ($roleFilter === 'admin') ? 'selected' : '' ?>>Admins Only</option>
          </select>
        </div>

        <div class="col-12 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
          <?php if ($searchQuery !== '' || $roleFilter !== ''): ?>
            <a href="<?= url('admin/users.php') ?>" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Users Table -->
    <div class="card card-custom shadow-none border">
      <div class="card-header-clean">
        <span class="text-body"><i class="bi bi-people-fill text-primary me-2"></i>Accounts Roster</span>
        <span class="badge bg-secondary-subtle text-secondary"><?= count($users) ?> Total</span>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-custom mb-0 align-middle">
            <thead>
              <tr>
                <th>User Details</th>
                <th>Role</th>
                <th>Department</th>
                <th>Roll No</th>
                <th>Joined</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($users)): ?>
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">No user accounts matched the filter criteria.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($users as $u): ?>
                  <tr>
                    <td>
                      <div class="fw-bold text-body"><?= e($u['name']) ?></div>
                      <small class="text-muted"><?= e($u['email']) ?></small>
                      <?php if ($u['phone']): ?>
                        <small class="text-muted d-block">&bull; <?= e($u['phone']) ?></small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($u['role'] === 'admin'): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Admin</span>
                      <?php else: ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Student</span>
                      <?php endif; ?>
                    </td>
                    <td><span class="badge bg-secondary-subtle text-secondary"><?= e($u['department'] ?: 'General') ?></span></td>
                    <td><code><?= e($u['roll_number'] ?: '—') ?></code></td>
                    <td><small class="text-muted"><?= format_date($u['created_at']) ?></small></td>
                    <td class="text-end">
                      <?php if ($u['id'] !== $currentAdminId): ?>
                        <form method="POST" action="<?= url('admin/users.php') ?>" class="d-inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="delete_user">
                          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Are you sure you want to delete user account '<?= e($u['name']) ?>'?">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      <?php else: ?>
                        <span class="badge bg-light text-muted border">Active Session</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card-custom">
      <form method="POST" action="<?= url('admin/users.php') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_user">

        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold" id="createUserModalLabel">Add New Campus User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Full Name *</label>
            <input type="text" class="form-control" name="name" required placeholder="e.g. Priya Patel">
          </div>

          <div class="mb-3">
            <label class="form-label">Email Address *</label>
            <input type="email" class="form-control" name="email" required placeholder="e.g. user@campusconnect.edu">
          </div>

          <div class="mb-3">
            <label class="form-label">Temporary Password *</label>
            <input type="password" class="form-control" name="password" minlength="8" required placeholder="Min 8 characters">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Role *</label>
              <select class="form-select" name="role" required>
                <option value="student" selected>Student</option>
                <option value="admin">Administrator</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Roll Number</label>
              <input type="text" class="form-control" name="roll_number" placeholder="e.g. CC-2024-BCA-010">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Department</label>
            <input type="text" class="form-control" name="department" placeholder="e.g. Computer Science (BCA)">
          </div>

          <div class="mb-3">
            <label class="form-label">Phone Contact</label>
            <input type="tel" class="form-control" name="phone" placeholder="+91 ...">
          </div>
        </div>

        <div class="modal-footer border-top">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>Create User
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
