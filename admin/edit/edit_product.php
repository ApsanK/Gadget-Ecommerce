<?php
include_once '../config.php';

$product = null;
if (isset($_GET['id'])) {
    $product_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($product_id) {
        try {
            $query = "SELECT * FROM products WHERE product_id = :product_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['product_id' => $product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Error loading product: " . htmlspecialchars($e->getMessage());
            file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - Edit Product: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}
?>

<div class="admin-form-wrapper">
    <h2 class="edit-product-title">Edit Product</h2>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php elseif ($product): ?>
        <form action="admin_handlers.php?action=edit_product" method="post" enctype="multipart/form-data" class="edit-product-form">
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['product_id']); ?>">
            <label for="product_title">Product Title</label>
            <input type="text" name="product_title" id="product_title" value="<?= htmlspecialchars($product['product_title']); ?>" required>
            <label for="product_description">Product Description</label>
            <textarea name="product_description" id="product_description" required><?= htmlspecialchars($product['product_description']); ?></textarea>
            <label for="product_category">Select a Category</label>
            <select name="product_category" id="product_category" required>
                <option value="">Select a Category</option>
                <?php
                try {
                    $category_query = "SELECT * FROM categories";
                    $category_stmt = $pdo->query($category_query);
                    $categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($categories as $category) {
                        $selected = ($category['category_id'] == $product['category_id']) ? 'selected' : '';
                        echo "<option value='{$category['category_id']}' $selected>" . htmlspecialchars($category['category_title']) . "</option>";
                    }
                } catch (PDOException $e) {
                    echo "<option value=''>Error loading categories</option>";
                    file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - Edit Product Categories: " . $e->getMessage() . "\n", FILE_APPEND);
                }
                ?>
            </select>
            <label for="product_brand">Select a Brand</label>
            <select name="product_brand" id="product_brand" required>
                <option value="">Select a Brand</option>
                <?php
                try {
                    $brand_query = "SELECT * FROM brands";
                    $brand_stmt = $pdo->query($brand_query);
                    $brands = $brand_stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($brands as $brand) {
                        $selected = ($brand['brand_id'] == $product['brand_id']) ? 'selected' : '';
                        echo "<option value='{$brand['brand_id']}' $selected>" . htmlspecialchars($brand['brand_title']) . "</option>";
                    }
                } catch (PDOException $e) {
                    echo "<option value=''>Error loading brands</option>";
                    file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - Edit Product Brands: " . $e->getMessage() . "\n", FILE_APPEND);
                }
                ?>
            </select>
            <label for="product_image">Product Image</label>
            <input type="file" name="product_image" id="product_image">
            <label for="product_price">Product Price</label>
            <input type="number" name="product_price" id="product_price" value="<?= htmlspecialchars($product['product_price']); ?>" step="0.01" required>
            <label for="product_status">Product Status</label>
            <select name="product_status" id="product_status">
                <option value="active" <?= ($product['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?= ($product['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
            <button type="submit" name="update_product" class="edit-product-submit">Update Product</button>
            <a href="<?php echo BASE_URL; ?>admin/admin.php?action=view_products" class="cancel-btn">Cancel</a>
        </form>
    <?php else: ?>
        <a href="<?php echo BASE_URL; ?>admin/admin.php?action=view_products" class="cancel-btn">Back to Products</a>
    <?php endif; ?>
</div>