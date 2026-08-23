<div class="modal fade" id="submitResignationModal" tabindex="-1" aria-labelledby="submitResignationModalLabel"
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
                padding:10px 14px;
                border-bottom:1px solid #eef2f7;
                background:#fff;
            ">

                <div>
                    <div style="
                        color:#9ca3af;
                        font-size:9px;
                        font-weight:700;
                        letter-spacing:.7px;
                        text-transform:uppercase;
                    ">
                        Employee Separation
                    </div>

                    <h5 id="submitResignationModalLabel" style="
                        margin:5px 0 0;
                        color:#111827;
                        font-size:16px;
                        font-weight:700;
                    ">
                        Submit Resignation
                    </h5>

                    <p style="
                        margin:4px 0 0;
                        color:#9ca3af;
                        font-size:10px;
                    ">
                        Submit your formal resignation request to Human Resources.
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>

            </div>

            <!-- FORM -->
            <form action="index.php?url=employee-resignation-store" method="POST" enctype="multipart/form-data">

                <div class="modal-body" style="
                    padding:24px;
                    background:#fff;
                ">

                    <div style="
    display:flex;
    gap:14px;
    width:100%;
    margin-bottom:18px;
">

                        <!-- RESIGNATION TYPE -->
                        <div style="flex:1;min-width:0;">
                            <label style="
            display:block;
            margin-bottom:7px;
            color:#374151;
            font-size:10px;
            font-weight:600;
        ">
                                Resignation Type
                                <span style="color:#dc2626;">*</span>
                            </label>

                            <select name="resignation_type" required style="
            width:100%;
            height:46px;
            padding:10px 12px;
            border:1px solid #d1d5db;
            border-radius:8px;
            background:#fff;
            color:#374151;
            font-size:11px;
            outline:none;
            box-sizing:border-box;
        ">
                                <option value="">Select resignation type</option>
                                <option value="With Notice">With Notice</option>
                                <option value="Immediate">Immediate</option>
                            </select>
                        </div>


                        <?php $defaultLastWorkingDay = date('Y-m-d', strtotime('+3 days')); ?>

                        <!-- LAST WORKING DAY -->
                        <div style="flex:1;min-width:0;">

                            <label style="
        display:flex;
        align-items:center;
        gap:4px;
        margin-bottom:7px;
        color:#374151;
        font-size:10px;
        font-weight:600;
    ">
                                Intended Last Working Day
                                <span style="color:#dc2626;">*</span>
                            </label>

                            <div id="dateTrigger" style="
            position:relative;
            display:flex;
            align-items:center;
            width:100%;
            height:46px;
            padding:10px 14px 10px 47px;
            border:1px solid #dbe1e8;
            border-radius:10px;
            background:#fff;
            color:#1f2937;
            font-size:11px;
            font-weight:600;
            box-sizing:border-box;
            cursor:pointer;
            transition:.2s ease;
        ">

                                <!-- Calendar Icon -->
                                <span style="
            position:absolute;
            left:11px;
            top:50%;
            transform:translateY(-50%);
            width:26px;
            height:26px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:7px;
            background:#eff6ff;
            color:#2563eb;
        ">
                                    <i class="fas fa-calendar-alt" style="font-size:11px;"></i>
                                </span>

                                <!-- Selected Date -->
                                <span id="selectedWorkingDay">
                                    <?= date('F d, Y', strtotime($defaultLastWorkingDay)) ?>
                                </span>

                            </div>

                            <!-- Hidden real date input -->
                            <input type="date" id="dateInput" name="intended_last_working_day"
                                value="<?= htmlspecialchars($defaultLastWorkingDay) ?>"
                                min="<?= htmlspecialchars($defaultLastWorkingDay) ?>" required style="
            position:absolute;
            width:1px;
            height:1px;
            opacity:0;
            pointer-events:none;
        ">

                            <!-- Helper -->
                            <div style="
        display:flex;
        align-items:center;
        justify-content:flex-end;
        margin-top:6px;
    ">
                                <span style="
            color:#9ca3af;
            font-size:9px;
        ">
                                    <i class="fas fa-info-circle"></i>
                                    Click the date above to select
                                </span>
                            </div>

                        </div>

                        <script>
                            const dateTrigger = document.getElementById('dateTrigger');
                            const dateInput = document.getElementById('dateInput');
                            const selectedWorkingDay = document.getElementById('selectedWorkingDay');

                            dateTrigger.addEventListener('click', function () {

                                if (dateInput.showPicker) {
                                    dateInput.showPicker();
                                } else {
                                    dateInput.click();
                                }

                            });

                            dateInput.addEventListener('change', function () {

                                if (!this.value) return;

                                const date = new Date(this.value + 'T00:00:00');

                                selectedWorkingDay.textContent =
                                    date.toLocaleDateString('en-US', {
                                        month: 'long',
                                        day: '2-digit',
                                        year: 'numeric'
                                    });

                            });
                            document.getElementById('lastWorkingDay').addEventListener('change', function () {
                                if (!this.value) return;

                                const date = new Date(this.value + 'T00:00:00');

                                document.getElementById('selectedWorkingDay').textContent =
                                    date.toLocaleDateString('en-US', {
                                        month: 'long',
                                        day: '2-digit',
                                        year: 'numeric'
                                    });
                            });
                        </script>

                    </div>



                    <!-- NOTICE -->
                    <div style="
                        display:flex;
                        gap:10px;
                        padding:13px 14px;
                        margin-bottom:22px;
                        border:1px solid #dbeafe;
                        border-radius:9px;
                        background:#eff6ff;
                    ">

                        <i class="fas fa-info-circle" style="
                            margin-top:2px;
                            color:#2563eb;
                            font-size:13px;
                        "></i>

                        <div style="
                            color:#374151;
                            font-size:10px;
                            line-height:1.6;
                        ">
                            Please provide accurate information before submitting.
                            Your resignation will be reviewed by Human Resources.
                        </div>

                    </div>


                    <!-- REASON -->
                    <div style="margin-bottom:18px;">

                        <label style="
                            display:block;
                            margin-bottom:7px;
                            color:#374151;
                            font-size:10px;
                            font-weight:600;
                        ">
                            Reason for Resignation
                            <span style="color:#dc2626;">*</span>
                        </label>

                        <textarea name="resignation_reason" rows="4" required
                            placeholder="Please provide your reason for resignation..." style="
                                width:100%;
                                padding:11px 12px;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                                background:#fff;
                                color:#374151;
                                font-size:11px;
                                line-height:1.5;
                                resize:vertical;
                                box-sizing:border-box;
                                outline:none;
                            "></textarea>

                    </div>


                    <!-- EMPLOYEE REMARKS -->
                    <div style="margin-bottom:18px;">

                        <label style="
                            display:block;
                            margin-bottom:7px;
                            color:#374151;
                            font-size:10px;
                            font-weight:600;
                        ">
                            Employee Remarks
                            <span style="
                                color:#9ca3af;
                                font-weight:400;
                            ">
                                (Optional)
                            </span>
                        </label>

                        <textarea name="employee_remarks" rows="3"
                            placeholder="Add any additional information or turnover notes..." style="
                                width:100%;
                                padding:11px 12px;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                                background:#fff;
                                color:#374151;
                                font-size:11px;
                                line-height:1.5;
                                resize:vertical;
                                box-sizing:border-box;
                                outline:none;
                            "></textarea>

                    </div>


                    <!-- ATTACHMENT -->
                    <div style="margin-bottom:5px;">

                        <label style="
        display:block;
        margin-bottom:7px;
        color:#374151;
        font-size:10px;
        font-weight:600;
    ">
                            Resignation Letter
                            <span style="color:#9ca3af;font-weight:400;">
                                (Optional)
                            </span>
                        </label>

                        <div onclick="document.getElementById('resignationAttachment').click()" style="
            display:block;
            padding:16px;
            border:1px dashed #cbd5e1;
            border-radius:10px;
            background:#f8fafc;
            cursor:pointer;
            transition:.2s ease;
        " onmouseover="
            this.style.borderColor='#2563eb';
            this.style.background='#eff6ff';
        " onmouseout="
            this.style.borderColor='#cbd5e1';
            this.style.background='#f8fafc';
        ">

                            <div style="
            display:flex;
            align-items:center;
            gap:11px;
        ">

                                <div style="
                width:38px;
                height:38px;
                flex-shrink:0;
                display:flex;
                align-items:center;
                justify-content:center;
                border-radius:9px;
                background:#fef2f2;
                color:#dc2626;
            ">
                                    <i class="fas fa-file-pdf" style="font-size:14px;"></i>
                                </div>

                                <div style="flex:1;">

                                    <div style="
                    color:#374151;
                    font-size:10px;
                    font-weight:600;
                ">
                                        Upload resignation letter
                                    </div>

                                    <div id="attachmentName" style="
                    margin-top:3px;
                    color:#9ca3af;
                    font-size:9px;
                ">
                                        PDF only • Maximum 5 MB
                                    </div>

                                </div>

                                <div style="
                width:30px;
                height:30px;
                display:flex;
                align-items:center;
                justify-content:center;
                border-radius:7px;
                background:#fff;
                border:1px solid #e5e7eb;
                color:#6b7280;
            ">
                                    <i class="fas fa-upload" style="font-size:11px;"></i>
                                </div>

                            </div>

                            <input type="file" id="resignationAttachment" name="attachment"
                                accept=".pdf,application/pdf" style="display:none;" onchange="
                const file = this.files[0];
                const name = document.getElementById('attachmentName');

                if (file) {
                    name.textContent = file.name;
                    name.style.color = '#2563eb';
                    name.style.fontWeight = '600';
                } else {
                    name.textContent = 'PDF only • Maximum 5 MB';
                    name.style.color = '#9ca3af';
                    name.style.fontWeight = '400';
                }
            ">

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
                            display:inline-flex;
                            align-items:center;
                            gap:7px;
                            padding:9px 16px;
                            border:0;
                            border-radius:8px;
                            background:#2563eb;
                            color:#fff;
                            font-size:10px;
                            font-weight:600;
                            cursor:pointer;
                        ">
                        <i class="fas fa-paper-plane"></i>
                        Submit Resignation
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>