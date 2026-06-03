<?php
/**
 * Enhanced Registration System with Validation and Security
 * This file handles user registration with comprehensive validation and security features
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';
require_once 'auth_check.php';

$error_message = '';
$success_message = '';

// Check if user is already logged in
if (checkUserLogin(false)) {
    header('Location: dashboard.php');
    exit();
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

    // Comprehensive validation
    $errors = [];
    
    // Required fields validation
    if (empty($username)) $errors[] = "Username is required.";
    if (empty($email)) $errors[] = "Email is required.";
    if (empty($full_name)) $errors[] = "Full name is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (empty($password)) $errors[] = "Password is required.";
    
    // Username validation
    if (!empty($username)) {
        if (strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters long.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Username can only contain letters, numbers, and underscores.";
        }
    }
    
    // Email validation
    if (!empty($email) && !validateEmail($email)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    // Phone validation
    if (!empty($phone) && !validatePhone($phone)) {
        $errors[] = "Please enter a valid 10-digit phone number.";
    }
    
    // Password validation
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter, one uppercase letter, and one number.";
        }
    }
    
    // Password confirmation
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // Check for existing users
    if (empty($errors)) {
        $check_sql = "SELECT id FROM users WHERE username = ? OR email = ? OR phone = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("sss", $username, $email, $phone);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = "Username, email, or phone number already exists.";
        }
    }
    
    // If no errors, create account
    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            
            // Hash password securely
            $hashed_password = hashPassword($password);
            
            // Insert new user with enhanced data
            $insert_sql = "INSERT INTO users (username, email, full_name, phone, password, created_at) 
                          VALUES (?, ?, ?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $username, $email, $full_name, $phone, $hashed_password);

            if ($insert_stmt->execute()) {
                $new_user_id = $conn->insert_id;
                
                // Set up notification preferences
                $notification_prefs = json_encode([
                    'email_notifications' => true,
                    'sms_notifications' => false,
                    'whatsapp_notifications' => false,
                    'pass_expiry_reminders' => true,
                    'payment_confirmations' => true
                ]);
                
                $update_sql = "UPDATE users SET notification_preferences = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("si", $notification_prefs, $new_user_id);
                $update_stmt->execute();
                
                $conn->commit();
                
                // Set secure session
                $user_data = [
                    'id' => $new_user_id,
                    'username' => $username,
                    'email' => $email,
                    'full_name' => $full_name
                ];
                setSecureSession($user_data);
                
                // Log registration activity
                logUserActivity('registration', 'New user registered');
                
                // Send welcome email (optional)
                $subject = "Welcome to EasyPass!";
                $message = "Dear {$full_name},<br><br>
                          Welcome to EasyPass! Your account has been created successfully.<br>
                          You can now purchase bus passes and manage your travel easily.<br><br>
                          <a href='https://easypass.com/dashboard' style='background-color: #1a73e8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Get Started</a><br><br>
                          Best regards,<br>EasyPass Team";
                
                sendEmail($email, $subject, $message);
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
                
            } else {
                throw new Exception("Failed to create user account.");
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error creating account. Please try again.";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - EasyPass</title>
    <link rel="stylesheet" href="styles/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>

<body>
    <div class="signup-container">
        <div class="logo">
            <i class="fas fa-bus"></i> EasyPass
        </div>
        <h2>Create Your Account</h2>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="POST" action="signup.php">
            <div class="form-group">
                <input type="text" name="full_name" placeholder="Full Name" required
                    value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            </div>

            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <input type="email" name="email" placeholder="Email Address" required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <input type="tel" name="phone" placeholder="Phone Number" required
                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>

            <div class="form-group">
                <input type="password" name="password" placeholder="Password (min 6 characters)" required>
            </div>

            <div class="form-group">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            </div>

            <button type="submit">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>

</html>