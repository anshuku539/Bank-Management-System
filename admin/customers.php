<?php
$pageTitle = 'Manage Customers';
require_once 'header.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$action = sanitizeInput($_GET['action'] ?? '');
$customerId = intval($_GET['customer_id'] ?? 0);

// Handle customer status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');
    $customerId = intval($_POST['customer_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? '');
    
    if ($action === 'update_status' && in_array($status, ['ACTIVE', 'INACTIVE'])) {
        $stmt = $conn->prepare("UPDATE customers SET registration_status = ? WHERE customer_id = ?");
        $stmt->bind_param("si", $status, $customerId);
        
        if ($stmt->execute()) {
            // Also update user status
            $stmt2 = $conn->prepare("UPDATE users SET status = ? WHERE user_id = (SELECT user_id FROM customers WHERE customer_id = ?)");
            $stmt2->bind_param("si", $status, $customerId);
            $stmt2->execute();
            $stmt2->close();
            
            $_SESSION['message'] = 'Customer status updated successfully.';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error updating customer status.';
            $_SESSION['message_type'] = 'danger';
        }
        $stmt->close();
        
        header("Location: customers.php");
        exit();
    }
}

// Get customers
$searchQuery = sanitizeInput($_GET['search'] ?? '');
$filterStatus = sanitizeInput($_GET['status'] ?? 'APPROVED');

$sql = "SELECT c.*, u.status FROM customers c JOIN users u ON c.user_id = u.user_id WHERE c.registration_status = ?";
$params = [$filterStatus];

if (!empty($searchQuery)) {
    $sql .= " AND (c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.customer_code LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
if (count($params) > 1) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("s", $params[0]);
}

$stmt->execute();
$customers = $stmt->get_result();
$stmt->close();
?>

<div class="container-fluid">
    <?php displayMessage(); ?>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-6">
            <input type="text" class="form-control" id="searchInput" placeholder="Search by name, email, phone..." 
                   value="<?php echo htmlspecialchars($searchQuery); ?>" onchange="filterCustomers()">
        </div>
        <div class="col-md-3">
            <select class="form-select" id="statusFilter" onchange="filterCustomers()">
                <option value="APPROVED" <?php echo $filterStatus === 'APPROVED' ? 'selected' : ''; ?>>Active</option>
                <option value="INACTIVE" <?php echo $filterStatus === 'INACTIVE' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100" onclick="filterCustomers()">
                <i class="bi bi-search"></i> Search
            </button>
        </div>
    </div>
    
    <!-- Customers Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Customers</h5>
        </div>
        <div class="card-body">
            <?php if ($customers->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Customer Code</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Registered On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($cust = $customers->fetch_assoc()): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($cust['customer_code']); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($cust['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($cust['email']); ?></td>
                                    <td><?php echo htmlspecialchars($cust['phone']); ?></td>
                                    <td><?php echo getStatusBadge($cust['status']); ?></td>
                                    <td><?php echo formatDate($cust['created_at']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal"
                                                onclick="viewCustomerDetails(<?php echo $cust['customer_id']; ?>)">
                                            View
                                        </button>
                                        <?php if ($cust['status'] === 'ACTIVE'): ?>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#statusModal"
                                                    onclick="setStatusChange(<?php echo $cust['customer_id']; ?>, 'INACTIVE')">
                                                Deactivate
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#statusModal"
                                                    onclick="setStatusChange(<?php echo $cust['customer_id']; ?>, 'ACTIVE')">
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
                <p class="text-muted">No customers found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Customer Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Customer Details</h5>
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
                <h5 class="modal-title">Change Customer Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="customer_id" id="statusCustomerId">
                    <input type="hidden" name="status" id="newStatus">
                    
                    <p>Are you sure you want to change the customer status?</p>
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
function filterCustomers() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    let url = 'customers.php?status=' + status;
    if (search) {
        url += '&search=' + encodeURIComponent(search);
    }
    window.location.href = url;
}

function viewCustomerDetails(customerId) {
    const xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            document.getElementById('detailsModalBody').innerHTML = this.responseText;
        }
    };
    xhr.open('GET', 'get_customer_details.php?customer_id=' + customerId, true);
    xhr.send();
}

function setStatusChange(customerId, status) {
    document.getElementById('statusCustomerId').value = customerId;
    document.getElementById('newStatus').value = status;
}
</script>

<?php require_once 'footer.php'; ?>
