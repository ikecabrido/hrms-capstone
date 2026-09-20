<?php
/**
 * Quick SMTP test for the HRMS mailer.
 *
 * Visit: http://localhost/hrms-capstone/test_email.php
 */

require_once __DIR__ . '/lib/vendor/autoload.php';
require_once __DIR__ . '/lib/config/email_config.php';

use App\Services\EmailService;
use App\Services\EmailTemplate;

$to = getenv('TEST_EMAIL_TO') ?: 'recipient@example.com';
$subject = 'HRMS Email Test';
$bodyText = 'This is a test email sent from the HRMS PHPMailer integration.';
$recipientName = 'Test Recipient';

$mailer = EmailService::getInstance();

if ($mailer->isLogMode()) {
    echo 'Mailer is in log/dev mode. Email not actually sent.';
    exit;
}

$html = EmailTemplate::buildHtml($subject, $bodyText, $recipientName, 'Bestlink College of the Philippines', '', '', true);
$altBody = EmailTemplate::buildText($subject, $bodyText, $recipientName);

$mail = $mailer->getMail();
EmailTemplate::embedLogo($mail);
EmailTemplate::embedSignatory($mail);

$sent = $mailer->send(
    ['email' => $to, 'name' => $recipientName],
    $subject,
    $html,
    $altBody
);

if ($sent) {
    echo 'Email sent successfully to ' . htmlspecialchars($to);
} else {
    echo 'Email failed to send. Check error_log for PHPMailer details.';
}
