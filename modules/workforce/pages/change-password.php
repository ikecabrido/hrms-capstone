<?php
include_once __DIR__ . '/../../../auth/session.php';
include_once __DIR__ . '/../../../database/db.php';
require_once dirname(__DIR__, 3) . '/includes/app-base.php';

$db = new Database();
$conn = $db->getConnection();
$dashboardUrl = AppBase::pathFor('modules/workforce/index.php') . '?page=dashboard-overview';

$statusMessage = $_GET['status'] ?? '';
$statusType = $_GET['type'] ?? 'success';
$message = '';
if ($statusMessage === 'success') {
    $message = 'Password updated successfully.';
} elseif ($statusMessage === 'error') {
    $message = 'Unable to update your password. Please check the details and try again.';
    $statusType = 'error';
}
?>

<section class="wf-password-page">
    <div class="wf-password-card">
        <div class="wf-password-header">
            <div>
                <span class="wa-eyebrow">Security</span>
                <h1>Change Password</h1>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="wf-alert wf-alert-<?= htmlspecialchars($statusType) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars(AppBase::pathFor('auth/change-password.php'), ENT_QUOTES, 'UTF-8') ?>" method="POST" class="wf-password-form">
            <label>
                <span>Current password</span>
                <input type="password" name="current_password" placeholder="Enter your current password" required>
            </label>

            <label>
                <span>New password</span>
                <input type="password" name="new_password" placeholder="Enter a new password" required>
            </label>

            <label>
                <span>Confirm new password</span>
                <input type="password" name="confirm_password" placeholder="Re-enter the new password" required>
            </label>

            <div class="wf-password-note">Use at least 8 characters and ensure both passwords match.</div>

            <div class="wf-actions">
                <button type="submit" class="wa-button">Update Password</button>
                <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>" class="wa-button wa-button-secondary">Back</a>
            </div>
        </form>
    </div>
</section>

<style>
.wf-password-page {
    padding: 24px 0;
}

.wf-password-card {
    background: #ffffff;
    border-radius: 18px;
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
    border: 1px solid #e5e7eb;
    padding: 28px;
    max-width: 680px;
}

.wf-password-header {
    margin-bottom: 24px;
    padding-bottom: 18px;
    border-bottom: 1px solid #e5e7eb;
}

.wf-password-header h1 {
    margin: 6px 0 0;
    font-size: 2rem;
    color: #111827;
}

.wf-password-form {
    display: grid;
    gap: 22px;
}

.wf-password-form label {
    display: grid;
    gap: 8px;
    color: #374151;
    font-weight: 600;
}

.wf-password-form input {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    font: inherit;
}

.wf-password-form input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.wf-password-note {
    color: #6b7280;
    font-size: 0.9rem;
}

.wf-alert {
    margin-bottom: 20px;
    padding: 12px 14px;
    border-radius: 10px;
    font-weight: 600;
}

.wf-alert-success {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.wf-alert-error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.wf-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.wa-button {
    padding: 11px 18px;
    border: none;
    border-radius: 10px;
    background: #2563eb;
    color: #fff;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.wa-button:hover {
    background: #1d4ed8;
}

.wa-button-secondary {
    background: #e5e7eb;
    color: #111827;
}

.wa-button-secondary:hover {
    background: #d1d5db;
}
</style>
