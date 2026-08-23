<div class="employee-dashboard">

    <section class="dashboard-welcome" id="dashboardWelcome">

        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label" id="welcomeLabel">
                <i class="fas fa-circle"></i>
                PAYROLL MANAGEMENT
            </span>

            <h1 id="welcomeTitle">
                Employee Payroll
            </h1>

            <p id="welcomeDescription">
                View and manage employee payroll records, salary details, deductions,
                allowances, payslips, and payroll status.
            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration" id="welcomeDecoration">
            <i class="fas fa-money-check-alt"></i>
        </div>

    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section id="dashboardWelcome" class="w-full min-h-0 bg-slate-50 p-3 sm:p-4 lg:p-5">

        <!-- HEADER -->
        <div class="mb-5">
            <h1 class="text-lg sm:text-xl font-bold text-slate-800">
                Payroll Requests
            </h1>

            <p class="mt-1 text-xs text-slate-500">
                Review, process, approve, and manage employee payroll document requests.
            </p>
        </div>

        <?php
        $pendingCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;

        foreach ($payrollList as $request) {
            $status = strtolower(trim($request['status'] ?? 'pending'));

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
                        Payroll Document Requests
                    </h2>

                    <p class="text-[10px] text-slate-400 mt-0.5">
                        Review employee requests and process the requested documents.
                    </p>
                </div>

            </div>

            <!-- RESPONSIVE TABLE -->
            <div class="w-full overflow-x-auto overflow-y-hidden" style="
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
                scrollbar-color: #cbd5e1 #f1f5f9;
            ">

                <table class="w-full min-w-[1050px] text-left">

                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 whitespace-nowrap">
                                ID
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 whitespace-nowrap">
                                Employee
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 whitespace-nowrap">
                                Request Type
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 whitespace-nowrap">
                                Purpose
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 whitespace-nowrap">
                                Payroll Period
                            </th>

                            <th
                                class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 text-center whitespace-nowrap">
                                Status
                            </th>

                            <th
                                class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 text-right whitespace-nowrap">
                                Action
                            </th>

                        </tr>
                    </thead>

                    <tbody id="payrollRequestTable">

                        <?php if (!empty($payrollList)): ?>

                            <?php foreach ($payrollList as $request): ?>

                                <?php
                                $requestId = (int) ($request['id'] ?? 0);

                                $employeeName =
                                    $request['employee_name']
                                    ?? $request['name']
                                    ?? 'Unknown Employee';

                                $status = strtolower(
                                    trim($request['status'] ?? 'pending')
                                );

                                $statusStyle = match ($status) {
                                    'approved' =>
                                    'bg-emerald-50 text-emerald-700 border-emerald-200',

                                    'rejected' =>
                                    'bg-red-50 text-red-700 border-red-200',

                                    default =>
                                    'bg-amber-50 text-amber-700 border-amber-200'
                                };

                                $startDate = !empty($request['payroll_period_start'])
                                    ? date('M d, Y', strtotime($request['payroll_period_start']))
                                    : '—';

                                $endDate = !empty($request['payroll_period_end'])
                                    ? date('M d, Y', strtotime($request['payroll_period_end']))
                                    : '—';

                                $avatarLetter = strtoupper(
                                    substr($employeeName, 0, 1)
                                );
                                ?>

                                <tr data-status="<?= htmlspecialchars($status) ?>"
                                    class="border-b border-slate-100 hover:bg-slate-50 transition">

                                    <!-- ID -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] font-bold">
                                            <?= $requestId ?>
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
                                                    <?= htmlspecialchars($request['employee_id'] ?? '—') ?>
                                                </div>
                                            </div>

                                        </div>

                                    </td>

                                    <!-- REQUEST TYPE -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-xs font-semibold text-slate-700">
                                            <?= htmlspecialchars($request['request_type'] ?? '—') ?>
                                        </span>
                                    </td>

                                    <!-- PURPOSE -->
                                    <td class="px-4 py-3">

                                        <div class="max-w-[220px]">
                                            <div class="text-xs font-semibold text-slate-700 truncate">
                                                <?= htmlspecialchars($request['purpose'] ?? '—') ?>
                                            </div>

                                            <?php if (!empty($request['remarks'])): ?>
                                                <div class="text-[10px] text-slate-400 mt-1 truncate">
                                                    <?= htmlspecialchars($request['remarks']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                    </td>

                                    <!-- PAYROLL PERIOD -->
                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <div class="text-xs font-semibold text-slate-700">
                                            <?= htmlspecialchars($startDate) ?>
                                        </div>

                                        <div class="text-[10px] text-slate-400 mt-1">
                                            to <?= htmlspecialchars($endDate) ?>
                                        </div>

                                    </td>

                                    <!-- STATUS -->
                                    <td class="px-4 py-3 text-center whitespace-nowrap">

                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full border text-[9px] font-bold uppercase <?= $statusStyle ?>">

                                            <?php if ($status === 'approved'): ?>
                                                <i class="fas fa-check-circle"></i>
                                            <?php elseif ($status === 'rejected'): ?>
                                                <i class="fas fa-times-circle"></i>
                                            <?php else: ?>
                                                <i class="fas fa-clock"></i>
                                            <?php endif; ?>

                                            <?= htmlspecialchars($request['status'] ?? 'Pending') ?>

                                        </span>

                                    </td>

                                    <!-- ACTION -->
                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <div class="flex justify-end gap-2">

                                            <?php if ($status === 'pending'): ?>

                                                <!-- UPLOAD / APPROVE -->
                                                <button type="button" onclick="openPayrollUploadModal(
                                                    <?= $requestId ?>,
                                                    '<?= htmlspecialchars($employeeName, ENT_QUOTES) ?>',
                                                    '<?= htmlspecialchars($request['request_type'] ?? 'Payroll Document', ENT_QUOTES) ?>'
                                                )"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold transition">

                                                    <i class="fas fa-upload"></i>
                                                    Upload

                                                </button>

                                                <!-- REJECT -->
                                                <button type="button" onclick="openPayrollRejectModal(
                                                    <?= $requestId ?>,
                                                    '<?= htmlspecialchars($employeeName, ENT_QUOTES) ?>'
                                                )"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold transition">

                                                    <i class="fas fa-times"></i>
                                                    Reject

                                                </button>

                                            <?php elseif ($status === 'approved'): ?>

                                                <?php if (!empty($request['document_path'])): ?>

                                                    <?php
                                                    $documentUrl = '/hrms-capstone/modules/portal/public/' . $request['document_path'];
                                                    ?>

                                                    <a href="<?= htmlspecialchars($documentUrl) ?>" target="_blank"
                                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 text-[10px] font-bold transition">
                                                        <i class="fas fa-file-alt"></i>
                                                        View
                                                    </a>

                                                <?php endif; ?>

                                            <?php else: ?>

                                                <span class="text-[10px] text-slate-400">
                                                    No action
                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center">

                                    <div
                                        class="w-12 h-12 mx-auto rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </div>

                                    <p class="mt-3 text-xs font-semibold text-slate-600">
                                        No Payroll Requests
                                    </p>

                                    <p class="mt-1 text-[10px] text-slate-400">
                                        Employee payroll document requests will appear here.
                                    </p>

                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>
                <!-- PAGINATION -->
                <div
                    class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                    <p id="payrollPaginationInfo" class="text-[10px] text-slate-400">
                    </p>

                    <div id="payrollPagination" class="flex items-center gap-1">
                    </div>

                </div>
            </div>

        </div>

    </section>
</div>
<?php require __DIR__ . '/upload-approve.php'; ?>
<?php require __DIR__ . '/reject.php'; ?>
<script src="/hrms-capstone/modules/portal/public/js/function/contentPayrollAdmin.js"></script>