<div class="employee-dashboard">

    <section class="dashboard-welcome" id="profileWelcome">
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">
            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                EMPLOYEE ATTENDANCE
            </span>

            <h1 class="welcome-title">Attendance</h1>

            <p class="welcome-description">
                View your attendance history, including time in, time out, hours worked, and approval status.
            </p>

            <div class="welcome-line"></div>
        </div>

        <div class="welcome-decoration">
            <i class="fas fa-calendar-check"></i>
        </div>
    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section class="dashboard-section" style="width:100%;box-sizing:border-box;">

        <!-- HEADER -->
        <div
            style="display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:18px;flex-wrap:wrap;">

            <div>
                <h3 style="margin:0 0 5px;color:#111827;font-size:20px;font-weight:700;">
                    <i class="fas fa-history" style="color:#2563eb;margin-right:7px;"></i>
                    Attendance History
                </h3>

                <p style="margin:0;color:#6b7280;font-size:13px;">
                    Review your daily attendance records and working hours.
                </p>
            </div>

            <div
                style="display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:20px;background:#eff6ff;color:#2563eb;font-size:12px;font-weight:600;">
                <i class="fas fa-calendar-check"></i>
                <?= count($attendanceHistory) ?> Records
            </div>

        </div>

        <?php if (!empty($attendanceHistory)): ?>

            <!-- MOBILE SCROLL INDICATOR -->
            <div
                style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px;padding:8px 12px;border-radius:8px;background:#f8fafc;border:1px solid #e5e7eb;color:#6b7280;font-size:11px;">

                <span>
                    <i class="fas fa-info-circle" style="color:#2563eb;margin-right:5px;"></i>
                    Swipe horizontally to view all attendance details
                </span>

                <i class="fas fa-arrows-alt-h" style="color:#2563eb;"></i>

            </div>

            <!-- TABLE CONTAINER -->
            <div id="attendanceTable"
                style="width:100%;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;cursor:grab;border:1px solid #e5e7eb;border-radius:12px;background:#fff;box-shadow:0 3px 12px rgba(0,0,0,.04);"
                onmousedown="this.style.cursor='grabbing'" onmouseup="this.style.cursor='grab'"
                onmouseleave="this.style.cursor='grab'" onwheel="this.scrollLeft += event.deltaY;event.preventDefault();">

                <table style="width:100%;min-width:900px;border-collapse:separate;border-spacing:0;font-size:13px;">

                    <thead>
                        <tr style="background:#f8fafc;">

                            <th
                                style="padding:14px 16px;text-align:left;white-space:nowrap;border-bottom:1px solid #e5e7eb;color:#6b7280;font-weight:600;">
                                <i class="fas fa-calendar-day" style="color:#2563eb;margin-right:6px;"></i>
                                Date
                            </th>

                            <th
                                style="padding:14px 16px;text-align:left;white-space:nowrap;border-bottom:1px solid #e5e7eb;color:#6b7280;font-weight:600;">
                                <i class="fas fa-sign-in-alt" style="color:#16a34a;margin-right:6px;"></i>
                                Time In
                            </th>

                            <th
                                style="padding:14px 16px;text-align:left;white-space:nowrap;border-bottom:1px solid #e5e7eb;color:#6b7280;font-weight:600;">
                                <i class="fas fa-sign-out-alt" style="color:#dc2626;margin-right:6px;"></i>
                                Time Out
                            </th>

                            <th
                                style="padding:14px 16px;text-align:center;white-space:nowrap;border-bottom:1px solid #e5e7eb;color:#6b7280;font-weight:600;">
                                <i class="fas fa-clock" style="color:#7c3aed;margin-right:6px;"></i>
                                Total Hours
                            </th>

                            <th
                                style="padding:14px 16px;text-align:center;white-space:nowrap;border-bottom:1px solid #e5e7eb;color:#6b7280;font-weight:600;">
                                <i class="fas fa-business-time" style="color:#0891b2;margin-right:6px;"></i>
                                Regular
                            </th>

                            <th
                                style="padding:14px 16px;text-align:center;white-space:nowrap;border-bottom:1px solid #e5e7eb;color:#6b7280;font-weight:600;">
                                <i class="fas fa-hourglass-half" style="color:#f59e0b;margin-right:6px;"></i>
                                Overtime
                            </th>

                            <th
                                style="padding:14px 16px;text-align:center;white-space:nowrap;border-bottom:1px solid #e5e7eb;color:#6b7280;font-weight:600;">
                                <i class="fas fa-info-circle" style="color:#2563eb;margin-right:6px;"></i>
                                Status
                            </th>

                            <th
                                style="padding:14px 16px;text-align:center;white-space:nowrap;border-bottom:1px solid #e5e7eb;color:#6b7280;font-weight:600;">
                                <i class="fas fa-check-circle" style="color:#16a34a;margin-right:6px;"></i>
                                Approval
                            </th>

                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($attendanceHistory as $attendance): ?>

                            <?php
                            $status = $attendance['status'] ?? 'UNKNOWN';

                            $statusBadge = match (strtoupper($status)) {
                                'PRESENT' => 'background:#dcfce7;color:#166534;',
                                'LATE' => 'background:#fef3c7;color:#92400e;',
                                'ABSENT' => 'background:#fee2e2;color:#991b1b;',
                                'ON_LEAVE' => 'background:#e0f2fe;color:#075985;',
                                'PENDING_APPROVAL' => 'background:#fef3c7;color:#92400e;',
                                'APPROVED' => 'background:#dcfce7;color:#166534;',
                                'REJECTED' => 'background:#fee2e2;color:#991b1b;',
                                default => 'background:#f3f4f6;color:#374151;'
                            };

                            $approvalBadge = ((int) ($attendance['is_approved'] ?? 0) === 1)
                                ? 'background:#dcfce7;color:#166534;'
                                : 'background:#fef3c7;color:#92400e;';

                            $approvalText = ((int) ($attendance['is_approved'] ?? 0) === 1)
                                ? 'Approved'
                                : 'Pending';
                            ?>

                            <tr style="transition:background .15s ease;" onmouseover="this.style.background='#f8fafc'"
                                onmouseout="this.style.background='#fff'">

                                <td
                                    style="padding:15px 16px;white-space:nowrap;color:#111827;font-weight:600;border-bottom:1px solid #f1f5f9;">
                                    <i class="far fa-calendar" style="color:#2563eb;margin-right:7px;"></i>
                                    <?= htmlspecialchars($attendance['attendance_date'] ?? '-') ?>
                                </td>

                                <td style="padding:15px 16px;white-space:nowrap;color:#374151;border-bottom:1px solid #f1f5f9;">
                                    <?= !empty($attendance['time_in'])
                                        ? date('h:i A', strtotime($attendance['time_in']))
                                        : '-' ?>
                                </td>

                                <td style="padding:15px 16px;white-space:nowrap;color:#374151;border-bottom:1px solid #f1f5f9;">
                                    <?= !empty($attendance['time_out'])
                                        ? date('h:i A', strtotime($attendance['time_out']))
                                        : '-' ?>
                                </td>

                                <td
                                    style="padding:15px 16px;text-align:center;white-space:nowrap;color:#111827;font-weight:700;border-bottom:1px solid #f1f5f9;">
                                    <?= number_format((float) ($attendance['total_hours_worked'] ?? 0), 2) ?>
                                    <small style="color:#9ca3af;font-weight:500;">hrs</small>
                                </td>

                                <td
                                    style="padding:15px 16px;text-align:center;white-space:nowrap;color:#374151;border-bottom:1px solid #f1f5f9;">
                                    <?= number_format((float) ($attendance['regular_hours'] ?? 0), 2) ?>
                                    <small style="color:#9ca3af;">hrs</small>
                                </td>

                                <td
                                    style="padding:15px 16px;text-align:center;white-space:nowrap;color:#2563eb;font-weight:700;border-bottom:1px solid #f1f5f9;">
                                    <?= number_format((float) ($attendance['overtime_hours'] ?? 0), 2) ?>
                                    <small style="color:#60a5fa;font-weight:500;">hrs</small>
                                </td>

                                <td
                                    style="padding:15px 16px;text-align:center;white-space:nowrap;border-bottom:1px solid #f1f5f9;">
                                    <span
                                        style="display:inline-flex;align-items:center;gap:5px;padding:6px 10px;border-radius:20px;font-size:11px;font-weight:600;<?= $statusBadge ?>">
                                        <i class="fas fa-circle" style="font-size:6px;"></i>
                                        <?= htmlspecialchars(str_replace('_', ' ', $status)) ?>
                                    </span>
                                </td>

                                <td
                                    style="padding:15px 16px;text-align:center;white-space:nowrap;border-bottom:1px solid #f1f5f9;">
                                    <span
                                        style="display:inline-flex;align-items:center;gap:5px;padding:6px 10px;border-radius:20px;font-size:11px;font-weight:600;<?= $approvalBadge ?>">
                                        <i class="fas <?= $approvalText === 'Approved' ? 'fa-check' : 'fa-clock' ?>"></i>
                                        <?= $approvalText ?>
                                    </span>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div style="text-align:center;padding:55px 20px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;">

                <div
                    style="width:60px;height:60px;margin:0 auto 15px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#eff6ff;color:#2563eb;font-size:25px;">
                    <i class="fas fa-calendar-times"></i>
                </div>

                <h5 style="margin:0 0 5px;color:#374151;font-weight:600;">
                    No Attendance Records
                </h5>

                <p style="margin:0;color:#9ca3af;font-size:13px;">
                    You don't have any attendance records yet.
                </p>

            </div>

        <?php endif; ?>

    </section>

</div>