<?php
session_start();
require_once 'config.php';
require_once 'header.php';
$conn = $pdo;

// Determine user or guest identifier and cart storage
$isLoggedIn = isset($_SESSION['user']);
$userId = $isLoggedIn ? $_SESSION['user']['user_id'] : 0;
if (!$isLoggedIn && !isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Log session for debugging
error_log("cart.php - Session ID: " . session_id() . ", User: " . ($isLoggedIn ? $userId : 'guest'));

$isAjax = isset($_SERVER['HTTP_AJAX_REQUEST']);

// Handle adding items
if (isset($_GET['add']) && !empty($_GET['add'])) {
    $productId = intval($_GET['add']);
    $checkStmt = $conn->prepare("SELECT product_id FROM products WHERE product_id = ? AND status = 'active'");
    $checkStmt->execute([$productId]);
    $product = $checkStmt->fetch();
    if (!$product) {
        $error = "Error: Product not found.";
        if ($isAjax) {
            echo $error;
            exit;
        } else {
            die("<p class=\"cart-error\">$error</p>");
        }
    }

    if ($isLoggedIn) {
        $cartStmt = $conn->prepare("SELECT quantity FROM orders_pending WHERE user_id = ? AND product_id = ? AND order_status = 'pending'");
        $cartStmt->execute([$userId, $productId]);
        if ($cartStmt->rowCount() > 0) {
            $currentQty = $cartStmt->fetchColumn();
            $newQty = $currentQty + 1;
            $updateStmt = $conn->prepare("UPDATE orders_pending SET quantity = ? WHERE user_id = ? AND product_id = ? AND order_status = 'pending'");
            $updateStmt->execute([$newQty, $userId, $productId]);
        } else {
            $invoiceNumber = 'INV-' . uniqid();
            $insertStmt = $conn->prepare("INSERT INTO orders_pending (user_id, invoice_number, product_id, quantity, order_status) VALUES (?, ?, ?, 1, 'pending')");
            $insertStmt->execute([$userId, $invoiceNumber, $productId]);
        }
    } else {
        if (!isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] = 1;
        } else {
            $_SESSION['cart'][$productId]++;
        }
    }

    if ($isAjax) {
        echo "Product added to cart.";
        exit;
    } else {
        header("Location: " . BASE_URL . "cart.php");
        exit;
    }
}

// Handle removing items
if (isset($_GET['remove']) && !empty($_GET['remove'])) {
    $productId = intval($_GET['remove']);
    if ($isLoggedIn) {
        $deleteStmt = $conn->prepare("DELETE FROM orders_pending WHERE user_id = ? AND product_id = ? AND order_status = 'pending'");
        $deleteStmt->execute([$userId, $productId]);
    } else {
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }
    }

    if ($isAjax) {
        echo "Product removed from cart.";
        exit;
    } else {
        header("Location: " . BASE_URL . "cart.php");
        exit;
    }
}

// Handle quantity updates
if (isset($_GET['update']) && !empty($_GET['product_id']) && isset($_GET['quantity'])) {
    $productId = intval($_GET['product_id']);
    $newQty = intval($_GET['quantity']);
    if ($newQty < 1) {
        $error = "Quantity must be at least 1.";
        if ($isAjax) {
            echo $error;
            exit;
        } else {
            die("<p class=\"cart-error\">$error</p>");
        }
    }

    if ($isLoggedIn) {
        $updateStmt = $conn->prepare("UPDATE orders_pending SET quantity = ? WHERE user_id = ? AND product_id = ? AND order_status = 'pending'");
        $updateStmt->execute([$newQty, $userId, $productId]);
    } else {
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] = $newQty;
        } else {
            $error = "Product not in cart.";
            if ($isAjax) {
                echo $error;
                exit;
            } else {
                die("<p class=\"cart-error\">$error</p>");
        }
    }
}

if ($isAjax) {
    echo "Quantity updated.";
    exit;
} else {
    header("Location: " . BASE_URL . "cart.php");
    exit;
}
}

