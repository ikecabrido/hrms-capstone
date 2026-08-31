<div class="employee-dashboard">
    <section class="dashboard-welcome" id="dashboardWelcome">
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>
        <div class="welcome-content">
            <span class="welcome-label" id="welcomeLabel">
                <i class="fas fa-circle"></i>
                ADMIN GRIEVANCE MODULE
            </span>
            <h1 id="welcomeTitle">
                Grievance Management
            </h1>
            <p id="welcomeDescription">
                Monitor, review, and manage employee grievances from initial
                submission through investigation, response, and resolution.
                Access case details, track case status, and ensure each grievance
                is handled fairly, efficiently, and in accordance with organizational procedures.
            </p>
            <div class="welcome-line"></div>
        </div>
        <div class="welcome-decoration" id="welcomeDecoration">
            <i class="fas fa-balance-scale"></i>
        </div>
    </section>


    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <div class="grievance-table-container" style="
        width:100%;
        background:#ffffff;
        border:1px solid #e2e8f0;
        border-radius:12px;
        overflow:hidden;
        box-shadow:0 2px 8px rgba(15,23,42,.04);
    ">


        <!-- TABLE HEADER -->
        <div style="
        padding:16px 20px;
        border-bottom:1px solid #e2e8f0;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:15px;
        flex-wrap:wrap;
    ">

            <div>
                <h5 style="
                margin:0;
                color:#1e293b;
                font-size:15px;
                font-weight:700;
            ">
                    Grievance Cases
                </h5>

                <p style="
                margin:4px 0 0;
                color:#94a3b8;
                font-size:11px;
            ">
                    Review and manage submitted employee grievances.
                </p>
            </div>

            <div style="
            display:flex;
            align-items:center;
            gap:8px;
            padding:7px 11px;
            border:1px solid #e2e8f0;
            border-radius:7px;
            background:#f8fafc;
            color:#64748b;
            font-size:11px;
            font-weight:600;
        ">
                <i class="fas fa-folder-open" style="color:#2563eb;"></i>

                <?= count($allGrievance ?? []) ?>
                <?= count($allGrievance ?? []) == 1 ? 'Case' : 'Cases' ?>
            </div>

        </div>


        <!-- SEARCH / FILTER BAR -->

        <div style="
        padding:12px 20px;
        border-bottom:1px solid #e2e8f0;
        background:#f8fafc;
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    ">

            <div style="
            position:relative;
            flex:1;
            min-width:220px;
        ">

                <i class="fas fa-search" style="
                position:absolute;
                left:11px;
                top:50%;
                transform:translateY(-50%);
                color:#94a3b8;
                font-size:11px;
            "></i>

                <input type="text" id="grievanceSearch" placeholder="Search grievances..." style="
                width:100%;
                height:34px;
                padding:0 12px 0 32px;
                border:1px solid #dbe3ef;
                border-radius:7px;
                outline:none;
                background:#ffffff;
                color:#334155;
                font-size:11px;
            ">

            </div>


            <select id="grievanceStatusFilter" style="
            height:34px;
            min-width:135px;
            padding:0 10px;
            border:1px solid #dbe3ef;
            border-radius:7px;
            background:#ffffff;
            color:#475569;
            font-size:11px;
            outline:none;
        ">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
            </select>


            <select id="grievancePriorityFilter" style="
            height:34px;
            min-width:125px;
            padding:0 10px;
            border:1px solid #dbe3ef;
            border-radius:7px;
            background:#ffffff;
            color:#475569;
            font-size:11px;
            outline:none;
        ">
                <option value="">All Priority</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
            </select>

        </div>


        <!-- RESPONSIVE TABLE -->

        <div style="
        width:100%;
        overflow-x:auto;
        -webkit-overflow-scrolling:touch;
    ">

            <?php if (!empty($allGrievance)): ?>

                <table id="grievanceTable" style="
                width:100%;
                min-width:850px;
                border-collapse:collapse;
                table-layout:auto;
            ">

                    <thead>

                        <tr style="
                        background:#f8fafc;
                        border-bottom:1px solid #e2e8f0;
                    ">

                            <th style="
                            width:75px;
                            padding:12px 15px;
                            text-align:left;
                            color:#64748b;
                            font-size:10px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            white-space:nowrap;
                        ">
                                ID
                            </th>


                            <th style="
                            min-width:220px;
                            padding:12px 15px;
                            text-align:left;
                            color:#64748b;
                            font-size:10px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                        ">
                                Subject
                            </th>


                            <th style="
                            min-width:150px;
                            padding:12px 15px;
                            text-align:left;
                            color:#64748b;
                            font-size:10px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                        ">
                                Category
                            </th>


                            <th style="
                            width:110px;
                            padding:12px 15px;
                            text-align:center;
                            color:#64748b;
                            font-size:10px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                        ">
                                Priority
                            </th>


                            <th style="
                            width:130px;
                            padding:12px 15px;
                            text-align:center;
                            color:#64748b;
                            font-size:10px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                        ">
                                Status
                            </th>


                            <th style="
                            width:145px;
                            padding:12px 15px;
                            text-align:left;
                            color:#64748b;
                            font-size:10px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                        ">
                                Submitted
                            </th>


                            <th style="
                            width:145px;
                            padding:12px 15px;
                            text-align:center;
                            color:#64748b;
                            font-size:10px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                        ">
                                Privacy
                            </th>


                            <th style="
                            width:85px;
                            padding:12px 15px;
                            text-align:center;
                            color:#64748b;
                            font-size:10px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                        ">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($allGrievance as $grievance): ?>

                            <?php

                            $priority = strtolower(
                                trim($grievance['priority'] ?? 'low')
                            );

                            $status = strtolower(
                                trim($grievance['status'] ?? 'pending')
                            );

                            $priorityLabel = ucfirst($priority);

                            $statusLabel = ucwords(
                                str_replace('_', ' ', $status)
                            );

                            ?>

                            <tr style="
                            border-bottom:1px solid #f1f5f9;
                            transition:background .15s ease;
                        " onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">

                                <!-- ID -->

                                <td style="
                                padding:14px 15px;
                                color:#64748b;
                                font-size:11px;
                                font-weight:700;
                                white-space:nowrap;
                            ">
                                    #<?= htmlspecialchars($grievance['eer_grievance_id']) ?>
                                </td>


                                <!-- SUBJECT -->

                                <td style="
                                padding:14px 15px;
                                max-width:280px;
                            ">

                                    <div style="
                                    color:#1e293b;
                                    font-size:12px;
                                    font-weight:700;
                                    white-space:nowrap;
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                " title="<?= htmlspecialchars($grievance['subject'] ?? '-') ?>">
                                        <?= htmlspecialchars($grievance['subject'] ?? '-') ?>
                                    </div>

                                    <div style="
                                    margin-top:4px;
                                    color:#94a3b8;
                                    font-size:10px;
                                    white-space:nowrap;
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                ">
                                        <?= htmlspecialchars(
                                            mb_substr($grievance['description'] ?? '', 0, 70)
                                        ) ?>
                                        <?= strlen($grievance['description'] ?? '') > 70 ? '...' : '' ?>
                                    </div>

                                </td>


                                <!-- CATEGORY -->

                                <td style="
                                padding:14px 15px;
                                color:#475569;
                                font-size:11px;
                                font-weight:600;
                            ">

                                    <span style="
                                    display:inline-flex;
                                    align-items:center;
                                    padding:5px 9px;
                                    border-radius:6px;
                                    background:#f1f5f9;
                                    color:#475569;
                                    font-size:10px;
                                    font-weight:600;
                                    white-space:nowrap;
                                ">
                                        <?= htmlspecialchars($grievance['category'] ?? 'Uncategorized') ?>
                                    </span>

                                </td>


                                <!-- PRIORITY -->

                                <td style="
                                padding:14px 15px;
                                text-align:center;
                            ">

                                    <?php

                                    $priorityStyles = [
                                        'low' => [
                                            'background:#f0fdf4;',
                                            'color:#15803d;',
                                            'border:1px solid #bbf7d0;'
                                        ],
                                        'medium' => [
                                            'background:#fffbeb;',
                                            'color:#b45309;',
                                            'border:1px solid #fde68a;'
                                        ],
                                        'high' => [
                                            'background:#fff7ed;',
                                            'color:#c2410c;',
                                            'border:1px solid #fed7aa;'
                                        ],
                                        'urgent' => [
                                            'background:#fef2f2;',
                                            'color:#dc2626;',
                                            'border:1px solid #fecaca;'
                                        ]
                                    ];

                                    $priorityStyle = implode(
                                        '',
                                        $priorityStyles[$priority]
                                        ?? $priorityStyles['low']
                                    );

                                    ?>

                                    <span style="
                                    display:inline-flex;
                                    align-items:center;
                                    justify-content:center;
                                    min-width:65px;
                                    padding:5px 9px;
                                    border-radius:20px;
                                    font-size:9px;
                                    font-weight:700;
                                    <?= $priorityStyle ?>
                                ">
                                        <?= htmlspecialchars($priorityLabel) ?>
                                    </span>

                                </td>


                                <!-- STATUS -->

                                <td style="
                                padding:14px 15px;
                                text-align:center;
                            ">

                                    <?php

                                    $statusStyles = [
                                        'pending' => 'background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;',
                                        'in_progress' => 'background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;',
                                        'resolved' => 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;',
                                        'closed' => 'background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;'
                                    ];

                                    $statusStyle =
                                        $statusStyles[$status]
                                        ?? 'background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;';

                                    ?>

                                    <span style="
                                    display:inline-flex;
                                    align-items:center;
                                    gap:5px;
                                    padding:5px 9px;
                                    border-radius:20px;
                                    font-size:9px;
                                    font-weight:700;
                                    white-space:nowrap;
                                    <?= $statusStyle ?>
                                ">

                                        <span style="
                                        width:5px;
                                        height:5px;
                                        border-radius:50%;
                                        background:currentColor;
                                    "></span>

                                        <?= htmlspecialchars($statusLabel) ?>

                                    </span>

                                </td>


                                <!-- SUBMITTED -->

                                <td style="
                                padding:14px 15px;
                            ">

                                    <div style="
                                    color:#334155;
                                    font-size:11px;
                                    font-weight:600;
                                    white-space:nowrap;
                                ">
                                        <?= !empty($grievance['created_at'])
                                            ? date('M d, Y', strtotime($grievance['created_at']))
                                            : '-' ?>
                                    </div>

                                    <div style="
                                    margin-top:3px;
                                    color:#94a3b8;
                                    font-size:9px;
                                ">
                                        <?= !empty($grievance['created_at'])
                                            ? date('h:i A', strtotime($grievance['created_at']))
                                            : '' ?>
                                    </div>

                                </td>


                                <!-- PRIVACY -->

                                <td style="
                                padding:14px 15px;
                                text-align:center;
                            ">

                                    <div style="
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    gap:5px;
                                    flex-wrap:wrap;
                                ">

                                        <?php if ((int) ($grievance['anonymous'] ?? 0) === 1): ?>

                                            <span style="
                                            display:inline-flex;
                                            align-items:center;
                                            gap:4px;
                                            padding:4px 7px;
                                            border-radius:5px;
                                            background:#f1f5f9;
                                            color:#475569;
                                            font-size:8px;
                                            font-weight:700;
                                        ">
                                                <i class="fas fa-user-secret"></i>
                                                Anonymous
                                            </span>

                                        <?php endif; ?>


                                        <?php if ((int) ($grievance['confidential'] ?? 0) === 1): ?>

                                            <span style="
                                            display:inline-flex;
                                            align-items:center;
                                            gap:4px;
                                            padding:4px 7px;
                                            border-radius:5px;
                                            background:#fef2f2;
                                            color:#b91c1c;
                                            font-size:8px;
                                            font-weight:700;
                                        ">
                                                <i class="fas fa-lock"></i>
                                                Confidential
                                            </span>

                                        <?php endif; ?>


                                        <?php if (
                                            (int) ($grievance['anonymous'] ?? 0) === 0 &&
                                            (int) ($grievance['confidential'] ?? 0) === 0
                                        ): ?>

                                            <span style="
                                            color:#94a3b8;
                                            font-size:10px;
                                        ">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <!-- ACTION -->

                                <td style="
                                padding:14px 15px;
                                text-align:center;
                            ">

                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#grievanceViewModal"
                                        data-bs-id="<?= htmlspecialchars($grievance['eer_grievance_id']) ?>">
                                        <i class="fas fa-eye"></i>
                                        View
                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <!-- EMPTY STATE -->

                <div style="
                padding:65px 20px;
                text-align:center;
                background:#ffffff;
            ">

                    <div style="
                    width:54px;
                    height:54px;
                    margin:0 auto 14px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border-radius:50%;
                    background:#eff6ff;
                    color:#2563eb;
                    font-size:20px;
                ">
                        <i class="fas fa-inbox"></i>
                    </div>

                    <h5 style="
                    margin:0 0 5px;
                    color:#334155;
                    font-size:14px;
                    font-weight:700;
                ">
                        No Grievances Found
                    </h5>

                    <p style="
                    margin:0;
                    color:#94a3b8;
                    font-size:11px;
                ">
                        There are currently no grievance cases to display.
                    </p>

                </div>

            <?php endif; ?>

        </div>


        <!-- TABLE FOOTER -->

        <?php if (!empty($allGrievance)): ?>

            <div style="
            padding:12px 20px;
            border-top:1px solid #e2e8f0;
            background:#ffffff;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            flex-wrap:wrap;
        ">

                <div style="
                color:#94a3b8;
                font-size:10px;
            ">
                    Showing
                    <strong style="color:#475569;">
                        <?= count($allGrievance) ?>
                    </strong>
                    grievance cases
                </div>

            </div>

        <?php endif; ?>


    </div>


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