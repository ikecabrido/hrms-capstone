<?php
include_once __DIR__ . '/../../../auth/session.php';
include_once __DIR__ . '/../../../database/db.php';

$db = new Database();
$conn = $db->getConnection();

$employeeId = $_SESSION['employee_id'] ?? null;
$profile = null;
if ($employeeId) {
    $stmt = $conn->prepare("SELECT e.*, d.department_name, p.position_name
        FROM em_employees e
        LEFT JOIN em_departments d ON d.department_id = e.department_id
        LEFT JOIN em_positions p ON p.position_id = e.position_id
        WHERE e.employee_id = :employee_id
        LIMIT 1");
    $stmt->execute([':employee_id' => $employeeId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
}

$statusMessage = $_GET['status'] ?? '';
$statusType = $_GET['type'] ?? 'success';
$successMessage = '';
if ($statusMessage === 'updated') {
    $successMessage = 'Profile updated successfully.';
}
if ($statusMessage === 'error') {
    $successMessage = 'Unable to update profile. Please try again.';
    $statusType = 'error';
}
?>

<section class="wf-settings-page">
    <div class="wf-settings-card">
        <div class="wf-settings-header">
            <div>
                <span class="wa-eyebrow">Account</span>
                <h1>Profile Settings</h1>
            </div>
            <div class="wf-avatar-circle"><?= htmlspecialchars(substr($_SESSION['employee_name'] ?? 'U', 0, 1)) ?></div>
        </div>

        <?php if ($successMessage): ?>
            <div class="wf-alert wf-alert-<?= htmlspecialchars($statusType) ?>"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <?php if ($profile): ?>
            <form action="../../auth/update-profile.php" method="POST" class="wf-settings-form">
                <div class="wf-grid-two">
                    <label>
                        <span>First name</span>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($profile['first_name'] ?? '') ?>" required>
                    </label>
                    <label>
                        <span>Middle name</span>
                        <input type="text" name="middle_name" value="<?= htmlspecialchars($profile['middle_name'] ?? '') ?>">
                    </label>
                </div>

                <div class="wf-grid-two">
                    <label>
                        <span>Last name</span>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($profile['last_name'] ?? '') ?>" required>
                    </label>
                    <label>
                        <span>Employee code</span>
                        <input type="text" value="<?= htmlspecialchars($_SESSION['employee_code'] ?? '') ?>" disabled>
                    </label>
                </div>

                <div class="wf-grid-two">
                    <label>
                        <span>Department</span>
                        <input type="text" value="<?= htmlspecialchars($profile['department_name'] ?? 'Unknown') ?>" disabled>
                    </label>
                    <label>
                        <span>Position</span>
                        <input type="text" value="<?= htmlspecialchars($profile['position_name'] ?? 'Unknown') ?>" disabled>
                    </label>
                </div>

                <div class="wf-grid-two">
                    <label>
                        <span>Email address</span>
                        <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>">
                    </label>
                    <label>
                        <span>Mobile number</span>
                        <input type="text" name="mobile_no" value="<?= htmlspecialchars($profile['mobile_no'] ?? '') ?>">
                    </label>
                </div>

                <div class="wf-grid-two">
                    <label>
                        <span>Phone number</span>
                        <input type="text" name="phone_no" value="<?= htmlspecialchars($profile['phone_no'] ?? '') ?>">
                    </label>
                    <label>
                        <span>Gender</span>
                        <input type="text" value="<?= htmlspecialchars($profile['gender'] ?? 'Not set') ?>" disabled>
                    </label>
                </div>

                <label>
                    <span>Current address</span>
                    <textarea name="current_address" rows="4"><?= htmlspecialchars($profile['current_address'] ?? '') ?></textarea>
                </label>

                <div class="wf-actions">
                    <button type="submit" class="wa-button">Save Changes</button>
                    <a href="?page=dashboard-overview" class="wa-button wa-button-secondary">Back</a>
                </div>
            </form>
        <?php else: ?>
            <div class="wf-alert wf-alert-error">Profile information could not be loaded.</div>
        <?php endif; ?>
    </div>
</section>

<style>
.wf-settings-page {
    padding: 24px 0;
}

.wf-settings-card {
    background: #ffffff;
    border-radius: 18px;
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
    border: 1px solid #e5e7eb;
    padding: 28px;
    max-width: 980px;
}

.wf-settings-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 18px;
    border-bottom: 1px solid #e5e7eb;
}

.wf-settings-header h1 {
    margin: 6px 0 0;
    font-size: 2rem;
    color: #111827;
}

.wf-avatar-circle {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #4f46e5, #0ea5e9);
    color: white;
    font-size: 1.45rem;
    font-weight: 700;
}

.wf-settings-form {
    display: grid;
    gap: 20px;
}

.wf-grid-two {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.wf-settings-form label {
    display: grid;
    gap: 8px;
    color: #374151;
    font-weight: 600;
}

.wf-settings-form input,
.wf-settings-form textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    font: inherit;
    background: #fff;
    color: #111827;
}

.wf-settings-form input:focus,
.wf-settings-form textarea:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.wf-settings-form textarea {
    resize: vertical;
    min-height: 110px;
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
    margin-top: 8px;
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

@media (max-width: 768px) {
    .wf-grid-two {
        grid-template-columns: 1fr;
    }

    .wf-settings-card {
        padding: 20px 16px;
    }
}
</style>
