<?php
$pageTitle = 'My Accounts';
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

// Get accounts
$accounts = $conn->query(
    "SELECT * FROM accounts WHERE customer_id = $customerId ORDER BY opened_at DESC"
);
?>

<div class="container-fluid">
    <?php displayMessage(); ?>
    
    <div class="row">
        <?php while ($account = $accounts->fetch_assoc()): ?>
            <div class="col-md-6 mb-4">
                <div class="card account-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title"><?php echo htmlspecialchars($account['account_type']); ?> Account</h5>
                                <p class="text-muted">Account #<?php echo htmlspecialchars($account['account_number']); ?></p>
                            </div>
                            <div>
                                <?php echo getStatusBadge($account['status']); ?>
                            </div>
                        </div>
                        
                        <div class="balance-section mb-3">
                            <p class="text-muted mb-1">Current Balance</p>
                            <h2 class="balance"><?php echo formatCurrency($account['balance']); ?></h2>
                        </div>
                        
                        <div class="account-details">
                            <p class="mb-1"><small><strong>Account Type:</strong> <?php echo htmlspecialchars($account['account_type']); ?></small></p>
                            <p class="mb-1"><small><strong>Opened:</strong> <?php echo formatDate($account['opened_at']); ?></small></p>
                            <p class="mb-0"><small><strong>Status:</strong> <?php echo htmlspecialchars($account['status']); ?></small></p>
                        </div>
                        
                        <div class="mt-3">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#statementModal"
                                    onclick="viewStatement(<?php echo $account['account_id']; ?>)">
                                View Statement
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Statement Modal -->
<div class="modal fade" id="statementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Account Statement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="statementModalBody">
                <!-- Loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
function viewStatement(accountId) {
    const xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            document.getElementById('statementModalBody').innerHTML = this.responseText;
        }
    };
    xhr.open('GET', 'get_account_statement.php?account_id=' + accountId, true);
    xhr.send();
}
</script>

<?php require_once 'footer.php'; ?>
