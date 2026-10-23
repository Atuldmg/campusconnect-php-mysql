<?php

require_once __DIR__ . '/functions.php';

function is_logged_in(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

function get_logged_in_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'          => $_SESSION['user_id'],
        'name'        => $_SESSION['user_name'] ?? '',
        'email'       => $_SESSION['user_email'] ?? '',
        'role'        => $_SESSION['user_role'] ?? '',
        'department'  => $_SESSION['user_department'] ?? '',
        'roll_number' => $_SESSION['user_roll_number'] ?? '',
    ];
}

function has_role(string $role): bool {
    return is_logged_in() && ($_SESSION['user_role'] === $role);
}

function require_login(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to access this portal page.');
        header('Location: ' . url('login.php'));
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    if ($_SESSION['user_role'] !== $role) {
        http_response_code(403);

        require_once __DIR__ . '/header.php';
        echo '<div class="container py-5 text-center">
            <div class="card shadow-sm border-danger p-4 mx-auto" style="max-width: 500px;">
                <div class="text-danger mb-3"><i class="bi bi-shield-lock display-4"></i></div>
                <h3 class="card-title fw-bold">Access Denied (403)</h3>
                <p class="text-muted">You do not have administrative authorization to access this area.</p>
                <a href="' . url('student/dashboard.php') . '" class="btn btn-primary mt-2">Return to Student Dashboard</a>
            </div>
        </div>';
        require_once __DIR__ . '/footer.php';
        exit;
    }
}

function login_user(array $user): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['user_id']          = (int)$user['id'];
    $_SESSION['user_name']        = $user['name'];
    $_SESSION['user_email']       = $user['email'];
    $_SESSION['user_role']        = $user['role'];
    $_SESSION['user_department']  = $user['department'] ?? '';
    $_SESSION['user_roll_number'] = $user['roll_number'] ?? '';
    $_SESSION['logged_in_time']   = time();

    session_regenerate_id(true);
}

function logout_user(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
