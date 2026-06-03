<?php
/**
 * Utility Functions for Bus Pass Management System
 * This file contains all helper functions for security, validation, and common operations
 */

require_once __DIR__ . '/../db_connect.php';


/**
 * Security Functions
 */

/**
 * Sanitize input data to prevent XSS attacks
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Indian format)
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function validatePhone($phone) {
    return preg_match('/^[6-9]\d{9}$/', $phone);
}

/**
 * Generate secure random token
 * @param int $length Token length
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Hash password securely
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if user is logged in
 * @return bool True if logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data (defined in auth_check.php)
 * This function is already defined in auth_check.php to avoid conflicts
 */

/**
 * Redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Pass Management Functions
 */

/**
 * Get all pass types
 * @return array Pass types
 */
function getPassTypes() {
    global $conn;
    $sql = "SELECT * FROM pass_types WHERE is_active = 1 ORDER BY price ASC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get user's active passes
 * @param int $user_id User ID
 * @return array User's passes
 */
function getUserPasses($user_id) {
    global $conn;
    $sql = "SELECT up.*, pt.name as pass_type_name, pt.description 
             FROM user_passes up 
             JOIN pass_types pt ON up.pass_type_id = pt.id 
             WHERE up.user_id = ? 
             ORDER BY up.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get user's active pass
 * @param int $user_id User ID
 * @return array|null Active pass or null
 */
function getActivePass($user_id) {
    global $conn;
    $sql = "SELECT up.*, pt.name as pass_type_name, pt.description 
             FROM user_passes up 
             JOIN pass_types pt ON up.pass_type_id = pt.id 
             WHERE up.user_id = ? AND up.status = 'active' 
             AND up.valid_until >= CURDATE()
             ORDER BY up.valid_until DESC 
             LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function sendEmail($to, $subject, $message, $name = '')
{
    require_once __DIR__ . '/email_helper.php';

    if (empty($to)) {
        error_log("sendEmail ERROR: Recipient email missing.");
        return false;
    }

    return sendSMTP($to, $name, $subject, $message);
}


function createNotification($user_id, $type, $title, $message, $sent_via = 'in_app') {
    global $conn;
    
    $sql = "INSERT INTO notifications (user_id, type, title, message, sent_via) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $type, $title, $message, $sent_via);
    
    return $stmt->execute();
}

/**
 * Create new pass for user
 * @param int $user_id User ID
 * @param int $pass_type_id Pass type ID
 * @param float $amount_paid Amount paid for the pass
 * @param string $routes Allowed routes
 * @return array Result with success status and pass data
 */
function createUserPass($user_id, $pass_type_id, $amount_paid, $routes = '["all"]') {
    global $conn;

    try {
        // Start transaction
        $conn->begin_transaction();

        // Fetch pass type
        $sql = "SELECT * FROM pass_types WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pass_type_id);
        $stmt->execute();
        $pass_type = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$pass_type) {
            throw new Exception("Invalid pass type");
        }

        // Prepare pass data
        $pass_number = 'EP' . str_pad($user_id, 6, '0', STR_PAD_LEFT) . date('Ymd') . rand(1000, 9999);
        $valid_from = date('Y-m-d');
        $valid_until = date('Y-m-d', strtotime("+{$pass_type['duration_days']} days"));

        $qr_data = json_encode([
            'pass_number' => $pass_number,
            'user_id'     => $user_id,
            'valid_until' => $valid_until
        ]);

        // Insert pass record
        $insert_sql = "INSERT INTO user_passes (user_id, pass_type_id, pass_number, qr_code, valid_from, valid_until, routes)
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iisssss", $user_id, $pass_type_id, $pass_number, $qr_data, $valid_from, $valid_until, $routes);

        if (!$stmt->execute()) {
            $err = $stmt->error ?: "Failed to execute insert";
            $stmt->close();
            throw new Exception("Failed to create pass: " . $err);
        }

        $pass_id = $conn->insert_id;
        $stmt->close();

        // Fetch user details for email (still inside try but we will commit before sending)
        $u_sql = "SELECT email, full_name FROM users WHERE id = ?";
        $u_stmt = $conn->prepare($u_sql);
        $u_stmt->bind_param("i", $user_id);
        $u_stmt->execute();
        $user_details = $u_stmt->get_result()->fetch_assoc();
        $u_stmt->close();

        // Commit transaction before performing any external IO (emails)
        $conn->commit();

        // After successful commit: send confirmation email (non-blocking)
        if (!empty($user_details['email'])) {
            // include notification functions only when needed (email_notifications.php should not require functions.php)
            require_once __DIR__ . '/email_notifications.php';

            $pass_details = [
                'user_id'        => $user_id,
                'email'          => $user_details['email'],
                'full_name'      => $user_details['full_name'],
                'pass_type_name' => $pass_type['name'] ?? 'Bus Pass',
                'pass_number'    => $pass_number,
                'valid_from'     => $valid_from,
                'valid_until'    => $valid_until,
                'amount'         => number_format($amount_paid, 2)
            ];

            try {
                // try/catch so email failures don't break the user flow
                sendPassPurchaseConfirmationEmail($pass_details);
            } catch (Throwable $e) {
                error_log("sendPassPurchaseConfirmationEmail failed: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'pass_id' => $pass_id,
            'pass_number' => $pass_number,
            'valid_until' => $valid_until
        ];

    } catch (Exception $e) {
        // Rollback on error, return structured error
        if ($conn->errno === 0) {
            // if commit already failed, ensure rollback safe
            @$conn->rollback();
        } else {
            @$conn->rollback();
        }

        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}


/**
 * Payment Functions
 */

/**
 * Create payment record
 * @param int $user_id User ID
 * @param float $amount Payment amount
 * @param string $payment_method Payment method
 * @param int $pass_id Pass ID (optional)
 * @param string $description Payment description
 * @return array Result with payment data
 */
function createPayment($user_id, $amount, $payment_method, $pass_id = null, $description = '') {
    global $conn;
    
    $transaction_id = 'TXN' . time() . rand(1000, 9999);
    
    $sql = "INSERT INTO payments (user_id, pass_id, amount, payment_method, transaction_id, description) 
             VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iidsss", $user_id, $pass_id, $amount, $payment_method, $transaction_id, $description);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'payment_id' => $conn->insert_id,
            'transaction_id' => $transaction_id
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Failed to create payment record'
    ];
}

/**
 * Update payment status
 * @param int $payment_id Payment ID
 * @param string $status New status
 * @param array $gateway_response Gateway response data
 * @return bool Success status
 */
function updatePaymentStatus($payment_id, $status, $gateway_response = null) {
    global $conn;
    
    $response_json = $gateway_response ? json_encode($gateway_response) : null;
    
    $sql = "UPDATE payments SET payment_status = ?, gateway_response = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $status, $response_json, $payment_id);
    
    return $stmt->execute();
}

/**
 * Notification Functions
 */

/**
 * Send email notification
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @return bool Success status
 */
/**
 * Send email notification
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @return bool Success status
 */

/**
 * Create notification record
 * @param int $user_id User ID
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $sent_via Delivery method
 * @return bool Success status
 */


/**
 * Send pass expiry notifications
 * @return int Number of notifications sent
 */


/**
 * Trip Management Functions
 */

/**
 * Record a trip
 * @param int $user_id User ID
 * @param int $route_id Route ID
 * @param string $from_stop Starting stop
 * @param string $to_stop Destination stop
 * @param float $fare Trip fare
 * @param int $pass_id Pass ID (optional)
 * @return bool Success status
 */
function recordTrip($user_id, $route_id, $from_stop, $to_stop, $fare, $pass_id = null) {
    global $conn;
    
    $sql = "INSERT INTO trips (user_id, pass_id, route_id, from_stop, to_stop, fare) 
             VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiissd", $user_id, $pass_id, $route_id, $from_stop, $to_stop, $fare);
    
    return $stmt->execute();
}

/**
 * Get user's trip history
 * @param int $user_id User ID
 * @param int $limit Number of trips to retrieve
 * @return array Trip history
 */
function getTripHistory($user_id, $limit = 50) {
    global $conn;
    
    $sql = "SELECT t.*, r.route_number, r.route_name 
             FROM trips t 
             JOIN routes r ON t.route_id = r.id 
             WHERE t.user_id = ? 
             ORDER BY t.trip_date DESC 
             LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Dashboard Statistics Functions
 */

/**
 * Get user dashboard statistics
 * @param int $user_id User ID
 * @return array Dashboard stats
 */
function getDashboardStats($user_id) {
    global $conn;
    
    $stats = [];
    
    // Active passes count
    $sql = "SELECT COUNT(*) as active_passes FROM user_passes WHERE user_id = ? AND status = 'active' AND valid_until >= CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['active_passes'] = $stmt->get_result()->fetch_assoc()['active_passes'];
    
    // Total trips this month
    $sql = "SELECT COUNT(*) as monthly_trips FROM trips WHERE user_id = ? AND MONTH(trip_date) = MONTH(CURDATE()) AND YEAR(trip_date) = YEAR(CURDATE())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['monthly_trips'] = $stmt->get_result()->fetch_assoc()['monthly_trips'];
    
    // Total spent this month
    $sql = "SELECT COALESCE(SUM(amount), 0) as monthly_spent FROM payments WHERE user_id = ? AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE()) AND payment_status = 'completed'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['monthly_spent'] = $stmt->get_result()->fetch_assoc()['monthly_spent'];
    
    // Unread notifications
    $sql = "SELECT COUNT(*) as unread_notifications FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['unread_notifications'] = $stmt->get_result()->fetch_assoc()['unread_notifications'];
    
    return $stats;
}

/**
 * Utility Functions
 */

/**
 * Format currency
 * @param float $amount Amount to format
 * @return string Formatted currency
 */
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Format date
 * @param string $date Date string
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate($date, $format = 'd M, Y') {
    return date($format, strtotime($date));
}

/**
 * Get time ago string
 * @param string $datetime DateTime string
 * @return string Time ago string
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

/**
 * Generate QR code for pass
 * @param string $data QR code data
 * @return string QR code image path
 */
function generateQRCode($data) {
    ob_start();
    \QRcode::png($data, null, QR_ECLEVEL_L, 4, 2);
    $imageData = ob_get_clean();
    return "data:image/png;base64," . base64_encode($imageData);
}



/**
 * Check if pass is valid for route
 * @param int $pass_id Pass ID
 * @param int $route_id Route ID
 * @return bool True if valid
 */
function isPassValidForRoute($pass_id, $route_id) {
    global $conn;
    
    $sql = "SELECT up.routes FROM user_passes up WHERE up.id = ? AND up.status = 'active' AND up.valid_until >= CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pass_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $pass = $result->fetch_assoc();
    $routes = json_decode($pass['routes'], true);
    
    return in_array('all', $routes) || in_array($route_id, $routes);
}

?>
