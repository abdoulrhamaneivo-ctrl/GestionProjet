<?php
/**
 * Configuration file for EMSP Assignment Manager
 * Correct database credentials should be provided here when installing.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'emsp_assignment_db');

// App settings
define('APP_NAME', 'EMSP Assignment Manager');
if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) {
    define('APP_URL', 'http://localhost/student-hub-php/public');
} else {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = parse_url($_SERVER['SCRIPT_NAME'] ?? '/public/index.php', PHP_URL_PATH) ?: '/public/index.php';
    $basePath = rtrim(dirname($scriptName), '/\\');
    define('APP_URL', $scheme . '://' . $host . ($basePath === '' ? '' : $basePath));
}

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour

// Autoload paths
define('APP_PATH', dirname(__DIR__) . '/app');
