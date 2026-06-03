<?php
/**
 * email_notifications.php
 * Clean, robust notification/email sender for EasyPass
 *
 * - Compatible with current notifications table:
 *   (id, user_id, type, title, message, sent_via, is_read, created_at)
 *
 * - DOES NOT require extra columns (user_email, user_phone, sent_at)
 * - Marks notifications as processed by setting is_read = 1 after successful send
 *
 * Requirements:
 * - $conn (mysqli) available via db_connect.php
 * - sendEmail($to, $subject, $html) function exists (functions.php)
 * - createNotification($user_id, $type, $title, $message, $sent_via) exists (functions.php)
 */

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/email_helper.php';


// Simple logger that writes to includes/../logs/email_debug.log
function _en_log($msg) {
    $log = __DIR__ . '/../logs/email_debug.log';
    if (!is_dir(dirname($log))) {
        @mkdir(dirname($log), 0777, true);
    }
    file_put_contents($log, date('c') . ' ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
}

/**
 * Get system setting safely (fallback to default)
 */
function _en_get_setting($key, $default = null) {
    global $conn;
    if (!isset($conn)) return $default;
    $sql = "SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('s', $key);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $stmt->close();
                return $row['setting_value'];
            }
        }
        $stmt->close();
    }
    return $default;
}

/**
 * Send pass expiry notifications for passes expiring in $days_before days.
 * Default behaviour: checks system_settings.notification_days_before_expiry or 7.
 *
 * Returns array: ['sent_count' => int, 'days_before' => int]
 */
function sendPassExpiryNotifications() {
    global $conn;

    // Determine reminder window
    $days_before = (int)_en_get_setting('notification_days_before_expiry', 7);
    if ($days_before < 0) $days_before = 7;

    // Build query: select active passes that expire exactly in $days_before days
    // Note: using DATE_ADD(CURDATE(), INTERVAL ? DAY)
    $sql = "SELECT up.id AS pass_id, up.user_id, up.pass_number, up.valid_until, pt.name AS pass_type_name, u.email, u.full_name
            FROM user_passes up
            JOIN users u ON up.user_id = u.id
            LEFT JOIN pass_types pt ON up.pass_type_id = pt.id
            WHERE up.status = 'active'
              AND DATE(up.valid_until) = DATE_ADD(CURDATE(), INTERVAL ? DAY)
              AND u.is_active = 1";

    $sent = 0;
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $days_before);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($pass = $res->fetch_assoc()) {
                $to = $pass['email'] ?? null;
                if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    _en_log("Skipping expiry email for user_id={$pass['user_id']} pass_id={$pass['pass_id']} - invalid email");
                    continue;
                }

                // Compose email
                $subject = "Your {$pass['pass_type_name']} will expire in {$days_before} day(s)";
                $html = createPassExpiryEmail($pass, $days_before);

                // Send and log
                $ok = false;
                try {
                    $ok = sendEmail($to, $subject, $html);
                } catch (Exception $e) {
                    $ok = false;
                    _en_log("Exception sending expiry email to {$to}: " . $e->getMessage());
                }

                if ($ok) {
                    // Create notification record (in-app) using existing function
                    if (function_exists('createNotification')) {
                        createNotification(
                            $pass['user_id'],
                            'pass_expiry',
                            $subject,
                            "Your pass ({$pass['pass_number']}) expires on {$pass['valid_until']}",
                            'email'
                        );
                    }
                    $sent++;
                } else {
                    _en_log("Failed to send expiry email to {$to} for pass_id={$pass['pass_id']}");
                }
            }
        } else {
            _en_log("Failed to execute expiry query: " . $stmt->error);
        }
        $stmt->close();
    } else {
        _en_log("Failed to prepare expiry query: " . $conn->error);
    }

    return ['sent_count' => $sent, 'days_before' => $days_before];
}

/**
 * Create pass expiry email HTML (already in your file, re-used)
 * Keep function as-is — safe sanitization done in template creation.
 */
