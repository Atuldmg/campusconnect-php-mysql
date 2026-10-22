<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (is_logged_in()) {
    header('Location: ' . url('student/dashboard.php'));
    exit;
}

$pageTitle = 'Student Registration';
$error = '';
$success = '';

$name = '';
$email = '';
$department = 'Computer Science (BCA)';
$rollNumber = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $name        = trim($_POST['name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $department  = trim($_POST['department'] ?? '');
        $rollNumber  = trim($_POST['roll_number'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $password    = $_POST['password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || empty($password) || empty($rollNumber)) {
            $error = 'Please fill out all required fields marked with an asterisk (*).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid academic email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirmPass) {
            $error = 'Passwords do not match. Please re-enter.';
        } else {
            try {
                $pdo = get_db_connection();

                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    $error = 'An account with this email address already exists. Please sign in instead.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $insertStmt = $pdo->prepare(
                        "INSERT INTO users (name, email, password_hash, role, department, roll_number, phone, created_at) " .
                        "VALUES (?, ?, ?, 'student', ?, ?, ?, NOW())"
                    );
                    $insertStmt->execute([$name, $email, $hash, $department, $rollNumber, $phone]);

                    $userId = $pdo->lastInsertId();

                    $newUser = [
                        'id'          => $userId,
                        'name'        => $name,
                        'email'       => $email,
                        'role'        => 'student',
                        'department'  => $department,
                        'roll_number' => $rollNumber,
                    ];
                    login_user($newUser);

                    set_flash('success', 'Registration successful! Welcome to CampusConnect.');
                    header('Location: ' . url('student/dashboard.php'));
                    exit;
                }
            } catch (Exception $e) {
                error_log('Registration error: ' . $e->getMessage());
                $error = 'Registration failed due to a database error. Please verify your details.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
      <div class="card card-custom p-4 shadow-sm">

        <div class="text-center mb-4">
          <div class="stat-icon-wrapper bg-primary-subtle text-primary mx-auto mb-2" style="width: 50px; height: 50px;">
            <i class="bi bi-person-plus fs-3"></i>
          </div>
          <h4 class="fw-bold mb-1">Create Student Account</h4>
          <p class="text-muted small">Join CampusConnect to access assignments, events, and resources</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 small" role="alert">
            <i class="bi bi-exclamation-circle-fill me-1"></i><?= e($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('register.php') ?>" id="registrationForm" class="needs-validation" novalidate>
          <?= csrf_field() ?>

          <div class="row g-3">
            <div class="col-12">
              <label for="reg_name" class="form-label">Full Name *</label>
              <input type="text" class="form-control" id="reg_name" name="name" value="<?= e($name) ?>" placeholder="e.g. Aarav Sharma" required>
            </div>

            <div class="col-12">
              <label for="reg_email" class="form-label">Campus / Personal Email *</label>
              <input type="email" class="form-control" id="reg_email" name="email" value="<?= e($email) ?>" placeholder="name@campusconnect.edu" required>
            </div>

            <div class="col-12 col-md-6">
              <label for="reg_roll" class="form-label">Student Roll Number *</label>
              <input type="text" class="form-control" id="reg_roll" name="roll_number" value="<?= e($rollNumber) ?>" placeholder="e.g. CC-2023-BCA-042" required>
            </div>

            <div class="col-12 col-md-6">
              <label for="reg_dept" class="form-label">Academic Department *</label>
              <select class="form-select" id="reg_dept" name="department" required>
                <option value="Computer Science (BCA)" <?= ($department === 'Computer Science (BCA)') ? 'selected' : '' ?>>Computer Science (BCA)</option>
                <option value="Information Technology (B.Sc IT)" <?= ($department === 'Information Technology (B.Sc IT)') ? 'selected' : '' ?>>Information Technology (B.Sc IT)</option>
                <option value="Computer Applications (MCA)" <?= ($department === 'Computer Applications (MCA)') ? 'selected' : '' ?>>Computer Applications (MCA)</option>
                <option value="Computer Engineering (B.Tech)" <?= ($department === 'Computer Engineering (B.Tech)') ? 'selected' : '' ?>>Computer Engineering (B.Tech)</option>
              </select>
            </div>

            <div class="col-12">
              <label for="reg_phone" class="form-label">Contact Phone Number (Optional)</label>
              <input type="tel" class="form-control" id="reg_phone" name="phone" value="<?= e($phone) ?>" placeholder="+91 9876543210">
            </div>

            <div class="col-12 col-md-6">
              <label for="reg_password" class="form-label">Password *</label>
              <div class="input-group">
                <input type="password" class="form-control" id="reg_password" name="password" minlength="8" placeholder="Min. 8 characters" required>
                <button class="btn btn-outline-secondary password-toggle-btn" type="button" data-target="reg_password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="col-12 col-md-6">
              <label for="reg_confirm_password" class="form-label">Confirm Password *</label>
              <div class="input-group">
                <input type="password" class="form-control" id="reg_confirm_password" name="confirm_password" minlength="8" placeholder="Repeat password" required>
                <button class="btn btn-outline-secondary password-toggle-btn" type="button" data-target="reg_confirm_password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 mt-4">
            <i class="bi bi-check-circle me-1"></i>Complete Registration
          </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center small text-muted">
          Already registered? <a href="<?= url('login.php') ?>" class="text-primary text-decoration-none fw-semibold">Sign In Here</a>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
