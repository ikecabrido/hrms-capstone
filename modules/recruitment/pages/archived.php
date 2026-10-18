<div class="dashboard-content">

    <!-- Header Section -->
    <div class="module-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; gap: 16px; flex-wrap: wrap;">
        <div>
            <h2 class="module-title" style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-archive" style="color: #64748b; font-size: 1.25rem;"></i>
                Archived Applications
            </h2>
        </div>

        <a href="index.php?page=applications" class="btn-create-secondary">
            <i class="fas fa-arrow-left"></i>
            Back to Active
        </a>
    </div>

    <!-- Table Container -->
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th class="col-number">No.</th>
                    <th>Applicant Name</th>
                    <th>Applied For</th>
                    <th>Archived Date</th>
                    <th>Source</th>
                    <th class="action-column" style="text-align: center;">Restore</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="6" class="empty-state-cell">
                            <i class="fas fa-box-open empty-state-icon"></i>
                            <span style="font-size: 0.875rem; font-weight: 500; color: #64748b;">
                                Your archive is currently empty.
                            </span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; ?>
                    <?php foreach ($applications as $row): ?>
                        <tr>
                            <!-- No. -->
                            <td class="cell-number">
                                <span class="date-text"><?= $no++ ?></span>
                            </td>

                            <!-- Applicant Name -->
                            <td>
                                <div class="applicant-info">
                                    <span class="applicant-name">
                                        <?= htmlspecialchars(
                                            trim(
                                                ($row['first_name'] ?? '') . ' ' .
                                                    ($row['last_name'] ?? '')
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </div>
                            </td>

                            <!-- Applied For -->
                            <td>
                                <span class="job-badge">
                                    <?= htmlspecialchars($row['job_title'] ?? 'Position Removed', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <!-- Archived Date -->
                            <td>
                                <span class="date-text">
                                    <?php if (!empty($row['archived_at'])): ?>
                                        <?= htmlspecialchars(date("M d, Y", strtotime($row['archived_at'])), ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </span>
                            </td>

                            <!-- Source -->
                            <td>
                                <span class="source-tag">
                                    <?= htmlspecialchars($row['source'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <!-- Restore Button -->
                            <td class="action-cell" style="text-align: center;">
                                <div class="action-group" style="justify-content: center;">
                                    <a href="#"
                                        class="btn-action btn-restore restore-btn"
                                        data-id="<?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?>"
                                        title="Restore Applicant">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- JavaScript SweetAlert script remains untouched -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.querySelectorAll('.restore-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;

            Swal.fire({
                title: 'Restore application?',
                text: 'This will move the applicant back to active list.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, restore'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `index.php?page=restore-application&id=${id}`;
                }
            });
        });
    });
</script>