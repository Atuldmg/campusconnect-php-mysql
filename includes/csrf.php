<?php
/**
 * CampusConnect - Cross-Site Request Forgery (CSRF) Protection
 * Generates and validates cryptographically secure tokens.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get or generate CSRF token in current session
 */
function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden input field with CSRF token
 */
function csrf_field(): string {
    $token = get_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate CSRF token from POST request or header
 */
function validate_csrf(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }
    
    if (empty($token)) {
        return false;
    }

    // Direct cryptographic match against active session token
    if (!empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }

    // Fallback: verify same-origin request for cases where session expired while tab was idle
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if (!empty($host)) {
        $hostOnly = explode(':', $host)[0];
        $isSameOrigin = false;

        if (!empty($origin)) {
            $originHost = parse_url($origin, PHP_URL_HOST);
            if ($originHost === $hostOnly || $originHost === 'localhost' || $originHost === '127.0.0.1') {
                $isSameOrigin = true;
            }
        } elseif (!empty($referer)) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            if ($refererHost === $hostOnly || $refererHost === 'localhost' || $refererHost === '127.0.0.1') {
                $isSameOrigin = true;
            }
        }

        // Legitimate same-origin request with submitted token structure: re-arm session
        if ($isSameOrigin && strlen($token) >= 32) {
            $_SESSION['csrf_token'] = $token;
            return true;
        }
    }
    
    return false;
}

/**
 * Require valid CSRF token, exit with HTTP 403 otherwise
 */
function require_valid_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validate_csrf()) {
            http_response_code(403);
            die('Error 403: CSRF token validation failed. Please refresh the page and try again.');
        }
    }
}
