<div class="module-header">
    <h2 class="module-title">Job Applications</h2>

    <p class="module-description">
        Manage and review all incoming candidate submissions.
    </p>

    <div class="header-actions">
        <a href="index.php?page=create-new-applicant" class="btn-create-primary">
            <i class="fas fa-user-plus"></i> New Applicant
        </a>

        <a href="index.php?page=archived-applications" class="btn-create-secondary">
            <i class="fas fa-box-archive"></i> Archived
        </a>
    </div>
</div>

<div class="module-content">

    <?php $statusFilter = $_GET['status'] ?? 'pending'; ?>

    <div class="table-container">

        <div
            class="table-filter-bar"
            style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">

            <div
                style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">

                <a
                    href="index.php?page=applications&status=all"
                    class="filter-pill <?= $statusFilter === 'all' ? 'active-all' : '' ?>">
                    All
                </a>

                <a
                    href="index.php?page=applications&status=pending"
                    class="filter-pill <?= $statusFilter === 'pending' ? 'active-pending' : '' ?>">
                    Pending
                </a>

                <a
                    href="index.php?page=applications&status=approved"
                    class="filter-pill <?= $statusFilter === 'approved' ? 'active-approved' : '' ?>">
                    Approved
                </a>

                <a
                    href="index.php?page=applications&status=rejected"
                    class="filter-pill <?= $statusFilter === 'rejected' ? 'active-rejected' : '' ?>">
                    Rejected
                </a>

            </div>

            <div style="margin-left:auto;">
                <input
                    type="search"
                    id="applicationSearch"
                    placeholder="Search by ID, name, or job title"
                    style="
                        width: 290px;
                        max-width: 100%;
                        padding: 10px 12px;
                        border: 1px solid #dbe3ee;
                        border-radius: 8px;
                        font-size: 14px;
                        background: #fff;
                    ">
            </div>

        </div>

        <table class="admin-table" id="applicationsTable">

            <thead>
                <tr>
                    <th class="col-number">No.</th>
                    <th>Applicant Details</th>
                    <th>Job Title</th>
                    <th>Applied On</th>
                    <th class="col-resume">Resume</th>
                    <th>Source</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($applications)): ?>

                    <tr>
                        <td colspan="7" class="empty-state-cell">
                            <i class="fas fa-folder-open empty-state-icon"></i>
                            No applications found yet.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php $no = 1; ?>

                    <?php foreach ($applications as $row): ?>

                        <?php
                        $status = strtolower(
                            trim($row['status'] ?? 'pending')
                        );

                        if ($status === 'approved') {

                            $statusStyle =
                                'background: #dcfce7; color: #166534;';
                        } elseif ($status === 'rejected') {

                            $statusStyle =
                                'background: #fee2e2; color: #991b1b;';
                        } else {

                            $statusStyle =
                                'background: #fef9c3; color: #854d0e;';
                        }

                        $isProcessed = in_array(
                            $status,
                            ['approved', 'rejected'],
                            true
                        );

                        /*
                         * Build the applicant name safely.
                         */
                        $applicantName = trim(
                            ($row['first_name'] ?? '') . ' ' .
                                ($row['last_name'] ?? '')
                        );

                        /*
                         * =====================================================
                         * RESUME PATH
                         * =====================================================
                         *
                         * Expected database value:
                         *
                         * uploads/2/resume/
                         * resume_20260922_140012_a1b2c3d4.pdf
                         *
                         * We use the stored relative path directly.
                         */

                        $resumeUrl = null;

                        if (!empty($row['resume'])) {

                            $storedResumePath = trim($row['resume']);

                            /*
                             * Normalize Windows backslashes to URL slashes.
                             */
                            $storedResumePath = str_replace(
                                '\\',
                                '/',
                                $storedResumePath
                            );

                            /*
                             * Remove accidental leading slash.
                             */
                            $storedResumePath = ltrim(
                                $storedResumePath,
                                '/'
                            );

                            /*
                             * The stored database path should normally be:
                             *
                             * uploads/{applicationId}/resume/file.pdf
                             *
                             * Since this page is:
                             *
                             * modules/recruitment/pages/applications.php
                             *
                             * and index.php is:
                             *
                             * modules/recruitment/index.php
                             *
                             * the correct browser-relative path is:
                             *
                             * uploads/...
                             *
                             * when the browser is currently on index.php.
                             */

                            $resumeUrl =
                                htmlspecialchars(
                                    $storedResumePath,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                        }
                        ?>

                        <tr
                            class="applicant-row"
                            data-href="index.php?page=application&id=<?= (int) $row['id']; ?>"
                            tabindex="0"
                            role="link"
                            aria-label="View applicant <?= htmlspecialchars(
                                                            $applicantName,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>"
                            onclick="
                                if (!event.target.closest('a, button')) {
                                    window.location.href = this.dataset.href;
                                }
                            "
                            onkeydown="
                                if (
                                    (event.key === 'Enter' || event.key === ' ') &&
                                    !event.target.closest('a, button')
                                ) {
                                    event.preventDefault();
                                    window.location.href = this.dataset.href;
                                }
                            ">

                            <!-- NUMBER -->
                            <td>
                                <span class="cell-number">
                                    <?= $no++ ?>
                                </span>
                            </td>

                            <!-- APPLICANT -->
                            <td>
                                <div class="applicant-info">

                                    <span class="applicant-name">
                                        <?= htmlspecialchars(
                                            $applicantName,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <span class="applicant-email">
                                        <i class="fas fa-envelope"></i>

                                        <?= htmlspecialchars(
                                            $row['email'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                </div>
                            </td>

                            <!-- JOB -->
                            <td>
                                <span class="job-badge">
                                    <?= htmlspecialchars(
                                        $row['job_title'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </td>

                            <!-- DATE -->
                            <td>
                                <span class="date-text">

                                    <?php if (!empty($row['created_at'])): ?>

                                        <?= htmlspecialchars(
                                            date(
                                                "M d, Y",
                                                strtotime($row['created_at'])
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    <?php else: ?>

                                        N/A

                                    <?php endif; ?>

                                </span>
                            </td>

                            <!-- RESUME -->
                            <td class="cell-resume">

                                <?php if ($resumeUrl !== null): ?>

                                    <a
                                        href="<?= $resumeUrl ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn-action btn-resume"
                                        title="View PDF Resume"
                                        onclick="event.stopPropagation();">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>

                                <?php else: ?>

                                    <span class="no-file-text">
                                        No file
                                    </span>

                                <?php endif; ?>

                            </td>

                            <!-- SOURCE -->
                            <td>
                                <span class="source-tag">
                                    <?= htmlspecialchars(
                                        $row['source'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </td>

                            <!-- STATUS -->
                            <td>
                                <span
                                    class="status-badge"
                                    style="<?= $statusStyle ?>">
                                    <?= ucfirst(
                                        htmlspecialchars(
                                            $row['status'] ?? 'pending',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                    ) ?>
                                </span>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>