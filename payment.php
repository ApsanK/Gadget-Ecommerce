<?php
// Start session
session_start();

require_once 'config.php';
require_once 'header.php';

// Initialize variables
$errors = [];
$orderId = $_SESSION['pending_order_id'] ?? null;
$total = $_SESSION['order_total'] ?? 0;
$customerInfo = $_SESSION['customer_info'] ?? null;
$cartItems = [];
$isLoggedIn = isset($_SESSION['user']);
$userId = $isLoggedIn ? $_SESSION['user']['user_id'] : null;

// Use Khalti test keys from config
$khaltiPublicKey = defined('KHALTI_PUBLIC_KEY') ? KHALTI_PUBLIC_KEY : '';
$khaltiSecretKey = defined('KHALTI_SECRET_KEY') ? KHALTI_SECRET_KEY : '';

if (!$khaltiPublicKey || !$khaltiSecretKey) {
    $errors[] = "Khalti configuration missing. Please contact support.";
}

// Construct absolute base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$baseUrl = $protocol . $_SERVER['HTTP_HOST'] . BASE_URL;

// Validate order existence
if (!$orderId || !$customerInfo) {
    $errors[] = "No order found. Please complete checkout first.";
} else {
    try {
        $orderSql = "SELECT * FROM user_orders WHERE order_id = ? AND user_id " . ($isLoggedIn ? "= ?" : "IS NULL");
        $orderStmt = $conn->prepare($orderSql);
        $params = [$orderId];
        if ($isLoggedIn) {
            $params[] = $userId;
        }
        $orderStmt->execute($params);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            $errors[] = "Invalid order.";
        }

        // Fetch order items
        $itemsSql = "SELECT oi.*, p.product_title FROM order_items oi 
                     JOIN products p ON oi.product_id = p.product_id 
                     WHERE oi.order_id = ?";
        $itemsStmt = $conn->prepare($itemsSql);
        $itemsStmt->execute([$orderId]);
        $cartItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = "Failed to fetch order details: " . htmlspecialchars($e->getMessage());
        error_log("Order fetch error: " . $e->getMessage());
    }
}

// Handle payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = trim($_POST['payment_method'] ?? '');
    if (!in_array($paymentMethod, ['CashOnDelivery', 'Khalti'])) {
        $errors[] = "Invalid payment method selected.";
    } elseif ($paymentMethod === 'Khalti' && (!$khaltiPublicKey || !$khaltiSecretKey)) {
        $errors[] = "Khalti payment is currently unavailable.";
    }

    if (empty($errors)) {
        // Clear cart function
        $clearCart = function ($conn, $isLoggedIn, $userId) {
            if ($isLoggedIn) {
                $deleteSql = "DELETE FROM orders_pending WHERE user_id = ? AND order_status = 'pending'";
                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->execute([$userId]);
            } else {
                $_SESSION['cart'] = [];
            }
        };

        if ($paymentMethod === 'CashOnDelivery') {
            try {
                $conn->beginTransaction();
                $invoiceNumber = 'INV-' . uniqid();
                $paymentSql = "INSERT INTO user_payments (order_id, invoice_number, amount, payment_method, payment_status, payment_date)
                               VALUES (:order_id, :invoice_number, :amount, :payment_method, 'pending', NOW())";
                $paymentStmt = $conn->prepare($paymentSql);
                $paymentStmt->execute([
                    ':order_id' => $orderId,
                    ':invoice_number' => $invoiceNumber,
                    ':amount' => $total,
                    ':payment_method' => 'CashOnDelivery'
                ]);

                // Update order status to confirmed
                $updateOrderSql = "UPDATE user_orders SET order_status = 'confirmed' WHERE order_id = ?";
                $updateOrderStmt = $conn->prepare($updateOrderSql);
                $updateOrderStmt->execute([$orderId]);

                // Clear the cart
                $clearCart($conn, $isLoggedIn, $userId);

                $conn->commit();

                // Clear session data
                unset($_SESSION['pending_order_id']);
                unset($_SESSION['order_total']);
                unset($_SESSION['customer_info']);

                header("Location: " . BASE_URL . "thank_you.php?order_id=$orderId&message=" . urlencode("Order placed successfully!"));
                exit();
            } catch (PDOException $e) {
                $conn->rollBack();
                $errors[] = "Failed to process COD payment: " . htmlspecialchars($e->getMessage());
                error_log("COD payment error: " . $e->getMessage());
            }
        } else {
            // Khalti payment initiation (test environment)
            try {
                $purchaseOrderId = (string)$orderId; // Use plain order_id
                $data = [
                    'return_url' => $baseUrl . 'thank_you.php?order_id=' . urlencode($orderId),
                    'website_url' => $baseUrl,
                    'amount' => $total * 100, // Amount in paisa
                    'purchase_order_id' => $purchaseOrderId,
                    'purchase_order_name' => 'Order #' . $orderId,
                    'customer_info' => [
                        'name' => $customerInfo['name'],
                        'email' => $customerInfo['email'],
                        'phone' => $customerInfo['phone']
                    ],
                    'amount_breakdown' => [
                        ['label' => 'Base Price', 'amount' => $total * 100]
                    ],
                    'product_details' => array_map(function ($item) {
                        return [
                            'identity' => $item['product_id'],
                            'name' => $item['product_title'],
                            'total_price' => $item['price'] * $item['quantity'] * 100,
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['price'] * 100
                        ];
                    }, $cartItems)
                ];

                $logDir = __DIR__ . '/';
                $khaltiLog = $logDir . 'khalti_initiate.txt';
                if (is_writable($logDir) || (file_exists($khaltiLog) && is_writable($khaltiLog))) {
                    file_put_contents($khaltiLog, "Data: " . json_encode($data) . "\n", FILE_APPEND);
                } else {
                    error_log("Cannot write to $khaltiLog: Permission denied");
                }

                // Insert initial payment record
                $invoiceNumber = 'INV-' . uniqid();
                $paymentSql = "INSERT INTO user_payments (order_id, invoice_number, amount, payment_method, payment_status, pidx, payment_date)
                               VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $paymentStmt = $conn->prepare($paymentSql);
                $paymentStmt->execute([
                    $orderId,
                    $invoiceNumber,
                    $total,
                    'Khalti',
                    'pending',
                    '' // pidx will be updated later
                ]);

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://a.khalti.com/api/v2/epayment/initiate/',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode($data),
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Key ' . $khaltiSecretKey,
                        'Content-Type: application/json'
                    ]
                ]);

                $response = curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                if (is_writable($logDir) || (file_exists($khaltiLog) && is_writable($khaltiLog))) {
                    file_put_contents($khaltiLog, "HTTP: $httpCode\nResponse: $response\n", FILE_APPEND);
                } else {
                    error_log("Cannot write to $khaltiLog: Permission denied");
                }

                if ($httpCode === 200) {
                    $responseData = json_decode($response, true);
                    if (isset($responseData['pidx']) && isset($responseData['payment_url'])) {
                        $_SESSION['khalti_pidx'] = $responseData['pidx'];

                        // Update payment record with pidx
                        $updateStmt = $conn->prepare("UPDATE user_payments SET pidx = ? WHERE order_id = ? AND payment_status = 'pending' AND payment_method = 'Khalti'");
                        $updateStmt->execute([$responseData['pidx'], $orderId]);

                        echo "<script>window.location.href = '" . htmlspecialchars($responseData['payment_url']) . "';</script>";
                        exit();
                    } else {
                        $errors[] = "Failed to initiate payment: Invalid response from Khalti.";
                        error_log("Khalti invalid response: " . print_r($responseData, true));
                    }
                } else {
                    $errors[] = "Failed to initiate payment: HTTP $httpCode - " . htmlspecialchars($response);
                    error_log("Khalti initiation failed: HTTP $httpCode, Response: $response");
                }
            } catch (Exception $e) {
                $errors[] = "Failed to initiate payment: " . htmlspecialchars($e->getMessage());
                error_log("Khalti initiation error: " . $e->getMessage());
                if (is_writable($logDir) || (file_exists($khaltiLog) && is_writable($khaltiLog))) {
                    file_put_contents($khaltiLog, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }
        }
    }
}

