<?php
// php/config.php - Database Configuration

// Load environment variables
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
}

// Load .env file
loadEnv(__DIR__ . '/../.env');

// Database configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'mysql');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_DATABASE', $_ENV['DB_DATABASE'] ?? 'kurdleave');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'kurdleave_user');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? 'kurdleave_pass');

// Application configuration
define('APP_NAME', $_ENV['APP_NAME'] ?? 'KurdLeave');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost:8080');
define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? 'true');

// Session configuration
define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? '120');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('UTC');

// Error reporting based on debug mode
if (APP_DEBUG === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
