<?php
require_once __DIR__ . '/includes/email_helper.php';

$sent = sendSMTP(
    "psphadtare2611@gmail.com",
    "Paras",
    "Test Email from EasyPass",
    "<h2>This is a test email</h2><p>If you received this, SMTP works!</p>"
);

echo $sent ? "Email Sent" : "Failed";
