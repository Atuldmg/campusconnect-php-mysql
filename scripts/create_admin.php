<?php
/**
 * CampusConnect - CLI First Administrator Provisioning Script
 * 
 * Usage:
 *   php scripts/create_admin.php "Admin Name" "email@campusconnect.edu" "SecurePassword123!"
 * 
 * Or run interactively without arguments.
 */

require_once dirname(__DIR__) . '/config/database.php';

echo "==========================================================" . PHP_EOL;
echo " CampusConnect - Administrator Account Provisioning Tool  " . PHP_EOL;
echo "==========================================================" . PHP_EOL;

if (php_sapi_name() !== 'cli') {
    die("Error: This provisioning script can only be run via CLI.\n");
}

$name = $argv[1] ?? null;
$email = $argv[2] ?? null;
$password = $argv[3] ?? null;

if (!$name) {
    echo "Enter Administrator Full Name: ";
    $name = trim(fgets(STDIN));
}

if (!$email) {
    echo "Enter Administrator Email Address: ";
    $email = trim(fgets(STDIN));
}

if (!$password) {
    echo "Enter Strong Password (min 8 chars): ";
    $password = trim(fgets(STDIN));
}

if (empty($name) || empty($email) || empty($password)) {
    die("Error: All fields (Name, Email, Password) are required.\n");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Error: Invalid email format.\n");
}

if (strlen($password) < 8) {
    die("Error: Password must be at least 8 characters long.\n");
}

try {
    $pdo = get_db_connection();
    
    // Check if email already exists
    $checkStmt = $pdo->prepare("SELECT id, role FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    $existing = $checkStmt->fetch();

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    if ($existing) {
        // Update user to admin role and update password
        $updateStmt = $pdo->prepare("UPDATE users SET name = ?, password_hash = ?, role = 'admin', updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$name, $hash, $existing['id']]);
        echo "[SUCCESS] Existing user account (ID: {$existing['id']}) updated to Administrator.\n";
    } else {
        // Insert new admin
        $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, department, created_at) VALUES (?, ?, ?, 'admin', 'Campus IT & Administration', NOW())");
        $insertStmt->execute([$name, $email, $hash]);
        $newId = $pdo->lastInsertId();
        echo "[SUCCESS] Administrator created successfully with User ID: {$newId}.\n";
    }

    echo "Email: {$email}\n";
    echo "Role:  admin\n";
    echo "You can now log in at /login.php\n";

} catch (Exception $e) {
    echo "[ERROR] Database error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
