<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * ارسال ایمیل از طریق SMTP با استفاده از PHPMailer
 */
function send_system_email($toEmail, $subject, $htmlBody, $textBody = '', $attachments = [])
{
    $settings = db_read_settings();

    $host = $settings['smtp_host'] ?? 'smtp.gmail.com';
    $port = (int)($settings['smtp_port'] ?? 587);
    $username = $settings['smtp_username'] ?? '';
    $password = $settings['smtp_password'] ?? '';
    $fromEmail = $settings['smtp_from_email'] ?? 'me@irankalanews.ir';
    $fromName = $settings['smtp_from_name'] ?? 'ایران کالا نیوز';
    $encryption = $settings['smtp_encryption'] ?? 'tls';

    if (empty($username) || empty($password)) {
        error_log('SMTP credentials not configured in settings.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings (matching your requested structure)
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->SMTPSecure = $encryption;
        $mail->Port       = $port;
        $mail->Timeout    = 5;
        $mail->SMTPOptions = [
            'socket' => [
                'timeout' => 5
            ]
        ];
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        if (!empty($textBody)) {
            $mail->AltBody = $textBody;
        }

        // Attachments
        foreach ($attachments as $attachment) {
            if (file_exists($attachment['path'])) {
                $mail->addAttachment($attachment['path'], $attachment['name'] ?? basename($attachment['path']));
            }
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}
