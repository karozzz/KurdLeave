<?php
// php/config.php - Database Configuration
// This is like the main settings file for our leave management system
// Think of it as the control panel where we tell the app how to connect to the database

// Hey, this function reads environment settings from a .env file
// It's like reading a recipe from a cookbook - we need to know the ingredients (database info)
function loadEnv($path) {
    // First, check if the .env file actually exists - no point trying to read something that's not there
    if (!file_exists($path)) {
        return; // If no file, just give up and move on
    }

    // Read the file line by line, ignoring empty lines
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comment lines that start with # - these are just notes for humans
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        // Split each line into name and value (like DB_HOST=localhost)
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);   // Remove any extra spaces
        $value = trim($value); // Remove any extra spaces
        $_ENV[$name] = $value; // Store it where PHP can find it
        putenv("$name=$value"); // Also store it in the system environment
    }
}

// Now let's actually load our settings from the .env file
loadEnv(__DIR__ . '/../.env');

// Database settings - these tell our app how to talk to the MySQL database
// It's like giving someone your address so they can visit you
define('DB_HOST', $_ENV['DB_HOST'] ?? 'mysql');           // Where is the database? (usually 'mysql' in Docker)
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');            // Which door to knock on? (3306 is MySQL's default)
define('DB_DATABASE', $_ENV['DB_DATABASE'] ?? 'kurdleave'); // Which database to use?
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'kurdleave_user'); // Username to log in
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? 'kurdleave_pass'); // Password to log in

// App settings - basic info about our leave management system
define('APP_NAME', $_ENV['APP_NAME'] ?? 'KurdLeave');      // What do we call this app?
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost:8080'); // Where can people find it?
define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? 'true');         // Should we show detailed errors? (helpful for fixing bugs)

// Session settings - how long should someone stay logged in?
define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? '120'); // 120 minutes = 2 hours

// Start a session if we haven't already - this tracks who's logged in
// Think of it like putting a wristband on someone at an event
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set the timezone - everyone needs to be on the same clock
date_default_timezone_set('UTC');

// Error reporting - should we show errors on screen?
if (APP_DEBUG === 'true') {
    // If we're in debug mode, show all errors (helpful for developers)
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // If we're live, hide errors from users (they don't need to see the technical stuff)
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
