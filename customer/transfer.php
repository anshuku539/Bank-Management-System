<?php
$pageTitle = 'Fund Transfer';
require_once 'header.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$userId = getCurrentUserId();
$error = '';
$success = '';

// Get customer
$stmt = $conn->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

$customerId = $customer['customer_id'];

// Handle transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fromAccountId = intval($_POST['from_account'] ?? 0);
    $toAccountNumber = sanitizeInput($_POST['to_account_number'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $remark = sanitizeInput($_POST['remark'] ?? '');
    
    // Validate from account belongs to customer
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE account_id = ? AND customer_id = ? AND status = 'ACTIVE'");
    $stmt->bind_param("ii", $fromAccountId, $customerId);
    $stmt->execute();
    $fromAccount = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$fromAccount) {
        $error = 'Invalid from account.';
    } elseif (empty($toAccountNumber)) {
        $error = 'Please enter recipient account number.';
    } elseif ($amount <= 0) {
        $error = 'Amount must be greater than zero.';
    } elseif ($amount > $fromAccount['balance']) {
        $error = 'Insufficient balance.';
    } else {
        // Find recipient account
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE account_number = ? AND status = 'ACTIVE'");
        $stmt->bind_param("s", $toAccountNumber);
        $stmt->execute();
        $toAccount = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$toAccount) {
            $error = 'Recipient account not found or inactive.';
        } else {
            // Process transfer
            $conn->begin_transaction();
            
            try {
                // Deduct from sender
                $newFromBalance = $fromAccount['balance'] - $amount;
                $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE account_id = ?");
                $stmt->bind_param("di", $newFromBalance, $fromAccountId);
                $stmt->execute();
                $stmt->close();
                
                // Add to recipient
                $newToBalance = $toAccount['balance'] + $amount;
                $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE account_id = ?");
                $stmt->bind_param("di", $newToBalance, $toAccount['account_id']);
                $stmt->execute();
                $stmt->close();
                
                // Create transaction for sender
                $txnType = 'TRANSFER';
                $stmt = $conn->prepare(
                    "INSERT INTO transactions (account_id, transaction_type, amount, related_account_id, balance_before, balance_after, remark, processed_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("isddddssi", $fromAccountId, $txnType, $amount, $toAccount['account_id'], $fromAccount['balance'], $newFromBalance, $remark, $userId);
                $stmt->execute();
                $stmt->close();
                
                // Create transaction for recipient
                $stmt = $conn->prepare(
                    "INSERT INTO transactions (account_id, transaction_type, amount, related_account_id, balance_before, balance_after, remark, processed_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("isddddssi", $toAccount['account_id'], $txnType, $amount, $fromAccountId, $toAccount['balance'], $newToBalance, $remark, $userId);
                $stmt->execute();
                $stmt->close();
                
                $conn->commit();
                
                redirectWithMessage(
                    'transfer.php',
                    'Transfer successful! ' . formatCurrency($amount) . ' transferred to account ' . htmlspecialchars($toAccountNumber),
                    'success'
                );
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Error processing transfer: ' . $e->getMessage();
            }
        }
    }
}

// Get customer accounts
$accounts = $conn->query("SELECT * FROM accounts WHERE customer_id = $customerId AND status = 'ACTIVE'");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <?php displayMessage(); ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-arrow-left-right"></i> Transfer Funds
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($accounts->num_rows > 0): ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="from_account" class="form-label">From Account *</label>
                                <select class="form-select" id="from_account" name="from_account" required onchange="updateBalance()">
                                    <option value="">-- Select From Account --</option>
                                    <?php
                                    $accounts->data_seek(0);
                                    while ($acc = $accounts->fetch_assoc()) {
                                        echo "<option value='{$acc['account_id']}' data-balance='{$acc['balance']}'>";
                                        echo htmlspecialchars($acc['account_number'] . ' - ' . formatCurrency($acc['balance']));
                                        echo "</option>";
                                    }
                                    ?>
                                </select>
                                <div class="form-text">Available balance: <strong id="availableBalance">₹ 0.00</strong></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="to_account_number" class="form-label">To Account Number *</label>
                                <input type="text" class="form-control" id="to_account_number" name="to_account_number" 
                                       placeholder="Enter recipient account number" required>
                                <small class="form-text text-muted">Account must be within this bank and active</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount (₹) *</label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       min="0.01" step="0.01" placeholder="0.00" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="remark" class="form-label">Remark (Optional)</label>
                                <textarea class="form-control" id="remark" name="remark" rows="3" 
                                          placeholder="Add a note for this transfer"></textarea>
                            </div>
                            
                            <div class="alert alert-info mb-3">
                                <strong><i class="bi bi-info-circle"></i> Note:</strong> 
                                Please verify the recipient account number before proceeding. Once transferred, funds cannot be reversed.
                            </div>
                            
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                <i class="bi bi-arrow-left-right"></i> Transfer Funds
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> 
                            You don't have any active accounts. Please contact the bank to create an account.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Transfer Guide</h5>
                </div>
                <div class="card-body">
                    <h6>Steps to transfer funds:</h6>
                    <ol>
                        <li>Select your account from the dropdown</li>
                        <li>Enter the recipient's account number</li>
                        <li>Enter the amount to transfer</li>
                        <li>Add an optional remark</li>
                        <li>Click "Transfer Funds"</li>
                    </ol>
                    
                    <hr>
                    
                    <h6>Important:</h6>
                    <ul>
                        <li>Transfers are instant</li>
                        <li>No charges applied</li>
                        <li>Minimum balance required</li>
                        <li>Account must be active</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateBalance() {
    const select = document.getElementById('from_account');
    const option = select.options[select.selectedIndex];
    const balance = parseFloat(option.getAttribute('data-balance')) || 0;
    document.getElementById('availableBalance').textContent = '₹ ' + balance.toFixed(2);
}
</script>

<?php require_once 'footer.php'; ?>
