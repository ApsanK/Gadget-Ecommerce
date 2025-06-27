<?php
// Start session
session_start();

require_once '../config.php';

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

// Fetch user details
$user_id = $_SESSION['user']['user_id'];
$query = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
$query->execute(['user_id' => $user_id]);
$user = $query->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - E-Commerce</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
</head>
<body>
<?php include_once '../header.php'; ?>

<main class="profile-main-container">
    <!-- Profile Sidebar -->
    <aside class="profile-sidebar">
        <div class="profile-sidebar-header">
            <h3 class="profile-sidebar-title">Account</h3>
            <p class="profile-sidebar-subtitle">Manage your account info.</p>
        </div>
        <nav class="profile-sidebar-nav">
            <a href="<?php echo BASE_URL; ?>user/profile.php" class="profile-sidebar-nav-link active"><i class="fas fa-user"></i> Profile</a>
            <a href="<?php echo BASE_URL; ?>user/edit_profile.php" class="profile-sidebar-nav-link"><i class="fas fa-edit"></i> Edit Profile</a>
            <a href="<?php echo BASE_URL; ?>user/edit_password.php" class="profile-sidebar-nav-link"><i class="fas fa-key"></i> Change Password</a>
            <a href="<?php echo BASE_URL; ?>user/order_history.php" class="profile-sidebar-nav-link"><i class="fas fa-shopping-bag"></i> My Orders</a>
            <a href="<?php echo BASE_URL; ?>logout.php" class="profile-sidebar-nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Profile Content -->
    <section class="profile-content">
        <div class="profile-details">
            <div class="profile-header">
                <h2 class="profile-title">Profile</h2>
            </div>
            <div class="profile-info">
                <div class="profile-field">
                    <label>Username</label>
                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="profile-field">
                    <label>Email</label>
                    <span><?php echo htmlspecialchars($user['user_email']); ?></span>
                </div>
                <div class="profile-field">
                    <label>Mobile Number</label>
                    <span><?php echo htmlspecialchars($user['user_mobile'] ?: 'Not provided'); ?></span>
                </div>
                <div class="profile-field">
                    <label>Location</label>
                    <span><?php echo htmlspecialchars($user['user_address'] ?: 'USA'); ?></span>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include_once '../footer.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>