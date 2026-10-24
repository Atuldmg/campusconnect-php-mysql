<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$redirect = trim($_GET['redirect'] ?? $_POST['redirect'] ?? '');
if ($redirect !== '' && (!str_starts_with($redirect, '/') || str_starts_with($redirect, '//'))) {
    $redirect = '';
}

if (is_logged_in()) {
    $role = $_SESSION['user_role'] ?? 'student';
    if (!empty($redirect)) {
        header('Location: ' . $redirect);
    } else {
        header('Location: ' . ($role === 'admin' ? url('admin/dashboard.php') : url('student/dashboard.php')));
    }
    exit;
}

$pageTitle = 'Sign In';
$error = '';
$loginInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!validate_csrf()) {
        $error = 'Session security token expired. Please refresh the page and try again.';
    } else {
        if (empty($loginInput) || empty($password)) {
            $error = 'Please enter both your identifier (email, roll number, or admin) and password.';
        } else {
            try {
                $pdo = get_db_connection();
                $stmt = $pdo->prepare("
                    SELECT id, name, email, password_hash, role, department, roll_number 
                    FROM users 
                    WHERE LOWER(email) = LOWER(?) 
                       OR LOWER(roll_number) = LOWER(?)
                       OR (LOWER(?) = 'admin' AND role = 'admin')
                       OR LOWER(SUBSTRING_INDEX(email, '@', 1)) = LOWER(?)
                    LIMIT 1
                ");
                $stmt->execute([$loginInput, $loginInput, $loginInput, $loginInput]);
                $user = $stmt->fetch();

                $authenticated = false;
                if ($user) {
                    if (password_verify($password, $user['password_hash'])) {
                        $authenticated = true;
                    } elseif (hash_equals($user['password_hash'], $password)) {
                        $authenticated = true;
                        try {
                            $newHash = password_hash($password, PASSWORD_BCRYPT);
                            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
                        } catch (Exception $e) {}
                    } elseif (hash_equals($user['password_hash'], md5($password))) {
                        $authenticated = true;
                        try {
                            $newHash = password_hash($password, PASSWORD_BCRYPT);
                            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
                        } catch (Exception $e) {}
                    }
                }

                if ($user && $authenticated) {
                    login_user($user);
                    set_flash('success', 'Welcome back, ' . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . '!');

                    if (!empty($redirect)) {
                        header('Location: ' . $redirect);
                    } elseif ($user['role'] === 'admin') {
                        header('Location: ' . url('admin/dashboard.php'));
                    } else {
                        header('Location: ' . url('student/dashboard.php'));
                    }
                    exit;
                } else {
                    $error = 'Invalid credentials or password. Please check your details and try again.';
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
          <?php if (!empty($redirect)): ?>
            <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
          <?php endif; ?>

          <div class="mb-3">
            <label for="email" class="form-label">Campus Email, Roll Number or Username</label>
            <div class="input-group has-validation">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input type="text" class="form-control" id="email" name="email" value="<?= e($loginInput) ?>" placeholder="e.g. admin@campusconnect.edu or admin" required autofocus autocomplete="username">
              <div class="invalid-feedback">Please enter your email, roll number, or admin username.</div>
            </div>
          </div>

          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
              <label for="password" class="form-label mb-0">Password</label>
            </div>
            <div class="input-group has-validation mt-1">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
              <button class="btn btn-outline-secondary password-toggle-btn" type="button" data-target="password" aria-label="Toggle password view">
                <i class="bi bi-eye"></i>
              </button>
              <div class="invalid-feedback">Please enter your password.</div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 mt-2">
            <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
          </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center small text-muted">
          New student? <a href="<?= url('register.php') ?>" class="text-primary text-decoration-none fw-semibold">Register for Student Account</a>
        </div>

        <div class="mt-4 p-3 bg-body-tertiary border rounded small">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-bold text-body"><i class="bi bi-info-circle me-1"></i>Demo Accounts for Evaluation:</span>
            <span class="text-muted" style="font-size: 0.72rem;">One-click login</span>
          </div>
          <div class="p-2 mb-2 rounded border bg-body">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <div class="fw-bold text-body" style="font-size: 0.82rem;"><span class="badge bg-danger-subtle text-danger me-1">Admin</span> admin@campusconnect.edu</div>
              <small class="text-muted" style="font-size: 0.72rem;"><code>AdminPassword123!</code></small>
            </div>
            <div class="d-flex gap-1 justify-content-end">
              <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2" style="font-size: 0.72rem;" onclick="fillCredentials('admin@campusconnect.edu', 'AdminPassword123!')">
                Fill Form
              </button>
              <button type="button" class="btn btn-sm btn-primary py-0.5 px-2" style="font-size: 0.72rem;" onclick="quickLogin('admin@campusconnect.edu', 'AdminPassword123!')">
                <i class="bi bi-lightning-fill"></i> Sign In as Admin
              </button>
            </div>
          </div>
          <div class="p-2 rounded border bg-body">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <div class="fw-bold text-body" style="font-size: 0.82rem;"><span class="badge bg-primary-subtle text-primary me-1">Student</span> aarav.sharma@campusconnect.edu</div>
              <small class="text-muted" style="font-size: 0.72rem;"><code>StudentPass123!</code></small>
            </div>
            <div class="d-flex gap-1 justify-content-end">
              <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2" style="font-size: 0.72rem;" onclick="fillCredentials('aarav.sharma@campusconnect.edu', 'StudentPass123!')">
                Fill Form
              </button>
              <button type="button" class="btn btn-sm btn-success py-0.5 px-2" style="font-size: 0.72rem;" onclick="quickLogin('aarav.sharma@campusconnect.edu', 'StudentPass123!')">
                <i class="bi bi-lightning-fill"></i> Sign In as Student
              </button>
            </div>
          </div>
        </div>

        <script>
          function fillCredentials(email, password) {
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            if (emailInput && passInput) {
              emailInput.value = email;
              passInput.value = password;
              emailInput.focus();
            }
          }
          function quickLogin(email, password) {
            fillCredentials(email, password);
            const form = document.querySelector('form.needs-validation') || document.querySelector('form');
            if (form) {
              form.submit();
            }
          }
        </script>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
