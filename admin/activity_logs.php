<?php
$pageTitle = 'Activity Logs';
require_once 'header.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Get filters
$action = sanitizeInput($_GET['action'] ?? '');
$dateFrom = sanitizeInput($_GET['date_from'] ?? date('Y-m-01'));
$dateTo = sanitizeInput($_GET['date_to'] ?? date('Y-m-d'));

$sql = "SELECT l.*, u.username FROM activity_logs l 
        LEFT JOIN users u ON l.user_id = u.user_id 
        WHERE DATE(l.created_at) BETWEEN ? AND ?";

$params = [$dateFrom, $dateTo];

if (!empty($action)) {
    $sql .= " AND l.action = ?";
    $params[] = $action;
}

$sql .= " ORDER BY l.created_at DESC LIMIT 500";

$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$logs = $stmt->get_result();
$stmt->close();

// Get available actions
$actionsResult = $conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
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
                    <label for="actionFilter" class="form-label">Action</label>
                    <select class="form-select" id="actionFilter" name="action">
                        <option value="">All Actions</option>
                        <?php while ($row = $actionsResult->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['action']); ?>" 
                                    <?php echo $action === $row['action'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['action']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Activity Logs Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Activity Logs (<?php echo $logs->num_rows; ?> records)</h5>
        </div>
        <div class="card-body">
            <?php if ($logs->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($log = $logs->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo formatDateTime($log['created_at']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($log['username'] ?? 'System'); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($log['action']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['description'] ?? '-'); ?></td>
                                    <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No activity logs found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
