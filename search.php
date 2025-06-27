<?php
require_once 'config.php';
require_once 'header.php';

$conn = $pdo;

// Handle search query
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT p.*, c.category_title, b.brand_title 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN brands b ON p.brand_id = b.brand_id 
        WHERE p.status = 'active' 
        AND (p.product_title LIKE :search OR p.product_description LIKE :search)";
$stmt = $conn->prepare($sql);
$stmt->execute([':search' => "%$search%"]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="products-main-container">
    <h2 class="products-main-heading">Search Results for "<?php echo htmlspecialchars($search); ?>"</h2>
    <section class="our-products-section">
        <div class="our-products-list">
            <?php if (empty($products)): ?>
                <p class="products-main-no-results">No products found.</p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="our-product-item" data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>">
                        <div class="image-wrapper">
                            <img src="<?php echo BASE_URL; ?>assets/images/<?php echo htmlspecialchars($product['product_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_title']); ?>" 
                                 onerror="this.src='<?php echo BASE_URL; ?>assets/images/default.jpg';">
                        </div>
                        <h3 class="products-main-product-title"><?php echo htmlspecialchars($product['product_title']); ?></h3>
                        <p class="our-product-description"><?php echo htmlspecialchars(substr($product['product_description'] ?? 'No description', 0, 50)) . '...'; ?></p>
                        <div class="our-product-rating">★★★★☆ (<?php echo number_format($product['product_rating'] ?? 4.5, 1); ?>)</div>
                        <p class="products-main-product-price">Rs <?php echo number_format($product['product_price'], 2); ?></p>
                        <button class="add-to-cart" data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>">Add to Cart</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>