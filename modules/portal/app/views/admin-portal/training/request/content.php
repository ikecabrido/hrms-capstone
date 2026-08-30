<div class="employee-dashboard">

    <section class="dashboard-welcome" id="trainingWelcome">
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>
        <div class="welcome-content">
            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                TRAINING REQUEST
            </span>
            <h1 class="welcome-title">
                Training Request of Employees
            </h1>
            <p class="welcome-description">
                View all the training request employees sent.
            </p>
            <div class="welcome-line"></div>
        </div>
        <div class="welcome-decoration">
            <i class="fas fa-graduation-cap"></i>
        </div>
    </section>

    <?php require __DIR__ . '/../../../partials/notification.php'; ?>

    <section class="w-full min-w-0 p-3 sm:p-4 lg:p-6">
        <div
            class="mb-5 flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">

            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                    <i class="fa-solid fa-graduation-cap text-blue-600"></i>
                </div>

                <div class="min-w-0">
                    <h2 class="text-xl font-bold leading-tight text-slate-800 sm:text-2xl">
                        Training Requests
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Manage and review employee training requests.
                    </p>
                </div>
            </div>

            <div class="flex w-full flex-col gap-2.5 sm:w-auto sm:flex-row sm:items-center">
                <div class="flex min-w-[150px] items-center gap-3 rounded-xl bg-white px-3.5 py-2.5 shadow-sm">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50">
                        <i class="fa-solid fa-list-check text-sm text-blue-600"></i>
                    </div>

                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            Total Requests
                        </p>

                        <p class="mt-0.5 text-sm font-bold text-slate-700">
                            <?= count($requests) ?>
                            <span class="font-medium text-slate-500">
                                <?= count($requests) === 1 ? 'Request' : 'Requests' ?>
                            </span>
                        </p>
                    </div>
                </div>

                <a href="index.php?url=admin-learning-index" style="text-decoration: none;"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-700 no-underline shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Back to Courses</span>
                </a>
            </div>

        </div>
        <div class="w-full min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div
                class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50/80 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100">
                        <i class="fa-solid fa-file-lines text-sm text-blue-600"></i>
                    </div>

                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-semibold text-slate-800">
                            Submitted Requests
                        </h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Review and update submitted training requests.
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="flex items-center gap-2 border-b border-slate-100 bg-white px-4 py-2.5 text-xs text-slate-400 sm:hidden">
                <i class="fa-solid fa-arrows-left-right"></i>
                <span>Swipe horizontally to view all columns</span>
            </div>

            <div class="w-full max-w-full overflow-x-auto overflow-y-visible"
                style="-webkit-overflow-scrolling: touch; scrollbar-width: thin;">

                <table class="w-full min-w-[900px] border-collapse text-left">

                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th
                                class="w-[65px] whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                ID
                            </th>

                            <th
                                class="w-[170px] whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Learner
                            </th>

                            <th
                                class="w-[190px] whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Requested Training
                            </th>

                            <th
                                class="w-[240px] whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Description
                            </th>

                            <th
                                class="w-[115px] whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Status
                            </th>

                            <th
                                class="w-[175px] whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Date Requested
                            </th>

                            <th
                                class="w-[150px] whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <?php if (empty($requests)): ?>

                            <tr>
                                <td colspan="7" class="px-4 py-14 text-center">
                                    <div
                                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                                        <i class="fa-solid fa-inbox text-xl text-slate-400"></i>
                                    </div>

                                    <h3 class="mt-4 text-sm font-semibold text-slate-700">
                                        No training requests
                                    </h3>

                                    <p class="mx-auto mt-1 max-w-sm text-xs text-slate-500">
                                        There are currently no submitted training requests.
                                    </p>
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($requests as $request): ?>

                                <?php
                                $status = strtolower(trim($request['status'] ?? 'pending'));

                                $statusClass = match ($status) {
                                    'reviewed' => 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20',
                                    'archived' => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-500/20',
                                    default => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
                                };

                                $statusIcon = match ($status) {
                                    'reviewed' => 'fa-eye',
                                    'archived' => 'fa-box-archive',
                                    default => 'fa-clock',
                                };

                                $createdAt = !empty($request['created_at'])
                                    ? date('M d, Y h:i A', strtotime($request['created_at']))
                                    : '—';
                                ?>

                                <tr class="group transition-colors duration-150 hover:bg-slate-50">

                                    <td class="whitespace-nowrap px-4 py-3.5">
                                        <span
                                            class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 font-mono text-xs font-semibold text-slate-600">
                                            #<?= htmlspecialchars($request['id']) ?>
                                        </span>
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600">
                                                <?= strtoupper(substr((string) $request['learner_id'], 0, 1)) ?>
                                            </div>

                                            <div class="min-w-0">
                                                <p class="whitespace-nowrap text-sm font-semibold text-slate-800">
                                                    Learner #<?= htmlspecialchars($request['learner_id']) ?>
                                                </p>

                                                <p class="mt-0.5 whitespace-nowrap text-xs text-slate-500">
                                                    ID: <?= htmlspecialchars($request['learner_id']) ?>
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <div class="max-w-[190px]">
                                            <p class="truncate text-sm font-semibold text-slate-800"
                                                title="<?= htmlspecialchars($request['requested_title']) ?>">
                                                <?= htmlspecialchars($request['requested_title']) ?>
                                            </p>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <div class="max-w-[240px]">
                                            <p class="truncate text-sm text-slate-600"
                                                title="<?= htmlspecialchars($request['description']) ?>">
                                                <?= htmlspecialchars($request['description']) ?>
                                            </p>
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3.5">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass ?>">
                                            <i class="fa-solid <?= $statusIcon ?> text-[10px]"></i>
                                            <?= htmlspecialchars(ucfirst($status)) ?>
                                        </span>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3.5">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100">
                                                <i class="fa-regular fa-calendar text-xs text-slate-500"></i>
                                            </div>

                                            <div>
                                                <p class="whitespace-nowrap text-sm font-medium text-slate-700">
                                                    <?= htmlspecialchars($createdAt) ?>
                                                </p>

                                                <p class="mt-0.5 text-[11px] text-slate-400">
                                                    Submitted
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3.5 text-right">
                                        <div class="relative inline-block">

                                            <button type="button"
                                                class="inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm transition-all hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 hover:shadow"
                                                data-bs-toggle="dropdown" aria-expanded="false">

                                                <i class="fa-solid fa-rotate text-xs"></i>
                                                Update Status
                                                <i class="fa-solid fa-chevron-down text-[9px]"></i>
                                            </button>

                                            <ul
                                                class="dropdown-menu dropdown-menu-end rounded-lg border border-slate-200 bg-white p-1.5 shadow-lg">

                                                <?php
                                                if ($status === 'pending') {
                                                    $nextStatus = 'reviewed';
                                                    $buttonLabel = 'Mark as Reviewed';
                                                    $buttonIcon = 'fa-eye';
                                                    $buttonClass = 'hover:bg-blue-50 hover:text-blue-700';
                                                    $iconClass = 'bg-blue-50 text-blue-500';
                                                } elseif ($status === 'reviewed') {
                                                    $nextStatus = 'archived';
                                                    $buttonLabel = 'Archive Request';
                                                    $buttonIcon = 'fa-box-archive';
                                                    $buttonClass = 'hover:bg-red-50 hover:text-red-700';
                                                    $iconClass = 'bg-red-50 text-red-500';
                                                } else {
                                                    $nextStatus = 'reviewed';
                                                    $buttonLabel = 'Mark as Reviewed';
                                                    $buttonIcon = 'fa-eye';
                                                    $buttonClass = 'hover:bg-blue-50 hover:text-blue-700';
                                                    $iconClass = 'bg-blue-50 text-blue-500';
                                                }
                                                ?>

                                                <li>
                                                    <form method="POST" action="index.php?url=toggle-training-request">

                                                        <input type="hidden" name="request_id"
                                                            value="<?= htmlspecialchars($request['id']) ?>">

                                                        <input type="hidden" name="status"
                                                            value="<?= htmlspecialchars($nextStatus) ?>">

                                                        <button type="submit"
                                                            class="flex w-full items-center gap-2 rounded-md px-3 py-2.5 text-left text-xs font-medium text-slate-700 transition <?= $buttonClass ?>">

                                                            <span
                                                                class="flex h-7 w-7 items-center justify-center rounded-md <?= $iconClass ?>">
                                                                <i class="fa-solid <?= $buttonIcon ?>"></i>
                                                            </span>

                                                            <span><?= $buttonLabel ?></span>

                                                        </button>

                                                    </form>
                                                </li>

                                            </ul>

                                        </div>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <?php if ($totalRequests > 0): ?>

                <div
                    class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">

                    <p class="text-xs text-slate-500">
                        Showing
                        <span class="font-semibold text-slate-700">
                            <?= $start ?>–<?= $end ?>
                        </span>
                        of
                        <span class="font-semibold text-slate-700">
                            <?= $totalRequests ?>
                        </span>
                        request<?= $totalRequests !== 1 ? 's' : '' ?>
                    </p>

                    <?php if ($totalPages > 1): ?>

                        <nav class="flex items-center gap-1" aria-label="Training request pagination">

                            <?php if ($page > 1): ?>

                                <a href="index.php?url=admin-training-requests&page=<?= $page - 1 ?>"
                                    class="inline-flex h-8 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-medium text-slate-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">

                                    <i class="fa-solid fa-chevron-left text-[9px]"></i>

                                    <span class="hidden sm:inline">
                                        Previous
                                    </span>

                                </a>

                            <?php else: ?>

                                <span
                                    class="inline-flex h-8 cursor-not-allowed items-center gap-1 rounded-lg border border-slate-200 bg-slate-100 px-2.5 text-xs font-medium text-slate-400">

                                    <i class="fa-solid fa-chevron-left text-[9px]"></i>

                                    <span class="hidden sm:inline">
                                        Previous
                                    </span>

                                </span>

                            <?php endif; ?>


                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                                <?php if ($i === $page): ?>

                                    <span
                                        class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-blue-600 px-2 text-xs font-semibold text-white shadow-sm">
                                        <?= $i ?>
                                    </span>

                                <?php else: ?>

                                    <a href="index.php?url=admin-training-requests&page=<?= $i ?>"
                                        class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-slate-200 bg-white px-2 text-xs font-medium text-slate-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">

                                        <?= $i ?>

                                    </a>

                                <?php endif; ?>

                            <?php endfor; ?>


                            <?php if ($page < $totalPages): ?>

                                <a href="index.php?url=admin-training-requests&page=<?= $page + 1 ?>"
                                    class="inline-flex h-8 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-medium text-slate-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">

                                    <span class="hidden sm:inline">
                                        Next
                                    </span>

                                    <i class="fa-solid fa-chevron-right text-[9px]"></i>

                                </a>

                            <?php else: ?>

                                <span
                                    class="inline-flex h-8 cursor-not-allowed items-center gap-1 rounded-lg border border-slate-200 bg-slate-100 px-2.5 text-xs font-medium text-slate-400">

                                    <span class="hidden sm:inline">
                                        Next
                                    </span>

                                    <i class="fa-solid fa-chevron-right text-[9px]"></i>

                                </span>

                            <?php endif; ?>

                        </nav>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>
    </section>
</div>