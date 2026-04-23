<?php
/**
 * CampusConnect - Student Profile & Account Settings
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

require_role('student');

$studentId = (int)$_SESSION['user_id'];
$pageTitle = 'My Profile';
$error = '';
$success = '';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $name = trim($_POST['name'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if (empty($name)) {
                $error = 'Full name cannot be blank.';
            } else {
                try {
                    $pdo = get_db_connection();
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, department = ?, phone = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$name, $department, $phone, $studentId]);

                    // Update active session values
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_department'] = $department;

                    set_flash('success', 'Profile details updated successfully.');
                    header('Location: ' . url('student/profile.php'));
                    exit;
                } catch (Exception $e) {
                    $error = 'Database error updating profile: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (empty($currentPass) || empty($newPass)) {
                $error = 'Please provide both current and new password.';
            } elseif (strlen($newPass) < 8) {
                $error = 'New password must contain at least 8 characters.';
            } elseif ($newPass !== $confirmPass) {
                $error = 'New password confirmation does not match.';
            } else {
                try {
                    $pdo = get_db_connection();
                    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                    $stmt->execute([$studentId]);
                    $user = $stmt->fetch();

                    if (!$user || !password_verify($currentPass, $user['password_hash'])) {
                        $error = 'Current password entered is incorrect.';
                    } else {
                        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                        $updateStmt->execute([$newHash, $studentId]);

                        set_flash('success', 'Your password was changed successfully.');
                        header('Location: ' . url('student/profile.php'));
                        exit;
                    }
                } catch (Exception $e) {
                    $error = 'Database error while updating password: ' . $e->getMessage();
                }
            }
        }
    }
}

// Fetch current user database record
$userRecord = null;
try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT id, name, email, role, department, roll_number, phone, created_at FROM users WHERE id = ?");
    $stmt->execute([$studentId]);
    $userRecord = $stmt->fetch();
} catch (Exception $e) {
    error_log('Fetch profile error: ' . $e->getMessage());
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="portal-container">
  <?php require_once dirname(__DIR__) . '/includes/student_sidebar.php'; ?>

  <main class="portal-content">
    
    <div class="mb-4">
      <h4 class="fw-bold mb-1">Student Profile & Settings</h4>
      <p class="text-muted small mb-0">Manage your enrollment information and security credentials.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger py-2 mb-3 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($error) ?>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      
      <!-- Profile Information & Edit -->
      <div class="col-12 col-lg-7">
        <div class="card card-custom p-4 shadow-none border">
          <h5 class="fw-bold mb-3"><i class="bi bi-person-lines-fill text-primary me-2"></i>Personal Details</h5>
          
          <form method="POST" action="<?= url('student/profile.php') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile">

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" name="name" value="<?= e($userRecord['name'] ?? '') ?>" required>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Campus Email</label>
                <input type="email" class="form-control" value="<?= e($userRecord['email'] ?? '') ?>" disabled>
                <div class="form-text small">Email cannot be modified without IT office approval.</div>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Roll Number</label>
                <input type="text" class="form-control" value="<?= e($userRecord['roll_number'] ?? '') ?>" disabled>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Academic Department</label>
                <input type="text" class="form-control" name="department" value="<?= e($userRecord['department'] ?? '') ?>">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Phone Contact</label>
                <input type="tel" class="form-control" name="phone" value="<?= e($userRecord['phone'] ?? '') ?>" placeholder="+91 ...">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Member Since</label>
                <input type="text" class="form-control" value="<?= format_date($userRecord['created_at'] ?? '', 'F d, Y') ?>" disabled>
              </div>
            </div>

            <div class="mt-4 pt-2 border-top text-end">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i>Save Profile Changes
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Change Password -->
      <div class="col-12 col-lg-5">
        <div class="card card-custom p-4 shadow-none border">
          <h5 class="fw-bold mb-3"><i class="bi bi-key-fill text-warning me-2"></i>Change Password</h5>
          
          <form method="POST" action="<?= url('student/profile.php') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">

            <div class="mb-3">
              <label class="form-label">Current Password</label>
              <input type="password" class="form-control" name="current_password" required>
            </div>

            <div class="mb-3">
              <label class="form-label">New Password (Min. 8 characters)</label>
              <input type="password" class="form-control" name="new_password" minlength="8" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Confirm New Password</label>
              <input type="password" class="form-control" name="confirm_password" minlength="8" required>
            </div>

            <div class="mt-4 pt-2 border-top">
              <button type="submit" class="btn btn-outline-warning w-100">
                <i class="bi bi-shield-check me-1"></i>Update Password
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>

  </main>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
