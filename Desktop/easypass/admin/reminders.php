<?php
/**
 * Admin: Reminder Settings
 */
$root_dir = __DIR__ . '/..';
require_once $root_dir . '/auth_check.php';
require_once $root_dir . '/db_connect.php';
require_once $root_dir . '/includes/functions.php';

// Secure the page
requireAdmin();

$message = '';
$error = '';

// Handle Settings Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $days = (int)$_POST['reminder_days'];
    if ($days > 0) {
        $sql = "UPDATE system_settings SET setting_value = ? WHERE setting_key = 'notification_days_before_expiry'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $days);
        if ($stmt->execute()) {
            $message = "Settings updated successfully.";
        } else {
            $error = "Failed to update settings.";
        }
    } else {
        $error = "Days must be a positive integer.";
    }
}

// Handle Test Email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    $test_email = sanitizeInput($_POST['test_email']);
    if (validateEmail($test_email)) {
        if (sendEmail($test_email, "Test Email from EasyPass Admin", "This is a test email to verify SMTP settings.")) {
            $message = "Test email sent to $test_email";
        } else {
            $error = "Failed to send test email. Check logs.";
        }
    } else {
        $error = "Invalid email address.";
    }
}

// Get current setting
$days_before = 7;
$res = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'notification_days_before_expiry'");
if ($res && $res->num_rows > 0) {
    $days_before = $res->fetch_assoc()['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reminder Settings - EasyPass Admin</title>
    <link rel="stylesheet" href="../styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        .admin-container { padding: 20px; max-width: 1200px; margin: 0 auto; display: flex; gap: 20px;}
        .admin-sidebar { width: 250px; background: #fff; padding: 20px; border-radius: 8px; height: fit-content; }
        .admin-content { flex: 1; }
        .form-card { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
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
                <li><a href="reminders.php" class="active"><i class="fas fa-bell"></i> Reminder Settings</a></li>
                <li><a href="logs.php"><i class="fas fa-list"></i> Email Logs</a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <h1 class="page-title">Reminder Settings</h1>
            
            <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

            <div class="form-card">
                <h2><i class="fas fa-cog"></i> Configuration</h2>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Days Before Expiry</label>
                        <input type="number" name="reminder_days" class="form-control" value="<?php echo htmlspecialchars($days_before); ?>" min="1" max="30" required>
                        <small>Send notification this many days before the pass expires.</small>
                    </div>
                    <button type="submit" name="save_settings" class="btn btn-primary">Save Settings</button>
                </form>
            </div>

            <div class="form-card">
                <h2><i class="fas fa-envelope"></i> Test SMTP Connection</h2>
                <p>Send a test email to verify your mail configuration.</p>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Recipient Email</label>
                        <input type="email" name="test_email" class="form-control" placeholder="admin@example.com" required>
                    </div>
                    <button type="submit" name="send_test" class="btn btn-secondary">Send Test Email</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
