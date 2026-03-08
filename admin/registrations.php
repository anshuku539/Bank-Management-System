<?php
$pageTitle = 'Registration Requests';
require_once 'header.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$action = sanitizeInput($_GET['action'] ?? '');
$requestId = intval($_GET['request_id'] ?? 0);

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');
    $requestId = intval($_POST['request_id'] ?? 0);
    $rejectionReason = sanitizeInput($_POST['rejection_reason'] ?? '');
    $initialBalance = floatval($_POST['initial_balance'] ?? 0);
    $password = $_POST['password'] ?? '';
    
    $adminId = getCurrentUserId();
    
    if ($action === 'approve' && !empty($password)) {
        // Get registration request
        $stmt = $conn->prepare("SELECT * FROM registration_requests WHERE request_id = ? AND request_status = 'PENDING'");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$request) {
            $_SESSION['message'] = 'Registration request not found.';
            $_SESSION['message_type'] = 'danger';
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Create user
                $passwordHash = hashPassword($password);
                $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role, status) VALUES (?, ?, ?, ?)");
                $role = 'CUSTOMER';
                $status = 'ACTIVE';
                $stmt->bind_param("ssss", $request['username'], $passwordHash, $role, $status);
                $stmt->execute();
                $userId = $conn->insert_id;
                $stmt->close();
                
                // Create customer
                $customerCode = generateCustomerId();
                $pending = 'APPROVED';
                $stmt = $conn->prepare(
                    "INSERT INTO customers (user_id, customer_code, full_name, dob, email, phone, address, city, state, zip_code, aadhar_number, pan_number, registration_status, approved_by, approved_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
                );
                $stmt->bind_param(
                    "issssssssssssi",
                    $userId,
                    $customerCode,
                    $request['full_name'],
                    $request['dob'],
                    $request['email'],
                    $request['phone'],
                    $request['address'],
                    $request['city'],
                    $request['state'],
                    $request['zip_code'],
                    $request['aadhar_number'],
                    $request['pan_number'],
                    $pending,
                    $adminId
                );
                $stmt->execute();
                $customerId = $conn->insert_id;
                $stmt->close();
                
                // Create account
                $accountNumber = generateAccountNumber();
                $accountType = 'SAVINGS';
                $acStatus = 'ACTIVE';
                $stmt = $conn->prepare(
                    "INSERT INTO accounts (account_number, customer_id, account_type, balance, status, opening_balance) 
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("sisdsd", $accountNumber, $customerId, $accountType, $initialBalance, $acStatus, $initialBalance);
                $stmt->execute();
                $stmt->close();
                
                // Update registration request
                $stmt = $conn->prepare("UPDATE registration_requests SET request_status = 'APPROVED', reviewed_by = ?, reviewed_at = NOW() WHERE request_id = ?");
                $stmt->bind_param("ii", $adminId, $requestId);
                $stmt->execute();
                $stmt->close();
                
                $conn->commit();
                
                $_SESSION['message'] = 'Registration approved successfully! Account created.';
                $_SESSION['message_type'] = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['message'] = 'Error approving registration: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
            }
        }
        
        header("Location: registrations.php");
        exit();
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE registration_requests SET request_status = 'REJECTED', rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE request_id = ?");
        $stmt->bind_param("sii", $rejectionReason, $adminId, $requestId);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Registration request rejected.';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error rejecting registration.';
            $_SESSION['message_type'] = 'danger';
        }
        $stmt->close();
        
        header("Location: registrations.php");
        exit();
    }
}

// Get pending requests
$status = sanitizeInput($_GET['status'] ?? 'PENDING');
$stmt = $conn->prepare("SELECT * FROM registration_requests WHERE request_status = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $status);
$stmt->execute();
$requests = $stmt->get_result();
$stmt->close();

// Get single request for approval modal
$selectedRequest = null;
if ($action === 'approve_modal' && $requestId > 0) {
    $stmt = $conn->prepare("SELECT * FROM registration_requests WHERE request_id = ? AND request_status = 'PENDING'");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $selectedRequest = $result->fetch_assoc();
    }
    $stmt->close();
}
?>

