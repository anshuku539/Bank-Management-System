<?php
/**
 * Navbar Component
 * Displays navigation bar for all pages
 * Responsive navbar with Bootstrap 5
 */

// Determine current page for active link
$current_page = basename($_SERVER['PHP_SELF']);
$is_authenticated = isAuthenticated();
$is_admin = $is_authenticated && isAdmin();
$is_customer = $is_authenticated && isCustomer();
$username = $_SESSION['username'] ?? '';
?>

<style>
    :root {
        --primary: #667eea;
        --secondary: #764ba2;
    }

    /* NAVBAR STYLING */
    .navbar {
        background: white !important;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 15px 0;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .navbar-brand {
        font-size: 24px;
        font-weight: bold;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-right: 30px;
    }

    .navbar-brand i {
        margin-right: 8px;
    }

    .nav-link {
        margin: 0 10px;
        color: #333 !important;
        font-weight: 500;
        transition: all 0.3s;
    }

    .nav-link:hover {
        color: var(--primary) !important;
        transform: translateY(-2px);
    }

    .nav-link.active {
        color: var(--primary) !important;
        border-bottom: 2px solid var(--primary);
        padding-bottom: 3px;
    }

    .navbar-user {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-left: 20px;
        padding-left: 20px;
        border-left: 2px solid #eee;
    }

    .user-name {
        color: #333;
        font-weight: 600;
        font-size: 14px;
    }

    .user-role {
        color: var(--primary);
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        background: #f0f4ff;
        padding: 3px 8px;
        border-radius: 12px;
    }

    .navbar-buttons {
        display: flex;
        gap: 10px;
    }

    .btn-nav-link {
        color: var(--primary);
        padding: 8px 16px;
        border-radius: 5px;
        font-weight: 600;
        transition: all 0.3s;
        text-decoration: none;
        border: 2px solid var(--primary);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-nav-link:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
    }

    .btn-nav-primary {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        border: none;
    }

    .btn-nav-primary:hover {
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-logout {
        color: #e74c3c;
        border-color: #e74c3c;
    }

    .btn-logout:hover {
        background: #e74c3c;
        color: white;
    }

    @media (max-width: 768px) {
        .navbar-brand {
            font-size: 20px;
            margin-right: 15px;
        }

        .navbar-user {
            margin-left: 0;
            padding-left: 0;
            border-left: none;
            margin-top: 10px;
            flex-direction: column;
            gap: 8px;
        }

        .navbar-buttons {
            width: 100%;
            flex-direction: column;
            gap: 10px;
            padding-top: 15px;
        }

        .btn-nav-link, .btn-nav-primary {
            width: 100%;
            text-align: center;
            justify-content: center;
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-lg">
        <a class="navbar-brand" href="<?php echo $is_admin ? 'admin/dashboard.php' : ($is_customer ? 'customer/dashboard.php' : 'index.php'); ?>">
            <i class="bi bi-bank2"></i><?php echo SITE_NAME; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (!$is_authenticated): ?>
                    <!-- Guest Navigation -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" 
                           href="index.php">
                            <i class="bi bi-house-fill"></i> Home
                        </a>
                    </li>
                <?php elseif ($is_admin): ?>
                    <!-- Admin Navigation -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'registrations.php' ? 'active' : ''; ?>" 
                           href="registrations.php">
                            <i class="bi bi-person-check"></i> Registrations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'customers.php' ? 'active' : ''; ?>" 
                           href="customers.php">
                            <i class="bi bi-people"></i> Customers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'accounts.php' ? 'active' : ''; ?>" 
                           href="accounts.php">
                            <i class="bi bi-credit-card"></i> Accounts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'transactions.php' ? 'active' : ''; ?>" 
                           href="transactions.php">
                            <i class="bi bi-arrow-left-right"></i> Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" 
                           href="reports.php">
                            <i class="bi bi-bar-chart"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'activity_logs.php' ? 'active' : ''; ?>" 
                           href="activity_logs.php">
                            <i class="bi bi-file-earmark-text"></i> Activity
                        </a>
                    </li>
                <?php elseif ($is_customer): ?>
                    <!-- Customer Navigation -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                            <i class="bi bi-house-fill"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'accounts.php' ? 'active' : ''; ?>" 
                           href="accounts.php">
                            <i class="bi bi-wallet2"></i> My Accounts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'transfer.php' ? 'active' : ''; ?>" 
                           href="transfer.php">
                            <i class="bi bi-arrow-left-right"></i> Transfer
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'transactions.php' ? 'active' : ''; ?>" 
                           href="transactions.php">
                            <i class="bi bi-receipt"></i> Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" 
                           href="profile.php">
                            <i class="bi bi-person"></i> Profile
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="navbar-buttons">
                <?php if ($is_authenticated): ?>
                    <!-- User Info & Logout -->
                    <div class="navbar-user">
                        <div>
                            <div class="user-name">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($username); ?>
                            </div>
                            <div class="user-role">
                                <?php echo $is_admin ? 'Admin' : 'Customer'; ?>
                            </div>
                        </div>
                    </div>
                    <a href="<?php echo $is_admin ? '../logout.php' : '../logout.php'; ?>" class="btn-nav-link btn-logout">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                <?php else: ?>
                    <!-- Guest Buttons -->
                    <a href="login.php" class="btn-nav-link">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                    <a href="register.php" class="btn-nav-link btn-nav-primary">
                        <i class="bi bi-person-plus"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
