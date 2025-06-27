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
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verify old password
    if (password_verify($old_password, $user['user_password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = $pdo->prepare("UPDATE users SET user_password = :user_password WHERE user_id = :user_id");
            $update_query->execute([
                'user_password' => $hashed_password,
                'user_id' => $user_id
            ]);
            $success_message = "Password updated successfully.";
        } else {
            $error_message = "New password and confirm password do not match.";
        }
    } else {
        $error_message = "Old password is incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - E-Commerce</title>
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
            <a href="edit_profile.php" class="profile-sidebar-nav-link"><i class="fas fa-edit"></i> Edit Profile</a>
            <a href="edit_password.php" class="profile-sidebar-nav-link active"><i class="fas fa-key"></i> Change Password</a>
            <a href="order_history.php" class="profile-sidebar-nav-link"><i class="fas fa-shopping-bag"></i> My Orders</a>
            <a href="../logout.php" class="profile-sidebar-nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Profile Content -->
    <section class="profile-content">
        <div class="profile-details">
            <div class="profile-header">
                <h2 class="profile-title">Change Password</h2>
            </div>
            <?php if (isset($error_message)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>
            <form class="profile-form" method="POST" action="edit_password.php">
                <div class="profile-field">
                    <label for="old_password">Old Password</label>
                    <input type="password" id="old_password" name="old_password" required>
                </div>
                <div class="profile-field">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="profile-field">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="profile-save-btn">Update Password</button>
            </form>
        </div>
    </section>
</main>

<?php include_once '../footer.php'; ?>
<script src="../assets/js/script.js"></script>
</body>
</html>