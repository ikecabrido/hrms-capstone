<?php
$viewData = get_defined_vars();

$applications = is_array($viewData['applications'] ?? null)
    ? $viewData['applications']
    : [];

$message = $_GET['message'] ?? '';
?>

<div class="module-header">
    <h1>Hired Applicants</h1>
    <p>Applicants successfully moved to the hired list.</p>
</div>

<div class="module-content">

    <?php if ($message !== ''): ?>
        <div style="background:#dcfce7;color:#166534;padding:10px 14px;border-radius:6px;margin-bottom:16px;">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:6px;margin-bottom:16px;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <div class="hired-applicants-scroll">

            <table class="admin-table" id="hiredApplicantsTable">

                <thead>
                    <tr>
                        <th>Applicant Name</th>
                        <th>Email</th>
                        <th>Cellphone Number</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Salary</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($applications)): ?>

                        <?php foreach ($applications as $application): ?>

                            <?php
                            // Applicant's full name
                            $applicantName = trim(
                                ($application['first_name'] ?? '') . ' ' .
                                    ($application['last_name'] ?? '')
                            );

                            if ($applicantName === '') {
                                $applicantName = 'N/A';
                            }

                            // Support either application_id or id.
                            $applicationId = (int) (
                                $application['application_id']
                                ?? $application['id']
                                ?? 0
                            );

                            // URL for viewing the hired applicant's details.
                            $detailsUrl = 'index.php?page=hired-applicant&id='
                                . $applicationId;
                            ?>

                            <tr
                                class="hired-applicant-row"
                                data-href="<?= htmlspecialchars(
                                                $detailsUrl,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                tabindex="0"
                                role="link"
                                aria-label="<?= htmlspecialchars(
                                                'View hired applicant ' . $applicantName,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                                <td>
                                    <?php if ($applicationId > 0): ?>

                                        <a
                                            href="<?= htmlspecialchars(
                                                        $detailsUrl,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                            class="hired-applicant-name">
                                            <?= htmlspecialchars(
                                                $applicantName,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </a>

                                    <?php else: ?>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $applicantName,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $application['email'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $application['phone'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $application['position'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $application['department'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        number_format(
                                            (float) (
                                                $application['salary'] ?? 0
                                            ),
                                            2
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty-state-cell">
                                <i class="fas fa-folder-open empty-state-icon"></i>
                                No hired applicants found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>
    </div>

</div>

<style>
    .hired-applicant-row {
        cursor: pointer;
    }

    .hired-applicant-row:focus-visible {
        outline: 2px solid #2563eb;
        outline-offset: -2px;
    }

    .hired-applicant-name {
        font-weight: 600;
        color: #2563eb;
        text-decoration: none;
    }

    .hired-applicant-name:hover {
        text-decoration: underline;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const rows = document.querySelectorAll('.hired-applicant-row');

        rows.forEach(function(row) {

            const url = row.dataset.href;

            if (!url) {
                return;
            }

            // Clicking anywhere on the row opens applicant details.
            row.addEventListener('click', function(event) {

                // Let links and interactive controls perform their own actions.
                if (event.target.closest('a, button, input, select, textarea')) {
                    return;
                }

                window.location.href = url;
            });

            // Enter or Space opens the details for keyboard users.
            row.addEventListener('keydown', function(event) {

                // Avoid handling key events originating from the name link.
                if (event.target.closest('a, button, input, select, textarea')) {
                    return;
                }

                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    window.location.href = url;
                }
            });

        });

    });
</script>