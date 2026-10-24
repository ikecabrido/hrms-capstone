<?php
$viewData = get_defined_vars();
$applicants = is_array($viewData['applicants'] ?? null) ? $viewData['applicants'] : [];
?>
<?php if (!empty($_SESSION['success'])): ?>

    <div id="successMessage" class="onboarding-alert success">
        <i class="fas fa-check-circle"></i>

        <span>
            <?= htmlspecialchars($_SESSION['success']) ?>
        </span>

        <button type="button" onclick="this.parentElement.remove()">
            ×
        </button>
    </div>

    <?php unset($_SESSION['success']); ?>

<?php endif; ?>


<?php if (!empty($_SESSION['error'])): ?>

    <div id="errorMessage" class="onboarding-alert error">
        <i class="fas fa-exclamation-circle"></i>

        <span>
            <?= htmlspecialchars($_SESSION['error']) ?>
        </span>

        <button type="button" onclick="this.parentElement.remove()">
            ×
        </button>
    </div>

    <?php unset($_SESSION['error']); ?>

<?php endif; ?>
<div class="dashboard-content onboarding-selection-page">
    <div class="page-header onboarding-selection-header">
        <div>
            <h2>New Employee Onboarding</h2>
            <p>Initialize the onboarding process for successfully hired candidates.</p>
        </div>
    </div>

    <div class="form-container onboarding-selection-card">
        <form method="POST" action="index.php?page=onboarding-store" class="admin-form onboarding-selection-form">

            <div class="onboarding-selection-field onboarding-selection-field-full">
                <label for="applicantSelect">Select Candidate to Onboard</label>
                <select name="application_id" id="applicantSelect" class="onboarding-selection-control" required>
                    <option value="">-- Search for an applicant --</option>
                    <?php foreach ($applicants as $a): ?>
                        <option
                            value="<?= $a['id'] ?>"
                            data-name="<?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?>"
                            data-job="<?= htmlspecialchars($a['base_job']) ?>"
                            data-position="<?= htmlspecialchars($a['position']) ?>"
                            data-department="<?= htmlspecialchars($a['department']) ?>">
                            <?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?> — <?= htmlspecialchars($a['position']) ?> — <?= htmlspecialchars($a['department']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <hr class="onboarding-selection-divider">

            <div class="onboarding-selection-grid onboarding-selection-grid-three">
                <div class="onboarding-selection-field">
                    <label for="full_name">Full Name</label>
                    <input type="text" name="full_name" id="full_name" class="onboarding-selection-control onboarding-selection-readonly" readonly>
                </div>
                <div class="onboarding-selection-field">
                    <label for="base_job">Department Group</label>
                    <input type="text" name="base_job" id="base_job" class="onboarding-selection-control onboarding-selection-readonly" readonly>
                </div>
                <div class="onboarding-selection-field">
                    <label for="position">Designated Position</label>
                    <input type="text" name="position" id="position" class="onboarding-selection-control onboarding-selection-readonly" readonly>
                </div>
            </div>

            <div class="onboarding-selection-grid onboarding-selection-grid-two">
                <div class="onboarding-selection-field">
                    <label for="department">Assigned Department</label>
                    <input type="text" name="department" id="department" class="onboarding-selection-control onboarding-selection-readonly" readonly>
                </div>
                <div class="onboarding-selection-field">
                    <label for="location">Work Location</label>
                    <input type="text" name="location" id="location" class="onboarding-selection-control" placeholder="e.g. Building A, 3rd Floor" required>
                </div>
            </div>

            <div class="onboarding-selection-grid onboarding-selection-grid-two">
                <div class="onboarding-selection-field">
                    <label for="start_date">Official Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="onboarding-selection-control" required>
                </div>
                <div class="onboarding-selection-field">
                    <label for="mentor">Assigned Mentor/Supervisor</label>
                    <input type="text" name="mentor" id="mentor" class="onboarding-selection-control" placeholder="Enter name of mentor" required>
                </div>
            </div>

            <div class="onboarding-selection-actions">
                <button type="button" class="onboarding-selection-cancel" onclick="window.history.back()">Cancel</button>
                <button type="submit" class="onboarding-selection-submit">
                    <i class="fas fa-user-plus"></i> Finalize Onboarding
                </button>
            </div>
        </form>
    </div>
</div>