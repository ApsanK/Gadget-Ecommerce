<?php
// Start session
session_start();

require_once "../config.php";

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: " . BASE_URL . "admin/admin.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, full_name, password FROM admins WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['full_name'];

                // Update last_login
                $updateStmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$admin['id']]);

                header("Location: " . BASE_URL . "admin/admin.php");
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "Error: Unable to process login.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Ecommerce</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>admin/assets/css/admin-auth.css">
</head>
<body class="admin-auth-page">
    <div class="admin-auth-container">
        <div class="admin-auth-header">
            <h1>Admin Login</h1>
            <p>Sign in to your account</p>
        </div>
        
        <form method="POST" class="admin-auth-form">
            <?php if (!empty($error)): ?>
                <div class="admin-auth-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="admin-form-group">
                <label for="email">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="admin-auth-input" 
                    required 
                    placeholder="Enter your email"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                >
            </div>
            
            <div class="admin-form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="admin-auth-input" 
                    required 
                    placeholder="Enter your password"
                >
            </div>
            
            <button type="submit" class="admin-auth-submit-btn">
                <i class="fas fa-sign-in-alt mr-2"></i> SIGN IN
            </button>
        </form>

        <div class="admin-auth-footer">
            <p>Don't have an account? <a href="#">Sign up</a></p>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>admin/assets/js/script.js"></script>
</body>
</html>