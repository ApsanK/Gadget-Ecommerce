<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once '../config.php';
$conn = $pdo;

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch user details
$user_id = $_SESSION['user']['user_id'];
$query = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
$query->execute(['user_id' => $user_id]);
$user = $query->fetch(PDO::FETCH_ASSOC);

// Handle form submission
$success_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['user_email'];
    $address = $_POST['user_address'];
    $mobile = $_POST['user_mobile'];

    $update_query = $pdo->prepare("UPDATE users SET username = :username, user_email = :user_email, user_address = :user_address, user_mobile = :user_mobile WHERE user_id = :user_id");
    $update_query->execute([
        'username' => $username,
        'user_email' => $email,
        'user_address' => $address,
        'user_mobile' => $mobile,
        'user_id' => $user_id
    ]);

    $success_message = "Profile updated successfully.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - E-Commerce</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
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
            <a href="profile.php" class="profile-sidebar-nav-link"><i class="fas fa-user"></i> Profile</a>
            <a href="edit_profile.php" class="profile-sidebar-nav-link active"><i class="fas fa-edit"></i> Edit Profile</a>
            <a href="edit_password.php" class="profile-sidebar-nav-link"><i class="fas fa-key"></i> Change Password</a>
            <a href="order_history.php" class="profile-sidebar-nav-link"><i class="fas fa-shopping-bag"></i> My Orders</a>
            <a href="../logout.php" class="profile-sidebar-nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Profile Content -->
    <section class="profile-content">
        <div class="profile-details">
            <div class="profile-header">
                <h2 class="profile-title">Edit Profile</h2>
            </div>
            <?php if ($success_message): ?>
                <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>
            <form class="profile-form" method="POST" action="edit_profile.php">
                <div class="profile-field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="profile-field">
                    <label for="user_email">Email</label>
                    <input type="email" id="user_email" name="user_email" value="<?php echo htmlspecialchars($user['user_email']); ?>" required>
                </div>
                <div class="profile-field">
                    <label for="user_address">Location</label>
                    <input type="text" id="user_address" name="user_address" value="<?php echo htmlspecialchars($user['user_address'] ?: ''); ?>">
                </div>
                <div class="profile-field">
                    <label for="user_mobile">Mobile Number</label>
                    <input type="text" id="user_mobile" name="user_mobile" value="<?php echo htmlspecialchars($user['user_mobile'] ?: ''); ?>">
                </div>
                <button type="submit" class="profile-save-btn">Save Changes</button>
            </form>
        </div>
    </section>
</main>

<?php include_once '../footer.php'; ?>
<script src="../assets/js/script.js"></script>
</body>
</html>