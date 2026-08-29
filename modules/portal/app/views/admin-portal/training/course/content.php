<div class="employee-dashboard">

    <section class="dashboard-welcome" id="courseManagementWelcome">

        <!-- Animated background -->
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                COURSE MANAGEMENT
            </span>

            <h1 class="welcome-title">
                Course Management
            </h1>

            <p class="welcome-description">
                Create and manage courses, organize modules and lessons,
                and prepare learning content for employees.
            </p>

            <div class="welcome-line"></div>

        </div>

        <!-- Course management icon -->
        <div class="welcome-decoration">
            <i class="fas fa-book-open"></i>
        </div>

    </section>

    <?php require __DIR__ . '/../../../partials/notification.php'; ?>

    <section class="w-full min-h-screen bg-slate-50 p-4 sm:p-6 lg:p-8">

        <div class="max-w-7xl mx-auto">

            <!-- Header -->
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                    <div>
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full
                                 bg-blue-50 text-blue-700 text-xs font-semibold">
                            <i class="fa-solid fa-layer-group"></i>
                            Course Content Management
                        </span>

                        <h1 class="mt-3 text-2xl sm:text-3xl font-bold text-slate-900">
                            Course Content
                        </h1>

                        <p class="mt-2 text-sm text-slate-500 max-w-2xl">
                            Manage modules, lessons, and learning materials
                            for your training courses.
                        </p>
                    </div>

                </div>
            </div>

            <!-- Course content -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">

                <div class="px-5 py-4 border-b border-slate-100
                        flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                    <div>
                        <h2 class="text-sm font-bold text-slate-800">
                            Microsoft Excel Fundamentals
                        </h2>

                        <p class="text-xs text-slate-400 mt-1">
                            <?= count($modules) ?>
                            <?= count($modules) === 1 ? 'Module' : 'Modules' ?>
                        </p>
                    </div>

                    <button type="button" data-bs-toggle="modal" data-bs-target="#addModuleModal" style="
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 38px;
            padding: 0 14px;
            border: 0;
            border-radius: 9px;
            background: #2563eb;
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            line-height: 1;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
            transition: background-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
        " onmouseover="this.style.background='#1d4ed8'; this.style.boxShadow='0 4px 10px rgba(37, 99, 235, 0.20)'"
                        onmouseout="this.style.background='#2563eb'; this.style.boxShadow='0 1px 2px rgba(0, 0, 0, 0.08)'"
                        onmousedown="this.style.transform='scale(0.98)'" onmouseup="this.style.transform='scale(1)'">

                        <i class="fa-solid fa-plus" style="font-size: 11px;"></i>

                        <span>Add Module</span>

                    </button>

                </div>

                <div class="overflow-x-auto">

                    <table class="w-full min-w-[900px]">

                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">

                                <th class="w-12 px-4 py-3"></th>

                                <th class="px-4 py-3 text-left text-[11px]
                                       uppercase tracking-wider font-bold text-slate-400">
                                    Module
                                </th>

                                <th class="px-4 py-3 text-left text-[11px]
                                       uppercase tracking-wider font-bold text-slate-400">
                                    Description
                                </th>

                                <th class="px-4 py-3 text-center text-[11px]
                                       uppercase tracking-wider font-bold text-slate-400">
                                    Lessons
                                </th>

                                <th class="px-4 py-3 text-center text-[11px]
                                       uppercase tracking-wider font-bold text-slate-400">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-right text-[11px]
                                       uppercase tracking-wider font-bold text-slate-400">
                                    Actions
                                </th>

                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            <?php if (!empty($modules)): ?>

                                <?php foreach ($modules as $module): ?>

                                    <?php
                                    $moduleId = (int) $module['id'];
                                    $lessons = $module['lessons'] ?? [];
                                    $lessonCount = count($lessons);
                                    $isActive = ($module['status'] ?? '') === 'active';
                                    ?>

                                    <!-- Module -->
                                    <tr class="module-row hover:bg-slate-50/70 transition" data-module-id="<?= $moduleId ?>">

                                        <td class="px-4 py-4">

                                            <?php if ($lessonCount > 0): ?>

                                                <button type="button" class="module-toggle w-8 h-8 rounded-lg
                                                       bg-slate-50 border border-slate-200
                                                       text-slate-500 inline-flex items-center
                                                       justify-center hover:bg-blue-50
                                                       hover:border-blue-200
                                                       hover:text-blue-600 transition">

                                                    <i class="fa-solid fa-chevron-right text-xs"></i>

                                                </button>

                                            <?php endif; ?>

                                        </td>

                                        <td class="px-4 py-4">

                                            <div class="flex items-center gap-3">

                                                <div class="w-10 h-10 rounded-xl bg-blue-50
                                                    text-blue-600 flex items-center
                                                    justify-center shrink-0">

                                                    <i class="fa-solid fa-layer-group"></i>

                                                </div>

                                                <div class="min-w-0">

                                                    <span class="text-[10px] font-bold uppercase text-blue-500">
                                                        Module <?= (int) $module['order_index'] ?>
                                                    </span>

                                                    <p class="text-sm font-bold text-slate-800 truncate">
                                                        <?= htmlspecialchars(
                                                            $module['title'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    </p>

                                                </div>

                                            </div>

                                        </td>

                                        <td class="px-4 py-4">

                                            <p class="max-w-md text-xs leading-5 text-slate-500">
                                                <?= htmlspecialchars(
                                                    $module['description'] ?? 'No description provided.',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </p>

                                        </td>

                                        <td class="px-4 py-4 text-center">

                                            <span class="inline-flex items-center justify-center
                                                 min-w-[32px] h-8 px-2 rounded-lg
                                                 bg-slate-100 text-slate-700
                                                 text-xs font-bold">
                                                <?= $lessonCount ?>
                                            </span>

                                        </td>

                                        <td class="px-4 py-4 text-center">

                                            <?php if ($isActive): ?>

                                                <span class="inline-flex items-center gap-1.5
                                                     px-2.5 py-1 rounded-full
                                                     bg-emerald-50 text-emerald-700
                                                     text-[11px] font-semibold">

                                                    <i class="fa-solid fa-circle text-[6px]"></i>
                                                    Active

                                                </span>

                                            <?php else: ?>

                                                <span class="inline-flex items-center gap-1.5
                                                     px-2.5 py-1 rounded-full
                                                     bg-slate-100 text-slate-500
                                                     text-[11px] font-semibold">

                                                    <i class="fa-solid fa-circle text-[6px]"></i>
                                                    Archived

                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td class="px-4 py-4">

                                            <div class="flex items-center justify-end gap-1">

                                                <button type="button" title="Edit Module" class="w-8 h-8 rounded-lg text-slate-400
                                                       hover:bg-blue-50 hover:text-blue-600
                                                       transition">

                                                    <i class="fa-solid fa-pen text-xs"></i>

                                                </button>

                                                <button type="button" title="Delete Module" class="w-8 h-8 rounded-lg text-slate-400
                                                       hover:bg-red-50 hover:text-red-500
                                                       transition">

                                                    <i class="fa-solid fa-trash text-xs"></i>

                                                </button>

                                            </div>

                                        </td>

                                    </tr>

                                    <?php if ($lessonCount > 0): ?>

                                        <tr class="lesson-container hidden" data-module-lessons="<?= $moduleId ?>">

                                            <td colspan="6" class="p-0 bg-slate-50/70">

                                                <div class="px-6 sm:px-12 py-4">

                                                    <div class="bg-white border border-slate-200
                                                        rounded-xl overflow-hidden">

                                                        <div class="px-4 py-3 bg-slate-50
                                                            border-b border-slate-200
                                                            flex items-center
                                                            justify-between gap-3">

                                                            <div>
                                                                <p class="text-xs font-bold text-slate-700">
                                                                    Lessons
                                                                </p>

                                                                <p class="text-[10px] text-slate-400 mt-0.5">
                                                                    Learning materials inside this module
                                                                </p>
                                                            </div>

                                                            <button type="button" class="text-xs font-semibold text-blue-600
                                                                   hover:text-blue-700">

                                                                <i class="fa-solid fa-plus mr-1"></i>
                                                                Add Lesson

                                                            </button>

                                                        </div>

                                                        <div class="divide-y divide-slate-100">

                                                            <?php foreach ($lessons as $index => $lesson): ?>

                                                                <?php
                                                                $lessonFiles = $lesson['files'] ?? [];
                                                                $contentType = $lesson['content_type'] ?? 'text';
                                                                ?>

                                                                <div class="px-4 py-3 flex flex-col sm:flex-row
                                                                    sm:items-center gap-3
                                                                    hover:bg-slate-50 transition">

                                                                    <div class="w-8 h-8 rounded-lg bg-slate-100
                                                                        flex items-center justify-center
                                                                        shrink-0">

                                                                        <span class="text-[10px] font-bold text-slate-500">
                                                                            <?= str_pad(
                                                                                $index + 1,
                                                                                2,
                                                                                '0',
                                                                                STR_PAD_LEFT
                                                                            ) ?>
                                                                        </span>

                                                                    </div>

                                                                    <div class="flex-1 min-w-0">

                                                                        <div class="flex flex-wrap items-center gap-2">

                                                                            <p class="text-sm font-semibold text-slate-700">
                                                                                <?= htmlspecialchars(
                                                                                    $lesson['title'],
                                                                                    ENT_QUOTES,
                                                                                    'UTF-8'
                                                                                ) ?>
                                                                            </p>

                                                                            <span class="px-2 py-0.5 rounded-md
                                                                                 bg-blue-50 text-blue-600
                                                                                 text-[9px] font-bold uppercase">
                                                                                <?= htmlspecialchars(
                                                                                    $contentType,
                                                                                    ENT_QUOTES,
                                                                                    'UTF-8'
                                                                                ) ?>
                                                                            </span>

                                                                        </div>

                                                                        <?php if (!empty($lesson['content_body'])): ?>

                                                                            <p class="mt-1 text-xs text-slate-400 line-clamp-1">
                                                                                <?= htmlspecialchars(
                                                                                    $lesson['content_body'],
                                                                                    ENT_QUOTES,
                                                                                    'UTF-8'
                                                                                ) ?>
                                                                            </p>

                                                                        <?php endif; ?>

                                                                        <?php if (!empty($lesson['video_url'])): ?>

                                                                            <div class="mt-1 flex items-center gap-1.5
                                                                                text-[10px] text-red-500">

                                                                                <i class="fa-brands fa-youtube"></i>
                                                                                Video available

                                                                            </div>

                                                                        <?php endif; ?>

                                                                        <?php if (!empty($lessonFiles)): ?>

                                                                            <div class="mt-2 flex flex-wrap gap-1.5">

                                                                                <?php foreach ($lessonFiles as $file): ?>

                                                                                    <span class="inline-flex items-center gap-1.5
                                                                                         px-2 py-1 rounded-md
                                                                                         bg-emerald-50 text-emerald-700
                                                                                         text-[10px] font-medium">

                                                                                        <i class="fa-solid fa-paperclip"></i>

                                                                                        <?= htmlspecialchars(
                                                                                            $file['title'],
                                                                                            ENT_QUOTES,
                                                                                            'UTF-8'
                                                                                        ) ?>

                                                                                    </span>

                                                                                <?php endforeach; ?>

                                                                            </div>

                                                                        <?php endif; ?>

                                                                    </div>

                                                                    <div class="shrink-0">

                                                                        <?php if (($lesson['status'] ?? '') === 'active'): ?>

                                                                            <span class="inline-flex items-center gap-1
                                                                                 text-[10px] font-semibold
                                                                                 text-emerald-600">

                                                                                <i class="fa-solid fa-circle text-[5px]"></i>
                                                                                Active

                                                                            </span>

                                                                        <?php else: ?>

                                                                            <span class="inline-flex items-center gap-1
                                                                                 text-[10px] font-semibold
                                                                                 text-slate-400">

                                                                                <i class="fa-solid fa-circle text-[5px]"></i>
                                                                                Archived

                                                                            </span>

                                                                        <?php endif; ?>

                                                                    </div>

                                                                    <div class="flex items-center gap-1 shrink-0">

                                                                        <button type="button" title="Edit Lesson" class="w-8 h-8 rounded-lg
                                                                               text-slate-400
                                                                               hover:bg-blue-50
                                                                               hover:text-blue-600
                                                                               transition">

                                                                            <i class="fa-solid fa-pen text-xs"></i>

                                                                        </button>

                                                                        <button type="button" title="Delete Lesson" class="w-8 h-8 rounded-lg
                                                                               text-slate-400
                                                                               hover:bg-red-50
                                                                               hover:text-red-500
                                                                               transition">

                                                                            <i class="fa-solid fa-trash text-xs"></i>

                                                                        </button>

                                                                    </div>

                                                                </div>

                                                            <?php endforeach; ?>

                                                        </div>

                                                    </div>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endif; ?>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="6" class="px-6 py-16 text-center">

                                        <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100
                                            flex items-center justify-center">

                                            <i class="fa-solid fa-layer-group text-slate-400 text-lg"></i>

                                        </div>

                                        <h3 class="mt-4 text-sm font-bold text-slate-700">
                                            No modules found
                                        </h3>

                                        <p class="mt-1 text-xs text-slate-400">
                                            Start building this course by adding a module.
                                        </p>

                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </section>
</div>
<?php require __DIR__ . '/add.php'; ?>
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
<script>
    document.addEventListener('DOMContentLoaded', function () {

        document.querySelectorAll('.module-toggle').forEach(function (button) {

            button.addEventListener('click', function () {

                const moduleRow = button.closest('.module-row');

                if (!moduleRow) return;

                const moduleId = moduleRow.dataset.moduleId;

                const lessonRow = document.querySelector(
                    `.lesson-container[data-module-lessons="${moduleId}"]`
                );

                if (!lessonRow) return;

                lessonRow.classList.toggle('hidden');

                const icon = button.querySelector('i');

                if (!icon) return;

                if (lessonRow.classList.contains('hidden')) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-right');
                } else {
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-down');
                }

            });

        });

    });
</script>