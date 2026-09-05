<?php
/**
 * Email Configuration
 *
 * Centralized SMTP settings for the HR Management System.
 * Supports: smtp, log/dev.
 *
 * Set environment variables in your web server / CLI environment, or edit
 * the fallback values below for local development.
 */

return [
    'provider' => getenv('MAIL_PROVIDER') ?: 'smtp',

    'smtp_host' => getenv('MAIL_SMTP_HOST') ?: 'smtp.gmail.com',
    'smtp_port' => (int) (getenv('MAIL_SMTP_PORT') ?: 465),
    'smtp_secure' => getenv('MAIL_SMTP_SECURE') ?: 'ssl',
    'smtp_auth' => true,

    'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'hrms@example.com',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'Bestlink HRMS',
    'reply_to_email' => getenv('MAIL_REPLY_TO_EMAIL') ?: 'hrms@example.com',
    'reply_to_name' => getenv('MAIL_REPLY_TO_NAME') ?: 'Human Resources',

    'username' => getenv('MAIL_USERNAME') ?: 'jalotjot.cheska@gmail.com',
    'password' => getenv('MAIL_PASSWORD') ?: 'odju jntd gyzs plln',

    'app_url' => getenv('APP_URL') ?: 'http://localhost/hrms-capstone',

    'company_name' => getenv('COMPANY_NAME') ?: 'Bestlink College of the Philippines',
    'company_email' => getenv('COMPANY_EMAIL') ?: 'hr@bestlink.edu.ph',
    'company_website' => getenv('COMPANY_WEBSITE') ?: 'www.bestlinkcollege.edu.ph',
    'company_address' => getenv('COMPANY_ADDRESS') ?: 'Quirino Highway, Brgy. Minuyan Proper, City of San Jose del Monte, Bulacan',

    'debug' => false,
    'timeout' => 15,
    'log_path' => __DIR__ . '/../logs/email_queue.log',
];
