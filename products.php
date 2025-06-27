<?php
require_once 'config.php';
require_once 'header.php';
$conn = $pdo;

// Validate sort parameter
$validSorts = [
    'default' => 'p.product_id ASC',
    'price_asc' => 'p.product_price ASC',
    'price_desc' => 'p.product_price DESC',
    'name_asc' => 'p.product_title ASC',
    'name_desc' => 'p.product_title DESC'
];
$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $validSorts) ? $_GET['sort'] : 'default';
$orderBy = $validSorts[$sort];

// Fetch all categories with status 'active'
$categoryStmt = $conn->prepare("SELECT category_id, category_title FROM categories WHERE status = 'active'");
$categoryStmt->execute();
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all brands with status 'active'
$brandStmt = $conn->prepare("SELECT brand_id, brand_title FROM brands WHERE status = 'active'");
$brandStmt->execute();
$brands = $brandStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle search, category, and brand filters via GET
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$selectedCategories = isset($_GET['category']) && is_array($_GET['category']) ? array_map('intval', $_GET['category']) : [];
$selectedBrands = isset($_GET['brand']) && is_array($_GET['brand']) ? array_map('intval', $_GET['brand']) : [];

// Build the SQL query dynamically
$sql = "SELECT p.*, c.category_title, b.brand_title, p.product_description 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id AND c.status = 'active'
        LEFT JOIN brands b ON p.brand_id = b.brand_id AND b.status = 'active'
        WHERE p.status = 'active'";
$params = [];

if (!empty($selectedCategories)) {
    $categoryPlaceholders = implode(',', array_fill(0, count($selectedCategories), '?'));
    $sql .= " AND p.category_id IN ($categoryPlaceholders)";
    $params = array_merge($params, $selectedCategories);
}
if (!empty($selectedBrands)) {
    $brandPlaceholders = implode(',', array_fill(0, count($selectedBrands), '?'));
    $sql .= " AND p.brand_id IN ($brandPlaceholders)";
    $params = array_merge($params, $selectedBrands);
}
if ($search) {
    $sql .= " AND (p.product_title LIKE ? OR p.product_description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY p.product_id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - E-Commerce</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
</head>
<body>
<main class="products-main-container">
    <div class="products-sidebar-container">
        <h2 class="products-sidebar-heading"></h2>
        <form id="filter-form" method="get" action="">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
            <div class="products-sidebar-section">
            <a href="<?php echo BASE_URL; ?>compare.php" class="compare-now-btn">Compare Products</a>
                <h3 class="products-sidebar-subheading">Categories</h3>
                <?php foreach ($categories as $category): ?>
                    <label class="products-sidebar-checkbox">
                        <input type="checkbox" name="category[]" value="<?php echo htmlspecialchars($category['category_id']); ?>"
                            <?php echo in_array($category['category_id'], $selectedCategories) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($category['category_title']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="products-sidebar-section">
                <h3 class="products-sidebar-subheading">Brands</h3>
                <?php foreach ($brands as $brand): ?>
                    <label class="products-sidebar-checkbox">
                        <input type="checkbox" name="brand[]" value="<?php echo htmlspecialchars($brand['brand_id']); ?>"
                            <?php echo in_array($brand['brand_id'], $selectedBrands) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($brand['brand_title']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
    <div class="products-content-container">
        <div class="products-header">
            <h2 class="products-heading">All Products</h2>
            <form class="sort-form" method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <select name="sort" class="sort-select" onchange="this.form.submit()">
                    <option value="default" <?php echo $sort === 'default' ? 'selected' : ''; ?>>Default</option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name: A-Z</option>
                    <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name: Z-A</option>
                </select>
            </form>
        </div>
        <div class="products-main-products-grid">
            <?php if (empty($products)): ?>
                <p class="products-main-no-results">No products found.</p>
            <?php else: ?>
                <?php foreach ($products as $product): 
                    $imagePath = BASE_URL . 'assets/images/' . htmlspecialchars($product['product_image']);
                    
                ?>
                    <div class="products-main-product" data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>">
                        <div class="image-wrapper">
                            <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['product_title']); ?>" class="products-main-product-image" 
                                 onerror="this.src='<?php echo BASE_URL; ?>assets/images/default.jpg';">
                        </div>
                        <h3 class="products-main-product-title"><?php echo htmlspecialchars($product['product_title']); ?></h3>
                        <p class="our-product-description"><?php echo htmlspecialchars(substr($product['product_description'], 0, 60)) . (strlen($product['product_description']) > 60 ? '...' : ''); ?></p>
                        <div class="our-product-rating">★★★★☆ (<?php echo number_format($product['product_rating'] ?? 4, 1); ?>)</div>
                        <p class="products-main-product-price"><?php echo format_currency($product['product_price']); ?></p>
                        <button class="add-to-cart" data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>">Add to Cart</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
<script>
document.querySelectorAll('.products-sidebar-checkbox input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', () => {
        document.getElementById('filter-form').submit();
    });
});
</script>
</body>
</html>