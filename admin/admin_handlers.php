<?php
session_start();
include_once '../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

$logDir = __DIR__ . '/logs/';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

function handleInsertProduct(PDO $pdo, array $post, array $files, string $logDir): string {
    $product_title = trim($post['product_title']);
    $product_description = trim($post['product_description']);
    $product_category = trim($post['product_category']);
    $product_brand = trim($post['product_brand']);
    $product_price = trim($post['product_price']);
    $product_image = $files['product_image']['name'] ?? '';
    $product_image_tmp = $files['product_image']['tmp_name'] ?? '';

    if (empty($product_title) || empty($product_description) || empty($product_category) || empty($product_brand) || empty($product_price) || empty($product_image)) {
        return "All fields are required!";
    }

    $target_path = "assets/images/" . basename($product_image);
    if (move_uploaded_file($product_image_tmp, $target_path)) {
        try {
            $check_query = "SELECT * FROM products WHERE product_title = :product_title";
            $stmt = $pdo->prepare($check_query);
            $stmt->bindParam(':product_title', $product_title);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($result) > 0) {
                return "Product already exists!";
            }

            $insert_query = "INSERT INTO products (product_title, product_description, category_id, brand_id, product_image, product_price, date, status) VALUES (:product_title, :product_description, :product_category, :product_brand, :product_image, :product_price, NOW(), 'active')";
            $stmt = $pdo->prepare($insert_query);
            $stmt->bindParam(':product_title', $product_title);
            $stmt->bindParam(':product_description', $product_description);
            $stmt->bindParam(':product_category', $product_category);
            $stmt->bindParam(':product_brand', $product_brand);
            $stmt->bindParam(':product_image', $product_image);
            $stmt->bindParam(':product_price', $product_price);

            if ($stmt->execute()) {
                return "Product inserted successfully!";
            }
            return "Error inserting product.";
        } catch (PDOException $e) {
            file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - Insert Product: " . $e->getMessage() . "\n", FILE_APPEND);
            return "Error inserting product: " . htmlspecialchars($e->getMessage());
        }
    }
    return "Error uploading product image.";
}

function handleUpdateProduct(PDO $pdo, array $post, array $files, string $logDir): string {
    $product_id = trim($post['product_id']);
    $product_title = trim($post['product_title']);
    $product_description = trim($post['product_description']);
    $product_category = trim($post['product_category']);
    $product_brand = trim($post['product_brand']);
    $product_price = trim($post['product_price']);
    $product_status = trim($post['product_status']);
    $product_image = $files['product_image']['name'] ?? '';

    if (empty($product_id) || empty($product_title) || empty($product_description) || empty($product_category) || empty($product_brand) || empty($product_price) || empty($product_status)) {
        return "All fields are required!";
    }

    try {
        if ($product_image) {
            $product_image_tmp = $files['product_image']['tmp_name'];
            $target_path = "assets/images/" . basename($product_image);
            if (!move_uploaded_file($product_image_tmp, $target_path)) {
                return "Error uploading product image.";
            }
            $update_query = "UPDATE products SET product_title = :product_title, product_description = :product_description, category_id = :product_category, brand_id = :product_brand, product_image = :product_image, product_price = :product_price, status = :product_status WHERE product_id = :product_id";
        } else {
            $update_query = "UPDATE products SET product_title = :product_title, product_description = :product_description, category_id = :product_category, brand_id = :product_brand, product_price = :product_price, status = :product_status WHERE product_id = :product_id";
        }

        $stmt = $pdo->prepare($update_query);
        $stmt->bindParam(':product_title', $product_title);
        $stmt->bindParam(':product_description', $product_description);
        $stmt->bindParam(':product_category', $product_category);
        $stmt->bindParam(':product_brand', $product_brand);
        $stmt->bindParam(':product_price', $product_price);
        $stmt->bindParam(':product_status', $product_status);
        $stmt->bindParam(':product_id', $product_id);
        if ($product_image) {
            $stmt->bindParam(':product_image', $product_image);
        }

        if ($stmt->execute()) {
            return "Product updated successfully!";
        }
        return "Error updating product.";
    } catch (PDOException $e) {
        file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - Update Product: " . $e->getMessage() . "\n", FILE_APPEND);
        return "Error updating product: " . htmlspecialchars($e->getMessage());
    }
}

