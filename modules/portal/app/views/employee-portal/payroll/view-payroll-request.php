<?php foreach ($payrollRequests as $request): ?>
<div class="modal fade" id="viewPayrollRequestModal<?= (int) $request['id'] ?>" tabindex="-1"
    aria-labelledby="viewPayrollRequestModalLabel<?= (int) $request['id'] ?>" aria-hidden="true">

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

                <div class="d-flex align-items-center gap-3">

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

                        <h5 class="modal-title" id="viewPayrollRequestModalLabel<?= (int) $request['id'] ?>" style="
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

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
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

                        <i class="fas fa-circle-info" style="
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

                    <?php
                    $status = strtolower(
                        trim($request['status'] ?? 'pending')
                    );
                    ?>

                    <span style="
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

                        <i class="fas fa-clock" style="font-size:9px;">
                        </i>

                        <?= htmlspecialchars(
                            ucwords(
                                str_replace(
                                    '_',
                                    ' ',
                                    $status
                                )
                            )
                        ) ?>

                    </span>

                </div>


                <!-- REQUEST INFORMATION -->
                <div style="margin-bottom:18px;">

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

                                <div style="
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:600;
                                ">
                                    <?= htmlspecialchars(
                                        $request['request_type'] ?? '-'
                                    ) ?>
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

                                <div style="
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:600;
                                ">
                                    <?= htmlspecialchars(
                                        $request['purpose'] ?? '-'
                                    ) ?>
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

                                <div style="
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:600;
                                ">

                                    <?php

                                    $startDate = !empty(
                                        $request['payroll_period_start']
                                    )
                                        ? date(
                                            'M d, Y',
                                            strtotime(
                                                $request['payroll_period_start']
                                            )
                                        )
                                        : '-';

                                    $endDate = !empty(
                                        $request['payroll_period_end']
                                    )
                                        ? date(
                                            'M d, Y',
                                            strtotime(
                                                $request['payroll_period_end']
                                            )
                                        )
                                        : '-';

                                    ?>

                                    <?= htmlspecialchars(
                                        $startDate . ' – ' . $endDate
                                    ) ?>

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

                                <div style="
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:600;
                                ">

                                    <?= !empty($request['requested_at'])
                                        ? htmlspecialchars(
                                            date(
                                                'M d, Y',
                                                strtotime(
                                                    $request['requested_at']
                                                )
                                            )
                                        )
                                        : '-'
                                        ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- REMARKS -->
                <div style="margin-bottom:18px;">

                    <div style="
                        margin-bottom:8px;
                        color:#111827;
                        font-size:12px;
                        font-weight:700;
                    ">
                        Remarks
                    </div>

                    <div style="
                        min-height:70px;
                        padding:12px;
                        border:1px solid #e5e7eb;
                        border-radius:10px;
                        background:#f8fafc;
                        color:#4b5563;
                        font-size:12px;
                        line-height:1.6;
                    ">

                        <?= nl2br(
                            htmlspecialchars(
                                $request['remarks'] ?? '-'
                            )
                        ) ?>

                    </div>

                </div>


                <!-- PROCESSING INFORMATION -->
                <?php if (
                    !empty($request['processed_at']) ||
                    !empty($request['rejection_reason'])
                ): ?>

                    <div style="margin-bottom:18px;">

                        <div style="
                            margin-bottom:10px;
                            color:#111827;
                            font-size:12px;
                            font-weight:700;
                        ">
                            Processing Information
                        </div>

                        <div class="row g-3">

                            <?php if (
                                !empty($request['processed_at'])
                            ): ?>

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

                                        <div style="
                                            color:#111827;
                                            font-size:12px;
                                            font-weight:600;
                                        ">

                                            <?= htmlspecialchars(
                                                date(
                                                    'M d, Y',
                                                    strtotime(
                                                        $request['processed_at']
                                                    )
                                                )
                                            ) ?>

                                        </div>

                                    </div>

                                </div>

                            <?php endif; ?>


                            <?php if (
                                $status === 'rejected' &&
                                !empty($request['rejection_reason'])
                            ): ?>

                                <div class="col-md-6">

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

                                        <div style="
                                            color:#991b1b;
                                            font-size:12px;
                                            line-height:1.5;
                                        ">

                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $request['rejection_reason']
                                                )
                                            ) ?>

                                        </div>

                                    </div>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endif; ?>


                <!-- DOCUMENT -->
                <div>

                    <div style="
        margin-bottom:8px;
        color:#111827;
        font-size:12px;
        font-weight:700;
    ">
                        Attached Document
                    </div>

                    <?php if (!empty($request['document_path'])): ?>
                        <?php
                        $documentPath = str_replace(
                            'D:\\xampp\\htdocs\\hrms-capstone\\modules\\portal\\public\\',
                            '',
                            $request['document_path']
                        );

                        $documentUrl = '/hrms-capstone/modules/portal/public/' .
                            ltrim(str_replace('\\', '/', $documentPath), '/');
                        ?>
                        <a href="<?= htmlspecialchars($documentUrl) ?>" target="_blank" style="
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

                    <?php else: ?>

                        <span style="
            color:#9ca3af;
            font-size:11px;
            font-weight:500;
        ">
                            No document attached
                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <!-- FOOTER -->
            <div class="modal-footer" style="
                    padding:13px 20px;
                    border-top:1px solid #e5e7eb;
                    background:#f8fafc;
                ">

                <button type="button" data-bs-dismiss="modal" style="
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
<?php endforeach; ?>