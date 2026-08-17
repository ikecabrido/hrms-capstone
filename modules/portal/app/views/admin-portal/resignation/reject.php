<!-- REJECT MODAL -->
<div id="rejectModal"
    style="
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
                background:#f1f5f9;
                color:#64748b;
                display:flex;
                align-items:center;
                justify-content:center;
                font-size:15px;
            ">
                <i class="fas fa-times-circle"></i>
            </div>

            <div>

                <div style="
                    font-size:14px;
                    font-weight:700;
                    color:#0f172a;
                ">
                    Reject Resignation
                </div>

                <div style="
                    margin-top:3px;
                    font-size:10px;
                    color:#64748b;
                ">
                    Provide a reason before rejecting this request.
                </div>

            </div>

        </div>


        <!-- FORM -->
        <form
            method="POST"
            action="index.php?url=resignation-reject"
            onsubmit="return submitRejection(this)"
        >

            <input
                type="hidden"
                name="resignation_id"
                id="rejectResignationId"
            >


            <!-- BODY -->
            <div style="padding:22px;">

                <!-- EMPLOYEE -->
                <div style="
                    padding:13px;
                    background:#f8fafc;
                    border:1px solid #e2e8f0;
                    border-radius:10px;
                    margin-bottom:17px;
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

                    <div
                        id="rejectEmployeeName"
                        style="
                            font-size:12px;
                            font-weight:700;
                            color:#334155;
                        "
                    >
                        —
                    </div>

                </div>


                <!-- REASON -->
                <label style="
                    display:block;
                    margin-bottom:7px;
                    font-size:10px;
                    font-weight:700;
                    color:#334155;
                ">

                    Reason for Rejection

                    <span style="color:#dc2626;">
                        *
                    </span>

                </label>


                <textarea
                    name="hr_remarks"
                    id="rejectionReason"
                    required
                    placeholder="Enter the reason for rejecting this resignation request..."
                    style="
                        width:100%;
                        min-height:110px;
                        padding:11px 12px;
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
                        this.style.borderColor='#94a3b8';
                        this.style.boxShadow='0 0 0 3px rgba(148,163,184,.12)'
                    "
                    onblur="
                        this.style.borderColor='#cbd5e1';
                        this.style.boxShadow='none'
                    "
                ></textarea>

                <div style="
                    margin-top:8px;
                    font-size:9px;
                    color:#94a3b8;
                ">
                    This reason will be recorded in the resignation request history.
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
                    onclick="closeRejectModal()"
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
                        border:1px solid #dc2626;
                        border-radius:9px;
                        background:#dc2626;
                        color:#fff;
                        font-size:10px;
                        font-weight:700;
                        cursor:pointer;
                    "
                    onmouseover="this.style.background='#b91c1c'"
                    onmouseout="this.style.background='#dc2626'"
                >

                    <i class="fas fa-times"></i>

                    Confirm Rejection

                </button>

            </div>

        </form>

    </div>

</div>