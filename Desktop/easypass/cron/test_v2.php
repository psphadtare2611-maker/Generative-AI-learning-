<?php
/**
 * Test Script v2
 */
$script_dir = __DIR__;
$root_dir = dirname($script_dir);

require_once $root_dir . '/db_connect.php';
require_once $root_dir . '/includes/functions.php';

$test_email = 'psphadtare2611@gmail.com';
$days_from_now = 1;

// Ensure User Exists
$user_sql = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("s", $test_email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $user_id = $res->fetch_assoc()['id'];
} else {
    // Create temp user
    $username = 'test_' . time();
    $password = password_hash('password', PASSWORD_DEFAULT);
    $full_name = 'Test User';
    $phone = '9000000000';
    $ins_sql = "INSERT INTO users (username, email, full_name, phone, password, is_active) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $conn->prepare($ins_sql);
    $stmt->bind_param("sssss", $username, $test_email, $full_name, $phone, $password);
    $stmt->execute();
    $user_id = $conn->insert_id;
}

// Create Active Pass expires tomorrow
$pt_sql = "SELECT id FROM pass_types LIMIT 1";
$pass_type_id = $conn->query($pt_sql)->fetch_assoc()['id'];

$pass_number = 'TESTV2' . time();
$qr_code = 'test_qr_code_v2';
$valid_from = date('Y-m-d');
$valid_until = date('Y-m-d', strtotime("+$days_from_now days"));
$routes = '["all"]';

$pass_ins = "INSERT INTO user_passes (user_id, pass_type_id, pass_number, qr_code, valid_from, valid_until, routes, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
$stmt = $conn->prepare($pass_ins);
$stmt->bind_param("iisssss", $user_id, $pass_type_id, $pass_number, $qr_code, $valid_from, $valid_until, $routes);
$stmt->execute();
$pass_id = $conn->insert_id;

echo "Created V2 pass (ID: $pass_id) expiring $valid_until<br>";

// Trigger Notification
putenv("REMINDER_DEFAULT_DAYS=$days_from_now");

// Override DB
$conn->query("UPDATE system_settings SET setting_value = '$days_from_now' WHERE setting_key = 'notification_days_before_expiry'");

// Include V2 script
include __DIR__ . '/send_pass_reminders_v2.php';

// Revert DB
$conn->query("UPDATE system_settings SET setting_value = '7' WHERE setting_key = 'notification_days_before_expiry'");

echo "Done V2.<br>";
?>
