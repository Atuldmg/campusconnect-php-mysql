<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (!is_logged_in()) {
    set_flash('warning', 'Please sign in to download course materials.');
    $currentUri = $_SERVER['REQUEST_URI'] ?? '';
    $redirectParam = !empty($currentUri) ? '?redirect=' . urlencode($currentUri) : '';
    header('Location: ' . url('login.php' . $redirectParam));
    exit;
}

$resourceId = (int)($_GET['id'] ?? 0);
if ($resourceId <= 0) {
    die('Invalid resource identifier.');
}

try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT id, title, file_path, file_name, file_type FROM resources WHERE id = ? LIMIT 1");
    $stmt->execute([$resourceId]);
    $resource = $stmt->fetch();

    if (!$resource) {
        http_response_code(404);
        die('The requested resource does not exist.');
    }

    $relativePath = $resource['file_path'];
    $fullPath = __DIR__ . '/' . ltrim($relativePath, '/');

    $realFullPath = realpath($fullPath);
    $uploadDir = realpath(__DIR__ . '/uploads');

    if (!$realFullPath || !str_starts_with($realFullPath, $uploadDir) || !file_exists($realFullPath)) {
        http_response_code(404);
        die('The physical file is currently unavailable on the campus server.');
    }

    $downloadFileName = basename($resource['file_name']);
    $contentType = $resource['file_type'] ?: 'application/octet-stream';

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadFileName) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($realFullPath));

    if (ob_get_level()) {
        ob_end_clean();
    }
    readfile($realFullPath);
    exit;

} catch (Exception $e) {
    error_log('Download error: ' . $e->getMessage());
    http_response_code(500);
    die('An unexpected error occurred while processing the download.');
}
