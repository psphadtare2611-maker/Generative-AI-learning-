-- Migration: Create Email Reminders Log Table
-- Date: 2025-12-06

CREATE TABLE IF NOT EXISTS email_reminders_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pass_id INT NOT NULL,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    status ENUM('sent', 'failed') NOT NULL,
    error_message TEXT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (sent_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure default setting exists in system_settings
-- We use INSERT IGNORE to skip if the key already exists (assuming 'setting_key' is UNIQUE)
INSERT IGNORE INTO system_settings (setting_key, setting_value, description) 
VALUES ('notification_days_before_expiry', '7', 'Number of days before pass expiry to send notification');
