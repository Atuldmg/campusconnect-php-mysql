<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

echo "========================================================\n";
echo "   CampusConnect Automated Test & Verification Suite    \n";
echo "========================================================\n\n";

$passed = 0;
$failed = 0;

function assert_test(string $name, bool $condition, string $detail = '') {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] {$name}\n";
        $passed++;
    } else {
        echo " [FAIL] {$name}" . ($detail ? " — {$detail}" : "") . "\n";
        $failed++;
    }
}

try {
    $pdo = get_db_connection();
    assert_test("Database PDO Connection", $pdo instanceof PDO);
} catch (Exception $e) {
    assert_test("Database PDO Connection", false, $e->getMessage());
}

try {
    $expectedTables = ['users', 'announcements', 'resources', 'assignments', 'assignment_submissions', 'events', 'event_registrations', 'feedback'];
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $stmt = $pdo->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $allTablesExist = count(array_diff($expectedTables, $existingTables)) === 0;
    assert_test("All 8 Required Database Tables Exist", $allTablesExist);
} catch (Exception $e) {
    assert_test("All 8 Required Database Tables Exist", false, $e->getMessage());
}

try {
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
    $stmt->execute(['admin@campusconnect.edu']);
    $adminUser = $stmt->fetch();

    $adminOk = $adminUser && $adminUser['role'] === 'admin' && password_verify('AdminPassword123!', $adminUser['password_hash']);
    assert_test("Admin Seed Account & Bcrypt Password", $adminOk);

    $stmt->execute(['aarav.sharma@campusconnect.edu']);
    $studentUser = $stmt->fetch();

    $studentOk = $studentUser && $studentUser['role'] === 'student' && password_verify('StudentPass123!', $studentUser['password_hash']);
    assert_test("Student Seed Account & Bcrypt Password", $studentOk);
} catch (Exception $e) {
    assert_test("Seed Accounts & Bcrypt Password", false, $e->getMessage());
}

try {

    $dupCaught = false;
    try {
        $stmtDup = $pdo->prepare("INSERT INTO event_registrations (event_id, student_id, registered_at) VALUES (1, 2, NOW())");
        $stmtDup->execute();
    } catch (Exception $e) {
        $dupCaught = true;
    }
    assert_test("Duplicate Event Registration Prevention (Unique Constraint)", $dupCaught);
} catch (Exception $e) {
    assert_test("Duplicate Event Registration Prevention", false, $e->getMessage());
}

$token = get_csrf_token();
$validCheck = validate_csrf($token);
$invalidCheck = !validate_csrf('fake_token_12345');
assert_test("CSRF Token Cryptographic Validation", $validCheck && $invalidCheck);

$rawMalicious = '<script>alert("xss")</script>';
$escaped = e($rawMalicious);
assert_test("XSS Sanitization Helper e()", $escaped === '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');

try {
    $xmlPath = dirname(__DIR__) . '/xml/courses.xml';
    $dom = new DOMDocument();
    $dom->resolveExternals = true;
    libxml_use_internal_errors(true);
    $xmlLoaded = $dom->load($xmlPath);
    $dtdValid = $dom->validate();
    libxml_clear_errors();

    assert_test("XML Course Catalogue & DTD Schema Validation", $xmlLoaded && $dtdValid);
} catch (Exception $e) {
    assert_test("XML Course Catalogue & DTD Schema Validation", false, $e->getMessage());
}

try {
    $q = 'Web';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM resources WHERE title LIKE ? OR description LIKE ?");
    $stmt->execute(['%' . $q . '%', '%' . $q . '%']);
    $count = (int)$stmt->fetchColumn();
    assert_test("Study Resource Query Search Execution", $count >= 1);
} catch (Exception $e) {
    assert_test("Study Resource Query Search Execution", false, $e->getMessage());
}

echo "\n========================================================\n";
echo " Test Suite Finished: {$passed} Passed, {$failed} Failed\n";
echo "========================================================\n";

if ($failed > 0) {
    exit(1);
}
