<?php
require_once '../includes/init.php';
requireAdmin();

$accountId = intval($_GET['account_id'] ?? 0);

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare(
    "SELECT a.*, c.full_name, c.email, c.phone FROM accounts a 
     JOIN customers c ON a.customer_id = c.customer_id 
     WHERE a.account_id = ?"
);
$stmt->bind_param("i", $accountId);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account) {
    echo "Account not found.";
    exit();
}

// Get recent transactions
$transactions = $conn->query(
    "SELECT * FROM transactions WHERE account_id = $accountId ORDER BY transaction_date DESC LIMIT 5"
);
?>

<table class="table table-borderless">
    <tr>
        <td><strong>Account Number:</strong></td>
        <td><?php echo htmlspecialchars($account['account_number']); ?></td>
    </tr>
    <tr>
        <td><strong>Customer:</strong></td>
        <td><?php echo htmlspecialchars($account['full_name']); ?></td>
    </tr>
    <tr>
        <td><strong>Email:</strong></td>
        <td><?php echo htmlspecialchars($account['email']); ?></td>
    </tr>
    <tr>
        <td><strong>Phone:</strong></td>
        <td><?php echo htmlspecialchars($account['phone']); ?></td>
    </tr>
    <tr>
        <td><strong>Account Type:</strong></td>
        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($account['account_type']); ?></span></td>
    </tr>
    <tr>
        <td><strong>Balance:</strong></td>
        <td><strong><?php echo formatCurrency($account['balance']); ?></strong></td>
    </tr>
    <tr>
        <td><strong>Status:</strong></td>
        <td><?php echo getStatusBadge($account['status']); ?></td>
    </tr>
    <tr>
        <td><strong>Opened On:</strong></td>
        <td><?php echo formatDate($account['opened_at']); ?></td>
    </tr>
    <tr>
        <td colspan="2"><strong>Recent Transactions</strong></td>
    </tr>
</table>

<?php if ($transactions->num_rows > 0): ?>
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Balance After</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($txn = $transactions->fetch_assoc()): ?>
                <tr>
                    <td><?php echo formatDateTime($txn['transaction_date']); ?></td>
                    <td><?php echo getTransactionBadge($txn['transaction_type']); ?></td>
                    <td><?php echo formatCurrency($txn['amount']); ?></td>
                    <td><?php echo formatCurrency($txn['balance_after']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p class="text-muted">No transactions found.</p>
<?php endif; ?>
