<?php
$applications = $applications ?? [];
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div style="background:#d1fae5; padding:10px; border-radius:5px; margin-bottom:15px;">
        <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div style="background:#fee2e2; padding:10px; border-radius:5px; margin-bottom:15px;">
        <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="dashboard-content">

    <div class="page-header">
        <h2>Send Job Offer</h2>
        <p>Finalize terms for candidates ready for an offer.</p>
    </div>

    <div class="form-container"
        style="background:white; padding:25px; border-radius:8px;
        box-shadow:0 2px 10px rgba(0,0,0,0.1); margin-bottom:40px;">

        <form method="POST" action="index.php?page=send-offer" data-skip>

            <!-- Applicant Selection -->

            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px; font-weight:600;">
                    Selected Applicant
                </label>

                <select
                    name="application_id"
                    id="applicationSelect"
                    required
                    style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:5px;">

                    <option value="">
                        -- Select Candidate Ready for Offer --
                    </option>

                    <?php foreach ($applications as $candidate): ?>

                        <?php
                        $disabled = '';
                        $note = '';

                        if (!empty($candidate['offer_status'])) {

                            if (
                                $candidate['offer_status'] === 'Accepted' ||
                                $candidate['offer_status'] === 'Rejected'
                            ) {
                                $disabled = 'disabled';
                                $note = ' (Finalized)';
                            } elseif ($candidate['offer_status'] === 'Sent') {
                                $disabled = 'disabled';
                                $note = ' (Offer Sent)';
                            }
                        }
                        ?>

                        <option
                            value="<?= (int) $candidate['id'] ?>"
                            <?= $disabled ?>
                            data-job="<?= htmlspecialchars($candidate['job_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-position="<?= htmlspecialchars($candidate['position'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-department="<?= htmlspecialchars($candidate['department'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-minimum-salary="<?= htmlspecialchars($candidate['minimum_salary'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-midpoint-salary="<?= htmlspecialchars($candidate['midpoint_salary'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-maximum-salary="<?= htmlspecialchars($candidate['maximum_salary'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars(
                                ($candidate['first_name'] ?? '') . ' ' .
                                    ($candidate['last_name'] ?? '') .
                                    $note,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </div>



            <!-- Auto-filled Information -->
            <div style="
                display:grid;
                grid-template-columns:1fr 1fr;
                gap:20px;
            ">

                <!-- Base Job -->
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:600;">
                        Base Job Title
                    </label>

                    <input
                        type="text"
                        id="jobTitleDisplay"
                        readonly
                        style="width:100%; padding:10px; background:#f1f5f9;
                        border:1px solid #cbd5e1; border-radius:5px;">

                    <input
                        type="hidden"
                        name="base_job"
                        id="jobTitle">
                </div>

                <!-- Department -->
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:600;">
                        Department
                    </label>

                    <input
                        type="text"
                        name="department"
                        id="departmentInput"
                        readonly
                        required
                        style="width:100%; padding:10px; background:#f1f5f9;
                        border:1px solid #cbd5e1; border-radius:5px;">
                </div>

                <!-- Position -->
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:600;">
                        Specific Position / Role
                    </label>

                    <input
                        type="text"
                        name="position"
                        id="positionInput"
                        readonly
                        required
                        style="width:100%; padding:10px; background:#f1f5f9;
                        border:1px solid #cbd5e1; border-radius:5px;">
                </div>

                <!-- Salary -->


                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:600;">
                        Monthly Salary Offer
                    </label>

                    <select
                        name="salary"
                        id="salaryOffer"
                        required
                        style="
            width:100%;
            padding:10px;
            background:#f1f5f9;
            border:1px solid #cbd5e1;
            border-radius:5px;
        ">
                        <option value="">
                            -- Select Salary Offer --
                        </option>

                        <option value="" id="minimumSalaryOption">
                            Minimum
                        </option>

                        <option value="" id="midpointSalaryOption">
                            Midpoint
                        </option>

                        <option value="" id="maximumSalaryOption">
                            Maximum
                        </option>
                    </select>
                </div>


                <!-- Benefit Package -->
                <div style="margin-top:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;">
                        Benefit Package
                    </label>

                    <textarea
                        name="benefit_package"
                        rows="3"
                        placeholder="List health insurance, bonuses, etc."
                        style="width:100%; padding:10px; border:1px solid #cbd5e1;
                    border-radius:5px;"></textarea>
                </div>

                <!-- Additional Notes -->
                <div style="margin-top:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;">
                        Additional Notes
                    </label>

                    <textarea
                        name="additional_note"
                        rows="3"
                        placeholder="Onboarding instructions or special conditions..."
                        style="width:100%; padding:10px; border:1px solid #cbd5e1;
                    border-radius:5px;"></textarea>
                </div>

                <!-- Submit -->
                <div style="margin-top:20px; text-align:right;">
                    <button
                        type="submit"
                        class="btn-create"
                        style="background:#6366f1; padding:12px 25px;">

                        <i class="fas fa-paper-plane"></i>
                        Dispatch Offer Letter
                    </button>
                </div>

        </form>
    </div>
</div>