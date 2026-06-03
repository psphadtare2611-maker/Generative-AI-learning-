<?php
/**
 * Pass Management System
 * This file handles pass purchasing, viewing, and management
 */

require_once 'auth_check.php';
require_once 'includes/functions.php';

// Check user authentication
checkUserLogin();
$user = getCurrentUser();

// Handle pass purchase
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'purchase_pass') {
        $pass_type_id = (int)$_POST['pass_type_id'];
        $payment_method = sanitizeInput($_POST['payment_method']);
        
        // Get pass type details
        $pass_type_sql = "SELECT * FROM pass_types WHERE id = ? AND is_active = 1";
        $stmt = $conn->prepare($pass_type_sql);
        $stmt->bind_param("i", $pass_type_id);
        $stmt->execute();
        $pass_type = $stmt->get_result()->fetch_assoc();
        
        if ($pass_type) {
            // Create payment record
            $payment_result = createPayment(
                $user['id'],
                $pass_type['price'],
                $payment_method,
                null,
                "Purchase of {$pass_type['name']}"
            );
            
            if ($payment_result['success']) {
                // Update payment status to completed (simulating successful payment)
                updatePaymentStatus($payment_result['payment_id'], 'completed');
                
                // Create user pass
                $pass_result = createUserPass($user['id'], $pass_type_id);
                
                if ($pass_result['success']) {
                    // Update payment with pass ID
                    $update_payment_sql = "UPDATE payments SET pass_id = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_payment_sql);
                    $update_stmt->bind_param("ii", $pass_result['pass_id'], $payment_result['payment_id']);
                    $update_stmt->execute();
                    
                    // Send confirmation email
                    $subject = "Pass Purchase Confirmation";
                    $message = "Dear {$user['full_name']},<br><br>
                              Your {$pass_type['name']} has been purchased successfully!<br>
                              Pass Number: {$pass_result['pass_number']}<br>
                              Valid Until: {$pass_result['valid_until']}<br><br>
                              You can view your pass in the dashboard.<br><br>
                              Best regards,<br>EasyPass Team";
                    
                    sendEmail($user['email'], $subject, $message);
                    
                    // Create notification
                    createNotification(
                        $user['id'],
                        'payment_success',
                        'Pass Purchase Successful',
                        "Your {$pass_type['name']} is now active",
                        'email'
                    );
                    
                    // Log activity
                    logUserActivity('pass_purchase', "Purchased {$pass_type['name']} for ₹{$pass_type['price']}");
                    
                    $success_message = "Pass purchased successfully!";
                } else {
                    $error_message = "Failed to create pass: " . $pass_result['error'];
                }
            } else {
                $error_message = "Payment failed: " . $payment_result['error'];
            }
        } else {
            $error_message = "Invalid pass type selected.";
        }
    }
}

// Get available pass types
$pass_types = getPassTypes();

// Get user's passes
$user_passes = getUserPasses($user['id']);

// Get user's payments
$payments_sql = "SELECT p.*, pt.name as pass_type_name 
                 FROM payments p 
                 LEFT JOIN pass_types pt ON p.pass_id = (SELECT id FROM user_passes WHERE user_id = ? LIMIT 1)
                 WHERE p.user_id = ? 
                 ORDER BY p.payment_date DESC";
$payments_stmt = $conn->prepare($payments_sql);
$payments_stmt->bind_param("ii", $user['id'], $user['id']);
$payments_stmt->execute();
$payments = $payments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pass Management - EasyPass</title>
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>
<body>
    <header>
        <div class="logo">
            <i class="fas fa-bus"></i> EasyPass
        </div>
        <div class="user-menu">
            <div class="user-info">Welcome, <?php echo htmlspecialchars($user['username']); ?></div>
            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </header>

    <div class="dashboard-container">
        <main class="main-content">
            <h1 class="page-title">Pass Management</h1>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Purchase New Pass Section -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Purchase New Pass</h2>
                </div>
                <div class="pass-types-grid">
                    <?php foreach ($pass_types as $pass_type): ?>
                    <div class="pass-type-card">
                        <div class="pass-type-header">
                            <h3><?php echo htmlspecialchars($pass_type['name']); ?></h3>
                            <div class="pass-price"><?php echo formatCurrency($pass_type['price']); ?></div>
                        </div>
                        <div class="pass-type-description">
                            <p><?php echo htmlspecialchars($pass_type['description']); ?></p>
                            <ul>
                                <li>Duration: <?php echo $pass_type['duration_days']; ?> days</li>
                                <li>Unlimited rides</li>
                                <li>All routes coverage</li>
                                <li>Digital pass</li>
                            </ul>
                        </div>
                        <form method="POST" class="purchase-form">
                            <input type="hidden" name="action" value="purchase_pass">
                            <input type="hidden" name="pass_type_id" value="<?php echo $pass_type['id']; ?>">
                            <div class="form-group">
                                <label>Payment Method</label>
                                <select name="payment_method" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="debit_card">Debit Card</option>
                                    <option value="upi">UPI</option>
                                    <option value="net_banking">Net Banking</option>
                                    <option value="wallet">Mobile Wallet</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i> Purchase Now
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- My Passes Section -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">My Passes</h2>
                </div>
                <?php if (empty($user_passes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-ticket-alt"></i>
                        <h3>No Passes Found</h3>
                        <p>You haven't purchased any passes yet. Choose a pass above to get started!</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pass Type</th>
                                <th>Pass Number</th>
                                <th>Valid From</th>
                                <th>Valid Until</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_passes as $pass): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pass['pass_type_name']); ?></td>
                                <td><?php echo htmlspecialchars($pass['pass_number']); ?></td>
                                <td><?php echo formatDate($pass['valid_from']); ?></td>
                                <td><?php echo formatDate($pass['valid_until']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $pass['status'] === 'active' ? 'success' : 
                                            ($pass['status'] === 'expired' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($pass['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($pass['status'] === 'active'): ?>
                                        <a href="view_pass.php?id=<?php echo $pass['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Payment History Section -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Payment History</h2>
                </div>
                <?php if (empty($payments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-credit-card"></i>
                        <h3>No Payments Found</h3>
                        <p>Your payment history will appear here once you make a purchase.</p>
                    </div>
                <?php else: ?>
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
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                <td><?php echo htmlspecialchars($payment['description']); ?></td>
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
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Add confirmation for pass purchase
        document.querySelectorAll('.purchase-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to purchase this pass?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
