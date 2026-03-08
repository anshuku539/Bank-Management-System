<?php
$pageTitle = 'Manage Accounts';
require_once 'header.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Handle account status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');
    $accountId = intval($_POST['account_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? '');
    
    if ($action === 'update_status' && in_array($status, ['ACTIVE', 'INACTIVE', 'CLOSED'])) {
        $stmt = $conn->prepare("UPDATE accounts SET status = ? WHERE account_id = ?");
        $stmt->bind_param("si", $status, $accountId);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Account status updated successfully.';
            $_SESSION['message_type'] = 'success';
        }
        $stmt->close();
    }
    
    header("Location: accounts.php");
    exit();
}

// Get accounts with filters
$filterStatus = sanitizeInput($_GET['status'] ?? 'ACTIVE');
$searchQuery = sanitizeInput($_GET['search'] ?? '');

$sql = "SELECT a.*, c.full_name, c.email FROM accounts a 
        JOIN customers c ON a.customer_id = c.customer_id 
        WHERE a.status = ?";
$params = [$filterStatus];

if (!empty($searchQuery)) {
    $sql .= " AND (a.account_number LIKE ? OR c.full_name LIKE ? OR c.email LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
if (count($params) > 1) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("s", $params[0]);
}

$stmt->execute();
$accounts = $stmt->get_result();
$stmt->close();
?>

<div class="container-fluid">
    <?php displayMessage(); ?>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-6">
            <input type="text" class="form-control" id="searchInput" placeholder="Search by account number or customer name..." 
                   value="<?php echo htmlspecialchars($searchQuery); ?>" onchange="filterAccounts()">
        </div>
        <div class="col-md-3">
            <select class="form-select" id="statusFilter" onchange="filterAccounts()">
                <option value="ACTIVE" <?php echo $filterStatus === 'ACTIVE' ? 'selected' : ''; ?>>Active</option>
                <option value="INACTIVE" <?php echo $filterStatus === 'INACTIVE' ? 'selected' : ''; ?>>Inactive</option>
                <option value="CLOSED" <?php echo $filterStatus === 'CLOSED' ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100" onclick="filterAccounts()">
                <i class="bi bi-search"></i> Search
            </button>
        </div>
    </div>
    
    <!-- Accounts Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Bank Accounts</h5>
        </div>
        <div class="card-body">
            <?php if ($accounts->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Account Number</th>
                                <th>Customer Name</th>
                                <th>Account Type</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Opened</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($acc = $accounts->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($acc['account_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($acc['full_name']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($acc['account_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatCurrency($acc['balance']); ?></td>
                                    <td><?php echo getStatusBadge($acc['status']); ?></td>
                                    <td><?php echo formatDate($acc['opened_at']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal"
                                                onclick="viewAccountDetails(<?php echo $acc['account_id']; ?>)">
                                            Details
                                        </button>
                                        <?php if ($acc['status'] === 'ACTIVE'): ?>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#statusModal"
                                                    onclick="setAccountStatus(<?php echo $acc['account_id']; ?>, 'INACTIVE')">
                                                Deactivate
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#statusModal"
                                                    onclick="setAccountStatus(<?php echo $acc['account_id']; ?>, 'ACTIVE')">
                                                Activate
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No accounts found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Account Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Account Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <!-- Loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Status Change Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Account Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="account_id" id="statusAccountId">
                    <input type="hidden" name="status" id="newAccountStatus">
                    
                    <p>Are you sure you want to change this account status?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Change Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filterAccounts() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    let url = 'accounts.php?status=' + status;
    if (search) {
        url += '&search=' + encodeURIComponent(search);
    }
    window.location.href = url;
}

function viewAccountDetails(accountId) {
    const xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            document.getElementById('detailsModalBody').innerHTML = this.responseText;
        }
    };
    xhr.open('GET', 'get_account_details.php?account_id=' + accountId, true);
    xhr.send();
}

function setAccountStatus(accountId, status) {
    document.getElementById('statusAccountId').value = accountId;
    document.getElementById('newAccountStatus').value = status;
}
</script>

<?php require_once 'footer.php'; ?>
