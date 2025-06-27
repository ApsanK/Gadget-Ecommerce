<?php
include_once '../config.php';
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">All Categories</h2>
        <a href="<?php echo BASE_URL; ?>admin/admin.php?action=add_category" class="btn">Add New Category</a>
    </div>
    <?php
    try {
        $query = "SELECT * FROM categories";
        $stmt = $pdo->query($query);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($categories) > 0) {
            ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?= htmlspecialchars($category['category_id']); ?></td>
                            <td><?= htmlspecialchars($category['category_title']); ?></td>
                            <td class="action-buttons">
                                <a href="<?php echo BASE_URL; ?>admin/admin.php?action=edit_category&id=<?= htmlspecialchars($category['category_id']); ?>" class="view-category-edit"><i class="fas fa-edit"></i></a>
                                <a href="delete_category.php?id=<?= htmlspecialchars($category['category_id']); ?>" class="view-category-delete" onclick="return confirm('Are you sure?');"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        } else {
            echo "<p>No categories found.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error loading categories: " . htmlspecialchars($e->getMessage()) . "</p>";
        file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - View Categories: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    ?>
</div>