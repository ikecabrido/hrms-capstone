<div class="modal fade" id="addFileModal" tabindex="-1" aria-labelledby="addFileModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content overflow-hidden rounded-2xl border-0 shadow-xl">

            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4">

                <div class="flex items-center gap-3">

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
                        <i class="fa-solid fa-paperclip text-blue-600"></i>
                    </div>

                    <div class="min-w-0">

                        <h3 id="addFileModalLabel" class="text-sm font-bold text-slate-800">
                            Add File
                        </h3>

                        <p class="mt-0.5 text-xs text-slate-500">
                            Attach a learning file to this lesson.
                        </p>

                    </div>

                </div>

                <button type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                    data-bs-dismiss="modal" aria-label="Close">

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>

            <!-- Form -->
            <form action="index.php?url=create-course-file" method="POST" enctype="multipart/form-data">

                <input type="hidden" name="lesson_id" id="fileLessonId">

                <div class="space-y-4 px-5 py-5">

                    <!-- Lesson -->
                    <div class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-2.5">

                        <p class="text-[10px] font-semibold uppercase tracking-wider text-blue-500">
                            Lesson
                        </p>

                        <p id="fileLessonTitle" class="mt-0.5 truncate text-sm font-semibold text-blue-700">
                            —
                        </p>

                    </div>

                    <!-- File Title -->
                    <div>

                        <label for="fileTitle" class="mb-1.5 block text-xs font-semibold text-slate-700">

                            File Title

                        </label>

                        <input type="text" id="fileTitle" name="title" required maxlength="255"
                            placeholder="e.g. Excel Introduction Guide"
                            class="block h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">

                    </div>

                    <!-- File -->
                    <div>

                        <label for="lessonFile" class="mb-1.5 block text-xs font-semibold text-slate-700">

                            Select File

                        </label>

                        <input type="file" id="lessonFile" name="file" required
                            class="block w-full cursor-pointer rounded-lg border border-slate-200 bg-white text-xs text-slate-600 file:mr-3 file:h-10 file:border-0 file:border-r file:border-slate-200 file:bg-slate-50 file:px-3 file:text-xs file:font-semibold file:text-slate-600 hover:file:bg-slate-100 focus:border-blue-500 focus:outline-none">

                        <p class="mt-1.5 text-[11px] text-slate-400">
                            Upload a document, presentation, spreadsheet, PDF, or other learning material.
                        </p>

                    </div>

                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4">

                    <button type="button" data-bs-dismiss="modal"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">

                        Cancel

                    </button>

                    <button type="submit"
                        class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700">

                        <i class="fa-solid fa-upload text-[10px]"></i>

                        Upload File

                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const addFileModal = document.getElementById('addFileModal');

        if (!addFileModal) return;

        addFileModal.addEventListener('show.bs.modal', function (event) {

            const button = event.relatedTarget;

            if (!button) return;

            const lessonId = button.getAttribute('data-lesson-id');
            const lessonTitle = button.getAttribute('data-lesson-title');

            document.getElementById('fileLessonId').value = lessonId || '';
            document.getElementById('fileLessonTitle').textContent =
                lessonTitle || '—';

        });

    });
</script>