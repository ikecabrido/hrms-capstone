<!-- SUBMIT GRIEVANCE MODAL -->
<div class="modal fade" id="submitGrievanceModal" tabindex="-1" aria-labelledby="submitGrievanceModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content" style="
            border:0;
            border-radius:16px;
            overflow:hidden;
            box-shadow:0 20px 50px rgba(0,0,0,.15);
        ">

            <!-- HEADER -->
            <div class="modal-header" style="
                padding:20px 24px;
                border-bottom:1px solid #eef2f7;
                background:#fff;
            ">

                <div>
                    <h5 id="submitGrievanceModalLabel" style="
                        margin:0;
                        color:#111827;
                        font-size:17px;
                        font-weight:700;
                    ">
                        Submit a Grievance
                    </h5>

                    <p style="
                        margin:5px 0 0;
                        color:#6b7280;
                        font-size:11px;
                    ">
                        Report a workplace concern or issue to HR.
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>

            </div>


            <!-- BODY -->
            <form method="POST" action="index.php?url=grievance-store" enctype="multipart/form-data">

                <div class="modal-body" style="
                    padding:24px;
                    background:#fff;
                ">

                    <!-- INFORMATION NOTICE -->
                    <div style="
                        display:flex;
                        gap:11px;
                        padding:13px 14px;
                        margin-bottom:22px;
                        border:1px solid #dbeafe;
                        border-radius:10px;
                        background:#eff6ff;
                    ">

                        <i class="fas fa-info-circle" style="
                                color:#2563eb;
                                font-size:14px;
                                margin-top:2px;
                            ">
                        </i>

                        <div style="
                            color:#374151;
                            font-size:10px;
                            line-height:1.6;
                        ">
                            Please provide an honest and clear description of
                            your concern. HR will review the grievance and
                            determine the appropriate action.
                        </div>

                    </div>


                    <!-- CATEGORY -->
                    <div style="margin-bottom:18px;">

                        <label for="category" style="
                            display:block;
                            margin-bottom:7px;
                            color:#374151;
                            font-size:11px;
                            font-weight:600;
                        ">
                            Category <span style="color:#dc2626;">*</span>
                        </label>

                        <select id="category" name="category" required style="
                                width:100%;
                                height:44px;
                                padding:0 13px;
                                border:1px solid #d1d5db;
                                border-radius:9px;
                                background:#fff;
                                color:#374151;
                                font-size:11px;
                                outline:none;
                            ">

                            <option value="">Select a category</option>

                            <option value="Workplace Conflict">
                                Workplace Conflict
                            </option>

                            <option value="Harassment">
                                Harassment
                            </option>

                            <option value="Discrimination">
                                Discrimination
                            </option>

                            <option value="Workplace Safety">
                                Workplace Safety
                            </option>

                            <option value="Management Concern">
                                Management Concern
                            </option>

                            <option value="Policy Violation">
                                Policy Violation
                            </option>

                            <option value="Work Environment">
                                Work Environment
                            </option>

                            <option value="Other">
                                Other
                            </option>

                        </select>

                    </div>


                    <!-- DESCRIPTION -->
                    <div style="margin-bottom:18px;">

                        <label for="grievanceDescription" style="
                            display:block;
                            margin-bottom:7px;
                            color:#374151;
                            font-size:11px;
                            font-weight:600;
                        ">
                            Describe your concern
                            <span style="color:#dc2626;">*</span>
                        </label>

                        <textarea id="grievanceDescription" name="description" required rows="6" maxlength="3000"
                            placeholder="Describe what happened, when it happened, and any relevant details..." style="
                                width:100%;
                                padding:12px 13px;
                                border:1px solid #d1d5db;
                                border-radius:9px;
                                resize:vertical;
                                color:#374151;
                                background:#fff;
                                font-size:11px;
                                line-height:1.6;
                                box-sizing:border-box;
                                outline:none;
                            "></textarea>

                        <div style="
                            display:flex;
                            justify-content:space-between;
                            margin-top:5px;
                            color:#9ca3af;
                            font-size:9px;
                        ">

                            <span>
                                Avoid including unnecessary personal information.
                            </span>

                            <span id="descriptionCounter">
                                0 / 3000
                            </span>

                        </div>

                    </div>


                    <!-- SUBJECT -->
                    <div style="margin-bottom:18px;">

                        <label for="grievanceSubject" style="
        display:block;
        margin-bottom:7px;
        color:#374151;
        font-size:11px;
        font-weight:600;
    ">
                            Subject
                        </label>

                        <input type="text" id="grievanceSubject" name="subject" list="grievanceSubjectSuggestions"
                            maxlength="150" placeholder="Type or select a subject" style="
            width:100%;
            height:44px;
            padding:0 13px;
            box-sizing:border-box;
            border:1px solid #d1d5db;
            border-radius:9px;
            color:#374151;
            background:#f9fafb;
            font-size:11px;
            outline:none;
        ">

                        <datalist id="grievanceSubjectSuggestions">

                            <option value="Workplace Conflict"></option>

                            <option value="Workplace Harassment Concern"></option>

                            <option value="Workplace Discrimination Concern"></option>

                            <option value="Management Concern"></option>

                            <option value="Workplace Safety Concern"></option>

                            <option value="Work Environment Concern"></option>

                            <option value="Attendance Concern"></option>

                            <option value="Work Schedule Concern"></option>

                            <option value="Payroll and Compensation Concern"></option>

                            <option value="Employee Benefits Concern"></option>

                            <option value="Leave Request Concern"></option>

                            <option value="Policy Violation Concern"></option>

                            <option value="Other Workplace Concern"></option>

                        </datalist>

                        <div style="
        margin-top:5px;
        color:#9ca3af;
        font-size:9px;
    ">
                            Select a suggested subject or type your own.
                        </div>

                    </div>


                    <!-- DATE + ATTACHMENT -->
                    <div style="
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
    margin-bottom:18px;
