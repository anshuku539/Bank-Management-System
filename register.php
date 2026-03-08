<?php
require_once 'includes/init.php';

if (isAuthenticated()) {
    redirect('login.php');
}

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $formData = [
        'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
        'dob' => sanitizeInput($_POST['dob'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'city' => sanitizeInput($_POST['city'] ?? ''),
        'state' => sanitizeInput($_POST['state'] ?? ''),
        'zip_code' => sanitizeInput($_POST['zip_code'] ?? ''),
        'aadhar_number' => sanitizeInput($_POST['aadhar_number'] ?? ''),
        'pan_number' => sanitizeInput($_POST['pan_number'] ?? ''),
        'username' => sanitizeInput($_POST['username'] ?? ''),
    ];
    
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validation
    if (empty($formData['full_name'])) {
        $errors[] = 'Full name is required.';
    }
    
    if (empty($formData['dob'])) {
        $errors[] = 'Date of birth is required.';
    }
    
    if (empty($formData['email']) || !isValidEmail($formData['email'])) {
        $errors[] = 'Valid email is required.';
    }
    
    if (empty($formData['phone']) || !isValidPhone($formData['phone'])) {
        $errors[] = 'Valid 10-digit phone number is required.';
    }
    
    if (empty($formData['username']) || strlen($formData['username']) < 5) {
        $errors[] = 'Username must be at least 5 characters long.';
    }
    
    if (empty($password) || empty($password_confirm)) {
        $errors[] = 'Password and confirm password are required.';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        $passErrors = validatePassword($password);
        if (!empty($passErrors)) {
            $errors = array_merge($errors, $passErrors);
        }
    }
    
    if (empty($errors)) {
        // Check if username already exists
        if (usernameExists($formData['username'])) {
            $errors[] = 'Username already exists. Please choose another.';
        }
        
        // Check if email already exists
        if (emailInRegistration($formData['email'])) {
            $errors[] = 'Email already registered. Please use another email.';
        }
    }
    
    if (empty($errors)) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Create registration request
        $stmt = $conn->prepare(
            "INSERT INTO registration_requests 
            (full_name, dob, email, phone, address, city, state, zip_code, aadhar_number, pan_number, username) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->bind_param(
            "sssssssssss",
            $formData['full_name'],
            $formData['dob'],
            $formData['email'],
            $formData['phone'],
            $formData['address'],
            $formData['city'],
            $formData['state'],
            $formData['zip_code'],
            $formData['aadhar_number'],
            $formData['pan_number'],
            $formData['username']
        );
        
        if ($stmt->execute()) {
            // Store password temporarily (will be saved after approval)
            $passwordHash = hashPassword($password);
            
            redirectWithMessage(
                'login.php',
                'Registration request submitted successfully! Your request is pending admin approval. You will be able to login once approved.',
                'success'
            );
        } else {
            $errors[] = 'Error submitting registration request. Please try again.';
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .register-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            padding: 40px 30px;
            max-width: 650px;
            margin: 20px auto;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .register-header h1 {
            color: var(--primary);
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .register-header p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        .password-info {
            font-size: 11px;
            color: #666;
            margin-top: 3px;
            background: #f5f5f5;
            padding: 6px 8px;
            border-radius: 4px;
            margin-bottom: 8px;
            display: none;
            animation: slideDown 0.3s ease-out;
        }

        .password-info.show {
            display: block;
        }

        .alert ul {
            margin-bottom: 0;
            padding-left: 20px;
        }

        .alert li {
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .register-container {
                padding: 30px 20px;
                margin: 15px 10px;
            }

            .register-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <?php require_once 'includes/navbar.php'; ?>

    <div class="register-container">
        <div class="register-header">
            <h1><i class="bi bi-person-plus-fill"></i> Register Account</h1>
            <p>Create your banking account</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Errors:</strong>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($formData['full_name'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="dob" class="form-label">Date of Birth *</label>
                        <input type="date" class="form-control" id="dob" name="dob" 
                               value="<?php echo htmlspecialchars($formData['dob'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone (10 digits) *</label>
                        <input type="text" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>" 
                               placeholder="9876543210" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" class="form-control" id="address" name="address" 
                       value="<?php echo htmlspecialchars($formData['address'] ?? ''); ?>">
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="city" class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city" 
                               value="<?php echo htmlspecialchars($formData['city'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="state" class="form-label">State</label>
                        <input type="text" class="form-control" id="state" name="state" 
                               value="<?php echo htmlspecialchars($formData['state'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="zip_code" class="form-label">ZIP Code</label>
                        <input type="text" class="form-control" id="zip_code" name="zip_code" 
                               value="<?php echo htmlspecialchars($formData['zip_code'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="aadhar_number" class="form-label">Aadhar Number</label>
                        <input type="text" class="form-control" id="aadhar_number" name="aadhar_number" 
                               value="<?php echo htmlspecialchars($formData['aadhar_number'] ?? ''); ?>" 
                               placeholder="12 digits (optional)">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="pan_number" class="form-label">PAN Number</label>
                        <input type="text" class="form-control" id="pan_number" name="pan_number" 
                               value="<?php echo htmlspecialchars($formData['pan_number'] ?? ''); ?>" 
                               placeholder="10 characters (optional)">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="username" class="form-label">Username (min 5 chars) *</label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>" required>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password" class="form-label"><i class="bi bi-lock"></i> Password *</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               required onkeyup="checkPasswordStrength()">
                        <div class="password-strength-bar">
                            <div class="strength-indicator" id="strengthIndicator"></div>
                        </div>
                        <div class="password-strength-text">
                            Strength: <span id="strengthText">-</span>
                        </div>
                        <div class="password-info">
                            ✓ Minimum 8 characters<br>
                            ✓ Must include letters<br>
                            ✓ Must include numbers
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label"><i class="bi bi-lock-fill"></i> Confirm Password *</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                               required onkeyup="checkPasswordMatch()">
                        <div id="passwordMatchMsg" class="password-match-msg"></div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-register" id="registerBtn">
                <i class="bi bi-person-plus"></i> Create Account
            </button>
        </form>
        
        <div class="links">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthIndicator = document.getElementById('strengthIndicator');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[!@#$%^&*]/.test(password)) strength++;
            
            strengthIndicator.className = 'strength-indicator';
            
            if (strength === 0) {
                strengthText.textContent = 'None';
                strengthIndicator.style.width = '0%';
            } else if (strength <= 2) {
                strengthText.textContent = 'Weak';
                strengthIndicator.style.width = '33%';
                strengthIndicator.classList.add('weak');
            } else if (strength <= 3) {
                strengthText.textContent = 'Fair';
                strengthIndicator.style.width = '66%';
                strengthIndicator.classList.add('fair');
            } else {
                strengthText.textContent = 'Strong';
                strengthIndicator.style.width = '100%';
                strengthIndicator.classList.add('strong');
            }
            
            checkPasswordMatch();
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirm').value;
            const msg = document.getElementById('passwordMatchMsg');
            const btn = document.getElementById('registerBtn');
            
            if (confirm === '') {
                msg.innerHTML = '';
                msg.className = 'password-match-msg';
                return;
            }
            
            if (password === confirm) {
                msg.innerHTML = '<i class="bi bi-check-circle"></i> Passwords match';
                msg.className = 'password-match-msg match';
                btn.disabled = false;
            } else {
                msg.innerHTML = '<i class="bi bi-x-circle"></i> Passwords do not match';
                msg.className = 'password-match-msg mismatch';
                btn.disabled = true;
            }
        }
    </script>
</body>
</html>
