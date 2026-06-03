<?php
/**
 * Automated Notification Cron Job
 * This script should be run daily to send pass expiry notifications
 * 
 * To set up the cron job, add this line to your crontab:
 * 0 9 * * * /usr/bin/php /path/to/your/project/cron/notification_cron.php
 */

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Include required files
require_once '../db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/email_notifications.php';

// Log cron job execution
$log_file = '../logs/cron.log';
$log_dir = dirname($log_file);

// Create logs directory if it doesn't exist
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function logCron($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);
}

logCron("Starting notification cron job");

try {
    // Send pass expiry notifications
    $notifications_sent = sendPassExpiryNotifications();
    logCron("Sent {$notifications_sent} pass expiry notifications");
    
    // Process pending notifications
    $processed = processPendingNotifications();
    logCron("Processed {$processed} pending notifications");
    
    // Update expired passes
    $expired_sql = "UPDATE user_passes SET status = 'expired' 
                    WHERE status = 'active' AND valid_until < CURDATE()";
    $conn->query($expired_sql);
    $expired_count = $conn->affected_rows;
    logCron("Updated {$expired_count} expired passes");
    
    // Clean up old notifications (older than 30 days)
    $cleanup_sql = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $conn->query($cleanup_sql);
    $cleaned_count = $conn->affected_rows;
    logCron("Cleaned up {$cleaned_count} old notifications");
    
    logCron("Notification cron job completed successfully");
    
} catch (Exception $e) {
    logCron("Error in notification cron job: " . $e->getMessage());
    error_log("Cron job error: " . $e->getMessage());
}

// Close database connection
$conn->close();
?>
