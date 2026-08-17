<div class="employee-dashboard">

    <section class="dashboard-welcome" id="dashboardWelcome">

        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label" id="welcomeLabel">
                <i class="fas fa-circle"></i>
                LEAVE MANAGEMENT
            </span>

            <h1 id="welcomeTitle">
                Employee Leave Requests
            </h1>

            <p id="welcomeDescription">
                View and manage employee leave requests, leave types, request dates,
                reasons, and approval status.
            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration" id="welcomeDecoration">
            <i class="fas fa-calendar-alt"></i>
        </div>

    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section id="dashboardWelcome" class="w-full min-h-0 bg-slate-50 p-3 sm:p-4 lg:p-5">

        <!-- HEADER -->
        <div class="mb-5">
            <h1 class="text-lg sm:text-xl font-bold text-slate-800">
                Employee Leave Requests
            </h1>
            <p class="mt-1 text-xs text-slate-500">
                Review, approve, and manage employee leave requests.
            </p>
        </div>

        <?php
        $pendingCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;

        foreach ($leaveHistory as $leave) {
            $status = strtolower(trim($leave['status'] ?? 'pending'));

            if ($status === 'pending') {
                $pendingCount++;
            } elseif ($status === 'approved') {
                $approvedCount++;
            } elseif ($status === 'rejected') {
                $rejectedCount++;
            }
        }
        ?>

        <!-- STAT CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">

            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-400">
                            Pending
                        </p>
                        <p class="mt-1 text-xl font-bold text-amber-600">
                            <?= $pendingCount ?>
                        </p>
                    </div>

                    <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i class="fas fa-clock text-sm"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-400">
                            Approved
                        </p>
                        <p class="mt-1 text-xl font-bold text-emerald-600">
                            <?= $approvedCount ?>
                        </p>
                    </div>

                    <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i class="fas fa-check-circle text-sm"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-400">
                            Rejected
                        </p>
                        <p class="mt-1 text-xl font-bold text-red-600">
                            <?= $rejectedCount ?>
                        </p>
                    </div>

                    <div class="w-9 h-9 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                        <i class="fas fa-times-circle text-sm"></i>
                    </div>
                </div>
            </div>

        </div>

        <!-- TABLE CARD -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">

            <!-- TABLE HEADER -->
            <div
                class="px-4 py-3 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                <div>
                    <h2 class="text-sm font-bold text-slate-800">
                        Leave Requests
                    </h2>
                    <p class="text-[10px] text-slate-400 mt-0.5">
                        Review employee requests and take appropriate action.
                    </p>
                </div>

                <select id="leaveStatusFilter"
                    class="h-9 px-3 border border-slate-200 rounded-lg text-xs text-slate-600 bg-white outline-none focus:border-blue-500">
                    <option value="all">All Requests</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>

            </div>

            <!-- TABLE -->
            <div class="overflow-x-auto">

                <table class="w-full text-left">

                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400">
                                ID
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400">
                                Employee
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400">
                                Leave Request
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400">
                                Dates
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 text-center">
                                Days
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 text-center">
                                Document
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 text-center">
                                Status
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 text-right">
                                Action
                            </th>

                        </tr>
                    </thead>

                    <tbody id="leaveRequestTable">

                        <?php if (!empty($leaveHistory)): ?>

                            <?php foreach ($leaveHistory as $leave): ?>

                                <?php

                                $employeeName = trim(
                                    ($leave['first_name'] ?? '') . ' ' . ($leave['last_name'] ?? '')
                                );

                                $avatarLetter = strtoupper(substr($leave['first_name'] ?? 'E', 0, 1));
                                $requestId = (int) (
                                    $leave['leave_request_id']
                                    ?? $leave['id']
                                    ?? $leave['lr_id']
                                    ?? 0
                                );

                                $status = strtolower(
                                    trim($leave['status'] ?? 'pending')
                                );

                                $start = !empty($leave['start_date'])
                                    ? strtotime($leave['start_date'])
                                    : false;

                                $end = !empty($leave['end_date'])
                                    ? strtotime($leave['end_date'])
                                    : false;

                                $businessDays = 0;

                                if ($start && $end) {
                                    for (
                                        $date = $start;
                                        $date <= $end;
                                        $date = strtotime('+1 day', $date)
                                    ) {
                                        if (date('N', $date) < 6) {
                                            $businessDays++;
                                        }
                                    }
                                }

                                $statusStyle = match ($status) {
                                    'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                    'cancelled' => 'bg-slate-50 text-slate-500 border-slate-200',
                                    default => 'bg-amber-50 text-amber-700 border-amber-200'
                                };

                                $employeeName =
                                    $leave['employee_name']
                                    ?? $leave['full_name']
                                    ?? (
                                        trim(
                                            ($leave['first_name'] ?? '') . ' ' .
                                            ($leave['last_name'] ?? '')
                                        )
                                    );

                                $employeeName = trim($employeeName) ?: 'Unknown Employee';

                                $department =
                                    $leave['department_name']
                                    ?? $leave['department']
                                    ?? '—';

                                $avatarLetter = strtoupper(
                                    substr($employeeName, 0, 1)
                                );

                                $document = trim(
                                    $leave['supporting_document'] ?? ''
                                );
                                ?>

                                <tr data-status="<?= htmlspecialchars($status) ?>"
                                    class="border-b border-slate-100 hover:bg-slate-50 transition">

                                    <!-- ID -->
                                    <td class="px-4 py-3">

                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold shrink-0">
                                                <?= $leave['id'] ?>
                                            </div>

                                        </div>

                                    </td>
                                    <!-- EMPLOYEE -->
                                    <td class="px-4 py-3">

                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold shrink-0">
                                                <?= htmlspecialchars($avatarLetter) ?>
                                            </div>

                                            <div class="min-w-0">
                                                <div class="text-xs font-bold text-slate-800 truncate">
                                                    <?= htmlspecialchars($employeeName) ?>
                                                </div>

                                                <div class="text-[10px] text-slate-400 mt-0.5">
                                                    <?= htmlspecialchars($department) ?>
                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                    <!-- LEAVE TYPE -->
                                    <td class="px-4 py-3">

                                        <div class="text-xs font-semibold text-slate-700">
                                            <?= htmlspecialchars(
                                                $leave['leave_type_name'] ?? '—'
                                            ) ?>
                                        </div>

                                        <div class="text-[10px] text-slate-400 mt-1 max-w-[180px] truncate">
                                            <?= htmlspecialchars(
                                                $leave['details'] ?? ''
                                            ) ?>
                                        </div>

                                    </td>

                                    <!-- DATES -->
                                    <td class="px-4 py-3">

                                        <div class="text-xs font-semibold text-slate-700">

                                            <?php if ($start): ?>
                                                <?= date('M d, Y', $start) ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>

                                        </div>

                                        <?php if ($end && $start && $start !== $end): ?>

                                            <div class="text-[10px] text-slate-400 mt-1">
                                                to <?= date('M d, Y', $end) ?>
                                            </div>

                                        <?php endif; ?>

                                    </td>

                                    <!-- DAYS -->
                                    <td class="px-4 py-3 text-center">

                                        <span class="text-xs font-bold text-slate-700">
                                            <?= $businessDays ?>
                                        </span>

                                    </td>

                                    <!-- DOCUMENT -->
                                    <td class="px-4 py-3 text-center">

                                        <?php if ($document): ?>

                                            <a href="<?= htmlspecialchars(
                                                'assets/uploads/leave/' . $document
                                            ) ?>" target="_blank"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition"
                                                title="View supporting document">
                                                <i class="fas fa-file-alt text-xs"></i>
                                            </a>

                                        <?php else: ?>

                                            <span class="text-[10px] text-slate-400">
                                                None
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <!-- STATUS -->
                                    <td class="px-4 py-3 text-center">

                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full border text-[9px] font-bold uppercase <?= $statusStyle ?>">

                                            <?php if ($status === 'approved'): ?>
                                                <i class="fas fa-check-circle"></i>
                                            <?php elseif ($status === 'rejected'): ?>
                                                <i class="fas fa-times-circle"></i>
                                            <?php elseif ($status === 'cancelled'): ?>
                                                <i class="fas fa-ban"></i>
                                            <?php else: ?>
                                                <i class="fas fa-clock"></i>
                                            <?php endif; ?>

                                            <?= htmlspecialchars($leave['status'] ?? 'Pending') ?>

                                        </span>

                                    </td>

                                    <!-- ACTION -->
                                    <td class="px-4 py-3">

                                        <?php if ($status === 'pending'): ?>

                                            <div class="flex justify-end gap-2">

                                                <!-- APPROVE -->
                                                <form action="index.php?url=leave-approve" method="POST">

                                                    <input type="hidden" name="leave_request_id" value="<?= $requestId ?>">

                                                    <button type="submit"
                                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold transition"
                                                        title="Approve request">

                                                        <i class="fas fa-check"></i>
                                                        Approve

                                                    </button>

                                                </form>

                                                <!-- REJECT -->
                                                <button type="button" onclick="openRejectModal(
                                                    <?= $requestId ?>,
                                                    '<?= htmlspecialchars(
                                                        $employeeName,
                                                        ENT_QUOTES
                                                    ) ?>',
                                                    '<?= htmlspecialchars(
                                                        $leave['leave_type_name'] ?? 'Leave',
                                                        ENT_QUOTES
                                                    ) ?>',
                                                    '<?= $start ? date('M d, Y', $start) : '—' ?>',
                                                    '<?= $end ? date('M d, Y', $end) : '—' ?>'
                                                )"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold transition">

                                                    <i class="fas fa-times"></i>
                                                    Reject

                                                </button>

                                            </div>

                                        <?php else: ?>

                                            <div class="text-right text-[10px] text-slate-400">
                                                No action
                                            </div>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center">

                                    <div
                                        class="w-12 h-12 mx-auto rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center">
                                        <i class="fas fa-calendar-times"></i>
                                    </div>

                                    <p class="mt-3 text-xs font-semibold text-slate-600">
                                        No Leave Requests
                                    </p>

                                    <p class="mt-1 text-[10px] text-slate-400">
                                        Employee leave requests will appear here.
                                    </p>

                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </section>


    <!-- REJECT MODAL -->
    <?php require __DIR__ . '/reject.php'; ?>


    <script>
        function openRejectModal(id, employee, leaveType, start, end) {

            document.getElementById('rejectLeaveId').value = id;
            document.getElementById('rejectEmployee').textContent = employee;
            document.getElementById('rejectLeaveType').textContent = leaveType;
            document.getElementById('rejectDates').textContent =
                start === end ? start : start + ' - ' + end;

            document.getElementById('rejectReason').value = '';
            document.getElementById('quickRejectReason').value = '';

            const modal = document.getElementById('rejectLeaveModal');

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            updateRejectButton();
        }

        function closeRejectModal() {

            const modal = document.getElementById('rejectLeaveModal');

            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function updateRejectButton() {

            const reason = document
                .getElementById('rejectReason')
                .value
                .trim();

            document.getElementById('confirmRejectButton').disabled =
                reason.length === 0;
        }

        document
            .getElementById('rejectReason')
            .addEventListener('input', updateRejectButton);

        document
            .getElementById('quickRejectReason')
            .addEventListener('change', function () {

                if (this.value) {
                    document.getElementById('rejectReason').value = this.value;
                    updateRejectButton();
                }

            });

        document
            .getElementById('rejectLeaveModal')
            .addEventListener('click', function (e) {

                if (e.target === this) {
                    closeRejectModal();
                }

            });


        // STATUS FILTER
        document
            .getElementById('leaveStatusFilter')
            .addEventListener('change', function () {

                const selected = this.value;

                document
                    .querySelectorAll('#leaveRequestTable tr[data-status]')
                    .forEach(row => {

                        row.style.display =
                            selected === 'all' ||
                                row.dataset.status === selected
                                ? ''
                                : 'none';

                    });

            });
    </script>
</div>

<script src="/hrms-capstone/modules/portal/public/js/function/"></script>