<?php
// Set session name for admin
session_name('ECOMMERCE_ADMIN_SESSION');


session_start();

// Unset all session variables
$_SESSION = array();


// Destroy the session
session_destroy();

// Clear any existing output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Redirect to login page with a success message
header("Location: admin_login.php?message=You+have+been+successfully+logged+out");
exit;
?>