">

                        <!-- INCIDENT DATE -->
                        <div>

                            <label for="incidentDateDisplay" style="
            display:block;
            margin-bottom:8px;
            color:#374151;
            font-size:12px;
            font-weight:600;
        ">
                                Incident Date
                            </label>

                            <div style="position:relative;">

                                <input type="text" id="incidentDateDisplay" readonly placeholder="Select incident date"
                                    style="
                    width:100%;
                    height:46px;
                    padding:0 40px 0 13px;
                    box-sizing:border-box;
                    border:1px solid #d1d5db;
                    border-radius:9px;
                    background:#fff;
                    color:#374151;
                    font-size:12px;
                    font-weight:500;
                    cursor:pointer;
                    outline:none;
                ">

                                <i class="fas fa-calendar-alt" style="
                position:absolute;
                right:13px;
                top:50%;
                transform:translateY(-50%);
                color:#6b7280;
                font-size:13px;
                pointer-events:none;
            "></i>

                                <input type="date" id="incidentDate" name="incident_date" max="<?= date('Y-m-d') ?>"
                                    style="
                    position:absolute;
                    width:1px;
                    height:1px;
                    opacity:0;
                    pointer-events:none;
                ">

                            </div>

                            <div style="
            margin-top:5px;
            color:#9ca3af;
            font-size:9px;
        ">
                                Select the date when the incident occurred.
                            </div>

                        </div>


                        <!-- ATTACHMENT -->
                        <div>

                            <label for="grievanceAttachment" style="
            display:block;
            margin-bottom:8px;
            color:#374151;
            font-size:12px;
            font-weight:600;
        ">
                                Supporting Document
                                <span style="
                color:#9ca3af;
                font-weight:400;
            ">
                                    (Optional)
                                </span>
                            </label>

                            <input type="file" id="grievanceAttachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf"
                                style="
                width:100%;
                height:46px;
                padding:9px 11px;
                border:1px solid #d1d5db;
                border-radius:9px;
                background:#fff;
                color:#374151;
                font-size:10px;
                box-sizing:border-box;
            ">

                            <div style="
            margin-top:5px;
            color:#9ca3af;
            font-size:9px;
        ">
                                JPG, PNG, or PDF. Only attach relevant documents.
                            </div>

                        </div>

                    </div>


                    <!-- PRIVACY OPTIONS - FULL WIDTH -->
                    <div style="
    width:100%;
    box-sizing:border-box;
    padding:16px 18px;
    margin:0 0 4px 0;
    border:1px solid #e5e7eb;
    border-radius:10px;
    background:#fafafa;
