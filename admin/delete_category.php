<?php
include_once('../config.php'); // Database connection
$conn = $pdo;

if (isset($_GET['id'])) {
    $category_id = $_GET['id'];

    // Check if the category exists
    $query = "SELECT * FROM categories WHERE category_id = :category_id";
    $stmt = $conn->prepare($query);
    $stmt->execute(['category_id' => $category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($category) {
        // Delete category
        $delete_query = "DELETE FROM categories WHERE category_id = :category_id";
        $delete_stmt = $conn->prepare($delete_query);
        if ($delete_stmt->execute(['category_id' => $category_id])) {
            header("Location: admin.php?action=view_categories&message=" . urlencode("Category deleted successfully!"));
        } else {
            header("Location: admin.php?action=view_categories&message=" . urlencode("Error deleting category."));
        }
    } else {
        header("Location: admin.php?action=view_categories&message=" . urlencode("Category not found!"));
    }
} else {
    header("Location: admin.php?action=view_categories&message=" . urlencode("Invalid request."));
}
exit();
?>

