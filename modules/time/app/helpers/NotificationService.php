<?php
/**
 * Notification Service
 * Handles SMS and Email delivery via Twilio and SendGrid
 * Configuration via environment variables or constants
 */

class NotificationService {
    
    // Configuration constants - set these via .env or directly
    const SMS_PROVIDER = 'twilio'; // 'twilio', 'aws', or 'local'
    const EMAIL_PROVIDER = 'sendgrid'; // 'sendgrid', 'smtp', or 'local'
    
    // Twilio Configuration
    const TWILIO_ACCOUNT_SID = ''; // Set via getenv('TWILIO_ACCOUNT_SID')
    const TWILIO_AUTH_TOKEN = ''; // Set via getenv('TWILIO_AUTH_TOKEN')
    const TWILIO_PHONE = ''; // Set via getenv('TWILIO_PHONE')
    
    // SendGrid Configuration
    const SENDGRID_API_KEY = ''; // Set via getenv('SENDGRID_API_KEY')
    const SENDGRID_FROM_EMAIL = ''; // Set via getenv('SENDGRID_FROM_EMAIL')
    
    // SMTP Configuration (fallback)
    const SMTP_HOST = 'localhost';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = '';
    const SMTP_PASSWORD = '';
    const SMTP_FROM_EMAIL = 'noreply@timeattendance.local';
    
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Send SMS notification
     * @param string $phone Phone number (E.164 format: +1234567890)
     * @param string $message Message content
     * @param string $type Type of notification (approval, reminder, etc.)
     * @return array ['success' => bool, 'message' => string, 'provider' => string]
     */
    public function sendSMS($phone, $message, $type = 'general') {
        $provider = $this->getSMSProvider();
        
        try {
            switch ($provider) {
                case 'twilio':
                    return $this->sendViaTwilio($phone, $message, $type);
                case 'aws':
                    return $this->sendViaAWS($phone, $message, $type);
                case 'local':
                default:
                    return $this->logLocalNotification($phone, $message, 'sms', $type);
            }
        } catch (Exception $e) {
            $this->logFailure($phone, $message, 'sms', $provider, $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'provider' => $provider];
        }
    }
    
    /**
     * Send Email notification
     * @param string $email Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string $type Type of notification
     * @param array $attachments Optional file attachments
     * @return array ['success' => bool, 'message' => string, 'provider' => string]
     */
    public function sendEmail($email, $subject, $body, $type = 'general', $attachments = []) {
        $provider = $this->getEmailProvider();
        
        try {
            switch ($provider) {
                case 'sendgrid':
                    return $this->sendViaSendGrid($email, $subject, $body, $type, $attachments);
                case 'smtp':
                    return $this->sendViaSMTP($email, $subject, $body, $type);
                case 'local':
                default:
                    return $this->logLocalNotification($email, $subject, 'email', $type);
            }
        } catch (Exception $e) {
            $this->logFailure($email, $subject, 'email', $provider, $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'provider' => $provider];
        }
    }
    
