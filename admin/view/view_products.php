<?php
include_once '../config.php';

try {
    $stmt = $pdo->query("SELECT p.*, c.category_title, b.brand_title 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.category_id 
                         LEFT JOIN brands b ON p.brand_id = b.brand_id");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - View Products: " . $e->getMessage() . "\n", FILE_APPEND);
    $products = [];
}
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Products</h2>
        <a href="<?php echo BASE_URL; ?>admin/admin.php?action=add_product" class="btn">Add Product</a>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Title</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="7">No products found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['product_id']); ?></td>
                        <td><img src="../assets/images/<?= htmlspecialchars($product['product_image']); ?>" alt="Product Image"></td>
                        <td><?= htmlspecialchars($product['product_title']); ?></td>
                        <td><?= htmlspecialchars($product['category_title']); ?></td>
                        <td><?= htmlspecialchars($product['brand_title']); ?></td>
                        <td class="currency"><?= format_currency((float)$product['product_price']); ?></td>
                        <td class="action-buttons">
                            <a href="<?php echo BASE_URL; ?>admin/admin.php?action=edit_product&id=<?= htmlspecialchars($product['product_id']); ?>" class="view-product-edit"><i class="fas fa-edit"></i></a>
                            <a href="../admin_handlers.php?action=delete_product&product_id=<?= htmlspecialchars($product['product_id']); ?>" class="view-product-delete" onclick="return confirm('Are you sure you want to delete this product?');"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>