function handleInsertCategory(PDO $pdo, array $post, string $logDir): string {
    $category_title = trim($post['category_title']);
    if (empty($category_title)) {
        return "Category title is required!";
    }

    try {
        $check_query = "SELECT * FROM categories WHERE category_title = :category_title";
        $stmt = $pdo->prepare($check_query);
        $stmt->bindParam(':category_title', $category_title);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) > 0) {
            return "Category already exists!";
        }

        $insert_query = "INSERT INTO categories (category_title) VALUES (:category_title)";
        $stmt = $pdo->prepare($insert_query);
        $stmt->bindParam(':category_title', $category_title);

        if ($stmt->execute()) {
            return "Category inserted successfully!";
        }
        return "Error inserting category.";
    } catch (PDOException $e) {
        file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - Insert Category: " . $e->getMessage() . "\n", FILE_APPEND);
        return "Error inserting category: " . htmlspecialchars($e->getMessage());
    }
}

function handleUpdateCategory(PDO $pdo, array $post, string $logDir): string {
    $category_id = trim($post['category_id']);
    $category_title = trim($post['category_title']);
    if (empty($category_id) || empty($category_title)) {
        return "All fields are required!";
    }

    try {
        $update_query = "UPDATE categories SET category_title = :category_title WHERE category_id = :category_id";
        $stmt = $pdo->prepare($update_query);
        $stmt->bindParam(':category_title', $category_title);
        $stmt->bindParam(':category_id', $category_id);

        if ($stmt->execute()) {
            return "Category updated successfully!";
        }
        return "Error updating category.";
    } catch (PDOException $e) {
        file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - Update Category: " . $e->getMessage() . "\n", FILE_APPEND);
        return "Error updating category: " . htmlspecialchars($e->getMessage());
    }
}

function handleInsertBrand(PDO $pdo, array $post, string $logDir): string {
    $brand_title = trim($post['brand_title']);
    if (empty($brand_title)) {
        return "Brand title is required!";
    }

    try {
        $check_query = "SELECT * FROM brands WHERE brand_title = :brand_title";
        $stmt = $pdo->prepare($check_query);
        $stmt->bindParam(':brand_title', $brand_title);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) > 0) {
            return "Brand already exists!";
        }

        $insert_query = "INSERT INTO brands (brand_title) VALUES (:brand_title)";
        $stmt = $pdo->prepare($insert_query);
        $stmt->bindParam(':brand_title', $brand_title);

        if ($stmt->execute()) {
            return "Brand inserted successfully!";
        }
        return "Error inserting brand.";
    } catch (PDOException $e) {
        file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - Insert Brand: " . $e->getMessage() . "\n", FILE_APPEND);
        return "Error inserting brand: " . htmlspecialchars($e->getMessage());
    }
}

function handleUpdateBrand(PDO $pdo, array $post, string $logDir): string {
    $brand_id = trim($post['brand_id']);
    $brand_title = trim($post['brand_title']);
    if (empty($brand_id) || empty($brand_title)) {
        return "All fields are required!";
    }

    try {
        $update_query = "UPDATE brands SET brand_title = :brand_title WHERE brand_id = :brand_id";
        $stmt = $pdo->prepare($update_query);
        $stmt->bindParam(':brand_title', $brand_title);
        $stmt->bindParam(':brand_id', $brand_id);

        if ($stmt->execute()) {
            return "Brand updated successfully!";
        }
        return "Error updating brand.";
    } catch (PDOException $e) {
        file_put_contents($logDir . 'admin_errors.txt', date('Y-m-d H:i:s') . " - Update Brand: " . $e->getMessage() . "\n", FILE_APPEND);
        return "Error updating brand: " . htmlspecialchars($e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = '';
    $action = $_GET['action'] ?? '';

    if (isset($_POST['insert_product'])) {
        $message = handleInsertProduct($pdo, $_POST, $_FILES, $logDir);
    } elseif (isset($_POST['update_product'])) {
        $message = handleUpdateProduct($pdo, $_POST, $_FILES, $logDir);
    } elseif (isset($_POST['insert_category'])) {
        $message = handleInsertCategory($pdo, $_POST, $logDir);
    } elseif (isset($_POST['update_category'])) {
        $message = handleUpdateCategory($pdo, $_POST, $logDir);
    } elseif (isset($_POST['insert_brand'])) {
        $message = handleInsertBrand($pdo, $_POST, $logDir);
    } elseif (isset($_POST['update_brand'])) {
        $message = handleUpdateBrand($pdo, $_POST, $logDir);
    }

    header("Location: admin.php?action=$action&message=" . urlencode($message));
    exit();
}
?>