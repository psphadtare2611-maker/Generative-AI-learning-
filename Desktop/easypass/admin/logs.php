<?php
/**
 * Admin: Email Logs
 */
$root_dir = __DIR__ . '/..';
require_once $root_dir . '/auth_check.php';
require_once $root_dir . '/db_connect.php';
require_once $root_dir . '/includes/functions.php';

// Secure the page
requireAdmin();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$where = "WHERE 1=1";
$params = [];
$types = "";

if ($status_filter) {
    $where .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
if ($search) {
    $where .= " AND (to_email LIKE ? OR subject LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

// Count total
$count_sql = "SELECT COUNT(*) as total FROM email_reminders_log $where";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_rows = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch logs
$sql = "SELECT * FROM email_reminders_log $where ORDER BY sent_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Logs - EasyPass Admin</title>
    <link rel="stylesheet" href="../styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        .admin-container { padding: 20px; max-width: 1200px; margin: 0 auto; display: flex; gap: 20px;}
        .admin-sidebar { width: 250px; background: #fff; padding: 20px; border-radius: 8px; height: fit-content; }
        .admin-content { flex: 1; }
        .card { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .pagination { margin-top: 20px; display: flex; gap: 5px; }
        .page-link { padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; border-radius: 4px; }
        .page-link.active { background: #1a73e8; color: white; border-color: #1a73e8; }
        .filters { display: flex; gap: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <header>
        <div class="logo"><i class="fas fa-bus"></i> EasyPass Admin</div>
        <div class="user-menu">
            <div class="user-info">Admin Area</div>
            <button class="logout-btn" onclick="window.location.href='../logout.php'">Logout</button>
        </div>
    </header>

    <div class="admin-container">
        <aside class="admin-sidebar">
            <ul class="sidebar-menu">
                <li><a href="../dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></li>
                <li><a href="reminders.php"><i class="fas fa-bell"></i> Reminder Settings</a></li>
                <li><a href="logs.php" class="active"><i class="fas fa-list"></i> Email Logs</a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <h1 class="page-title">Email Logs</h1>
            
            <div class="card">
                <form method="GET" class="filters">
                    <select name="status" class="form-control" style="width: auto;">
                        <option value="">All Status</option>
                        <option value="sent" <?php if ($status_filter == 'sent') echo 'selected'; ?>>Sent</option>
                        <option value="failed" <?php if ($status_filter == 'failed') echo 'selected'; ?>>Failed</option>
                    </select>
                    <input type="text" name="search" class="form-control" placeholder="Search email..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>To</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($log['sent_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['to_email']); ?></td>
                                <td><?php echo htmlspecialchars($log['subject']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $log['status'] === 'sent' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($log['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['error_message'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No logs found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" class="page-link">&laquo; Prev</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php if ($i == $page) echo 'active'; ?>"><?php echo $i; ?></a>
                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                            <span class="page-link">...</span>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" class="page-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
