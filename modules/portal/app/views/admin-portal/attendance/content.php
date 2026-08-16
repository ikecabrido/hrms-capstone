<div class="employee-dashboard">

    <section class="dashboard-welcome" id="dashboardWelcome">

        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label" id="welcomeLabel">
                <i class="fas fa-circle"></i>
                ATTENDANCE MANAGEMENT
            </span>

            <h1 id="welcomeTitle">
                Employee Attendance
            </h1>

            <p id="welcomeDescription">
                View and monitor employee attendance records, time in and out, work hours, overtime, and attendance
                status.
            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration" id="welcomeDecoration">
            <i class="fas fa-calendar-check"></i>
        </div>

    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section id="dashboardWelcome" class="w-full min-h-0 bg-slate-50 p-3 sm:p-4 lg:p-5">

        <?php
        $attendance = $attendanceList ?? [];

        $today = date('Y-m-d');

        $totalRecords = count($attendance);
        $present = 0;
        $absent = 0;
        $late = 0;

        foreach ($attendance as $row) {
            $status = strtoupper($row['status'] ?? '');

            if ($status === 'PRESENT') {
                $present++;
            } elseif ($status === 'ABSENT') {
                $absent++;
            } elseif ($status === 'LATE') {
                $late++;
            }
        }
        ?>

        <!-- Header -->
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-xl sm:text-2xl font-semibold text-slate-800">
                    Employee Attendance
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    View and monitor employee attendance records.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-2">

                <input type="date" id="attendanceDate" value="<?= htmlspecialchars($today) ?>"
                    class="h-10 px-3 border border-slate-200 rounded-lg bg-white text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">

                <button type="button"
                    class="h-10 px-4 inline-flex items-center justify-center gap-2 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">
                    <i class="fas fa-file-export"></i>
                    Export CSV
                </button>

            </div>

        </div>

        <?php require __DIR__ . '/calendar.php'; ?>
        <!-- Table -->
        <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">

            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between gap-3">

                <div>
                    <h2 class="text-sm font-semibold text-slate-800">
                        Attendance Records Table
                    </h2>

                    <p class="text-xs text-slate-400 mt-0.5 sm:hidden">
                        <i class="fas fa-arrows-left-right mr-1"></i>
                        Swipe horizontally to view more
                    </p>
                </div>

            </div>

            <div class="relative">

                <!-- Mobile scroll fade -->
                <div
                    class="pointer-events-none absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-white to-transparent z-10 sm:hidden">
                </div>

                <div class="w-full overflow-x-auto overflow-y-hidden scrollbar-thin">

                    <table class="w-full min-w-[1100px] text-sm" id="attendanceTable">

                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-left text-xs font-semibold text-slate-500 uppercase">

                                <th class="px-4 py-3 whitespace-nowrap">
                                    Employee
                                </th>

                                <th class="px-4 py-3 whitespace-nowrap">
                                    Date
                                </th>

                                <th class="px-4 py-3 whitespace-nowrap">
                                    Shift
                                </th>

                                <th class="px-4 py-3 whitespace-nowrap">
                                    Clock In
                                </th>

                                <th class="px-4 py-3 whitespace-nowrap">
                                    Clock Out
                                </th>

                                <th class="px-4 py-3 whitespace-nowrap">
                                    Total Hours
                                </th>

                                <th class="px-4 py-3 whitespace-nowrap">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-center">
                                    Action
                                </th>

                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            <?php if (!empty($attendance)): ?>

                                <?php foreach ($attendance as $row): ?>

                                    <?php
                                    $status = strtoupper($row['status'] ?? 'UNKNOWN');

                                    $statusClass = match ($status) {
                                        'PRESENT' => 'bg-green-50 text-green-700 border-green-200',
                                        'ABSENT' => 'bg-red-50 text-red-700 border-red-200',
                                        'LATE' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'ON_LEAVE' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'APPROVED' => 'bg-green-50 text-green-700 border-green-200',
                                        'PENDING_APPROVAL' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        default => 'bg-slate-50 text-slate-600 border-slate-200'
                                    };

                                    $timeIn = !empty($row['time_in'])
                                        ? date('h:i A', strtotime($row['time_in']))
                                        : '--';

                                    $timeOut = !empty($row['time_out'])
                                        ? date('h:i A', strtotime($row['time_out']))
                                        : '--';

                                    $date = !empty($row['attendance_date'])
                                        ? date('M d, Y', strtotime($row['attendance_date']))
                                        : '--';

                                    $hours = number_format(
                                        (float) ($row['total_hours_worked'] ?? 0),
                                        2
                                    );
                                    ?>

                                    <tr class="attendance-row hover:bg-slate-50 transition"
                                        data-name="<?= htmlspecialchars(strtolower($row['employee_name'] ?? '')) ?>"
                                        data-id="<?= htmlspecialchars(strtolower($row['employee_num'] ?? '')) ?>"
                                        data-status="<?= htmlspecialchars($status) ?>"
                                        data-date="<?= htmlspecialchars($row['attendance_date'] ?? '') ?>">

                                        <!-- Employee -->
                                        <td class="px-4 py-3">

                                            <div class="flex items-center gap-3 min-w-[190px]">

                                                <div
                                                    class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-semibold text-sm shrink-0">
                                                    <?= strtoupper(substr($row['employee_name'] ?? 'U', 0, 1)) ?>
                                                </div>

                                                <div>
                                                    <div class="font-medium text-slate-800 whitespace-nowrap">
                                                        <?= htmlspecialchars($row['employee_name'] ?? 'Unknown') ?>
                                                    </div>

                                                    <div class="text-xs text-slate-400 mt-0.5">
                                                        <?= htmlspecialchars($row['employee_num'] ?? '--') ?>
                                                    </div>
                                                </div>

                                            </div>

                                        </td>

                                        <!-- Date -->
                                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                                            <?= htmlspecialchars($date) ?>
                                        </td>

                                        <!-- Shift -->
                                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                                            <?= !empty($row['shift_id'])
                                                ? 'Shift #' . htmlspecialchars($row['shift_id'])
                                                : 'No Shift' ?>
                                        </td>

                                        <!-- Clock In -->
                                        <td class="px-4 py-3 text-slate-700 font-medium whitespace-nowrap">
                                            <?= htmlspecialchars($timeIn) ?>
                                        </td>

                                        <!-- Clock Out -->
                                        <td class="px-4 py-3 text-slate-700 font-medium whitespace-nowrap">
                                            <?= htmlspecialchars($timeOut) ?>
                                        </td>

                                        <!-- Total Hours -->
                                        <td class="px-4 py-3 whitespace-nowrap">

                                            <span class="font-medium text-slate-700">
                                                <?= $hours ?> hrs
                                            </span>

                                            <?php if ((float) ($row['overtime_hours'] ?? 0) > 0): ?>

                                                <div class="text-xs text-amber-600 mt-0.5">
                                                    +<?= number_format((float) $row['overtime_hours'], 2) ?> OT
                                                </div>

                                            <?php endif; ?>

                                        </td>

                                        <!-- Status -->
                                        <td class="px-4 py-3 whitespace-nowrap">

                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-medium <?= $statusClass ?>">
                                                <?= htmlspecialchars(str_replace('_', ' ', $status)) ?>
                                            </span>

                                        </td>

                                        <!-- Action -->
                                        <td class="px-4 py-3 text-center">

                                            <button type="button"
                                                class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition"
                                                title="View attendance"
                                                onclick="viewAttendance(<?= (int) $row['attendance_id'] ?>)">

                                                <i class="fas fa-ellipsis"></i>

                                            </button>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center">

                                        <div class="flex flex-col items-center">

                                            <div
                                                class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                                                <i class="fas fa-calendar-xmark"></i>
                                            </div>

                                            <p class="text-sm font-medium text-slate-700">
                                                No attendance records found
                                            </p>

                                            <p class="text-xs text-slate-400 mt-1">
                                                Attendance records will appear here once available.
                                            </p>

                                        </div>

                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-t border-slate-200 bg-white">

                        <div class="text-xs text-slate-500">
                            Showing
                            <span id="paginationStart" class="font-medium text-slate-700">0</span>
                            -
                            <span id="paginationEnd" class="font-medium text-slate-700">0</span>
                            of
                            <span id="paginationTotal" class="font-medium text-slate-700">0</span>
                            records
                        </div>

                        <div class="flex items-center gap-1">

                            <button type="button" id="prevPage"
                                class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
                                title="Previous page">

                                <i class="fas fa-chevron-left text-xs"></i>

                            </button>

                            <div id="paginationNumbers" class="flex items-center gap-1"></div>

                            <button type="button" id="nextPage"
                                class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
                                title="Next page">

                                <i class="fas fa-chevron-right text-xs"></i>

                            </button>

                        </div>

                    </div>
                </div>

            </div>

        </div>

    </section>
</div>

<script src="/hrms-capstone/modules/portal/public/js/function/contentEmployeeAttendance.js"></script>
