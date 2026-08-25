<div class="employee-dashboard">

    <section class="dashboard-welcome" id="dashboardWelcome">

        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label" id="welcomeLabel">
                <i class="fas fa-circle"></i>
                USER MANAGEMENT
            </span>

            <h1 id="welcomeTitle">
                Employee User Accounts
            </h1>

            <p id="welcomeDescription">
                View and manage employee accounts, roles, access permissions, and account status.
            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration" id="welcomeDecoration">
            <i class="fas fa-users-gear"></i>
        </div>

    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section id="dashboardWelcome" class="w-full min-h-0 bg-slate-50 p-3 sm:p-4 lg:p-5">

        <!-- WORKSPACE HEADER -->
        <div
            class="mb-6 flex flex-col gap-4 border-b border-slate-200 pb-5 sm:mb-7 lg:flex-row lg:items-center lg:justify-between">

            <!-- TITLE / DESCRIPTION -->
            <div class="min-w-0">

                <div class="flex flex-wrap items-center gap-2.5">

                    <!-- Icon -->
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg
                bg-indigo-50 text-indigo-600 ring-1 ring-inset ring-indigo-100">

                        <i class="fa-solid fa-users text-sm"></i>

                    </div>

                    <!-- Title -->
                    <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">
                        All Employees
                    </h1>

                    <!-- Count -->
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full
                bg-indigo-50 px-2.5 py-1 text-[10px] font-semibold text-indigo-700
                ring-1 ring-inset ring-indigo-100 sm:text-xs">

                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>

                        <?= count($employeesList) ?> Employees

                    </span>

                </div>

                <div class="mt-1.5 flex items-center gap-2 pl-0 sm:pl-11">

                    <span class="text-xs leading-5 text-slate-500 sm:text-sm">
                        View and manage all registered employees.
                    </span>

                </div>

            </div>

        </div>


        <!-- TABLE CARD -->
        <div class="w-full min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <!-- RESPONSIVE TABLE WRAPPER -->
            <div class="w-full overflow-x-auto overflow-y-hidden
            [scrollbar-width:thin]
            [&::-webkit-scrollbar]:h-2
            [&::-webkit-scrollbar-track]:bg-slate-100
            [&::-webkit-scrollbar-thumb]:rounded-full
            [&::-webkit-scrollbar-thumb]:bg-slate-300
            [&::-webkit-scrollbar-thumb:hover]:bg-slate-400">

                <table id="employees-table" class="w-full min-w-[850px] text-left">

                    <!-- HEADER -->
                    <thead class="border-b border-slate-300 bg-slate-300">

                        <tr>

                            <th
                                class="w-[280px] whitespace-nowrap px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 sm:px-5">
                                Employee
                            </th>

                            <th
                                class="w-[150px] whitespace-nowrap px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 sm:px-5">
                                Department
                            </th>

                            <th
                                class="w-[150px] whitespace-nowrap px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 sm:px-5">
                                Position
                            </th>

                            <th
                                class="w-[110px] whitespace-nowrap px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 sm:px-5">
                                Status
                            </th>

                            <th
                                class="w-[130px] whitespace-nowrap px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 sm:px-5">
                                Employee No.
                            </th>

                            <th
                                class="w-[110px] whitespace-nowrap px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500 sm:px-5">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <!-- BODY -->
                    <tbody class="divide-y divide-slate-100">

                        <?php if (!empty($employeesList)): ?>

                            <?php foreach ($employeesList as $employee): ?>

                                <?php
                                $fullName = trim(
                                    ($employee['first_name'] ?? '') . ' ' .
                                    ($employee['middle_name'] ?? '') . ' ' .
                                    ($employee['last_name'] ?? '') . ' ' .
                                    ($employee['suffix'] ?? '')
                                );

                                $initials =
                                    strtoupper(substr($employee['first_name'] ?? 'E', 0, 1)) .
                                    strtoupper(substr($employee['last_name'] ?? 'M', 0, 1));

                                $isCurrentUser =
                                    (int) ($employee['user_id'] ?? 0) ===
                                    (int) ($_SESSION['user_id'] ?? 0);

                                $status = strtolower($employee['employment_status'] ?? '');
                                $active = $status === 'active';
                                ?>

                                <tr class="group transition duration-150 hover:bg-slate-50"
                                    data-name="<?= htmlspecialchars(strtolower($fullName)) ?>"
                                    data-department="<?= htmlspecialchars(strtolower($employee['department'] ?? '')) ?>">

                                    <!-- EMPLOYEE -->
                                    <td class="max-w-[280px] px-4 py-4 sm:px-5">

                                        <div class="flex min-w-0 items-center gap-3">

                                            <!-- PROFILE IMAGE -->
                                            <div
                                                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-50 text-xs font-bold text-indigo-600 ring-1 ring-indigo-100">

                                                <?php if (!empty($employee['profile_image'])): ?>

                                                    <img src="/hrms-capstone/modules/portal/public/assets/uploads/profile/<?= htmlspecialchars($employee['profile_image']) ?>"
                                                        alt="<?= htmlspecialchars($fullName) ?>" class="h-full w-full object-cover">

                                                <?php else: ?>

                                                    <?= htmlspecialchars($initials) ?>

                                                <?php endif; ?>

                                            </div>

                                            <!-- EMPLOYEE INFO -->
                                            <div class="min-w-0">

                                                <div class="flex min-w-0 items-center gap-2">

                                                    <span class="max-w-[190px] truncate text-sm font-semibold text-slate-800">
                                                        <?= htmlspecialchars($fullName) ?>
                                                    </span>

                                                    <?php if ($isCurrentUser): ?>

                                                        <span
                                                            class="shrink-0 rounded-md bg-indigo-50 px-1.5 py-0.5 text-[9px] font-bold text-indigo-600">
                                                            You
                                                        </span>

                                                    <?php endif; ?>

                                                </div>

                                                <div class="mt-0.5 flex items-center gap-2">

                                                    <span class="whitespace-nowrap text-[10px] text-slate-400">
                                                        <?= htmlspecialchars($employee['employment_type'] ?? '-') ?>
                                                    </span>

                                                    <span class="text-slate-300">
                                                        •
                                                    </span>

                                                    <span class="whitespace-nowrap text-[10px] text-slate-400">
                                                        Registered Employee
                                                    </span>

                                                </div>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- DEPARTMENT -->
                                    <td class="px-4 py-4 sm:px-5">

                                        <span class="block max-w-[130px] truncate text-xs font-medium text-slate-700">
                                            <?= htmlspecialchars($employee['department_name'] ?? '-') ?>
                                        </span>

                                    </td>


                                    <!-- POSITION -->
                                    <td class="px-4 py-4 sm:px-5">

                                        <span class="block max-w-[130px] truncate text-xs text-slate-500">
                                            <?= htmlspecialchars($employee['position_name'] ?? '-') ?>
                                        </span>

                                    </td>


                                    <!-- STATUS -->
                                    <td class="px-4 py-4 sm:px-5">

                                        <span class="inline-flex whitespace-nowrap rounded-md px-2 py-1 text-[10px] font-bold
                                    <?= $active
                                        ? 'bg-emerald-50 text-emerald-600'
                                        : 'bg-amber-50 text-amber-600' ?>">

                                            <?= htmlspecialchars($employee['employment_status'] ?? '-') ?>

                                        </span>

                                    </td>


                                    <!-- EMPLOYEE NUMBER -->
                                    <td class="px-4 py-4 sm:px-5">

                                        <span class="whitespace-nowrap font-mono text-[10px] font-medium text-slate-500">
                                            <?= htmlspecialchars($employee['employee_code'] ?? '-') ?>
                                        </span>

                                    </td>

                                    <!-- ACTION -->
                                    <td class="px-4 py-4 text-right sm:px-5">
                                        <button type="button"
                                            class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2 rounded-3"
                                            data-bs-toggle="modal" data-bs-target="#employeeViewModal"
                                            data-id="<?= (int) $employee['employee_id'] ?>"
                                            data-profile-image="<?= htmlspecialchars($employee['profile_image'] ?? '') ?>"
                                            data-employee-num="<?= htmlspecialchars($employee['employee_code'] ?? '') ?>"
                                            data-first-name="<?= htmlspecialchars($employee['first_name'] ?? '') ?>"
                                            data-middle-name="<?= htmlspecialchars($employee['middle_name'] ?? '') ?>"
                                            data-last-name="<?= htmlspecialchars($employee['last_name'] ?? '') ?>"
                                            data-suffix="<?= htmlspecialchars($employee['suffix'] ?? '') ?>"
                                            data-department="<?= htmlspecialchars($employee['department_name'] ?? '') ?>"
                                            data-position="<?= htmlspecialchars($employee['position_name'] ?? '') ?>"
                                            data-employment-type="<?= htmlspecialchars($employee['employment_type'] ?? '') ?>"
                                            data-employment-status="<?= htmlspecialchars($employee['employment_status'] ?? '') ?>"
                                            data-phone="<?= htmlspecialchars($employee['mobile_no'] ?? '') ?>"
                                            data-gender="<?= htmlspecialchars($employee['gender'] ?? '') ?>"
                                            data-birth-date="<?= htmlspecialchars($employee['birth_date'] ?? '') ?>"
                                            data-address="<?= htmlspecialchars($employee['current_address'] ?? '') ?>"
                                            data-date-hired="<?= htmlspecialchars($employee['hire_date'] ?? '') ?>">

                                            <i class="fa-solid fa-eye"></i>
                                            View
                                        </button>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="6" class="px-5 py-16 text-center">

                                    <div
                                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 text-slate-400">

                                        <i class="fa-solid fa-users text-lg"></i>

                                    </div>

                                    <h3 class="mt-3 text-sm font-semibold text-slate-800">
                                        No employees found
                                    </h3>

                                    <p class="mt-1 text-xs text-slate-500">
                                        There are currently no registered employees.
                                    </p>

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <!-- PAGINATION -->
            <div
                class="flex flex-col gap-3 border-t border-slate-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">

                <p class="text-[11px] text-slate-500">
                    Showing
                    <span id="pageStart" class="font-semibold text-slate-700">0</span>
                    to
                    <span id="pageEnd" class="font-semibold text-slate-700">0</span>
                    of
                    <span id="totalEntries" class="font-semibold text-slate-700">
                        <?= count($employeesList) ?>
                    </span>
                    entries
                </p>

                <div class="flex w-full items-center justify-between gap-1 sm:w-auto sm:justify-end">

                    <button type="button" id="prevPage"
                        class="inline-flex h-8 flex-1 cursor-not-allowed items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 text-[11px] font-medium text-slate-300 transition sm:flex-none">
                        <i class="fa-solid fa-chevron-left text-[8px]"></i>
                        Previous
                    </button>

                    <span id="pageInfo"
                        class="inline-flex h-8 min-w-8 items-center justify-center rounded-md bg-indigo-50 px-2 text-[11px] font-semibold text-indigo-600">
                        1
                    </span>

                    <button type="button" id="nextPage"
                        class="inline-flex h-8 flex-1 items-center justify-center gap-1.5 rounded-md bg-indigo-600 px-3 text-[11px] font-semibold text-white shadow-sm transition hover:bg-indigo-700 sm:flex-none">
                        Next
                        <i class="fa-solid fa-chevron-right text-[8px]"></i>
                    </button>

                </div>

            </div>

        </div>

    </section>
</div>

<script src="/hrms-capstone/modules/portal/public/js/function/viewAllEmployees.js"></script>
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