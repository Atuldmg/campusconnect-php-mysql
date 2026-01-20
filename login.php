<?php
/**
 * CampusConnect - User Authentication Page
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// Redirect if already logged in
if (is_logged_in()) {
    $role = $_SESSION['user_role'] ?? 'student';
    header('Location: ' . ($role === 'admin' ? url('admin/dashboard.php') : url('student/dashboard.php')));
    exit;
}

$pageTitle = 'Sign In';
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Session security token expired. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your email address and password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } else {
            try {
                $pdo = get_db_connection();
                $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role, department, roll_number FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    login_user($user);
                    set_flash('success', 'Welcome back, ' . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . '!');

                    if ($user['role'] === 'admin') {
                        header('Location: ' . url('admin/dashboard.php'));
                    } else {
                        header('Location: ' . url('student/dashboard.php'));
                    }
                    exit;
                } else {
                    // Generic security message to prevent user enumeration
                    $error = 'Invalid email credentials or password.';
                }
            } catch (Exception $e) {
                error_log('Login error: ' . $e->getMessage());
                $error = 'Authentication service encountered a database error. Please verify database connectivity.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-8 col-lg-5">
      <div class="card card-custom p-4 shadow-sm">
        
        <div class="text-center mb-4">
          <div class="stat-icon-wrapper bg-primary-subtle text-primary mx-auto mb-2" style="width: 54px; height: 54px;">
            <i class="bi bi-mortarboard fs-3"></i>
          </div>
          <h4 class="fw-bold mb-1">CampusConnect Portal</h4>
          <p class="text-muted small">Sign in to access your student or admin dashboard</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 small" role="alert">
            <i class="bi bi-exclamation-circle-fill me-1"></i><?= e($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('login.php') ?>" class="needs-validation" novalidate>
          <?= csrf_field() ?>

          <div class="mb-3">
            <label for="email" class="form-label">Campus Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="user@campusconnect.edu" required autofocus>
            </div>
          </div>

          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
              <label for="password" class="form-label mb-0">Password</label>
            </div>
            <div class="input-group mt-1">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
              <button class="btn btn-outline-secondary password-toggle-btn" type="button" data-target="password" aria-label="Toggle password view">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 mt-2">
            <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
          </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center small text-muted">
          New student? <a href="<?= url('register.php') ?>" class="text-primary text-decoration-none fw-semibold">Register for Student Account</a>
        </div>

        <!-- Demonstration Credentials Notice (Essential for College Viva Demo) -->
        <div class="mt-4 p-3 bg-body-tertiary border rounded small">
          <div class="fw-bold mb-1 text-body"><i class="bi bi-info-circle me-1"></i>Demo Accounts for Evaluation:</div>
          <div class="d-flex justify-content-between text-muted mb-1">
            <span><strong>Admin:</strong> admin@campusconnect.edu</span>
            <code>AdminPassword123!</code>
          </div>
          <div class="d-flex justify-content-between text-muted">
            <span><strong>Student:</strong> aarav.sharma@campusconnect.edu</span>
            <code>StudentPass123!</code>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
