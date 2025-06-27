<?php
     session_start();
     include_once '../config.php';
     $conn = $pdo;

     if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
         header("Location: ../index.php");
         exit();
     }

     if (isset($_GET['id'])) {
         $order_id = $_GET['id'];
         $query = "DELETE FROM user_orders WHERE order_id = :order_id";
         $stmt = $conn->prepare($query);
         $stmt->execute(['order_id' => $order_id]);
         header("Location: admin.php?action=view_orders&message=" . urlencode("Order deleted successfully!"));
         exit();
     } else {
         header("Location: admin.php?action=view_orders&message=" . urlencode("Invalid order ID."));
         exit();
     }
     ?>