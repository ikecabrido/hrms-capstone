<?php
require_once __DIR__ . '/../classes/ParsedResume.php';

$parsedResumeModel = new ParsedResume();

try {
    $parsedResumes = $parsedResumeModel->findAll();
} catch (Exception $e) {
    $parsedResumes = [];
    $error = $e->getMessage();
}
?>
<div class="module-header">
    <h1>Parsed Resumes <span class="badge" style="background: #dcfce7; color: #166534; margin-left: 10px;">Approved</span></h1>

    <p>Candidates ready for the interview scheduling phase.</p>
</div>

<div class="module-content">
    <div class="card-body">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">App ID</th>
                        <th scope="col">Applicant Name</th>
                        <th scope="col">Cellphone Number</th>
                        <th scope="col">Email</th>
                        <th scope="col">Position</th>
                        <th scope="col">Approved Date</th>
                        <th scope="col">Resume</th>
                        <th scope="col">Next Steps</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($parsedResumes)): ?>
                        <?php foreach ($parsedResumes as $resume): ?>
                            <tr>
                                <td>
                                    <span class="app-id-badge">
                                        #<?= htmlspecialchars($resume['application_id'] ?? '') ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="candidate-info">
                                        <span class="candidate-name">
                                            <?= htmlspecialchars(
                                                trim(
                                                    ($resume['first_name'] ?? '') . ' ' .
                                                        ($resume['last_name'] ?? '')
                                                )
                                            ) ?>
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <?php if (!empty($resume['phone'])): ?>
                                        <span class="contact-phone">
                                            <i class="fas fa-phone-alt"></i>
                                            <span><?= htmlspecialchars($resume['phone']) ?></span>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($resume['email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($resume['email']) ?>" class="contact-link">
                                            <i class="fas fa-envelope"></i>
                                            <span><?= htmlspecialchars($resume['email']) ?></span>
                                        </a>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge-position">
                                        <?= htmlspecialchars($resume['job_title'] ?? 'N/A') ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="text-date">
                                        <?= htmlspecialchars($resume['approved_at'] ?? '') ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($resume['resume'])): ?>
                                        <a href="<?= htmlspecialchars($resume['resume']) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="btn-view-resume">
                                            <i class="fas fa-file-pdf"></i>

                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">
                                            <i class="fas fa-file-excel"></i> No Resume
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Next Steps Column -->
                                <td>
                                    <a href="index.php?page=schedule-interview&id=<?= urlencode($resume['application_id']) ?>" class="btn-schedule">
                                        <i class="fas fa-calendar-plus"></i> Schedule
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-folder-open empty-icon"></i>
                                    <p class="mb-0">No parsed resumes found.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>