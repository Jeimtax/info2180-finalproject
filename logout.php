<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include auth functions
require_once 'includes/auth.php';

// Log the user out
logout();

// Redirect to login page
header('Location: login.php');
exit();
?>