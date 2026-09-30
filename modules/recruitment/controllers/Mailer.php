<?php

namespace Controllers;

$root_path = $_SERVER['DOCUMENT_ROOT'] . '/hrms-capstone-master';

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{

    private $mail;
    private $debug = true;
    public $last_error = '';

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        try {
            // SMTP Configuration
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.gmail.com';
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = 'bcp.hr.recruitment@gmail.com';
            $this->mail->Password   = 'ymbwcccqxlaqisvq';

            // Use SSL on port 465
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->mail->Port       = 465;

            // SMTP Options to bypass SSL verification
            $this->mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Timeout settings
            $this->mail->Timeout = 30;

            // Enable debugging
            if ($this->debug) {
                $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $this->mail->Debugoutput = function ($str, $level) {
                    $this->logToFile("SMTP Debug: $str");
                };
            }

            // Sender info
            $this->mail->setFrom('bcp.hr.recruitment@gmail.com', 'HR Team');
            $this->mail->addReplyTo('bcp.hr.recruitment@gmail.com', 'HR Team');

            // Default charset
            $this->mail->CharSet = 'UTF-8';

            $this->logToFile("✅ Mailer initialized successfully");
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            $this->logToFile("❌ Mailer Initialization Error: " . $e->getMessage());
            throw new Exception("Failed to initialize mailer: " . $e->getMessage());
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

    /**
     * Send interview invitation email
     * 
     * @param string $to Recipient email
     * @param string $name Recipient name
     * @param array $data Interview details (date, time, type, mode, link, interviewer)
     * @return bool
     */
    public function sendInterviewEmail($to, $name, $data)
    {
        $this->logToFile("📧 Preparing to send email to: $to");
        $this->logToFile("📦 Data received: " . print_r($data, true));

        try {
            // Validate email
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $this->last_error = "Invalid email: $to";
                $this->logToFile("❌ " . $this->last_error);
                throw new Exception($this->last_error);
            }

            // Validate required data
            $required_fields = ['date', 'time', 'mode'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    $this->logToFile("⚠️ Warning: $field is not set, using default value");
                }
            }

            // Clear previous recipients
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();

            // Add recipient
            $this->mail->addAddress($to, $name);
            $this->logToFile("✅ Recipient added: $name <$to>");

            // Email content
            $this->mail->isHTML(true);
            $this->mail->Subject = "Interview Invitation - " . ($data['type'] ?? 'Interview Schedule');

            // Build HTML body
            $this->mail->Body = $this->buildInterviewTemplate($name, $data);

            // Plain text alternative
            $this->mail->AltBody = strip_tags(str_replace(['<br>', '</p>', '<li>', '</li>'], "\n", $this->mail->Body));

            $this->logToFile("📨 Attempting to send email...");

            // Send email
            $result = $this->mail->send();

            $this->logToFile("✅ Email sent successfully to: $to");
            return true;
        } catch (Exception $e) {
            $this->last_error = $this->mail->ErrorInfo;
            $this->logToFile("❌ Failed: " . $this->last_error);
            $this->logToFile("Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build HTML email template
     */
    private function buildInterviewTemplate($name, $data)
    {
        // Extract data with defaults
        $date = !empty($data['date']) ? $data['date'] : 'To be confirmed';
        $time = !empty($data['time']) ? $data['time'] : 'To be confirmed';
        $type = !empty($data['type']) ? $data['type'] : 'Interview';
        $mode = !empty($data['mode']) ? $data['mode'] : 'To be confirmed';
        $link = !empty($data['link']) ? $data['link'] : '';
        $interviewer = !empty($data['interviewer']) ? $data['interviewer'] : 'HR Team';

        // Format mode display
        $modeDisplay = ucfirst(strtolower($mode));
        if ($modeDisplay == 'Online' || $modeDisplay == 'Virtual') {
            $modeDisplay = '💻 Online (Virtual)';
        } elseif ($modeDisplay == 'In-person' || $modeDisplay == 'Face to face') {
            $modeDisplay = '🏢 In-person (Face to face)';
        }

        // Format link display
        $linkDisplay = '';
        if (!empty($link)) {
            if (filter_var($link, FILTER_VALIDATE_URL)) {
                $linkDisplay = "<a href='{$link}' style='color: #007bff; text-decoration: none;' target='_blank'>🔗 Click here to join the meeting</a>";
            } else {
                $linkDisplay = "Location: " . htmlspecialchars($link);
            }
        } else {
            if (strtolower($mode) == 'online' || strtolower($mode) == 'virtual') {
                $linkDisplay = "Meeting link will be sent closer to the interview date";
            } else {
                $linkDisplay = "Location details will be provided separately";
            }
        }

        return "
    <!DOCTYPE html>
<html>
<head>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <style>
        /* Base Styles */
        body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: 'Times New Roman', Times, serif; -webkit-text-size-adjust: 100%; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f4f4f4; padding-bottom: 40px; }
        .main { background-color: #ffffff; width: 100%; max-width: 700px; margin: 0 auto; border-bottom: 4px solid #1a2a44; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        
        /* Header Styling */
        .header-top { background-color: #1a2a44; padding: 25px 40px; color: #ffffff; }
        .logo-placeholder { vertical-align: middle; max-width: 60px; height: auto; }
        .header-text { display: inline-block; vertical-align: middle; padding-left: 15px; border-left: 1px solid #ffffff4d; margin-left: 15px; }
        .college-name { font-size: 18px; font-weight: bold; letter-spacing: 1px; color: #ffffff; text-transform: uppercase; margin: 0; }
        .dept-name { font-size: 11px; color: #d4af37; font-style: italic; margin: 0; }
        
        /* Letter Info Bar */
        .info-bar { padding: 10px 40px; font-size: 11px; color: #666; border-bottom: 1px solid #eee; background: #fafafa; }
        
        /* Content Body */
        .content { padding: 40px 60px; color: #333; line-height: 1.6; }
        .subject-line { font-family: Arial, sans-serif; color: #d4af37; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .interview-title { font-size: 22px; color: #1a2a44; margin-top: 0; margin-bottom: 30px; font-weight: normal; border-bottom: 1px solid #d4af37; padding-bottom: 10px; }
        
        .candidate-info { margin-bottom: 25px; }
        .candidate-name { font-size: 18px; font-weight: bold; color: #1a2a44; margin-bottom: 2px; }
        .candidate-dept { font-size: 14px; color: #777; font-style: italic; }

        /* Table Styling */
        .details-table { width: 100%; border: 1px solid #d4af37; border-collapse: collapse; margin: 25px 0; }
        .details-header { background-color: #fdfaf0; font-family: Arial, sans-serif; font-size: 10px; letter-spacing: 1px; color: #d4af37; padding: 8px 15px; text-transform: uppercase; border-bottom: 1px solid #d4af37; }
        .details-table td { padding: 12px 15px; border: 1px solid #eee; font-size: 14px; }
        .label-cell { background-color: #fafafa; color: #555; width: 30%; font-weight: bold; text-transform: uppercase; font-size: 11px; }
        
        /* Sections */
        .prep-header { font-family: Arial, sans-serif; font-size: 11px; font-weight: bold; color: #d4af37; text-transform: uppercase; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 30px; }
        .prep-list { padding-left: 20px; list-style-type: none; }
        .prep-list li { margin-bottom: 10px; font-size: 13px; position: relative; }
        .prep-list li:before { content: '▪'; color: #d4af37; position: absolute; left: -15px; }

        .notice-box { background-color: #fdfaf0; border-left: 4px solid #d4af37; padding: 20px; margin-top: 30px; font-size: 13px; }
        
        .footer { background-color: #1a2a44; color: #ffffff; padding: 20px 40px; font-size: 10px; }

        /* --- MOBILE ADJUSTMENTS --- */
        @media only screen and (max-width: 600px) {
            .header-top { padding: 25px 20px !important; text-align: center !important; }
            .header-text { 
                display: block !important; 
                border-left: none !important; 
                margin-left: 0 !important; 
                padding-left: 0 !important; 
                margin-top: 15px !important; 
            }
            .logo-placeholder { width: 70px !important; }
            .college-name { font-size: 16px !important; }
            .content { padding: 30px 20px !important; }
            .interview-title { font-size: 18px !important; }
            .info-bar { padding: 10px 20px !important; }
            .label-cell { width: 40% !important; font-size: 10px !important; }
            .footer { padding: 20px !important; text-align: center !important; }
            .footer td { display: block !important; width: 100% !important; text-align: center !important; }
        }
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
                        <td>REF: HR-INV-" . date('Ymd') . "</td>
                        <td align='right'>" . date('F d, Y') . "</td>
                    </tr>
                </table>
            </div>

            <div class='content'>
                <div class='subject-line'>Subject</div>
                <h1 class='interview-title'>Invitation for a " . htmlspecialchars($type) . "</h1>

                <div class='candidate-info'>
                    <div class='candidate-name'>" . htmlspecialchars($name) . "</div>
                    <div class='candidate-dept'>Applicant — Personnel Recruitment</div>
                </div>

                <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
                
We are pleased to inform you that, after a careful review of your application and credentials, the Human Resources Department of <strong>Bestlink College of the Philippines</strong> has selected you to proceed to the next stage of our selection process.</p>

                <table class='details-table'>
                    <tr>
                        <td colspan='2' class='details-header'>Interview Details</td>
                    </tr>
                    <tr>
                        <td class='label-cell'>Date</td>
                        <td><strong>" . htmlspecialchars($date) . "</strong></td>
                    </tr>
                    <tr>
                        <td class='label-cell'>Time</td>
                        <td>" . htmlspecialchars($time) . "</td>
                    </tr>
                    <tr>
                    <td class='label-cell'>Interview Type</td>
                        <td>" . htmlspecialchars($type) . "</td>
                    </tr>
                    <tr>
                        <td class='label-cell'>Mode / Location</td>
                        <td>" . $modeDisplay . "</td>
                    </tr>
                    <tr>
                        <td class='label-cell'>Meeting Link</td>
                        <td style='color: #d4af37; font-weight: bold; word-break: break-all;'>" . $linkDisplay . "</td>
                    </tr>
                    <tr>
                    <td class='label-cell'>Interviewer</td>
                        <td>" . htmlspecialchars($interviewer) . "</td>
                    </tr>
                </table>

                <p>Kindly confirm your attendance by replying to this correspondence no later than 24 hours before the scheduled time.</p>

                <div class='prep-header'>Preparation</div>
                <ul class='prep-list'>
                    <li>Please log in or arrive <strong>10 to 15 minutes</strong> before the scheduled start time.</li>
                    <li>Ensure a <strong>stable internet connection</strong> and a quiet, well-lit environment.</li>
                    <li>Prepare a digital or physical copy of your <strong>updated resume and portfolio</strong>.</li>
                    <li>Have a valid <strong>government-issued identification card</strong> ready for verification.</li>                
                </ul>

                <div class='notice-box'>
                    <strong>Important Notice:</strong> Should you need to reschedule or if you have any questions, please contact the HR Department at <strong>bcp.hr.recruitment@gmail.com</strong>. We kindly request all communications be made at least two (2) working days before the date.                
                </div>

                <div class='signature' style='margin-top: 30px;'>
                    <p>Respectfully yours,</p>
                    <div class='sig-name'>" . htmlspecialchars($interviewer) . "</div>
                    <div class='sig-title'>Director, Human Resources Department</div>
                    <div style='font-size: 11px; color: #999;'>Bestlink College of the Philippines</div>
                </div>
            </div>

            <div class='footer'>
                <table width='100%'>
                    <tr>
                        <td>BCP · HR SYSTEM</td>
             <td align='right' style='color: #aaa;'>
              System-generated notification. Please do not reply to this address.<br>
              &copy; " . date('Y') . " Bestlink College of the Philippines. All rights reserved.
                </td>
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
