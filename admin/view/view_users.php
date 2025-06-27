<?php
include_once '../config.php';
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">All Users</h2>
    </div>
    <?php
    try {
        $query = "SELECT * FROM users";
        $stmt = $pdo->query($query);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($users) > 0) {
            ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($user['user_id']); ?></td>
                            <td><?= htmlspecialchars($user['username']); ?></td>
                            <td><?= htmlspecialchars($user['user_email']); ?></td>
                            <td class="action-buttons">
                                <a href="delete_user.php?id=<?= $user['user_id']; ?>" class="view-user-delete" onclick="return confirm('Are you sure?');"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        } else {
            echo "<p>No users found.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error loading users: " . htmlspecialchars($e->getMessage()) . "</p>";
        file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - View Users: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    ?>
</div>