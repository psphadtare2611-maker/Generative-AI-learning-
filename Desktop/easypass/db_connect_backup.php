<?php
/**
 * Simplified Database Connection and Schema Setup
 * This version creates tables without foreign key constraints to avoid errors
 */

$host = 'localhost';
$db = 'bus_pass_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for proper Unicode support
$conn->set_charset("utf8mb4");

/**
 * Create all required tables for the bus pass management system
 * WITHOUT foreign key constraints to avoid errors
 */

// Users table with enhanced fields
$users_sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    profile_image VARCHAR(500) NULL,
    address TEXT NULL,
    date_of_birth DATE NULL,
    emergency_contact VARCHAR(20) NULL,
    notification_preferences JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
)";

// Pass types table
$pass_types_sql = "CREATE TABLE IF NOT EXISTS pass_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration_days INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    routes JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Routes table
$routes_sql = "CREATE TABLE IF NOT EXISTS routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_number VARCHAR(20) NOT NULL UNIQUE,
    route_name VARCHAR(255) NOT NULL,
    start_location VARCHAR(255) NOT NULL,
    end_location VARCHAR(255) NOT NULL,
    stops JSON NOT NULL,
    fare DECIMAL(8,2) NOT NULL,
    duration_minutes INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// User passes table (without foreign keys)
$user_passes_sql = "CREATE TABLE IF NOT EXISTS user_passes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pass_type_id INT NOT NULL,
    pass_number VARCHAR(50) NOT NULL UNIQUE,
    qr_code VARCHAR(500) NOT NULL,
    status ENUM('active', 'expired', 'cancelled', 'suspended') DEFAULT 'active',
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    routes JSON NULL,
    auto_renewal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Trips table (without foreign keys)
$trips_sql = "CREATE TABLE IF NOT EXISTS trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pass_id INT NULL,
    route_id INT NOT NULL,
    from_stop VARCHAR(255) NOT NULL,
    to_stop VARCHAR(255) NOT NULL,
    fare DECIMAL(8,2) NOT NULL,
    trip_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('completed', 'cancelled', 'refunded') DEFAULT 'completed',
    conductor_id INT NULL,
    bus_number VARCHAR(20) NULL,
    notes TEXT NULL
)";

// Payments table (without foreign keys)
$payments_sql = "CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pass_id INT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('credit_card', 'debit_card', 'upi', 'net_banking', 'wallet', 'cash') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255) NULL,
    gateway_response JSON NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description TEXT NULL
)";

// Notifications table (without foreign keys)
$notifications_sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('pass_expiry', 'payment_success', 'payment_failed', 'trip_reminder', 'system_update') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    sent_via ENUM('email', 'sms', 'whatsapp', 'in_app') NOT NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// System settings table
$settings_sql = "CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Login attempts table
$login_attempts_sql = "CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(255),
    success BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, created_at)
)";

// User activity log table (without foreign keys)
$activity_log_sql = "CREATE TABLE IF NOT EXISTS user_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_time (user_id, created_at)
)";

// Execute table creation queries
$tables = [
    'users' => $users_sql,
    'pass_types' => $pass_types_sql,
    'routes' => $routes_sql,
    'user_passes' => $user_passes_sql,
    'trips' => $trips_sql,
    'payments' => $payments_sql,
    'notifications' => $notifications_sql,
    'system_settings' => $settings_sql,
    'login_attempts' => $login_attempts_sql,
    'user_activity_log' => $activity_log_sql
];

foreach ($tables as $table_name => $sql) {
    if ($conn->query($sql) === TRUE) {
        // Table created successfully or already exists
    } else {
        echo "Error creating table $table_name: " . $conn->error . "<br>";
    }
}

// Insert default pass types if they don't exist
$check_pass_types = "SELECT COUNT(*) as count FROM pass_types";
$result = $conn->query($check_pass_types);
$count = $result->fetch_assoc()['count'];

if ($count == 0) {
    $default_pass_types = [
        "INSERT INTO pass_types (name, description, duration_days, price, routes) VALUES 
         ('Daily Pass', 'Unlimited rides for 24 hours', 1, 680.00, '[\"all\"]')",
        "INSERT INTO pass_types (name, description, duration_days, price, routes) VALUES 
         ('Monthly Pass', 'Unlimited rides for 30 days', 30, 3699.00, '[\"all\"]')",
        "INSERT INTO pass_types (name, description, duration_days, price, routes) VALUES 
         ('Six Month Pass', 'Unlimited rides for 180 days', 180, 19999.00, '[\"all\"]')",
        "INSERT INTO pass_types (name, description, duration_days, price, routes) VALUES 
         ('Annual Pass', 'Unlimited rides for 365 days', 365, 7499.00, '[\"all\"]')"
    ];
    
    foreach ($default_pass_types as $sql) {
        $conn->query($sql);
    }
}

// Insert default routes if they don't exist
$check_routes = "SELECT COUNT(*) as count FROM routes";
$result = $conn->query($check_routes);
$count = $result->fetch_assoc()['count'];

if ($count == 0) {
    $default_routes = [
        "INSERT INTO routes (route_number, route_name, start_location, end_location, stops, fare, duration_minutes) VALUES 
         ('1A', 'Central Station to Tech Park', 'Central Station', 'Tech Park', '[\"Central Station\", \"Mall Plaza\", \"University\", \"Tech Park\"]', 15.00, 45)",
        "INSERT INTO routes (route_number, route_name, start_location, end_location, stops, fare, duration_minutes) VALUES 
         ('2B', 'Tech Park to Mall Plaza', 'Tech Park', 'Mall Plaza', '[\"Tech Park\", \"University\", \"Mall Plaza\"]', 12.00, 30)",
        "INSERT INTO routes (route_number, route_name, start_location, end_location, stops, fare, duration_minutes) VALUES 
         ('3C', 'Central Station to University', 'Central Station', 'University', '[\"Central Station\", \"Mall Plaza\", \"University\"]', 20.00, 35)"
    ];
    
    foreach ($default_routes as $sql) {
        $conn->query($sql);
    }
}

// Insert default system settings
$check_settings = "SELECT COUNT(*) as count FROM system_settings";
$result = $conn->query($check_settings);
$count = $result->fetch_assoc()['count'];

if ($count == 0) {
    $default_settings = [
        "INSERT INTO system_settings (setting_key, setting_value, description) VALUES 
         ('site_name', 'EasyPass', 'Name of the bus pass management system')",
        "INSERT INTO system_settings (setting_key, setting_value, description) VALUES 
         ('notification_days_before_expiry', '7', 'Number of days before pass expiry to send notification')",
        "INSERT INTO system_settings (setting_key, setting_value, description) VALUES 
         ('email_from', 'noreply@easypass.com', 'Default email sender address')",
        "INSERT INTO system_settings (setting_key, setting_value, description) VALUES 
         ('sms_enabled', 'false', 'Enable SMS notifications')",
        "INSERT INTO system_settings (setting_key, setting_value, description) VALUES 
         ('whatsapp_enabled', 'false', 'Enable WhatsApp notifications')"
    ];
    
    foreach ($default_settings as $sql) {
        $conn->query($sql);
    }
}

?>