    /**
     * Send Twilio SMS
     */
    private function sendViaTwilio($phone, $message, $type) {
        $sid = getenv('TWILIO_ACCOUNT_SID') ?: self::TWILIO_ACCOUNT_SID;
        $token = getenv('TWILIO_AUTH_TOKEN') ?: self::TWILIO_AUTH_TOKEN;
        $from = getenv('TWILIO_PHONE') ?: self::TWILIO_PHONE;
        
        if (!$sid || !$token || !$from) {
            throw new Exception("Twilio credentials not configured");
        }
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'From' => $from,
            'To' => $phone,
            'Body' => $message
        ]));
        curl_setopt($ch, CURLOPT_USERPWD, "$sid:$token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code >= 200 && $http_code < 300) {
            $data = json_decode($response, true);
            $this->logSuccess($phone, $message, 'sms', 'twilio', $data['sid'] ?? '');
            return ['success' => true, 'message' => 'SMS sent successfully', 'provider' => 'twilio'];
        } else {
            throw new Exception("Twilio API error: HTTP $http_code");
        }
    }
    
    /**
     * Send SendGrid Email
     */
    private function sendViaSendGrid($email, $subject, $body, $type, $attachments = []) {
        $apiKey = getenv('SENDGRID_API_KEY') ?: self::SENDGRID_API_KEY;
        $fromEmail = getenv('SENDGRID_FROM_EMAIL') ?: self::SENDGRID_FROM_EMAIL;
        
        if (!$apiKey || !$fromEmail) {
            throw new Exception("SendGrid credentials not configured");
        }
        
        $url = 'https://api.sendgrid.com/v3/mail/send';
        
        $payload = [
            'personalizations' => [
                [
                    'to' => [['email' => $email]],
                    'subject' => $subject
                ]
            ],
            'from' => ['email' => $fromEmail, 'name' => 'Time & Attendance System'],
            'content' => [
                ['type' => 'text/html', 'value' => $body]
            ],
            'reply_to' => ['email' => $fromEmail]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code >= 200 && $http_code < 300) {
            $this->logSuccess($email, $subject, 'email', 'sendgrid', '');
            return ['success' => true, 'message' => 'Email sent successfully', 'provider' => 'sendgrid'];
        } else {
            throw new Exception("SendGrid API error: HTTP $http_code - $response");
        }
    }
    
    /**
     * Send via SMTP (PHP mail or PEAR Mail)
     */
    private function sendViaSMTP($email, $subject, $body, $type) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . self::SMTP_FROM_EMAIL . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        if (@mail($email, $subject, $body, $headers)) {
            $this->logSuccess($email, $subject, 'email', 'smtp', '');
            return ['success' => true, 'message' => 'Email sent via SMTP', 'provider' => 'smtp'];
        } else {
            throw new Exception("SMTP mail() function failed");
        }
    }
    
    /**
     * Send AWS SNS/SES (placeholder for AWS integration)
     */
    private function sendViaAWS($phone, $message, $type) {
        // TODO: Implement AWS SNS for SMS and SES for email
        throw new Exception("AWS integration not yet implemented");
    }
    
    /**
     * Log notification locally (for development)
     */
    private function logLocalNotification($recipient, $content, $channel, $type) {
        $logFile = __DIR__ . '/../logs/notifications.log';
        
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        $entry = json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'channel' => $channel,
            'type' => $type,
            'recipient' => $recipient,
            'content' => substr($content, 0, 100),
            'environment' => 'local'
        ]) . "\n";
        
        file_put_contents($logFile, $entry, FILE_APPEND);
        
        return ['success' => true, 'message' => 'Logged locally (dev mode)', 'provider' => 'local'];
    }
    
    /**
     * Log successful delivery to database
     */
    private function logSuccess($recipient, $content, $channel, $provider, $external_id) {
        $query = "INSERT INTO ta_notifications (user_phone, content, channel, status, external_id, provider, sent_at)
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->execute([$recipient, substr($content, 0, 500), $channel, 'sent', $external_id, $provider]);
    }
    
    /**
     * Log failed delivery to database
     */
    private function logFailure($recipient, $content, $channel, $provider, $error) {
        $query = "INSERT INTO ta_notifications (user_phone, content, channel, status, error_message, provider, sent_at)
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->execute([$recipient, substr($content, 0, 500), $channel, 'failed', $error, $provider]);
    }
    
    /**
     * Get configured SMS provider
     */
    private function getSMSProvider() {
        $provider = getenv('SMS_PROVIDER') ?: self::SMS_PROVIDER;
        return in_array($provider, ['twilio', 'aws', 'local']) ? $provider : 'local';
    }
    
    /**
     * Get configured Email provider
     */
    private function getEmailProvider() {
        $provider = getenv('EMAIL_PROVIDER') ?: self::EMAIL_PROVIDER;
        return in_array($provider, ['sendgrid', 'smtp', 'local']) ? $provider : 'smtp';
    }
    
    /**
     * Send batch notifications
     */
    public function sendBatch($recipients, $subject, $body, $channel = 'email', $type = 'general') {
        $results = [];
        
        foreach ($recipients as $recipient) {
            if ($channel === 'email') {
                $results[$recipient] = $this->sendEmail($recipient, $subject, $body, $type);
            } elseif ($channel === 'sms') {
                $results[$recipient] = $this->sendSMS($recipient, $subject, $type);
            }
        }
        
        return $results;
    }
    
    /**
     * Send approval notification
     */
    public function notifyApproval($user_id, $type, $details) {
        $query = "SELECT users.email, users.phone, employees.full_name 
                  FROM users 
                  JOIN employees ON users.id = employees.user_id
                  WHERE users.id = ?";
        
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($type === 'leave_approved') {
            $subject = "Your Leave Request Has Been Approved";
            $body = $this->getEmailTemplate('leave_approved', [
                'name' => $user['full_name'],
                'start_date' => $details['start_date'],
                'end_date' => $details['end_date']
            ]);
            $sms = "Your leave request from {$details['start_date']} to {$details['end_date']} has been approved.";
        } elseif ($type === 'leave_rejected') {
            $subject = "Your Leave Request Has Been Rejected";
            $body = $this->getEmailTemplate('leave_rejected', [
                'name' => $user['full_name'],
                'reason' => $details['reason']
            ]);
            $sms = "Your leave request has been rejected. Reason: " . substr($details['reason'], 0, 50);
        } else {
            return ['success' => false, 'message' => 'Unknown notification type'];
        }
        
        $emailResult = $this->sendEmail($user['email'], $subject, $body, $type);
        
        if ($user['phone']) {
            $this->sendSMS($user['phone'], $sms, $type);
        }
        
        return $emailResult;
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($template, $data = []) {
        $templates = [
            'leave_approved' => "
                <h2>Leave Request Approved</h2>
                <p>Dear {$data['name']},</p>
                <p>Your leave request from <strong>{$data['start_date']}</strong> to <strong>{$data['end_date']}</strong> has been <strong style='color: green;'>APPROVED</strong>.</p>
                <p>Please ensure to handover any pending tasks before the leave period.</p>
                <p>Best regards,<br>Time & Attendance System</p>
            ",
            'leave_rejected' => "
                <h2>Leave Request Rejected</h2>
                <p>Dear {$data['name']},</p>
                <p>Unfortunately, your leave request has been <strong style='color: red;'>REJECTED</strong>.</p>
                <p><strong>Reason:</strong> {$data['reason']}</p>
                <p>Please contact HR for further details.</p>
                <p>Best regards,<br>Time & Attendance System</p>
            ",
            'reminder_attendance' => "
                <h2>Attendance Reminder</h2>
                <p>Dear {$data['name']},</p>
                <p>This is a friendly reminder to mark your attendance for today.</p>
                <p>Please visit the Time & Attendance portal to record your time.</p>
                <p>Best regards,<br>Time & Attendance System</p>
            "
        ];
        
        return isset($templates[$template]) ? $templates[$template] : '';
    }
}
?>
