<?php
/**
 * EmailService — Reusable email sending service for the HR Management System.
 *
 * Usage:
 *   require_once __DIR__ . '/../../lib/vendor/autoload.php';
 *   require_once __DIR__ . '/../../lib/config/email_config.php';
 *
 *   $mailer = EmailService::getInstance();
 *   $mailer->send($recipients, 'Subject', '<html>...</html>', 'Plain text');
 */

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

require_once __DIR__ . '/../../lib/vendor/autoload.php';

class EmailService
{
    private static ?self $instance = null;
    private array $config;
    private PHPMailer $mail;

    private function __construct()
    {
        $this->config = require __DIR__ . '/../../lib/config/email_config.php';
        $this->mail = new PHPMailer(true);
        $this->configure();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function configure(): void
    {
        $provider = strtolower((string) ($this->config['provider'] ?? 'smtp'));

        if ($provider === 'log' || $provider === 'dev') {
            return;
        }

        $this->mail->isSMTP();
        $this->mail->Host = $this->config['smtp_host'];
        $this->mail->Port = (int) $this->config['smtp_port'];
        $this->mail->SMTPAuth = (bool) $this->config['smtp_auth'];
        $this->mail->Username = $this->config['username'];
        $this->mail->Password = preg_replace('/\s+/', '', (string) $this->config['password']);
        $this->mail->SMTPSecure = $this->config['smtp_secure'];
        $this->mail->Timeout = (int) ($this->config['timeout'] ?? 15);

        $this->mail->setFrom($this->config['from_email'], $this->config['from_name']);
        $this->mail->addReplyTo($this->config['reply_to_email'], $this->config['reply_to_name']);

        $this->mail->isHTML(true);
        $this->mail->CharSet = PHPMailer::CHARSET_UTF8;

        if (!empty($this->config['debug'])) {
            $this->mail->SMTPDebug = 2;
            $this->mail->DebugOutput = function ($str) {
                if (!empty($this->config['log_path'])) {
                    error_log('[PHPMailer] ' . $str);
                }
            };
        }
    }

    public function send($to, string $subject, string $body, ?string $altBody = null): bool
    {
        $provider = strtolower((string) ($this->config['provider'] ?? 'smtp'));

        if ($provider === 'log' || $provider === 'dev') {
            return $this->logEmail($to, $subject, $body, $altBody);
        }

        try {
            $this->mail->clearAddresses();

            if (is_array($to)) {
                if (isset($to['email'])) {
                    $to = [$to];
                }

                foreach ($to as $recipient) {
                    if (!is_array($recipient)) continue;
                    $email = trim((string) ($recipient['email'] ?? ''));
                    $name  = trim((string) ($recipient['name'] ?? ''));
                    if ($email !== '') {
                        $this->mail->addAddress($email, $name);
                    }
                }
            } else {
                $this->mail->addAddress($to);
            }

            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = $altBody ?? strip_tags(str_replace('<br>', "\n", $body));

            return $this->mail->send();
        } catch (MailerException $e) {
            error_log('EmailService send error: ' . $e->getMessage());
            error_log('EmailService send trace: ' . $e->getTraceAsString());
            return false;
        } catch (Throwable $e) {
            error_log('EmailService send generic error: ' . $e->getMessage());
            error_log('EmailService send generic trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    private function logEmail($to, string $subject, string $body, ?string $altBody = null): bool
    {
        $logPath = $this->config['log_path'] ?? __DIR__ . '/../../logs/email_queue.log';
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $toStr = is_array($to)
            ? (isset($to['email']) ? ($to['email'] . (isset($to['name']) ? ' <' . $to['name'] . '>' : '')) : implode(', ', array_map(fn($r) => $r['email'] ?? $r, $to)))
            : (string) $to;

        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $toStr,
            'subject' => $subject,
            'body' => $body,
            'alt_body' => $altBody,
        ];

        file_put_contents($logPath, json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n" . str_repeat('-', 80) . "\n", FILE_APPEND);
        return true;
    }

    public function getMail(): PHPMailer
    {
        return $this->mail;
    }

    public function isLogMode(): bool
    {
        $provider = strtolower((string) ($this->config['provider'] ?? 'smtp'));
        return $provider === 'log' || $provider === 'dev';
    }
}
