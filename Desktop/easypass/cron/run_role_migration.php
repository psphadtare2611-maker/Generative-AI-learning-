<?php
// cron/run_role_migration.php
$script_dir = __DIR__;
$root_dir = dirname($script_dir);
require_once $root_dir . '/db_connect.php';

// Check if column exists
$check = "SHOW COLUMNS FROM users LIKE 'role'";
$res = $conn->query($check);
if ($res->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN role ENUM('admin', 'user') DEFAULT 'user'";
    if ($conn->query($sql)) {
        echo "Column 'role' added.<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "Column 'role' already exists.<br>";
}

// Make user 1 admin
$conn->query("UPDATE users SET role = 'admin' WHERE id = 1");
echo "User 1 promoted to admin.<br>";
?>
