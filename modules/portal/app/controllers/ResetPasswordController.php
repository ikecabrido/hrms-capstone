<?php

namespace App\Controllers;

use Exception;
use App\Core\Session;
use App\Helper\Helper;
use App\Models\Reset;
use PHPMailer\PHPMailer\PHPMailer;

class ResetPasswordController
{
    private Reset $resetModel;

    public function __construct()
    {
        $this->resetModel = new Reset();
    }
    public function send()
    {
        Session::start();

        try {

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Helper::redirect('index.php?url=auth-index');
                exit;
            }

            $email = trim(
                Helper::sanitize($_POST['email'] ?? '')
            );

            if (
                empty($email) ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                !str_ends_with(strtolower($email), '@gmail.com')
            ) {
                throw new Exception(
                    'Please enter a valid Gmail address.'
                );
            }

            $user = $this->resetModel->findByEmail($email);

            if (!$user) {
                throw new Exception(
                    'No account was found with that Gmail address.'
                );
            }

            $token = bin2hex(random_bytes(32));

            $saved = $this->resetModel->savePasswordResetToken(
                $user['id'],
                $token
            );

            if (!$saved) {
                throw new Exception(
                    'Unable to process password reset. Please try again.'
                );
            }

            $resetLink =
                'http://localhost/hrms-capstone/modules/portal/' .
                '?url=auth-reset-password&token=' .
                urlencode($token);
            $mail = new PHPMailer(true);

            $mail->isSMTP();

            $mail->Host = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'];
            $mail->Password = $_ENV['MAIL_PASSWORD'];

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;

            $mail->Port =
                (int) $_ENV['MAIL_PORT'];

            $mail->setFrom(
                $_ENV['MAIL_FROM_ADDRESS'],
                $_ENV['MAIL_FROM_NAME']
            );

            $mail->addAddress($email);

            $mail->isHTML(true);

            $mail->Subject =
                'Password Reset - HR Employee Portal';

            $name = htmlspecialchars(
                $user['full_name']
                ?? $user['username']
                ?? $email
            );

            $safeResetLink = htmlspecialchars(
                $resetLink,
                ENT_QUOTES,
                'UTF-8'
            );

            $mail->Body = '
                <div style="
                    font-family:Arial,sans-serif;
                    max-width:600px;
                    margin:0 auto;
                    padding:30px;
                    border:1px solid #ddd;
                    border-radius:10px;
                ">

                    <h2 style="color:#333;">
                        Password Reset Request
                    </h2>

                    <p>
                        Hello ' . $name . ',
                    </p>

                    <p>
                        We received a request to reset the password
                        for your HR Employee Portal account.
                    </p>

                    <p>
                        Click the button below to reset your password:
                    </p>

                    <div style="
                        text-align:center;
                        margin:30px 0;
                    ">

                        <a href="' . $safeResetLink . '"
                            style="
                                background:#007bff;
                                color:#fff;
                                padding:12px 25px;
                                text-decoration:none;
                                border-radius:6px;
                                display:inline-block;
                            "
                        >
                            Reset Password
                        </a>

                    </div>

                    <p>
                        This link will expire in
                        <strong>1 hour</strong>.
                    </p>

                    <p>
                        If you did not request a password reset,
                        you can safely ignore this email.
                    </p>

                    <hr>

                    <p style="
                        color:#777;
                        font-size:12px;
                    ">
                        HR Employee Portal<br>
                        This is an automated email.
                        Please do not reply.
                    </p>

                </div>
            ';

            $mail->AltBody =
                "Password Reset Request\n\n" .
                "Hello {$name},\n\n" .
                "Click this link to reset your password:\n" .
                $resetLink .
                "\n\nThis link will expire in 1 hour.";

            $mail->send();

            Session::set(
                'success',
                'A password reset link has been sent to your Gmail address.'
            );

        } catch (Exception $e) {

            error_log(
                'Password Reset Error: ' .
                $e->getMessage()
            );

            Session::set(
                'error',
                $e->getMessage()
            );
        }

        Helper::redirect(
            'index.php?url=auth-index'
        );

        exit;
    }
    public function resetPassword()
    {
        Session::start();

        $token = trim($_GET['token'] ?? '');

        if (empty($token)) {
            Session::set(
                'error',
                'Invalid password reset link.'
            );

            Helper::redirect('index.php?url=auth-index');
            exit;
        }

        $user = $this->resetModel->findByResetToken($token);

        if (!$user) {
            Session::set(
                'error',
                'This password reset link is invalid or has expired.'
            );

            Helper::redirect('index.php?url=auth-index');
            exit;
        }

        $title = "Reset Password";

        $content = __DIR__ . '/../views/auth/reset-password.php';

        require __DIR__ . '/../views/auth/layout.php';
    }
    public function updatePassword()
    {
        Session::start();

        $token = trim($_POST['token'] ?? '');

        try {

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Helper::redirect('index.php?url=auth-index');
                exit;
            }

            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['password_confirmation'] ?? '';

            if (empty($token)) {
                throw new Exception('Invalid password reset token.');
            }

            if (strlen($password) < 8) {
                throw new Exception(
                    'Password must be at least 8 characters.'
                );
            }

            if ($password !== $confirmPassword) {
                throw new Exception(
                    'Passwords do not match.'
                );
            }

            $user = $this->resetModel->findByResetToken($token);

            if (!$user) {
                throw new Exception(
                    'This password reset link is invalid or has expired.'
                );
            }
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            if ($hashedPassword === false) {
                throw new Exception(
                    'Unable to secure your new password.'
                );
            }
            $updated = $this->resetModel->updatePassword(
                (int) $user['id'],
                $hashedPassword
            );

            if (!$updated) {
                throw new Exception(
                    'Unable to update your password.'
                );
            }
            $this->resetModel->clearPasswordResetToken(
                (int) $user['id']
            );

            Session::set(
                'success',
                'Your password has been successfully reset. You can now log in.'
            );

            Helper::redirect(
                'index.php?url=auth-index'
            );

            exit;

        } catch (Exception $e) {

            Session::set(
                'error',
                $e->getMessage()
            );

            Helper::redirect(
                'index.php?url=auth-reset-password&token=' .
                urlencode($token)
            );

            exit;
        }
    }
}