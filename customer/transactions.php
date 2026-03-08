<?php
$pageTitle = 'My Transactions';
require_once 'header.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$userId = getCurrentUserId();

// Get customer
$stmt = $conn->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

$customerId = $customer['customer_id'];

// Get filters
$searchQuery = sanitizeInput($_GET['search'] ?? '');
$txnType = sanitizeInput($_GET['type'] ?? '');
$dateFrom = sanitizeInput($_GET['date_from'] ?? date('Y-m-01'));
$dateTo = sanitizeInput($_GET['date_to'] ?? date('Y-m-d'));

// Build query
$sql = "SELECT t.*, a.account_number FROM transactions t 
        JOIN accounts a ON t.account_id = a.account_id 
        WHERE a.customer_id = $customerId";

if (!empty($txnType)) {
    $sql .= " AND t.transaction_type = '" . $conn->real_escape_string($txnType) . "'";
}

if (!empty($dateFrom)) {
    $sql .= " AND DATE(t.transaction_date) >= '$dateFrom'";
}

if (!empty($dateTo)) {
    $sql .= " AND DATE(t.transaction_date) <= '$dateTo'";
}

$sql .= " ORDER BY t.transaction_date DESC LIMIT 100";

$transactions = $conn->query($sql);
?>

<div class="container-fluid">
    <!-- Filters -->
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
                <div class="col-md-3">
                    <label for="typeFilter" class="form-label">Type</label>
                    <select class="form-select" id="typeFilter" name="type">
                        <option value="">All Types</option>
                        <option value="DEPOSIT" <?php echo $txnType === 'DEPOSIT' ? 'selected' : ''; ?>>Deposit</option>
                        <option value="WITHDRAW" <?php echo $txnType === 'WITHDRAW' ? 'selected' : ''; ?>>Withdraw</option>
                        <option value="TRANSFER" <?php echo $txnType === 'TRANSFER' ? 'selected' : ''; ?>>Transfer</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Transactions Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Transaction History (<?php echo $transactions->num_rows; ?> records)</h5>
        </div>
        <div class="card-body">
            <?php if ($transactions->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Account</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Balance After</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($txn = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo formatDateTime($txn['transaction_date']); ?></td>
                                    <td><?php echo htmlspecialchars($txn['account_number']); ?></td>
                                    <td><?php echo getTransactionBadge($txn['transaction_type']); ?></td>
                                    <td><?php echo formatCurrency($txn['amount']); ?></td>
                                    <td><?php echo formatCurrency($txn['balance_after']); ?></td>
                                    <td><?php echo htmlspecialchars($txn['remark'] ?? '-'); ?></td>
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

<?php require_once 'footer.php'; ?>
