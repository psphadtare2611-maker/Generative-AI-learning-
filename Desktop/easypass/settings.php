<?php
/**
 * User Settings and Profile Management
 * This file handles user profile updates, password changes, and notification preferences
 */

require_once 'auth_check.php';
require_once 'includes/functions.php';

// Check user authentication
checkUserLogin();
$user = getCurrentUser();

$error_message = '';
$success_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $full_name = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        $emergency_contact = sanitizeInput($_POST['emergency_contact']);
        
        // Validate input
        $errors = [];
        
        if (empty($full_name)) {
            $errors[] = "Full name is required.";
        }
        
        if (empty($email) || !validateEmail($email)) {
            $errors[] = "Please enter a valid email address.";
        }
        
        if (empty($phone) || !validatePhone($phone)) {
            $errors[] = "Please enter a valid 10-digit phone number.";
        }
        
        // Check if email is already used by another user
        if (empty($errors)) {
            $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("si", $email, $user['id']);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors[] = "Email address is already in use by another account.";
            }
        }
        
        if (empty($errors)) {
            // Update profile
            $update_sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, emergency_contact = ?, updated_at = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssssi", $full_name, $email, $phone, $address, $emergency_contact, $user['id']);
            
            if ($update_stmt->execute()) {
                $success_message = "Profile updated successfully!";
                
                // Update session data
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                
                // Log activity
                logUserActivity('profile_update', 'User updated profile information');
                
                // Refresh user data
                $user = getCurrentUser();
            } else {
                $error_message = "Failed to update profile. Please try again.";
            }
        } else {
            $error_message = implode("<br>", $errors);
        }
    }
    
    // Handle password change
    elseif ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate input
        $errors = [];
        
        if (empty($current_password)) {
            $errors[] = "Current password is required.";
        }
        
        if (empty($new_password) || strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long.";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match.";
        }
        
        if (empty($errors)) {
            // Verify current password
            $user_sql = "SELECT password FROM users WHERE id = ?";
            $user_stmt = $conn->prepare($user_sql);
            $user_stmt->bind_param("i", $user['id']);
            $user_stmt->execute();
            $user_data = $user_stmt->get_result()->fetch_assoc();
            
            if (password_verify($current_password, $user_data['password'])) {
                // Update password
                $hashed_password = hashPassword($new_password);
                $update_sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("si", $hashed_password, $user['id']);
                
                if ($update_stmt->execute()) {
                    $success_message = "Password changed successfully!";
                    
                    // Log activity
                    logUserActivity('password_change', 'User changed password');
                } else {
                    $error_message = "Failed to change password. Please try again.";
                }
            } else {
                $error_message = "Current password is incorrect.";
            }
        } else {
            $error_message = implode("<br>", $errors);
        }
    }
    
    // Handle notification preferences update
    elseif ($_POST['action'] === 'update_notifications') {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $whatsapp_notifications = isset($_POST['whatsapp_notifications']) ? 1 : 0;
        $pass_expiry_reminders = isset($_POST['pass_expiry_reminders']) ? 1 : 0;
        $payment_confirmations = isset($_POST['payment_confirmations']) ? 1 : 0;
        
        $notification_prefs = json_encode([
            'email_notifications' => $email_notifications,
            'sms_notifications' => $sms_notifications,
            'whatsapp_notifications' => $whatsapp_notifications,
            'pass_expiry_reminders' => $pass_expiry_reminders,
            'payment_confirmations' => $payment_confirmations
        ]);
        
        $update_sql = "UPDATE users SET notification_preferences = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $notification_prefs, $user['id']);
        
        if ($update_stmt->execute()) {
            $success_message = "Notification preferences updated successfully!";
            
            // Log activity
            logUserActivity('notification_preferences_update', 'User updated notification preferences');
        } else {
            $error_message = "Failed to update notification preferences. Please try again.";
        }
    }
}

