<div class="overflow-hidden rounded-[28px] bg-white shadow-sm ring-1 ring-slate-200/80">
    <!-- Course Hero -->
    <div class="relative overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950">

        <!-- Background decoration -->
        <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-indigo-500/20 blur-3xl">
        </div>
        <div class="pointer-events-none absolute -bottom-40 left-1/3 h-96 w-96 rounded-full bg-violet-500/10 blur-3xl">
        </div>

        <div class="relative px-7 py-9 sm:px-10 sm:py-11 lg:px-12 lg:py-12">

            <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_260px] lg:items-center">

                <!-- Course Information -->
                <div class="min-w-0">

                    <!-- Status + Category -->
                    <div class="mb-5 flex flex-wrap items-center gap-3">

                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide text-emerald-300">
                            <span class="relative flex h-1.5 w-1.5">
                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                                <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            </span>

                            <?= htmlspecialchars($statusLabel) ?>
                        </span>

                        <?php if (!empty($course['category'])): ?>

                            <span class="text-[11px] font-medium text-slate-400">
                                <?= htmlspecialchars($course['category']) ?>
                            </span>

                        <?php endif; ?>

                    </div>

                    <!-- Title -->
                    <h1 style="margin-left: 20px;"
                        class="max-w-4xl text-3xl font-bold leading-[1.12] tracking-tight text-white sm:text-4xl lg:text-[46px]">
                        <?= htmlspecialchars($course['title'] ?? 'Enrolled Course') ?>
                    </h1>

                    <!-- Description -->
                    <p style="margin-left: 20px;" class="mt-5 max-w-3xl text-sm leading-7 text-slate-300 sm:text-base">
                        <?= htmlspecialchars(
                            $course['description'] ??
                            'Continue your learning journey and complete the course at your own pace.'
                        ) ?>
                    </p>

                    <!-- Course metadata -->
                    <div style="margin-left: 20px;" class="mt-8 flex flex-wrap items-center gap-x-7 gap-y-4">

                        <div class="flex items-center gap-2.5 text-xs font-medium text-slate-300">
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/5 ring-1 ring-white/10">
                                <i class="fa-regular fa-clock text-indigo-300"></i>
                            </span>

                            <span>
                                <?= htmlspecialchars($course['duration'] ?? 'Self-paced') ?>
                            </span>
                        </div>

                        <div class="flex items-center gap-2.5 text-xs font-medium text-slate-300">
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/5 ring-1 ring-white/10">
                                <i class="fa-solid fa-layer-group text-indigo-300"></i>
                            </span>

                            <span>
                                <?= $totalModules ?> modules
                            </span>
                        </div>

                        <div class="flex items-center gap-2.5 text-xs font-medium text-slate-300">
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/5 ring-1 ring-white/10">
                                <i class="fa-solid fa-book-open text-indigo-300"></i>
                            </span>

                            <span>
                                <?= $totalLessons ?> lessons
                            </span>
                        </div>

                    </div>

                </div>

                <!-- Course Visual -->
                <div class="hidden lg:flex justify-center">

                    <div class="relative">

                        <div class="absolute inset-0 rounded-[32px] bg-indigo-500/20 blur-2xl"></div>

                        <div
                            class="relative flex h-52 w-52 items-center justify-center rounded-[32px] border border-white/10 bg-white/[0.06] shadow-2xl backdrop-blur-xl">

                            <div
                                class="flex h-28 w-28 items-center justify-center rounded-[28px] bg-gradient-to-br from-indigo-500/30 to-violet-500/20 ring-1 ring-white/10">
                                <i class="fa-solid fa-graduation-cap text-6xl text-indigo-200"></i>
                            </div>

                        </div>

                        <!-- Decorative dots -->
                        <span class="absolute -right-3 top-8 h-3 w-3 rounded-full bg-indigo-400/70"></span>
                        <span class="absolute -left-4 bottom-12 h-2 w-2 rounded-full bg-violet-400/70"></span>
                        <span class="absolute right-8 -bottom-3 h-2 w-2 rounded-full bg-emerald-400/60"></span>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Course Statistics -->
    <div class="grid divide-y divide-slate-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0" style="margin-top: 10px;">

        <!-- Course Progress -->
        <div class="px-7 py-6 sm:px-8">

            <div class="flex items-start justify-between">

                <div>

                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">
                        Course progress
                    </p>

                    <div class="mt-2 flex items-baseline gap-1">

                        <span class="text-3xl font-bold tracking-tight text-slate-900">
                            <?= (int) $courseProgress ?>
                        </span>

                        <span class="text-sm font-semibold text-slate-400">
                            %
                        </span>

                    </div>

                </div>

                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <i class="fa-solid fa-chart-line text-xs"></i>
                </div>

            </div>

            <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-slate-100">

                <div class="h-full rounded-full bg-indigo-600 transition-all duration-500"
                    style="width: <?= min(100, max(0, (int) $courseProgress)) ?>%;">
                </div>

            </div>

            <p class="mt-3 text-xs text-slate-400">
                <?= (int) $completedLessons ?>
                of
                <?= (int) $totalLessons ?>
                lessons completed
            </p>

        </div>


        <!-- Lessons Completed -->
        <div class="px-7 py-6 sm:px-8">

            <div class="flex items-start justify-between">

                <div>

                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">
                        Lessons completed
                    </p>

                    <div class="mt-2 flex items-baseline gap-1">

                        <span class="text-3xl font-bold tracking-tight text-slate-900">
                            <?= (int) $completedLessons ?>
                        </span>

                        <span class="text-sm font-medium text-slate-400">
                            / <?= (int) $totalLessons ?>
                        </span>

                    </div>

                </div>

                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <i class="fa-solid fa-circle-check text-xs"></i>
                </div>

            </div>

            <p class="mt-3 text-xs text-slate-400">
                <?= (int) $remainingLessons ?> lessons remaining
            </p>

        </div>


        <!-- Modules Remaining -->
        <div class="px-7 py-6 sm:px-8">

            <div class="flex items-start justify-between">

                <div>

                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">
                        Modules remaining
                    </p>

                    <div class="mt-2 flex items-baseline gap-1">

                        <span class="text-3xl font-bold tracking-tight text-slate-900">
                            <?= (int) $remainingModules ?>
                        </span>

                        <span class="text-sm font-medium text-slate-400">
                            modules
                        </span>

                    </div>

                </div>

                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </div>

            </div>

            <p class="mt-3 text-xs text-slate-400">

                <?php if ($remainingModules > 0): ?>

                    <?= (int) $remainingModules ?>
                    <?= $remainingModules === 1 ? 'module' : 'modules' ?>
                    left to complete

                <?php else: ?>

                    All modules completed

                <?php endif; ?>

            </p>

        </div>

    </div>

</div>