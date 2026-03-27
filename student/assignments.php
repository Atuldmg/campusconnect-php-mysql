<?php
/**
 * CampusConnect - Student Assignments & Submissions
 * Displays deadlines, submission status, grade evaluations, and handles assignment upload/submission.
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

require_role('student');

$studentId = (int)$_SESSION['user_id'];
$pageTitle = 'My Course Assignments';
$error = '';
$success = '';

// Handle assignment submission POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_assignment') {
    if (!validate_csrf()) {
        $error = 'Session security token expired. Please reload and try again.';
    } else {
        $assignmentId = (int)($_POST['assignment_id'] ?? 0);
        $notes = trim($_POST['submission_notes'] ?? '');

        if ($assignmentId <= 0) {
            $error = 'Invalid assignment target.';
        } else {
            try {
                $pdo = get_db_connection();

                // Verify assignment exists
                $stmtCheck = $pdo->prepare("SELECT id, title, due_date FROM assignments WHERE id = ?");
                $stmtCheck->execute([$assignmentId]);
                $asg = $stmtCheck->fetch();

                if (!$asg) {
                    $error = 'Selected assignment was not found.';
                } else {
                    $uploadedFilePath = null;
                    $uploadedFileName = null;

                    // Handle optional file upload
                    if (!empty($_FILES['submission_file']['name']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['submission_file'];
                        $maxBytes = 10 * 1024 * 1024; // 10MB

                        if ($file['size'] > $maxBytes) {
                            $error = 'File exceeds the maximum permitted size of 10MB.';
                        } else {
                            $allowedExts = ['pdf', 'docx', 'txt', 'zip', 'rar', 'cpp', 'java', 'py'];
                            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                            if (!in_array($ext, $allowedExts)) {
                                $error = 'File type .' . htmlspecialchars($ext, ENT_QUOTES, 'UTF-8') . ' is not permitted. Please upload a PDF, DOCX, TXT, or ZIP archive.';
                            } else {
                                $uploadDir = dirname(__DIR__) . '/uploads/submissions';
                                if (!is_dir($uploadDir)) {
                                    mkdir($uploadDir, 0755, true);
                                }

                                $uniqueName = 'sub_' . $assignmentId . '_' . $studentId . '_' . time() . '.' . $ext;
                                $targetDest = $uploadDir . '/' . $uniqueName;

                                if (move_uploaded_file($file['tmp_name'], $targetDest)) {
                                    $uploadedFilePath = 'uploads/submissions/' . $uniqueName;
                                    $uploadedFileName = basename($file['name']);
                                } else {
                                    $error = 'Server failed to save uploaded file.';
                                }
                            }
                        }
                    }

                    if (empty($error)) {
                        // Check if previous submission exists
                        $stmtSubCheck = $pdo->prepare("SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
                        $stmtSubCheck->execute([$assignmentId, $studentId]);
                        $prevSub = $stmtSubCheck->fetch();

                        if ($prevSub) {
                            // Update existing submission
                            $sql = "UPDATE assignment_submissions SET submission_notes = ?, status = 'submitted', submitted_at = NOW()";
                            $params = [$notes];

                            if ($uploadedFilePath) {
                                $sql .= ", file_path = ?, file_name = ?";
                                $params[] = $uploadedFilePath;
                                $params[] = $uploadedFileName;
                            }
                            $sql .= " WHERE id = ?";
                            $params[] = $prevSub['id'];

                            $updateStmt = $pdo->prepare($sql);
                            $updateStmt->execute($params);
                            set_flash('success', 'Your submission for "' . $asg['title'] . '" has been updated.');
                        } else {
                            // Insert new submission
                            $insertStmt = $pdo->prepare("
                                INSERT INTO assignment_submissions (assignment_id, student_id, file_path, file_name, submission_notes, status, submitted_at)
                                VALUES (?, ?, ?, ?, ?, 'submitted', NOW())
                            ");
                            $insertStmt->execute([$assignmentId, $studentId, $uploadedFilePath, $uploadedFileName, $notes]);
                            set_flash('success', 'Assignment "' . $asg['title'] . '" submitted successfully!');
                        }

                        header('Location: ' . url('student/assignments.php'));
                        exit;
                    }
                }
            } catch (Exception $e) {
                error_log('Submission error: ' . $e->getMessage());
                $error = 'Database error occurred during submission: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all assignments with student's submission status
$assignments = [];
try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.description, a.subject, a.due_date, a.max_marks,
               s.id as submission_id, s.file_name as submitted_file_name, s.submission_notes,
               s.status as submission_status, s.grade, s.feedback, s.submitted_at
        FROM assignments a
        LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
        ORDER BY a.due_date ASC
    ");
    $stmt->execute([$studentId]);
    $assignments = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Fetch assignments error: ' . $e->getMessage());
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="portal-container">
  <?php require_once dirname(__DIR__) . '/includes/student_sidebar.php'; ?>

  <main class="portal-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold mb-1">Course Assignments & Tasks</h4>
        <p class="text-muted small mb-0">Review submission deadlines, upload solutions, and inspect faculty feedback.</p>
      </div>
</div></div></div><?php include '../includes/footer.php'; ?>