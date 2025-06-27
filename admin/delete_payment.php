<?php
session_start();
include_once '../config.php';
$conn = $pdo;

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $payment_id = $_GET['id'];
    $query = "DELETE FROM user_payments WHERE payment_id = :payment_id";
    $stmt = $conn->prepare($query);
    $stmt->execute(['payment_id' => $payment_id]);
    header("Location: admin.php?action=view_payments&message=" . urlencode("Payment deleted successfully!"));
    exit();
} else {
    header("Location: admin.php?action=view_payments&message=" . urlencode("Invalid payment ID."));
    exit();
}
?>