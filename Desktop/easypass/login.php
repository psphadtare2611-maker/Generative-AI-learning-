<?php
/**
 * Enhanced Login System with Security Features
 * This file handles user authentication with rate limiting, security checks, and proper session management
 */

// Start output buffering to prevent any output before headers
ob_start();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';
require_once 'auth_check.php';

$error_message = '';
$success_message = '';

// Check if user is already logged in
if (!empty($_SESSION['logged_in'])) {
    ob_end_clean();
    header('Location: /easypass/dashboard.php');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        // Check rate limiting
        if (isRateLimited($ip_address)) {
            $error_message = "Too many login attempts. Please try again in 15 minutes.";
        } else {
            // Check user credentials
            // Check user credentials
            $sql = "SELECT id, username, email, full_name, password, is_active, email_verified, role 
                    FROM users 
                    WHERE (username = ? OR email = ?) AND is_active = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    // Force session variables so checkUserLogin() works
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['email']     = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role']      = $user['role'];

                    session_write_close();

                    // Correct absolute path
                    header('Location: /easypass/dashboard.php');
                    exit();
                } else {
                    // Invalid password
                    recordLoginAttempt($ip_address, false, $username);
                    $error_message = "Invalid username or password.";
                }
            } else {
                // User not found
                recordLoginAttempt($ip_address, false, $username);
                $error_message = "Invalid username or password.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - EasyPass</title>
  <link rel="stylesheet" href="styles/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>

<body>
  <div class="login-container">
    <div class="logo">
      <i class="fas fa-bus"></i> EasyPass
    </div>
    <h2>Login to Your Account</h2>

    <?php if ($error_message): ?>
      <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <input type="text" name="username" placeholder="Username or Email" required
        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
      <input type="password" name="password" placeholder="Enter your password" required>
      <button type="submit">Login</button>
    </form>

    <div class="signup-link">
      Don't have an account? <a href="signup.php">Sign up here</a>
    </div>
  </div>
</body>

</html>