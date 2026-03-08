<?php
$pageTitle = 'Manage Transactions';
require_once 'header.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Handle deposit/withdrawal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');
    $accountId = intval($_POST['account_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $remark = sanitizeInput($_POST['remark'] ?? '');
    
    if ($action === 'deposit' || $action === 'withdraw') {
        // Validate
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE account_id = ? AND status = 'ACTIVE'");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$account) {
            $_SESSION['message'] = 'Invalid or inactive account.';
            $_SESSION['message_type'] = 'danger';
        } elseif ($amount <= 0) {
            $_SESSION['message'] = 'Amount must be greater than zero.';
            $_SESSION['message_type'] = 'danger';
        } elseif ($action === 'withdraw' && $account['balance'] < $amount) {
            $_SESSION['message'] = 'Insufficient balance.';
            $_SESSION['message_type'] = 'danger';
        } else {
            // Process transaction
            $conn->begin_transaction();
            
            try {
                $balanceBefore = $account['balance'];
                $txnType = ($action === 'deposit') ? TXN_DEPOSIT : TXN_WITHDRAW;
                
                if ($action === 'deposit') {
                    $newBalance = $balanceBefore + $amount;
                } else {
                    $newBalance = $balanceBefore - $amount;
                }
                
                // Update balance
                $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE account_id = ?");
                $stmt->bind_param("di", $newBalance, $accountId);
                $stmt->execute();
                $stmt->close();
                
                // Create transaction record
                $adminId = getCurrentUserId();
                $stmt = $conn->prepare(
                    "INSERT INTO transactions (account_id, transaction_type, amount, balance_before, balance_after, remark, processed_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("isdddss", $accountId, $txnType, $amount, $balanceBefore, $newBalance, $remark, $adminId);
                $stmt->execute();
                $stmt->close();
                
                $conn->commit();
                
                $_SESSION['message'] = ucfirst($action) . ' processed successfully!';
                $_SESSION['message_type'] = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['message'] = 'Error processing transaction: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
            }
        }
        
        header("Location: transactions.php");
        exit();
    }
}

// Get transactions with filters
$searchQuery = sanitizeInput($_GET['search'] ?? '');
$txnType = sanitizeInput($_GET['type'] ?? '');
$dateFrom = sanitizeInput($_GET['date_from'] ?? '');
$dateTo = sanitizeInput($_GET['date_to'] ?? '');

$sql = "SELECT t.*, a.account_number, c.full_name, r.account_number as related_account 
        FROM transactions t 
        JOIN accounts a ON t.account_id = a.account_id 
        JOIN customers c ON a.customer_id = c.customer_id 
        LEFT JOIN accounts r ON t.related_account_id = r.account_id
        WHERE 1=1";

$params = [];

if (!empty($txnType)) {
    $sql .= " AND t.transaction_type = ?";
    $params[] = $txnType;
}

if (!empty($searchQuery)) {
    $sql .= " AND (a.account_number LIKE ? OR c.full_name LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
}

if (!empty($dateFrom)) {
    $sql .= " AND DATE(t.transaction_date) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $sql .= " AND DATE(t.transaction_date) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY t.transaction_date DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$transactions = $stmt->get_result();
$stmt->close();
?>

<div class="container-fluid">
    <?php displayMessage(); ?>
    
    <!-- Transaction Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#viewTab">View Transactions</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#processTab">Process Deposit/Withdraw</a>
        </li>
    </ul>
    
    <div class="tab-content">
        <!-- View Transactions Tab -->
        <div id="viewTab" class="tab-pane fade show active">
            <!-- Filters -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search account or customer..." 
                           value="<?php echo htmlspecialchars($searchQuery); ?>" onchange="filterTransactions()">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="typeFilter" onchange="filterTransactions()">
                        <option value="">All Types</option>
                        <option value="DEPOSIT" <?php echo $txnType === 'DEPOSIT' ? 'selected' : ''; ?>>Deposit</option>
                        <option value="WITHDRAW" <?php echo $txnType === 'WITHDRAW' ? 'selected' : ''; ?>>Withdraw</option>
                        <option value="TRANSFER" <?php echo $txnType === 'TRANSFER' ? 'selected' : ''; ?>>Transfer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" id="dateFrom" value="<?php echo htmlspecialchars($dateFrom); ?>" 
                           onchange="filterTransactions()">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" id="dateTo" value="<?php echo htmlspecialchars($dateTo); ?>" 
                           onchange="filterTransactions()">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" onclick="filterTransactions()">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </div>
            
            <!-- Transactions Table -->
            <div class="card">
                <div class="card-body">
                    <?php if ($transactions->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Customer / Account</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Balance After</th>
                                        <th>Date & Time</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($txn = $transactions->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $txn['transaction_id']; ?></strong></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($txn['full_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($txn['account_number']); ?></small>
                                            </td>
                                            <td><?php echo getTransactionBadge($txn['transaction_type']); ?></td>
                                            <td><?php echo formatCurrency($txn['amount']); ?></td>
                                            <td><?php echo formatCurrency($txn['balance_after']); ?></td>
                                            <td><?php echo formatDateTime($txn['transaction_date']); ?></td>
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
        
        <!-- Process Deposit/Withdraw Tab -->
        <div id="processTab" class="tab-pane fade">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-arrow-down-circle"></i> Process Deposit
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="deposit">
                                
                                <div class="mb-3">
                                    <label for="deposit_account" class="form-label">Select Account *</label>
                                    <select class="form-select" id="deposit_account" name="account_id" required>
                                        <option value="">-- Choose Account --</option>
                                        <?php
                                        $accounts = $conn->query(
                                            "SELECT a.account_id, a.account_number, c.full_name, a.balance 
                                             FROM accounts a 
                                             JOIN customers c ON a.customer_id = c.customer_id 
                                             WHERE a.status = 'ACTIVE' 
                                             ORDER BY c.full_name"
                                        );
                                        while ($acc = $accounts->fetch_assoc()) {
                                            echo "<option value='{$acc['account_id']}'>";
                                            echo htmlspecialchars($acc['full_name'] . ' - ' . $acc['account_number'] . ' (' . formatCurrency($acc['balance']) . ')');
                                            echo "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="deposit_amount" class="form-label">Amount (₹) *</label>
                                    <input type="number" class="form-control" id="deposit_amount" name="amount" 
                                           min="0.01" step="0.01" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="deposit_remark" class="form-label">Remark</label>
                                    <textarea class="form-control" id="deposit_remark" name="remark" rows="2"></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-arrow-down-circle"></i> Process Deposit
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-arrow-up-circle"></i> Process Withdrawal
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="withdraw">
                                
                                <div class="mb-3">
                                    <label for="withdraw_account" class="form-label">Select Account *</label>
                                    <select class="form-select" id="withdraw_account" name="account_id" required>
                                        <option value="">-- Choose Account --</option>
                                        <?php
                                        $accounts = $conn->query(
                                            "SELECT a.account_id, a.account_number, c.full_name, a.balance 
                                             FROM accounts a 
                                             JOIN customers c ON a.customer_id = c.customer_id 
                                             WHERE a.status = 'ACTIVE' 
                                             ORDER BY c.full_name"
                                        );
                                        while ($acc = $accounts->fetch_assoc()) {
                                            echo "<option value='{$acc['account_id']}'>";
                                            echo htmlspecialchars($acc['full_name'] . ' - ' . $acc['account_number'] . ' (' . formatCurrency($acc['balance']) . ')');
                                            echo "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="withdraw_amount" class="form-label">Amount (₹) *</label>
                                    <input type="number" class="form-control" id="withdraw_amount" name="amount" 
                                           min="0.01" step="0.01" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="withdraw_remark" class="form-label">Remark</label>
                                    <textarea class="form-control" id="withdraw_remark" name="remark" rows="2"></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="bi bi-arrow-up-circle"></i> Process Withdrawal
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterTransactions() {
    const search = document.getElementById('searchInput').value;
    const type = document.getElementById('typeFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    let url = 'transactions.php';
    const params = [];
    
    if (search) params.push('search=' + encodeURIComponent(search));
    if (type) params.push('type=' + encodeURIComponent(type));
    if (dateFrom) params.push('date_from=' + encodeURIComponent(dateFrom));
    if (dateTo) params.push('date_to=' + encodeURIComponent(dateTo));
    
    if (params.length > 0) {
        url += '?' + params.join('&');
    }
    
    window.location.href = url;
}
</script>

<?php require_once 'footer.php'; ?>
