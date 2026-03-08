<?php
require_once '../includes/init.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin-style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3 class="mb-0"><i class="bi bi-bank"></i> Bank Admin</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a></li>
                <li><a href="registrations.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'registrations.php' ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-check"></i> Registrations
                </a></li>
                <li><a href="customers.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i> Customers
                </a></li>
                <li><a href="accounts.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'accounts.php' ? 'active' : ''; ?>">
                    <i class="bi bi-wallet2"></i> Accounts
                </a></li>
                <li><a href="transactions.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'transactions.php' ? 'active' : ''; ?>">
                    <i class="bi bi-arrow-left-right"></i> Transactions
                </a></li>
                <li><a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                    <i class="bi bi-bar-chart"></i> Reports
                </a></li>
                <li><a href="activity_logs.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'activity_logs.php' ? 'active' : ''; ?>">
                    <i class="bi bi-clock-history"></i> Activity Logs
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
                                echo htmlspecialchars($user['username'] ?? '');
                                ?>
                            </span>
                            <a href="../logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="page-content">
