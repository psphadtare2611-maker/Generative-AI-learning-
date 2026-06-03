<?php
/**
 * Database Reset Script
 * This script drops and recreates all tables in the correct order
 * Use this if you encounter foreign key constraint errors
 */

// Include database connection
require_once 'db_connect.php';

echo "<h2>EasyPass Database Reset</h2>";
echo "<p>This script will drop and recreate all tables...</p>";

// Disable foreign key checks temporarily
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// List of tables to drop (in reverse dependency order)
$tables_to_drop = [
    'user_activity_log',
    'login_attempts', 
    'notifications',
    'payments',
    'trips',
    'user_passes',
    'system_settings',
    'routes',
    'pass_types',
    'users'
];

echo "<h3>Dropping existing tables...</h3>";
foreach ($tables_to_drop as $table) {
    $sql = "DROP TABLE IF EXISTS `$table`";
    if ($conn->query($sql)) {
        echo "✓ Dropped table: $table<br>";
    } else {
        echo "✗ Error dropping table $table: " . $conn->error . "<br>";
    }
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "<h3>Recreating tables...</h3>";

// Recreate tables in correct order
$users_sql = "CREATE TABLE users (
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

$pass_types_sql = "CREATE TABLE pass_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration_days INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    routes JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$routes_sql = "CREATE TABLE routes (
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

$user_passes_sql = "CREATE TABLE user_passes (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pass_type_id) REFERENCES pass_types(id) ON DELETE CASCADE
)";

$trips_sql = "CREATE TABLE trips (
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
    notes TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pass_id) REFERENCES user_passes(id) ON DELETE SET NULL,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
)";

$payments_sql = "CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pass_id INT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('credit_card', 'debit_card', 'upi', 'net_banking', 'wallet', 'cash') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255) NULL,
    gateway_response JSON NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pass_id) REFERENCES user_passes(id) ON DELETE SET NULL
)";

$notifications_sql = "CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('pass_expiry', 'payment_success', 'payment_failed', 'trip_reminder', 'system_update') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    sent_via ENUM('email', 'sms', 'whatsapp', 'in_app') NOT NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

$settings_sql = "CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$login_attempts_sql = "CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(255),
    success BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, created_at)
)";

$activity_log_sql = "CREATE TABLE user_activity_log (
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

// Create tables in correct order
$create_queries = [
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

foreach ($create_queries as $table_name => $sql) {
    if ($conn->query($sql)) {
        echo "✓ Created table: $table_name<br>";
    } else {
        echo "✗ Error creating table $table_name: " . $conn->error . "<br>";
    }
}

// Insert default data
echo "<h3>Inserting default data...</h3>";

// Insert default pass types
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
    if ($conn->query($sql)) {
        echo "✓ Inserted pass type<br>";
    } else {
        echo "✗ Error inserting pass type: " . $conn->error . "<br>";
    }
}

// Insert default routes
$default_routes = [
    "INSERT INTO routes (route_number, route_name, start_location, end_location, stops, fare, duration_minutes) VALUES 
     ('1A', 'Central Station to Tech Park', 'Central Station', 'Tech Park', '[\"Central Station\", \"Mall Plaza\", \"University\", \"Tech Park\"]', 15.00, 45)",
    "INSERT INTO routes (route_number, route_name, start_location, end_location, stops, fare, duration_minutes) VALUES 
     ('2B', 'Tech Park to Mall Plaza', 'Tech Park', 'Mall Plaza', '[\"Tech Park\", \"University\", \"Mall Plaza\"]', 12.00, 30)",
    "INSERT INTO routes (route_number, route_name, start_location, end_location, stops, fare, duration_minutes) VALUES 
     ('3C', 'Central Station to University', 'Central Station', 'University', '[\"Central Station\", \"Mall Plaza\", \"University\"]', 20.00, 35)"
];

foreach ($default_routes as $sql) {
    if ($conn->query($sql)) {
        echo "✓ Inserted route<br>";
    } else {
        echo "✗ Error inserting route: " . $conn->error . "<br>";
    }
}

// Insert default system settings
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
    if ($conn->query($sql)) {
        echo "✓ Inserted system setting<br>";
    } else {
        echo "✗ Error inserting system setting: " . $conn->error . "<br>";
    }
}

echo "<h3>Database reset complete!</h3>";
echo "<p><a href='index.php'>Go to Homepage</a> | <a href='signup.php'>Create Account</a></p>";

$conn->close();
?>
