<?php
// php/functions.php - Helper Functions

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'database.php';

// Authentication Functions
function login($email, $password) {
    $user = db_fetch("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['employee_id'] = $user['employee_id'];

        // Update last login
        db_update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $user['id']]);

        // Log activity
        log_activity($user['id'], 'Login', 'User login successful');

        return true;
    }

    return false;
}

function logout() {
    if (isset($_SESSION['user_id'])) {
        log_activity($_SESSION['user_id'], 'Logout', 'User logout');
    }

    session_destroy();
    header('Location: ' . APP_URL . '/user/login.php');
    exit;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function is_manager() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'manager' || $_SESSION['user_role'] === 'admin');
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . APP_URL . '/user/login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        header('Location: ' . APP_URL . '/user/home.php');
        exit;
    }
}

// User Functions - RENAMED to avoid conflict with PHP built-in function
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }

    return db_fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

function get_user_by_id($id) {
    return db_fetch("SELECT * FROM users WHERE id = ?", [$id]);
}

function get_users_by_department($department_id) {
    return db_fetch_all("SELECT * FROM users WHERE department_id = ? AND status = 'active'", [$department_id]);
}

// Leave Functions
function get_user_leave_balance($user_id, $leave_type_id, $year = null) {
    if ($year === null) {
        $year = date('Y');
    }

    return db_fetch("SELECT * FROM leave_balances WHERE user_id = ? AND leave_type_id = ? AND year = ?",
                   [$user_id, $leave_type_id, $year]);
}

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

function calculate_working_days($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end = $end->modify('+1 day'); // Include end date

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);

    $working_days = 0;
    foreach ($period as $date) {
        // Skip weekends (Saturday = 6, Sunday = 0)
        if ($date->format('w') != 0 && $date->format('w') != 6) {
            $working_days++;
        }
    }

    return $working_days;
}

// Department Functions
function get_all_departments() {
    return db_fetch_all("SELECT * FROM departments ORDER BY name");
}

function get_department_by_id($id) {
    return db_fetch("SELECT * FROM departments WHERE id = ?", [$id]);
}

// Leave Type Functions
function get_all_leave_types() {
    return db_fetch_all("SELECT * FROM leave_types WHERE status = 'active' ORDER BY name");
}

function get_leave_type_by_id($id) {
    return db_fetch("SELECT * FROM leave_types WHERE id = ?", [$id]);
}

// Activity Logging
function log_activity($user_id, $action, $description, $ip_address = null) {
    if ($ip_address === null) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    db_insert('activity_logs', [
        'user_id' => $user_id,
        'action' => $action,
        'description' => $description,
        'ip_address' => $ip_address,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

// Utility Functions
function format_date($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function format_datetime($datetime, $format = 'M d, Y H:i') {
    return date($format, strtotime($datetime));
}

function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Statistics Functions
function get_dashboard_stats() {
    $stats = [];

    // Active users
    $stats['active_users'] = db_fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'];

    // Pending leave requests
    $stats['pending_leaves'] = db_fetch("SELECT COUNT(*) as count FROM leaves WHERE status = 'pending'")['count'];

    // Users on leave today
    $today = date('Y-m-d');
    $stats['users_on_leave_today'] = db_fetch("
        SELECT COUNT(*) as count FROM leaves
        WHERE status = 'approved' AND ? BETWEEN start_date AND end_date
    ", [$today])['count'];

    // Total leave days used this year
    $year = date('Y');
    $stats['total_leave_days'] = db_fetch("
        SELECT SUM(working_days) as total FROM leaves
        WHERE status = 'approved' AND YEAR(start_date) = ?
    ", [$year])['total'] ?? 0;

    return $stats;
}

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
