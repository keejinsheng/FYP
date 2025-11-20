<?php
require_once '../../config/database.php';

// Check if session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Destroy all session data
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to admin login page
redirect('login.php');
exit;
?> 