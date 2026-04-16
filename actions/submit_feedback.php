<?php
/**
 * CampusConnect - Feedback Submission Action Endpoint
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('student/feedback.php'));
    exit;
}

if (!validate_csrf()) {
    set_flash('danger', 'Security session expired. Please refresh and try again.');
    header('Location: ' . url('student/feedback.php'));
    exit;
}

$studentId = (int)$_SESSION['user_id'];
$subject   = trim($_POST['subject'] ?? '');
$category  = trim($_POST['category'] ?? 'General');
$message   = trim($_POST['message'] ?? '');

if (empty($subject) || empty($message)) {
    set_flash('danger', 'Subject and message are required fields.');
    header('Location: ' . url('student/feedback.php'));
    exit;
}

try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("INSERT INTO feedback (student_id, subject, category, message, status, created_at) VALUES (?, ?, ?, ?, 'new', NOW())");
    $stmt->execute([$studentId, $subject, $category, $message]);
    set_flash('success', 'Your feedback was successfully received by campus administration.');
} catch (Exception $e) {
    error_log('Action submit_feedback error: ' . $e->getMessage());
    set_flash('danger', 'Database error while submitting feedback.');
}

header('Location: ' . url('student/feedback.php'));
exit;
