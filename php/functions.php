<?php
// php/functions.php - Helper Functions
// This is like the Swiss Army knife of our leave management system
// It contains all the useful functions that other parts of the app need

// Start session if not already started - this keeps track of who's logged in
// Think of it like putting a wristband on someone at an event
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'database.php';

// AUTHENTICATION FUNCTIONS - These handle logging in and checking permissions

// This function checks if someone can log in with their email and password
function login($email, $password) {
    // First, find the user in the database by their email (and make sure they're active)
    $user = db_fetch("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);

    // If we found the user AND their password is correct
    if ($user && password_verify($password, $user['password'])) {
        // Save their info in the session so we remember they're logged in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['employee_id'] = $user['employee_id'];

        // Update their last login time in the database
        db_update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $user['id']]);

        // Keep a record that they logged in (for security and tracking)
        log_activity($user['id'], 'Login', 'User login successful');

        return true; // Success! They're logged in
    }

    return false; // Nope, wrong email or password
}

// This function logs someone out
function logout() {
    // If someone is logged in, record that they're logging out
    if (isset($_SESSION['user_id'])) {
        log_activity($_SESSION['user_id'], 'Logout', 'User logout');
    }

    // Destroy their session (forget they were logged in)
    session_destroy();
    // Send them back to the login page
    header('Location: ' . APP_URL . '/user/login.php');
    exit;
}

// Check if someone is currently logged in
function is_logged_in() {
    return isset($_SESSION['user_id']); // Do we have their user ID saved?
}

// Check if the logged-in person is an admin (has full system access)
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Check if the logged-in person is a manager (can manage their department)
function is_manager() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'manager' || $_SESSION['user_role'] === 'admin');
}

// Force someone to log in - if they're not logged in, send them to login page
function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . APP_URL . '/user/login.php');
        exit;
    }
}

// Force admin access - if they're not an admin, kick them out
function require_admin() {
    require_login(); // First make sure they're logged in
    if (!is_admin()) {
        // Not an admin? Send them back to regular user homepage
        header('Location: ' . APP_URL . '/user/home.php');
        exit;
    }
}

// USER FUNCTIONS - These help us work with user data

// Get info about whoever is currently logged in
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null; // Nobody's logged in
    }
    // Get their full user record from the database
    return db_fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

// Get info about any user by their ID number
function get_user_by_id($id) {
    return db_fetch("SELECT * FROM users WHERE id = ?", [$id]);
}

// Get all active users in a specific department
function get_users_by_department($department_id) {
    return db_fetch_all("SELECT * FROM users WHERE department_id = ? AND status = 'active'", [$department_id]);
}

// LEAVE FUNCTIONS - These handle all the leave request stuff

// Get how many vacation days someone has left for a specific year
function get_user_leave_balance($user_id, $leave_type_id, $year = null) {
    if ($year === null) {
        $year = date('Y'); // If no year specified, use current year
    }

    return db_fetch("SELECT * FROM leave_balances WHERE user_id = ? AND leave_type_id = ? AND year = ?",
                   [$user_id, $leave_type_id, $year]);
}

