<?php
/**
 * Simple Database Setup Script
 * This script creates the database and tables without foreign key constraints initially
 */

// Database connection parameters
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'bus_pass_db';

// Create connection
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>EasyPass Database Setup</h2>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS `$dbname`";
if ($conn->query($sql) === TRUE) {
    echo "✓ Database '$dbname' created successfully<br>";
} else {
    echo "✗ Error creating database: " . $conn->error . "<br>";
}

// Select database
$conn->select_db($dbname);

// Disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Create tables without foreign key constraints first
$tables = [
    'users' => "CREATE TABLE IF NOT EXISTS users (
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
    )",
    
    'pass_types' => "CREATE TABLE IF NOT EXISTS pass_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        duration_days INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        routes JSON NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    'routes' => "CREATE TABLE IF NOT EXISTS routes (
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
    )",
    
    'user_passes' => "CREATE TABLE IF NOT EXISTS user_passes (
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
    )",
    
    'trips' => "CREATE TABLE IF NOT EXISTS trips (
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
    )",
    
    'payments' => "CREATE TABLE IF NOT EXISTS payments (
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
    )",
    
    'notifications' => "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('pass_expiry', 'payment_success', 'payment_failed', 'trip_reminder', 'system_update') NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        sent_via ENUM('email', 'sms', 'whatsapp', 'in_app') NOT NULL,
        sent_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    'system_settings' => "CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL,
        description TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    'login_attempts' => "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        username VARCHAR(255),
        success BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip_time (ip_address, created_at)
    )",
    
    'user_activity_log' => "CREATE TABLE IF NOT EXISTS user_activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_time (user_id, created_at)
    )"
];

// Create tables
echo "<h3>Creating tables...</h3>";
foreach ($tables as $table_name => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "✓ Table '$table_name' created successfully<br>";
    } else {
        echo "✗ Error creating table '$table_name': " . $conn->error . "<br>";
    }
}

// Now add foreign key constraints
echo "<h3>Adding foreign key constraints...</h3>";

$foreign_keys = [
    "ALTER TABLE user_passes ADD CONSTRAINT fk_user_passes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
    "ALTER TABLE user_passes ADD CONSTRAINT fk_user_passes_pass_type FOREIGN KEY (pass_type_id) REFERENCES pass_types(id) ON DELETE CASCADE",
    "ALTER TABLE trips ADD CONSTRAINT fk_trips_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
    "ALTER TABLE trips ADD CONSTRAINT fk_trips_pass FOREIGN KEY (pass_id) REFERENCES user_passes(id) ON DELETE SET NULL",
    "ALTER TABLE trips ADD CONSTRAINT fk_trips_route FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE",
    "ALTER TABLE payments ADD CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
    "ALTER TABLE payments ADD CONSTRAINT fk_payments_pass FOREIGN KEY (pass_id) REFERENCES user_passes(id) ON DELETE SET NULL",
    "ALTER TABLE notifications ADD CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
    "ALTER TABLE user_activity_log ADD CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE"
];

foreach ($foreign_keys as $fk_sql) {
    if ($conn->query($fk_sql) === TRUE) {
        echo "✓ Foreign key constraint added<br>";
    } else {
        echo "✗ Error adding foreign key: " . $conn->error . "<br>";
    }
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Insert default data
echo "<h3>Inserting default data...</h3>";

// Insert default pass types
$pass_types = [
    "INSERT INTO pass_types (name, description, duration_days, price, routes) VALUES ('Daily Pass', 'Unlimited rides for 24 hours', 1, 680.00, '[\"all\"]')",
    "INSERT INTO pass_types (name, description, duration_days, price, routes) VALUES ('Monthly Pass', 'Unlimited rides for 30 days', 30, 3699.00, '[\"all\"]')",
    "INSERT INTO pass_types (name, description, duration_days, price, routes) VALUES ('Six Month Pass', 'Unlimited rides for 180 days', 180, 19999.00, '[\"all\"]')",
    "INSERT INTO pass_types (name, description, duration_days, price, routes) VALUES ('Annual Pass', 'Unlimited rides for 365 days', 365, 7499.00, '[\"all\"]')"
];

foreach ($pass_types as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "✓ Pass type inserted<br>";
    } else {
        echo "✗ Error inserting pass type: " . $conn->error . "<br>";
    }
}

// Insert default routes
$routes = [
    "INSERT INTO routes (route_number, route_name, start_location, end_location, stops, fare, duration_minutes) VALUES ('1A', 'Central Station to Tech Park', 'Central Station', 'Tech Park', '[\"Central Station\", \"Mall Plaza\", \"University\", \"Tech Park\"]', 15.00, 45)",
    "INSERT INTO routes (route_number, route_name, start_location, end_location, stops, fare, duration_minutes) VALUES ('2B', 'Tech Park to Mall Plaza', 'Tech Park', 'Mall Plaza', '[\"Tech Park\", \"University\", \"Mall Plaza\"]', 12.00, 30)",
    "INSERT INTO routes (route_number, route_name, start_location, end_location, stops, fare, duration_minutes) VALUES ('3C', 'Central Station to University', 'Central Station', 'University', '[\"Central Station\", \"Mall Plaza\", \"University\"]', 20.00, 35)"
];

foreach ($routes as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "✓ Route inserted<br>";
    } else {
        echo "✗ Error inserting route: " . $conn->error . "<br>";
    }
}

// Insert default system settings
$settings = [
    "INSERT INTO system_settings (setting_key, setting_value, description) VALUES ('site_name', 'EasyPass', 'Name of the bus pass management system')",
    "INSERT INTO system_settings (setting_key, setting_value, description) VALUES ('notification_days_before_expiry', '7', 'Number of days before pass expiry to send notification')",
    "INSERT INTO system_settings (setting_key, setting_value, description) VALUES ('email_from', 'noreply@easypass.com', 'Default email sender address')",
    "INSERT INTO system_settings (setting_key, setting_value, description) VALUES ('sms_enabled', 'false', 'Enable SMS notifications')",
    "INSERT INTO system_settings (setting_key, setting_value, description) VALUES ('whatsapp_enabled', 'false', 'Enable WhatsApp notifications')"
];

foreach ($settings as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "✓ System setting inserted<br>";
    } else {
        echo "✗ Error inserting system setting: " . $conn->error . "<br>";
    }
}

echo "<h3>Database setup complete!</h3>";
echo "<p><a href='index.php'>Go to Homepage</a> | <a href='signup.php'>Create Account</a></p>";

$conn->close();
?>
