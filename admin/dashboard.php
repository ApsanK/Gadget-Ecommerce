<?php
include_once '../config.php';

// Set PDO time zone to Nepal (+05:45)
try {
    $pdo->exec("SET time_zone = '+05:45'");
} catch (PDOException $e) {
    // Silently fail to avoid disrupting the dashboard
}

try {
    // Total Orders
    $order_count = $pdo->query("SELECT COUNT(*) FROM user_orders")->fetchColumn();

    // Revenue (sum of total_amount for completed orders)
    $revenue_stmt = $pdo->query("SELECT SUM(total_amount) FROM user_orders WHERE order_status = 'completed'");
    $revenue = $revenue_stmt->fetchColumn() ?: 0.00;

    // Revenue for previous month (for percentage change)
    $prev_revenue_stmt = $pdo->query("SELECT SUM(total_amount) FROM user_orders WHERE order_status = 'completed' AND order_date >= DATE_SUB(LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 2 MONTH)), INTERVAL 1 MONTH) AND order_date < LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
    $prev_revenue = $prev_revenue_stmt->fetchColumn() ?: 0.00;
    $revenue_change = ($prev_revenue > 0) ? round((($revenue - $prev_revenue) / $prev_revenue) * 100, 2) : 0.00;

    // New Orders (orders in the current month)
    $new_orders_stmt = $pdo->query("SELECT COUNT(*) FROM user_orders WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL DAY(CURDATE()) - 1 DAY)");
    $new_orders = $new_orders_stmt->fetchColumn() ?: 0;

    // New Orders for previous month (for percentage change)
    $prev_new_orders_stmt = $pdo->query("SELECT COUNT(*) FROM user_orders WHERE order_date >= DATE_SUB(LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 2 MONTH)), INTERVAL 1 MONTH) AND order_date < LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
    $prev_new_orders = $prev_new_orders_stmt->fetchColumn() ?: 0;
    $new_orders_change = ($prev_new_orders > 0) ? round((($new_orders - $prev_new_orders) / $prev_new_orders) * 100, 2) : 0.00;

    // Recent Orders
    $stmt = $pdo->query("SELECT o.*, u.username 
                         FROM user_orders o 
                         LEFT JOIN users u ON o.user_id = u.user_id 
                         ORDER BY o.order_date DESC 
                         LIMIT 5");
    $recent_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    // Set default values if queries fail
    $order_count = 0;
    $revenue = 0.00;
    $revenue_change = 0.00;
    $new_orders = 0;
    $new_orders_change = 0.00;
    $recent_orders = [];
}
?>

<div class="dashboard-container">
    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon orders">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
                <h3>Total Orders</h3>
                <p><?= htmlspecialchars($order_count); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon products">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
                <h3>Revenue</h3>
                <p class="currency"><?= format_currency($revenue); ?> <span class="<?= $revenue_change >= 0 ? 'text-success' : 'text-danger' ?>"><?= $revenue_change >= 0 ? '+' : '' ?><?= $revenue_change ?>%</span></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon brands">
                <i class="fas fa-cart-plus"></i>
            </div>
            <div class="stat-info">
                <h3>New Orders</h3>
                <p><?= htmlspecialchars(number_format($new_orders)); ?> <span class="text-success"><?= $new_orders_change >= 0 ? '+' : '' ?><?= $new_orders_change ?>%</span></p>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="recent-orders">
        <h2>Recent Orders</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>User</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_orders)): ?>
                    <tr>
                        <td colspan="5">No recent orders found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <?php
                        // Sanitize order_status for CSS class
                        $status = strtolower($order['order_status'] ?? 'unknown');
                        $valid_statuses = ['pending', 'completed', 'cancelled', 'confirmed'];
                        $status_class = in_array($status, $valid_statuses) ? $status : 'unknown';
                        $status_display = $order['order_status'] ? htmlspecialchars($order['order_status']) : 'Unknown';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($order['order_id']); ?></td>
                            <td><?= htmlspecialchars($order['username'] ?? 'Guest'); ?></td>
                            <td class="currency"><?= format_currency((float)$order['total_amount']); ?></td>
                            <td>
                                <span class="status-badge status-<?= $status_class ?>">
                                    <?= $status_display ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($order['order_date']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>