<div class="modal fade" id="addLessonModal" tabindex="-1" aria-labelledby="addLessonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content overflow-hidden rounded-2xl border-0 shadow-xl">

            <div class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50">
                        <i class="fa-solid fa-book-open text-emerald-600"></i>
                    </div>

                    <div class="min-w-0">
                        <h3 id="addLessonModalLabel" class="text-sm font-bold text-slate-800">
                            Add Lesson
                        </h3>

                        <p class="mt-0.5 text-xs text-slate-500">
                            Add a lesson to this module.
                        </p>
                    </div>
                </div>

                <button type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                    data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="index.php?url=create-course-lesson" method="POST">


                <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">

                <input type="hidden" name="module_id" id="lessonModuleId">

                <div class="space-y-4 px-5 py-5">

                    <div class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-2.5">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-blue-500">
                            Module
                        </p>

                        <p id="lessonModuleTitle" class="mt-0.5 truncate text-sm font-semibold text-blue-700">
                            —
                        </p>
                    </div>

                    <div>
                        <label for="lessonTitle" class="mb-1.5 block text-xs font-semibold text-slate-700">
                            Lesson Title
                        </label>

                        <input type="text" id="lessonTitle" name="title" required maxlength="255"
                            placeholder="e.g. Getting Started with Excel"
                            class="block h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div>
                        <label for="lessonContentType" class="mb-1.5 block text-xs font-semibold text-slate-700">
                            Content Type
                        </label>

                        <select id="lessonContentType" name="content_type" required
                            class="block h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            <option value="text">Text</option>
                            <option value="video">Video</option>
                            <option value="file">File</option>
                            <option value="mixed">Mixed</option>
                        </select>

                        <p class="mt-1.5 text-[11px] text-slate-400">
                            Choose the type of content this lesson will contain.
                        </p>
                    </div>

                    <div>
                        <label for="lessonContentBody" class="mb-1.5 block text-xs font-semibold text-slate-700">
                            Lesson Content
                            <span class="font-normal text-slate-400">(Optional)</span>
                        </label>

                        <textarea id="lessonContentBody" name="content_body" rows="4" maxlength="5000" required
                            placeholder="Enter the lesson content or learning instructions..."
                            class="block w-full resize-none rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></textarea>
                    </div>

                    <div id="videoUrlField">
                        <label for="lessonVideoUrl" class="mb-1.5 block text-xs font-semibold text-slate-700">
                            Video URL
                            <span class="font-normal text-slate-400">(Optional)</span>
                        </label>

                        <input type="url" id="lessonVideoUrl" name="video_url" maxlength="500"
                            placeholder="https://www.youtube.com/watch?v=..."
                            class="block h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>

                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4">

                    <button type="button" data-bs-dismiss="modal"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
                        Cancel
                    </button>

                    <button type="submit"
                        class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                        Create Lesson
                    </button>

                </div>

            </form>
        </div>
    </div>
</div>