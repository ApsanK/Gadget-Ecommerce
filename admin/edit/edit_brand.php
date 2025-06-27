<?php
include_once '../config.php';

$brand = null;
if (isset($_GET['id'])) {
    $brand_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($brand_id) {
        try {
            $query = "SELECT * FROM brands WHERE brand_id = :brand_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['brand_id' => $brand_id]);
            $brand = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Error loading brand: " . htmlspecialchars($e->getMessage());
            file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - Edit Brand: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}
?>

<div class="admin-form-wrapper">
    <h2 class="edit-brand-title">Edit Brand</h2>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php elseif ($brand): ?>
        <form action="admin_handlers.php?action=edit_brand" method="post" class="edit-brand-form">
            <input type="hidden" name="brand_id" value="<?= htmlspecialchars($brand['brand_id']); ?>">
            <label for="brand_title">Brand Title</label>
            <input type="text" name="brand_title" id="brand_title" value="<?= htmlspecialchars($brand['brand_title']); ?>" required>
            <button type="submit" name="update_brand" class="edit-brand-submit">Update Brand</button>
            <a href="<?php echo BASE_URL; ?>admin/admin.php?action=view_brands" class="cancel-btn">Cancel</a>
        </form>
    <?php else: ?>
        <a href="<?php echo BASE_URL; ?>admin/admin.php?action=view_brands" class="cancel-btn">Back to Brands</a>
    <?php endif; ?>
</div>