<?php
/**
 * Test Script: Create Test Pass & Notify
 * Usage: php create_test_pass_and_notify.php [email]
 */

// Determine script directory and root directory
$script_dir = __DIR__;
$root_dir = dirname($script_dir);

// Correct path: db_connect is in root
require_once $root_dir . '/db_connect.php';
require_once $root_dir . '/includes/functions.php';

// 1. Parameters
$start_time = microtime(true);
$test_email = isset($argv[1]) ? $argv[1] : 'psphadtare2611@gmail.com'; // Default from params
$days_from_now = 1;

echo "--- Starting Test Run ---\n";
echo "Target Email: $test_email\n";
echo "Expiry Days: $days_from_now\n";

// 2. Ensure User Exists
$user_sql = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("s", $test_email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $user_id = $res->fetch_assoc()['id'];
    echo "User found (ID: $user_id)\n";
} else {
    // Create temp user
    $username = 'test_' . time();
    $password = password_hash('password', PASSWORD_DEFAULT);
    $full_name = 'Test User';
    $phone = '9000000000';
    
    $ins_sql = "INSERT INTO users (username, email, full_name, phone, password, is_active) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $conn->prepare($ins_sql);
    $stmt->bind_param("sssss", $username, $test_email, $full_name, $phone, $password);
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        echo "Created new test user (ID: $user_id)\n";
    } else {
        die("Failed to create user: " . $conn->error . "\n");
    }
}

// 3. Create Active Pass expiring in $days_from_now
// First get a pass type
$pt_sql = "SELECT id FROM pass_types LIMIT 1";
$pt_res = $conn->query($pt_sql);
if ($pt_res->num_rows == 0) {
    die("No pass types found. Please run seed script.\n");
}
$pass_type_id = $pt_res->fetch_assoc()['id'];

// Create pass manually to control dates
$pass_number = 'TEST' . time();
$qr_code = 'test_qr_code';
$valid_from = date('Y-m-d');
$valid_until = date('Y-m-d', strtotime("+$days_from_now days"));
$routes = '["all"]';

$pass_ins = "INSERT INTO user_passes (user_id, pass_type_id, pass_number, qr_code, valid_from, valid_until, routes, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
$stmt = $conn->prepare($pass_ins);
$stmt->bind_param("iisssss", $user_id, $pass_type_id, $pass_number, $qr_code, $valid_from, $valid_until, $routes);

if ($stmt->execute()) {
    $pass_id = $conn->insert_id;
    echo "Created test pass (ID: $pass_id) expiring on $valid_until\n";
} else {
    die("Failed to create pass: " . $conn->error . "\n");
}

// 4. Trigger Notification Routine
echo "\n--- Triggering Notification Job ---\n";

// Override environment variable to match our test day
putenv("REMINDER_DEFAULT_DAYS=$days_from_now");

// Override DB setting temporarily
$get_setting = "SELECT setting_value FROM system_settings WHERE setting_key = 'notification_days_before_expiry'";
$orig_setting = $conn->query($get_setting)->fetch_assoc()['setting_value'];

echo "Original DB Setting: $orig_setting\n";
echo "Updating DB Setting to: $days_from_now\n";

$conn->query("UPDATE system_settings SET setting_value = '$days_from_now' WHERE setting_key = 'notification_days_before_expiry'");

ob_start(); // Capture output
include __DIR__ . '/send_pass_reminders.php';
$output = ob_get_clean();

echo $output;

// Revert DB
echo "Reverting DB Setting to: $orig_setting\n";
$conn->query("UPDATE system_settings SET setting_value = '$orig_setting' WHERE setting_key = 'notification_days_before_expiry'");

// 5. Verify Log
$log_sql = "SELECT * FROM email_reminders_log WHERE pass_id = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($log_sql);
$stmt->bind_param("i", $pass_id);
$stmt->execute();
$log_res = $stmt->get_result();

if ($log_res->num_rows > 0) {
    $log = $log_res->fetch_assoc();
    echo "\n--- Verification Results ---\n";
    echo "Log Found: ID {$log['id']}\n";
    echo "Status: {$log['status']}\n";
    echo "Subject: {$log['subject']}\n";
    if ($log['status'] == 'sent') {
        echo "SUCCESS: Email marked as sent.\n";
    } else {
        echo "FAILURE: Email marked as failed. Error: {$log['error_message']}\n";
    }
} else {
    echo "\nFAILURE: No log entry found for pass $pass_id.\n";
}

echo "Done.\n";
?>
