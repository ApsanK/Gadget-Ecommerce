<?php

require_once 'config.php';
require_once 'header.php';
$conn = $pdo;

// Fetch a limited set of products
$sql = "SELECT p.*, c.category_title, b.brand_title 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id AND c.status = 'active'
        LEFT JOIN brands b ON p.brand_id = b.brand_id AND b.status = 'active'
        WHERE p.status = 'active' 
        ORDER BY p.product_id DESC 
        LIMIT 6";
$stmt = $conn->prepare($sql);
$stmt->execute();
$ourProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Placeholder categories data
$categories = [
    ['category_id' => 1, 'category_title' => 'Headphones', 'icon' => 'fa-headphones', 'svg_file' => ''],
    ['category_id' => 2, 'category_title' => 'Laptops', 'icon' => 'fa-laptop', 'svg_file' => ''],
    ['category_id' => 3, 'category_title' => 'Smartphones', 'icon' => 'fa-mobile-alt', 'svg_file' => ''],
    ['category_id' => 4, 'category_title' => 'Tablets', 'icon' => 'fa-tablet-alt', 'svg_file' => ''],
    ['category_id' => 5, 'category_title' => 'Cameras', 'icon' => 'fa-camera', 'svg_file' => ''],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
</head>
<body>
<main>
    <div class="hero-section">
        <div class="hero-carousel" id="heroCarousel">
            <!-- Slide 1: Headphones -->
            <div class="hero-slide">
                <div class="hero-content">
                    <span class="offer">Limited Time Offer 15% Off</span>
                    <h1>Experience Pure Sound – Your Perfect Headphones Await!</h1>
                    <div class="hero-buttons">
                        <a href="products.php" class="cta-button primary">Buy now</a>
                        <a href="#" class="cta-button secondary">Find more <span>→</span></a>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="assets/images/p-3.png" alt="Headphones">
                </div>
            </div>
            <!-- Slide 2: Laptop -->
            <div class="hero-slide">
                <div class="hero-content">
                    <span class="offer">Limited Time Offer 15% Off</span>
                    <h1>Power Up Your Work – Discover Our Latest Laptops!</h1>
                    <div class="hero-buttons">
                        <a href="products.php" class="cta-button primary">Buy now</a>
                        <a href="#" class="cta-button secondary">Find more <span>→</span></a>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="assets/images/BGalienware.avif" alt="Laptop">
                </div>
            </div>
            <!-- Slide 3: Phone -->
            <div class="hero-slide">
                <div class="hero-content">
                    <span class="offer">Limited Time Offer 15% Off</span>
                    <h1>Stay Connected – Explore Our Cutting-Edge Phones!</h1>
                    <div class="hero-buttons">
                        <a href="products.php" class="cta-button primary">Buy now</a>
                        <a href="#" class="cta-button secondary">Find more <span>→</span></a>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="assets/images/BGphone3.webp" alt="Phone">
                </div>
            </div>
        </div>
        <!-- Pagination Dots -->
        <div class="hero-pagination" id="heroPagination">
            <span class="dot active" data-slide="0"></span>
            <span class="dot" data-slide="1"></span>
            <span class="dot" data-slide="2"></span>
        </div>
    </div>

    <!-- Categories Section -->
    <section class="categories-section">
        <h2>Shop by Category</h2>
        <div class="categories-list">
            <?php if (empty($categories)): ?>
                <p>No categories available at this time.</p>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                    <a href="products.php?category_id=<?php echo htmlspecialchars($category['category_id']); ?>" class="category-item">
                        <div class="category-icon-wrapper">
                            <?php if (!empty($category['svg_file'])): ?>
                                <img src="<?php echo BASE_URL; ?>assets/icons/<?php echo htmlspecialchars($category['svg_file']); ?>" alt="<?php echo htmlspecialchars($category['category_title']); ?> icon" class="category-svg-img">
                            <?php else: ?>
                                <i class="fas <?php echo htmlspecialchars($category['icon']); ?>"></i>
                            <?php endif; ?>
                        </div>
                        <h3 class="category-title"><?php echo htmlspecialchars($category['category_title']); ?></h3>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Our Products Section -->
    <section class="our-products-section">
        <h2>Popular Products</h2>
        <div class="our-products-list">
            <?php if (empty($ourProducts)): ?>
                <p>No products available at this time.</p>
            <?php else: ?>
                <?php foreach ($ourProducts as $product): ?>
                    <div class="our-product-item" data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>">
                        <div class="image-wrapper">
                            <img src="assets/images/<?php echo htmlspecialchars($product['product_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_title']); ?>" 
                                 onerror="this.src='assets/images/default.jpg';">
                        </div>
                        <h3 class="products-main-product-title"><?php echo htmlspecialchars($product['product_title']); ?></h3>
                        <p class="our-product-description"><?php echo htmlspecialchars(substr($product['product_description'] ?? 'No description', 0, 50)) . '...'; ?></p>
                        <div class="our-product-rating">★★★★☆ (<?php echo number_format($product['product_rating'] ?? 4, 1); ?>)</div>
                        <p class="products-main-product-price">Rs.<?php echo number_format($product['product_price']); ?></p>
                        <button class="add-to-cart" data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>">Add to Cart</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<!-- Back to Top Button -->
<button id="back-to-top"><i class="fa-solid fa-arrow-up"></i></button>

<?php include 'footer.php'; ?>
<script src="assets/js/script.js"></script>
</body>
</html>