<?php
$pageTitle = 'My Dashboard';
require_once 'header.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$userId = getCurrentUserId();

// Get customer info
$stmt = $conn->prepare("SELECT customer_id, full_name FROM customers WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer) {
    redirect('../login.php');
}

$customerId = $customer['customer_id'];

// Get accounts and statistics
$accountsResult = $conn->query("SELECT * FROM accounts WHERE customer_id = $customerId AND status = 'ACTIVE'");
$totalAccounts = $accountsResult->num_rows;
$totalBalance = 0;

while ($acc = $accountsResult->fetch_assoc()) {
    $totalBalance += $acc['balance'];
}

// Get recent transactions
$recentTxns = $conn->query(
    "SELECT t.*, a.account_number FROM transactions t 
     JOIN accounts a ON t.account_id = a.account_id 
     WHERE a.customer_id = $customerId 
     ORDER BY t.transaction_date DESC LIMIT 5"
);
?>

<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="welcome-card">
                <h2>Welcome back, <?php echo htmlspecialchars($customer['full_name']); ?>! 👋</h2>
                <p>Manage your bank accounts and transactions securely</p>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card bg-primary">
                <div class="stats-content">
                    <h3><?php echo $totalAccounts; ?></h3>
                    <p>Active Accounts</p>
                </div>
                <div class="stats-icon">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card bg-success">
                <div class="stats-content">
                    <h3><?php echo formatCurrency($totalBalance); ?></h3>
                    <p>Total Balance</p>
                </div>
                <div class="stats-icon">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card bg-info">
                <div class="stats-content">
                    <h3>
                        <?php
                        $count = $conn->query("SELECT COUNT(*) as cnt FROM transactions t JOIN accounts a ON t.account_id = a.account_id WHERE a.customer_id = $customerId")->fetch_assoc()['cnt'];
                        echo $count;
                        ?>
                    </h3>
                    <p>Total Transactions</p>
                </div>
                <div class="stats-icon">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2 d-sm-flex">
                        <a href="accounts.php" class="btn btn-primary">
                            <i class="bi bi-wallet2"></i> View Accounts
                        </a>
                        <a href="transfer.php" class="btn btn-success">
                            <i class="bi bi-arrow-left-right"></i> Transfer Funds
                        </a>
                        <a href="transactions.php" class="btn btn-info">
                            <i class="bi bi-receipt"></i> View Transactions
                        </a>
                        <a href="profile.php" class="btn btn-warning">
                            <i class="bi bi-person"></i> Update Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Transactions</h5>
                    <a href="transactions.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if ($recentTxns->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Type</th>
                                        <th>Account</th>
                                        <th>Amount</th>
                                        <th>Balance After</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($txn = $recentTxns->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo formatDateTime($txn['transaction_date']); ?></td>
                                            <td><?php echo getTransactionBadge($txn['transaction_type']); ?></td>
                                            <td><?php echo htmlspecialchars($txn['account_number']); ?></td>
                                            <td><?php echo formatCurrency($txn['amount']); ?></td>
                                            <td><?php echo formatCurrency($txn['balance_after']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No transactions yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
