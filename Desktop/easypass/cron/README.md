# Email Notifications & Cron Jobs

This directory contains scripts for handling automated email notifications for pass expirations.

## Automation Setup (Cron)

To automatically send reminder emails, set up a daily cron job.

### Linux/Mac (Crontab)
Run `crontab -e` and add:

```bash
# Run daily at 8:00 AM
0 8 * * * /usr/bin/php /var/www/html/easypass/cron/send_pass_reminders.php >> /var/www/html/easypass/logs/cron_reminder.log 2>&1
```
*Adjust the paths (`/usr/bin/php`, `/var/www/html/...`) to match your server environment.*

### Windows (Task Scheduler)
Create a Basic Task:
- **Trigger**: Daily at 8:00 AM
- **Action**: Start a Program
- **Program/script**: `C:\xampp\php\php.exe`
- **Arguments**: `C:\xampp\htdocs\easypass\cron\send_pass_reminders.php`
- **Start in**: `C:\xampp\htdocs\easypass\cron\`

## Configuration

1.  **Environment Variables**:
    Copy `.env.example` in the project root to `.env` (or set system environment variables).
    ```ini
    MAILER=smtp
    SMTP_HOST=smtp.gmail.com
    SMTP_PORT=587
    SMTP_USER=your_email@gmail.com
    SMTP_PASS=your_app_password
    ```

2.  **Dependencies**:
    Ensure PHPMailer is installed via Composer:
    ```bash
    composer require phpmailer/phpmailer
    ```

## Testing

To verify the setup:

1.  **Test Script**:
    Run the test script to create a dummy user/pass and trigger a notification attempt immediately.
    ```bash
    php cron/create_test_pass_and_notify.php [optional_email_address]
    ```

2.  **Admin Panel**:
    - Log in with an admin account.
    - Go to `/easypass/admin/reminders.php`.
    - Use the "Test SMTP Connection" form.
    - Check `/easypass/admin/logs.php` for delivery status.
