<?php
ob_start();
include_once '../config.php';

// Handle GET request for single row refresh
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_order_row' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => false, 'message' => 'Error fetching order'];

    $order_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($order_id) {
        $stmt = $pdo->prepare("SELECT * FROM user_orders WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            ob_start();
            $status = strtolower($row['order_status'] ?? 'unknown');
            $valid_statuses = ['pending', 'completed', 'cancelled', 'confirmed'];
            $status_class = in_array($status, $valid_statuses) ? $status : 'unknown';
            $status_display = $row['order_status'] ? htmlspecialchars($row['order_status']) : 'Unknown';
            ?>
            <tr data-order-id="<?php echo htmlspecialchars($row['order_id']); ?>">
                <td>#<?php echo htmlspecialchars($row['order_id']); ?></td>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
                <td><?php echo format_currency((float)$row['total_amount']); ?></td>
                <td><span class="status-badge status-<?php echo $status_class; ?>"><?php echo $status_display; ?></span></td>
                <td class="action-buttons">
                    <a href="#" class="view-order-edit" data-order-id="<?php echo htmlspecialchars($row['order_id']); ?>" onclick="toggleStatusDropdown('<?php echo htmlspecialchars($row['order_id']); ?>')"><i class="fas fa-edit"></i></a>
                    <a href="../delete_order.php?id=<?php echo htmlspecialchars($row['order_id']); ?>" class="view-order-delete" onclick="return confirm('Are you sure?');"><i class="fas fa-trash-alt"></i></a>
                    <div class="status-dropdown" id="status-dropdown-<?php echo htmlspecialchars($row['order_id']); ?>" style="display: none;">
                        <select class="status-select" data-order-id="<?php echo htmlspecialchars($row['order_id']); ?>">
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button class="btn status-save-btn" onclick="updateOrderStatus('<?php echo htmlspecialchars($row['order_id']); ?>')">Save</button>
                    </div>
                </td>
            </tr>
            <?php
            $row_html = ob_get_clean();
            $response = [
                'success' => true,
                'row_html' => $row_html
            ];
        } else {
            $response['message'] = 'Order ID not found';
        }
    }

    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => false, 'message' => 'Error updating status'];

    if (isset($_POST['order_id'], $_POST['status'])) {
        $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
        $status = $_POST['status'];
        $valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];

        if ($order_id && in_array($status, $valid_statuses)) {
            // Check if order_id exists
            $stmt = $pdo->prepare("SELECT order_id FROM user_orders WHERE order_id = ?");
            $stmt->execute([$order_id]);
            if ($stmt->fetch()) {
                // Update order_status
                $stmt = $pdo->prepare("UPDATE user_orders SET order_status = ? WHERE order_id = ?");
                $stmt->execute([$status, $order_id]);
                $response = [
                    'success' => true,
                    'message' => 'Status updated successfully',
                    'status' => ucfirst($status)
                ];
            } else {
                $response['message'] = 'Order ID not found';
            }
        } else {
            $response['message'] = 'Invalid order ID or status';
        }
    }

    // Log response
    file_put_contents(__DIR__ . '/../../logs/status_responses.txt', date('Y-m-d H:i:s') . " - Response: " . json_encode($response) . "\n", FILE_APPEND);

    ob_end_clean();
    echo json_encode($response);
    exit;
}
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">All Orders</h2>
    </div>
    <?php
    $query = "SELECT * FROM user_orders ORDER BY order_date DESC";
    $stmt = $pdo->query($query);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log rendered order IDs
    $order_ids = array_column($orders, 'order_id');

    if (count($orders) > 0) {
        ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $row): ?>
                    <?php
                    $status = strtolower($row['order_status'] ?? 'unknown');
                    $valid_statuses = ['pending', 'completed', 'cancelled', 'confirmed'];
                    $status_class = in_array($status, $valid_statuses) ? $status : 'unknown';
                    $status_display = $row['order_status'] ? htmlspecialchars($row['order_status']) : 'Unknown';
                    ?>
                    <tr data-order-id="<?php echo htmlspecialchars($row['order_id']); ?>">
                        <td>#<?php echo htmlspecialchars($row['order_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
                        <td><?php echo format_currency((float)$row['total_amount']); ?></td>
                        <td><span class="status-badge status-<?php echo $status_class; ?>"><?php echo $status_display; ?></span></td>
                        <td class="action-buttons">
                            <a href="#" class="view-order-edit" data-order-id="<?php echo htmlspecialchars($row['order_id']); ?>" onclick="toggleStatusDropdown('<?php echo htmlspecialchars($row['order_id']); ?>')"><i class="fas fa-edit"></i></a>
                            <a href="../delete_order.php?id=<?php echo htmlspecialchars($row['order_id']); ?>" class="view-order-delete" onclick="return confirm('Are you sure?');"><i class="fas fa-trash-alt"></i></a>
                            <div class="status-dropdown" id="status-dropdown-<?php echo htmlspecialchars($row['order_id']); ?>" style="display: none;">
                                <select class="status-select" data-order-id="<?php echo htmlspecialchars($row['order_id']); ?>">
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <button class="btn status-save-btn" onclick="updateOrderStatus('<?php echo htmlspecialchars($row['order_id']); ?>')">Save</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    } else {
        echo "<p>No orders found.</p>";
    }
    ob_end_flush();
    ?>
</div>