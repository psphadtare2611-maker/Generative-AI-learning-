<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendSMTP($to, $name, $subject, $html)
{
    $cfg = include __DIR__ . '/mail_config.php';
    $log = $cfg['log_file'];

    // Ensure log directory exists
    if (!is_dir(dirname($log))) {
        mkdir(dirname($log), 0777, true);
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP SETTINGS
        $mail->isSMTP();
        $mail->Host       = $cfg['smtp_host'];   // smtp.gmail.com
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['smtp_user'];   // your Gmail
        $mail->Password   = $cfg['smtp_pass'];   // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = (int)$cfg['smtp_port']; // 587
        $mail->isHTML(true);

        // Debugging
        if (!empty($cfg['debug'])) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) use ($log) {
                file_put_contents($log, date('c') . " [DEBUG] $str\n", FILE_APPEND);
            };
        }

        // SENDER DETAILS
        $mail->setFrom($cfg['from_email'], $cfg['from_name']);

        // RECEIVER DETAILS
        $mail->addAddress($to, $name ?: '');

        // MESSAGE CONTENT
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = strip_tags($html);

        // SEND EMAIL
        if (!$mail->send()) {
            file_put_contents($log, date('c') . " ERROR: " . $mail->ErrorInfo . "\n", FILE_APPEND);
            return false;
        }

        file_put_contents($log, date('c') . " SENT to {$to}\n", FILE_APPEND);
        return true;

    } catch (Exception $e) {
        file_put_contents($log, date('c') . " EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}
