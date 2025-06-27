<?php
require_once 'config.php';
require_once 'header.php';
$conn = $pdo;

// Get selected product IDs from GET parameter
$productIds = isset($_GET['products']) ? array_map('intval', explode(',', $_GET['products'])) : [];

// Limit to a maximum of 4 products
$productIds = array_slice($productIds, 0, 4);

// Fetch product details
$products = [];
if (!empty($productIds)) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $conn->prepare("SELECT p.product_id, p.product_title, p.product_description, p.product_price, 
                                   p.product_image, c.category_title, b.brand_title 
                            FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.category_id AND c.status = 'active'
                            LEFT JOIN brands b ON p.brand_id = b.brand_id AND b.status = 'active'
                            WHERE p.product_id IN ($placeholders) AND p.status = 'active'");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compare Products - E-Commerce</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
</head>
<body>
<main class="compare-main-container">
    <h2 class="compare-heading">Compare Products</h2>
    <?php if (empty($products)): ?>
        <p class="compare-no-products">No products selected for comparison.</p>
    <?php else: ?>
        <div class="compare-table-container">
            <table class="compare-table">
                <thead>
                    <tr>
                        <th class="compare-attribute"></th>
                        <?php foreach ($products as $product): ?>
                            <th class="compare-product-column">
                                <div class="compare-product-header">
                                    <img src="<?php echo BASE_URL . 'assets/images/' . htmlspecialchars($product['product_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['product_title']); ?>" 
                                         class="compare-product-image" 
                                         onerror="this.src='<?php echo BASE_URL; ?>assets/images/default.jpg';">
                                    <h3><?php echo htmlspecialchars($product['product_title']); ?></h3>
                                    <button class="compare-remove-btn" data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>">Remove</button>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Price</strong></td>
                        <?php foreach ($products as $product): ?>
                            <td><?php echo format_currency($product['product_price']); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td><strong>Brand</strong></td>
                        <?php foreach ($products as $product): ?>
                            <td><?php echo htmlspecialchars($product['brand_title'] ?? 'N/A'); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td><strong>Category</strong></td>
                        <?php foreach ($products as $product): ?>
                            <td><?php echo htmlspecialchars($product['category_title'] ?? 'N/A'); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td><strong>Rating</strong></td>
                        <?php foreach ($products as $product): ?>
                            <td>★★★★☆ (4.0)</td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td><strong>Description</strong></td>
                        <?php foreach ($products as $product): ?>
                            <td><?php echo htmlspecialchars($product['product_description']); ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
            <div class="compare-actions">
                <a href="<?php echo BASE_URL; ?>products.php" class="compare-continue-btn">Continue Shopping</a>
                <button class="compare-clear-btn">Clear All</button>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>