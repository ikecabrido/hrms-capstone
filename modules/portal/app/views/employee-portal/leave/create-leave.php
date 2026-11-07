<?php
$today = date('Y-m-d');
?>
<style>
    /* ========================================
       LEAVE REQUEST PAGE
    ======================================== */

    .leave-request-page {
        width: 100%;
        max-width: 100%;
        margin: 20px auto 0;
    }

    .leave-request-page,
    .leave-request-page * {
        box-sizing: border-box;
    }

    /* Form Card */
    .leave-form-card {
        width: 100%;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(15, 23, 42, 0.04);
    }

    .leave-form-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        background: #ffffff;
    }

    .leave-form-header h3 {
        margin: 0;
        color: #1e293b;
        font-size: 16px;
        font-weight: 700;
    }

    .leave-form-header p {
        margin: 5px 0 0;
        color: #64748b;
        font-size: 12px;
        line-height: 1.5;
    }

    /* Form Body */
    .leave-form-body {
        padding: 24px;
    }

    .leave-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .leave-form-group {
        min-width: 0;
        margin-bottom: 20px;
    }

    .leave-form-group:last-child {
        margin-bottom: 0;
    }

    .leave-form-label {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 8px;
        color: #374151;
        font-size: 12px;
        font-weight: 600;
    }

    .leave-form-label i {
        color: #2563eb;
        font-size: 13px;
    }

    .leave-form-control {
        display: block;
        width: 100%;
        min-width: 0;
        height: 44px;
        padding: 0 13px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        outline: none;
        background: #ffffff;
        color: #374151;
        font-family: inherit;
        font-size: 13px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .leave-form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.10);
    }

    .leave-form-control::placeholder {
        color: #9ca3af;
    }

    .leave-form-control:disabled {
        background: #f8fafc;
        cursor: not-allowed;
    }

    select.leave-form-control {
        cursor: pointer;
    }

    textarea.leave-form-control {
        height: auto;
        min-height: 115px;
        padding: 12px 13px;
        line-height: 1.6;
        resize: vertical;
    }

    /* Supporting Document */
    .leave-upload-container {
        padding: 16px;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        background: #f8fafc;
        transition: border-color 0.2s, background 0.2s;
    }

    .leave-upload-container:focus-within {
        border-color: #3b82f6;
        background: #eff6ff;
    }

    .leave-upload-input {
        display: block;
        width: 100%;
        max-width: 100%;
        color: #475569;
        font-family: inherit;
        font-size: 12px;
    }

    .leave-upload-help {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 9px;
        color: #94a3b8;
        font-size: 11px;
    }

    /* Information Notice */
    .leave-info-notice {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        margin-top: 22px;
        padding: 13px 15px;
        border: 1px solid #dbeafe;
        border-radius: 9px;
        background: #eff6ff;
        color: #475569;
        font-size: 12px;
        line-height: 1.6;
    }

    .leave-info-notice i {
        margin-top: 2px;
        color: #2563eb;
        font-size: 15px;
    }

    /* Footer */
    .leave-form-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 10px;
        padding: 16px 24px;
        border-top: 1px solid #e5e7eb;
        background: #f8fafc;
    }

    .leave-form-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 39px;
        padding: 9px 17px;
        border: 1px solid transparent;
        border-radius: 8px;
        font-family: inherit;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .leave-form-btn-cancel {
        border-color: #d1d5db;
        background: #ffffff;
        color: #4b5563;
    }

    .leave-form-btn-cancel:hover {
        border-color: #9ca3af;
        background: #f1f5f9;
    }

    .leave-form-btn-submit {
        background: #2563eb;
        color: #ffffff;
        box-shadow: 0 2px 5px rgba(37, 99, 235, 0.18);
    }

    .leave-form-btn-submit:hover {
        background: #1d4ed8;
        box-shadow: 0 4px 9px rgba(37, 99, 235, 0.22);
    }

    .leave-form-btn:focus-visible {
        outline: 3px solid rgba(59, 130, 246, 0.25);
        outline-offset: 2px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .leave-request-page {
            margin-top: 15px;
        }

        .leave-form-header {
            padding: 18px;
        }

        .leave-form-body {
            padding: 20px 18px;
        }

        .leave-form-grid {
            gap: 14px;
        }

        .leave-form-footer {
            padding: 15px 18px;
        }
    }

    @media (max-width: 576px) {
        .leave-form-grid {
            grid-template-columns: 1fr;
            gap: 0;
        }

        .leave-form-body {
            padding: 18px 15px;
        }

        .leave-form-footer {
            flex-direction: column-reverse;
            align-items: stretch;
            padding: 15px;
        }

        .leave-form-btn {
            width: 100%;
        }

        .leave-form-header {
            padding: 17px 15px;
        }
    }
</style>

