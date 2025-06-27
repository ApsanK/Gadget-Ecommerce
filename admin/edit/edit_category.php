<?php
include_once '../config.php';

$category = null;
if (isset($_GET['id'])) {
    $category_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($category_id) {
        try {
            $query = "SELECT * FROM categories WHERE category_id = :category_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['category_id' => $category_id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Error loading category: " . htmlspecialchars($e->getMessage());
            file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - Edit Category: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}
?>

<div class="admin-form-wrapper">
    <h2 class="edit-category-title">Edit Category</h2>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php elseif ($category): ?>
        <form action="admin_handlers.php?action=edit_category" method="post" class="edit-category-form">
            <input type="hidden" name="category_id" value="<?= htmlspecialchars($category['category_id']); ?>">
            <label for="category_title">Category Title</label>
            <input type="text" name="category_title" id="category_title" value="<?= htmlspecialchars($category['category_title']); ?>" required>
            <button type="submit" name="update_category" class="edit-category-submit">Update Category</button>
            <a href="<?php echo BASE_URL; ?>admin/admin.php?action=view_categories" class="cancel-btn">Cancel</a>
        </form>
    <?php else: ?>
        <a href="<?php echo BASE_URL; ?>admin/admin.php?action=view_categories" class="cancel-btn">Back to Categories</a>
    <?php endif; ?>
</div>