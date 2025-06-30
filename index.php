<?php
// index.php - Main Entry Point
// This is like the front door of our leave management system
// When someone visits the website, this file decides where to send them

require_once 'php/functions.php';

// Check if someone is already logged in
if (is_logged_in()) {
    // They're logged in! Send them to their appropriate homepage
    if (is_admin()) {
        redirect(APP_URL . '/admin/admin_dashboard.php'); // Admins get the admin panel
    } else {
        redirect(APP_URL . '/user/home.php'); // Regular employees get the user homepage
    }
} else {
    // They're not logged in, so send them to the login page first
    redirect(APP_URL . '/user/login.php');
}

// Note: This file never actually shows any content to the user
// It's just a traffic director that sends people to the right place
?>