// Handle payment cancellation
if (isset($_GET['cancelled'])) {
    $errors[] = "Payment was cancelled. Please try again.";
    if (isset($_SESSION['khalti_pidx'])) {
        try {
            $paymentSql = "INSERT INTO user_payments (order_id, invoice_number, amount, payment_method, payment_status, pidx, payment_date) 
                           VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $invoiceNumber = 'INV-' . uniqid();
            $paymentStmt = $conn->prepare($paymentSql);
            $paymentStmt->execute([
                $orderId,
                $invoiceNumber,
                $total,
                'Khalti',
                'cancelled',
                $_SESSION['khalti_pidx']
            ]);
            unset($_SESSION['khalti_pidx']);
        } catch (PDOException $e) {
            $errors[] = "Failed to record cancellation: " . htmlspecialchars($e->getMessage());
            error_log("Cancellation error: " . $e->getMessage());
        }
    }
}

// Function to generate the Order Summary HTML
function generateOrderSummaryHtml($cartItems, $total) {
    ob_start();
    ?>
    <div class="payment-order-container">
        <h2 class="payment-order-heading">Order Summary</h2>
        <?php if (empty($cartItems)): ?>
            <p class="payment-order-no-items">No items in order.</p>
        <?php else: ?>
            <div class="payment-order-items">
                <?php foreach ($cartItems as $item): ?>
                    <div class="payment-item">
                        <div class="payment-item-details">
                            <h3 class="payment-item-title"><?php echo htmlspecialchars($item['product_title']); ?></h3>
                            <p class="payment-item-price">Price: Rs <?php echo number_format($item['price'], 2); ?></p>
                            <p class="payment-item-quantity">Quantity: <?php echo $item['quantity']; ?></p>
                            <p class="payment-item-subtotal">Subtotal: Rs <?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="payment-total">
                    <h3 class="payment-total-heading">Order Total: Rs <span id="payment-total-amount"><?php echo number_format($total, 2); ?></span></h3>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - E-Commerce</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
</head>
<body>
<main class="payment-main-container">
    <div class="payment-content-container">
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <p class="payment-error"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
            <a href="<?php echo BASE_URL; ?>checkout.php" class="payment-back-btn cta-button secondary">Back to Checkout</a>
        <?php else: ?>
            <?php echo generateOrderSummaryHtml($cartItems, $total); ?>
            <form method="POST" action="payment.php" class="payment-form">
                <h3 class="payment-method-heading">Select Payment Method</h3>
                <div class="payment-form-group">
                    <label>
                        <input type="radio" name="payment_method" value="CashOnDelivery" checked> Cash on Delivery
                    </label>
                    <label>
                        <input type="radio" name="payment_method" value="Khalti"> Khalti (Test Mode)
                    </label>
                </div>
                <button type="submit" class="payment-btn cta-button primary">Confirm Payment</button>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>