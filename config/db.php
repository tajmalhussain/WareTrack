<?php
/**
 * WareTrack - Database Configuration & PDO Connection
 * Provides secure PDO connection to MySQL / MariaDB database.
 */

// Database Credentials (Configure for XAMPP/WAMP/MAMP/Production)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'waretrack_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO Database Connection
 *
 * @param bool $throwOnError Whether to throw an exception on failure
 * @return PDO|null
 */
function getDBConnection($throwOnError = false) {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 3,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Try fallback without specific dbname to check if MySQL is running but database is not yet created
        try {
            $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
            $tempPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
            // Attempt auto-creating the database if privileges allow
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            // Retry connecting to the now created database
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (Exception $fallbackEx) {
            // Keep original exception
        }

        if ($throwOnError) {
            throw $e;
        }
        return null;
    }
}

/**
 * Helper to check connection status and diagnostic message
 *
 * @return array ['connected' => bool, 'error' => string|null]
 */
function checkDBConnection() {
    try {
        $conn = getDBConnection(true);
        return ['connected' => ($conn !== null), 'error' => null];
    } catch (Exception $e) {
        return ['connected' => false, 'error' => $e->getMessage()];
    }
}
