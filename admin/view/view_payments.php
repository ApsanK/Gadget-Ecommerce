<?php
include_once '../config.php';
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">All Payments</h2>
    </div>
    <?php
    try {
        $query = "SELECT * FROM user_payments ORDER BY payment_date DESC";
        $stmt = $pdo->query($query);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($payments) > 0) {
            ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Order ID</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($payment['payment_id']); ?></td>
                            <td>#<?= htmlspecialchars($payment['order_id']); ?></td>
                            <td><?= format_currency((float)$payment['amount']); ?></td>
                            <td><?= date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                            <td class="action-buttons">
                                <a href="delete_payment.php?id=<?= $payment['payment_id']; ?>&csrf_token=<?= htmlspecialchars($_SESSION['csrf_token']); ?>" class="view-payment-delete" onclick="return confirm('Are you sure?');"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        } else {
            echo "<p>No payments found.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error loading payments: " . htmlspecialchars($e->getMessage()) . "</p>";
        file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - View Payments: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    ?>
</div>