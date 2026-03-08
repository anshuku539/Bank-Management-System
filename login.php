<?php
require_once 'includes/init.php';

// If already logged in, redirect to appropriate dashboard
if (isAuthenticated()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('customer/dashboard.php');
    }
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT user_id, username, password_hash, role, status FROM users WHERE username = ?");
        if (!$stmt) {
            $error = "Database error. Please try again.";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if ($user['status'] !== STATUS_ACTIVE) {
                    $error = 'Your account is inactive. Please contact administrator.';
                } elseif (verifyPassword($password, $user['password_hash'])) {
                    // Password correct - create session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['last_activity'] = time();
                    
                    // Update last login
                    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                    $updateStmt->bind_param("i", $user['user_id']);
                    $updateStmt->execute();
                    
                    // Log activity
                    logActivity($user['user_id'], 'LOGIN', 'User login successful');
                    
                    if ($user['role'] === ROLE_ADMIN) {
                        redirect('admin/dashboard.php');
                    } else {
                        redirect('customer/dashboard.php');
                    }
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php require_once 'includes/navbar.php'; ?>

    <div class="login-wrapper">
        <div class="login-container">
        <div class="login-header">
            <h1><i class="bi bi-bank2"></i> <?php echo SITE_NAME; ?></h1>
            <p>Secure Online Banking Platform</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <strong>Error!</strong> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        


        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label"><i class="bi bi-person"></i> Username</label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?php echo htmlspecialchars($username); ?>" 
                       placeholder="Enter your username" required autofocus>
            </div>
            
            <div class="mb-3 password-field">
                <label for="password" class="form-label"><i class="bi bi-lock"></i> Password</label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Enter your password" required>
                <span class="toggle-password" onclick="togglePassword()" title="Show/Hide Password">
                    <i class="bi bi-eye"></i>
                </span>
            </div>
            
            <button type="submit" class="btn btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Login Now
            </button>
        </form>
        
        <div class="links">
            <p>Don't have an account? <a href="register.php"><i class="bi bi-person-plus"></i> Register here</a></p>
            <p><a href="forgot_password.php"><i class="bi bi-key"></i> Forgot Password?</a></p>
        </div>
        
        <div class="login-benefits">
            <h5>Why Choose Us?</h5>
            <div class="benefits-list">
                <span><i class="bi bi-shield-lock"></i> Secure</span>
                <span><i class="bi bi-lightning-charge"></i> Fast</span>
                <span><i class="bi bi-24h"></i> 24/7</span>
                <span><i class="bi bi-headset"></i> Support</span>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = event.target;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<i class="bi bi-lock-fill"></i>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<i class="bi bi-eye-fill"></i>';
            }
        }
    </script>
</body>
</html>
