<?php
require_once '../includes/init.php';
requireAdmin();

$customerId = intval($_GET['customer_id'] ?? 0);

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare(
    "SELECT c.*, u.status FROM customers c 
     JOIN users u ON c.user_id = u.user_id 
     WHERE c.customer_id = ?"
);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer) {
    echo "Customer not found.";
    exit();
}

// Get accounts
$accounts = $conn->query(
    "SELECT * FROM accounts WHERE customer_id = $customerId ORDER BY opened_at DESC"
);
?>

<table class="table table-borderless">
    <tr>
        <td colspan="2"><strong>Personal Information</strong></td>
    </tr>
    <tr>
        <td><strong>Customer Code:</strong></td>
        <td><?php echo htmlspecialchars($customer['customer_code']); ?></td>
    </tr>
    <tr>
        <td><strong>Full Name:</strong></td>
        <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
    </tr>
    <tr>
        <td><strong>Date of Birth:</strong></td>
        <td><?php echo formatDate($customer['dob']); ?></td>
    </tr>
    <tr>
        <td><strong>Email:</strong></td>
        <td><?php echo htmlspecialchars($customer['email']); ?></td>
    </tr>
    <tr>
        <td><strong>Phone:</strong></td>
        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
    </tr>
    <tr>
        <td><strong>Address:</strong></td>
        <td><?php echo htmlspecialchars($customer['address'] ?? 'N/A'); ?></td>
    </tr>
    <tr>
        <td><strong>City, State, ZIP:</strong></td>
        <td><?php echo htmlspecialchars(($customer['city'] ?? '') . ', ' . ($customer['state'] ?? '') . ' ' . ($customer['zip_code'] ?? '')); ?></td>
    </tr>
    <tr>
        <td><strong>Aadhar Number:</strong></td>
        <td><?php echo htmlspecialchars($customer['aadhar_number'] ?? 'N/A'); ?></td>
    </tr>
    <tr>
        <td><strong>PAN Number:</strong></td>
        <td><?php echo htmlspecialchars($customer['pan_number'] ?? 'N/A'); ?></td>
    </tr>
    <tr>
        <td colspan="2"><strong>Account Status</strong></td>
    </tr>
    <tr>
        <td><strong>Status:</strong></td>
        <td><?php echo getStatusBadge($customer['status']); ?></td>
    </tr>
    <tr>
        <td><strong>Registered On:</strong></td>
        <td><?php echo formatDateTime($customer['created_at']); ?></td>
    </tr>
    <tr>
        <td><strong>Approved On:</strong></td>
        <td><?php echo $customer['approved_at'] ? formatDateTime($customer['approved_at']) : 'N/A'; ?></td>
    </tr>
    <tr>
        <td colspan="2"><strong>Accounts (<?php echo $accounts->num_rows; ?>)</strong></td>
    </tr>
</table>

<?php if ($accounts->num_rows > 0): ?>
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Account Number</th>
                <th>Type</th>
                <th>Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($acc = $accounts->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($acc['account_number']); ?></td>
                    <td><?php echo htmlspecialchars($acc['account_type']); ?></td>
                    <td><?php echo formatCurrency($acc['balance']); ?></td>
                    <td><?php echo getStatusBadge($acc['status']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p class="text-muted">No accounts found.</p>
<?php endif; ?>
