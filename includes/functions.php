<?php
/**
 * CampusConnect - Core Utility Functions
 * Helper functions for URL routing, sanitization, dates, flash messaging, and formatters.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session parameters before starting
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

/**
 * Returns project base URL for robust subfolder deployment in XAMPP htdocs.
 * Automatically adapts whether the project is at root (e.g. http://localhost:8000/)
 * or in a subfolder (e.g. http://localhost/Final-AWT-Project/ or http://localhost/campusconnect/).
 */
function base_url(): string {
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    // Determine the root folder relative to current execution
    $dir = dirname($scriptName);
    
    // Normalize Windows/Unix path separators
    $dir = str_replace('\\', '/', $dir);

    // If script is executed inside a subfolder like /student or /admin or /api, step out
    $subfolders = ['/student', '/admin', '/api', '/actions', '/scripts', '/includes', '/xml'];
    foreach ($subfolders as $sub) {
        if (str_ends_with($dir, $sub)) {
            $dir = substr($dir, 0, -strlen($sub));
            break;
        }
    }

    $base = rtrim($dir, '/');
    return $base;
}

/**
 * Generate an absolute URL path for internal links and assets.
 * 
 * @param string $path e.g. "student/dashboard.php" or "/assets/css/style.css"
 * @return string Full web path
 */
function url(string $path = ''): string {
    $cleanPath = ltrim($path, '/');
    $base = base_url();
    if ($base === '' || $base === '/') {
        return '/' . $cleanPath;
    }
    return $base . '/' . $cleanPath;
}

/**
 * HTML sanitization helper for XSS defense
 */
function e(?string $string): string {
    if ($string === null) {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Flash messaging system
 */
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => in_array($type, ['success', 'danger', 'warning', 'info']) ? $type : 'info',
        'message' => $message,
    ];
}

/**
 * Retrieve and clear all queued flash messages
 * 
 * @return array
 */
function get_flashes(): array {
    $flashes = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $flashes;
}

/**
 * Date/time formatting helper
 */
function format_date(?string $dateTimeStr, string $format = 'M d, Y'): string {
    if (!$dateTimeStr) {
        return '—';
    }
    $timestamp = strtotime($dateTimeStr);
    return $timestamp ? date($format, $timestamp) : '—';
}

/**
 * Relative time helper (e.g. "2 hours ago", "yesterday")
 */
function time_ago(?string $dateTimeStr): string {
    if (!$dateTimeStr) {
        return '—';
    }
    $timestamp = strtotime($dateTimeStr);
    if (!$timestamp) return '—';

    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'just now';
    }
    if ($diff < 3600) {
        $mins = (int)floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    }
    if ($diff < 86400) {
        $hrs = (int)floor($diff / 3600);
        return $hrs . ' hr' . ($hrs > 1 ? 's' : '') . ' ago';
    }
    if ($diff < 604800) {
        $days = (int)floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
    return date('M d, Y', $timestamp);
}

/**
 * Format bytes to readable size (KB, MB)
 */
function format_bytes(int $bytes): string {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = (int)floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 1) . ' ' . $units[$i];
}

/**
 * Helper to get badge class for categories
 */
function category_badge_class(string $cat): string {
    return match (strtolower(trim($cat))) {
        'academic'    => 'bg-primary-subtle text-primary border border-primary-subtle',
        'events'      => 'bg-success-subtle text-success border border-success-subtle',
        'general'     => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
        'facilities'  => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
        'technical'   => 'bg-info-subtle text-info-emphasis border border-info-subtle',
        'competition' => 'bg-danger-subtle text-danger border border-danger-subtle',
        default       => 'bg-light text-dark border',
    };
}
