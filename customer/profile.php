<?php
$pageTitle = 'My Profile';
require_once 'header.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$userId = getCurrentUserId();
$error = '';
$success = '';

// Get customer and user details
$stmt = $conn->prepare("SELECT * FROM customers WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');
    
    if ($action === 'update_profile') {
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        $state = sanitizeInput($_POST['state'] ?? '');
        $zip_code = sanitizeInput($_POST['zip_code'] ?? '');
        
        if (empty($phone) || !isValidPhone($phone)) {
            $error = 'Valid 10-digit phone number is required.';
        } else {
            $stmt = $conn->prepare(
                "UPDATE customers SET phone = ?, address = ?, city = ?, state = ?, zip_code = ? WHERE customer_id = ?"
            );
            $stmt->bind_param("sssssi", $phone, $address, $city, $state, $zip_code, $customer['customer_id']);
            
            if ($stmt->execute()) {
                $success = 'Profile updated successfully!';
                // Refresh customer data
                $stmt2 = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
                $stmt2->bind_param("i", $customer['customer_id']);
                $stmt2->execute();
                $customer = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();
            } else {
                $error = 'Error updating profile.';
            }
            $stmt->close();
        }
    } elseif ($action === 'change_password') {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Get user
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!verifyPassword($oldPassword, $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif (empty($newPassword) || empty($confirmPassword)) {
            $error = 'Please enter new password and confirmation.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } else {
            $passErrors = validatePassword($newPassword);
            if (!empty($passErrors)) {
                $error = implode(', ', $passErrors);
            } else {
                $newHash = hashPassword($newPassword);
                $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $stmt->bind_param("si", $newHash, $userId);
                
                if ($stmt->execute()) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Error changing password.';
                }
                $stmt->close();
            }
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Personal Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name (Not Editable)</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($customer['full_name']); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth (Not Editable)</label>
                                <input type="text" class="form-control" value="<?php echo formatDate($customer['dob']); ?>" disabled>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email (Not Editable)</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($customer['email']); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($customer['phone']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" 
                                   value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state" 
                                       value="<?php echo htmlspecialchars($customer['state'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="zip_code" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                       value="<?php echo htmlspecialchars($customer['zip_code'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="old_password" class="form-label">Current Password *</label>
                            <input type="password" class="form-control" id="old_password" name="old_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password *</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <small class="form-text text-muted">Min 8 chars, must include letters & numbers</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Customer Code:</strong></p>
                    <p><code><?php echo htmlspecialchars($customer['customer_code']); ?></code></p>
                    
                    <hr>
                    
                    <p><strong>Status:</strong></p>
                    <p><?php echo getStatusBadge($customer['registration_status']); ?></p>
                    
                    <hr>
                    
                    <p><strong>Member Since:</strong></p>
                    <p><?php echo formatDate($customer['created_at']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
