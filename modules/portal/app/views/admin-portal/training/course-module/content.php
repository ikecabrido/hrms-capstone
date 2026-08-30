<div class="employee-dashboard">

    <section class="dashboard-welcome" id="courseManagementWelcome">
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>
        <div class="welcome-content">
            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                COURSE MODULE MANAGEMENT
            </span>
            <h1 class="welcome-title">
                Course Module Management
            </h1>
            <p class="welcome-description">
                Create and manage course modules, organize modules and lessons,
                and prepare learning content for employees.
            </p>
            <div class="welcome-line"></div>
        </div>
        <div class="welcome-decoration">
            <i class="fas fa-book-open"></i>
        </div>

    </section>

    <?php require __DIR__ . '/../../../partials/notification.php'; ?>

    <section class="w-full min-h-screen bg-slate-50 p-4 sm:p-6 lg:p-8">

        <div class="mx-auto w-full max-w-6xl">

            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100">
                        <i class="fa-solid fa-graduation-cap text-blue-600"></i>
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">
                            Course Management
                        </p>

                        <h1 class="truncate text-xl font-bold text-slate-800 sm:text-2xl">
                            <?= htmlspecialchars($course['title']) ?>
                        </h1>

                        <p class="mt-1 text-sm text-slate-500">
                            Build and organize your course content.
                        </p>
                    </div>
                </div>
                <a href="index.php?url=admin-learning-index" style="text-decoration: none;"
                    class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Back to Course
                </a>
                <button type="button"
                    class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                    data-bs-toggle="modal" data-bs-target="#addModuleModal">
                    <i class="fa-solid fa-plus text-xs"></i>
                    Add Module
                </button>
            </div>

            <div class="mb-6 rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="flex items-start gap-3 p-4 sm:p-5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50">
                        <i class="fa-solid fa-circle-info text-sm text-blue-600"></i>
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Course Description
                        </p>

                        <h2 class="mt-1 text-sm font-semibold text-slate-800">
                            <?= htmlspecialchars($course['title']) ?>
                        </h2>

                        <?php if (!empty($course['description'])): ?>
                            <p class="mt-1 text-sm leading-relaxed text-slate-500">
                                <?= htmlspecialchars($course['description']) ?>
                            </p>
                        <?php else: ?>
                            <p class="mt-1 text-sm italic text-slate-400">
                                No course description available.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-2 border-t border-slate-100 sm:grid-cols-3">

                    <div class="border-r border-slate-100 px-4 py-3 sm:px-5">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                            Modules
                        </p>

                        <p class="mt-0.5 text-sm font-bold text-slate-700">
                            <?= count($modules) ?>
                        </p>
                    </div>

                    <?php
                    $totalLessons = 0;
                    $totalFiles = 0;

                    foreach ($modules as $module) {
                        $lessons = $module['lessons'] ?? [];
                        $totalLessons += count($lessons);

                        foreach ($lessons as $lesson) {
                            $totalFiles += count($lesson['files'] ?? []);
                        }
                    }
                    ?>

                    <div class="border-r border-slate-100 px-4 py-3 sm:px-5">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                            Lessons
                        </p>

                        <p class="mt-0.5 text-sm font-bold text-slate-700">
                            <?= $totalLessons ?>
                        </p>
                    </div>

                    <div class="hidden px-4 py-3 sm:block sm:px-5">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                            Learning Files
                        </p>

                        <p class="mt-0.5 text-sm font-bold text-slate-700">
                            <?= $totalFiles ?>
                        </p>
                    </div>

                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                <div
                    class="flex flex-col gap-3 border-b border-slate-200 bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">

                    <div>
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50">
                                <i class="fa-solid fa-layer-group text-xs text-blue-600"></i>
                            </div>

                            <h3 class="text-sm font-semibold text-slate-800">
                                Course Content
                            </h3>

                            <span
                                class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">
                                <?= count($modules) ?> modules
                            </span>
                        </div>

                        <p class="mt-1 pl-10 text-xs text-slate-400">
                            Expand a module to manage its lessons and learning files.
                        </p>
                    </div>

                </div>

                <?php if (empty($modules)): ?>

                    <div class="px-5 py-16 text-center">

                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                            <i class="fa-solid fa-layer-group text-xl text-slate-400"></i>
                        </div>

                        <h3 class="mt-4 text-sm font-semibold text-slate-700">
                            No modules yet
                        </h3>

                        <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-slate-500">
                            Add your first module to start building the lessons and learning materials for this course.
                        </p>

                        <button type="button"
                            class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                            data-bs-toggle="modal" data-bs-target="#addModuleModal">
                            <i class="fa-solid fa-plus text-xs"></i>
                            Add First Module
                        </button>

                    </div>

                <?php else: ?>

                    <div class="divide-y divide-slate-100">

                        <?php foreach ($modules as $module): ?>

                            <?php
                            $moduleId = (int) $module['id'];
                            $lessons = $module['lessons'] ?? [];
                            $moduleContentId = 'module-content-' . $moduleId;
                            ?>

                            <div class="module-item">

                                <div class="flex items-center gap-3 px-4 py-4 sm:px-5">

                                    <button type="button"
                                        class="module-toggle inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600"
                                        data-target="<?= $moduleContentId ?>" aria-expanded="false" title="Expand module">

                                        <i
                                            class="fa-solid fa-chevron-right module-chevron text-xs transition-transform duration-200"></i>

                                    </button>

                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100">
                                        <i class="fa-solid fa-layer-group text-sm text-blue-600"></i>
                                    </div>

                                    <div class="min-w-0 flex-1">

                                        <div class="flex flex-wrap items-center gap-2">

                                            <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">
                                                Module <?= (int) $module['order_index'] ?>
                                            </span>

                                            <span class="h-1 w-1 rounded-full bg-slate-300"></span>

                                            <span
                                                class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">
                                                <?= count($lessons) ?>
                                                <?= count($lessons) === 1 ? 'Lesson' : 'Lessons' ?>
                                            </span>

                                        </div>

                                        <h4 class="mt-1 truncate text-sm font-semibold text-slate-800">
                                            <?= htmlspecialchars($module['title']) ?>
                                        </h4>

                                        <?php if (!empty($module['description'])): ?>
                                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                                <?= htmlspecialchars($module['description']) ?>
                                            </p>
                                        <?php endif; ?>

                                    </div>

                                    <div class="flex shrink-0 items-center gap-1">
                                        <button type="button"
                                            class="btn btn-primary ml-5 hidden h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold text-slate-500 transition hover:bg-blue-50 hover:text-blue-600 sm:inline-flex"
                                            title="Add Lesson" data-bs-toggle="modal" data-bs-target="#addLessonModal"
                                            data-module-id="<?= (int) $module['id'] ?>"
                                            data-module-title="<?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fa-solid fa-plus text-[10px]"></i>
                                            Lesson
                                        </button>

                                        <button type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 sm:hidden"
                                            title="Add Lesson" data-bs-toggle="modal" data-bs-target="#addLessonModal"
                                            data-module-id="<?= (int) $module['id'] ?>"
                                            data-module-title="<?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fa-solid fa-plus text-xs"></i>
                                        </button>

                                        <button type="button"
                                            class="module-toggle inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                            data-target="<?= $moduleContentId ?>" aria-expanded="false" title="Expand module">
                                            <i
                                                class="fa-solid fa-chevron-down module-chevron text-[10px] transition-transform duration-200"></i>
                                        </button>
                                        <form action="index.php?url=delete-course-module" method="POST"
                                            onsubmit="return confirm('Delete this module? All lessons and learning files inside it will also be deleted.');">

                                            <input type="hidden" name="module_id" value="<?= (int) $module['id'] ?>">

                                            <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">

                                            <button type="submit"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600 mr-5"
                                                title="Delete Module">

                                                <i class="fa-solid fa-trash text-[20px] btn btn-danger"></i>

                                            </button>

                                        </form>
                                    </div>

                                </div>

                                <div id="<?= $moduleContentId ?>" class="hidden border-t border-slate-100 bg-slate-50/70">

                                    <div class="px-4 py-4 sm:px-6">

                                        <?php if (empty($lessons)): ?>

                                            <div
                                                class="rounded-lg border border-dashed border-slate-200 bg-white px-4 py-8 text-center">

                                                <div
                                                    class="mx-auto flex h-10 w-10 items-center justify-center rounded-lg bg-slate-50">
                                                    <i class="fa-solid fa-book-open text-sm text-slate-300"></i>
                                                </div>

                                                <p class="mt-2 text-xs font-medium text-slate-500">
                                                    This module has no lessons
                                                </p>

                                                <p class="mt-1 text-[11px] text-slate-400">
                                                    Add a lesson to start adding learning content.
                                                </p>

                                                <?php require __DIR__ . '/add-lesson-btn.php'; ?>

                                            </div>

                                        <?php else: ?>

                                            <div class="relative ml-2 border-l-2 border-slate-200 pl-4 sm:ml-3 sm:pl-5">

                                                <div class="mb-3 flex items-center justify-between">

                                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                                        Lessons
                                                    </p>

                                                    <?php require __DIR__ . '/add-lesson-btn.php'; ?>

                                                </div>

                                                <div class="space-y-3">

                                                    <?php foreach ($lessons as $lesson): ?>

                                                        <?php
                                                        $lessonId = (int) $lesson['id'];
                                                        $files = $lesson['files'] ?? [];
                                                        $lessonContentId = 'lesson-content-' . $lessonId;

                                                        $contentType = strtolower($lesson['content_type'] ?? 'text');

                                                        $contentIcon = match ($contentType) {
                                                            'video' => 'fa-video',
                                                            'file' => 'fa-file',
                                                            'mixed' => 'fa-layer-group',
                                                            default => 'fa-align-left'
                                                        };
                                                        ?>

                                                        <div
                                                            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                                                            <div class="flex items-center gap-3 px-3 py-3 sm:px-4 border-blue-100">

                                                                <div
                                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50">
                                                                    <i
                                                                        class="fa-solid <?= $contentIcon ?> text-xs text-emerald-600"></i>
                                                                </div>

                                                                <div class="min-w-0 flex-1">

                                                                    <div class="flex flex-wrap items-center gap-2">

                                                                        <h5 class="truncate text-sm font-semibold text-slate-700">
                                                                            <?= htmlspecialchars($lesson['title']) ?>
                                                                        </h5>

                                                                        <span
                                                                            class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-slate-500">
                                                                            <?= htmlspecialchars($contentType) ?>
                                                                        </span>

                                                                    </div>

                                                                    <div
                                                                        class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] text-slate-400">

                                                                        <span>
                                                                            <i class="fa-regular fa-file mr-1"></i>
                                                                            <?= count($files) ?>
                                                                            <?= count($files) === 1 ? 'file' : 'files' ?>
                                                                        </span>

                                                                        <?php if (!empty($lesson['video_url'])): ?>
                                                                            <span>
                                                                                <i class="fa-solid fa-video mr-1"></i>
                                                                                Video included
                                                                            </span>
                                                                        <?php endif; ?>

                                                                    </div>

                                                                </div>

                                                                <div class="flex shrink-0 items-center gap-1">

                                                                    <!-- Desktop Add File -->
                                                                    <button type="button"
                                                                        class="hidden h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold text-slate-500 transition hover:bg-blue-50 hover:text-blue-600 sm:inline-flex"
                                                                        title="Add File" data-bs-toggle="modal"
                                                                        data-bs-target="#addFileModal"
                                                                        data-lesson-id="<?= (int) $lesson['id'] ?>"
                                                                        data-lesson-title="<?= htmlspecialchars($lesson['title'], ENT_QUOTES, 'UTF-8') ?>">

                                                                        <i class="fa-solid fa-paperclip text-[10px]"></i>
                                                                        File

                                                                    </button>

                                                                    <!-- Mobile Add File -->
                                                                    <button type="button"
                                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-blue-50 hover:text-blue-600 sm:hidden"
                                                                        title="Add File" data-bs-toggle="modal"
                                                                        data-bs-target="#addFileModal"
                                                                        data-lesson-id="<?= (int) $lesson['id'] ?>"
                                                                        data-lesson-title="<?= htmlspecialchars($lesson['title'], ENT_QUOTES, 'UTF-8') ?>">

                                                                        <i class="fa-solid fa-paperclip text-xs"></i>

                                                                    </button>

                                                                    <!-- Expand Lesson -->
                                                                    <button type="button"
                                                                        class="lesson-toggle inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                                                        data-target="<?= $lessonContentId ?>" aria-expanded="false"
                                                                        title="View lesson content">

                                                                        <i
                                                                            class="fa-solid fa-chevron-down lesson-chevron text-[10px] transition-transform duration-200"></i>

                                                                    </button>

                                                                </div>
                                                                <form action="index.php?url=delete-course-lesson" method="POST"
                                                                    onsubmit="return confirm('Delete this lesson? All files attached to this lesson will also be deleted.');">

                                                                    <input type="hidden" name="lesson_id"
                                                                        value="<?= (int) $lesson['id'] ?>">

                                                                    <input type="hidden" name="course_id"
                                                                        value="<?= (int) $course['id'] ?>">

                                                                    <button type="submit"
                                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                                        title="Delete Lesson">

                                                                        <i class="fa-solid fa-trash text-[15px] btn btn-danger"></i>

                                                                    </button>

                                                                </form>
                                                            </div>

                                                            <div id="<?= $lessonContentId ?>"
                                                                class="hidden border-t border-slate-200 bg-slate-50">

                                                                <div class="space-y-4 px-3 py-4 sm:px-5 sm:py-5">

                                                                    <!-- Lesson Content -->
                                                                    <?php if (!empty($lesson['content_body'])): ?>

                                                                        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

                                                                            <div
                                                                                class="flex items-center gap-2.5 border-b border-slate-100 px-4 py-3">

                                                                                <div
                                                                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100">
                                                                                    <i
                                                                                        class="fa-solid fa-align-left text-xs text-slate-500"></i>
                                                                                </div>

                                                                                <div class="min-w-0">
                                                                                    <p class="text-xs font-semibold text-slate-700">
                                                                                        Lesson Content
                                                                                    </p>

                                                                                    <p class="text-[10px] text-slate-400">
                                                                                        Learning instructions and information
                                                                                    </p>
                                                                                </div>

                                                                            </div>

                                                                            <div class="px-4 py-3.5">
                                                                                <p
                                                                                    class="whitespace-pre-line text-xs leading-6 text-slate-600">
                                                                                    <?= htmlspecialchars($lesson['content_body']) ?>
                                                                                </p>
                                                                            </div>

                                                                        </div>

                                                                    <?php endif; ?>


                                                                    <!-- Video -->
                                                                    <?php if (!empty($lesson['video_url'])): ?>

                                                                        <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-3.5">

                                                                            <div class="flex items-center gap-3">

                                                                                <div
                                                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm">
                                                                                    <i class="fa-solid fa-play text-xs text-blue-600"></i>
                                                                                </div>

                                                                                <div class="min-w-0 flex-1">

                                                                                    <p
                                                                                        class="text-[10px] font-bold uppercase tracking-wider text-blue-500">
                                                                                        Video Lesson
                                                                                    </p>

                                                                                    <a href="<?= htmlspecialchars($lesson['video_url']) ?>"
                                                                                        target="_blank" rel="noopener noreferrer"
                                                                                        class="mt-0.5 block truncate text-xs font-semibold text-blue-700 hover:text-blue-800 hover:underline">
                                                                                        <?= htmlspecialchars($lesson['video_url']) ?>
                                                                                    </a>

                                                                                </div>

                                                                                <a href="<?= htmlspecialchars($lesson['video_url']) ?>"
                                                                                    target="_blank" rel="noopener noreferrer"
                                                                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-blue-500 shadow-sm transition hover:bg-blue-100 hover:text-blue-700"
                                                                                    title="Open Video">

                                                                                    <i
                                                                                        class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>

                                                                                </a>

                                                                            </div>

                                                                        </div>

                                                                    <?php endif; ?>


                                                                    <!-- Learning Files -->
                                                                    <div>

                                                                        <div class="mb-2.5 flex items-center justify-between gap-3">

                                                                            <div class="flex min-w-0 items-center gap-2.5">

                                                                                <div
                                                                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-200">
                                                                                    <i
                                                                                        class="fa-solid fa-paperclip text-xs text-slate-500"></i>
                                                                                </div>

                                                                                <div class="min-w-0">

                                                                                    <div class="flex items-center gap-2">

                                                                                        <h6 class="text-xs font-bold text-slate-700">
                                                                                            Learning Files
                                                                                        </h6>

                                                                                        <span
                                                                                            class="rounded-full bg-slate-200 px-1.5 py-0.5 text-[9px] font-bold text-slate-500">
                                                                                            <?= count($files) ?>
                                                                                        </span>

                                                                                    </div>

                                                                                    <p
                                                                                        class="hidden text-[10px] text-slate-400 sm:block">
                                                                                        Documents and materials for this lesson
                                                                                    </p>

                                                                                </div>

                                                                            </div>


                                                                            <!-- Add File -->
                                                                            <button type="button" data-bs-toggle="modal"
                                                                                data-bs-target="#addFileModal"
                                                                                data-lesson-id="<?= (int) $lesson['id'] ?>"
                                                                                data-lesson-title="<?= htmlspecialchars($lesson['title'], ENT_QUOTES, 'UTF-8') ?>"
                                                                                class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-lg bg-blue-600 px-2.5 text-[10px] font-semibold text-white shadow-sm transition hover:bg-blue-700">

                                                                                <i class="fa-solid fa-plus text-[9px]"></i>

                                                                                <span>Add File</span>

                                                                            </button>

                                                                        </div>


                                                                        <?php if (empty($files)): ?>

                                                                            <!-- Empty Files -->
                                                                            <div
                                                                                class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center">

                                                                                <div
                                                                                    class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100">
                                                                                    <i
                                                                                        class="fa-regular fa-folder-open text-sm text-slate-400"></i>
                                                                                </div>

                                                                                <p class="mt-2.5 text-xs font-semibold text-slate-600">
                                                                                    No learning files yet
                                                                                </p>

                                                                                <p
                                                                                    class="mx-auto mt-1 max-w-xs text-[10px] leading-relaxed text-slate-400">
                                                                                    Attach PDFs, documents, presentations, spreadsheets, or
                                                                                    other learning materials.
                                                                                </p>

                                                                                <button type="button" data-bs-toggle="modal"
                                                                                    data-bs-target="#addFileModal"
                                                                                    data-lesson-id="<?= (int) $lesson['id'] ?>"
                                                                                    data-lesson-title="<?= htmlspecialchars($lesson['title'], ENT_QUOTES, 'UTF-8') ?>"
                                                                                    class="mt-3 inline-flex h-8 items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 text-[10px] font-semibold text-blue-600 transition hover:bg-blue-100">

                                                                                    <i class="fa-solid fa-paperclip text-[9px]"></i>
                                                                                    Attach File

                                                                                </button>

                                                                            </div>


                                                                        <?php else: ?>

                                                                            <!-- File List -->
                                                                            <div class="space-y-2">

                                                                                <?php foreach ($files as $file): ?>

                                                                                    <?php
                                                                                    $fileUrl = 'public/' . ltrim($file['file_path'], '/');

                                                                                    $extension = strtolower(
                                                                                        pathinfo($file['file_path'], PATHINFO_EXTENSION)
                                                                                    );

                                                                                    $fileIcon = 'fa-file';

                                                                                    if ($extension === 'pdf') {
                                                                                        $fileIcon = 'fa-file-pdf';
                                                                                    } elseif (in_array($extension, ['doc', 'docx'])) {
                                                                                        $fileIcon = 'fa-file-word';
                                                                                    } elseif (in_array($extension, ['xls', 'xlsx', 'csv'])) {
                                                                                        $fileIcon = 'fa-file-excel';
                                                                                    } elseif (in_array($extension, ['ppt', 'pptx'])) {
                                                                                        $fileIcon = 'fa-file-powerpoint';
                                                                                    } elseif (in_array($extension, ['zip', 'rar', '7z'])) {
                                                                                        $fileIcon = 'fa-file-zipper';
                                                                                    } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                                                                        $fileIcon = 'fa-file-image';
                                                                                    }
                                                                                    ?>

                                                                                    <div
                                                                                        class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 shadow-sm transition hover:border-blue-200 hover:shadow">

                                                                                        <!-- File Icon -->
                                                                                        <div
                                                                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 transition group-hover:bg-blue-50">

                                                                                            <i
                                                                                                class="fa-solid <?= $fileIcon ?> text-sm text-slate-500 group-hover:text-blue-600"></i>

                                                                                        </div>


                                                                                        <!-- File Information -->
                                                                                        <div class="min-w-0 flex-1">

                                                                                            <p
                                                                                                class="truncate text-xs font-semibold text-slate-700">
                                                                                                <?= htmlspecialchars($file['title']) ?>
                                                                                            </p>

                                                                                            <div class="mt-1 flex items-center gap-2">

                                                                                                <?php if ($extension): ?>

                                                                                                    <span
                                                                                                        class="rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-bold uppercase text-slate-500">
                                                                                                        <?= htmlspecialchars($extension) ?>
                                                                                                    </span>

                                                                                                <?php endif; ?>

                                                                                                <p class="truncate text-[10px] text-slate-400">
                                                                                                    <?= htmlspecialchars(basename($file['file_path'])) ?>
                                                                                                </p>

                                                                                            </div>

                                                                                        </div>


                                                                                        <!-- Open File -->
                                                                                        <a href="<?= htmlspecialchars($fileUrl) ?>"
                                                                                            target="_blank" rel="noopener noreferrer"
                                                                                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-blue-50 hover:text-blue-600"
                                                                                            title="Open File">

                                                                                            <i
                                                                                                class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>

                                                                                        </a>

                                                                                        <form action="index.php?url=delete-course-file"
                                                                                            method="POST"
                                                                                            onsubmit="return confirm('Delete this learning file?');">

                                                                                            <input type="hidden" name="file_id"
                                                                                                value="<?= (int) $file['id'] ?>">

                                                                                            <input type="hidden" name="course_id"
                                                                                                value="<?= (int) $course['id'] ?>">

                                                                                            <button type="submit"
                                                                                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                                                                title="Delete File">

                                                                                                <i
                                                                                                    class="fa-solid fa-trash text-[10px] btn btn-danger"></i>

                                                                                            </button>

                                                                                        </form>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>

                                                    <?php endforeach; ?>

                                                </div>

                                            </div>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>
