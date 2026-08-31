<div class="employee-dashboard">

    <section class="dashboard-welcome" id="enrolledCourseWelcome">

        <div class="welcome-glow glow-one"></div>

        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label active-learning">

                <i class="fas fa-book-open"></i>

                MY ACTIVE LEARNING

            </span>

            <h1 class="welcome-title">

                Enrolled Course

            </h1>

            <p class="welcome-description">

                Access your current classes, resume your last lesson,
                and track your academic completion progress.

            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration">

            <i class="fas fa-user-graduate"></i>

        </div>

    </section>


    <?php require __DIR__ . '/../../../partials/notification.php'; ?>


    <?php require __DIR__ . '/php-logic.php'; ?>


    <section class="w-full bg-slate-50 px-4 py-6 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-14 lg:py-8">

        <div class="mx-auto w-full max-w-[1700px] space-y-6">


            <?php require __DIR__ . '/header.php'; ?>


            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">


                <div class="min-w-0">


                    <?php if ($courseIsCompleted): ?>

                        <div
                            class="flex flex-col items-center justify-center rounded-2xl border border-emerald-100 bg-white px-6 py-16 text-center shadow-sm">

                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                <i class="fa-solid fa-circle-check text-2xl"></i>
                            </div>

                            <h2 class="mt-5 text-xl font-bold tracking-tight text-slate-900">
                                Training Completed
                            </h2>

                            <p class="mt-2 max-w-md text-sm leading-6 text-slate-500">
                                You have successfully completed
                                <span class="font-semibold text-slate-700">
                                    <?= htmlspecialchars($course['title'] ?? 'this training') ?>
                                </span>.
                                You can enroll in another training program.
                            </p>

                            <a href="index.php?url=training"
                                class="mt-6 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                <i class="fa-solid fa-plus text-[10px]"></i>
                                Enroll New Training
                            </a>

                        </div>

                    <?php else: ?>

                        <div class="mb-5">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-indigo-600">
                                Learning Path
                            </p>

                            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                                Course Content
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Work through each module at your own pace.
                            </p>
                        </div>

                        <?php if (empty($modules)): ?>

                            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center shadow-sm">

                                <div
                                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <i class="fa-solid fa-book-open text-xl"></i>
                                </div>

                                <h3 class="mt-4 text-base font-bold text-slate-900">
                                    No Course Modules
                                </h3>

                                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                                    This training does not have any active modules yet.
                                    Please check back later.
                                </p>

                            </div>

                        <?php else: ?>

                            <div class="space-y-4">

                                <?php foreach ($modules as $moduleIndex => $module): ?>

                                    <?php
                                    $moduleLessons = $module['lessons'] ?? [];
                                    $moduleLessonCount = count($moduleLessons);

                                    $moduleCompletedLessons = 0;
                                    $moduleFileCount = 0;

                                    foreach ($moduleLessons as $lesson) {

                                        $lessonCompleted =
                                            ($lesson['progress_status'] ?? '') === 'completed'
                                            || !empty($lesson['completed']);

                                        if ($lessonCompleted) {
                                            $moduleCompletedLessons++;
                                        }

                                        $moduleFileCount += count($lesson['files'] ?? []);
                                    }

                                    $moduleProgress = $moduleLessonCount > 0
                                        ? (int) round(
                                            ($moduleCompletedLessons / $moduleLessonCount) * 100
                                        )
                                        : 0;

                                    $isCompleted =
                                        ($module['progress_status'] ?? '') === 'completed'
                                        || (
                                            $moduleLessonCount > 0
                                            && $moduleCompletedLessons === $moduleLessonCount
                                        );

                                    if ($isCompleted) {
                                        $moduleProgress = 100;
                                    }

                                    $isActive =
                                        !$isCompleted
                                        && $moduleCompletedLessons > 0;
                                    ?>

                                    <!-- =====================================================
                     MODULE DROPDOWN
                ====================================================== -->

                                    <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                                        <?= $moduleIndex === 0 ? 'open' : '' ?>>

                                        <!-- MODULE HEADER -->

                                        <summary class="cursor-pointer list-none select-none">

                                            <div
                                                class="flex flex-col gap-5 px-5 py-5 transition hover:bg-slate-50 sm:px-6 sm:py-6 lg:flex-row lg:items-center lg:justify-between">

                                                <div class="flex min-w-0 items-start gap-4">

                                                    <!-- Module Icon -->

                                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl
                                    <?= $isCompleted
                                        ? 'bg-emerald-50 text-emerald-600'
                                        : ($isActive
                                            ? 'bg-indigo-50 text-indigo-600'
                                            : 'bg-slate-100 text-slate-500') ?>">

                                                        <?php if ($isCompleted): ?>

                                                            <i class="fa-solid fa-circle-check"></i>

                                                        <?php elseif ($isActive): ?>

                                                            <i class="fa-solid fa-play"></i>

                                                        <?php else: ?>

                                                            <i class="fa-solid fa-book"></i>

                                                        <?php endif; ?>

                                                    </div>

                                                    <!-- Module Information -->

                                                    <div class="min-w-0 flex-1">

                                                        <div class="flex items-center gap-2">

                                                            <p
                                                                class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">
                                                                Module <?= $moduleIndex + 1 ?>
                                                            </p>

                                                            <i
                                                                class="fa-solid fa-chevron-down text-[9px] text-slate-400 transition-transform duration-200 group-open:rotate-180"></i>

                                                        </div>

                                                        <h3 class="mt-1 text-base font-bold text-slate-900 sm:text-lg">
                                                            <?= htmlspecialchars($module['title'] ?? 'Untitled Module') ?>
                                                        </h3>

                                                        <?php if (!empty($module['description'])): ?>

                                                            <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500">
                                                                <?= htmlspecialchars($module['description']) ?>
                                                            </p>

                                                        <?php endif; ?>

                                                        <!-- Module Statistics -->

                                                        <div
                                                            class="mt-3 flex flex-wrap items-center gap-3 text-[11px] text-slate-400">

                                                            <span class="inline-flex items-center gap-1.5">
                                                                <i class="fa-regular fa-file-lines"></i>
                                                                <?= $moduleLessonCount ?>
                                                                <?= $moduleLessonCount === 1 ? 'Lesson' : 'Lessons' ?>
                                                            </span>

                                                            <span class="inline-flex items-center gap-1.5">
                                                                <i class="fa-solid fa-check"></i>
                                                                <?= $moduleCompletedLessons ?> completed
                                                            </span>

                                                            <span class="inline-flex items-center gap-1.5">
                                                                <i class="fa-solid fa-paperclip"></i>
                                                                <?= $moduleFileCount ?>
                                                                <?= $moduleFileCount === 1 ? 'File' : 'Files' ?>
                                                            </span>

                                                        </div>

                                                    </div>

                                                </div>

                                                <!-- Module Progress -->

                                                <div class="w-full shrink-0 lg:w-44">

                                                    <div class="mb-2 flex items-center justify-between text-[11px]">

                                                        <span class="font-medium text-slate-400">
                                                            Progress
                                                        </span>

                                                        <span
                                                            class="font-bold <?= $isCompleted ? 'text-emerald-600' : 'text-indigo-600' ?>">
                                                            <?= $moduleProgress ?>%
                                                        </span>

                                                    </div>

                                                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">

                                                        <div class="h-full rounded-full transition-all duration-500 <?= $isCompleted ? 'bg-emerald-500' : 'bg-indigo-600' ?>"
                                                            style="width: <?= $moduleProgress ?>%;">
                                                        </div>

                                                    </div>

                                                </div>

                                            </div>

                                        </summary>

                                        <!-- =====================================================
                         MODULE CONTENT
                    ====================================================== -->

                                        <div class="border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-5">

                                            <?php if ($moduleLessonCount > 0): ?>

                                                <div class="space-y-3">

                                                    <?php foreach ($moduleLessons as $lessonIndex => $lesson): ?>

                                                        <?php
                                                        $lessonCompleted =
                                                            ($lesson['progress_status'] ?? '') === 'completed'
                                                            || !empty($lesson['completed']);

                                                        $lessonFiles = $lesson['files'] ?? [];
                                                        $lessonFileCount = count($lessonFiles);

                                                        $hasLessonContent =
                                                            !empty($lesson['content_body'])
                                                            || !empty($lesson['video_url'])
                                                            || $lessonFileCount > 0;
                                                        ?>

                                                        <!-- =================================================
                                         LESSON DROPDOWN
                                    ================================================== -->

                                                        <details class="group overflow-hidden rounded-xl border border-slate-200 bg-white">

                                                            <!-- LESSON HEADER -->

                                                            <summary class="cursor-pointer list-none select-none">

                                                                <div
                                                                    class="flex flex-col gap-3 px-4 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center">

                                                                    <!-- Lesson Icon -->

                                                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg
                                                    <?= $lessonCompleted
                                                        ? 'bg-emerald-50 text-emerald-600'
                                                        : 'bg-indigo-50 text-indigo-600' ?>">

                                                                        <?php if ($lessonCompleted): ?>

                                                                            <i class="fa-solid fa-check text-xs"></i>

                                                                        <?php else: ?>

                                                                            <i class="fa-solid fa-play text-[10px]"></i>

                                                                        <?php endif; ?>

                                                                    </div>

                                                                    <!-- Lesson Information -->

                                                                    <div class="min-w-0 flex-1">

                                                                        <div class="flex items-center gap-2">

                                                                            <p
                                                                                class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                                                                Lesson <?= $lessonIndex + 1 ?>
                                                                            </p>

                                                                            <i
                                                                                class="fa-solid fa-chevron-down text-[8px] text-slate-400 transition-transform duration-200 group-open:rotate-180"></i>

                                                                        </div>

                                                                        <h4 class="mt-0.5 text-sm font-semibold text-slate-800">
                                                                            <?= htmlspecialchars($lesson['title'] ?? 'Untitled Lesson') ?>
                                                                        </h4>

                                                                        <div
                                                                            class="mt-1 flex flex-wrap items-center gap-3 text-[10px] text-slate-400">

                                                                            <?php if (!empty($lesson['content_type'])): ?>

                                                                                <span class="inline-flex items-center gap-1">
                                                                                    <i class="fa-solid fa-layer-group"></i>
                                                                                    <?= htmlspecialchars(ucfirst($lesson['content_type'])) ?>
                                                                                </span>

                                                                            <?php endif; ?>

                                                                            <?php if (!empty($lesson['duration'])): ?>

                                                                                <span class="inline-flex items-center gap-1">
                                                                                    <i class="fa-regular fa-clock"></i>
                                                                                    <?= htmlspecialchars($lesson['duration']) ?>
                                                                                </span>

                                                                            <?php endif; ?>

                                                                            <span class="inline-flex items-center gap-1">
                                                                                <i class="fa-solid fa-paperclip"></i>
                                                                                <?= $lessonFileCount ?>
                                                                                <?= $lessonFileCount === 1 ? 'file' : 'files' ?>
                                                                            </span>

                                                                        </div>

                                                                    </div>

                                                                    <!-- Lesson Status -->

                                                                    <?php if ($lessonCompleted): ?>

                                                                        <span
                                                                            class="inline-flex w-fit shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-[10px] font-bold text-emerald-600">
                                                                            <i class="fa-solid fa-check text-[9px]"></i>
                                                                            Completed
                                                                        </span>

                                                                    <?php else: ?>

                                                                        <span
                                                                            class="inline-flex w-fit shrink-0 items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 text-[10px] font-bold text-indigo-600">
                                                                            <i class="fa-solid fa-circle-play text-[9px]"></i>
                                                                            Available
                                                                        </span>

                                                                    <?php endif; ?>

                                                                </div>

                                                            </summary>

                                                            <!-- =================================================
                                             LESSON CONTENT
                                        ================================================== -->

                                                            <?php if ($hasLessonContent): ?>

                                                                <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-4">

                                                                    <div class="space-y-4">

                                                                        <!-- TEXT CONTENT -->

                                                                        <?php if (!empty($lesson['content_body'])): ?>

                                                                            <div>

                                                                                <p
                                                                                    class="mb-2 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">
                                                                                    Lesson Content
                                                                                </p>

                                                                                <div
                                                                                    class="rounded-xl border border-slate-200 bg-white px-4 py-4 text-sm leading-6 text-slate-600">
                                                                                    <?= nl2br(htmlspecialchars($lesson['content_body'])) ?>
                                                                                </div>

                                                                            </div>

                                                                        <?php endif; ?>

                                                                        <!-- VIDEO -->

                                                                        <?php if (!empty($lesson['video_url'])): ?>

                                                                            <div>

                                                                                <p
                                                                                    class="mb-2 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">
                                                                                    Video Lesson
                                                                                </p>

                                                                                <a href="<?= htmlspecialchars($lesson['video_url']) ?>"
                                                                                    target="_blank" rel="noopener noreferrer"
                                                                                    class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 transition hover:border-indigo-200 hover:bg-indigo-50">

                                                                                    <span
                                                                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                                                                        <i class="fa-solid fa-play text-xs"></i>
                                                                                    </span>

                                                                                    <span class="min-w-0 flex-1">

                                                                                        <span class="block text-xs font-bold text-slate-800">
                                                                                            Watch Video
                                                                                        </span>

                                                                                        <span
                                                                                            class="mt-0.5 block truncate text-[10px] text-slate-400">
                                                                                            <?= htmlspecialchars($lesson['video_url']) ?>
                                                                                        </span>

                                                                                    </span>

                                                                                    <i
                                                                                        class="fa-solid fa-arrow-up-right-from-square text-[10px] text-slate-400"></i>

                                                                                </a>

                                                                            </div>

                                                                        <?php endif; ?>

                                                                        <!-- LESSON FILES -->

                                                                        <?php if ($lessonFileCount > 0): ?>

                                                                            <?php
                                                                            /*
                                                                             * Your public assets directory:
                                                                             *
                                                                             * http://localhost/hrms-capstone/modules/portal/public/assets/
                                                                             *
                                                                             * Files stored in:
                                                                             *
                                                                             * uploads/training/filename.pdf
                                                                             *
                                                                             * become:
                                                                             *
                                                                             * http://localhost/hrms-capstone/modules/portal/public/assets/uploads/training/filename.pdf
                                                                             */

                                                                            $publicAssetsUrl =
                                                                                'http://localhost/hrms-capstone/modules/portal/public/assets/';
                                                                            ?>

                                                                            <div>

                                                                                <div class="mb-2 flex items-center justify-between">

                                                                                    <p
                                                                                        class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">
                                                                                        Lesson Files
                                                                                    </p>

                                                                                    <span class="text-[10px] font-semibold text-slate-400">
                                                                                        <?= $lessonFileCount ?>
                                                                                        <?= $lessonFileCount === 1 ? 'file' : 'files' ?>
                                                                                    </span>

                                                                                </div>

                                                                                <div class="space-y-2">

                                                                                    <?php foreach ($lessonFiles as $fileIndex => $file): ?>

                                                                                        <?php
                                                                                        $storedFilePath = trim(
                                                                                            (string) ($file['file_path'] ?? '')
                                                                                        );

                                                                                        /*
                                                                                         * Build the correct browser URL.
                                                                                         *
                                                                                         * Supports:
                                                                                         *
                                                                                         * 1. uploads/training/file.pdf
                                                                                         * 2. /uploads/training/file.pdf
                                                                                         * 3. assets/uploads/training/file.pdf
                                                                                         * 4. full http:// URL
                                                                                         */

                                                                                        if (
                                                                                            filter_var(
                                                                                                $storedFilePath,
                                                                                                FILTER_VALIDATE_URL
                                                                                            )
                                                                                        ) {

                                                                                            $filePath = $storedFilePath;

                                                                                        } else {

                                                                                            $storedFilePath = str_replace('\\', '/', $storedFilePath);

                                                                                            $storedFilePath = ltrim($storedFilePath, '/');

                                                                                            if (
                                                                                                str_starts_with($storedFilePath, 'http://')
                                                                                                || str_starts_with($storedFilePath, 'https://')
                                                                                            ) {

                                                                                                $filePath = $storedFilePath;

                                                                                            } elseif (
                                                                                                str_starts_with(
                                                                                                    $storedFilePath,
                                                                                                    'modules/portal/public/assets/'
                                                                                                )
                                                                                            ) {

                                                                                                $filePath =
                                                                                                    'http://localhost/hrms-capstone/'
                                                                                                    . $storedFilePath;

                                                                                            } elseif (
                                                                                                str_starts_with(
                                                                                                    $storedFilePath,
                                                                                                    'assets/'
                                                                                                )
                                                                                            ) {

                                                                                                $filePath =
                                                                                                    'http://localhost/hrms-capstone/modules/portal/public/'
                                                                                                    . $storedFilePath;

                                                                                            } else {

                                                                                                $filePath =
                                                                                                    $publicAssetsUrl
                                                                                                    . $storedFilePath;
                                                                                            }
                                                                                        }

                                                                                        $fileTitle =
                                                                                            $file['title']
                                                                                            ?? ('Lesson File ' . ($fileIndex + 1));

                                                                                        $fileExtension =
                                                                                            strtolower(
                                                                                                pathinfo(
                                                                                                    parse_url(
                                                                                                        $filePath,
                                                                                                        PHP_URL_PATH
                                                                                                    ) ?? '',
                                                                                                    PATHINFO_EXTENSION
                                                                                                )
                                                                                            );
                                                                                        ?>

                                                                                        <a href="<?= htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8') ?>"
                                                                                            target="_blank" rel="noopener noreferrer"
                                                                                            class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 transition hover:border-indigo-200 hover:bg-indigo-50">

                                                                                            <!-- File Icon -->

                                                                                            <div
                                                                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 transition group-hover:bg-indigo-100">

                                                                                                <?php if ($fileExtension === 'pdf'): ?>

                                                                                                    <i class="fa-solid fa-file-pdf text-xs"></i>

                                                                                                <?php elseif (in_array($fileExtension, ['doc', 'docx'])): ?>

                                                                                                    <i class="fa-solid fa-file-word text-xs"></i>

                                                                                                <?php elseif (in_array($fileExtension, ['xls', 'xlsx'])): ?>

                                                                                                    <i class="fa-solid fa-file-excel text-xs"></i>

                                                                                                <?php elseif (in_array($fileExtension, ['ppt', 'pptx'])): ?>

                                                                                                    <i class="fa-solid fa-file-powerpoint text-xs"></i>

                                                                                                <?php elseif (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>

                                                                                                    <i class="fa-solid fa-file-image text-xs"></i>

                                                                                                <?php else: ?>

                                                                                                    <i class="fa-solid fa-file-arrow-down text-xs"></i>

                                                                                                <?php endif; ?>

                                                                                            </div>

                                                                                            <!-- File Information -->

                                                                                            <div class="min-w-0 flex-1">

                                                                                                <p
                                                                                                    class="truncate text-xs font-semibold text-slate-800">
                                                                                                    <?= htmlspecialchars($fileTitle) ?>
                                                                                                </p>

                                                                                                <div
                                                                                                    class="mt-0.5 flex flex-wrap items-center gap-2 text-[10px] text-slate-400">

                                                                                                    <?php if ($fileExtension): ?>

                                                                                                        <span class="uppercase">
                                                                                                            <?= htmlspecialchars($fileExtension) ?>
                                                                                                        </span>

                                                                                                    <?php endif; ?>

                                                                                                    <?php if (!empty($file['uploaded_at'])): ?>

                                                                                                        <span>
                                                                                                            • Uploaded
                                                                                                            <?= htmlspecialchars($file['uploaded_at']) ?>
                                                                                                        </span>

                                                                                                    <?php endif; ?>

                                                                                                </div>

                                                                                            </div>

                                                                                            <!-- Open File -->

                                                                                            <span
                                                                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition group-hover:text-indigo-600">
                                                                                                <i
                                                                                                    class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                                                                            </span>

                                                                                        </a>

                                                                                    <?php endforeach; ?>

                                                                                </div>

                                                                            </div>

                                                                        <?php endif; ?>

                                                                    </div>

                                                                </div>

                                                            <?php else: ?>

                                                                <div class="border-t border-slate-100 px-4 py-5 text-center">

                                                                    <div
                                                                        class="mx-auto flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                                                        <i class="fa-solid fa-file-circle-question text-xs"></i>
                                                                    </div>

                                                                    <p class="mt-2 text-xs font-semibold text-slate-600">
                                                                        No lesson content
                                                                    </p>

                                                                    <p class="mt-1 text-[10px] text-slate-400">
                                                                        This lesson does not have any content or files yet.
                                                                    </p>

                                                                </div>

                                                            <?php endif; ?>

                                                        </details>

                                                    <?php endforeach; ?>

                                                </div>

                                            <?php else: ?>

                                                <!-- No Lessons -->

                                                <div
                                                    class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-white px-6 py-8 text-center">

                                                    <div
                                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                                        <i class="fa-solid fa-file-circle-question"></i>
                                                    </div>

                                                    <p class="mt-3 text-xs font-semibold text-slate-600">
                                                        No lessons available
                                                    </p>

                                                    <p class="mt-1 text-[11px] text-slate-400">
                                                        No lessons have been added to this module yet.
                                                    </p>

                                                </div>

                                            <?php endif; ?>

                                        </div>

                                    </details>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                    <?php endif; ?>



                </div>


                <aside class="space-y-4">


                    <?php if ($activeLesson): ?>


                        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">


                            <div class="border-b border-slate-100 px-5 py-4">

                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-indigo-600">

                                    Continue Learning

                                </p>


                                <h3 class="mt-1 text-base font-bold text-slate-900">

                                    Pick up where you left off

                                </h3>

                            </div>


                            <div class="p-5">


                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">

                                    <i class="fa-solid fa-play"></i>

                                </div>


                                <p class="mt-4 text-xs font-medium text-slate-400">

                                    Current lesson

                                </p>


                                <h4 class="mt-1 text-sm font-bold leading-5 text-slate-900">

                                    <?= htmlspecialchars(
                                        $activeLesson['title']
                                        ?? 'Continue learning'
                                    ) ?>

                                </h4>


                                <?php if (!empty($activeLesson['duration'])): ?>

                                    <p class="mt-2 text-xs text-slate-400">

                                        <i class="fa-regular fa-clock mr-1"></i>

                                        <?= htmlspecialchars(
                                            $activeLesson['duration']
                                        ) ?>

                                    </p>

                                <?php endif; ?>


                                <?php if (!empty($activeLesson['url'])): ?>

                                    <a href="<?= htmlspecialchars(
                                        $activeLesson['url']
                                    ) ?>"
                                        class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm shadow-indigo-200 transition hover:bg-indigo-700">

                                        <i class="fa-solid fa-play text-[10px]"></i>

                                        Continue Lesson

                                    </a>

                                <?php endif; ?>


                            </div>


                        </div>


                    <?php endif; ?>


                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">


                        <div class="flex items-center justify-between">


                            <div>

                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">

                                    Your Progress

                                </p>


                                <p class="mt-1 text-2xl font-bold text-slate-900">

                                    <?= $courseProgress ?>%

                                </p>

                            </div>


                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">

                                <i class="fa-solid fa-chart-line"></i>

                            </div>

                        </div>


                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">

                            <div class="h-full rounded-full bg-indigo-600 transition-all duration-500"
                                style="width: <?= $courseProgress ?>%;">

                            </div>

                        </div>


                        <div class="mt-3 flex items-center justify-between text-[11px]">

                            <span class="text-slate-400">

                                <?= $completedLessons ?>

                                completed

                            </span>


                            <span class="font-semibold text-slate-500">

                                <?= $remainingLessons ?>

                                remaining

                            </span>

                        </div>


                    </div>


                    <div class="rounded-2xl bg-indigo-50 p-5">


                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm">

                            <i class="fa-solid fa-lightbulb"></i>

                        </div>


                        <h3 class="mt-4 text-sm font-bold text-slate-900">

                            Keep going

                        </h3>


                        <p class="mt-1 text-xs leading-5 text-slate-500">

                            Consistent progress is better than rushing.
                            Complete one lesson at a time.

                        </p>


                    </div>


                </aside>


            </div>


            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-5 shadow-sm sm:px-6">


                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">


                    <div class="flex items-center gap-4">


                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">

                            <i class="fa-solid fa-trophy"></i>

                        </div>


                        <div>

                            <h3 class="text-sm font-bold text-slate-900">

                                You're making progress

                            </h3>


                            <p class="mt-1 text-xs text-slate-500 sm:text-sm">

                                You've completed

                                <strong class="text-slate-700">

                                    <?= $completedLessons ?>

                                </strong>

                                of

                                <strong class="text-slate-700">

                                    <?= $totalLessons ?>

                                </strong>

                                lessons.

                            </p>

                        </div>


                    </div>


                    <div class="text-left sm:text-right">

                        <p class="text-xl font-bold text-indigo-600">

                            <?= $courseProgress ?>%

                        </p>


                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">

                            Complete

                        </p>

                    </div>


                </div>


            </div>


        </div>

    </section>

</div>