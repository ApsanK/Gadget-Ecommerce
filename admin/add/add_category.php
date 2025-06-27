<?php
include_once '../config.php';
?>

<div class="form-container">
    <h2 class="form-title">Add New Category</h2>
    <form action="admin_handlers.php?action=add_category" method="post" class="add-category-form">
        <label for="category_title">Category Title</label>
        <input type="text" name="category_title" id="category_title" placeholder="Type here" required>

        <button type="submit" name="insert_category" class="accent-btn">ADD</button>
    </form>
</div>