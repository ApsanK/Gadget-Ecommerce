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

// Fetch user details from the session
$user_id = $_SESSION['user']['user_id'];

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Delete user from the database
    $delete_query = $pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
    $delete_query->execute(['user_id' => $user_id]);

    // Destroy the session and redirect to index page
    session_destroy();
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Profile - E-Commerce</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
</head>
<body>
<?php include_once '../header.php'; ?>

<main class="user-profile">
    <div class="user-profile-header">
        <span class="user-profile-icon"><i class="fas fa-trash-alt"></i></span>
        <h2 class="user-profile-title">Delete Profile</h2>
    </div>
    <h3 class="user-profile-heading">Are you sure you want to delete your account?</h3>

    <form action="delete_profile.php" method="POST" class="user-delete-form">
        <div class="form-group">
            <button type="submit" class="btn-outline btn-danger"><i class="fas fa-trash"></i> Delete Account</button>
        </div>
    </form>
</main>

<?php include_once '../footer.php'; ?>
<script src="../assets/js/script.js"></script>
</body>
</html>