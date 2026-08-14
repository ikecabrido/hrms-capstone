<div
    class="modal fade"
    id="submitBenefitModal"
    tabindex="-1"
    aria-labelledby="submitBenefitModalLabel"
    aria-hidden="true"
>

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
                background:#fff;
            ">

                <div style="
                    display:flex;
                    align-items:center;
                    gap:11px;
                ">

                    <!-- ICON -->
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
                        <i class="fas fa-file-arrow-up"></i>
                    </div>


                    <!-- TITLE -->
                    <div>

                        <h5
                            id="submitBenefitModalLabel"
                            style="
                                margin:0;
                                color:#111827;
                                font-size:16px;
                                font-weight:700;
                            "
                        >
                            Submit Benefit Document
                        </h5>

                        <p style="
                            margin:3px 0 0;
                            color:#6b7280;
                            font-size:11px;
                        ">
                            Submit a document for HR verification and review.
                        </p>

                    </div>

                </div>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>


            <!-- FORM -->
            <form
                action="index.php?url=employee-benefits-store"
                method="POST"
                enctype="multipart/form-data"
            >

                <!-- BODY -->
                <div class="modal-body" style="
                    padding:20px;
                    background:#fff;
                ">

                    <div class="row g-3">


                        <!-- RECORD TYPE -->
                        <div class="col-md-6">

                            <label
                                for="benefit_record_type"
                                style="
                                    display:block;
                                    margin-bottom:6px;
                                    color:#374151;
                                    font-size:11px;
                                    font-weight:700;
                                "
                            >
                                Record Type
                                <span style="color:#dc2626;">*</span>
                            </label>

                            <select
                                name="record_type"
                                id="benefit_record_type"
                                class="form-select"
                                required
                                style="
                                    height:40px;
                                    border:1px solid #d1d5db;
                                    border-radius:8px;
                                    font-size:12px;
                                "
                            >

                                <option value="">
                                    Select record type
                                </option>

                                <option value="SSS">
                                    SSS
                                </option>

                                <option value="PhilHealth">
                                    PhilHealth
                                </option>

                                <option value="Pag-IBIG">
                                    Pag-IBIG
                                </option>

                                <option value="Withholding Tax">
                                    Withholding Tax
                                </option>

                                <option value="BIR Form 2316">
                                    BIR Form 2316
                                </option>

                            </select>

                        </div>


                        <!-- PERIOD -->
                        <div class="col-md-6">

                            <label
                                for="benefit_period"
                                style="
                                    display:block;
                                    margin-bottom:6px;
                                    color:#374151;
                                    font-size:11px;
                                    font-weight:700;
                                "
                            >
                                Period
                                <span style="color:#dc2626;">*</span>
                            </label>

                            <input
                                type="month"
                                name="period"
                                id="benefit_period"
                                class="form-control"
                                required
                                style="
                                    height:40px;
                                    border:1px solid #d1d5db;
                                    border-radius:8px;
                                    font-size:12px;
                                "
                            >

                        </div>


                        <!-- DESCRIPTION -->
                        <div class="col-12">

                            <label
                                for="benefit_description"
                                style="
                                    display:block;
                                    margin-bottom:6px;
                                    color:#374151;
                                    font-size:11px;
                                    font-weight:700;
                                "
                            >
                                Description
                            </label>

                            <textarea
                                name="description"
                                id="benefit_description"
                                rows="3"
                                class="form-control"
                                placeholder="Enter a description or additional information..."
                                style="
                                    border:1px solid #d1d5db;
                                    border-radius:8px;
                                    font-size:12px;
                                    resize:vertical;
                                "
                            ></textarea>

                        </div>


                        <!-- DOCUMENT UPLOAD -->
                        <div class="col-12">

                            <label
                                for="benefit_file"
                                style="
                                    display:block;
                                    margin-bottom:6px;
                                    color:#374151;
                                    font-size:11px;
                                    font-weight:700;
                                "
                            >
                                Supporting Document
                                <span style="color:#dc2626;">*</span>
                            </label>


                            <div style="
                                padding:16px;
                                border:1px dashed #bfdbfe;
                                border-radius:10px;
                                background:#f8fbff;
                            ">

                                <input
                                    type="file"
                                    name="document"
                                    class="form-control"
                                    accept=".pdf,.jpg,.jpeg,.png,.docx"
                                    required
                                    style="
                                        border:1px solid #d1d5db;
                                        border-radius:8px;
                                        font-size:12px;
                                    "
                                >


                                <div style="
                                    display:flex;
                                    align-items:center;
                                    gap:6px;
                                    margin-top:8px;
                                    color:#6b7280;
                                    font-size:10px;
                                ">

                                    <i class="fas fa-circle-info"></i>

                                    <span>
                                        Accepted formats: PDF, JPG, JPEG, PNG.
                                    </span>

                                </div>

                            </div>

                        </div>


                        <!-- INFORMATION NOTICE -->
                        <div class="col-12">

                            <div style="
                                display:flex;
                                align-items:flex-start;
                                gap:9px;
                                padding:12px 14px;
                                border:1px solid #dbeafe;
                                border-radius:9px;
                                background:#eff6ff;
                                color:#1e40af;
                            ">

                                <i
                                    class="fas fa-circle-info"
                                    style="
                                        margin-top:2px;
                                        font-size:12px;
                                    "
                                ></i>

                                <div style="
                                    font-size:10px;
                                    line-height:1.5;
                                ">
                                    This document will be submitted to HR for
                                    verification. Your submission will remain
                                    pending until it has been reviewed.
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- FOOTER -->
                <div class="modal-footer" style="
                    padding:13px 20px;
                    border-top:1px solid #e5e7eb;
                    background:#f8fafc;
                ">

                    <!-- CANCEL -->
                    <button
                        type="button"
                        data-bs-dismiss="modal"
                        style="
                            display:inline-flex;
                            align-items:center;
                            justify-content:center;
                            padding:8px 14px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                            background:#fff;
                            color:#374151;
                            font-size:11px;
                            font-weight:600;
                        "
                    >
                        Cancel
                    </button>


                    <!-- SUBMIT -->
                    <button
                        type="submit"
                        style="
                            display:inline-flex;
                            align-items:center;
                            gap:6px;
                            justify-content:center;
                            padding:8px 14px;
                            border:0;
                            border-radius:8px;
                            background:#2563eb;
                            color:#fff;
                            font-size:11px;
                            font-weight:600;
                        "
                    >
                        <i class="fas fa-paper-plane"></i>
                        Submit for Review
                    </button>

                </div>

            </form>
        </div>
    </div>
</div>