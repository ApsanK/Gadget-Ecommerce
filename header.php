<?php
require_once 'config.php';
$conn = $pdo;

$isLoggedIn = isset($_SESSION['user']);

// Calculate cart count
$cartCount = 0;
if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM orders_pending WHERE user_id = ? AND order_status = 'pending'");
    $stmt->execute([$_SESSION['user']['user_id']]);
    $cartCount = $stmt->fetchColumn() ?: 0;
} else {
    $cartCount = isset($_SESSION['guest_cart']) ? array_sum($_SESSION['guest_cart']) : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <script>
        window.baseUrl = '<?php echo BASE_URL; ?>';
    </script>
</head>
<body>
<div id="search-error-overlay" class="alert-overlay alert-warning" style="display: none;">
    <p>Please fill search form.</p>
</div>
<header>
    <div class="header-container">
        <div class="logo">
            <a href="<?php echo BASE_URL; ?>index.php">
                <img src="<?php echo BASE_URL; ?>assets/images/Techhomelogo.png" alt="Logo" style="height:40px;">
            </a>
        </div>
        <form id="inline-search-form" action="<?php echo BASE_URL; ?>search.php" method="get" onsubmit="return validateSearch()">
            <input type="text" class="inline-search-input" placeholder="Search products..." name="q">
            <button type="submit" class="inline-search-btn"><i class="fas fa-search"></i></button>
        </form>
        <nav class="primary-nav">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <a href="<?php echo BASE_URL; ?>products.php">Shop</a>
            <a href="<?php echo BASE_URL; ?>about.php">About Us</a>
            <a href="<?php echo BASE_URL; ?>cart.php">
                <div class="cart-icon-wrapper">
                    <i class="fas fa-shopping-cart cart-icon"></i>
                    <span id="cart-count"><?php echo $cartCount; ?></span>
                </div>
            </a>
            <?php if ($isLoggedIn): ?>
                <div class="profile-dropdown">
                    <a href="#" class="profile-btn">
                        <i class="fas fa-user profile-icon"></i>
                    </a>
                    <div class="profile-dropdown-content">
                        <a href="<?php echo BASE_URL; ?>user/profile.php">Profile</a>
                        <a href="<?php echo BASE_URL; ?>user/order_history.php">Order History</a>
                        <a href="<?php echo BASE_URL; ?>user/edit_password.php">Change Password</a>
                        <a href="<?php echo BASE_URL; ?>logout.php" class="logout-link">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>login.php">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<script>
function validateSearch() {
    const searchInput = document.querySelector('.inline-search-input').value.trim();
    const errorOverlay = document.getElementById('search-error-overlay');
    if (searchInput === '') {
        errorOverlay.style.display = 'flex';
        setTimeout(() => {
            errorOverlay.style.display = 'none';
        }, 3000);
        return false;
    }
    errorOverlay.style.display = 'none';
    return true;
}
</script>
<script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>