function createPassExpiryEmail($pass, $days_before) {
    $renewal_url = (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https') . '://' . $_SERVER['HTTP_HOST'] : 'https://easypass.com') . '/passes.php';
    $full_name = htmlspecialchars($pass['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
    $pass_type = htmlspecialchars($pass['pass_type_name'] ?? 'Bus Pass', ENT_QUOTES, 'UTF-8');
    $pass_number = htmlspecialchars($pass['pass_number'] ?? '', ENT_QUOTES, 'UTF-8');
    $valid_until = htmlspecialchars($pass['valid_until'] ?? '', ENT_QUOTES, 'UTF-8');
    $days = (int)$days_before;

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #1a73e8; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .button { display: inline-block; background-color: #1a73e8; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>EasyPass Notification</h1>
            </div>
            <div class='content'>
                <h2>Pass Expiry Reminder</h2>
                <p>Dear {$full_name},</p>
                <p>Your <strong>{$pass_type}</strong> (Pass #{$pass_number}) will expire in <strong>{$days} day(s)</strong> on <strong>{$valid_until}</strong>.</p>
                <p>To continue enjoying unlimited bus rides, please renew your pass before it expires.</p>
                <p style='text-align: center;'>
                    <a href='{$renewal_url}' class='button'>Renew Your Pass Now</a>
                </p>
                <p>If you have any questions or need assistance, please contact our support team.</p>
            </div>
            <div class='footer'>
                <p>Best regards,<br>EasyPass Team</p>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Create pass purchase confirmation email HTML
 * @param array $pass_details Pass details array
 * @return string HTML email content
 */
function createPassPurchaseConfirmationEmail(array $pass_details) {
    $dashboard_url = (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https') . '://' . $_SERVER['HTTP_HOST'] : 'https://easypass.com') . '/dashboard.php';
    
    $full_name = htmlspecialchars($pass_details['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
    $pass_type = htmlspecialchars($pass_details['pass_type_name'] ?? 'Bus Pass', ENT_QUOTES, 'UTF-8');
    $pass_number = htmlspecialchars($pass_details['pass_number'] ?? '', ENT_QUOTES, 'UTF-8');
    $valid_from = htmlspecialchars($pass_details['valid_from'] ?? '', ENT_QUOTES, 'UTF-8');
    $valid_until = htmlspecialchars($pass_details['valid_until'] ?? '', ENT_QUOTES, 'UTF-8');
    $amount = htmlspecialchars($pass_details['amount'] ?? '0.00', ENT_QUOTES, 'UTF-8');

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #1a73e8; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .pass-card { background: linear-gradient(135deg, #1a73e8 0%, #4285f4 100%); color: white; padding: 20px; border-radius: 10px; margin: 20px 0; }
            .pass-detail { margin: 10px 0; }
            .pass-detail strong { display: inline-block; width: 120px; }
            .button { display: inline-block; background-color: #1a73e8; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
            .success-icon { font-size: 48px; color: #34a853; text-align: center; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🚌 EasyPass</h1>
            </div>
            <div class='content'>
                <div class='success-icon'>✅</div>
                <h2 style='text-align: center; color: #1a73e8;'>Pass Purchase Successful!</h2>
                <p>Dear {$full_name},</p>
                <p>Thank you for your purchase! Your <strong>{$pass_type}</strong> has been successfully activated.</p>
                
                <div class='pass-card'>
                    <h3 style='margin-top: 0;'>Pass Details</h3>
                    <div class='pass-detail'><strong>Pass Number:</strong> {$pass_number}</div>
                    <div class='pass-detail'><strong>Pass Type:</strong> {$pass_type}</div>
                    <div class='pass-detail'><strong>Valid From:</strong> {$valid_from}</div>
                    <div class='pass-detail'><strong>Valid Until:</strong> {$valid_until}</div>
                    <div class='pass-detail'><strong>Amount Paid:</strong> ₹{$amount}</div>
                </div>
                
                <p>Your digital pass is now available in your dashboard. You can show this pass to the conductor when boarding the bus.</p>
                <p style='text-align: center;'>
                    <a href='{$dashboard_url}' class='button'>View Your Pass</a>
                </p>
                <p><strong>Important:</strong></p>
                <ul>
                    <li>Keep your pass number safe for future reference</li>
                    <li>You can access your pass anytime from the dashboard</li>
                    <li>You'll receive a reminder before your pass expires</li>
                </ul>
                <p>If you have any questions or need assistance, please contact our support team.</p>
            </div>
            <div class='footer'>
                <p>Best regards,<br>EasyPass Team</p>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Create payment confirmation email HTML
 * @param string $user_name User's name
 * @param array $payment_details Payment details array
 * @return string HTML email content
 */
function createPaymentConfirmationEmail($user_name, array $payment_details) {
    $dashboard_url = (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https') . '://' . $_SERVER['HTTP_HOST'] : 'https://easypass.com') . '/dashboard.php';
    
    $name = htmlspecialchars($user_name ?? 'User', ENT_QUOTES, 'UTF-8');
    $amount = htmlspecialchars($payment_details['amount'] ?? '0.00', ENT_QUOTES, 'UTF-8');
    $method = htmlspecialchars($payment_details['method'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
    $transaction_id = htmlspecialchars($payment_details['transaction_id'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
    $date = htmlspecialchars($payment_details['date'] ?? date('d M, Y H:i'), ENT_QUOTES, 'UTF-8');

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #1a73e8; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .payment-summary { background-color: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #34a853; }
            .summary-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
            .summary-row:last-child { border-bottom: none; font-weight: bold; font-size: 1.2em; }
            .button { display: inline-block; background-color: #1a73e8; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
            .success-icon { font-size: 48px; color: #34a853; text-align: center; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🚌 EasyPass</h1>
            </div>
            <div class='content'>
                <div class='success-icon'>✅</div>
                <h2 style='text-align: center; color: #1a73e8;'>Payment Successful!</h2>
                <p>Dear {$name},</p>
                <p>We have successfully received your payment. Here are the details:</p>
                
                <div class='payment-summary'>
                    <h3 style='margin-top: 0;'>Payment Summary</h3>
                    <div class='summary-row'>
                        <span>Transaction ID:</span>
                        <span>{$transaction_id}</span>
                    </div>
                    <div class='summary-row'>
                        <span>Payment Method:</span>
                        <span>{$method}</span>
                    </div>
                    <div class='summary-row'>
                        <span>Date & Time:</span>
                        <span>{$date}</span>
                    </div>
                    <div class='summary-row'>
                        <span>Amount Paid:</span>
                        <span>₹{$amount}</span>
                    </div>
                </div>
                
                <p>Your payment has been processed successfully and your bus pass is now active.</p>
                <p style='text-align: center;'>
                    <a href='{$dashboard_url}' class='button'>View Dashboard</a>
                </p>
                <p>If you have any questions about this transaction, please contact our support team.</p>
            </div>
            <div class='footer'>
                <p>Best regards,<br>EasyPass Team</p>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>";
}


/**
 * Send pass purchase confirmation email (wrapper)
 * Accepts array $pass_details with keys:
 *   user_id, email, full_name, pass_type_name, pass_number, valid_from, valid_until, amount
 */
function sendPassPurchaseConfirmationEmail(array $pass_details) {
    // Basic validation
    if (empty($pass_details['email']) || empty($pass_details['valid_until'])) {
        _en_log("sendPassPurchaseConfirmationEmail: missing email or valid_until");
        return false;
    }

    $to = $pass_details['email'];
    $subject = "Your {$pass_details['pass_type_name']} is active — Expires {$pass_details['valid_until']}";
    $html = createPassPurchaseConfirmationEmail($pass_details);

    $ok = false;
    try {
        $ok = sendEmail($to, $subject, $html);
    } catch (Exception $e) {
        _en_log("Exception in sendPassPurchaseConfirmationEmail: " . $e->getMessage());
        $ok = false;
    }

    if ($ok) {
        if (function_exists('createNotification')) {
            createNotification(
                $pass_details['user_id'] ?? 0,
                'pass_purchase',
                $subject,
                "Your pass ({$pass_details['pass_number']}) is active and expires on {$pass_details['valid_until']}.",
                'email'
            );
        }
    } else {
        _en_log("Failed to send purchase email to {$to}");
    }

    return $ok;
}

/**
 * Payment confirmation email wrapper
 */
function sendPaymentConfirmationEmail($user_email, $user_name, $payment_details) {
    if (empty($user_email)) {
        _en_log("sendPaymentConfirmationEmail: missing email");
        return false;
    }

    $subject = "Payment Confirmation - EasyPass";
    $html = createPaymentConfirmationEmail($user_name, $payment_details);

    $ok = false;
    try {
        $ok = sendEmail($user_email, $subject, $html);
    } catch (Exception $e) {
        _en_log("Exception in sendPaymentConfirmationEmail: " . $e->getMessage());
        $ok = false;
    }

    if ($ok && function_exists('createNotification')) {
        createNotification(
            0, // if you want to link to user id, pass it in $payment_details and change here
            'payment',
            $subject,
            "Payment of ₹" . ($payment_details['amount'] ?? '0.00') . " successful.",
            'email'
        );
    }

    return $ok;
}

/**
 * Process pending notifications (A — keep current schema)
 *
 * Behavior:
 *  - Fetch notifications where is_read = 0 (i.e., pending/unread).
 *  - For email notifications: look up user's email from users table and send.
 *  - After successful send, mark notification as is_read = 1 to avoid re-sending.
 *
 * NOTE: This design uses is_read as a 'processed' flag for outgoing notifications.
 *       If you later add dedicated columns (sent_at, processed) you can update logic.
 */
function processPendingNotifications($limit = 50) {
    global $conn;
    $processed = 0;

    $sql = "SELECT id, user_id, type, title, message, sent_via FROM notifications WHERE is_read = 0 ORDER BY created_at ASC LIMIT ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $limit);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($notif = $res->fetch_assoc()) {
                $sent = false;
                $user_id = (int)$notif['user_id'];

                // Fetch user's email & phone from users table
                $u_sql = "SELECT email, full_name, phone FROM users WHERE id = ? LIMIT 1";
                $u_stmt = $conn->prepare($u_sql);
                $u_stmt->bind_param('i', $user_id);
                $u_stmt->execute();
                $user_row = $u_stmt->get_result()->fetch_assoc();
                $u_stmt->close();

                $to_email = $user_row['email'] ?? null;
                $to_name = $user_row['full_name'] ?? '';

                switch ($notif['sent_via']) {
                    case 'email':
                        if ($to_email && filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
                            try {
                                $sent = sendEmail($to_email, $notif['title'], $notif['message']);
                            } catch (Exception $e) {
                                _en_log("processPendingNotifications: exception sending email to {$to_email}: " . $e->getMessage());
                                $sent = false;
                            }
                        } else {
                            _en_log("processPendingNotifications: no valid email for user_id={$user_id}");
                        }
                        break;

                    case 'sms':
                        // Placeholder: we don't have SMS provider integrated — fallback to logging
                        $sent = sendSMS($user_row['phone'] ?? '', $notif['message']);
                        break;

                    case 'whatsapp':
                        $sent = sendWhatsApp($user_row['phone'] ?? '', $notif['message']);
                        break;

                    default:
                        // unknown channel — log and mark as processed to avoid infinite loop
                        _en_log("processPendingNotifications: unknown sent_via='{$notif['sent_via']}' for notification id={$notif['id']}");
                        $sent = true;
                        break;
                }

                if ($sent) {
                    // Mark as processed by setting is_read = 1 (so we don't re-send)
                    $up_sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
                    $up_stmt = $conn->prepare($up_sql);
                    $up_stmt->bind_param('i', $notif['id']);
                    $up_stmt->execute();
                    $up_stmt->close();
                    $processed++;
                } else {
                    // keep it for retry later; optionally log
                    _en_log("processPendingNotifications: failed to send notification id={$notif['id']}");
                }
            }
        } else {
            _en_log("processPendingNotifications: execute failed - " . $stmt->error);
        }
        $stmt->close();
    } else {
        _en_log("processPendingNotifications: prepare failed - " . $conn->error);
    }

    return $processed;
}

/* ---------------------------
   Execution / Test Runner
   ---------------------------
   This block runs only when this file is executed directly (not when included).
   It demonstrates:
     1) sendPassExpiryNotifications
     2) sendPassPurchaseConfirmationEmail (sample)
     3) sendPaymentConfirmationEmail (sample)
     4) processPendingNotifications (attempt to send queued)
*/
/*if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    echo "<h2>--- Notification Runner (Simulated) ---</h2>";

    // 1) Expiry reminders
    $expiry = sendPassExpiryNotifications();
    echo "<h3>1. Expiry Reminder Check</h3>";
    echo "Notifications Sent (Expiry): " . intval($expiry['sent_count']) . "<br>";
    if (intval($expiry['sent_count']) === 0) {
        echo "No passes found expiring in the configured time frame ({$expiry['days_before']} days), OR an error occurred.<br>";
    }

    // 2) Pass purchase confirmation test
    echo "<h3>2. Pass Purchase Confirmation Test (NEW)</h3>";
    $pass_purchase_details = [
        'user_id' => 1,
        'email' => 'test@example.com', // change to a real test email for live test
        'full_name' => 'Test User',
        'pass_type_name' => 'Monthly Unlimited',
        'pass_number' => 'EP00000120251201',
        'valid_from' => date('Y-m-d'),
        'valid_until' => date('Y-m-d', strtotime('+30 days')),
        'amount' => '499.00',
    ];
    $ok = sendPassPurchaseConfirmationEmail($pass_purchase_details);
    echo $ok ? "<p style='color:green;'>✅ Pass Purchase Email sent (or attempted).</p>" : "<p style='color:red;'>❌ Failed to send Pass Purchase Email.</p>";

    // 3) Payment confirmation test
    echo "<h3>3. Payment Confirmation Test</h3>";
    $payment_details = [
        'amount' => '510.00',
        'method' => 'Credit Card (Visa)',
        'transaction_id' => 'TXN-987654321',
        'date' => date('Y-m-d H:i:s')
    ];
    $ok2 = sendPaymentConfirmationEmail('test@example.com', 'Test User', $payment_details);
    echo $ok2 ? "<p style='color:green;'>✅ Payment Email sent (or attempted).</p>" : "<p style='color:red;'>❌ Failed to send Payment Email.</p>";

    // 4) Process pending notifications (attempt to send queued notifications)
    echo "<h3>4. Process Pending Notifications</h3>";
    $processed = processPendingNotifications(50);
    echo "Processed notifications: {$processed}<br>";
    echo "<p>Check logs at <code>logs/email_debug.log</code> for details.</p>";
}

?>
*/