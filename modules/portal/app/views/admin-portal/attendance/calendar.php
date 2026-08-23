<div class="mb-5">

    <?php
    $currentMonth = date('Y-m');
    $currentYear = (int)date('Y');
    $currentMonthNum = (int)date('m');
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonthNum, $currentYear);
    $firstDay = (int)date('N', strtotime($currentMonth . '-01'));

    $dailyAttendance = [];

    foreach ($attendance as $row) {
        $date = $row['attendance_date'] ?? '';
        $employeeId = $row['employee_id'] ?? 0;

        if (!$date || !$employeeId) {
            continue;
        }

        if (!isset($dailyAttendance[$date])) {
            $dailyAttendance[$date] = [];
        }

        $dailyAttendance[$date][$employeeId] = [
            'name' => $row['employee_name'] ?? 'Unknown',
            'id' => $row['employee_num'] ?? '--'
        ];
    }

    $maxPresent = 0;

    foreach ($dailyAttendance as $employees) {
        $count = count($employees);

        if ($count > $maxPresent) {
            $maxPresent = $count;
        }
    }

    $monthName = date('F Y');
    ?>

    <!-- Calendar Header -->
    <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">

        <div class="px-4 py-3 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">

            <div>
                <h2 class="text-sm font-semibold text-slate-800">
                    Attendance Calendar Overview
                </h2>

                <p class="text-xs text-slate-400 mt-0.5">
                    <?= htmlspecialchars($monthName) ?> · Employees present per day
                </p>
            </div>

            <button
                type="button"
                id="showAllAttendance"
                class="hidden h-8 px-3 rounded-lg border border-slate-200 bg-white text-xs font-medium text-slate-600 hover:bg-slate-50 transition">
                <i class="fas fa-calendar-days mr-1"></i>
                Show All
            </button>

        </div>

        <div class="p-3 sm:p-4">

            <!-- Legend -->
            <div class="flex items-center justify-between mb-3">

                <span class="text-[11px] text-slate-400">
                    Less attendance
                </span>

                <div class="flex items-center gap-1.5">

                    <span class="text-[10px] text-slate-400 mr-1">
                        0
                    </span>

                    <span class="w-3 h-3 rounded-sm bg-slate-100 border border-slate-200"></span>
                    <span class="w-3 h-3 rounded-sm bg-blue-100"></span>
                    <span class="w-3 h-3 rounded-sm bg-blue-200"></span>
                    <span class="w-3 h-3 rounded-sm bg-blue-400"></span>
                    <span class="w-3 h-3 rounded-sm bg-blue-600"></span>

                    <span class="text-[10px] text-slate-400 ml-1">
                        <?= $maxPresent ?>
                    </span>

                </div>

            </div>

            <!-- Weekdays -->
            <div class="grid grid-cols-7 gap-1.5 sm:gap-2 mb-1.5">

                <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day): ?>

                    <div class="text-center text-[10px] sm:text-xs font-medium text-slate-400 py-1">
                        <?= $day ?>
                    </div>

                <?php endforeach; ?>

            </div>

            <!-- Calendar -->
            <div
                id="attendanceCalendar"
                class="grid grid-cols-7 gap-1.5 sm:gap-2">

                <?php for ($i = 1; $i < $firstDay; $i++): ?>

                    <div class="min-h-[58px] sm:min-h-[68px]"></div>

                <?php endfor; ?>


                <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>

                    <?php
                    $date = sprintf(
                        '%04d-%02d-%02d',
                        $currentYear,
                        $currentMonthNum,
                        $day
                    );

                    $employees = $dailyAttendance[$date] ?? [];
                    $presentCount = count($employees);

                    $isToday = $date === date('Y-m-d');

                    if ($presentCount === 0) {
                        $heatClass = 'bg-slate-50 border-slate-200';
                    } elseif ($maxPresent <= 1) {
                        $heatClass = 'bg-blue-200 border-blue-300';
                    } elseif ($presentCount <= ceil($maxPresent * .25)) {
                        $heatClass = 'bg-blue-100 border-blue-200';
                    } elseif ($presentCount <= ceil($maxPresent * .50)) {
                        $heatClass = 'bg-blue-200 border-blue-300';
                    } elseif ($presentCount <= ceil($maxPresent * .75)) {
                        $heatClass = 'bg-blue-400 border-blue-500';
                    } else {
                        $heatClass = 'bg-blue-600 border-blue-700';
                    }

                    $textClass = $presentCount >= ceil($maxPresent * .75)
                        ? 'text-white'
                        : 'text-slate-700';

                    $employeeNames = array_map(
                        fn($employee) => $employee['name'],
                        $employees
                    );
                    ?>

                    <button
                        type="button"
                        class="attendance-day relative min-h-[58px] sm:min-h-[68px] rounded-lg border <?= $heatClass ?> <?= $isToday ? 'ring-2 ring-blue-500 ring-offset-1' : '' ?> hover:scale-[1.03] hover:shadow-sm transition-all duration-150 text-left p-2"
                        data-date="<?= $date ?>"
                        data-count="<?= $presentCount ?>"
                        title="<?= htmlspecialchars(
                            date('M d, Y', strtotime($date)) .
                            ' · ' .
                            $presentCount .
                            ' present'
                        ) ?>">

                        <div class="flex items-start justify-between gap-1">

                            <span class="text-xs font-semibold <?= $textClass ?>">
                                <?= $day ?>
                            </span>

                            <?php if ($isToday): ?>

                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>

                            <?php endif; ?>

                        </div>

                        <div class="mt-2">

                            <span class="text-[10px] sm:text-xs font-medium <?= $textClass ?>">
                                <?= $presentCount ?>
                                <?= $presentCount === 1 ? 'present' : 'present' ?>
                            </span>

                        </div>

                        <?php if ($presentCount > 0): ?>

                            <div class="hidden sm:block mt-1 text-[9px] truncate <?= $textClass ?> opacity-80">
                                <?= htmlspecialchars($employeeNames[0]) ?>

                                <?php if ($presentCount > 1): ?>
                                    +<?= $presentCount - 1 ?>
                                <?php endif; ?>
                            </div>

                        <?php endif; ?>

                    </button>

                <?php endfor; ?>

            </div>

        </div>

    </div>

    <!-- Selected Day Summary -->
    <div
        id="selectedDaySummary"
        class="hidden mt-3 bg-white border border-slate-200 rounded-lg p-3">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">

            <div>

                <p class="text-xs text-slate-400">
                    Selected Date
                </p>

                <p
                    id="selectedDayTitle"
                    class="text-sm font-semibold text-slate-800">
                </p>

            </div>

            <div
                id="selectedDayCount"
                class="text-xs font-medium text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full">
            </div>

        </div>

        <div
            id="selectedEmployeeNames"
            class="mt-3 flex gap-1.5 overflow-x-auto pb-1">
        </div>

    </div>

</div>

<script src="/hrms-capstone/modules/portal/public/js/function/calendarEmployeeAttendance.js"></script>
