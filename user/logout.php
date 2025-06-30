<?php
/*
 * LOGOUT HANDLER - The Exit Door! 🚪
 * ==================================
 *
 * Hey there! This simple but important file handles logging users out of the system.
 * It's like the "exit door" of the application - it safely ends their session and
 * sends them back to the login page.
 *
 * WHAT THIS DOES:
 * - 🔒 Safely ends their login session (forgets they were logged in)
 * - 📝 Records their logout activity for security tracking
 * - 💬 Shows a friendly "goodbye" message
 * - 🔄 Redirects them back to the login page
 *
 * SECURITY FEATURES:
 * - Properly destroys their session data
 * - Logs the logout activity for audit trails
 * - Prevents any lingering access after logout
 *
 * This is a critical security function - it ensures that when someone logs out,
 * they're truly logged out and can't accidentally leave their session open! 🛡️
 */

// user/logout.php - Logout Handler

require_once '../php/functions.php';

// SET GOODBYE MESSAGE: Show a friendly confirmation that they logged out successfully
set_flash_message('You have been successfully logged out.', 'success');

// PERFORM LOGOUT: This function (defined in functions.php) handles all the logout logic:
// - Records the logout activity in the system logs
// - Destroys their session data (forgets they were logged in)
// - Redirects them to the login page
logout();
?>
