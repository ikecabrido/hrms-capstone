<div class="employee-dashboard">

    <section class="dashboard-welcome" id="dashboardWelcome">

        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label" id="welcomeLabel">
                <i class="fas fa-circle"></i>
                RESIGNATION MANAGEMENT
            </span>

            <h1 id="welcomeTitle">
                Employee Resignation
            </h1>

            <p id="welcomeDescription">
                Review and manage employee resignation requests, submitted resignation
                letters, effective dates, reasons, and resignation status.
            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration" id="welcomeDecoration">
            <i class="fas fa-sign-out-alt"></i>
        </div>

    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section id="dashboardWelcome" class="w-full min-h-0 bg-slate-50 p-3 sm:p-4 lg:p-5">

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">

            <!-- HEADER -->
            <div
                class="px-4 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                <div>
                    <h2 class="text-sm font-bold text-slate-800">
                        Employee Resignation Requests
                    </h2>

                    <p class="text-[10px] text-slate-400 mt-1">
                        Review and manage resignation requests submitted by employees.
                    </p>
                </div>

                <div class="flex items-center gap-2">

                    <span
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-[10px] font-semibold text-slate-500">
                        <i class="fas fa-users"></i>
                        <?= count($resignations) ?> Requests
                    </span>

                </div>

            </div>


            <!-- TABLE -->
            <div class="w-full overflow-x-auto overflow-y-hidden" style="
        border-radius:12px;
        -webkit-overflow-scrolling:touch;
        scrollbar-width:thin;
    ">

                <table id="resignationTable" class="w-full min-w-[1100px] text-left" style="
            border-collapse:separate;
            border-spacing:0;
        ">

                    <thead class="bg-slate-50 border-b border-slate-200" style="
                position:sticky;
                top:0;
                z-index:10;
                box-shadow:0 1px 0 #e2e8f0;
            ">

                        <tr>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400">ID</th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400">Employee</th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400">Resignation Type</th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400">Reason</th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400">Date Submitted</th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400">Last Working Day</th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 text-center">Status</th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 text-right">Action</th>

                        </tr>

                    </thead>

                    <tbody id="resignationTableBody">

                        <?php if (!empty($resignations)): ?>

                            <?php foreach ($resignations as $resignation): ?>

                                <?php

                                $resignationId = (int) ($resignation['resignation_id'] ?? 0);

                                $employeeName = trim(
                                    $resignation['employee_name'] ?? 'Unknown Employee'
                                );

                                $status = strtolower(
                                    trim($resignation['status'] ?? 'pending')
                                );

                                $statusStyle = match ($status) {
                                    'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                    'completed' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    default => 'bg-amber-50 text-amber-700 border-amber-200'
                                };

                                $avatarLetter = strtoupper(
                                    substr($employeeName, 0, 1)
                                );

                                $dateSubmitted = !empty($resignation['date_submitted'])
                                    ? date('M d, Y', strtotime($resignation['date_submitted']))
                                    : '—';

                                $lastWorkingDay = !empty($resignation['intended_last_working_day'])
                                    ? date('M d, Y', strtotime($resignation['intended_last_working_day']))
                                    : '—';

                                ?>

                                <tr class="resignation-row border-b border-slate-100 transition" style="background:#fff;"
                                    onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">

                                    <!-- ID -->
                                    <td class="px-4 py-3">

                                        <div
                                            class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] font-bold">
                                            <?= $resignationId ?>
                                        </div>

                                    </td>

                                    <!-- EMPLOYEE -->
                                    <td class="px-4 py-3">

                                        <div class="flex items-center gap-3 min-w-[180px]">

                                            <div
                                                class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold shrink-0">
                                                <?= htmlspecialchars($avatarLetter) ?>
                                            </div>

                                            <div class="min-w-0">

                                                <div class="text-xs font-bold text-slate-800 truncate">
                                                    <?= htmlspecialchars($employeeName) ?>
                                                </div>

                                                <div class="text-[10px] text-slate-400 mt-0.5">
                                                    Employee ID:
                                                    <?= htmlspecialchars($resignation['employee_id'] ?? '—') ?>
                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                    <!-- TYPE -->
                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <span class="text-xs font-semibold text-slate-700">
                                            <?= htmlspecialchars($resignation['resignation_type'] ?? '—') ?>
                                        </span>

                                    </td>

                                    <!-- REASON -->
                                    <td class="px-4 py-3">

                                        <div class="max-w-[220px]">

                                            <div class="text-xs font-semibold text-slate-700 truncate">
                                                <?= htmlspecialchars($resignation['resignation_reason'] ?? '—') ?>
                                            </div>

                                            <?php if (!empty($resignation['employee_remarks'])): ?>

                                                <div class="text-[10px] text-slate-400 mt-1 truncate">
                                                    <?= htmlspecialchars($resignation['employee_remarks']) ?>
                                                </div>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                    <!-- DATE -->
                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <span class="text-xs font-semibold text-slate-700">
                                            <?= htmlspecialchars($dateSubmitted) ?>
                                        </span>

                                    </td>

                                    <!-- LAST WORKING DAY -->
                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <span class="text-xs font-semibold text-slate-700">
                                            <?= htmlspecialchars($lastWorkingDay) ?>
                                        </span>

                                    </td>

                                    <!-- STATUS -->
                                    <td class="px-4 py-3 text-center whitespace-nowrap">

                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full border text-[9px] font-bold uppercase <?= $statusStyle ?>">

                                            <?php if ($status === 'approved'): ?>

                                                <i class="fas fa-check-circle"></i>

                                            <?php elseif ($status === 'rejected'): ?>

                                                <i class="fas fa-times-circle"></i>

                                            <?php elseif ($status === 'completed'): ?>

                                                <i class="fas fa-flag-checkered"></i>

                                            <?php else: ?>

                                                <i class="fas fa-clock"></i>

                                            <?php endif; ?>

                                            <?= htmlspecialchars($resignation['status'] ?? 'Pending') ?>

                                        </span>

                                    </td>

                                    <!-- ACTION -->
                                    <td class="px-4 py-3">

                                        <div class="flex justify-end gap-2">

                                            <button type="button" onclick="viewResignation(
                                        <?= $resignationId ?>,
                                        '<?= htmlspecialchars($employeeName, ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($resignation['resignation_type'] ?? '—', ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($resignation['resignation_reason'] ?? '—', ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($dateSubmitted, ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($lastWorkingDay, ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($resignation['status'] ?? 'Pending', ENT_QUOTES) ?>'
                                    )"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 text-[10px] font-bold transition">

                                                <i class="fas fa-eye"></i>
                                                View

                                            </button>

                                            <?php if ($status === 'pending'): ?>

                                                <button type="button" onclick="openApproveModal(
                                            <?= $resignationId ?>,
                                            '<?= htmlspecialchars($employeeName, ENT_QUOTES) ?>',
                                            '<?= htmlspecialchars($lastWorkingDay, ENT_QUOTES) ?>'
                                        )"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold transition">

                                                    <i class="fas fa-check"></i>
                                                    Approve

                                                </button>

                                                <button type="button" onclick="openRejectModal(
                                            <?= $resignationId ?>,
                                            '<?= htmlspecialchars($employeeName, ENT_QUOTES) ?>'
                                        )"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-300 text-slate-600 hover:bg-slate-50 text-[10px] font-bold transition">

                                                    <i class="fas fa-times"></i>
                                                    Reject

                                                </button>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr id="emptyResignationRow">

                                <td colspan="8" class="px-4 py-12 text-center">

                                    <div
                                        class="w-12 h-12 mx-auto rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center">
                                        <i class="fas fa-file-signature"></i>
                                    </div>

                                    <p class="mt-3 text-xs font-semibold text-slate-600">
                                        No Resignation Requests
                                    </p>

                                    <p class="mt-1 text-[10px] text-slate-400">
                                        Employee resignation requests will appear here.
                                    </p>

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>
            <!-- PAGINATION -->
            <div style="
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:15px;
        padding:14px 16px;
        border-top:1px solid #e2e8f0;
        background:#ffffff;
        flex-wrap:wrap;
    ">

                <div style="
            display:flex;
            align-items:center;
            gap:7px;
            font-size:10px;
            color:#64748b;
        ">

                    <span>Show</span>

                    <select id="rowsPerPage" onchange="changeRowsPerPage()" style="
                padding:5px 8px;
                border:1px solid #cbd5e1;
                border-radius:7px;
                background:#fff;
                color:#334155;
                font-size:10px;
                outline:none;
                cursor:pointer;
            ">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                    </select>

                    <span>rows</span>

                </div>


                <div id="paginationInfo" style="
            font-size:10px;
            color:#64748b;
        ">
                    Showing 0–0 of 0
                </div>


                <div id="paginationButtons" style="
            display:flex;
            align-items:center;
            gap:4px;
        ">
                </div>

            </div>

        </div>

    </section>
</div>

<?php require __DIR__ . '/view.php'; ?>
<?php require __DIR__ . '/approve.php'; ?>
<?php require __DIR__ . '/reject.php'; ?>

<script src="/hrms-capstone/modules/portal/public/js/function/contentResignationAdmin.js"></script>

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