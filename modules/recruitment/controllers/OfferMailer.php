<?php

namespace Controllers;

$root_path = $_SERVER['DOCUMENT_ROOT'] . '/hrms-capstone-master';

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class OfferMailer
{
    private $mail;
    private $debug = true;
    public $last_error = '';

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        try {
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.gmail.com';
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = 'bcp.hr.recruitment@gmail.com';
            $this->mail->Password   = 'ymbwcccqxlaqisvq';
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->mail->Port       = 465;

            $this->mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $this->mail->Timeout = 30;

            if ($this->debug) {
                $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $this->mail->Debugoutput = function ($str, $level) {
                    $this->logToFile("SMTP Debug: $str");
                };
            }

            $this->mail->setFrom('bcp.hr.recruitment@gmail.com', 'HR Team');
            $this->mail->addReplyTo('bcp.hr.recruitment@gmail.com', 'HR Team');
            $this->mail->CharSet = 'UTF-8';

            $this->logToFile("✅ OfferMailer initialized successfully");
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            $this->logToFile("❌ OfferMailer Initialization Error: " . $e->getMessage());
            throw new Exception("Failed to initialize OfferMailer: " . $e->getMessage());
        }
    }

    private function logToFile($message)
    {
        $log_file = __DIR__ . '/email_debug.log';

        $timestamp = date('Y-m-d H:i:s');

        file_put_contents(
            $log_file,
            "[$timestamp] [Mailer] $message" . PHP_EOL,
            FILE_APPEND
        );
    }

    public function sendOfferEmail($to, $name, $data)
    {
        try {
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $this->last_error = "Invalid email: $to";
                return false;
            }

            $this->mail->clearAddresses();
            $this->mail->clearAttachments();

            $this->mail->addAddress($to, $name);
            $this->mail->isHTML(true);
            $this->mail->Subject = "Job Offer from Bestlink College of the Philippines";

            $this->mail->Body = $this->buildOfferTemplate($name, $data);
            $this->mail->AltBody = strip_tags($this->mail->Body);

            $this->logToFile("📨 Attempting to send job offer email to: $to");

            return $this->mail->send();
        } catch (\Exception $e) {
            $this->last_error = $this->mail->ErrorInfo;
            $this->logToFile("❌ Failed to send job offer: " . $this->last_error);
            return false;
        }
    }

    private function buildOfferTemplate($name, $data)
    {
        $department      = htmlspecialchars($data['department'] ?? '');
        $position       = htmlspecialchars($data['position'] ?? '');
        $baseJob        = htmlspecialchars($data['base_job'] ?? '');
        $salary         = number_format($data['salary'] ?? 0, 2);
        $benefits       = nl2br(htmlspecialchars($data['benefits'] ?? ''));
        $additionalNote = nl2br(htmlspecialchars($data['additional_note'] ?? ''));
        $date           = $data['date'] ?? date('F d, Y');
        $hr_name        = htmlspecialchars($data['hr_name'] ?? 'HR Team');
        $hr_title       = htmlspecialchars($data['hr_title'] ?? 'Director, Human Resources Department');

        return "
<!DOCTYPE html>
<html>
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0'>
<style>
/* Styles same as before (omitted here for brevity) */
</style>
</head>
<body>
<div class='wrapper'>
<div class='main'>
    <div class='header-top'>
        <img src='https://bcp.edu.ph/images/logo300.png' alt='BCP Logo' width='60' class='logo-placeholder'>
        <div class='header-text'>
            <div class='college-name'>Bestlink College of the Philippines</div>
            <div class='dept-name'>Human Resources Department · OPM</div>
        </div>
    </div>

    <div class='info-bar'>
        <table width='100%'>
            <tr>
                <td>REF: HR-OFFER-" . date('Ymd') . "</td>
                <td align='right'>" . date('F d, Y') . "</td>
            </tr>
        </table>
    </div>

    <div class='content'>
        <div class='subject-line'>Subject</div>
        <h1 class='offer-title'>Job Offer for {$name}</h1>

        <p>Dear <strong>{$name}</strong>,</p>
        <p>We are pleased to offer you the position of <strong>{$position}</strong> ({$baseJob}) at <strong>Bestlink College of the Philippines</strong>.</p>

        <table class='details-table'>
            <tr>
                <td colspan='2' class='details-header'>Offer Details</td>
            </tr>
             <tr>
                <td class='label-cell'>Department</td>
                <td>{$department}</td>
            </tr>
            <tr>
                <td class='label-cell'>Position/Role</td>
                <td>{$position}</td>
            </tr>
            <tr>
                <td class='label-cell'>Base Job Title</td>
                <td>{$baseJob}</td>
            </tr>
            <tr>
                <td class='label-cell'>Salary</td>
                <td>₱ {$salary} / month</td>
            </tr>
            <tr>
                <td class='label-cell'>Benefit Package</td>
                <td>{$benefits}</td>
            </tr>
        </table>

        <p>{$additionalNote}</p>
        <p>Please reply to this email to confirm your acceptance of this offer by <strong>{$date}</strong>.</p>

        <div class='signature' style='margin-top:30px;'>
            <p>Respectfully yours,</p>
            <div class='sig-name'>{$hr_name}</div>
            <div class='sig-title'>{$hr_title}</div>
            <div style='font-size:11px; color:#999;'>Bestlink College of the Philippines</div>
        </div>
    </div>

    <div class='footer'>
        <table width='100%'>    
            <tr>
                <td>BCP · HR SYSTEM</td>
                <td align='right' style='color:#aaa;'>System-generated notification. Please do not reply.<br>&copy; " . date('Y') . " Bestlink College of the Philippines. All rights reserved.</td>
            </tr>
        </table>
    </div>
</div>
</div>
</body>
</html>
";
    }
}
