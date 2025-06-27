<?php
session_start();
require_once "config.php";
require_once "header.php";
$conn = $pdo;

if (isset($_SESSION['user'])) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, full_name, user_password FROM users WHERE user_email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['user_password'])) {
        $_SESSION['user'] = [
            'user_id' => $user['user_id'],
            'full_name' => $user['full_name']
        ];
        header("Location: " . BASE_URL . "index.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Commerce</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/auth.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
</head>
<body>
    <div class="main-content">
        <div class="auth-page">
            <div class="auth-container">
                <div class="auth-header">
                    <h1>Welcome Back</h1>
                    <p>Sign in to your account to continue</p>
                </div>
                
                <form method="POST" action="login.php" class="auth-form">
                    <?php if (!empty($error)): ?>
                        <div class="auth-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="auth-input" 
                            required 
                            placeholder="Enter your email" 
                            autocomplete="email"
                            autofocus
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="auth-input" 
                            required 
                            placeholder="Enter your password"
                            autocomplete="current-password"
                        >
                    </div>
                    
                    <button type="submit" class="auth-submit-btn">
                        <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                    </button>
                    
                    <div class="auth-links" style="justify-content: flex-start;">
                        <a href="register.php" class="auth-link">Don't have an account? Sign up</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>