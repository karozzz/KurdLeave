<?php
// user/logout.php - Logout Handler

require_once '../php/functions.php';

// Set success message
set_flash_message('You have been successfully logged out.', 'success');

// Perform logout
logout();
?>