// Fetch cart products
$cartItems = [];
$total = 0;
if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT op.*, p.product_title, p.product_price, p.product_image, c.category_title, b.brand_title 
                            FROM orders_pending op 
                            LEFT JOIN products p ON op.product_id = p.product_id AND p.status = 'active'
                            LEFT JOIN categories c ON p.category_id = c.category_id AND c.status = 'active'
                            LEFT JOIN brands b ON p.brand_id = b.brand_id AND b.status = 'active'
                            WHERE op.user_id = ? AND op.order_status = 'pending'");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cartItems as &$item) {
        $item['subtotal'] = $item['product_price'] * $item['quantity'];
        $total += $item['subtotal'];
    }
} else {
    $productIds = array_keys($_SESSION['cart'] ?? []);
    if (!empty($productIds)) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $conn->prepare("SELECT p.*, c.category_title, b.brand_title 
                                FROM products p 
                                LEFT JOIN categories c ON p.category_id = c.category_id AND c.status = 'active'
                                LEFT JOIN brands b ON p.brand_id = b.brand_id AND b.status = 'active'
                                WHERE p.product_id IN ($placeholders) AND p.status = 'active'");
        $stmt->execute($productIds);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cartItems as &$item) {
            $item['quantity'] = $_SESSION['cart'][$item['product_id']];
            $item['subtotal'] = $item['product_price'] * $item['quantity'];
            $total += $item['subtotal'];
        }
    }
}

// Function to generate cart HTML
function generateCartHtml($conn, $userId, $isLoggedIn, &$cartItems, $total) {
    ob_start();
    ?>
    <h2 class="cart-main-heading">Your Cart</h2>
    <?php if (empty($cartItems)): ?>
        <p class="cart-main-no-items">
            Your cart is empty. 
            <a href="<?php echo BASE_URL; ?>products.php" class="cart-continue-shopping">Continue Shopping</a>
        </p>
    <?php else: ?>
        <div class="cart-items-table">
            <div class="cart-item-header">
                <div>Product Details</div>
                <div>Price</div>
                <div class="cart-item-header-quantity">Quantity</div>
                <div class="cart-item-header-subtotal">Subtotal</div>
            </div>
            <?php foreach ($cartItems as $item): ?>
                <div class="cart-item">
                    <div class="cart-item-details">
                        <img src="<?php echo BASE_URL; ?>assets/images/<?php echo htmlspecialchars($item['product_image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['product_title']); ?>" 
                             class="cart-item-image" 
                             onerror="this.src='<?php echo BASE_URL; ?>assets/images/default.jpg';">
                        <div class="cart-item-info">
                            <h3 class="cart-item-title"><?php echo htmlspecialchars($item['product_title']); ?></h3>
                            <a href="?remove=<?php echo $item['product_id']; ?>" 
                               class="cart-item-remove">Remove</a>
                        </div>
                    </div>
                    <div class="cart-item-price"><?php echo format_currency($item['product_price']); ?></div>
                    <div class="cart-item-quantity-control">
                        <input type="number" class="cart-item-quantity" value="<?php echo $item['quantity']; ?>" 
                               data-product-id="<?php echo $item['product_id']; ?>" min="1">
                    </div>
                    <div class="cart-item-subtotal"><?php echo format_currency($item['subtotal']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="<?php echo BASE_URL; ?>products.php" class="cart-continue-shopping">Continue Shopping</a>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - E-Commerce</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
</head>
<body>
<main class="cart-main-container">
    <div class="cart-content-container">
        <div class="cart-wrapper">
            <div class="cart-grid">
                <div class="cart-items-container">
                    <?php echo generateCartHtml($conn, $isLoggedIn ? $userId : null, $isLoggedIn, $cartItems, $total); ?>
                </div>
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <div class="order-summary-item">
                        <span>Items (<?php echo count($cartItems); ?>)</span>
                        <span><?php echo format_currency($total); ?></span>
                    </div>
                    <div class="order-summary-item">
                        <span>Shipping Fee</span>
                        <span>Free</span>
                    </div>
                    <div class="order-summary-total">
                        <span>Total</span>
                        <span><?php echo format_currency($total); ?></span>
                    </div>
                    <?php if ($isLoggedIn): ?>
                        <a href="<?php echo BASE_URL; ?>checkout.php" class="order-summary-button">Checkout</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>login.php?redirect=<?php echo urlencode(BASE_URL . 'checkout.php'); ?>" class="order-summary-button">Login to Place Order</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>