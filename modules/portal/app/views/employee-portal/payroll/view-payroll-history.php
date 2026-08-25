<?php if (!empty($payrollHistory)): ?>
    <?php foreach ($payrollHistory as $payroll): ?>

        <div class="modal fade" id="viewPayrollModal<?= (int) $payroll['payslip_id'] ?>" tabindex="-1"
            aria-labelledby="viewPayrollModalLabel<?= (int) $payroll['payslip_id'] ?>" aria-hidden="true">

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

                                <h5 id="viewPayrollModalLabel<?= (int) $payroll['payslip_id'] ?>" style="
                                margin:0;
                                color:#111827;
                                font-size:16px;
                                font-weight:700;
                            ">
                                    Payslip Details
                                </h5>

                                <p style="
                            margin:3px 0 0;
                            color:#6b7280;
                            font-size:11px;
                        ">
                                    View your payroll and payslip information.
                                </p>

                            </div>

                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>

                    </div>


                    <!-- BODY -->
                    <div class="modal-body" style="
                    padding:20px;
                    background:#fff;
                ">

                        <!-- PAYSLIP INFORMATION -->
                        <div style="margin-bottom:18px;">

                            <div style="
                        margin-bottom:10px;
                        color:#111827;
                        font-size:12px;
                        font-weight:700;
                    ">
                                Payslip Information
                            </div>

                            <div class="row g-3">

                                <!-- PAYSLIP ID -->
                                <div class="col-md-6">
                                    <div style="
                                padding:12px;
                                border:1px solid #e5e7eb;
                                border-radius:10px;
                            ">

                                        <div style="
                                    margin-bottom:5px;
                                    color:#6b7280;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                                            Payslip ID
                                        </div>

                                        <div style="
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:600;
                                ">
                                            #<?= (int) $payroll['payslip_id'] ?>
                                        </div>

                                    </div>
                                </div>


                                <!-- PAYROLL RUN -->
                                <div class="col-md-6">
                                    <div style="
                                padding:12px;
                                border:1px solid #e5e7eb;
                                border-radius:10px;
                            ">

                                        <div style="
                                    margin-bottom:5px;
                                    color:#6b7280;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                                            Payroll Run
                                        </div>

                                        <div style="
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:600;
                                ">
                                            #<?= htmlspecialchars($payroll['run_id'] ?? '-') ?>
                                        </div>

                                    </div>
                                </div>


                                <!-- EMPLOYEE -->
                                <div class="col-md-6">
                                    <div style="
                                padding:12px;
                                border:1px solid #e5e7eb;
                                border-radius:10px;
                            ">

                                        <div style="
                                    margin-bottom:5px;
                                    color:#6b7280;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                                            Employee ID
                                        </div>

                                        <div style="
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:600;
                                ">
                                            <?= htmlspecialchars($payroll['employee_id'] ?? '-') ?>
                                        </div>

                                    </div>
                                </div>


                                <!-- GENERATED DATE -->
                                <div class="col-md-6">
                                    <div style="
                                padding:12px;
                                border:1px solid #e5e7eb;
                                border-radius:10px;
                            ">

                                        <div style="
                                    margin-bottom:5px;
                                    color:#6b7280;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                                            Generated Date
                                        </div>

                                        <div style="
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:600;
                                ">
                                            <?= !empty($payroll['generated_at'])
                                                ? date('M d, Y h:i A', strtotime($payroll['generated_at']))
                                                : '-' ?>
                                        </div>

                                    </div>
                                </div>

                            </div>

                        </div>


                        <!-- PAY SUMMARY -->
                        <div style="margin-bottom:18px;">

                            <div style="
                        margin-bottom:10px;
                        color:#111827;
                        font-size:12px;
                        font-weight:700;
                    ">
                                Pay Summary
                            </div>

                            <div class="row g-3">

                                <!-- GROSS PAY -->
                                <div class="col-md-4">
                                    <div style="
                                padding:12px;
                                border:1px solid #e5e7eb;
                                border-radius:10px;
                                background:#fff;
                            ">

                                        <div style="
                                    margin-bottom:5px;
                                    color:#6b7280;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                                            Gross Pay
                                        </div>

                                        <div style="
                                    color:#111827;
                                    font-size:14px;
                                    font-weight:700;
                                ">
                                            ₱<?= number_format((float) ($payroll['gross_pay'] ?? 0), 2) ?>
                                        </div>

                                    </div>
                                </div>


                                <!-- DEDUCTIONS -->
                                <div class="col-md-4">
                                    <div style="
                                padding:12px;
                                border:1px solid #e5e7eb;
                                border-radius:10px;
                                background:#fff;
                            ">

                                        <div style="
                                    margin-bottom:5px;
                                    color:#6b7280;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                                            Total Deductions
                                        </div>

                                        <div style="
                                    color:#b91c1c;
                                    font-size:14px;
                                    font-weight:700;
                                ">
                                            ₱<?= number_format((float) ($payroll['total_deductions'] ?? 0), 2) ?>
                                        </div>

                                    </div>
                                </div>


                                <!-- NET PAY -->
                                <div class="col-md-4">
                                    <div style="
                                padding:12px;
                                border:1px solid #bbf7d0;
                                border-radius:10px;
                                background:#f0fdf4;
                            ">

                                        <div style="
                                    margin-bottom:5px;
                                    color:#166534;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                                            Net Pay
                                        </div>

                                        <div style="
                                    color:#15803d;
                                    font-size:16px;
                                    font-weight:700;
                                ">
                                            ₱<?= number_format((float) ($payroll['net_pay'] ?? 0), 2) ?>
                                        </div>

                                    </div>
                                </div>

                            </div>

                        </div>


                        <!-- EXIT SETTLEMENT -->
                        <?php if (!empty($payroll['is_exit_settlement'])): ?>

                            <div style="
                        padding:12px 14px;
                        margin-bottom:18px;
                        border:1px solid #fed7aa;
                        border-radius:10px;
                        background:#fff7ed;
                    ">

                                <div style="
                            display:flex;
                            align-items:center;
                            gap:8px;
                            color:#9a3412;
                            font-size:11px;
                            font-weight:700;
                        ">
                                    <i class="fas fa-circle-info"></i>
                                    Final / Exit Settlement Payslip
                                </div>

                                <?php if (!empty($payroll['settlement_id'])): ?>

                                    <div style="
                                margin-top:5px;
                                color:#7c2d12;
                                font-size:10px;
                            ">
                                        Settlement ID:
                                        <?= htmlspecialchars($payroll['settlement_id']) ?>
                                    </div>

                                <?php endif; ?>

                                <?php if (!empty($payroll['resignation_id'])): ?>

                                    <div style="
                                margin-top:3px;
                                color:#7c2d12;
                                font-size:10px;
                            ">
                                        Resignation ID:
                                        <?= htmlspecialchars($payroll['resignation_id']) ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endif; ?>

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
                        background:#fff;
                        color:#374151;
                        font-size:11px;
                        font-weight:600;
                    ">
                            Close
                        </button>

                    </div>

                </div>
            </div>
        </div>

    <?php endforeach; ?>
<?php endif; ?>