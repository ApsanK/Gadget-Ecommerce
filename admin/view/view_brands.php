<?php
include_once '../config.php';
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">All Brands</h2>
        <a href="<?php echo BASE_URL; ?>admin/admin.php?action=add_brand" class="btn">Add New Brand</a>
    </div>
    <?php
    try {
        $query = "SELECT * FROM brands";
        $stmt = $pdo->query($query);
        $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($brands) > 0) {
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
                    <?php foreach ($brands as $brand): ?>
                        <tr>
                            <td><?= htmlspecialchars($brand['brand_id']); ?></td>
                            <td><?= htmlspecialchars($brand['brand_title']); ?></td>
                            <td class="action-buttons">
                                <a href="<?php echo BASE_URL; ?>admin/admin.php?action=edit_brand&id=<?= htmlspecialchars($brand['brand_id']); ?>" class="view-brand-edit"><i class="fas fa-edit"></i></a>
                                <a href="delete_brand.php?id=<?= htmlspecialchars($brand['brand_id']); ?>" class="view-brand-delete" onclick="return confirm('Are you sure?');"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        } else {
            echo "<p>No brands found.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error loading brands: " . htmlspecialchars($e->getMessage()) . "</p>";
        file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - View Brands: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    ?>
</div>