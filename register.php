<?php
require_once "config.php";
$conn = $pdo;

$errors = [];
$success = '';

// Set page title
$pageTitle = "Register";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $user_email = trim($_POST['user_email'] ?? '');
        $user_password = $_POST['user_password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $user_mobile = trim($_POST['user_mobile'] ?? '');
        $user_address = trim($_POST['user_address'] ?? '');

        // Validate inputs
        if (empty($full_name)) {
            $errors[] = "Full name is required.";
        } elseif (!preg_match('/^[a-zA-Z\s\-.\']+$/', $full_name)) {
            $errors[] = "Full name can only contain letters, spaces, hyphens, periods, or apostrophes.";
        }
        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
            $errors[] = "Username can only contain letters and numbers.";
        }
        if (empty($user_email) || !filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required.";
        }
        if (empty($user_password) || $user_password !== $password_confirm) {
            $errors[] = "Passwords do not match or are empty.";
        } elseif (strlen($user_password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
        if (!empty($user_mobile) && !preg_match('/^[0-9+\-\s]+$/', $user_mobile)) {
            $errors[] = "Phone number can only contain numbers, plus sign, hyphens, or spaces.";
        }

        // Check for duplicate username or email
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR user_email = :user_email");
            $stmt->execute([':username' => $username, ':user_email' => $user_email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Username or email already exists.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error checking duplicates: " . htmlspecialchars($e->getMessage());
        }

        // Process registration if no errors
        if (empty($errors)) {
            try {
                $hashed_password = password_hash($user_password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, full_name, user_email, user_password, user_mobile, user_address, role) 
                        VALUES (:username, :full_name, :user_email, :user_password, :user_mobile, :user_address, 'customer')";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':username' => $username,
                    ':full_name' => $full_name,
                    ':user_email' => $user_email,
                    ':user_password' => $hashed_password,
                    ':user_mobile' => $user_mobile ?: null,
                    ':user_address' => $user_address ?: null
                ]);

                // Set session data and log in
                $user_id = $conn->lastInsertId();
                $_SESSION['user'] = [
                    'user_id' => $user_id,
                    'username' => $username,
                    'full_name' => $full_name,
                    'user_email' => $user_email,
                    'user_mobile' => $user_mobile ?: '',
                    'user_address' => $user_address ?: '',
                    'role' => 'customer'
                ];

                $success = "Registration successful! You are now logged in.";
                header("Location: index.php");
                exit();
            } catch (PDOException $e) {
                $errors[] = "Registration failed: " . htmlspecialchars($e->getMessage());
            }
        }
}

?>
<?php include 'header.php'; ?>

<div class="main-content">
    <div class="auth-page">
        <div class="auth-container">
        <div class="auth-header">
            <h1>Create Account</h1>
            <p>Sign up to get started</p>
        </div>
        
        <form method="POST" action="register.php" class="auth-form">
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="auth-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="auth-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            
           
            
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input 
                    type="text" 
                    id="full_name" 
                    name="full_name" 
                    class="auth-input" 
                    required 
                    placeholder="Enter your full name"
                    value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                    autocomplete="name"
                    autofocus
                >
            </div>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="auth-input" 
                    required 
                    placeholder="Choose a username"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    autocomplete="username"
                >
            </div>
            
            <div class="form-group">
                <label for="user_email">Email</label>
                <input 
                    type="email" 
                    id="user_email" 
                    name="user_email" 
                    class="auth-input" 
                    required 
                    placeholder="Enter your email"
                    value="<?php echo htmlspecialchars($_POST['user_email'] ?? ''); ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="user_password">Password</label>
                <input 
                    type="text" 
                    id="user_password" 
                    name="user_password" 
                    class="auth-input" 
                    required 
                    placeholder="Create a password"
                >
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Confirm Password</label>
                <input 
                    type="text" 
                    id="password_confirm" 
                    name="password_confirm" 
                    class="auth-input" 
                    required 
                    placeholder="Confirm your password"
                >
            </div>
            
            <div class="form-group">
                <label for="user_mobile">Phone Number (Optional)</label>
                <input 
                    type="text" 
                    id="user_mobile" 
                    name="user_mobile" 
                    class="auth-input" 
                    placeholder="Enter your phone number"
                    value="<?php echo htmlspecialchars($_POST['user_mobile'] ?? ''); ?>"
                    autocomplete="tel"
                >
            </div>
            
            <div class="form-group">
                <label for="user_address">Address (Optional)</label>
                <textarea 
                    id="user_address" 
                    name="user_address" 
                    class="auth-input" 
                    placeholder="Enter your address"
                    autocomplete="street-address"
                ><?php echo isset($_POST['user_address']) ? htmlspecialchars($_POST['user_address']) : ''; ?></textarea>
            </div>
            
            <button type="submit" class="auth-submit-btn">
                <i class="fas fa-user-plus mr-2"></i> Register
            </button>
            
            <div class="auth-links">
                <a href="login.php" class="auth-link">Already have an account? Log in</a>
            </div>
        </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>