<div class="modal fade"
    id="viewPayrollRequestModal"
    tabindex="-1"
    aria-labelledby="viewPayrollRequestModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-lg">

        <div class="modal-content" style="
            border:0;
            border-radius:14px;
            overflow:hidden;
            box-shadow:0 10px 30px rgba(0,0,0,.12);
        ">

            <!-- HEADER -->
            <div class="modal-header" style="
                padding:18px 20px;
                border-bottom:1px solid #e5e7eb;
                background:#ffffff;
            ">

                <div style="
                    display:flex;
                    align-items:center;
                    gap:11px;
                ">

                    <div style="
                        width:40px;
                        height:40px;
                        min-width:40px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        border-radius:10px;
                        background:#eff6ff;
                        color:#2563eb;
                        font-size:16px;
                    ">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>

                    <div>
                        <h5 id="viewPayrollRequestModalLabel" style="
                            margin:0;
                            color:#111827;
                            font-size:16px;
                            font-weight:700;
                        ">
                            Payroll Request Details
                        </h5>

                        <p style="
                            margin:3px 0 0;
                            color:#6b7280;
                            font-size:11px;
                        ">
                            View the details of your payroll request.
                        </p>
                    </div>

                </div>

                <button type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>

            </div>


            <!-- BODY -->
            <div class="modal-body" style="
                padding:20px;
                background:#ffffff;
            ">

                <!-- STATUS -->
                <div style="
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    gap:12px;
                    padding:12px 14px;
                    margin-bottom:18px;
                    border:1px solid #e5e7eb;
                    border-radius:10px;
                    background:#f8fafc;
                ">

                    <div style="
                        display:flex;
                        align-items:center;
                        gap:8px;
                    ">

                        <i class="fas fa-circle-info"
                            style="
                                color:#2563eb;
                                font-size:14px;
                            ">
                        </i>

                        <span style="
                            color:#374151;
                            font-size:11px;
                            font-weight:600;
                        ">
                            Request Status
                        </span>

                    </div>

                    <span id="viewRequestStatus" style="
                        display:inline-flex;
                        align-items:center;
                        gap:5px;
                        padding:5px 10px;
                        border-radius:20px;
                        background:#fef3c7;
                        color:#92400e;
                        font-size:10px;
                        font-weight:700;
                    ">
                        <i class="fas fa-clock" style="font-size:9px;"></i>
                        Pending
                    </span>

                </div>


                <!-- REQUEST INFORMATION -->
                <div style="
                    margin-bottom:18px;
                ">

                    <div style="
                        margin-bottom:10px;
                        color:#111827;
                        font-size:12px;
                        font-weight:700;
                    ">
                        Request Information
                    </div>


                    <div class="row g-3">

                        <!-- REQUEST TYPE -->
                        <div class="col-md-6">

                            <div style="
                                padding:12px;
                                border:1px solid #e5e7eb;
                                border-radius:10px;
                                background:#ffffff;
                            ">

                                <div style="
                                    margin-bottom:5px;
                                    color:#6b7280;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                                    Request Type
                                </div>

                                <div id="viewRequestType" style="
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:600;
                                ">
                                    -
                                </div>

                            </div>

                        </div>


                        <!-- PURPOSE -->
                        <div class="col-md-6">

                            <div style="
                                padding:12px;
                                border:1px solid #e5e7eb;
                                border-radius:10px;
                                background:#ffffff;
                            ">

                                <div style="
                                    margin-bottom:5px;
                                    color:#6b7280;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                                    Purpose
                                </div>

                                <div id="viewRequestPurpose" style="
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:600;
                                ">
                                    -
                                </div>

                            </div>

                        </div>


                        <!-- PAYROLL PERIOD -->
                        <div class="col-md-6">

                            <div style="
                                padding:12px;
                                border:1px solid #e5e7eb;
                                border-radius:10px;
                                background:#ffffff;
                            ">

                                <div style="
                                    margin-bottom:5px;
                                    color:#6b7280;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                                    Payroll Period
                                </div>

                                <div id="viewPayrollPeriod" style="
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:600;
                                ">
                                    -
                                </div>

                            </div>

                        </div>


                        <!-- REQUESTED DATE -->
                        <div class="col-md-6">

                            <div style="
                                padding:12px;
                                border:1px solid #e5e7eb;
                                border-radius:10px;
                                background:#ffffff;
                            ">

                                <div style="
                                    margin-bottom:5px;
                                    color:#6b7280;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                                    Requested Date
                                </div>

                                <div id="viewRequestedDate" style="
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:600;
                                ">
                                    -
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- REMARKS -->
                <div style="
                    margin-bottom:18px;
                ">

                    <div style="
                        margin-bottom:8px;
                        color:#111827;
                        font-size:12px;
                        font-weight:700;
                    ">
                        Remarks
                    </div>

                    <div id="viewRequestRemarks" style="
                        min-height:70px;
                        padding:12px;
                        border:1px solid #e5e7eb;
                        border-radius:10px;
                        background:#f8fafc;
                        color:#4b5563;
                        font-size:12px;
                        line-height:1.6;
                    ">
                        -
                    </div>

                </div>


                <!-- PROCESSING INFORMATION -->
                <div id="processingInformation" style="
                    display:none;
                    margin-bottom:18px;
                ">

                    <div style="
                        margin-bottom:10px;
                        color:#111827;
                        font-size:12px;
                        font-weight:700;
                    ">
                        Processing Information
                    </div>

                    <div class="row g-3">

                        <!-- PROCESSED DATE -->
                        <div class="col-md-6">

                            <div style="
                                padding:12px;
                                border:1px solid #e5e7eb;
                                border-radius:10px;
                                background:#ffffff;
                            ">

                                <div style="
                                    margin-bottom:5px;
                                    color:#6b7280;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                                    Processed Date
                                </div>

                                <div id="viewProcessedDate" style="
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:600;
                                ">
                                    -
                                </div>

                            </div>

                        </div>


                        <!-- REJECTION REASON -->
                        <div class="col-md-6" id="rejectionReasonContainer"
                            style="display:none;">

                            <div style="
                                padding:12px;
                                border:1px solid #fecaca;
                                border-radius:10px;
                                background:#fef2f2;
                            ">

                                <div style="
                                    margin-bottom:5px;
                                    color:#b91c1c;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                                    Rejection Reason
                                </div>

                                <div id="viewRejectionReason" style="
                                    color:#991b1b;
                                    font-size:12px;
                                    line-height:1.5;
                                ">
                                    -
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- DOCUMENT -->
                <div id="documentContainer" style="
                    display:none;
                ">

                    <div style="
                        margin-bottom:8px;
                        color:#111827;
                        font-size:12px;
                        font-weight:700;
                    ">
                        Attached Document
                    </div>

                    <a id="viewRequestDocument"
                        href="#"
                        target="_blank"
                        style="
                            display:inline-flex;
                            align-items:center;
                            gap:7px;
                            padding:8px 12px;
                            border:1px solid #dbeafe;
                            border-radius:8px;
                            background:#eff6ff;
                            color:#2563eb;
                            font-size:11px;
                            font-weight:600;
                            text-decoration:none;
                        ">
                        <i class="fas fa-file-arrow-down"></i>
                        View Document
                    </a>

                </div>

            </div>


            <!-- FOOTER -->
            <div class="modal-footer" style="
                padding:13px 20px;
                border-top:1px solid #e5e7eb;
                background:#f8fafc;
            ">

                <button type="button"
                    data-bs-dismiss="modal"
                    style="
                        display:inline-flex;
                        align-items:center;
                        justify-content:center;
                        padding:8px 14px;
                        border:1px solid #d1d5db;
                        border-radius:8px;
                        background:#ffffff;
                        color:#374151;
                        font-size:11px;
                        font-weight:600;
                        cursor:pointer;
                    ">
                    Close
                </button>

            </div>

        </div>

    </div>

</div>