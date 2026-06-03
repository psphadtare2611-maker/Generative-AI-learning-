<?php
/**
 * Enhanced Dashboard with Dynamic Data and Real-time Statistics
 * This file provides a comprehensive dashboard with user statistics, active passes, and trip history
 */

require_once 'includes/functions.php';
require_once 'auth_check.php';
require_once 'db_connect.php';

// Check user authentication (session already started in auth_check.php)
if (empty($_SESSION['logged_in'])) {
    header("Location: /easypass/login.php");
    exit();
}
$user = getCurrentUser();

// Check if user is properly authenticated
if (!$user) {
    header("Location: /easypass/login.php");
    exit();
}

// Get dashboard statistics
$stats = getDashboardStats($user['id']);

// Get user's active passes (All passes, including expired/inactive)
$user_passes = getUserPasses($user['id']);
// Get user's single active pass for the main card
$active_pass = getActivePass($user['id']);

// Get recent trips
$recent_trips = getTripHistory($user['id'], 10);

// Get recent payments
$payments_sql = "SELECT p.*, pt.name as pass_type_name 
                 FROM payments p 
                 LEFT JOIN user_passes up ON p.pass_id = up.id
                 LEFT JOIN pass_types pt ON up.pass_type_id = pt.id
                 WHERE p.user_id = ? 
                 ORDER BY p.payment_date DESC 
                 LIMIT 10";
$payments_stmt = $conn->prepare($payments_sql);
$payments_stmt->bind_param("i", $user['id']);
$payments_stmt->execute();
$user_payments = $payments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unread notifications
$notifications_sql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5";
$notifications_stmt = $conn->prepare($notifications_sql);
$notifications_stmt->bind_param("i", $user['id']);
$notifications_stmt->execute();
$unread_notifications = $notifications_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Check if user is new (no passes)
$is_new_user = empty($user_passes);

// Log dashboard access
logUserActivity('dashboard_access', 'User accessed dashboard');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyPass - Your Bus Pass Dashboard</title>
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>

