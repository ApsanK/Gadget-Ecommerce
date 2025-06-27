<?php
// Start session explicitly
session_start();

// Restore session if sid provided
if (isset($_GET['sid']) && session_id() !== $_GET['sid']) {
    session_write_close();
    session_id($_GET['sid']);
    session_start();
    error_log("thank_you.php - Restored Session ID: " . session_id());
}

require_once 'config.php';
require_once 'header.php';
$conn = $pdo;

// Log session and GET parameters for debugging
error_log("thank_you.php START - Session ID: " . session_id());
error_log("GET params: " . print_r($_GET, true));
error_log("Session: " . print_r($_SESSION, true));

// Initialize variables
$errors = [];
$orderDetails = null;
$paymentDetails = null;
$isLoggedIn = isset($_SESSION['user']);
$userId = $isLoggedIn ? $_SESSION['user']['user_id'] : null;
$orderId = $_GET['order_id'] ?? null;
$message = $_GET['message'] ?? null;
$khaltiPidx = $_GET['pidx'] ?? null;
$khaltiStatus = $_GET['status'] ?? null;

// Clear cart immediately if order_id is present
if ($orderId) {
    try {
        // Fetch user_id from user_orders
        $fetchStmt = $conn->prepare("SELECT user_id FROM user_orders WHERE order_id = ?");
        $fetchStmt->execute([$orderId]);
        $orderUserId = $fetchStmt->fetchColumn();

        // Check payment status to avoid clearing for failed payments
        $paymentStmt = $conn->prepare("SELECT payment_status FROM user_payments WHERE order_id = ? ORDER BY payment_id DESC LIMIT 1");
        $paymentStmt->execute([$orderId]);
        $paymentStatus = $paymentStmt->fetchColumn();

        if ($paymentStatus !== 'Failed') {
            if ($orderUserId) {
                $clearStmt = $conn->prepare("DELETE FROM orders_pending WHERE user_id = ? AND order_status = 'pending'");
                $clearStmt->execute([$orderUserId]);
                error_log("thank_you.php - Cleared orders_pending for user_id: $orderUserId");
            }
            // Clear session cart
            $_SESSION['cart'] = [];
            error_log("thank_you.php - Cleared session cart");
            // Clear order-related session variables
            unset($_SESSION['pending_order_id']);
            unset($_SESSION['order_total']);
            unset($_SESSION['customer_info']);
            unset($_SESSION['khalti_pidx']);
        } else {
            error_log("thank_you.php - Skipped cart clearing due to Failed payment status for order_id: $orderId");
        }
    } catch (PDOException $e) {
        error_log("thank_you.php - Cart clear error: " . $e->getMessage());
    }
}

// Use Khalti secret key from config.php
$khaltiSecretKey = defined('KHALTI_SECRET_KEY') ? KHALTI_SECRET_KEY : '';
if (!$khaltiSecretKey && $khaltiPidx) {
    error_log("Khalti secret key missing in config.php");
}

