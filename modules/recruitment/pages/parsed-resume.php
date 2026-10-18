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
                                    <?php
                                    $resumePath = trim($resume['resume'] ?? '');
                                    $resumeUrl = '';

                                    if (!empty($resumePath)) {

                                        /*
         * =====================================================
         * BUILD RESUME URL
         * =====================================================
         *
         * Current upload structure:
         *
         * /modules/recruitment/uploads/{application_id}/filename
         *
         * Examples:
         *
         * /modules/recruitment/uploads/4/GROUP_6.docx
         * /modules/recruitment/uploads/5/sample_resume.pdf
         */

                                        // 1. If the database already contains a complete URL
                                        if (preg_match('~^https?://~i', $resumePath)) {

                                            $resumeUrl = $resumePath;
                                        } else {

                                            // Normalize Windows backslashes
                                            $resumePath = str_replace('\\', '/', $resumePath);

                                            /*
             * Remove any leading slash so we can safely
             * construct the website URL.
             */
                                            $resumePath = ltrim($resumePath, '/');

                                            /*
             * If the stored path already contains the
             * correct recruitment upload directory,
             * use it directly.
             */
                                            if (str_starts_with($resumePath, 'modules/recruitment/uploads/')) {

                                                $resumeUrl = '/' . $resumePath;

                                                /*
             * Handle stored paths such as:
             *
             * uploads/4/GROUP_6.docx
             */
                                            } elseif (str_starts_with($resumePath, 'uploads/')) {

                                                $resumeUrl = '/modules/recruitment/' . $resumePath;

                                                /*
             * Handle old stored paths:
             *
             * modules/recruitment/JobPortal/uploads/4/file.docx
             *
             * Convert them to the NEW location.
             */
                                            } elseif (
                                                str_contains(
                                                    $resumePath,
                                                    'modules/recruitment/JobPortal/uploads/'
                                                )
                                            ) {

                                                $resumePath = substr(
                                                    $resumePath,
                                                    strpos(
                                                        $resumePath,
                                                        'modules/recruitment/JobPortal/uploads/'
                                                    ) + strlen(
                                                        'modules/recruitment/JobPortal/'
                                                    )
                                                );

                                                $resumeUrl = '/modules/recruitment/' . $resumePath;

                                                /*
             * Handle old filesystem paths such as:
             *
             * /home/recruitment.bcp-hrms.com/public_html/
             * modules/recruitment/JobPortal/uploads/4/file.docx
             */
                                            } elseif (
                                                str_contains(
                                                    $resumePath,
                                                    '/modules/recruitment/JobPortal/uploads/'
                                                )
                                            ) {

                                                $position = strpos(
                                                    $resumePath,
                                                    '/modules/recruitment/JobPortal/uploads/'
                                                );

                                                $relativePath = substr(
                                                    $resumePath,
                                                    $position + strlen(
                                                        '/modules/recruitment/JobPortal/'
                                                    )
                                                );

                                                $resumeUrl = '/modules/recruitment/' . $relativePath;

                                                /*
             * Handle a plain filename/path as a fallback.
             */
                                            } else {

                                                $resumeUrl = '/modules/recruitment/uploads/' .
                                                    ltrim($resumePath, '/');
                                            }
                                        }
                                    }
                                    ?>

                                    <?php if (!empty($resumeUrl)): ?>

                                        <a href="<?= htmlspecialchars(
                                                        $resumeUrl,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="btn-view-resume"
                                            title="View Resume">

                                            <i class="fas fa-file-pdf"></i>

                                        </a>

                                    <?php else: ?>

                                        <span class="text-muted small">
                                            <i class="fas fa-file-excel"></i>
                                            No Resume
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