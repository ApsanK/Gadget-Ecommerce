<?php
require_once 'config.php';
require_once 'header.php';
$conn = $pdo;

if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    header("Location: " . BASE_URL . "products.php");
    exit;
}

$productId = intval($_GET['product_id']);
$stmt = $conn->prepare("SELECT p.*, c.category_title, b.brand_title 
                        FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.category_id AND c.status = 'active'
                        LEFT JOIN brands b ON p.brand_id = b.brand_id AND b.status = 'active'
                        WHERE p.product_id = ? AND p.status = 'active'");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die('<p class="error">Product not found or inactive.</p>');
}

// Mock rating data
$rating = 4;
$reviewCount = 1;

// Mock original price (20% discount)
$originalPrice = $product['product_price'] * 1.2;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_title']); ?> - E-Commerce</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
</head>
<body>
<main class="products-description-main">
    <nav class="products-description-breadcrumb">
        <a href="<?php echo BASE_URL; ?>index.php">Home</a> >
        <a href="<?php echo BASE_URL; ?>products.php?category=<?php echo urlencode($product['category_id']); ?>">
            <?php echo htmlspecialchars($product['category_title']); ?>
        </a> >
        <span><?php echo htmlspecialchars($product['product_title']); ?></span>
    </nav>

    <div class="products-description-container">
        <div class="products-description-image-section">
            <img src="<?php echo BASE_URL; ?>assets/images/<?php echo htmlspecialchars($product['product_image']); ?>" 
                 alt="<?php echo htmlspecialchars($product['product_title']); ?>" 
                 class="products-description-image" 
                 onerror="this.src='<?php echo BASE_URL; ?>assets/images/default.jpg';">
            
        </div>

        <div class="products-description-details-section">
            <h1 class="products-description-title"><?php echo htmlspecialchars($product['product_title']); ?></h1>
            <div class="products-description-rating">
                <div class="stars">
                    <?php
                    $fullStars = floor($rating);
                    $halfStar = $rating - $fullStars >= 0.5 ? 1 : 0;
                    $emptyStars = 5 - $fullStars - $halfStar;
                    for ($i = 0; $i < $fullStars; $i++) {
                        echo '<i class="fas fa-star"></i>';
                    }
                    if ($halfStar) {
                        echo '<i class="fas fa-star-half-alt"></i>';
                    }
                    for ($i = 0; $i < $emptyStars; $i++) {
                        echo '<i class="far fa-star"></i>';
                    }
                    ?>
                </div>
                <span>(<?php echo $reviewCount; ?> reviews)</span>
            </div>
            <p class="products-description-details"><?php echo htmlspecialchars($product['product_description']); ?></p>
            <div class="products-description-price">
                <span><?php echo format_currency($product['product_price']); ?></span>
                <span class="original-price"><?php echo format_currency($originalPrice); ?></span>
            </div>
            <div class="products-description-meta">
                <p><strong>Brand:</strong> <?php echo htmlspecialchars($product['brand_title']); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category_title']); ?></p>
            </div>
            <div class="products-description-actions">
                <button class="add-to-cart" data-product-id="<?php echo $product['product_id']; ?>">Add to Cart</button>
                <button class="buy-now" data-product-id="<?php echo $product['product_id']; ?>">Buy Now</button>
            </div>
            <label class="compare-checkbox">
                <input type="checkbox" class="compare-product" data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>">Compare
            </label>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>