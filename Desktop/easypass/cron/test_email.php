<?php
require_once __DIR__ . '/../includes/functions.php';

// Use a default or override with CLI argument
$to = isset($argv[1]) ? $argv[1] : 'test@example.com';
$subject = 'Test Email – PHPMailer Working';
$body = '<h3>This is a test email from EasyPass using SMTP.</h3><p>If you see this, PHPMailer is correctly integrated.</p>';

echo "Attempting to send email to: $to\n";

if (sendEmail($to, $subject, $body)) {
    echo "SUCCESS: Email sent!\n";
    echo "Check logs/email_debug.log for details.\n";
} else {
    echo "ERROR: Could not send email.\n";
    echo "Check logs/email_debug.log for error details.\n";
}
