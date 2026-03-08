<?php
require_once '../includes/init.php';
requireCustomer();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/customer-style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3 class="mb-0"><i class="bi bi-bank"></i> My Bank</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a></li>
                <li><a href="accounts.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'accounts.php' ? 'active' : ''; ?>">
                    <i class="bi bi-wallet2"></i> My Accounts
                </a></li>
                <li><a href="transfer.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'transfer.php' ? 'active' : ''; ?>">
                    <i class="bi bi-arrow-left-right"></i> Fund Transfer
                </a></li>
                <li><a href="transactions.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'transactions.php' ? 'active' : ''; ?>">
                    <i class="bi bi-receipt"></i> Transactions
                </a></li>
                <li><a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person"></i> My Profile
                </a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?></h5>
                        <div class="user-info">
                            <span class="me-3">
                                <i class="bi bi-person-circle"></i>
                                <?php 
                                $user = getCurrentUserDetails();
                                $db = Database::getInstance();
                                $conn = $db->getConnection();
                                $stmt = $conn->prepare("SELECT full_name FROM customers WHERE user_id = ?");
                                $stmt->bind_param("i", $user['user_id']);
                                $stmt->execute();
                                $customer = $stmt->get_result()->fetch_assoc();
                                $stmt->close();
                                echo htmlspecialchars($customer['full_name'] ?? $user['username']);
                                ?>
                            </span>
                            <a href="../logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="page-content">
