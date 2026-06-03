<?php
// includes/mail_config.php
// Reads config from environment variables (preferred) with sensible defaults.
// Do NOT store real secrets in source control — use a .env file or server env vars.

return [
    'smtp_host'   => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'smtp_port'   => (int)(getenv('SMTP_PORT') ?: 587),
    'smtp_secure' => getenv('SMTP_SECURE') ?: 'tls',   // 'tls' or 'ssl' or ''
    'smtp_user'   => getenv('SMTP_USER') ?: 'psphadtare2611@gmail.com',       // SMTP username (email)
    'smtp_pass'   => getenv('SMTP_PASS') ?: 'mjogiyhtosuetrkk',       // SMTP password / app password
    'from_email'  => getenv('FROM_EMAIL') ?: 'psphadtare2611@gmail.com',
    'from_name'   => getenv('FROM_NAME') ?: 'EasyPass',
    'debug'       => (bool)(getenv('MAIL_DEBUG') ?: false),
    'log_file'    => __DIR__ . '/../logs/email_debug.log'
];
