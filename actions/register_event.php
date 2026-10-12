<?php
/**
 * CampusConnect - Event Registration Action Endpoint
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('student/events.php'));
    exit;
}

if (!validate_csrf()) {
    set_flash('danger', 'Security session expired. Please refresh and try again.');
    header('Location: ' . url('student/events.php'));
    exit;
}

$eventId = (int)($_POST['event_id'] ?? 0);
$studentId = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? 'register';

if ($eventId <= 0) {
    set_flash('danger', 'Invalid event selected.');
    header('Location: ' . url('student/events.php'));
    exit;
}

try {
    $pdo = get_db_connection();

    if ($action === 'register') {
        // Enforce uniqueness
        $checkStmt = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND student_id = ?");
        $checkStmt->execute([$eventId, $studentId]);
        if ($checkStmt->fetch()) {
            set_flash('info', 'You are already registered for this event.');
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO event_registrations (event_id, student_id, registered_at) VALUES (?, ?, NOW())");
            $insertStmt->execute([$eventId, $studentId]);
            set_flash('success', 'Event registration confirmed!');
        }
    } elseif ($action === 'cancel') {
        $delStmt = $pdo->prepare("DELETE FROM event_registrations WHERE event_id = ? AND student_id = ?");
        $delStmt->execute([$eventId, $studentId]);
        set_flash('info', 'Registration cancelled.');
    }

} catch (Exception $e) {
    error_log('Action register_event error: ' . $e->getMessage());
    set_flash('danger', 'Failed to update event registration.');
}

header('Location: ' . url('student/events.php'));
exit;
