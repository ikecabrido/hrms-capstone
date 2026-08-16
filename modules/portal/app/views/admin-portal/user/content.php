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
                User Accounts
            </h1>

            <p id="welcomeDescription">
                Manage employee user accounts, access, roles, and account status.
            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration" id="welcomeDecoration">
            <i class="fas fa-user-gear"></i>
        </div>

    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section id="dashboardWelcome" class="w-full min-h-0 bg-slate-50 p-4 sm:p-5">

        <!-- WORKSPACE HEADER -->
        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">

                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg
                    bg-indigo-50 text-indigo-600 ring-1 ring-inset ring-indigo-100">
                        <i class="fa-solid fa-user-plus text-sm"></i>
                    </div>

                    <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">
                        Account Provisioning Queue
                    </h1>

                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full
                    bg-indigo-50 px-2.5 py-1 text-[10px] font-semibold text-indigo-700
                    ring-1 ring-inset ring-indigo-100 sm:text-xs">

                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>

                        <?= count($employeeWithoutAccount) ?> Pending

                    </span>

                </div>

                <p class="mt-1.5 text-xs leading-5 text-slate-500 sm:text-sm sm:pl-11">
                    Provision user accounts for onboarded employees who do not have an account yet.
                </p>
            </div>

            <!-- SEARCH / FILTER -->
            <div class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">

                <div class="relative w-full sm:w-64 lg:w-72">

                    <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3
                    top-1/2 -translate-y-1/2 text-[11px] text-slate-400"></i>

                    <input type="search" id="employeeSearch" autocomplete="off" class="h-9 w-full rounded-lg border border-slate-200 bg-white
                    pl-9 pr-3 text-xs text-slate-700 shadow-sm outline-none
                    transition-all duration-150 placeholder:text-slate-400
                    hover:border-slate-300
                    focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">

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

                <table id="pending-provision-table" class="w-full min-w-[850px] text-left">

                    <thead class="border-b border-slate-300 bg-slate-300">

                        <tr>

                            <th class="w-[280px] whitespace-nowrap px-4 py-3 text-[10px]
                            font-bold uppercase tracking-wider text-slate-500 sm:px-5">
                                Employee
                            </th>

                            <th class="w-[150px] whitespace-nowrap px-4 py-3 text-[10px]
                            font-bold uppercase tracking-wider text-slate-500 sm:px-5">
                                Department
                            </th>

                            <th class="w-[150px] whitespace-nowrap px-4 py-3 text-[10px]
                            font-bold uppercase tracking-wider text-slate-500 sm:px-5">
                                Position
                            </th>

                            <th class="w-[130px] whitespace-nowrap px-4 py-3 text-[10px]
                            font-bold uppercase tracking-wider text-slate-500 sm:px-5">
                                Date Onboarded
                            </th>

                            <th class="w-[150px] whitespace-nowrap px-4 py-3 text-right text-[10px]
                            font-bold uppercase tracking-wider text-slate-500 sm:px-5">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        <?php if (!empty($employeeWithoutAccount)): ?>

                            <?php foreach ($employeeWithoutAccount as $employee): ?>

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
                                ?>

                                <tr class="group transition duration-150 hover:bg-slate-50"
                                    data-name="<?= htmlspecialchars(strtolower($fullName)) ?>"
                                    data-department="<?= htmlspecialchars(strtolower($employee['department'] ?? '')) ?>">

                                    <!-- EMPLOYEE -->
                                    <td class="max-w-[280px] px-4 py-4 sm:px-5">

                                        <div class="flex min-w-0 items-center gap-3">

                                            <!-- PROFILE IMAGE -->
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden
            rounded-full bg-indigo-50 text-xs font-bold text-indigo-600
            ring-1 ring-indigo-100">

                                                <?php if (!empty($employee['profile_image'])): ?>

                                                    <img src="/hrms-capstone/modules/portal/public/assets/uploads/profile/<?= htmlspecialchars($employee['profile_image']) ?>"
                                                        alt="<?= htmlspecialchars($fullName) ?>" class="h-full w-full object-cover">

                                                <?php else: ?>

                                                    <?= htmlspecialchars($initials) ?>

                                                <?php endif; ?>

                                            </div>

                                            <!-- EMPLOYEE INFO -->
                                            <div class="min-w-0">

                                                <div class="truncate text-sm font-semibold text-slate-800">
                                                    <?= htmlspecialchars($fullName) ?>
                                                </div>

                                                <div class="mt-0.5 flex items-center gap-2">

                                                    <span class="whitespace-nowrap font-mono text-[10px] text-slate-400">
                                                        <?= htmlspecialchars($employee['employee_num'] ?? '-') ?>
                                                    </span>

                                                    <span class="text-slate-300">
                                                        •
                                                    </span>

                                                    <span class="whitespace-nowrap text-[10px] text-slate-400">
                                                        No account
                                                    </span>

                                                </div>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- DEPARTMENT -->
                                    <td class="px-4 py-4 sm:px-5">

                                        <span class="block max-w-[130px] truncate text-xs font-medium text-slate-700">
                                            <?= htmlspecialchars($employee['department'] ?? '-') ?>
                                        </span>

                                    </td>


                                    <!-- POSITION -->
                                    <td class="px-4 py-4 sm:px-5">

                                        <span class="block max-w-[130px] truncate text-xs text-slate-500">
                                            <?= htmlspecialchars($employee['position'] ?? '-') ?>
                                        </span>

                                    </td>


                                    <!-- DATE -->
                                    <td class="px-4 py-4 sm:px-5">

                                        <div class="flex items-center gap-2 whitespace-nowrap text-xs text-slate-600">

                                            <i class="fa-regular fa-calendar text-[10px] text-slate-400"></i>

                                            <?= !empty($employee['created_at'])
                                                ? date('M d, Y', strtotime($employee['created_at']))
                                                : '-' ?>

                                        </div>

                                    </td>


                                    <!-- ACTION -->
                                    <td class="px-4 py-4 text-right sm:px-5">
                                        <button type="button" style="border-radius: 10px" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg
           bg-indigo-600 px-3 py-2 text-[11px] font-semibold text-white
           shadow-sm transition-all duration-150
           hover:bg-indigo-700 hover:shadow-md
           focus:outline-none focus:ring-2 focus:ring-indigo-500/30
           active:scale-[0.97]" data-bs-toggle="modal"
                                            data-bs-target="#createAccountModal<?= (int) $employee['id'] ?>">

                                            <i class="fa-solid fa-user-plus text-[10px]"></i>
                                            <span>Create Account</span>

                                        </button>
                                    </td>

                                </tr>
                                <?php require __DIR__ . '/create.php'; ?>
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
                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="5" class="px-6 py-16 text-center">

                                    <div class="mx-auto flex h-12 w-12 items-center justify-center
                                    rounded-full bg-emerald-50 text-emerald-500">
                                        <i class="fa-solid fa-circle-check text-lg"></i>
                                    </div>

                                    <h3 class="mt-3 text-sm font-semibold text-slate-800">
                                        All accounts are provisioned
                                    </h3>

                                    <p class="mt-1 text-xs text-slate-500">
                                        There are no onboarded employees waiting for account creation.
                                    </p>

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <!-- PAGINATION -->
            <div class="flex flex-col gap-3 border-t border-slate-200 bg-white px-4 py-3
            sm:flex-row sm:items-center sm:justify-between sm:px-5">

                <p class="text-[11px] text-slate-500">

                    Showing

                    <span id="pageStart" class="font-semibold text-slate-700">0</span>

                    to

                    <span id="pageEnd" class="font-semibold text-slate-700">0</span>

                    of

                    <span id="totalEntries" class="font-semibold text-slate-700">
                        <?= count($employeeWithoutAccount) ?>
                    </span>

                    entries

                </p>


                <div class="flex w-full items-center justify-between gap-1 sm:w-auto sm:justify-end">

                    <button type="button" id="prevPage" class="inline-flex h-8 flex-1 cursor-not-allowed items-center
                    justify-center gap-1.5 rounded-md border border-slate-200
                    bg-white px-3 text-[11px] font-medium text-slate-300
                    transition sm:flex-none">

                        <i class="fa-solid fa-chevron-left text-[8px]"></i>

                        Previous

                    </button>


                    <span id="pageInfo" class="inline-flex h-8 min-w-8 items-center justify-center
                    rounded-md bg-indigo-50 px-2 text-[11px] font-semibold text-indigo-600">
                        1
                    </span>


                    <button type="button" id="nextPage" class="inline-flex h-8 flex-1 items-center justify-center gap-1.5
                    rounded-md bg-indigo-600 px-3 text-[11px] font-semibold text-white
                    shadow-sm transition hover:bg-indigo-700 sm:flex-none">

                        Next

                        <i class="fa-solid fa-chevron-right text-[8px]"></i>

                    </button>

                </div>

            </div>

        </div>

    </section>
</div>

<script src="/hrms-capstone/modules/portal/public/js/function/contentUserCreation.js"></script>