// Get user's notification preferences
$notification_prefs = json_decode($user['notification_preferences'] ?? '{}', true);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - EasyPass</title>
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
            <h1 class="page-title">Account Settings</h1>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Profile Information -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Profile Information</h2>
                </div>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="full_name">Full Name *</label>
                            <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="form-group half">
                            <label for="username">Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small>Username cannot be changed</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="email">Email Address *</label>
                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-group half">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea name="address" id="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="emergency_contact">Emergency Contact</label>
                        <input type="tel" name="emergency_contact" id="emergency_contact" value="<?php echo htmlspecialchars($user['emergency_contact'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Change Password</h2>
                </div>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password *</label>
                        <input type="password" name="current_password" id="current_password" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="new_password">New Password *</label>
                            <input type="password" name="new_password" id="new_password" required>
                        </div>
                        <div class="form-group half">
                            <label for="confirm_password">Confirm New Password *</label>
                            <input type="password" name="confirm_password" id="confirm_password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>

            <!-- Notification Preferences -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Notification Preferences</h2>
                </div>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_notifications">
                    
                    <div class="notification-settings">
                        <h3>Email Notifications</h3>
                        <div class="form-group checkbox">
                            <input type="checkbox" name="email_notifications" id="email_notifications" 
                                   <?php echo ($notification_prefs['email_notifications'] ?? true) ? 'checked' : ''; ?>>
                            <label for="email_notifications">Receive email notifications</label>
                        </div>
                        
                        <h3>SMS Notifications</h3>
                        <div class="form-group checkbox">
                            <input type="checkbox" name="sms_notifications" id="sms_notifications" 
                                   <?php echo ($notification_prefs['sms_notifications'] ?? false) ? 'checked' : ''; ?>>
                            <label for="sms_notifications">Receive SMS notifications</label>
                        </div>
                        
                        <h3>WhatsApp Notifications</h3>
                        <div class="form-group checkbox">
                            <input type="checkbox" name="whatsapp_notifications" id="whatsapp_notifications" 
                                   <?php echo ($notification_prefs['whatsapp_notifications'] ?? false) ? 'checked' : ''; ?>>
                            <label for="whatsapp_notifications">Receive WhatsApp notifications</label>
                        </div>
                        
                        <h3>Notification Types</h3>
                        <div class="form-group checkbox">
                            <input type="checkbox" name="pass_expiry_reminders" id="pass_expiry_reminders" 
                                   <?php echo ($notification_prefs['pass_expiry_reminders'] ?? true) ? 'checked' : ''; ?>>
                            <label for="pass_expiry_reminders">Pass expiry reminders</label>
                        </div>
                        
                        <div class="form-group checkbox">
                            <input type="checkbox" name="payment_confirmations" id="payment_confirmations" 
                                   <?php echo ($notification_prefs['payment_confirmations'] ?? true) ? 'checked' : ''; ?>>
                            <label for="payment_confirmations">Payment confirmations</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-bell"></i> Update Notifications
                    </button>
                </form>
            </div>

            <!-- Account Information -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Account Information</h2>
                </div>
                <div class="account-info">
                    <div class="info-row">
                        <span class="info-label">Member Since:</span>
                        <span class="info-value"><?php echo formatDate($user['created_at']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Last Login:</span>
                        <span class="info-value"><?php echo $user['last_login'] ? formatDate($user['last_login']) : 'Never'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Account Status:</span>
                        <span class="info-value">
                            <span class="badge badge-success">Active</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card danger-zone">
                <div class="card-header">
                    <h2 class="card-title">Danger Zone</h2>
                </div>
                <div class="danger-actions">
                    <div class="danger-item">
                        <div class="danger-info">
                            <h3>Delete Account</h3>
                            <p>Permanently delete your account and all associated data. This action cannot be undone.</p>
                        </div>
                        <button class="btn btn-danger" onclick="confirmDeleteAccount()">
                            <i class="fas fa-trash"></i> Delete Account
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Confirm account deletion
        function confirmDeleteAccount() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                if (confirm('This will permanently delete all your data. Are you absolutely sure?')) {
                    // In a real application, this would make an AJAX request to delete the account
                    alert('Account deletion feature would be implemented here.');
                }
            }
        }

        // Form validation
        document.querySelectorAll('.settings-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                submitBtn.disabled = true;
            });
        });
    </script>
</body>
</html>