// Get someone's leave history - their past vacation requests
function get_user_leave_history($user_id, $limit = 10) {
    return db_fetch_all("
        SELECT l.*, lt.name as leave_type_name, u.name as approved_by_name
        FROM leaves l
        LEFT JOIN leave_types lt ON l.leave_type_id = lt.id
        LEFT JOIN users u ON l.approved_by = u.id
        WHERE l.user_id = ?
        ORDER BY l.submitted_at DESC
        LIMIT ?
    ", [$user_id, $limit]);
}

// Get all leave requests that are waiting for approval
function get_pending_leaves() {
    return db_fetch_all("
        SELECT l.*, u.name as user_name, u.employee_id, lt.name as leave_type_name, d.name as department_name
        FROM leaves l
        JOIN users u ON l.user_id = u.id
        JOIN leave_types lt ON l.leave_type_id = lt.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE l.status = 'pending'
        ORDER BY l.submitted_at ASC
    ");
}

// Figure out how many working days are between two dates
// This skips weekends because most people don't work Saturdays and Sundays
function calculate_working_days($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end = $end->modify('+1 day'); // Include the end date in our calculation

    $interval = new DateInterval('P1D'); // One day at a time
    $period = new DatePeriod($start, $interval, $end);

    $working_days = 0;
    foreach ($period as $date) {
        // Skip weekends (Saturday = 6, Sunday = 0 in PHP's date system)
        if ($date->format('w') != 0 && $date->format('w') != 6) {
            $working_days++; // Count this as a working day
        }
    }

    return $working_days; // Return the total count
}

// DEPARTMENT FUNCTIONS - These handle company departments

// Get all departments in the company
function get_all_departments() {
    return db_fetch_all("SELECT * FROM departments ORDER BY name");
}

// Get info about a specific department
function get_department_by_id($id) {
    return db_fetch("SELECT * FROM departments WHERE id = ?", [$id]);
}

// LEAVE TYPE FUNCTIONS - These handle different types of leave (vacation, sick, etc.)

// Get all active leave types (vacation, sick leave, personal days, etc.)
function get_all_leave_types() {
    return db_fetch_all("SELECT * FROM leave_types WHERE status = 'active' ORDER BY name");
}

// Get info about a specific leave type
function get_leave_type_by_id($id) {
    return db_fetch("SELECT * FROM leave_types WHERE id = ?", [$id]);
}

// ACTIVITY LOGGING - This keeps track of what people do in the system

// Record when someone does something important (login, approve leave, etc.)
function log_activity($user_id, $action, $description, $ip_address = null) {
    if ($ip_address === null) {
        // If no IP provided, try to get it from the web server
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    // Save the activity record to the database
    db_insert('activity_logs', [
        'user_id' => $user_id,
        'action' => $action,
        'description' => $description,
        'ip_address' => $ip_address,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null // What browser they're using
    ]);
}

// UTILITY FUNCTIONS - These are helpful tools used throughout the app

// Format a date nicely for display (like "Jan 15, 2024")
function format_date($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Format a date with time nicely for display (like "Jan 15, 2024 2:30 PM")
function format_datetime($datetime, $format = 'M d, Y H:i') {
    return date($format, strtotime($datetime));
}

// Clean up user input to prevent security issues
// This removes dangerous characters that hackers might try to use
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Redirect someone to a different page
function redirect($url) {
    header("Location: $url"); // Tell the browser to go to this URL
    exit; // Stop running the current page
}

// FLASH MESSAGES - These are one-time messages shown to users

// Get a flash message that was saved for the user (like "Leave approved!")
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']); // Remove it so it only shows once
        return $message;
    }
    return null; // No message waiting
}

// Save a flash message to show the user on their next page
function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type // 'info', 'success', 'warning', or 'error'
    ];
}

// STATISTICS FUNCTIONS - These calculate numbers for the admin dashboard

// Get various statistics for the admin dashboard
function get_dashboard_stats() {
    $stats = [];

    // Count how many active users we have
    $stats['active_users'] = db_fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'];

    // Count how many leave requests are waiting for approval
    $stats['pending_leaves'] = db_fetch("SELECT COUNT(*) as count FROM leaves WHERE status = 'pending'")['count'];

    // Count how many people are on vacation today
    $today = date('Y-m-d');
    $stats['users_on_leave_today'] = db_fetch("
        SELECT COUNT(*) as count FROM leaves
        WHERE status = 'approved' AND ? BETWEEN start_date AND end_date
    ", [$today])['count'];

    // Calculate total vacation days used this year
    $year = date('Y');
    $stats['total_leave_days'] = db_fetch("
        SELECT SUM(working_days) as total FROM leaves
        WHERE status = 'approved' AND YEAR(start_date) = ?
    ", [$year])['total'] ?? 0;

    return $stats;
}

// Get recent activity in the system (for the activity log on dashboard)
function get_recent_activity($limit = 10) {
    return db_fetch_all("
        SELECT al.*, u.name as user_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT ?
    ", [$limit]);
}
?>
