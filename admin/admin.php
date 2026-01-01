<?php
// Start session
session_start();

require_once '../config.php';

Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: " . BASE_URL . "admin/admin_login.php");
    exit;
}

$admin_username = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin');
$action = isset($_GET['action']) ? htmlspecialchars($_GET['action']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Commerce</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>admin/assets/css/admin-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="<?php echo BASE_URL; ?>admin/assets/images/download.png" alt="Admin Logo" class="sidebar-logo">
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/admin.php" class="<?php echo $action === '' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/admin.php?action=view_products" class="<?php echo $action === 'view_products' ? 'active' : ''; ?>">
                            <i class="fas fa-box"></i> Products
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/admin.php?action=view_categories" class="<?php echo $action === 'view_categories' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> Categories
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/admin.php?action=view_brands" class="<?php echo $action === 'view_brands' ? 'active' : ''; ?>">
                            <i class="fas fa-tags"></i> Brands
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/admin.php?action=view_orders" class="<?php echo $action === 'view_orders' ? 'active' : ''; ?>">
                            <i class="fas fa-shopping-cart"></i> View Orders
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/admin.php?action=view_users" class="<?php echo $action === 'view_users' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i> View Users
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-content">
                    <button class="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="header-title">Admin Dashboard</h1>
                    <div class="user-info">
                        <span class="username"><?php echo htmlspecialchars($admin_username); ?></span>
                        <a href="<?php echo BASE_URL; ?>logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </header>

            <!-- Main -->
            <main class="main">
                <?php
                switch ($action) {
                    case 'add_product':
                        include 'add/add_product.php';
                        break;
                    case 'edit_product':
                        include 'edit/edit_product.php';
                        break;
                    case 'view_products':
                        include 'view/view_products.php';
                        break;
                    case 'add_category':
                        include 'add/add_category.php';
                        break;
                    case 'edit_category':
                        include 'edit/edit_category.php';
                        break;
                    case 'view_categories':
                        include 'view/view_categories.php';
                        break;
                    case 'add_brand':
                        include 'add/add_brand.php';
                        break;
                    case 'edit_brand':
                        include 'edit/edit_brand.php';
                        break;
                    case 'view_brands':
                        include 'view/view_brands.php';
                        break;
                    case 'view_orders':
                        include 'view/view_orders.php';
                        break;
                    case 'view_users':
                        include 'view/view_users.php';
                        break;
                    default:
                        include 'dashboard.php';
                        break;
                }
                ?> 
            </main>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>admin/assets/js/admin.js"></script>
</body>
</html>