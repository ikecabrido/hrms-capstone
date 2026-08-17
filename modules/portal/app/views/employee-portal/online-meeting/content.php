<div class="employee-dashboard">

    <section class="dashboard-welcome" id="dashboardWelcome">

        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label" id="welcomeLabel">
                <i class="fas fa-circle"></i>
                ONLINE MEETING
            </span>

            <h1 id="welcomeTitle">
                Online Meetings
            </h1>

            <p id="welcomeDescription">
                Schedule, manage, and monitor online meetings, meeting schedules,and virtual sessions.
            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration" id="welcomeDecoration">
            <i class="fas fa-video"></i>
        </div>

    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>
    <section id="dashboardWelcome" class="w-full min-h-0 bg-slate-50 p-3 sm:p-4 lg:p-5">

        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">

            <!-- Header -->
            <div class="px-4 py-4 border-b border-slate-200">

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                    <div>
                        <h2 class="text-base font-semibold text-slate-800">
                            Online Meetings
                        </h2>

                        <p class="text-xs text-slate-400 mt-1">
                            View and join your scheduled online meetings.
                        </p>
                    </div>

                    <!-- Search -->
                    <div class="relative w-full sm:w-64">

                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>

                        <input type="text" id="meetingSearch" placeholder="Search meetings..." class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm
                               text-slate-700 placeholder-slate-400
                               focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">

                    </div>

                </div>

            </div>

            <!-- Mobile hint -->
            <div class="px-4 py-2 bg-slate-50 border-b border-slate-100 sm:hidden">

                <p class="text-xs text-slate-400">
                    <i class="fas fa-arrows-left-right mr-1"></i>
                    Swipe horizontally to view more
                </p>

            </div>

            <!-- Table -->
            <div class="relative">

                <div class="pointer-events-none absolute right-0 top-0 bottom-0 w-8
                        bg-gradient-to-l from-white to-transparent z-10 sm:hidden">
                </div>

                <div class="overflow-x-auto">

                    <table id="meetingsTable" class="w-full min-w-[760px] text-sm">

                        <thead class="bg-slate-50 border-b border-slate-200">

                            <tr class="text-left text-xs font-semibold text-slate-500 uppercase">

                                <th class="px-4 py-3 whitespace-nowrap">
                                    Meeting
                                </th>

                                <th class="px-4 py-3 whitespace-nowrap">
                                    Date & Time
                                </th>

                                <th class="px-4 py-3 whitespace-nowrap">
                                    Platform
                                </th>

                                <th class="px-4 py-3 whitespace-nowrap">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-center whitespace-nowrap">
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            <?php if (!empty($meetingsList)): ?>

                                <?php foreach ($meetingsList as $meeting): ?>

                                    <?php

                                    $status = strtolower(
                                        $meeting['status'] ?? 'scheduled'
                                    );

                                    $statusClass = match ($status) {

                                        'scheduled' =>
                                        'bg-blue-50 text-blue-700 border-blue-200',

                                        'ongoing' =>
                                        'bg-green-50 text-green-700 border-green-200',

                                        'completed' =>
                                        'bg-slate-50 text-slate-600 border-slate-200',

                                        'cancelled' =>
                                        'bg-red-50 text-red-700 border-red-200',

                                        default =>
                                        'bg-slate-50 text-slate-600 border-slate-200'
                                    };

                                    $statusLabel = match ($status) {

                                        'scheduled' => 'Upcoming',
                                        'ongoing' => 'Live',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',

                                        default => ucfirst($status)
                                    };

                                    $scheduledDate = !empty($meeting['scheduled_at'])
                                        ? date(
                                            'M d, Y',
                                            strtotime($meeting['scheduled_at'])
                                        )
                                        : '--';

                                    $scheduledTime = !empty($meeting['scheduled_at'])
                                        ? date(
                                            'h:i A',
                                            strtotime($meeting['scheduled_at'])
                                        )
                                        : '--';

                                    $meetingLink =
                                        $meeting['meeting_link'] ?? '';

                                    ?>

                                    <tr class="meeting-row hover:bg-slate-50 transition" data-search="<?= htmlspecialchars(
                                        strtolower(
                                            ($meeting['title'] ?? '') . ' ' .
                                            ($meeting['status'] ?? '')
                                        )
                                    ) ?>">

                                        <!-- Meeting -->
                                        <td class="px-4 py-4">

                                            <div class="flex items-center gap-3 min-w-[230px]">

                                                <div class="w-10 h-10 rounded-lg
                                                       bg-blue-50 text-blue-600
                                                       flex items-center justify-center
                                                       shrink-0">

                                                    <i class="fas fa-video"></i>

                                                </div>

                                                <div class="min-w-0">

                                                    <div class="font-medium text-slate-800 truncate">

                                                        <?= htmlspecialchars(
                                                            $meeting['title'] ?? '--'
                                                        ) ?>

                                                    </div>

                                                    <div class="text-xs text-slate-400 mt-0.5">

                                                        Online Meeting

                                                    </div>

                                                </div>

                                            </div>

                                        </td>

                                        <!-- Date & Time -->
                                        <td class="px-4 py-4 whitespace-nowrap">

                                            <div class="text-slate-700 font-medium">

                                                <?= htmlspecialchars($scheduledDate) ?>

                                            </div>

                                            <div class="text-xs text-slate-400 mt-0.5">

                                                <?= htmlspecialchars($scheduledTime) ?>

                                            </div>

                                        </td>

                                        <!-- Platform -->
                                        <td class="px-4 py-4">

                                            <?php if ($meetingLink): ?>

                                                <a href="<?= htmlspecialchars($meetingLink) ?>" target="_blank"
                                                    rel="noopener noreferrer" class="inline-flex items-center gap-2 group">

                                                    <span class="w-8 h-8 rounded-lg
                                                           bg-blue-50 text-blue-600
                                                           flex items-center justify-center
                                                           group-hover:bg-blue-100
                                                           transition">

                                                        <i class="fas fa-video text-xs"></i>

                                                    </span>

                                                    <span class="text-xs font-medium text-slate-600
                                                           group-hover:text-blue-600 transition">

                                                        Join Meet

                                                    </span>

                                                </a>

                                            <?php else: ?>

                                                <span class="text-xs text-slate-400">
                                                    No platform
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <!-- Status -->
                                        <td class="px-4 py-4 whitespace-nowrap">

                                            <span class="inline-flex items-center gap-1.5
                                                   px-2.5 py-1 rounded-full border
                                                   text-xs font-medium
                                                   <?= $statusClass ?>">

                                                <span class="w-1.5 h-1.5 rounded-full
                                                       bg-current
                                                       <?= $status === 'ongoing'
                                                           ? 'animate-pulse'
                                                           : '' ?>">
                                                </span>

                                                <?= htmlspecialchars($statusLabel) ?>

                                            </span>

                                        </td>

                                        <!-- Action -->
                                        <td class="px-4 py-4 text-center">

                                            <?php if ($meetingLink && $status !== 'completed' && $status !== 'cancelled'): ?>

                                                <a href="<?= htmlspecialchars($meetingLink) ?>" target="_blank"
                                                    rel="noopener noreferrer" class="inline-flex items-center justify-center
                                                       gap-2 px-3 py-2 rounded-lg
                                                       bg-blue-600 text-white
                                                       text-xs font-medium
                                                       hover:bg-blue-700 transition">

                                                    <i class="fas fa-video"></i>

                                                    <?= $status === 'ongoing'
                                                        ? 'Join Now'
                                                        : 'Join Meeting' ?>

                                                </a>

                                            <?php elseif ($status === 'completed'): ?>

                                                <span class="text-xs text-slate-400">
                                                    Completed
                                                </span>

                                            <?php elseif ($status === 'cancelled'): ?>

                                                <span class="text-xs text-red-400">
                                                    Cancelled
                                                </span>

                                            <?php else: ?>

                                                <span class="text-xs text-slate-400">
                                                    No link
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr id="noMeetingRow">

                                    <td colspan="5" class="px-4 py-14 text-center">

                                        <div class="flex flex-col items-center">

                                            <div class="w-12 h-12 rounded-full
                                                   bg-slate-100 text-slate-400
                                                   flex items-center justify-center mb-3">

                                                <i class="fas fa-video-slash"></i>

                                            </div>

                                            <p class="text-sm font-medium text-slate-700">
                                                No online meetings found
                                            </p>

                                            <p class="text-xs text-slate-400 mt-1">
                                                Scheduled meetings will appear here.
                                            </p>

                                        </div>

                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-slate-200
                   flex flex-col sm:flex-row
                   sm:items-center sm:justify-between
                   gap-3">

                <p class="text-xs text-slate-500">

                    Showing

                    <span id="meetingPageStart" class="font-medium text-slate-700">
                        0
                    </span>

                    -

                    <span id="meetingPageEnd" class="font-medium text-slate-700">
                        0
                    </span>

                    of

                    <span id="meetingTotal" class="font-medium text-slate-700">
                        0
                    </span>

                    meetings

                </p>

                <div class="flex items-center gap-1">

                    <button type="button" id="meetingPrev" class="w-8 h-8 rounded-lg border border-slate-200
                           text-slate-500 flex items-center justify-center
                           hover:bg-slate-50
                           disabled:opacity-40
                           disabled:cursor-not-allowed">

                        <i class="fas fa-chevron-left text-xs"></i>

                    </button>

                    <span id="meetingPageInfo" class="min-w-[60px] text-center
                           text-xs text-slate-500">

                        1 / 1

                    </span>

                    <button type="button" id="meetingNext" class="w-8 h-8 rounded-lg border border-slate-200
                           text-slate-500 flex items-center justify-center
                           hover:bg-slate-50
                           disabled:opacity-40
                           disabled:cursor-not-allowed">

                        <i class="fas fa-chevron-right text-xs"></i>

                    </button>

                </div>

            </div>

        </div>

    </section>
</div>