">

                        <!-- HEADER -->
                        <div style="
        margin-bottom:14px;
        color:#374151;
        font-size:11px;
        font-weight:700;
    ">
                            Privacy Options
                        </div>


                        <!-- HORIZONTAL OPTIONS -->
                        <div style="
        display:flex;
        align-items:stretch;
        width:100%;
        gap:0;
    ">

                            <!-- ANONYMOUS -->
                            <div style="
            flex:1;
            display:flex;
            align-items:flex-start;
            gap:10px;
            padding-right:20px;
            box-sizing:border-box;
        ">

                                <input type="checkbox" id="anonymous" name="anonymous" value="1" style="
                    width:15px;
                    height:15px;
                    margin:2px 0 0;
                    flex-shrink:0;
                    cursor:pointer;
                    accent-color:#2563eb;
                ">

                                <label for="anonymous" style="
                    margin:0;
                    cursor:pointer;
                    flex:1;
                ">

                                    <div style="
                    color:#374151;
                    font-size:10px;
                    font-weight:600;
                ">
                                        Submit anonymously
                                    </div>

                                    <div style="
                    margin-top:3px;
                    color:#9ca3af;
                    font-size:9px;
                    line-height:1.5;
                ">
                                        Your identity will not be displayed as the complainant.
                                    </div>

                                </label>

                            </div>


                            <!-- DIVIDER -->
                            <div style="
            width:1px;
            background:#e5e7eb;
            margin:0 20px;
        "></div>


                            <!-- CONFIDENTIAL -->
                            <div style="
            flex:1;
            display:flex;
            align-items:flex-start;
            gap:10px;
            padding-left:0;
            box-sizing:border-box;
        ">

                                <input type="checkbox" id="confidential" name="confidential" value="1" checked style="
                    width:15px;
                    height:15px;
                    margin:2px 0 0;
                    flex-shrink:0;
                    cursor:pointer;
                    accent-color:#2563eb;
                ">

                                <label for="confidential" style="
                    margin:0;
                    cursor:pointer;
                    flex:1;
                ">

                                    <div style="
                    color:#374151;
                    font-size:10px;
                    font-weight:600;
                ">
                                        Keep grievance confidential
                                    </div>

                                    <div style="
                    margin-top:3px;
                    color:#9ca3af;
                    font-size:9px;
                    line-height:1.5;
                ">
                                        Restricts access to authorized HR personnel.
                                    </div>

                                </label>

                            </div>

                        </div>

                    </div>


                    <!-- FOOTER -->
                    <div class="modal-footer" style="
                    padding:14px 24px;
                    border-top:1px solid #eef2f7;
                    background:#fff;
                ">

                        <button type="button" data-bs-dismiss="modal" style="
                            padding:9px 16px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                            background:#fff;
                            color:#374151;
                            font-size:10px;
                            font-weight:600;
                            cursor:pointer;
                        ">
                            Cancel
                        </button>

                        <button type="submit" style="
                            padding:9px 18px;
                            border:0;
                            border-radius:8px;
                            background:#2563eb;
                            color:#fff;
                            font-size:10px;
                            font-weight:600;
                            cursor:pointer;
                        ">
                            <i class="fas fa-paper-plane" style="margin-right:5px;">
                            </i>
                            Submit Grievance
                        </button>

                    </div>

            </form>

        </div>
    </div>
</div>
<script src="/hrms-capstone/modules/portal/public/js/function/grievance.js"></script>