<div class="container-fluid">
    <?php displayMessage(); ?>
    
    <!-- Filter Tabs -->
    <div class="mb-3">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link <?php echo $status === 'PENDING' ? 'active' : ''; ?>" 
                   href="registrations.php?status=PENDING">
                    <span class="badge bg-warning">Pending</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $status === 'APPROVED' ? 'active' : ''; ?>" 
                   href="registrations.php?status=APPROVED">
                    <span class="badge bg-success">Approved</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $status === 'REJECTED' ? 'active' : ''; ?>" 
                   href="registrations.php?status=REJECTED">
                    <span class="badge bg-danger">Rejected</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Registrations Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Registration Requests - <?php echo $status; ?></h5>
        </div>
        <div class="card-body">
            <?php if ($requests->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Username</th>
                                <th>Applied On</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($req = $requests->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($req['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($req['email']); ?></td>
                                    <td><?php echo htmlspecialchars($req['phone']); ?></td>
                                    <td><code><?php echo htmlspecialchars($req['username']); ?></code></td>
                                    <td><?php echo formatDate($req['created_at']); ?></td>
                                    <td><?php echo getStatusBadge($req['request_status']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal"
                                                onclick="viewRequest('<?php echo htmlspecialchars(json_encode($req), ENT_QUOTES); ?>')">
                                            View
                                        </button>
                                        <?php if ($status === 'PENDING'): ?>
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal"
                                                    onclick="setRequestId(<?php echo $req['request_id']; ?>)">
                                                Approve
                                            </button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal"
                                                    onclick="setRequestId(<?php echo $req['request_id']; ?>)">
                                                Reject
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No registration requests found with this status.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registration Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Content loaded via JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Registration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="request_id" id="requestId">
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Set Account Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="form-text text-muted">Min 8 chars, letters & numbers required</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="initial_balance" class="form-label">Initial Balance</label>
                        <input type="number" class="form-control" id="initial_balance" name="initial_balance" 
                               value="0" min="0" step="0.01">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve & Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Registration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="request_id" id="rejectRequestId">
                    
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason *</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Registration</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setRequestId(id) {
    document.getElementById('requestId').value = id;
    document.getElementById('rejectRequestId').value = id;
}

function viewRequest(requestData) {
    const req = JSON.parse(decodeHtmlEntities(requestData));
    let html = `
        <div class="row mb-3">
            <div class="col-md-6">
                <p><strong>Full Name:</strong> ${req.full_name}</p>
                <p><strong>Date of Birth:</strong> ${req.dob}</p>
                <p><strong>Email:</strong> ${req.email}</p>
                <p><strong>Phone:</strong> ${req.phone}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Username:</strong> <code>${req.username}</code></p>
                <p><strong>Status:</strong> ${req.request_status}</p>
                <p><strong>Applied On:</strong> ${req.created_at}</p>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <p><strong>Address:</strong> ${req.address || 'N/A'}</p>
                <p><strong>City:</strong> ${req.city || 'N/A'}</p>
                <p><strong>State:</strong> ${req.state || 'N/A'}</p>
                <p><strong>ZIP Code:</strong> ${req.zip_code || 'N/A'}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Aadhar:</strong> ${req.aadhar_number || 'N/A'}</p>
                <p><strong>PAN:</strong> ${req.pan_number || 'N/A'}</p>
            </div>
        </div>
    `;
    document.getElementById('viewModalBody').innerHTML = html;
}

function decodeHtmlEntities(text) {
    const map = {
        '&amp;': '&',
        '&lt;': '<',
        '&gt;': '>',
        '&quot;': '"',
        '&#039;': "'"
    };
    return text.replace(/&[^;]+;/g, m => map[m] || m);
}
</script>

<?php require_once 'footer.php'; ?>