// Handle Khalti payment verification
if ($khaltiPidx && $khaltiStatus && $khaltiSecretKey) {
    try {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://a.khalti.com/api/v2/epayment/lookup/?pidx=' . urlencode($khaltiPidx),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Authorization: Key ' . $khaltiSecretKey,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $logDir = __DIR__ . '/';
        $khaltiLog = $logDir . 'khalti_lookup.txt';
        if (is_writable($logDir) || (file_exists($khaltiLog) && is_writable($khaltiLog))) {
            file_put_contents($khaltiLog, "HTTP: $httpCode\nPidx: $khaltiPidx\nResponse: $response\nKey: " . substr($khaltiSecretKey, 0, 8) . "...\n", FILE_APPEND);
        } else {
            error_log("Cannot write to $khaltiLog: Permission denied");
        }

        if ($httpCode === 200) {
            $responseData = json_decode($response, true);
            if (isset($responseData['status'])) {
                $orderId = $orderId ?: ($responseData['order_id'] ?? null);
                $paymentStatus = $responseData['status'] === 'Completed' ? 'Completed' : 'Failed';
                $message = $message ?: ($paymentStatus === 'Completed' ? 'Payment successful!' : 'Payment failed.');

                // Update payment record
                $paymentStmt = $conn->prepare("SELECT payment_id FROM user_payments WHERE order_id = ? AND pidx = ?");
                $paymentStmt->execute([$orderId, $khaltiPidx]);
                if ($paymentStmt->fetch()) {
                    $updateStmt = $conn->prepare("UPDATE user_payments SET payment_status = ?, transaction_id = ?, payment_date = NOW() WHERE order_id = ? AND pidx = ?");
                    $updateStmt->execute([$paymentStatus, $responseData['transaction_id'] ?? null, $orderId, $khaltiPidx]);
                } else {
                    $paymentSql = "INSERT INTO user_payments (order_id, invoice_number, amount, payment_method, payment_status, pidx, transaction_id, payment_date)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                    $invoiceNumber = 'INV-' . uniqid();
                    $paymentStmt = $conn->prepare($paymentSql);
                    $paymentStmt->execute([
                        $orderId,
                        $invoiceNumber,
                        ($responseData['total_amount'] ?? 0) / 100,
                        'Khalti',
                        $paymentStatus,
                        $khaltiPidx,
                        $responseData['transaction_id'] ?? null
                    ]);
                }

                // Update order status if payment completed
                if ($paymentStatus === 'Completed') {
                    $updateOrderStmt = $conn->prepare("UPDATE user_orders SET order_status = 'confirmed' WHERE order_id = ?");
                    $updateOrderStmt->execute([$orderId]);
                }
            } else {
                error_log("Khalti lookup invalid response: " . print_r($responseData, true));
            }
        } else {
            error_log("Khalti lookup failed: HTTP $httpCode, Response: $response");
            // Fallback: Use $_GET['status']
            if ($khaltiStatus === 'Completed' && $orderId) {
                $paymentStatus = 'Completed';
                $message = $message ?: 'Payment successful!';
                $updateStmt = $conn->prepare("UPDATE user_payments SET payment_status = ?, payment_date = NOW() WHERE order_id = ? AND pidx = ?");
                $updateStmt->execute([$paymentStatus, $orderId, $khaltiPidx]);

                $updateOrderStmt = $conn->prepare("UPDATE user_orders SET order_status = 'confirmed' WHERE order_id = ?");
                $updateOrderStmt->execute([$orderId]);
            }
        }
    } catch (Exception $e) {
        error_log("Khalti lookup error: " . $e->getMessage());
    }
}

// Fetch payment details
if ($orderId) {
    try {
        $paymentDetailsStmt = $conn->prepare("SELECT * FROM user_payments WHERE order_id = ? ORDER BY payment_id DESC LIMIT 1");
        $paymentDetailsStmt->execute([$orderId]);
        $paymentDetails = $paymentDetailsStmt->fetch(PDO::FETCH_ASSOC);
        if ($paymentDetails) {
            $total = $paymentDetails['amount'];
        }
    } catch (PDOException $e) {
        $errors[] = "Failed to fetch payment details: " . htmlspecialchars($e->getMessage());
        error_log("Payment details fetch error: " . $e->getMessage());
    }
}

// Fetch order details
if ($orderId) {
    try {
        // Try fetching without user_id
        $orderStmt = $conn->prepare("SELECT * FROM user_orders WHERE order_id = ?");
        $orderStmt->execute([$orderId]);
        $orderDetails = $orderStmt->fetch(PDO::FETCH_ASSOC);

        // Fallback to user_id constraint
        if (!$orderDetails) {
            $orderStmt = $conn->prepare("SELECT * FROM user_orders WHERE order_id = ? AND user_id " . ($isLoggedIn ? "= ?" : "IS NULL"));
            $params = [$orderId];
            if ($isLoggedIn) {
                $params[] = $userId;
            }
            $orderStmt->execute($params);
            $orderDetails = $orderStmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$orderDetails) {
            $errors[] = "Order not found.";
        }
    } catch (PDOException $e) {
        $errors[] = "Failed to fetch order: " . htmlspecialchars($e->getMessage());
        error_log("Order fetch error: " . $e->getMessage());
    }
}

