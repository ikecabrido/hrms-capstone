<div class="employee-dashboard">

    <section class="dashboard-welcome" id="dashboardWelcome">
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">
            <span class="welcome-label" id="welcomeLabel">
                <i class="fas fa-circle"></i>
                COMPLAINT & GRIEVANCE MANAGEMENT
            </span>

            <h1 id="welcomeTitle">
                Employee Complaints
            </h1>

            <p id="welcomeDescription">
                Review and manage employee complaints, grievances, case details,
                supporting information, and resolution progress to ensure concerns
                are handled properly and efficiently.
            </p>

            <div class="welcome-line"></div>
        </div>

        <div class="welcome-decoration" id="welcomeDecoration">
            <i class="fas fa-comment-dots"></i>
        </div>
    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <div class="complaint-table-container">


        <div class="complaint-table-top">
            <div>
                <h2>Complaint Management</h2>
                <p>Employee complaints and reports</p>
            </div>

            <div class="complaint-total">
                <?= count($allComplaints ?? []) ?> Complaints
            </div>
        </div>

        <div class="complaint-table-box">

            <?php if (!empty($allComplaints)): ?>

                <div class="complaint-table-scroll">

                    <table class="complaint-admin-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Employee</th>
                                <th>Complaint</th>
                                <th>Type</th>
                                <th>Severity</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($allComplaints as $complaint): ?>

                                <?php
                                $severity = strtolower(
                                    $complaint['severity'] ?? 'low'
                                );

                                $status = strtolower(
                                    $complaint['status'] ?? 'unknown'
                                );

                                $statusLabel = ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $complaint['status'] ?? 'Unknown'
                                    )
                                );

                                $employeeName =
                                    $complaint['reporter_name']
                                    ?? 'Unknown';

                                $initial = strtoupper(
                                    substr(trim($employeeName), 0, 1)
                                );
                                ?>

                                <tr>

                                    <!-- ID -->
                                    <td>
                                        <span class="complaint-id">
                                            #<?= htmlspecialchars($complaint['id']) ?>
                                        </span>
                                    </td>

                                    <!-- EMPLOYEE -->
                                    <td>
                                        <div class="employee-cell">

                                            <div class="employee-avatar">
                                                <?= htmlspecialchars($initial) ?>
                                            </div>

                                            <div>
                                                <div class="employee-name">
                                                    <?= htmlspecialchars($employeeName) ?>
                                                </div>

                                                <div class="employee-department">
                                                    <?= htmlspecialchars(
                                                        $complaint['reporter_department']
                                                        ?? 'Employee'
                                                    ) ?>
                                                </div>
                                            </div>

                                        </div>
                                    </td>

                                    <!-- COMPLAINT -->
                                    <td>
                                        <div class="complaint-cell">

                                            <div class="complaint-title">
                                                <?= htmlspecialchars(
                                                    $complaint['title'] ?? '-'
                                                ) ?>
                                            </div>

                                            <div class="complaint-description">
                                                <?= htmlspecialchars(
                                                    $complaint['description'] ?? ''
                                                ) ?>
                                            </div>

                                        </div>
                                    </td>

                                    <!-- TYPE -->
                                    <td>
                                        <span class="complaint-type">
                                            <?= htmlspecialchars(
                                                $complaint['type'] ?? '-'
                                            ) ?>
                                        </span>
                                    </td>

                                    <!-- SEVERITY -->
                                    <td>
                                        <span class="severity severity-<?= htmlspecialchars($severity) ?>">
                                            <?= htmlspecialchars(
                                                $complaint['severity'] ?? '-'
                                            ) ?>
                                        </span>
                                    </td>

                                    <!-- STATUS -->
                                    <td>
                                        <span class="complaint-status status-<?= htmlspecialchars($status) ?>">
                                            <span></span>
                                            <?= htmlspecialchars($statusLabel) ?>
                                        </span>
                                    </td>

                                    <!-- ASSIGNED -->
                                    <td>

                                        <?php if (!empty($complaint['assigned_name'])): ?>

                                            <div class="assigned-cell">

                                                <div class="assigned-avatar">
                                                    <?= strtoupper(
                                                        substr(
                                                            trim(
                                                                $complaint['assigned_name']
                                                            ),
                                                            0,
                                                            1
                                                        )
                                                    ) ?>
                                                </div>

                                                <span>
                                                    <?= htmlspecialchars(
                                                        $complaint['assigned_name']
                                                    ) ?>
                                                </span>

                                            </div>

                                        <?php else: ?>

                                            <span class="unassigned">
                                                Unassigned
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <!-- CREATED -->
                                    <td>

                                        <div class="created-cell">

                                            <strong>
                                                <?= !empty($complaint['created_at'])
                                                    ? date(
                                                        'M d, Y',
                                                        strtotime(
                                                            $complaint['created_at']
                                                        )
                                                    )
                                                    : '-'
                                                    ?>
                                            </strong>

                                            <small>
                                                <?= !empty($complaint['created_at'])
                                                    ? date(
                                                        'h:i A',
                                                        strtotime(
                                                            $complaint['created_at']
                                                        )
                                                    )
                                                    : ''
                                                    ?>
                                            </small>

                                        </div>

                                    </td>

                                    <!-- ACTION -->
                                    <td>

                                        <button type="button" class="complaint-view-btn" data-bs-toggle="modal"
                                            data-bs-target="#complaintViewModal"
                                            data-bs-id="<?= htmlspecialchars($complaint['id']) ?>">
                                            <i class="fas fa-eye"></i>
                                            View
                                        </button>


                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="complaint-empty">
                    <i class="fas fa-inbox"></i>

                    <h3>No Complaints Found</h3>

                    <p>
                        There are currently no employee complaints to display.
                    </p>
                </div>

            <?php endif; ?>

        </div>


    </div>

    <style>
        /* =========================================
   COMPLAINT TABLE
========================================= */

        .complaint-table-container {
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }


        /* HEADER */

        .complaint-table-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .complaint-table-top h2 {
            margin: 0;
            font-size: 21px;
            font-weight: 700;
            color: #1f2937;
        }

        .complaint-table-top p {
            margin: 5px 0 0;
            font-size: 13px;
            color: #6b7280;
        }

        .complaint-total {
            padding: 8px 13px;
            border: 1px solid #e5e7eb;
            border-radius: 7px;
            background: #fff;
            color: #4b5563;
            font-size: 12px;
            font-weight: 600;
        }


        /* TABLE BOX */

        .complaint-table-box {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
        }

        .complaint-table-scroll {
            width: 100%;
            overflow-x: auto;
        }


        /* TABLE */

        .complaint-admin-table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
        }

        .complaint-admin-table thead {
            background: #f8fafc;
        }

        .complaint-admin-table th {
            padding: 13px 14px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            white-space: nowrap;
        }

        .complaint-admin-table td {
            padding: 14px;
            border-bottom: 1px solid #f1f5f9;
            color: #374151;
            font-size: 13px;
            vertical-align: middle;
        }

        .complaint-admin-table tbody tr {
            transition: background .15s ease;
        }

        .complaint-admin-table tbody tr:hover {
            background: #f8fbff;
        }

        .complaint-admin-table tbody tr:last-child td {
            border-bottom: 0;
        }


        /* ID */

        .complaint-id {
            display: inline-block;
            padding: 4px 7px;
            background: #f3f4f6;
            border-radius: 5px;
            color: #4b5563;
            font-size: 11px;
            font-weight: 600;
        }


        /* EMPLOYEE */

        .employee-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 170px;
        }

        .employee-avatar {
            width: 34px;
            height: 34px;
            min-width: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #e8f0fe;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
        }

        .employee-name {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #1f2937;
            font-weight: 600;
        }

        .employee-department {
            margin-top: 3px;
            color: #9ca3af;
            font-size: 11px;
        }


        /* COMPLAINT */

        .complaint-cell {
            max-width: 270px;
        }

        .complaint-title {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #1f2937;
            font-weight: 600;
        }

        .complaint-description {
            margin-top: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #9ca3af;
            font-size: 11px;
            max-width: 270px;
        }

        .complaint-type {
            color: #4b5563;
            font-size: 12px;
            white-space: nowrap;
        }


        /* SEVERITY */

        .severity {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .severity-low {
            background: #ecfdf5;
            color: #15803d;
        }

        .severity-medium {
            background: #fff7ed;
            color: #c2410c;
        }

        .severity-high {
            background: #fef2f2;
            color: #dc2626;
        }

        .severity-critical {
            background: #fee2e2;
            color: #991b1b;
        }


        /* STATUS */

        .complaint-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .complaint-status>span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }


        /* INITIAL REVIEW */

        .status-under_initial_review {
            background: #eff6ff;
            color: #2563eb;
        }

        .status-under_initial_review>span {
            background: #3b82f6;
        }


        /* INVESTIGATION */

        .status-under_investigation {
            background: #f5f3ff;
            color: #7c3aed;
        }

        .status-under_investigation>span {
            background: #8b5cf6;
        }


        /* PENDING EMPLOYEE */

        .status-pending_employee_response {
            background: #fffbeb;
            color: #b45309;
        }

        .status-pending_employee_response>span {
            background: #f59e0b;
        }


        /* FOR DECISION */

        .status-for_decision {
            background: #ecfeff;
            color: #0e7490;
        }

        .status-for_decision>span {
            background: #06b6d4;
        }


        /* CLOSED */

        .status-closed_termination_recommended {
            background: #f3f4f6;
            color: #4b5563;
        }

        .status-closed_termination_recommended>span {
            background: #6b7280;
        }


        /* ASSIGNED */

        .assigned-cell {
            display: flex;
            align-items: center;
            gap: 7px;
            max-width: 160px;
        }

        .assigned-avatar {
            width: 27px;
            height: 27px;
            min-width: 27px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f3f4f6;
            color: #4b5563;
            font-size: 10px;
            font-weight: 700;
        }

        .assigned-cell span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #4b5563;
            font-size: 12px;
        }

        .unassigned {
            color: #9ca3af;
            font-size: 12px;
            font-style: italic;
        }


        /* CREATED */

        .created-cell strong {
            display: block;
            color: #374151;
            font-size: 12px;
            white-space: nowrap;
        }

        .created-cell small {
            display: block;
            margin-top: 3px;
            color: #9ca3af;
            font-size: 10px;
        }


        /* VIEW BUTTON */

        .complaint-view-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 32px;
            padding: 0 10px;
            border: 1px solid #dbe3ef;
            border-radius: 6px;
            background: #fff;
            color: #2563eb;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s ease;
        }

        .complaint-view-btn:hover {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        .complaint-view-btn i {
            font-size: 11px;
        }


        /* EMPTY */

        .complaint-empty {
            padding: 70px 20px;
            text-align: center;
        }

        .complaint-empty>i {
            display: flex;
            width: 55px;
            height: 55px;
            margin: 0 auto 14px;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f3f4f6;
            color: #9ca3af;
            font-size: 20px;
        }

        .complaint-empty h3 {
            margin: 0 0 5px;
            color: #374151;
            font-size: 15px;
        }

        .complaint-empty p {
            margin: 0;
            color: #9ca3af;
            font-size: 12px;
        }


        /* SCROLLBAR */

        .complaint-table-scroll::-webkit-scrollbar {
            height: 7px;
        }

        .complaint-table-scroll::-webkit-scrollbar-track {
            background: #f8fafc;
        }

        .complaint-table-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }


        /* RESPONSIVE */

        @media (max-width: 768px) {

            .complaint-table-container {
                padding: 12px;
            }

            .complaint-table-top {
                align-items: flex-start;
            }

            .complaint-table-top h2 {
                font-size: 18px;
            }

            .complaint-total {
                font-size: 11px;
            }

        }
    </style>

</div>
<?php require __DIR__ . '/view.php'; ?>
<script>
    document.querySelectorAll('.modal').forEach(modal => {
        document.body.appendChild(modal);
    });
</script>
<style>
    .modal {
        z-index: 1055 !important;
    }

    .modal-backdrop {
        z-index: 1050 !important;
    }
</style>