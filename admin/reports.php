<?php
$pageTitle = 'Reports';
require_once 'header.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Get date range filters
$dateFrom = sanitizeInput($_GET['date_from'] ?? date('Y-m-01'));
$dateTo = sanitizeInput($_GET['date_to'] ?? date('Y-m-d'));

// Statistics by date range
$totalTxns = $conn->query(
    "SELECT COUNT(*) as count FROM transactions 
     WHERE DATE(transaction_date) BETWEEN '$dateFrom' AND '$dateTo'"
)->fetch_assoc()['count'];

$totalDeposits = $conn->query(
    "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
     WHERE transaction_type = 'DEPOSIT' AND DATE(transaction_date) BETWEEN '$dateFrom' AND '$dateTo'"
)->fetch_assoc()['total'];

$totalWithdrawals = $conn->query(
    "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
     WHERE transaction_type = 'WITHDRAW' AND DATE(transaction_date) BETWEEN '$dateFrom' AND '$dateTo'"
)->fetch_assoc()['total'];

$totalTransfers = $conn->query(
    "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
     WHERE transaction_type = 'TRANSFER' AND DATE(transaction_date) BETWEEN '$dateFrom' AND '$dateTo'"
)->fetch_assoc()['total'];

// Customer growth
$totalCustomers = $conn->query(
    "SELECT COUNT(*) as count FROM customers 
     WHERE registration_status = 'APPROVED' AND DATE(created_at) <= '$dateTo'"
)->fetch_assoc()['count'];

// Account statistics
$totalAccounts = $conn->query(
    "SELECT COUNT(*) as count FROM accounts 
     WHERE status = 'ACTIVE' AND DATE(opened_at) <= '$dateTo'"
)->fetch_assoc()['count'];

$totalBalance = $conn->query(
    "SELECT COALESCE(SUM(balance), 0) as total FROM accounts 
     WHERE status = 'ACTIVE'"
)->fetch_assoc()['total'];
?>

<div class="container-fluid">
    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="dateFrom" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="dateFrom" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                <div class="col-md-3">
                    <label for="dateTo" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="dateTo" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                <div class="col-md-6">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card bg-primary">
                <div class="stats-content">
                    <h3><?php echo $totalCustomers; ?></h3>
                    <p>Total Customers</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card bg-success">
                <div class="stats-content">
                    <h3><?php echo $totalAccounts; ?></h3>
                    <p>Total Accounts</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card bg-info">
                <div class="stats-content">
                    <h3><?php echo formatCurrency($totalBalance); ?></h3>
                    <p>Total Balance</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card bg-warning">
                <div class="stats-content">
                    <h3><?php echo $totalTxns; ?></h3>
                    <p>Total Transactions</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Transaction Summary -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Transaction Summary</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Total Deposits:</strong></td>
                            <td class="text-end"><span class="badge bg-success"><?php echo formatCurrency($totalDeposits); ?></span></td>
                        </tr>
                        <tr>
                            <td><strong>Total Withdrawals:</strong></td>
                            <td class="text-end"><span class="badge bg-danger"><?php echo formatCurrency($totalWithdrawals); ?></span></td>
                        </tr>
                        <tr>
                            <td><strong>Total Transfers:</strong></td>
                            <td class="text-end"><span class="badge bg-info"><?php echo formatCurrency($totalTransfers); ?></span></td>
                        </tr>
                        <tr class="table-active">
                            <td><strong>Net Change:</strong></td>
                            <td class="text-end"><strong><?php echo formatCurrency($totalDeposits + $totalTransfers - $totalWithdrawals); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Customers (by balance)</h5>
                </div>
                <div class="card-body">
                    <?php
                    $topCustomers = $conn->query(
                        "SELECT c.full_name, SUM(a.balance) as total_balance, COUNT(a.account_id) as account_count 
                         FROM customers c 
                         JOIN accounts a ON c.customer_id = a.customer_id 
                         WHERE a.status = 'ACTIVE' 
                         GROUP BY c.customer_id 
                         ORDER BY total_balance DESC 
                         LIMIT 10"
                    );
                    ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Total Balance</th>
                                <th>Accounts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($cust = $topCustomers->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cust['full_name']); ?></td>
                                    <td><?php echo formatCurrency($cust['total_balance']); ?></td>
                                    <td><?php echo $cust['account_count']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