// Fetch order items
if ($orderDetails) {
    try {
        $itemsStmt = $conn->prepare("SELECT oi.*, p.product_title FROM order_items oi 
                                     JOIN products p ON oi.product_id = p.product_id 
                                     WHERE oi.order_id = ?");
        $itemsStmt->execute([$orderId]);
        $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$total) {
            $total = 0;
            foreach ($orderItems as $item) {
                $total += $item['price'] * $item['quantity'];
            }
        }
    } catch (PDOException $e) {
        $errors[] = "Failed to fetch order items: " . htmlspecialchars($e->getMessage());
        error_log("Order items fetch error: " . $e->getMessage());
    }
}

// Function to generate thank you page HTML
function generateThankYouHtml($orderDetails, $orderItems, $paymentDetails, $errors, $message) {
    ob_start();
    ?>
    <main class="thank-you-main-container">
        <div class="thank-you-content-container">
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <p class="thank-you-error"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
                <a href="<?php echo BASE_URL; ?>checkout.php" class="thank-you-back-btn cta-button secondary">Back to Checkout</a>
            <?php else: ?>
                <h2 class="thank-you-heading">Thank You for Your Order!</h2>
                <p class="thank-you-message">
                    <?php echo $message ? htmlspecialchars($message) : 'Your order has been successfully placed.'; ?>
                </p>
                
                <h3 class="thank-you-subheading">Order Details</h3>
                <div class="thank-you-order-details">
                    <p><strong>Order ID:</strong> <?php echo htmlspecialchars($orderDetails['order_id']); ?></p>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($orderDetails['full_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($orderDetails['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($orderDetails['phone']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($orderDetails['address']); ?></p>
                </div>

                <h3 class="thank-you-subheading">Order Items</h3>
                <div class="thank-you-order-items">
                    <?php foreach ($orderItems as $item): ?>
                        <div class="thank-you-item">
                            <h4 class="thank-you-item-title"><?php echo htmlspecialchars($item['product_title']); ?></h4>
                            <p class="thank-you-item-price">Price: Rs <?php echo number_format($item['price'], 2); ?></p>
                            <p class="thank-you-item-quantity">Quantity: <?php echo $item['quantity']; ?></p>
                            <p class="thank-you-item-subtotal">Subtotal: Rs <?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h3 class="thank-you-subheading">Payment Details</h3>
                <div class="thank-you-payment-details">
                    <p><strong>Payment ID:</strong> <?php echo htmlspecialchars($paymentDetails['payment_id'] ?? 'N/A'); ?></p>
                    <p><strong>Invoice Number:</strong> <?php echo htmlspecialchars($paymentDetails['invoice_number'] ?? 'N/A'); ?></p>
                    <p><strong>Amount:</strong> Rs <?php echo number_format($paymentDetails['amount'] ?? 0, 2); ?></p>
                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($paymentDetails['payment_method'] ?? 'N/A'); ?></p>
                    <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($paymentDetails['payment_status'] ?? 'N/A'); ?></p>
                    <p><strong>Payment Date:</strong> <?php echo htmlspecialchars($paymentDetails['payment_date'] ?? 'N/A'); ?></p>
                    <?php if (!empty($paymentDetails['pidx'])): ?>
                        <p><strong>Khalti PIDX:</strong> <?php echo htmlspecialchars($paymentDetails['pidx']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($paymentDetails['transaction_id'])): ?>
                        <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($paymentDetails['transaction_id']); ?></p>
                    <?php endif; ?>
                </div>

                <a href="<?php echo BASE_URL; ?>products.php" class="thank-you-continue-btn cta-button primary">Continue Shopping</a>
            <?php endif; ?>
        </div>
    </main>
    <?php
    return ob_get_clean();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - E-Commerce</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
</head>
<body>
<?php echo generateThankYouHtml($orderDetails, $orderItems, $paymentDetails, $errors, $message); ?>
<?php include 'footer.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>