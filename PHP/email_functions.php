<?php
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if (class_exists('Dotenv\\Dotenv')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
}

/**
 * Send a low-stock notification email using SMTP credentials.
 *
 * @param string $productName
 * @param int    $stockQty
 * @return void
 */
function sendLowStockEmail(string $productName, int $stockQty): void
{
    $host = $_ENV['BREVO_SMTP_HOST'] ?? '';
    $port = (int)($_ENV['BREVO_SMTP_PORT'] ?? 587);
    $username = $_ENV['BREVO_SMTP_USERNAME'] ?? '';
    $password = $_ENV['BREVO_SMTP_PASSWORD'] ?? '';
    $from = $_ENV['BREVO_FROM_EMAIL'] ?? '';
    $fromName = $_ENV['BREVO_FROM_NAME'] ?? 'Stock Bot';

    if (!$host || !$from) {
        error_log('SMTP configuration incomplete.');
        return;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->SMTPAuth = !empty($username);
        if ($mail->SMTPAuth) {
            $mail->Username = $username;
            $mail->Password = $password;
        }
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->setFrom($from, $fromName);
        $mail->addAddress($from);
        $mail->Subject = 'Low Stock Alert: ' . $productName;
        $mail->Body = "Stock for {$productName} is low. Current level: {$stockQty}.";
        $mail->send();
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
    }
}
?>