<div class="employee-dashboard">

    <!-- ========================================
         WELCOME SECTION
    ======================================== -->
    <section class="dashboard-welcome" id="leaveRequestWelcome">

        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                EMPLOYEE LEAVE REQUEST
            </span>

            <h1 class="welcome-title">Leave Request Form</h1>

            <p class="welcome-description">
                Submit and manage your leave requests, view request history,
                and check the approval status of your submitted leaves.
            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration">
            <i class="fas fa-calendar-minus"></i>
        </div>

    </section>


    <!-- ========================================
         NOTIFICATIONS
    ======================================== -->
    <?php require __DIR__ . '/../../partials/notification.php'; ?>


    <!-- ========================================
         LEAVE REQUEST FORM
    ======================================== -->
    <section class="leave-request-page">

        <div class="leave-form-card">

            <!-- FORM HEADER -->
            <div class="leave-form-header">

                <h3>
                    <i class="fas fa-file-signature"
                       style="color:#2563eb;margin-right:7px;"></i>
                    Leave Application Details
                </h3>

                <p>
                    Complete the information below to submit your leave request.
                </p>

            </div>


            <!-- FORM -->
            <form
                action="<?= htmlspecialchars(appUrl('index.php?url=leave-store'), ENT_QUOTES, 'UTF-8') ?>"
                method="POST"
                enctype="multipart/form-data"
                id="leaveRequestForm"
            >

                <div class="leave-form-body">

                    <!-- LEAVE TYPE -->
                    <div class="leave-form-group">

                        <label for="leave_type_id" class="leave-form-label">
                            <i class="fas fa-tag"></i>
                            Leave Type
                        </label>

                        <select
                            name="leave_type_id"
                            id="leave_type_id"
                            class="leave-form-control"
                            required
                        >

                            <option value="">Select leave type</option>

                            <?php foreach ($leaveTypes as $leaveType): ?>

                                <option value="<?= (int) $leaveType['leave_type_id'] ?>">
                                    <?= htmlspecialchars(
                                        $leaveType['leave_type_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- DATE FIELDS -->
                    <div class="leave-form-grid">

                        <!-- START DATE -->
                        <div class="leave-form-group">

                            <label for="start_date" class="leave-form-label">
                                <i class="far fa-calendar"></i>
                                Start Date
                            </label>

                            <input
                                type="date"
                                name="start_date"
                                id="start_date"
                                class="leave-form-control"
                                min="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >

                        </div>


                        <!-- END DATE -->
                        <div class="leave-form-group">

                            <label for="end_date" class="leave-form-label">
                                <i class="far fa-calendar-check"></i>
                                End Date
                            </label>

                            <input
                                type="date"
                                name="end_date"
                                id="end_date"
                                class="leave-form-control"
                                min="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- REASON -->
                    <div class="leave-form-group">

                        <label for="details" class="leave-form-label">
                            <i class="fas fa-comment-alt"></i>
                            Reason
                        </label>

                        <textarea
                            name="details"
                            id="details"
                            class="leave-form-control"
                            rows="4"
                            placeholder="Please provide the reason for your leave request..."
                            required
                        ></textarea>

                    </div>


                    <!-- SUPPORTING DOCUMENT -->
                    <div class="leave-form-group">

                        <label for="supporting_document" class="leave-form-label">

                            <i class="fas fa-paperclip"></i>

                            Supporting Document

                            <span style="
                                color:#94a3b8;
                                font-size:10px;
                                font-weight:400;
                            ">
                                (Optional)
                            </span>

                        </label>

                        <div class="leave-upload-container">

                            <input
                                type="file"
                                name="supporting_document"
                                id="supporting_document"
                                class="leave-upload-input"
                                accept=".pdf,.jpg,.jpeg,.png"
                            >

                            <div class="leave-upload-help">
                                <i class="fas fa-info-circle"></i>
                                Accepted formats: PDF, JPG, JPEG, PNG.
                            </div>

                        </div>

                    </div>


                    <!-- INFORMATION NOTICE -->
                    <div class="leave-info-notice">

                        <i class="fas fa-info-circle"></i>

                        <span>
                            Your leave request will be submitted for review.
                            The status will appear in your Leave History after
                            submission.
                        </span>

                    </div>

                </div>


                <!-- FORM FOOTER -->
                <div class="leave-form-footer">

                    <button
                        type="button"
                        class="leave-form-btn leave-form-btn-cancel"
                        data-bs-dismiss="modal"
                    >
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>


                    <button
                        type="submit"
                        class="leave-form-btn leave-form-btn-submit"
                    >
                        <i class="fas fa-paper-plane"></i>
                        Submit Request
                    </button>

                </div>

            </form>

        </div>

    </section>

</div>


<!-- ========================================
     DATE VALIDATION
======================================== -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const form = document.getElementById('leaveRequestForm');

    if (!startDate || !endDate || !form) {
        return;
    }

    // Update the minimum end date when start date changes.
    startDate.addEventListener('change', function () {

        if (!startDate.value) {
            return;
        }

        endDate.min = startDate.value;

        // Reset end date if it is earlier than the selected start date.
        if (endDate.value && endDate.value < startDate.value) {
            endDate.value = '';
        }

    });

    // Prevent end date from being earlier than start date.
    endDate.addEventListener('change', function () {

        if (
            startDate.value &&
            endDate.value &&
            endDate.value < startDate.value
        ) {
            endDate.setCustomValidity(
                'End date cannot be earlier than start date.'
            );
        } else {
            endDate.setCustomValidity('');
        }

    });

    // Final client-side date check before submission.
    form.addEventListener('submit', function (event) {

        if (
            startDate.value &&
            endDate.value &&
            endDate.value < startDate.value
        ) {
            event.preventDefault();

            endDate.setCustomValidity(
                'End date cannot be earlier than start date.'
            );

            endDate.reportValidity();
        } else {
            endDate.setCustomValidity('');
        }

    });

});
</script>
