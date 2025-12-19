<?php
// Start session
session_start();

$host = 'localhost';
$dbname = 'ecommerce';
$username = 'root';
$password = '';



try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection error. Please contact the administrator.');
}

function format_currency($amount) {
    return 'Rs. ' . number_format((float)$amount, 0);
}

// Define base URL
define('BASE_URL', '/ecommerce/');

define('KHALTI_PUBLIC_KEY', 'YOUR_PUBLIC_KEY'); 
define('KHALTI_SECRET_KEY', 'YOUR_SECRET_KEY');
define('KHALTI_ENV', 'YOUR_KHALTI_ENVIRONMENT'); 
?>