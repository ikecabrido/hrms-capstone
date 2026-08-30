<div class="modal fade" id="addModuleModal" tabindex="-1" aria-labelledby="addModuleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content overflow-hidden rounded-2xl border-0 shadow-xl">

            <div class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100">
                        <i class="fa-solid fa-layer-group text-blue-600"></i>
                    </div>

                    <div>
                        <h5 id="addModuleModalLabel" class="text-base font-bold text-slate-800">
                            Add Module
                        </h5>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Add a new module to this course.
                        </p>
                    </div>
                </div>

                <button type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="index.php?url=create-course-module" method="POST">

                <input type="hidden"
                    name="course_id"
                    value="<?= (int) $course['id'] ?>">

                <div class="space-y-4 px-5 py-5">

                    <div>
                        <label for="moduleTitle"
                            class="mb-1.5 block text-xs font-semibold text-slate-700">
                            Module Title
                        </label>

                        <input type="text"
                            id="moduleTitle"
                            name="title"
                            required
                            maxlength="255"
                            placeholder="e.g. Introduction to Excel"
                            class="block h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div>
                        <label for="moduleDescription"
                            class="mb-1.5 block text-xs font-semibold text-slate-700">
                            Description
                            <span class="font-normal text-slate-400">(Optional)</span>
                        </label>

                        <textarea id="moduleDescription"
                            name="description"
                            rows="4"
                            maxlength="1000"
                            placeholder="Describe what employees will learn in this module..."
                            class="block w-full resize-none rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></textarea>
                    </div>

                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4">

                    <button type="button"
                        data-bs-dismiss="modal"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
                        Cancel
                    </button>

                    <button type="submit"
                        class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                        Create Module
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>