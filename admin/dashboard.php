<?php
$pageTitle = 'Admin Dashboard';
require_once 'header.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Get statistics
$totalCustomers = $conn->query("SELECT COUNT(*) as count FROM customers WHERE registration_status = 'APPROVED'")->fetch_assoc()['count'];
$totalAccounts = $conn->query("SELECT COUNT(*) as count FROM accounts WHERE status = 'ACTIVE'")->fetch_assoc()['count'];
$pendingRequests = $conn->query("SELECT COUNT(*) as count FROM registration_requests WHERE request_status = 'PENDING'")->fetch_assoc()['count'];
$totalBalance = $conn->query("SELECT COALESCE(SUM(balance), 0) as total FROM accounts WHERE status = 'ACTIVE'")->fetch_assoc()['total'];
$totalTransactions = $conn->query("SELECT COUNT(*) as count FROM transactions")->fetch_assoc()['count'];

// Recent transactions
$recentTxns = $conn->query(
    "SELECT t.*, a.account_number, c.full_name, r.account_number as related_account 
     FROM transactions t 
     JOIN accounts a ON t.account_id = a.account_id 
     JOIN customers c ON a.customer_id = c.customer_id 
     LEFT JOIN accounts r ON t.related_account_id = r.account_id
     ORDER BY t.transaction_date DESC LIMIT 10"
);
?>

<div class="container-fluid">
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card bg-primary">
                <div class="stats-content">
                    <h3><?php echo $totalCustomers; ?></h3>
                    <p>Total Customers</p>
                </div>
                <div class="stats-icon">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card bg-success">
                <div class="stats-content">
                    <h3><?php echo $totalAccounts; ?></h3>
                    <p>Active Accounts</p>
                </div>
                <div class="stats-icon">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card bg-warning">
                <div class="stats-content">
                    <h3><?php echo $pendingRequests; ?></h3>
                    <p>Pending Requests</p>
                </div>
                <div class="stats-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card bg-info">
                <div class="stats-content">
                    <h3><?php echo formatCurrency($totalBalance); ?></h3>
                    <p>Total Balance</p>
                </div>
                <div class="stats-icon">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Transactions</h5>
                    <a href="transactions.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if ($recentTxns->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Customer</th>
                                        <th>Account</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Date & Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($txn = $recentTxns->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo $txn['transaction_id']; ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($txn['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($txn['account_number']); ?></td>
                                            <td><?php echo getTransactionBadge($txn['transaction_type']); ?></td>
                                            <td><?php echo formatCurrency($txn['amount']); ?></td>
                                            <td><?php echo formatDateTime($txn['transaction_date']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No transactions found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="registrations.php" class="btn btn-outline-primary">
                            <i class="bi bi-file-earmark-check"></i> Approve Registrations
                        </a>
                        <a href="accounts.php?action=create" class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle"></i> Create Account
                        </a>
                        <a href="transactions.php?action=deposit" class="btn btn-outline-success">
                            <i class="bi bi-arrow-down-circle"></i> Process Deposit
                        </a>
                        <a href="transactions.php?action=withdraw" class="btn btn-outline-danger">
                            <i class="bi bi-arrow-up-circle"></i> Process Withdrawal
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">System Statistics</h5>
                </div>
                <div class="card-body">
                    <p><strong>Total Transactions:</strong> <?php echo $totalTransactions; ?></p>
                    <p><strong>Active Accounts:</strong> <?php echo $totalAccounts; ?></p>
                    <p><strong>Registered Customers:</strong> <?php echo $totalCustomers; ?></p>
                    <p><strong>Total Bank Balance:</strong> <?php echo formatCurrency($totalBalance); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
