<div class="modal fade" id="complaintModal" tabindex="-1" aria-labelledby="complaintModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="
            border:0;
            border-radius:16px;
            overflow:hidden;
            box-shadow:0 15px 40px rgba(15,23,42,.15);
        ">

            <!-- HEADER -->
            <div class="modal-header" style="
                padding:20px 24px;
                border-bottom:1px solid #e5e7eb;
                background:#f8fafc;
            ">
                <div>
                    <div style="
                        color:#2563eb;
                        font-size:9px;
                        font-weight:700;
                        letter-spacing:.08em;
                        margin-bottom:4px;
                    ">EMPLOYEE RELATIONS</div>

                    <h5 class="modal-title" style="
                        margin:0;
                        color:#111827;
                        font-size:18px;
                        font-weight:700;
                    ">
                        Submit Employee Complaint
                    </h5>

                    <p style="
                        margin:4px 0 0;
                        color:#6b7280;
                        font-size:10px;
                    ">
                        Report a workplace concern or incident to HR.
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- FORM -->
            <form action="index.php?url=employee-complaints-store" method="POST">

                <!-- SCROLLABLE BODY -->
                <div class="modal-body  complaint-modal-body" style="
        padding:24px;
        max-height:70vh;
        overflow-y:auto;
    ">

                    <!-- PERSON BEING REPORTED -->
                    <div style="margin-bottom:20px;">

                        <div style="
                font-size:11px;
                font-weight:700;
                color:#111827;
                margin-bottom:12px;
            ">
                            <i class="fas fa-user-shield" style="color:#2563eb;margin-right:6px;"></i>
                            Person Being Reported
                        </div>

                        <div class="row g-3">

                            <!-- RESPONDENT -->
                            <div class="col-12">

                                <label class="form-label small fw-semibold">
                                    Respondent Employee
                                </label>

                                <select name="employee_id" class="form-select" required>

                                    <option value="">
                                        Select employee
                                    </option>

                                    <?php foreach ($employees as $employeeOption): ?>

                                        <option value="<?= htmlspecialchars($employeeOption['employee_id'] ?? '') ?>">
                                            <?= htmlspecialchars($employeeOption['first_name'] ?? '') ?>
                                            <?= htmlspecialchars($employeeOption['middle_name'] ?? '') ?>
                                            <?= htmlspecialchars($employeeOption['last_name'] ?? '') ?>
                                            (<?= htmlspecialchars($employeeOption['employee_code'] ?? '') ?>)
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <small class="text-muted d-block mt-1">
                                    Select the employee involved in the complaint.
                                </small>

                            </div>

                        </div>

                    </div>


                    <!-- INCIDENT DETAILS -->
                    <div style="
            padding-top:18px;
            border-top:1px solid #e5e7eb;
            margin-bottom:20px;
        ">

                        <div style="
                font-size:11px;
                font-weight:700;
                color:#111827;
                margin-bottom:12px;
            ">
                            <i class="fas fa-exclamation-circle" style="color:#2563eb;margin-right:6px;"></i>
                            Incident Details
                        </div>

                        <div class="row g-3">

                            <!-- COMPLAINT TYPE -->
                            <div class="col-md-6">

                                <label class="form-label small fw-semibold">
                                    Complaint Type
                                </label>

                                <select name="type" class="form-select" required>

                                    <option value="">
                                        Select complaint type
                                    </option>

                                    <option value="Misconduct">
                                        Misconduct
                                    </option>

                                    <option value="Harassment">
                                        Harassment
                                    </option>

                                    <option value="Absenteeism">
                                        Absenteeism
                                    </option>

                                    <option value="Workplace Conflict">
                                        Workplace Conflict
                                    </option>

                                    <option value="Discrimination">
                                        Discrimination
                                    </option>

                                    <option value="Other">
                                        Other
                                    </option>

                                </select>

                            </div>


                            <!-- SEVERITY -->
                            <div class="col-md-6">

                                <label class="form-label small fw-semibold">
                                    Severity
                                </label>

                                <select name="severity" class="form-select" required>

                                    <option value="">
                                        Select severity
                                    </option>

                                    <option value="Low">
                                        Low
                                    </option>

                                    <option value="Medium">
                                        Medium
                                    </option>

                                    <option value="High">
                                        High
                                    </option>

                                </select>

                            </div>


                            <!-- INCIDENT DATE -->
                            <div class="col-md-6">

                                <label class="form-label small fw-semibold">
                                    Incident Date
                                </label>

                                <input type="date" name="incident_date" class="form-control"
                                    value="<?= date('Y-m-d') ?>" required>

                            </div>


                            <!-- INCIDENT TIME -->
                            <div class="col-md-6">

                                <label class="form-label small fw-semibold">
                                    Incident Time
                                </label>

                                <input type="time" name="incident_time" class="form-control" value="<?= (new DateTime(
                                    'now',
                                    new DateTimeZone('Asia/Manila')
                                ))->format('H:i') ?>" required>

                            </div>


                            <!-- LOCATION -->
                            <div class="col-12">

                                <label class="form-label small fw-semibold">
                                    Location
                                </label>

                                <input type="text" name="location" class="form-control" list="incidentLocations"
                                    placeholder="Select or type incident location" required>

                                <datalist id="incidentLocations">

                                    <option value="Office">
                                    <option value="Classroom">
                                    <option value="Faculty Room">
                                    <option value="HR Office">
                                    <option value="Meeting Room">
                                    <option value="Campus Grounds">
                                    <option value="Online">
                                    <option value="Off Campus">
                                    <option value="Comfort Room">

                                </datalist>

                            </div>


                            <!-- TITLE -->
                            <div class="col-12">

                                <label class="form-label small fw-semibold">
                                    Complaint Title
                                </label>

                                <input type="text" name="title" class="form-control" maxlength="255"
                                    placeholder="Enter a short title describing the complaint" required>

                            </div>


                            <!-- DESCRIPTION -->
                            <div class="col-12">

                                <label class="form-label small fw-semibold">
                                    Complaint Description
                                </label>

                                <textarea name="description" class="form-control" rows="4"
                                    placeholder="Please provide a complete and factual description of the incident."
                                    required></textarea>

                                <small class="text-muted d-block mt-2">
                                    This complaint is a formal request for HR review.
                                    Please provide truthful, accurate, and complete
                                    information. Submit only complaints that you consider
                                    serious and require appropriate attention or action
                                    from HR.
                                </small>

                            </div>

                        </div>

                    </div>


                    <!-- FACTUAL STATEMENT -->
                    <div style="
            padding:13px 14px;
            border:1px solid #e5e7eb;
            border-radius:10px;
            background:#f8fafc;
        ">

                        <label class="d-flex align-items-start gap-2" style="cursor:pointer;">

                            <input type="checkbox" name="factual_confirmation" value="1" required
                                style="margin-top:2px;">

                            <span>

                                <strong style="
                        display:block;
                        color:#374151;
                        font-size:10px;
                    ">
                                    Confirmation of factual statement
                                </strong>

                                <small style="
                        display:block;
                        margin-top:3px;
                        color:#6b7280;
                        font-size:9px;
                        line-height:1.5;
                    ">
                                    I confirm that the information provided in this
                                    complaint is true and based on facts or events that
                                    I personally know or have reasonable basis to report.
                                    I understand that providing false or misleading
                                    information may be subject to appropriate action.
                                </small>

                            </span>

                        </label>

                    </div>

                </div>


                <!-- FOOTER -->
                <div class="modal-footer" style="
        display:flex;
        justify-content:flex-end;
        gap:8px;
        padding:14px 24px;
        border-top:1px solid #e5e7eb;
        background:#fff;
    ">

                    <button type="button" class="btn" data-bs-dismiss="modal" style="
                padding:8px 14px;
                border:1px solid #d1d5db;
                border-radius:9px;
                background:#fff;
                color:#374151;
                font-size:10px;
                font-weight:600;
            ">
                        Cancel
                    </button>


                    <button type="submit" class="btn" style="
                padding:9px 15px;
                border:0;
                border-radius:9px;
                background:#2563eb;
                color:#fff;
                font-size:10px;
                font-weight:600;
            ">

                        <i class="fas fa-paper-plane me-1"></i>
                        Submit Complaint

                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<style>
    .complaint-modal-body {
        padding: 24px;
        max-height: 70vh;
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }

    .complaint-modal-body::-webkit-scrollbar {
        width: 7px;
    }

    .complaint-modal-body::-webkit-scrollbar-track {
        background: transparent;
    }

    .complaint-modal-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .complaint-modal-body::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>