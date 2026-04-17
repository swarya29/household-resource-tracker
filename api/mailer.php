<?php
namespace PHPMailer\PHPMailer;

require_once __DIR__ . '/vendor/PHPMailer/Exception.php';
require_once __DIR__ . '/vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/vendor/PHPMailer/SMTP.php';

function load_env()
{
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath))
        return [];
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $env[trim($name)] = trim($value, " \t\n\r\0\x0B\"");
        }
    }
    return $env;
}

function send_alert_email($to_email, $user_name, $resource_type, $limit_value, $current_usage, $unit, $timestamp)
{
    if (empty($to_email) || strpos($to_email, '@') === false) {
        file_put_contents(__DIR__ . "/api_errors.log", "[" . date("Y-m-d H:i:s") . "] Alert skipped: No valid email for user $user_name\n", FILE_APPEND);
        return false;
    }

    $env = load_env();
    if (empty($env['SMTP_USER']) || $env['SMTP_USER'] === 'dummy@gmail.com') {
        file_put_contents(__DIR__ . "/api_errors.log", "[" . date("Y-m-d H:i:s") . "] Email skipped: SMTP vars not set in .env. Over limit by user $user_name for $resource_type.\n", FILE_APPEND);
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $env['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $env['SMTP_USER'];
        $mail->Password = $env['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $env['SMTP_PORT'];

        $mail->setFrom($env['SMTP_FROM_EMAIL'], $env['SMTP_FROM_NAME']);
        $mail->addAddress($to_email, $user_name);

        $mail->isHTML(true);
        $mail->Subject = 'EcoTracker - Resource Usage Alert: ' . ucfirst($resource_type);

        $body = "
            <div style='font-family: Arial, sans-serif; color: #333;'>
                <h2>Resource Usage Alert</h2>
                <p>Hello <b>$user_name</b>,</p>
                <p>You have exceeded your <strong>$resource_type</strong> usage limit.</p>
                <ul>
                    <li><b>Limit set:</b> $limit_value $unit</li>
                    <li><b>Current usage:</b> $current_usage $unit</li>
                    <li><b>Time recorded:</b> $timestamp</li>
                </ul>
                <p>Please log in to your EcoTracker Dashboard to review your usage and optimize consumption.</p>
                <p><br><small>This is an automated notification from Smart Resource Tracker.</small></p>
            </div>
        ";

        $mail->Body = $body;
        $mail->AltBody = "Hello $user_name, you have exceeded your $resource_type usage limit. Limit: $limit_value $unit, Current Usage: $current_usage $unit. Recorded at: $timestamp.";

        $mail->send();
        file_put_contents(__DIR__ . "/api_errors.log", "[" . date("Y-m-d H:i:s") . "] Alert sent via email to $to_email\n", FILE_APPEND);
        return true;
    } catch (Exception $e) {
        file_put_contents(__DIR__ . "/api_errors.log", "[" . date("Y-m-d H:i:s") . "] Mailer Error: {$mail->ErrorInfo}\n", FILE_APPEND);
        return false;
    }
}

function send_password_reset_email($to_email, $user_name, $reset_link)
{
    if (empty($to_email) || strpos($to_email, '@') === false) {
        return false;
    }

    $env = load_env();
    if (empty($env['SMTP_USER']) || $env['SMTP_USER'] === 'dummy@gmail.com') {
        file_put_contents(__DIR__ . "/api_errors.log", "[" . date("Y-m-d H:i:s") . "] Reset Email skipped (Log Only): Link is $reset_link\n", FILE_APPEND);
        return true; // Return true as we "handled" it for local dev
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $env['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $env['SMTP_USER'];
        $mail->Password = $env['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $env['SMTP_PORT'];

        $mail->setFrom($env['SMTP_FROM_EMAIL'], $env['SMTP_FROM_NAME']);
        $mail->addAddress($to_email, $user_name);

        $mail->isHTML(true);
        $mail->Subject = 'EcoTracker - Password Reset Request';

        $body = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #00f0ff; text-align: center;'>EcoTracker</h2>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <p>Hello <b>$user_name</b>,</p>
                <p>We received a request to reset your password. If you didn't make this request, you can safely ignore this email.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$reset_link' style='background: #00f0ff; color: #fff; padding: 12px 25px; text-decoration: none; font-weight: bold; border-radius: 5px; display: inline-block;'>Reset Your Password</a>
                </div>
                <p>Or copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #666; font-size: 0.9rem;'>$reset_link</p>
                <p>This link will expire in 1 hour.</p>
                <hr style='border: 0; border-top: 1px solid #eee; margin-top: 30px;'>
                <p style='font-size: 0.8rem; color: #999; text-align: center;'>EcoTracker Security Team</p>
            </div>
        ";

        $mail->Body = $body;
        $mail->AltBody = "Hello $user_name, reset your password by visiting this link: $reset_link. The link expires in 1 hour.";

        $mail->send();
        file_put_contents(__DIR__ . "/api_errors.log", "[" . date("Y-m-d H:i:s") . "] Reset email sent to $to_email\n", FILE_APPEND);
        return true;
    } catch (Exception $e) {
        file_put_contents(__DIR__ . "/api_errors.log", "[" . date("Y-m-d H:i:s") . "] Reset Mailer Error: {$mail->ErrorInfo}\n", FILE_APPEND);
        return false;
    }
}
?>