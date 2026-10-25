<?php

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Safely initialize controller data
|--------------------------------------------------------------------------
*/
$application = isset($application) && is_array($application)
    ? $application
    : [];

$education = isset($education) && is_array($education)
    ? $education
    : [];

$experience = isset($experience) && is_array($experience)
    ? $experience
    : [];

$existingEmployee = isset($existingEmployee) && is_array($existingEmployee)
    ? $existingEmployee
    : null;

/*
|--------------------------------------------------------------------------
| HTML escaping helper
|--------------------------------------------------------------------------
*/
$e = static function ($value): string {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
};

/*
|--------------------------------------------------------------------------
| Applicant information
|--------------------------------------------------------------------------
*/
$fullName = trim(
    ($application['first_name'] ?? '') . ' ' .
        ($application['middle_name'] ?? '') . ' ' .
        ($application['last_name'] ?? '')
);

if ($fullName === '') {
    $fullName = 'Hired Applicant';
}

$applicationId = (int) (
    $application['application_id']
    ?? $application['id']
    ?? 0
);

/*
|--------------------------------------------------------------------------
| Flash messages
|--------------------------------------------------------------------------
*/
$message = $_SESSION['success'] ?? '';
$errorMessage = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

/*
|--------------------------------------------------------------------------
| Table columns to exclude from education and experience
|--------------------------------------------------------------------------
*/
$excludedColumns = [
    'id',
    'application_id'
];

?>

<div class="application-detail">

    <div class="detail-header">


        <div class="module-header">
            <a href="index.php?page=hired-applicants" class="application-back-link">
                <i class="fas fa-arrow-left"></i> Back to Hired Applicants
            </a>
            <div>
                <p class="detail-eyebrow">Hired Applicant Review</p>
                <h1 class="module-title"><?= $e($fullName) ?></h1>
                <p class="module-description">
                    <?= $e($application['position'] ?? 'N/A') ?> &middot; <?= $e($application['department'] ?? 'N/A') ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Success message -->
    <?php if ($message !== ''): ?>
        <div style="background:#dcfce7;color:#166534;padding:12px 16px;border-radius:6px;margin-bottom:18px;">
            <?= $e($message) ?>
        </div>
    <?php endif; ?>

    <!-- Error message -->
    <?php if ($errorMessage !== ''): ?>
        <div style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:6px;margin-bottom:18px;">
            <?= $e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <!-- Personal Information Section -->
    <div class="detail-section">
        <h3><i class="fas fa-user-circle"></i> Personal Information</h3>

        <div class="detail-grid personal-info-grid">
            <div>
                <span class="detail-label">First Name</span>
                <strong><?= $e($application['first_name'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Middle Name</span>
                <strong><?= $e($application['middle_name'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Last Name</span>
                <strong><?= $e($application['last_name'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Email Address</span>
                <strong><?= $e($application['email'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Phone Number</span>
                <strong><?= $e($application['phone'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Gender</span>
                <strong><?= $e($application['gender'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Birth Date</span>
                <strong><?= $e($application['birthdate'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Birthplace</span>
                <strong><?= $e($application['birthplace'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Age</span>
                <strong><?= $e($application['age'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Civil Status</span>
                <strong><?= $e($application['civil_status'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Citizenship</span>
                <strong><?= $e($application['citizenship'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Religion</span>
                <strong><?= $e($application['religion'] ?? 'N/A') ?></strong>
            </div>
            <div class="detail-wide">
                <span class="detail-label">Current Address</span>
                <strong><?= $e($application['address'] ?? 'N/A') ?></strong>
            </div>
            <div class="detail-wide">
                <span class="detail-label">Permanent Address</span>
                <strong><?= $e($application['permanent_address'] ?? 'N/A') ?></strong>
            </div>
        </div>
    </div>

    <!-- Employment Information Section -->
    <div class="detail-section">
        <h3><i class="fas fa-briefcase"></i> Employment Information</h3>

        <div class="detail-grid">
            <div>
                <span class="detail-label">Position</span>
                <strong><?= $e($application['position'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Department</span>
                <strong><?= $e($application['department'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Job Title</span>
                <strong><?= $e($application['base_job'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Salary</span>
                <strong>₱<?= number_format((float) ($application['salary'] ?? 0), 2) ?></strong>
            </div>
            <div class="detail-wide">
                <span class="detail-label">Hired Date</span>
                <strong><?= $e($application['hired_at'] ?? 'N/A') ?></strong>
            </div>
        </div>
    </div>

    <!-- Education Section -->
    <div class="detail-section">
        <h3><i class="fas fa-graduation-cap"></i> Education History</h3>

        <?php if (!empty($education)): ?>
            <div class="detail-records">
                <?php foreach ($education as $row): ?>
                    <div class="detail-record">
                        <strong><?= $e($row['degree'] ?? $row['school'] ?? 'Education Record') ?></strong>
                        <?php foreach ($row as $column => $value): ?>
                            <?php if (!in_array($column, $excludedColumns, true) && !in_array($column, ['degree', 'school'])): ?>
                                <small><strong><?= $e(ucwords(str_replace('_', ' ', $column))) ?>:</strong> <?= $e(($value !== null && $value !== '') ? $value : 'N/A') ?></small>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="detail-empty">No education records found.</p>
        <?php endif; ?>
    </div>

    <!-- Work Experience Section -->
    <div class="detail-section">
        <h3><i class="fas fa-history"></i> Work Experience</h3>

        <?php if (!empty($experience)): ?>
            <div class="detail-records">
                <?php foreach ($experience as $row): ?>
                    <div class="detail-record">
                        <strong><?= $e($row['job_title'] ?? $row['company'] ?? 'Work Experience') ?></strong>
                        <?php foreach ($row as $column => $value): ?>
                            <?php if (!in_array($column, $excludedColumns, true) && !in_array($column, ['job_title', 'company'])): ?>
                                <small><strong><?= $e(ucwords(str_replace('_', ' ', $column))) ?>:</strong> <?= $e(($value !== null && $value !== '') ? $value : 'N/A') ?></small>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="detail-empty">No work experience records found.</p>
        <?php endif; ?>


        <!-- Move to Employee Action Bar -->
        <div style="display:flex;justify-content:flex-end;margin-top:24px;">
            <?php if (!empty($existingEmployee)): ?>
                <div style="background:#dcfce7;color:#166534;padding:12px 16px;border-radius:6px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-check-circle"></i>
                    Already an employee: <strong><?= $e($existingEmployee['employee_code'] ?? '') ?></strong>
                </div>
            <?php elseif ($applicationId > 0): ?>
                <form method="POST" action="index.php?page=move-to-employee" onsubmit="return confirm('Move this hired applicant to Employees?');">
                    <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                    <button type="submit" class="btn-create-primary">
                        <i class="fas fa-user-plus"></i> Move to Employee
                    </button>
                </form>
            <?php else: ?>
                <div style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:6px;">
                    Unable to transfer this applicant because the application ID is missing.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>