<?php
// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

require_once '../config.php';
$conn = $pdo;

// Fetch orders and items for the logged-in user
$user_id = $_SESSION['user']['user_id'];
$sql = "SELECT uo.order_id, uo.full_name, uo.address, uo.order_date, uo.total_amount, uo.order_status,
               oi.item_id, oi.quantity, oi.price, p.product_title
        FROM user_orders uo
        LEFT JOIN order_items oi ON uo.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE uo.user_id = :user_id
        ORDER BY uo.order_date DESC, oi.item_id ASC";
$stmt = $conn->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group orders and their items
$orders = [];
foreach ($rows as $row) {
    $order_id = $row['order_id'];
    if (!isset($orders[$order_id])) {
        $orders[$order_id] = [
            'order_id' => $row['order_id'],
            'full_name' => $row['full_name'],
            'address' => $row['address'],
            'order_date' => $row['order_date'],
            'total_amount' => $row['total_amount'],
            'order_status' => $row['order_status'],
            'items' => []
        ];
    }
    if ($row['item_id']) {
        $orders[$order_id]['items'][] = [
            'product_title' => $row['product_title'],
            'quantity' => $row['quantity'],
            'price' => $row['price']
        ];
    }
}
?>

<?php include_once '../header.php'; ?>

<main class="profile-main-container">
    <!-- Profile Sidebar -->
    <aside class="profile-sidebar">
        <div class="profile-sidebar-header">
            <h3 class="profile-sidebar-title">Account</h3>
            <p class="profile-sidebar-subtitle">Manage your account info.</p>
        </div>
        <nav class="profile-sidebar-nav">
            <a href="profile.php" class="profile-sidebar-nav-link"><i class="fas fa-user"></i> Profile</a>
            <a href="edit_profile.php" class="profile-sidebar-nav-link"><i class="fas fa-edit"></i> Edit Profile</a>
            <a href="edit_password.php" class="profile-sidebar-nav-link"><i class="fas fa-key"></i> Change Password</a>
            <a href="order_history.php" class="profile-sidebar-nav-link active"><i class="fas fa-shopping-bag"></i> My Orders</a>
            <a href="../logout.php" class="profile-sidebar-nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Profile Content -->
    <section class="profile-content">
        <div class="profile-details">
            <div class="profile-header">
                <h2 class="profile-title">My Orders</h2>
            </div>
            <?php if (empty($orders)): ?>
                <p class="profile-info">No orders found.</p>
            <?php else: ?>
                <div class="order-history-grid">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-history-item">
                            <h3 class="order-history-item-title">Order #<?php echo htmlspecialchars($order['order_id']); ?></h3>
                            <p class="order-history-item-detail"><strong>Date:</strong> <?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($order['order_date']))); ?></p>
                            <p class="order-history-item-detail"><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                            <p class="order-history-item-detail"><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                            <p class="order-history-item-detail"><strong>Total:</strong> Rs <?php echo number_format($order['total_amount'], 2); ?></p>
                            <p class="order-history-item-detail"><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($order['order_status'])); ?></p>
                            <?php if (!empty($order['items'])): ?>
                                <div class="order-history-item-products">
                                    <h4>Items</h4>
                                    <ul>
                                        <?php foreach ($order['items'] as $item): ?>
                                            <li>
                                                <?php echo htmlspecialchars($item['product_title']); ?> 
                                                (Qty: <?php echo htmlspecialchars($item['quantity']); ?>, 
                                                Price: Rs <?php echo number_format($item['price'], 2); ?>)
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include_once '../footer.php'; ?>
</body>
</html>