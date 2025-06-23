<?php
// index.php - Main Entry Point

require_once 'php/functions.php';

// Check if user is logged in
if (is_logged_in()) {
    // Redirect based on user role
    if (is_admin()) {
        redirect(APP_URL . '/admin/admin_dashboard.php');
    } else {
        redirect(APP_URL . '/user/home.php');
    }
} else {
    // Redirect to login page
    redirect(APP_URL . '/user/login.php');
}
?>
