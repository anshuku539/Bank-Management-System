<?php
require_once 'includes/init.php';

requireAuth();

$db = Database::getInstance();
$conn = $db->getConnection();

// Log logout
$userId = getCurrentUserId();
logActivity($userId, 'LOGOUT', 'User logged out');

// Destroy session
session_destroy();

redirectWithMessage('login.php', 'You have been logged out successfully.', 'success');
?>
