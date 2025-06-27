<?php
require_once 'config.php';
require_once 'header.php';
$conn = $pdo;

$errors = [];
$cartItems = [];
$total = 0;
$isLoggedIn = isset($_SESSION['user']);
$userId = $isLoggedIn ? $_SESSION['user']['user_id'] : null;

// Fetch cart products
if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT op.*, p.product_title, p.product_price, p.product_image 
                            FROM orders_pending op 
                            LEFT JOIN products p ON op.product_id = p.product_id AND p.status = 'active'
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
        $stmt = $conn->prepare("SELECT p.* 
                                FROM products p 
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

// Check if cart is empty
if (empty($cartItems)) {
    $errors[] = "Your cart is empty. Please add items to proceed.";
}

// Initialize checkout details
$checkoutDetails = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => ''
];

// Pre-fill details
if ($isLoggedIn) {
    $checkoutDetails['name'] = $_SESSION['user']['full_name'] ?? '';
    $checkoutDetails['email'] = $_SESSION['user']['user_email'] ?? '';
    $checkoutDetails['phone'] = $_SESSION['user']['user_mobile'] ?? '';
    $checkoutDetails['address'] = $_SESSION['user']['user_address'] ?? '';
} elseif (isset($_SESSION['customer_info'])) {
    $checkoutDetails['name'] = $_SESSION['customer_info']['name'] ?? '';
    $checkoutDetails['email'] = $_SESSION['customer_info']['email'] ?? '';
    $checkoutDetails['phone'] = $_SESSION['customer_info']['phone'] ?? '';
    $checkoutDetails['address'] = $_SESSION['customer_info']['address'] ?? '';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Basic validation
    if (empty($name)) {
        $errors[] = "Full name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }
    if (empty($address)) {
        $errors[] = "Address is required.";
    }

    // Process order if no errors
    if (empty($errors)) {
        // Insert into user_orders
        $orderStmt = $conn->prepare("INSERT INTO user_orders (user_id, full_name, email, phone, address, order_date, total_amount, order_status) 
                                     VALUES (?, ?, ?, ?, ?, NOW(), ?, 'pending')");
        $orderStmt->execute([$isLoggedIn ? $userId : null, $name, $email, $phone, $address, $total]);
        $orderId = $conn->lastInsertId();

        // Insert into order_items
        foreach ($cartItems as $item) {
            $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                        VALUES (?, ?, ?, ?)");
            $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['product_price']]);
        }

        // Update user data if logged in
        if ($isLoggedIn) {
            $updateStmt = $conn->prepare("UPDATE users SET user_email = ?, user_mobile = ?, user_address = ? WHERE user_id = ?");
            $updateStmt->execute([$email, $phone, $address, $userId]);
            $_SESSION['user']['user_email'] = $email;
            $_SESSION['user']['user_mobile'] = $phone;
            $_SESSION['user']['user_address'] = $address;
        }

        // Store order details in session
        $_SESSION['pending_order_id'] = $orderId;
        $_SESSION['order_total'] = $total;
        $_SESSION['customer_info'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address
        ];

        // Redirect to payment page
        header("Location: " . BASE_URL . "payment.php");
        exit;
    }
}

// Function to generate cart items HTML (read-only)
function generateCheckoutCartHtml($cartItems, $total) {
    ob_start();
    ?>
    <h2 class="cart-main-heading">Your Cart</h2>
    <?php if (empty($cartItems)): ?>
        <p class="cart-main-no-items">
            <p class="cart-error">Your cart is empty.</p>
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
                        </div>
                    </div>
                    <div class="cart-item-price"><?php echo format_currency($item['product_price']); ?></div>
                    <div class="cart-item-quantity-control">
                        <span class="cart-item-quantity-static"><?php echo $item['quantity']; ?></span>
                    </div>
                    <div class="cart-item-subtotal"><?php echo format_currency($item['subtotal']); ?></div>
                </div>
            <?php endforeach; ?>
            <div class="cart-item checkout-total">
                <div></div>
                <div></div>
                <div></div>
                <div class="cart-item-subtotal">Total: <?php echo format_currency($total); ?></div>
            </div>
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
    <title>Checkout Order - E-Commerce</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
</head>
<body>
<main class="cart-main-container">
    <div class="cart-content-container">
        <div class="cart-wrapper">
            <div class="cart-grid">
                <div class="cart-items-container">
                    <?php echo generateCheckoutCartHtml($cartItems, $total); ?>
                </div>
                <div class="order-summary">
                    <h3 class="cart-main-heading">Checkout Details</h3>
                    <form method="POST" action="checkout.php">
                        <div class="checkout-form-group">
                            <label for="name">Full Name:</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($checkoutDetails['name']); ?>" required>
                        </div>
                        <div class="checkout-form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($checkoutDetails['email']); ?>" required>
                        </div>
                        <div class="checkout-form-group">
                            <label for="phone">Phone Number:</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($checkoutDetails['phone']); ?>" required>
                        </div>
                        <div class="checkout-form-group">
                            <label for="address">Address:</label>
                            <textarea id="address" name="address" required><?php echo htmlspecialchars($checkoutDetails['address']); ?></textarea>
                        </div>
                        <button type="submit" class="order-summary-button">Proceed to Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>