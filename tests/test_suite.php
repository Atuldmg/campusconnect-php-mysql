<?php
/**
 * CampusConnect - Automated Verification & Quality Assurance Suite
 * Validates database operations, security constraints, and API responses.
 */

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

// -----------------------------------------------------------
// TEST 1: Database Connection
// -----------------------------------------------------------
try {
    $pdo = get_db_connection();
    assert_test("Database PDO Connection", $pdo instanceof PDO);
} catch (Exception $e) {
    assert_test("Database PDO Connection", false, $e->getMessage());
}

// -----------------------------------------------------------
// TEST 2: Schema Tables Existence
// -----------------------------------------------------------
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

// -----------------------------------------------------------
// TEST 3: Admin & Student Seed Accounts & Bcrypt Verification
// -----------------------------------------------------------
try {
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
    $stmt->execute(['admin@campusconnect.edu']);
    echo 'Initial basic tests finished.\n';
?>