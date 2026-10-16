<?php
require_once __DIR__ . '/../classes/Job.php';

$jobId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$jobModel = new Job();
$job = $jobId ? $jobModel->find($jobId) : false;
$message = '';
$error = '';
$isPosted = $job && (int) ($job['is_posted'] ?? 0) === 1;

if (!$job) {
    $error = 'The selected job could not be found.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $isPosted) {
    $message = 'This job has already been posted.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description'] ?? '');
    $qualifications = trim($_POST['qualifications'] ?? '');

    if ($description === '' || $qualifications === '') {
        $error = 'Job description and qualifications are required.';
    } elseif ($jobModel->updatePosting($jobId, $description, $qualifications)) {
        $job = $jobModel->find($jobId);
        $message = 'Job posting saved successfully.';
    } else {
        $error = 'Unable to save the job posting. Please try again.';
    }
}

$salaryRange = 'Not set';
if ($job && !empty($job['position_id'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        $salaryStatement = $db->prepare("
            SELECT minimum_salary, maximum_salary
            FROM pr_salary_structures
            WHERE position_id = :position_id
              AND status = 'Active'
              AND effective_date <= CURDATE()
              AND (end_date IS NULL OR end_date >= CURDATE())
            ORDER BY effective_date DESC
            LIMIT 1
        ");
        $salaryStatement->execute([':position_id' => (int) $job['position_id']]);
        $salary = $salaryStatement->fetch(PDO::FETCH_ASSOC);

        if ($salary) {
            $salaryRange = 'PHP ' . number_format((float) $salary['minimum_salary'], 2) .
                ' - PHP ' . number_format((float) $salary['maximum_salary'], 2);
        }
    } catch (Throwable $exception) {
        // Salary data is optional for posting details.
    }
}
?>

<div class="job-posting-header">
    <a href="index.php?page=job-list" class="back-link"><i class="fas fa-arrow-left"></i> Back to job list</a>
    <span class="posting-kicker">Recruitment / Job Posting</span>
    <h1><?= $job ? htmlspecialchars($job['title'] ?? '') : 'Job Posting' ?></h1>
    <p>Review the position details, then complete the public job posting.</p>
</div>

<?php if ($error): ?>
    <div class="posting-alert posting-alert-error"><?= htmlspecialchars($error) ?></div>
<?php elseif ($message): ?>
    <div class="posting-alert posting-alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($job): ?>
    <div class="job-posting-layout">
        <section class="posting-details-panel">
            <div class="posting-panel-heading">
                <span class="posting-icon"><i class="fas fa-briefcase"></i></span>
                <div>
                    <h2>Position information</h2>
                    <p>Details assigned to this opening.</p>
                </div>
            </div>

            <dl class="job-detail-list">
                <div>
                    <dt>Job title</dt>
                    <dd><?= htmlspecialchars($job['title'] ?? 'Not set') ?></dd>
                </div>
                <div>
                    <dt>Position</dt>
                    <dd><?= htmlspecialchars($job['position'] ?? $job['title'] ?? 'Not set') ?></dd>
                </div>
                <div>
                    <dt>Department</dt>
                    <dd><?= htmlspecialchars($job['department'] ?? 'Not set') ?></dd>
                </div>
                <div>
                    <dt>Employment type</dt>
                    <dd>Full Time</dd>
                </div>
                <div>
                    <dt>Work location</dt>
                    <dd><?= htmlspecialchars($job['location'] ?? 'Not set') ?></dd>
                </div>
                <div>
                    <dt>Salary range</dt>
                    <dd><?= htmlspecialchars($salaryRange) ?></dd>
                </div>
                <div>
                    <dt>Number of vacancies</dt>
                    <dd><?= (int) ($job['max_applicants'] ?? 0) ?: 'Unlimited' ?></dd>
                </div>
            </dl>
        </section>

        <section class="posting-form-panel">
            <div class="posting-panel-heading">
                <span class="posting-icon posting-icon-accent"><i class="fas fa-pen"></i></span>
                <div>
                    <h2>Posting content</h2>
                    <p>Write what applicants need to know.</p>
                </div>
            </div>

            <?php if ($isPosted): ?>
                <div class="posted-status"><i class="fas fa-check-circle"></i> This job is already live on JobPortal.</div>
            <?php endif; ?>

            <form method="POST" action="index.php?page=job-posting&amp;id=<?= (int) $job['id'] ?>" class="job-posting-form <?= $isPosted ? 'is-posted' : '' ?>" data-skip>
                <label for="description">Job description</label>
                <textarea id="description" name="description" rows="8" required <?= $isPosted ? 'readonly' : '' ?>><?= htmlspecialchars($job['description'] ?? '') ?></textarea>

                <label for="qualifications">Qualifications</label>
                <textarea id="qualifications" name="qualifications" rows="8" required <?= $isPosted ? 'readonly' : '' ?>><?= htmlspecialchars($job['qualifications'] ?? '') ?></textarea>

                <button type="submit" class="post-job-button <?= $isPosted ? 'posted' : '' ?>" <?= $isPosted ? 'disabled' : '' ?>>
                    <i class="fas <?= $isPosted ? 'fa-check' : 'fa-paper-plane' ?>"></i>
                    <?= $isPosted ? 'Posted' : 'Post job' ?>
                </button>
            </form>
        </section>
    </div>
<?php endif; ?>