<div class="modal fade" id="createPayrollRequestModal" tabindex="-1" aria-labelledby="createPayrollRequestModalLabel"
    aria-hidden="true" style="z-index: index 1045;">
    <div class="modal-dialog modal-dialog-centered modal-lg">

        <div class="modal-content" style="
            border:0;
            border-radius:16px;
            overflow:hidden;
            box-shadow:0 15px 45px rgba(15,23,42,.18);
        ">

            <!-- HEADER -->
            <div style="
                padding:20px 24px;
                background:linear-gradient(135deg,#eff6ff,#ffffff);
                border-bottom:1px solid #e5e7eb;
            ">

                <div style="
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    gap:15px;
                ">

                    <div style="
                        display:flex;
                        align-items:center;
                        gap:12px;
                    ">

                        <div style="
                            width:42px;
                            height:42px;
                            min-width:42px;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            border-radius:11px;
                            background:#dbeafe;
                            color:#2563eb;
                            font-size:18px;
                        ">
                            <i class="fas fa-money-check-dollar"></i>
                        </div>

                        <div>

                            <h5 id="createPayrollRequestModalLabel" style="
                                    margin:0;
                                    color:#111827;
                                    font-size:18px;
                                    font-weight:700;
                                ">
                                Create Payroll Request
                            </h5>

                            <p style="
                                margin:3px 0 0;
                                color:#6b7280;
                                font-size:12px;
                            ">
                                Submit a request regarding your payroll.
                            </p>

                        </div>

                    </div>

                    <!-- CLOSE -->
                    <button type="button" data-bs-dismiss="modal" aria-label="Close" style="
                            width:32px;
                            height:32px;
                            border:0;
                            border-radius:8px;
                            background:#f1f5f9;
                            color:#64748b;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            cursor:pointer;
                        ">
                        <i class="fas fa-times"></i>
                    </button>

                </div>

            </div>


            <!-- FORM -->
            <form action="index.php?url=payroll-request-store" method="POST">

                <!-- BODY -->
                <div style="
                    padding:22px 24px;
                    background:#fff;
                ">

                    <!-- REQUEST TYPE -->
                    <div style="margin-bottom:16px;">

                        <label for="payroll_request_type" style="
                                display:block;
                                margin-bottom:6px;
                                color:#374151;
                                font-size:12px;
                                font-weight:600;
                            ">
                            <i class="fas fa-file-invoice-dollar" style="
                                margin-right:5px;
                                color:#2563eb;
                            "></i>

                            Request Type
                        </label>

                        <select name="request_type" id="payroll_request_type" required style="
                                width:100%;
                                height:42px;
                                padding:0 12px;
                                border:1px solid #d1d5db;
                                border-radius:9px;
                                background:#fff;
                                color:#374151;
                                font-size:13px;
                                box-sizing:border-box;
                                outline:none;
                            ">

                            <option value="">
                                Select request type
                            </option>

                            <option value="Payslip Request">
                                Payslip Request
                            </option>

                            <option value="Payroll Correction">
                                Payroll Correction
                            </option>

                            <option value="Missing Salary">
                                Missing Salary
                            </option>

                            <option value="Incorrect Deduction">
                                Incorrect Deduction
                            </option>

                            <option value="Other">
                                Other Payroll Concern
                            </option>

                        </select>

                    </div>


                    <!-- PAYROLL PERIOD -->
                    <div style="
                        display:grid;
                        grid-template-columns:repeat(2,minmax(0,1fr));
                        gap:14px;
                        margin-bottom:16px;
                    ">

                        <!-- PERIOD FROM -->
                        <div style="min-width:0;">

                            <label for="payroll_period_from" style="
                                    display:block;
                                    margin-bottom:6px;
                                    color:#374151;
                                    font-size:12px;
                                    font-weight:600;
                                ">
                                <i class="far fa-calendar" style="
                                    margin-right:5px;
                                    color:#2563eb;
                                "></i>

                                Payroll Period From
                            </label>

                            <input type="date" name="period_from" id="payroll_period_from" required style="
                                    width:100%;
                                    height:42px;
                                    padding:0 12px;
                                    border:1px solid #d1d5db;
                                    border-radius:9px;
                                    background:#fff;
                                    color:#374151;
                                    font-size:13px;
                                    box-sizing:border-box;
                                ">

                        </div>


                        <!-- PERIOD TO -->
                        <div style="min-width:0;">

                            <label for="payroll_period_to" style="
                                    display:block;
                                    margin-bottom:6px;
                                    color:#374151;
                                    font-size:12px;
                                    font-weight:600;
                                ">
                                <i class="far fa-calendar-check" style="
                                    margin-right:5px;
                                    color:#2563eb;
                                "></i>

                                Payroll Period To
                            </label>

                            <input type="date" name="period_to" id="payroll_period_to" required style="
                                    width:100%;
                                    height:42px;
                                    padding:0 12px;
                                    border:1px solid #d1d5db;
                                    border-radius:9px;
                                    background:#fff;
                                    color:#374151;
                                    font-size:13px;
                                    box-sizing:border-box;
                                ">

                        </div>

                    </div>


                    <!-- SUBJECT -->
                    <div style="margin-bottom:16px;">

                        <label for="payroll_subject" style="
                                display:block;
                                margin-bottom:6px;
                                color:#374151;
                                font-size:12px;
                                font-weight:600;
                            ">
                            <i class="fas fa-heading" style="
                                margin-right:5px;
                                color:#2563eb;
                            "></i>

                            Subject
                        </label>

                        <select name="subject" id="payroll_subject" required style="
    width:100%;
    height:42px;
    padding:0 12px;
    border:1px solid #d1d5db;
    border-radius:9px;
    background:#fff;
    color:#374151;
    font-size:13px;
    box-sizing:border-box;
">
                            <option value="" disabled selected>Select the subject of your request</option>
                            <option value="Regular Payroll Processing">Regular Payroll Processing</option>
                            <option value="Salary Discrepancy / Correction">Salary Discrepancy / Correction</option>
                            <option value="New Hire Payroll Onboarding">New Hire Payroll Onboarding</option>
                            <option value="Salary Advance Request">Salary Advance Request</option>
                            <option value="Other / General Inquiry">Other / General Inquiry</option>
                        </select>

                    </div>


                    <!-- DESCRIPTION -->
                    <div>

                        <label for="payroll_description" style="
                                display:block;
                                margin-bottom:6px;
                                color:#374151;
                                font-size:12px;
                                font-weight:600;
                            ">
                            <i class="fas fa-comment-alt" style="
                                margin-right:5px;
                                color:#2563eb;
                            "></i>

                            Details
                        </label>

                        <textarea name="description" id="payroll_description" rows="4" required
                            placeholder="Please describe your payroll concern or request..." style="
                                width:100%;
                                padding:11px 12px;
                                border:1px solid #d1d5db;
                                border-radius:9px;
                                background:#fff;
                                color:#374151;
                                font-size:13px;
                                line-height:1.5;
                                resize:vertical;
                                box-sizing:border-box;
                            "></textarea>

                    </div>


                    <!-- INFORMATION -->
                    <div style="
                        display:flex;
                        align-items:flex-start;
                        gap:9px;
                        margin-top:14px;
                        padding:10px 12px;
                        border-radius:9px;
                        background:#f8fafc;
                        border:1px solid #e5e7eb;
                        color:#64748b;
                        font-size:11px;
                        line-height:1.5;
                    ">

                        <i class="fas fa-info-circle" style="
                            margin-top:2px;
                            color:#2563eb;
                        "></i>

                        <span>
                            Your payroll request will be submitted for review.
                            You can monitor its status from your payroll request history.
                        </span>

                    </div>

                </div>


                <!-- FOOTER -->
                <div style="
                    display:flex;
                    justify-content:flex-end;
                    align-items:center;
                    gap:8px;
                    padding:14px 24px;
                    background:#f8fafc;
                    border-top:1px solid #e5e7eb;
                ">

                    <!-- CANCEL -->
                    <button type="button" data-bs-dismiss="modal" style="
                            display:inline-flex;
                            align-items:center;
                            justify-content:center;
                            gap:6px;
                            padding:8px 14px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                            background:#fff;
                            color:#4b5563;
                            font-size:12px;
                            font-weight:600;
                            cursor:pointer;
                        ">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>


                    <!-- SUBMIT -->
                    <button type="submit" style="
                            display:inline-flex;
                            align-items:center;
                            justify-content:center;
                            gap:7px;
                            padding:9px 16px;
                            border:0;
                            border-radius:8px;
                            background:#2563eb;
                            color:#fff;
                            font-size:12px;
                            font-weight:600;
                            cursor:pointer;
                            box-shadow:0 2px 5px rgba(37,99,235,.20);
                        ">
                        <i class="fas fa-paper-plane"></i>
                        Submit Request
                    </button>

                </div>

            </form>

        </div>

    </div>
</div>