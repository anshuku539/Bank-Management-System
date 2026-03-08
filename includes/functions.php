<?php
// Common Functions

/**
 * Hash password using bcrypt
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_HASH_ALGO);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long.";
    }
    
    if (!preg_match('/[a-zA-Z]/', $password)) {
        $errors[] = "Password must contain at least one letter.";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    }
    
    return $errors;
}

/**
 * Generate unique account number
 */
function generateAccountNumber() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    do {
        $accountNumber = str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
        $result = $conn->query("SELECT account_id FROM accounts WHERE account_number = '$accountNumber'");
    } while ($result->num_rows > 0);
    
    return $accountNumber;
}

/**
 * Generate unique customer ID
 */
function generateCustomerId() {
    return "CUST" . date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if email is valid
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if phone is valid (10 digits)
 */
function isValidPhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

/**
 * Redirect to page
 */
function redirect($page) {
    header("Location: " . SITE_URL . "/" . $page);
    exit();
}

/**
 * Redirect with message
 */
function redirectWithMessage($page, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    redirect($page);
}

/**
 * Display message
 */
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'success';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        
        echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return "₹ " . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date) {
    return date('d-M-Y', strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime) {
    return date('d-M-Y H:i:s', strtotime($datetime));
}

/**
 * Get transaction type badge
 */
function getTransactionBadge($type) {
    $badges = [
        TXN_DEPOSIT => '<span class="badge bg-success">Deposit</span>',
        TXN_WITHDRAW => '<span class="badge bg-danger">Withdraw</span>',
        TXN_TRANSFER => '<span class="badge bg-info">Transfer</span>'
    ];
    return $badges[$type] ?? $type;
}

/**
 * Get status badge
 */
function getStatusBadge($status) {
    $badges = [
        STATUS_ACTIVE => '<span class="badge bg-success">Active</span>',
        STATUS_INACTIVE => '<span class="badge bg-warning">Inactive</span>',
        STATUS_PENDING => '<span class="badge bg-info">Pending</span>',
        STATUS_REJECTED => '<span class="badge bg-danger">Rejected</span>',
        STATUS_CLOSED => '<span class="badge bg-secondary">Closed</span>'
    ];
    return $badges[$status] ?? $status;
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (!isAuthenticated()) {
        return false;
    }
    
    $current_time = time();
    $last_activity = $_SESSION['last_activity'] ?? $current_time;
    
    if (($current_time - $last_activity) > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = $current_time;
    return true;
}

/**
 * Get user role
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isAuthenticated() && getUserRole() === ROLE_ADMIN;
}

/**
 * Check if user is customer
 */
function isCustomer() {
    return isAuthenticated() && getUserRole() === ROLE_CUSTOMER;
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!checkSessionTimeout()) {
        redirectWithMessage('login.php', 'Session expired. Please login again.', 'warning');
    }
}

/**
 * Require admin
 */
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        redirectWithMessage('index.php', 'You do not have permission to access this page.', 'danger');
    }
}

/**
 * Require customer
 */
function requireCustomer() {
    requireAuth();
    if (!isCustomer()) {
        redirectWithMessage('index.php', 'You do not have permission to access this page.', 'danger');
    }
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user details
 */
function getCurrentUserDetails() {
    $userId = getCurrentUserId();
    if (!$userId) return null;
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Log user activity
 */
function logActivity($userId, $action, $description) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $action, $description, $ipAddress, $userAgent);
    $stmt->execute();
    $stmt->close();
}

/**
 * Check if username exists
 */
function usernameExists($username) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

/**
 * Check if email exists in registration requests
 */
function emailInRegistration($email) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $pending = 'PENDING';
    
    $stmt = $conn->prepare("SELECT request_id FROM registration_requests WHERE email = ? AND request_status = ?");
    $stmt->bind_param("ss", $email, $pending);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}
?>
