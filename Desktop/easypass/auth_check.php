<?php
/**
 * Enhanced Authentication and Security Functions
 * This file handles user authentication, session management, and security checks
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include utility functions
require_once 'includes/functions.php';

/**
 * Enhanced user login check with security features
 * @param bool $redirect Whether to redirect to login page if not authenticated
 * @return bool True if user is logged in
 */
function checkUserLogin($redirect = true) {
    return (!empty($_SESSION['logged_in']));
}

/**
 * Verify that the current session is still valid
 * @return bool True if session is valid
 */
function verifySession() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
        return false;
    }
    
    global $conn;
    
    // Check if user still exists and is active
    $sql = "SELECT id, is_active FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $user = $result->fetch_assoc();
    return $user['is_active'] == 1;
}

/**
 * Get current user data with enhanced information
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (!checkUserLogin(false)) {
        return null;
    }
    
    global $conn;
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT id, username, email, full_name, phone, created_at, last_login 
            FROM users 
            WHERE id = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Update user's last login time
 * @param int $user_id User ID
 */
function updateLastLogin($user_id) {
    global $conn;
    
    $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

/**
 * Generate secure session token
 * @return string Session token
 */
function generateSessionToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Set secure session data
 * @param array $user_data User data to store in session
 */
function setSecureSession($user_data) {
    // Regenerate session ID for security (keep old session data)
    session_regenerate_id(false);

    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['username'] = $user_data['username'];
    $_SESSION['full_name'] = $user_data['full_name'];
    $_SESSION['email'] = $user_data['email'];
    $_SESSION['session_token'] = generateSessionToken();
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['role'] = $user_data['role'] ?? 'user';

    // Update last login
    updateLastLogin($user_data['id']);
}

/**
 * Require admin privileges
 */
function requireAdmin() {
    if (!checkUserLogin()) {
        header('Location: /easypass/login.php');
        exit();
    }
    
    if (($_SESSION['role'] ?? 'user') !== 'admin') {
        // Redirect to dashboard or error page
        header('Location: /easypass/dashboard.php');
        exit();
    }
}

/**
 * Validate session security
 * @return bool True if session is secure
 */
function validateSessionSecurity() {
    // Check if IP address changed (basic security check)
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
        return false;
    }
    
    // Check if user agent changed
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        return false;
    }
    
    // Check session timeout (24 hours)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 86400) {
        return false;
    }
    
    return true;
}

/**
 * Log user activity
 * @param string $action Action performed
 * @param string $details Additional details
 */
function logUserActivity($action, $details = '') {
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    global $conn;
    
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $sql = "INSERT INTO user_activity_log (user_id, action, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $action, $details, $ip_address, $user_agent);
    $stmt->execute();
}

/**
 * Check if user has permission for action
 * @param string $permission Permission to check
 * @return bool True if user has permission
 */
function hasPermission($permission) {
    // Basic permission system - can be extended
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    // For now, all logged-in users have basic permissions
    $basic_permissions = [
        'view_dashboard',
        'manage_passes',
        'view_trips',
        'make_payments',
        'update_profile'
    ];
    
    return in_array($permission, $basic_permissions);
}

/**
 * Secure logout with cleanup
 */
function secureLogout() {
    // Log logout activity
    logUserActivity('logout', 'User logged out');
    
    // Clear all session data
    $_SESSION = array();
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Rate limiting for login attempts
 * @param string $ip_address IP address
 * @return bool True if rate limit exceeded
 */
function isRateLimited($ip_address) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $result = $stmt->get_result();
    $attempts = $result->fetch_assoc()['attempts'];
    
    return $attempts >= 5; // Max 5 attempts per 15 minutes
}

/**
 * Record login attempt
 * @param string $ip_address IP address
 * @param bool $success Whether login was successful
 * @param string $username Username attempted
 */
function recordLoginAttempt($ip_address, $success, $username = '') {
    global $conn;
    
    $sql = "INSERT INTO login_attempts (ip_address, username, success, created_at) 
            VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $ip_address, $username, $success);
    $stmt->execute();
}

/**
 * Create login attempts table if it doesn't exist
 */
function createLoginAttemptsTable() {
    global $conn;
    
    $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        username VARCHAR(255),
        success BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip_time (ip_address, created_at)
    )";
    
    $conn->query($sql);
}

/**
 * Create user activity log table if it doesn't exist
 */
function createActivityLogTable() {
    global $conn;
    
    $sql = "CREATE TABLE IF NOT EXISTS user_activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_time (user_id, created_at)
    )";
    
    $conn->query($sql);
}

// Create necessary tables
createLoginAttemptsTable();
createActivityLogTable();

?>