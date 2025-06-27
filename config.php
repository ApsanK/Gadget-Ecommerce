<?php
// Start session
session_start();

$host = 'localhost';
$dbname = 'ecommerce';
$username = 'root';
$password = '';

// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

define('KHALTI_PUBLIC_KEY', '1f4bda5a8d6049e08de0dc2315e4d164'); 
define('KHALTI_SECRET_KEY', 'fc513f598be14bddaa7f3974124e97af');
define('KHALTI_ENV', 'https://dev.khalti.com/api/v2/'); 
?>