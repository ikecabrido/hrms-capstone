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
<div class="dashboard-content">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0;">
        <div>
            <h2 style="margin: 0; color: #1e293b; font-size: 1.5rem;">🚀 New Employee Onboarding</h2>
            <p style="margin: 5px 0 0 0; color: #64748b;">Initialize the onboarding process for successfully hired candidates.</p>
        </div>
    </div>

    <div class="form-container" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
        <form method="POST" action="index.php?page=onboarding-store" class="admin-form">

            <div class="form-group" style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #475569;">Select Candidate to Onboard</label>
                <select name="application_id" id="applicantSelect" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 15px;">
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

            <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 25px 0;">

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label style="font-size: 13px; color: #64748b;">Full Name</label>
                    <input type="text" name="full_name" id="full_name" readonly style="background: #f8fafc; border: 1px solid #e2e8f0; width: 100%; padding: 10px; border-radius: 6px; color: #1e293b; font-weight: 600;">
                </div>
                <div class="form-group">
                    <label style="font-size: 13px; color: #64748b;">Department Group</label>
                    <input type="text" name="base_job" id="base_job" readonly style="background: #f8fafc; border: 1px solid #e2e8f0; width: 100%; padding: 10px; border-radius: 6px;">
                </div>
                <div class="form-group">
                    <label style="font-size: 13px; color: #64748b;">Designated Position</label>
                    <input type="text" name="position" id="position" readonly style="background: #f8fafc; border: 1px solid #e2e8f0; width: 100%; padding: 10px; border-radius: 6px;">
                </div>
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label style="font-size: 13px; color: #64748b;">Assigned Department</label>
                    <input type="text" name="department" id="department" readonly style="background: #f8fafc; border: 1px solid #e2e8f0; width: 100%; padding: 10px; border-radius: 6px;">
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; color: #475569;">Work Location</label>
                    <input type="text" name="location" placeholder="e.g. Building A, 3rd Floor" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px;">
                </div>
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                <div class="form-group">
                    <label style="font-weight: 600; color: #475569;">Official Start Date</label>
                    <input type="date" name="start_date" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px;">
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; color: #475569;">Assigned Mentor/Supervisor</label>
                    <input type="text" name="mentor" placeholder="Enter name of mentor" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px;">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                <button type="button" onclick="window.history.back()" style="background: white; border: 1px solid #e2e8f0; color: #64748b; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">Cancel</button>
                <button type="submit" style="background: #2563eb; color: white; border: none; padding: 12px 30px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-user-plus"></i> Finalize Onboarding
                </button>
            </div>
        </form>
    </div>
</div>