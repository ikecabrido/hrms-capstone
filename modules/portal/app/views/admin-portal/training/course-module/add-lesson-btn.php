<button type="button"
    class="hidden h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold text-slate-500 transition hover:bg-blue-50 hover:text-blue-600 sm:inline-flex"
    title="Add Lesson"
    data-bs-toggle="modal"
    data-bs-target="#addLessonModal"
    data-module-id="<?= (int) $module['id'] ?>"
    data-module-title="<?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?>">

    <i class="fa-solid fa-plus text-[10px]"></i>
    Add Lesson
</button>