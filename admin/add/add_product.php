<?php
include_once '../config.php';
?>

<div class="form-container">
    <h2 class="form-title">Add New Product</h2>
    <form action="admin_handlers.php?action=add_product" method="post" enctype="multipart/form-data" class="add-product-form">
        <!-- Product Image Section -->
        <label for="product_image">Product Image</label>
        <div class="product-image-upload-container">
            <input type="file" name="product_image[]" id="product_image_1" class="product-image-upload-input" required>
            <label for="product_image_1" class="product-image-upload-label">Upload</label>
        </div>

        <!-- Product Name -->
        <label for="product_title">Product Name</label>
        <input type="text" name="product_title" id="product_title" placeholder="Type here" required>

        <!-- Product Description -->
        <label for="product_description">Product Description</label>
        <textarea name="product_description" id="product_description" placeholder="Type here" required></textarea>

        <!-- Category, Product Price, and Offer Price -->
        <div class="form-row">
            <div>
                <label for="product_category">Category</label>
                <select name="product_category" id="product_category" required>
                    <option value="">Select a Category</option>
                    <?php
                    $category_query = "SELECT * FROM categories";
                    $category_stmt = $pdo->query($category_query);
                    $categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($categories as $category) {
                        echo "<option value='{$category['category_id']}'>{$category['category_title']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="product_price">Product Price</label>
                <input type="number" name="product_price" id="product_price" placeholder="0" required>
            </div>
            <div>
                <label for="offer_price">Offer Price</label>
                <input type="number" name="offer_price" id="offer_price" placeholder="0">
            </div>
        </div>

        <!-- Brand -->
        <label for="product_brand">Select a Brand</label>
        <select name="product_brand" id="product_brand" required>
            <option value="">Select a Brand</option>
            <?php
            $brand_query = "SELECT * FROM brands";
            $brand_stmt = $pdo->query($brand_query);
            $brands = $brand_stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($brands as $brand) {
                echo "<option value='{$brand['brand_id']}'>{$brand['brand_title']}</option>";
            }
            ?>
        </select>

        <!-- Submit Button -->
        <button type="submit" name="insert_product" class="accent-btn">ADD</button>
    </form>
</div>