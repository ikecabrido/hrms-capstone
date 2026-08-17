<!-- APPROVE CONFIRMATION MODAL -->
<div id="approveModal" style="
    display:none;
    position:fixed;
    inset:0;
    z-index:10000;
    background:rgba(15,23,42,.55);
    align-items:center;
    justify-content:center;
    padding:20px;
">

    <div style="
        width:100%;
        max-width:460px;
        background:#fff;
        border-radius:16px;
        box-shadow:0 20px 50px rgba(15,23,42,.25);
        overflow:hidden;
        font-family:Arial,sans-serif;
    ">

        <!-- HEADER -->
        <div style="
            display:flex;
            align-items:center;
            gap:12px;
            padding:20px 22px;
            border-bottom:1px solid #e2e8f0;
        ">

            <div style="
                width:40px;
                height:40px;
                border-radius:10px;
                background:#ecfdf5;
                color:#059669;
                display:flex;
                align-items:center;
                justify-content:center;
                font-size:15px;
            ">
                <i class="fas fa-check-circle"></i>
            </div>

            <div>
                <div style="
                    font-size:14px;
                    font-weight:700;
                    color:#0f172a;
                ">
                    Approve Resignation
                </div>

                <div style="
                    margin-top:3px;
                    font-size:10px;
                    color:#64748b;
                ">
                    Confirm this resignation request
                </div>
            </div>

        </div>


        <!-- FORM -->
        <form method="POST" action="index.php?url=resignation-approve" onsubmit="return submitApproval(this)">

            <!-- BODY -->
            <div style="padding:22px;">

                <!-- RESIGNATION ID -->
                <div style="
    margin-top:4px;
    font-size:10px;
    color:#64748b;
">
    Resignation ID:
    <span id="approveResignationIdDisplay">—</span>
</div>

<input type="hidden" name="resignation_id" id="approveResignationId">

                <!-- EMPLOYEE -->
                <div style="
                    padding:13px;
                    background:#f8fafc;
                    border:1px solid #e2e8f0;
                    border-radius:10px;
                ">

                    <div style="
                        font-size:9px;
                        font-weight:700;
                        color:#94a3b8;
                        text-transform:uppercase;
                        margin-bottom:5px;
                    ">
                        Employee
                    </div>

                    <div id="approveEmployeeName" style="
                        font-size:12px;
                        font-weight:700;
                        color:#334155;
                    ">
                        —
                    </div>

                    <div id="approveLastWorkingDay" style="
                        margin-top:4px;
                        font-size:10px;
                        color:#64748b;
                    ">
                        Last Working Day: —
                    </div>

                </div>


                <!-- NOTICE -->
                <div style="
                    margin-top:14px;
                    padding:13px;
                    border:1px solid #d1fae5;
                    background:#f0fdf4;
                    border-radius:10px;
                ">

                    <div style="
                        display:flex;
                        gap:9px;
                        align-items:flex-start;
                    ">

                        <i class="fas fa-info-circle" style="
                            color:#059669;
                            font-size:13px;
                            margin-top:2px;
                        "></i>

                        <div style="
                            font-size:10px;
                            line-height:1.6;
                            color:#166534;
                        ">
                            Approving this request will move the employee
                            into the resignation and offboarding process.
                        </div>

                    </div>

                </div>


                <!-- APPROVAL REMARKS -->
                <div style="margin-top:16px;">

                    <label style="
                        display:block;
                        margin-bottom:7px;
                        font-size:10px;
                        font-weight:700;
                        color:#334155;
                    ">
                        Approval Remarks
                        <span style="
                            font-weight:400;
                            color:#94a3b8;
                        ">
                            (Optional)
                        </span>
                    </label>

                    <textarea
                        name="hr_remarks"
                        id="approvalRemarks"
                        placeholder="Enter any approval remarks..."
                        style="
                            width:100%;
                            min-height:90px;
                            padding:10px 11px;
                            box-sizing:border-box;
                            resize:vertical;
                            border:1px solid #cbd5e1;
                            border-radius:9px;
                            outline:none;
                            font-family:Arial,sans-serif;
                            font-size:11px;
                            color:#334155;
                        "
                        onfocus="
                            this.style.borderColor='#10b981';
                            this.style.boxShadow='0 0 0 3px rgba(16,185,129,.10)'
                        "
                        onblur="
                            this.style.borderColor='#cbd5e1';
                            this.style.boxShadow='none'
                        "
                    ></textarea>

                </div>

            </div>


            <!-- FOOTER -->
            <div style="
                display:flex;
                justify-content:flex-end;
                gap:9px;
                padding:15px 22px;
                background:#f8fafc;
                border-top:1px solid #e2e8f0;
            ">

                <button
                    type="button"
                    onclick="closeApproveModal()"
                    style="
                        padding:9px 14px;
                        border:1px solid #cbd5e1;
                        border-radius:9px;
                        background:#fff;
                        color:#475569;
                        font-size:10px;
                        font-weight:700;
                        cursor:pointer;
                    "
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    style="
                        display:inline-flex;
                        align-items:center;
                        gap:7px;
                        padding:9px 16px;
                        border:1px solid #059669;
                        border-radius:9px;
                        background:#059669;
                        color:#fff;
                        font-size:10px;
                        font-weight:700;
                        cursor:pointer;
                    "
                    onmouseover="this.style.background='#047857'"
                    onmouseout="this.style.background='#059669'"
                >
                    <i class="fas fa-check"></i>
                    Confirm Approval
                </button>

            </div>

        </form>

    </div>

</div>