</div>

<?php require __DIR__ . '/create-module.php'; ?>
<?php require __DIR__ . '/create-lesson.php'; ?>
<?php require __DIR__ . '/create-file.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        function toggleElement(button, target, chevron) {

            const expanded = button.getAttribute('aria-expanded') === 'true';
            const newState = !expanded;

            target.classList.toggle('hidden', !newState);
            button.setAttribute('aria-expanded', newState);

            if (chevron) {
                chevron.classList.toggle('rotate-180', newState);
            }
        }

        document.querySelectorAll('.module-toggle').forEach(function (button) {

            button.addEventListener('click', function () {

                const targetId = this.dataset.target;
                const target = document.getElementById(targetId);

                if (!target) {
                    return;
                }

                const buttons = document.querySelectorAll(
                    '.module-toggle[data-target="' + targetId + '"]'
                );

                const expanded = target.classList.contains('hidden');
                const newState = expanded;

                target.classList.toggle('hidden', !newState);

                buttons.forEach(function (toggle) {

                    toggle.setAttribute('aria-expanded', newState);

                    const chevron = toggle.querySelector('.module-chevron');

                    if (chevron) {
                        chevron.classList.toggle('rotate-180', newState);
                    }

                });

            });

        });

        document.querySelectorAll('.lesson-toggle').forEach(function (button) {

            button.addEventListener('click', function () {

                const targetId = this.dataset.target;
                const target = document.getElementById(targetId);

                if (!target) {
                    return;
                }

                const expanded = this.getAttribute('aria-expanded') === 'true';
                const newState = !expanded;

                target.classList.toggle('hidden', !newState);
                this.setAttribute('aria-expanded', newState);

                const chevron = this.querySelector('.lesson-chevron');

                if (chevron) {
                    chevron.classList.toggle('rotate-180', newState);
                }

            });

        });

    });
    document.getElementById('addLessonModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        document.getElementById('lessonModuleId').value = button.dataset.moduleId;
        document.getElementById('lessonModuleTitle').textContent = button.dataset.moduleTitle;
    });
</script>

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