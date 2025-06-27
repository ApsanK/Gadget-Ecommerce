<?php
include_once '../config.php';
?>

<div class="form-container">
    <h2 class="form-title">Add New Brand</h2>
    <form action="admin_handlers.php?action=add_brand" method="post" class="add-brand-form">
        <label for="brand_title">Brand Title</label>
        <input type="text" name="brand_title" id="brand_title" placeholder="Type here" required>

        <button type="submit" name="insert_brand" class="accent-btn">ADD</button>
    </form>
</div>