<body>
    <header>
        <div class="logo">
            <i class="fas fa-bus"></i> EasyPass
        </div>
        <div class="user-menu">
            <div class="user-info">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?></div>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="/easypass/dashboard.php" data-page="dashboard" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="#" data-page="passes"><i class="fas fa-ticket-alt"></i> My Passes</a></li>
                <li><a href="#" data-page="history"><i class="fas fa-history"></i> Trip History</a></li>
                <li><a href="#" data-page="routes"><i class="fas fa-route"></i> Routes</a></li>
                <?php if (!$is_new_user): ?>
                <li><a href="#" data-page="payments"><i class="fas fa-credit-card"></i> Payments</a></li>
                <?php endif; ?>
                <li><a href="#" data-page="settings"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="#" data-page="support"><i class="fas fa-question-circle"></i> Help & Support</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <!-- Dashboard Page -->
            <div id="dashboard" class="page-content active">
                <h1 class="page-title">Dashboard</h1>

                <div class="dashboard-cards">
                    <?php if ($is_new_user): ?>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Get Started</h2>
                            <div class="card-icon primary">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                        </div>
                        <div class="card-value">Choose Your Pass</div>
                        <p class="card-description">Select a bus pass that suits your needs</p>
                        <button class="btn btn-primary" onclick="window.location.href='passes.php'">Choose Pass</button>
                    </div>
                    <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Active Pass</h2>
                            <div class="card-icon primary">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                        </div>
                        <div class="card-value"><?php echo htmlspecialchars($active_pass['pass_type_name'] ?? 'No Active Pass'); ?></div>
                        <p class="card-description">
                            <?php if ($active_pass): ?>
                                Valid until: <?php echo formatDate($active_pass['valid_until']); ?>
                            <?php else: ?>
                                No active pass
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Total Trips</h2>
                            <div class="card-icon accent">
                                <i class="fas fa-bus"></i>
                            </div>
                        </div>
                        <div class="card-value"><?php echo $stats['monthly_trips']; ?></div>
                        <p class="card-description">This month</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Total Spent</h2>
                            <div class="card-icon success">
                                <i class="fas fa-wallet"></i>
                            </div>
                        </div>
                        <div class="card-value"><?php echo formatCurrency($stats['monthly_spent']); ?></div>
                        <p class="card-description">This month</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Notifications</h2>
                            <div class="card-icon <?php echo $stats['unread_notifications'] > 0 ? 'danger' : 'accent'; ?>">
                                <i class="fas fa-bell"></i>
                            </div>
                        </div>
                        <div class="card-value"><?php echo $stats['unread_notifications']; ?></div>
                        <p class="card-description">Unread notifications</p>
                    </div>
                </div>

                <?php if ($active_pass): ?>
                <div class="bus-pass-card">
                    <div class="pass-header">
                        <div class="pass-type"><?php echo strtoupper($active_pass['pass_type_name']); ?></div>
                        <div class="pass-logo">
                            <i class="fas fa-bus"></i> EasyPass
                        </div>
                    </div>
                    <div class="pass-details">
                        <div class="pass-holder"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        <div class="pass-id">PASS ID: <?php echo htmlspecialchars($active_pass['pass_number']); ?></div>

                        <div class="pass-info">
                            <div class="pass-info-item">
                                <div class="pass-info-label">ISSUE DATE</div>
                                <div class="pass-info-value"><?php echo formatDate($active_pass['valid_from']); ?></div>
                            </div>
                            <div class="pass-info-item">
                                <div class="pass-info-label">VALID UNTIL</div>
                                <div class="pass-info-value"><?php echo formatDate($active_pass['valid_until']); ?></div>
                            </div>
                            <div class="pass-info-item">
                                <div class="pass-info-label">ROUTES</div>
                                <div class="pass-info-value">All City Routes</div>
                            </div>
                        </div>
                    </div>
                    <div class="pass-qr">
                        <!-- Dynamic QR Code: Uses generateQRCode function from functions.php -->
                        <div style="width:80px; height:80px; background:#eee; display:flex; align-items:center; justify-content:center; margin:0 auto;">
                            <span style="font-size:10px; color:#666;">QR Disabled</span>
                        </div>
                    </div>
                    <div class="pass-watermark">
                        <i class="fas fa-bus"></i>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($recent_trips)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Recent Trips</h2>
                        <a href="trips.php" class="btn btn-secondary">View All</a>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Route</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_trips as $trip): ?>
                            <tr>
                                <td><?php echo formatDate($trip['trip_date'], 'd M, Y'); ?></td>
                                <td><?php echo htmlspecialchars($trip['route_number']); ?></td>
                                <td><?php echo htmlspecialchars($trip['from_stop']); ?></td>
                                <td><?php echo htmlspecialchars($trip['to_stop']); ?></td>
                                <td><?php echo formatCurrency($trip['fare']); ?></td>
                                <td><span class="badge badge-<?php echo $trip['status'] === 'completed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($trip['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- My Passes Page -->
            <div id="passes" class="page-content">
                <h1 class="page-title">My Bus Passes</h1>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">All Passes</h2>
                        <button class="btn btn-primary" onclick="window.location.href='index.php#pricing'">Buy New Pass</button>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pass Type</th>
                                <th>Valid From</th>
                                <th>Valid Until</th>
                                <th>Routes</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($user_passes)): ?>
                                <?php foreach ($user_passes as $pass): ?>
                                <?php 
                                    // Determine badge class based on pass status and expiry date
                                    $status_badge_class = 'secondary';
                                    $status_text = ucfirst($pass['status']);

                                    if ($pass['status'] === 'active') {
                                        if (strtotime($pass['valid_until']) >= time()) {
                                            $status_badge_class = 'success';
                                            $status_text = 'Active';
                                        } else {
                                            $status_badge_class = 'danger';
                                            $status_text = 'Expired';
                                        }
                                    } elseif ($pass['status'] === 'expired') {
                                         $status_badge_class = 'danger';
                                    } elseif ($pass['status'] === 'pending') {
                                         $status_badge_class = 'warning';
                                    }

                                    // Safely decode routes
                                    $routes_array = json_decode($pass['routes'], true);
                                    $routes_display = is_array($routes_array) ? ucfirst(implode(', ', $routes_array)) : 'N/A';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pass['pass_type_name']); ?></td>
                                    <td><?php echo formatDate($pass['valid_from']); ?></td>
                                    <td><?php echo formatDate($pass['valid_until']); ?></td>
                                    <td><?php echo htmlspecialchars($routes_display); ?></td>
                                    <td><span class="badge badge-<?php echo $status_badge_class; ?>"><?php echo $status_text; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center">You have no passes yet. <a href="index.php#pricing">Buy one now!</a></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Trip History Page -->
            <div id="history" class="page-content">
                <h1 class="page-title">Trip History</h1>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Recent Trips</h2>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Route</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_trips)): ?>
                                <?php foreach ($recent_trips as $trip): ?>
                                <tr>
                                    <td><?php echo formatDate($trip['trip_date'], 'd M, Y') . ' - ' . date('h:i A', strtotime($trip['trip_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($trip['route_number']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['from_stop']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['to_stop']); ?></td>
                                    <td><?php echo formatCurrency($trip['fare']); ?></td>
                                    <td><span class="badge badge-<?php echo $trip['status'] === 'completed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($trip['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center">No trip records found. Start riding the bus!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Routes Page (Static data retained) -->
            <div id="routes" class="page-content">
                <h1 class="page-title">Bus Routes</h1>

                <div class="dashboard-cards">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Sangli</h2>
                            <div class="card-icon primary">
                                <i class="fas fa-route"></i>
                            </div>
                        </div>
                        <div class="card-value">₹15</div>
                        <p class="card-description">Central Station ↔ Tech Park</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Ashta</h2>
                            <div class="card-icon accent">
                                <i class="fas fa-route"></i>
                            </div>
                        </div>
                        <div class="card-value">₹41</div>
                        <p class="card-description">Tech Park ↔ Mall Plaza</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Islampur</h2>
                            <div class="card-icon success">
                                <i class="fas fa-route"></i>
                            </div>
                        </div>
                        <div class="card-value">₹80</div>
                        <p class="card-description">Central Station ↔ University</p>
                    </div>
                </div>
            </div>

            <!-- Payments Page (Static data retained) -->
            <div id="payments" class="page-content">
                <h1 class="page-title">Payment History</h1>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Recent Payments</h2>
                        <button class="btn btn-success">Add Money</button>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($user_payments)): ?>
                                <?php foreach ($user_payments as $payment): ?>
                                <tr>
                                    <td><?php echo formatDate($payment['payment_date']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['description'] ?? 'Pass Purchase'); ?></td>
                                    <td><?php echo formatCurrency($payment['amount']); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $payment['payment_status'] === 'completed' ? 'success' : 
                                                ($payment['payment_status'] === 'failed' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($payment['payment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No payment records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Settings Page (Static data retained) -->
            <div id="settings" class="page-content">
                <h1 class="page-title">Account Settings</h1>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Profile Information</h2>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control"
                            value="<?php echo htmlspecialchars($user['full_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control"
                            value="<?php echo htmlspecialchars($user['username']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <!-- Assuming email is available in $user object -->
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <!-- Assuming phone is available in $user object -->
                        <input type="tel" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?>">
                    </div>
                    <button class="btn btn-primary">Update Profile</button>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Change Password</h2>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control">
                    </div>
                    <button class="btn btn-primary">Change Password</button>
                </div>
            </div>

            <!-- Support Page (Static data retained) -->
            <div id="support" class="page-content">
                <h1 class="page-title">Help & Support</h1>

                <div class="dashboard-cards">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Contact Support</h2>
                            <div class="card-icon primary">
                                <i class="fas fa-headset"></i>
                            </div>
                        </div>
                        <p class="card-description">Get help from our support team</p>
                        <button class="btn btn-primary">Contact Us</button>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">FAQ</h2>
                            <div class="card-icon accent">
                                <i class="fas fa-question-circle"></i>
                            </div>
                        </div>
                        <p class="card-description">Find answers to common questions</p>
                        <button class="btn btn-primary">View FAQ</button>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Report Issue</h2>
                            <div class="card-icon danger">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                        <p class="card-description">Report a problem with the service</p>
                        <button class="btn btn-primary">Report</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Navigation functionality
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
            const pageContents = document.querySelectorAll('.page-content');

            sidebarLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    if (this.getAttribute('data-page') !== 'dashboard') {
                        e.preventDefault();
                    }

                    // Remove active class from all links and pages
                    sidebarLinks.forEach(l => l.classList.remove('active'));
                    pageContents.forEach(p => p.classList.remove('active'));

                    // Add active class to clicked link
                    this.classList.add('active');

                    // Show corresponding page
                    const pageId = this.getAttribute('data-page');
                    document.getElementById(pageId).classList.add('active');
                });
            });
        });
    </script>
</body>

</html>
