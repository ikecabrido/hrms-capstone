<div class="modal fade" id="submitTrainingModal" tabindex="-1" aria-labelledby="submitTrainingModalLabel"
    aria-hidden="true" style="z-index: index 1045;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="
                border:0;
                border-radius:16px;
                overflow:hidden;
                box-shadow:0 15px 45px rgba(15,23,42,.18);">
            <!-- MODAL HEADER -->
            <div style="
                    padding:20px 24px;
                    background:linear-gradient(135deg,#eff6ff,#ffffff);
                    border-bottom:1px solid #e5e7eb;">
                <div style="
                        display:flex;
                        align-items:center;
                        justify-content:space-between;
                        gap:15px;">

                    <div style="
                            display:flex;
                            align-items:center;
                            gap:12px;">
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
                            <i class="fas fa-calendar-plus"></i>
                        </div>

                        <div>

                            <h5 id="submitTrainingModalLabel" style="
                                    margin:0;
                                    color:#111827;
                                    font-size:18px;
                                    font-weight:700;
                                ">
                                Submit Training Request
                            </h5>

                            <p style="
                                    margin:3px 0 0;
                                    color:#6b7280;
                                    font-size:12px;
                                ">
                                Fill in the details of your training request.
                            </p>

                        </div>

                    </div>


                    <!-- CLOSE BUTTON -->
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


            <!-- MODAL BODY -->
            <form action="index.php?url=store-training-request" method="POST">
                <div style="padding:22px 24px;background:#fff;">

                    <!-- DESCRIPTION -->
                    <div style="margin-bottom:4px;">

                        <label for="requested_title" style="
                display:block;
                margin-bottom:6px;
                color:#374151;
                font-size:12px;
                font-weight:600;
            ">
                            <i class="fas fa-list-alt" style="
                    margin-right:5px;
                    color:#2563eb;
                "></i>
                            Requested Title
                        </label>
                        <input name="requested_title" required
                            placeholder="Please provide the title for your training request..." style="
                    width:100%;
                    padding:11px 12px;
                    border:1px solid #d1d5db;
                    border-radius:9px;
                    background:#fff;
                    color:#374151;
                    font-size:13px;
                    line-height:1.5;
                    resize:vertical;
                    box-sizing:border-box;">
                    </div>

                    <!-- DESCRIPTION -->
                    <div style="margin-bottom:4px;">

                        <label for="description" style="
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
                            Description
                        </label>
                        <textarea name="description" id="details" rows="4" required
                            placeholder="Please provide the description for your training request..." style="
                    width:100%;
                    padding:11px 12px;
                    border:1px solid #d1d5db;
                    border-radius:9px;
                    background:#fff;
                    color:#374151;
                    font-size:13px;
                    line-height:1.5;
                    resize:vertical;
                    box-sizing:border-box;">
                    </textarea>
                    </div>

                </div>
                <!-- MODAL FOOTER -->
                <div style="
        display:flex;
        justify-content:flex-end;
        align-items:center;
        gap:8px;
        padding:14px 24px;
        background:#f8fafc;
        border-top:1px solid #e5e7eb;
    ">

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