<?php
// Start session
session_start();

// Clear session variables
$_SESSION = [];

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Redirect to login page
require_once 'config.php';
header("Location: " . BASE_URL . "login.php");
exit;
?>