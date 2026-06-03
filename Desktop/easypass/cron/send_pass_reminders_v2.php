<?php
/**
 * Cron Job: Send Pass Expiry Reminders
 * Run this script daily (e.g., at 8:00 AM)
 */

// Determine script directory and root directory
$script_dir = __DIR__;
$root_dir = dirname($script_dir);

// Include necessary files (Fixed path: db_connect.php is in root)
require_once $root_dir . '/db_connect.php';
require_once $root_dir . '/includes/functions.php';
require_once $root_dir . '/includes/email_notifications.php'; // For createPassExpiryEmail template

// Set default timezone
date_default_timezone_set('Asia/Kolkata'); // Adjust as per requirement

// Logging function
function log_reminder_v2($message) {
    global $root_dir;
    $log_file = $root_dir . '/logs/email_reminder.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    echo "[$timestamp] $message<br>" . PHP_EOL;
}

log_reminder_v2("Starting pass reminder cron job (v2).");

// 1. Get configuration
$days_before = 7; // Default
$from_env = getenv('REMINDER_DEFAULT_DAYS');
if ($from_env !== false) {
    $days_before = (int)$from_env;
}

// Check DB settings (overrides env)
$settings_sql = "SELECT setting_value FROM system_settings WHERE setting_key = 'notification_days_before_expiry'";
$res = $conn->query($settings_sql);
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $days_before = (int)$row['setting_value'];
}

log_reminder_v2("Configuration: Sending reminders for passes expiring in $days_before days.");

// 2. Select expiring passes
$sql = "SELECT up.id as pass_id, up.user_id, up.pass_number, up.valid_until, 
               u.email, u.full_name, pt.name as pass_type_name
        FROM user_passes up
        JOIN users u ON up.user_id = u.id
        JOIN pass_types pt ON up.pass_type_id = pt.id
        WHERE up.status = 'active'
          AND up.valid_until = DATE_ADD(CURDATE(), INTERVAL ? DAY)
          AND u.is_active = 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    log_reminder_v2("Error preparing query: " . $conn->error);
    exit(1);
}

$stmt->bind_param("i", $days_before);
$stmt->execute();
$result = $stmt->get_result();

$count = 0;
while ($pass = $result->fetch_assoc()) {
    $to = $pass['email'];
    $subject = "Your {$pass['pass_type_name']} expires in {$days_before} days";
    
    // Generate body
    $html_body = createPassExpiryEmail($pass, $days_before);
    
    // Attempt to send
    $sent = sendEmail($to, $subject, $html_body);
    
    // Log status to DB
    $status = $sent ? 'sent' : 'failed';
    $error = $sent ? null : 'Failed to send via sendEmail()';
    
    $log_sql = "INSERT INTO email_reminders_log (user_id, pass_id, to_email, subject, status, error_message) VALUES (?, ?, ?, ?, ?, ?)";
    $log_stmt = $conn->prepare($log_sql);
    $log_stmt->bind_param("iissss", $pass['user_id'], $pass['pass_id'], $to, $subject, $status, $error);
    $log_stmt->execute();
    
    if ($sent) {
        log_reminder_v2("Sent reminder to {$to} (Pass ID: {$pass['pass_id']})");
        $count++;
    } else {
        log_reminder_v2("Failed to send to {$to} (Pass ID: {$pass['pass_id']})");
    }
}

log_reminder_v2("Job finished. Sent $count reminders.");
?>
