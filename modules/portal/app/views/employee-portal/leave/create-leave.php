<div class="modal fade" id="submitLeaveModal" tabindex="-1" aria-labelledby="submitLeaveModalLabel" aria-hidden="true"
    style="z-index: index 1045;">
    <div class="modal-dialog modal-dialog-centered modal-lg">

        <div class="modal-content" style="
                border:0;
                border-radius:16px;
                overflow:hidden;
                box-shadow:0 15px 45px rgba(15,23,42,.18);
            ">

            <!-- MODAL HEADER -->
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
                            <i class="fas fa-calendar-plus"></i>
                        </div>

                        <div>

                            <h5 id="submitLeaveModalLabel" style="
                                    margin:0;
                                    color:#111827;
                                    font-size:18px;
                                    font-weight:700;
                                ">
                                Submit Leave Request
                            </h5>

                            <p style="
                                    margin:3px 0 0;
                                    color:#6b7280;
                                    font-size:12px;
                                ">
                                Fill in the details of your leave request.
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
            <form action="index.php?url=leave-store" method="POST" enctype="multipart/form-data">

                <div style="padding:22px 24px;background:#fff;">

                    <!-- LEAVE TYPE -->
                    <div style="margin-bottom:16px;">
                        <label for="leave_type_id" style="
                display:block;
                margin-bottom:6px;
                color:#374151;
                font-size:12px;
                font-weight:600;
            ">
                            <i class="fas fa-tag" style="margin-right:5px;color:#2563eb;"></i>
                            Leave Type
                        </label>

                        <select name="leave_type_id" id="leave_type_id" required style="
                width:100%;
                height:42px;
                padding:0 12px;
                border:1px solid #d1d5db;
                border-radius:9px;
                background:#fff;
                color:#374151;
                font-size:13px;
                outline:none;
                box-sizing:border-box;
            ">
                            <option value="">Select leave type</option>

                            <?php foreach ($leaveTypes as $leaveType): ?>
                                <option value="<?= (int) $leaveType['leave_type_id'] ?>">
                                    <?= htmlspecialchars($leaveType['leave_type_name']) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <!-- DATES -->
                    <div style="
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:14px;
            margin-bottom:16px;
        ">

                        <?php $today = date('Y-m-d'); ?>

                        <!-- START DATE -->
                        <div style="min-width:0;">

                            <label for="start_date" style="
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
                                Start Date
                            </label>

                            <input type="date" name="start_date" id="start_date" required min="<?= $today ?>" style="
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

                        <!-- END DATE -->
                        <div style="min-width:0;">

                            <label for="end_date" style="
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
                                End Date
                            </label>

                            <input type="date" name="end_date" id="end_date" required min="<?= $today ?>" style="
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

                    <!-- REASON -->
                    <div style="margin-bottom:4px;">

                        <label for="reason" style="
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
                            Reason
                        </label>

                        <textarea name="details" id="details" rows="4" required
                            placeholder="Please provide the reason for your leave request..." style="
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
                    <!-- SUPPORTING DOCUMENT -->
                    <div style="margin-top:16px;">

                        <label for="supporting_document" style="
        display:block;
        margin-bottom:6px;
        color:#374151;
        font-size:12px;
        font-weight:600;
    ">
                            <i class="fas fa-paperclip" style="
            margin-right:5px;
            color:#2563eb;
        "></i>
                            Supporting Document
                            <span style="
            color:#94a3b8;
            font-size:10px;
            font-weight:400;
        ">
                                (Optional)
                            </span>
                        </label>

                        <div style="
        border:1px dashed #cbd5e1;
        border-radius:9px;
        background:#f8fafc;
        padding:12px;
    ">

                            <input type="file" name="supporting_document" id="supporting_document"
                                accept=".pdf,.jpg,.jpeg,.png" style="
                width:100%;
                font-size:11px;
                color:#475569;
            ">

                            <div style="
            margin-top:5px;
            color:#94a3b8;
            font-size:10px;
        ">
                                Accepted formats: PDF, JPG, JPEG, PNG.
                            </div>

                        </div>

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
                            Your leave request will be submitted for review.
                            The status will appear in your Leave History after submission.
                        </span>
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