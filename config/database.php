<?php
/**
 * CampusConnect - Database Connection Manager
 * Secure PDO connection handler supporting MySQL (default for XAMPP)
 * and SQLite fallback for portable offline testing environments.
 */

// Default MySQL configuration (Standard XAMPP default)
defined('DB_HOST') or define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
defined('DB_PORT') or define('DB_PORT', getenv('DB_PORT') ?: '3306');
defined('DB_NAME') or define('DB_NAME', getenv('DB_NAME') ?: 'campusconnect');
defined('DB_USER') or define('DB_USER', getenv('DB_USER') ?: 'root');
defined('DB_PASS') or define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
defined('DB_DRIVER') or define('DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql');

// Allow local config override if exists (e.g., config/local.php)
if (file_exists(__DIR__ . '/local.php')) {
    require_once __DIR__ . '/local.php';
}

/**
 * Returns a shared PDO database instance (Singleton pattern)
 * 
 * @return PDO
 * @throws PDOException
 */
function get_db_connection(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $driver = strtolower(DB_DRIVER);

    // 1. Try MySQL connection if driver is 'mysql'
    if ($driver === 'mysql') {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                DB_HOST,
                DB_PORT,
                DB_NAME
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_TIMEOUT            => 2, // Fast timeout if daemon is not running
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (PDOException $e) {
            // If MySQL failed, check if SQLite fallback is available for testing/development
            $sqlitePath = dirname(__DIR__) . '/database/campusconnect_dev.sqlite';
            if (file_exists($sqlitePath)) {
                return init_sqlite_connection($sqlitePath);
            }

            // If no SQLite fallback exists, rethrow error with clear explanation
            error_log('CampusConnect MySQL Connection Error: ' . $e->getMessage());
            throw new Exception(
                'Unable to connect to MySQL database "' . DB_NAME . '". ' .
                'Ensure MySQL is started in XAMPP and "database/campusconnect.sql" is imported into phpMyAdmin. ' .
                'Error: ' . $e->getMessage()
            );
        }
    }

    // 2. Direct SQLite driver support for zero-configuration testing
    if ($driver === 'sqlite') {
        $sqlitePath = dirname(__DIR__) . '/database/campusconnect_dev.sqlite';
        return init_sqlite_connection($sqlitePath);
    }

    throw new Exception("Unsupported database driver: $driver");
}

/**
 * Helper to initialize SQLite connection with compatibility functions
 */
function init_sqlite_connection(string $path): PDO {
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Register MySQL compatibility functions for SQLite
    $pdo->sqliteCreateFunction('NOW', function() {
        return date('Y-m-d H:i:s');
    }, 0);

    $pdo->sqliteCreateFunction('DATE_SUB', function($date, $interval) {
        return date('Y-m-d H:i:s', strtotime('-1 day', strtotime($date)));
    }, 2);

    $pdo->sqliteCreateFunction('DATE_ADD', function($date, $interval) {
        return date('Y-m-d H:i:s', strtotime('+7 days', strtotime($date)));
    }, 2);

    $pdo->exec('PRAGMA foreign_keys = ON;');
    return $pdo;
}
