<?php
$viewData = get_defined_vars();
$application = is_array($viewData['application'] ?? null) ? $viewData['application'] : [];
$education = is_array($viewData['education'] ?? null) ? $viewData['education'] : [];
$experience = is_array($viewData['experience'] ?? null) ? $viewData['experience'] : [];

$fullName = trim(($application['first_name'] ?? '') . ' ' . ($application['last_name'] ?? ''));
$positionValue = $application['position'] ?? 'N/A';
$departmentValue = $application['department'] ?? 'N/A';
$escape = static function ($value) {
    return htmlspecialchars((string)($value ?? 'N/A'), ENT_QUOTES, 'UTF-8');
};
$status = strtolower((string)($application['status'] ?? ''));
$isProcessed = in_array($status, ['approved', 'rejected'], true);
?>

<div class="application-detail">
    <div class="detail-header">
        <a href="index.php?page=applications" class="application-back-link">
            <i class="fas fa-arrow-left"></i> Back to Applications
        </a>
        <div class="detail-heading">
            <div>
                <p class="detail-eyebrow">Applicant Profile</p>
                <h2 class="module-title"><?= $escape($fullName ?: 'Applicant') ?></h2>
                <p class="module-description"><?= $escape($application['job_title'] ?? 'Application details') ?></p>
            </div>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">

                <?php if (!$isProcessed): ?>
                    <a href="index.php?page=approve-application&id=<?= (int)($application['id'] ?? 0); ?>"
                        class="btn btn-success"
                        onclick="return confirm('Are you sure you want to approve this applicant?');">
                        <i class="fas fa-check"></i> Approve
                    </a>
                    <a href="index.php?page=reject-application&id=<?= (int)($application['id'] ?? 0); ?>"
                        class="btn btn-danger"
                        onclick="return confirm('Are you sure you want to reject this applicant?');">
                        <i class="fas fa-times"></i> Reject
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <section class="detail-section">
        <h3><i class="fas fa-user"></i> Personal Information</h3>
        <div class="detail-grid personal-info-grid">
            <div><span class="detail-label">First Name</span><strong><?= $escape($application['first_name'] ?? 'N/A') ?></strong></div>
            <div><span class="detail-label">Middle Name</span><strong><?= $escape($application['middle_name'] ?? 'N/A') ?></strong></div>
            <div><span class="detail-label">Last Name</span><strong><?= $escape($application['last_name'] ?? 'N/A') ?></strong></div>
            <div><span class="detail-label">Email</span><strong><?= $escape($application['email'] ?? 'N/A') ?></strong></div>
            <div><span class="detail-label">Phone</span><strong><?= $escape($application['phone'] ?? 'N/A') ?></strong></div>
            <div><span class="detail-label">Age</span><strong><?= $escape($application['age'] ?? 'N/A') ?></strong></div>
            <div><span class="detail-label">Gender</span><strong><?= $escape($application['gender'] ?? 'N/A') ?></strong></div>
            <div><span class="detail-label">Birthplace</span><strong><?= $escape($application['birthplace'] ?? 'N/A') ?></strong></div>
            <div><span class="detail-label">Birthdate</span><strong><?= !empty($application['birthdate']) ? $escape(date('M d, Y', strtotime($application['birthdate']))) : 'N/A' ?></strong></div>
            <div><span class="detail-label">Civil Status</span><strong><?= $escape($application['civil_status'] ?? 'N/A') ?></strong></div>
            <div><span class="detail-label">Citizenship</span><strong><?= $escape($application['citizenship'] ?? 'N/A') ?></strong></div>
            <div><span class="detail-label">Religion</span><strong><?= $escape($application['religion'] ?? 'N/A') ?></strong></div>
            <div class="detail-wide"><span class="detail-label">Current Address</span><strong><?= $escape($application['address'] ?? 'N/A') ?></strong></div>
            <div class="detail-wide"><span class="detail-label">Permanent Address</span><strong><?= $escape($application['permanent_address'] ?? 'N/A') ?></strong></div>
        </div>
    </section>

    <section class="detail-section">
        <h3><i class="fas fa-briefcase"></i> Application Information</h3>
        <div class="detail-grid">
            <div><span class="detail-label">Position</span><strong><?= $escape($positionValue) ?></strong></div>
            <div><span class="detail-label">Department</span><strong><?= $escape($departmentValue) ?></strong></div>
            <div><span class="detail-label">Source</span><strong><?= $escape($application['source'] ?? 'N/A') ?></strong></div>
            <div><span class="detail-label">Applied On</span><strong><?= !empty($application['created_at']) ? $escape(date('M d, Y', strtotime($application['created_at']))) : 'N/A' ?></strong></div>
            <div><span class="detail-label">Resume</span>
                <?php if (!empty($application['resume'])): ?>
                    <a href="../recruitment/<?= $escape($application['resume']) ?>" target="_blank" class="detail-link">
                        <i class="fas fa-file-pdf"></i> View Resume
                    </a>
                <?php else: ?>
                    <strong>No resume uploaded</strong>
                <?php endif; ?>
            </div>
            <div class="detail-wide"><span class="detail-label">Summary</span><strong><?= nl2br($escape($application['summary'] ?? 'N/A')) ?></strong></div>
        </div>
    </section>

    <section class="detail-section">
        <h3><i class="fas fa-graduation-cap"></i> Education</h3>
        <?php if (empty($education)): ?>
            <p class="detail-empty">No education records available.</p>
        <?php else: ?>
            <div class="detail-records">
                <?php foreach ($education as $record): ?>
                    <div class="detail-record">
                        <strong><?= $escape($record['degree'] ?? $record['course'] ?? $record['program'] ?? 'Education') ?></strong>
                        <span><?= $escape($record['school'] ?? $record['institution'] ?? $record['school_name'] ?? '') ?></span>
                        <small><?= $escape($record['year_graduated'] ?? $record['graduation_year'] ?? '') ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="detail-section">
        <h3><i class="fas fa-building"></i> Work Experience</h3>
        <?php if (empty($experience)): ?>
            <p class="detail-empty">No work experience records available.</p>
        <?php else: ?>
            <div class="detail-records">
                <?php foreach ($experience as $record): ?>
                    <div class="detail-record">
                        <strong><?= $escape($record['position'] ?? $record['job_title'] ?? $record['role'] ?? 'Work Experience') ?></strong>
                        <span><?= $escape($record['company'] ?? $record['company_name'] ?? $record['employer'] ?? '') ?></span>
                        <small><?= $escape($record['duration'] ?? $record['date_range'] ?? '') ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>