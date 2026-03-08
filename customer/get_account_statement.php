<?php
require_once '../includes/init.php';
requireCustomer();

$accountId = intval($_GET['account_id'] ?? 0);
$db = Database::getInstance();
$conn = $db->getConnection();

$userId = getCurrentUserId();

// Verify account belongs to customer
$stmt = $conn->prepare(
    "SELECT a.* FROM accounts a 
     JOIN customers c ON a.customer_id = c.customer_id 
     WHERE a.account_id = ? AND c.user_id = ?"
);
$stmt->bind_param("ii", $accountId, $userId);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account) {
    echo "Account not found.";
    exit();
}

// Get transactions for this account
$transactions = $conn->query(
    "SELECT * FROM transactions WHERE account_id = $accountId ORDER BY transaction_date DESC LIMIT 30"
);
?>

<div class="statement">
    <h6>Account Statement</h6>
    <p><strong>Account Number:</strong> <?php echo htmlspecialchars($account['account_number']); ?></p>
    <p><strong>Type:</strong> <?php echo htmlspecialchars($account['account_type']); ?></p>
    <p><strong>Current Balance:</strong> <?php echo formatCurrency($account['balance']); ?></p>
    <p><strong>Statement Date:</strong> <?php echo date('d-M-Y H:i:s'); ?></p>
    
    <hr>
    
    <?php if ($transactions->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($txn = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo formatDateTime($txn['transaction_date']); ?></td>
                            <td><?php echo htmlspecialchars($txn['transaction_type']); ?></td>
                            <td><?php echo formatCurrency($txn['amount']); ?></td>
                            <td><?php echo formatCurrency($txn['balance_after']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <hr>
        <p class="text-center">
            <button class="btn btn-sm btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
        </p>
    <?php else: ?>
        <p class="text-muted">No transactions found.</p>
    <?php endif; ?>
</div>
