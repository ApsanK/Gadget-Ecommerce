<?php
include_once('../config.php'); // Include connection
$conn = $pdo;

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];

    // Check if product exists
    $check_query = "SELECT product_image FROM products WHERE product_id = :product_id";
    $stmt = $conn->prepare($check_query);
    $stmt->execute(['product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Delete product image file if it exists
        $image_path = "../product_images/" . $product['product_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        // Delete product from database
        $delete_query = "DELETE FROM products WHERE product_id = :product_id";
        $stmt = $conn->prepare($delete_query);
        if ($stmt->execute(['product_id' => $product_id])) {
            header("Location: admin.php?action=view_products&message=" . urlencode("Product deleted successfully!"));
        } else {
            header("Location: admin.php?action=view_products&message=" . urlencode("Error deleting product."));
        }
    } else {
        header("Location: admin.php?action=view_products&message=" . urlencode("Product not found."));
    }
} else {
    header("Location: admin.php?action=view_products&message=" . urlencode("Invalid request."));
}
exit();
?>

