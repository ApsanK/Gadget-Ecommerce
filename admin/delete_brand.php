<?php
include_once('../config.php'); // Database connection
$conn = $pdo;

if (isset($_GET['id'])) {
    $brand_id = $_GET['id'];

    // Check if the brand exists before deleting
    $checkQuery = "SELECT * FROM brands WHERE brand_id = :brand_id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':brand_id', $brand_id, PDO::PARAM_INT);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        // Delete brand
        $deleteQuery = "DELETE FROM brands WHERE brand_id = :brand_id";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bindParam(':brand_id', $brand_id, PDO::PARAM_INT);

        if ($deleteStmt->execute()) {
            header("Location: admin.php?action=view_brands&message=" . urlencode("Brand deleted successfully!"));
        } else {
            header("Location: admin.php?action=view_brands&message=" . urlencode("Error deleting brand."));
        }
    } else {
        header("Location: admin.php?action=view_brands&message=" . urlencode("Brand not found."));
    }
} else {
    header("Location: admin.php?action=view_brands&message=" . urlencode("Invalid request."));
}
exit();
?>
