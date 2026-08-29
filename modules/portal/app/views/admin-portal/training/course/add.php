<button
    type="button"
    class="inline-flex items-center justify-center gap-2
           h-9 px-3.5 rounded-lg bg-blue-600 text-white
           text-xs font-semibold hover:bg-blue-700 transition"
    data-bs-toggle="modal"
    data-bs-target="#addModuleModal">
    <i class="fa-solid fa-plus"></i>
    Add Module
</button>

<div class="modal fade" id="addModuleModal" tabindex="-1"
     aria-labelledby="addModuleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">

            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold" id="addModuleModalLabel">
                        Add Module
                    </h5>
                    <p class="text-muted small mb-0 mt-1">
                        Create a new module for this course.
                    </p>
                </div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>

            <form method="POST" action="">
                <div class="modal-body">

                    <div class="mb-3">
                        <label for="moduleTitle"
                               class="form-label fw-semibold">
                            Module Title
                        </label>

                        <input type="text"
                               class="form-control"
                               id="moduleTitle"
                               name="title"
                               placeholder="e.g. Introduction to Excel"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="moduleDescription"
                               class="form-label fw-semibold">
                            Description
                        </label>

                        <textarea
                            class="form-control"
                            id="moduleDescription"
                            name="description"
                            rows="4"
                            placeholder="Describe what employees will learn in this module..."></textarea>
                    </div>

                </div>

                <div class="modal-footer border-top">
                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                            class="btn btn-primary">
                        <i class="fa-solid fa-plus me-1"></i>
                        Add Module
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>