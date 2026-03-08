<?php
require_once 'includes/init.php';

// If already logged in, redirect to appropriate dashboard
if (isAuthenticated()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    }
    redirect('customer/dashboard.php');
}

$error = '';
$success = '';

// Allow user to restart the flow
if (isset($_GET['start_over'])) {
    unset($_SESSION['reset_user_id'], $_SESSION['reset_username'], $_SESSION['reset_email'], $_SESSION['reset_verified']);
}

$step = isset($_SESSION['reset_user_id']) ? 2 : 1;
$email = '';
$username = '';

// Lightweight CSRF/flow nonce
if (empty($_SESSION['reset_nonce'])) {
    $_SESSION['reset_nonce'] = bin2hex(random_bytes(16));
}
$nonce = $_SESSION['reset_nonce'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedNonce = $_POST['nonce'] ?? '';
    if (!hash_equals($_SESSION['reset_nonce'] ?? '', $postedNonce)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = sanitizeInput($_POST['action'] ?? '');

        if ($action === 'verify') {
            // Step 1: Verify account (username + email)
            $username = sanitizeInput($_POST['username'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');

            if (empty($username)) {
                $error = 'Please enter your username.';
            } elseif (empty($email) || !isValidEmail($email)) {
                $error = 'Please enter a valid email address.';
            } else {
                $db = Database::getInstance();
                $conn = $db->getConnection();

                $stmt = $conn->prepare(
                    "SELECT u.user_id, u.username, u.role\n"
                    . "FROM users u\n"
                    . "LEFT JOIN customers c ON c.user_id = u.user_id\n"
                    . "LEFT JOIN admin_users a ON a.user_id = u.user_id\n"
                    . "WHERE u.username = ? AND u.status = 'ACTIVE'\n"
                    . "  AND ((u.role = 'CUSTOMER' AND c.email = ?) OR (u.role = 'ADMIN' AND a.email = ?))\n"
                    . "LIMIT 1"
                );
                $stmt->bind_param("sss", $username, $email, $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    $_SESSION['reset_user_id'] = (int)$user['user_id'];
                    $_SESSION['reset_username'] = $user['username'];
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_verified'] = true;
                    $step = 2;
                    $success = 'Account verified. Please set your new password.';
                } else {
                    $error = 'No active account found matching that username and email.';
                }
                $stmt->close();
            }
        } elseif ($action === 'reset') {
            // Step 2: Set new password
            if (empty($_SESSION['reset_verified']) || empty($_SESSION['reset_user_id'])) {
                $error = 'Invalid session. Please start over.';
                $step = 1;
                unset($_SESSION['reset_user_id'], $_SESSION['reset_username'], $_SESSION['reset_email'], $_SESSION['reset_verified']);
            } else {
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if (empty($newPassword) || empty($confirmPassword)) {
                    $error = 'Please enter and confirm password.';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'Passwords do not match.';
                } else {
                    $passErrors = validatePassword($newPassword);
                    if (!empty($passErrors)) {
                        $error = implode(' ', $passErrors);
                    } else {
                        $db = Database::getInstance();
                        $conn = $db->getConnection();

                        $hash = hashPassword($newPassword);
                        $userId = (int)$_SESSION['reset_user_id'];
                        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                        $stmt->bind_param("si", $hash, $userId);

                        if ($stmt->execute()) {
                            unset($_SESSION['reset_user_id'], $_SESSION['reset_username'], $_SESSION['reset_email'], $_SESSION['reset_verified']);
                            // Rotate nonce to prevent replay
                            $_SESSION['reset_nonce'] = bin2hex(random_bytes(16));
                            redirectWithMessage('login.php', 'Password reset successfully! Please login with your new password.', 'success');
                        } else {
                            $error = 'Error resetting password. Please try again.';
                        }
                        $stmt->close();
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
        }

        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }

        .reset-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 70px);
            padding: 40px 20px;
        }
        
        .reset-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            padding: 50px 40px;
            max-width: 450px;
            width: 100%;
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .reset-header h2 {
            color: var(--primary);
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .reset-header p {
            color: #666;
            font-size: 14px;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 35px;
            gap: 10px;
        }
        
        .step-indicator {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #666;
            transition: all 0.3s;
            flex: 1;
            text-align: center;
        }
        
        .step-indicator.active {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .step-indicator.completed {
            background: #4caf50;
            color: white;
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
            outline: none;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .btn-reset {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            color: white;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
            font-size: 16px;
            margin-top: 10px;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 25px;
        }
        
        .back-to-login a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-to-login a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        .alert {
            border-radius: 8px;
            border: none;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <?php require_once 'includes/navbar.php'; ?>

    <div class="reset-wrapper">
    <div class="reset-container">
        <div class="reset-header">
            <h2><i class="bi bi-key"></i> Reset Password</h2>
            <p>Recover your account securely</p>
        </div>

        <div class="progress-steps" aria-label="Reset steps">
            <div class="step-indicator <?php echo $step === 1 ? 'active' : 'completed'; ?>" title="Verify">1</div>
            <div class="step-indicator <?php echo $step === 2 ? 'active' : ''; ?>" title="Reset">2</div>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong><i class="bi bi-exclamation-circle"></i> Error!</strong> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong><i class="bi bi-check-circle"></i> Success!</strong> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Step 1: Verify account -->
        <?php if ($step === 1): ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="verify">
                <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce); ?>">
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username *</label>
                    <input type="text" class="form-control" id="username" name="username"
                           placeholder="Enter your username" required>
                    <small class="form-text text-muted">Enter the same username you use to login</small>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address *</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Enter your registered email" required>
                    <small class="form-text text-muted">We will verify your account using username + email</small>
                </div>
                
                <button type="submit" class="btn btn-reset">
                    Verify Account
                </button>
            </form>
        <?php endif; ?>

        <!-- Step 2: Set New Password -->
        <?php if ($step === 2 && !empty($_SESSION['reset_verified']) && !empty($_SESSION['reset_user_id'])): ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="reset">
                <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce); ?>">
                
                <div class="alert alert-info mb-3">
                    <strong>Account:</strong> <?php echo htmlspecialchars($_SESSION['reset_username'] ?? ''); ?><br>
                    <strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['reset_email'] ?? ''); ?>
                </div>
                
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password *</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" 
                           placeholder="Minimum 8 characters" required>
                    <small class="form-text text-muted">Must include letters and numbers</small>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Re-enter your password" required>
                </div>
                
                <button type="submit" class="btn btn-reset">
                    Reset Password
                </button>
            </form>

            <div class="back-to-login" style="margin-top: 15px;">
                <a href="forgot_password.php?start_over=1">Start over</a>
            </div>
        <?php endif; ?>
        
        <div class="back-to-login">
            <a href="login.php"><i class="bi bi-arrow-left"></i> Back to Login</a>
        </div>
    </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
