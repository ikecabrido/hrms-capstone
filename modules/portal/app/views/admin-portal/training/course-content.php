<div class="modal fade" id="manageContentModal" tabindex="-1" aria-labelledby="manageContentModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">

        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">


            <!-- ================================================= -->
            <!-- HEADER -->
            <!-- ================================================= -->

            <div class="px-5 py-4 border-bottom border-slate-100 bg-white">

                <div class="flex items-start justify-between gap-4">

                    <div class="min-w-0">

                        <div class="flex items-center gap-2 mb-2">

                            <div class="w-9 h-9 rounded-xl
                                        bg-blue-50
                                        flex items-center justify-center">

                                <i class="fa-solid fa-layer-group text-blue-600"></i>

                            </div>

                            <span class="text-[10px]
                                         uppercase
                                         tracking-wider
                                         font-bold
                                         text-slate-400">

                                Course Content

                            </span>

                        </div>


                        <h3 id="manageContentCourseTitle" class="text-xl font-bold text-slate-900">

                            Course Content

                        </h3>


                        <p class="mt-1 text-sm text-slate-500">

                            Manage modules, lessons, and learning materials
                            for this course.

                        </p>

                    </div>


                    <!-- CLOSE -->

                    <button type="button" data-bs-dismiss="modal" class="w-9 h-9 rounded-xl
                               flex items-center justify-center
                               text-slate-400
                               hover:bg-slate-100
                               hover:text-slate-600
                               transition">

                        <i class="fa-solid fa-xmark"></i>

                    </button>

                </div>

            </div>



            <!-- ================================================= -->
            <!-- BODY -->
            <!-- ================================================= -->

            <div class="p-5 bg-slate-50">


                <!-- TOP BAR -->

                <div class="flex items-center justify-between
                            gap-3 mb-4">

                    <div>

                        <h4 class="text-sm font-bold text-slate-800">

                            Course Content

                        </h4>

                        <p class="text-xs text-slate-400 mt-0.5">

                            Build the learning structure of this course.

                        </p>

                    </div>


                    <!-- ADD MODULE -->

                    <button type="button" id="addModuleButton" class="inline-flex items-center
                               gap-2
                               px-3.5
                               h-9
                               rounded-lg
                               bg-blue-600
                               text-white
                               text-xs
                               font-semibold
                               hover:bg-blue-700
                               transition">

                        <i class="fa-solid fa-plus"></i>

                        Add Module

                    </button>

                </div>



                <!-- ================================================= -->
                <!-- MODULE CONTAINER -->
                <!-- ================================================= -->

                <div id="courseModulesContainer" class="space-y-3">


                    <!-- ================================================= -->
                    <!-- MODULE -->
                    <!-- ================================================= -->

                    <div class="course-module
                                bg-white
                                border
                                border-slate-200
                                rounded-xl
                                overflow-hidden">


                        <!-- MODULE HEADER -->

                        <div class="flex items-center
                                    justify-between
                                    gap-3
                                    px-4
                                    py-3
                                    border-b
                                    border-slate-100">


                            <div class="flex items-center gap-3 min-w-0">


                                <!-- TOGGLE -->

                                <button type="button" class="module-toggle
                                           w-7
                                           h-7
                                           rounded-lg
                                           bg-slate-50
                                           text-slate-500
                                           flex
                                           items-center
                                           justify-center
                                           hover:bg-slate-100
                                           transition">

                                    <i class="fa-solid fa-chevron-down text-[10px]"></i>

                                </button>


                                <!-- MODULE INFO -->

                                <div class="min-w-0">

                                    <div class="flex items-center gap-2">

                                        <span class="module-number
                                                     text-[10px]
                                                     font-bold
                                                     text-blue-500
                                                     uppercase">

                                            Module 1

                                        </span>

                                        <span class="text-xs text-slate-300">

                                            —

                                        </span>

                                        <h5 class="module-title
                                                   text-sm
                                                   font-bold
                                                   text-slate-800
                                                   truncate">

                                            Introduction to Excel

                                        </h5>

                                    </div>


                                    <p class="module-description
                                              text-xs
                                              text-slate-400
                                              mt-0.5">

                                        Introduction to Excel and the workspace

                                    </p>

                                </div>

                            </div>



                            <!-- MODULE ACTIONS -->

                            <div class="flex items-center gap-1 shrink-0">


                                <!-- EDIT MODULE -->

                                <button type="button" class="edit-module
                                           w-8
                                           h-8
                                           rounded-lg
                                           text-slate-400
                                           hover:bg-blue-50
                                           hover:text-blue-600
                                           transition" title="Edit module">

                                    <i class="fa-solid fa-pen text-xs"></i>

                                </button>


                                <!-- DELETE MODULE -->

                                <button type="button" class="delete-module
                                           w-8
                                           h-8
                                           rounded-lg
                                           text-slate-400
                                           hover:bg-red-50
                                           hover:text-red-500
                                           transition" title="Delete module">

                                    <i class="fa-solid fa-trash text-xs"></i>

                                </button>

                            </div>

                        </div>



                        <!-- ================================================= -->
                        <!-- LESSONS -->
                        <!-- ================================================= -->

                        <div class="module-lessons px-4 py-3">


                            <!-- LESSON -->

                            <div class="lesson-item
                                        flex
                                        items-center
                                        gap-3
                                        py-2.5
                                        border-b
                                        border-slate-100">


                                <div class="w-7
                                            h-7
                                            rounded-lg
                                            bg-slate-50
                                            flex
                                            items-center
                                            justify-center
                                            shrink-0">

                                    <span class="lesson-number
                                                 text-[10px]
                                                 font-bold
                                                 text-slate-500">

                                        01

                                    </span>

                                </div>


                                <div class="flex-1 min-w-0">

                                    <p class="text-sm
                                              font-semibold
                                              text-slate-700
                                              truncate">

                                        Getting Started with Excel

                                    </p>

                                    <p class="text-[10px] text-slate-400">

                                        Lesson

                                    </p>

                                </div>


                                <div class="flex items-center gap-1">

                                    <button type="button" class="edit-lesson
                                               w-8
                                               h-8
                                               rounded-lg
                                               text-slate-400
                                               hover:bg-blue-50
                                               hover:text-blue-600">

                                        <i class="fa-solid fa-pen text-xs"></i>

                                    </button>


                                    <button type="button" class="delete-lesson
                                               w-8
                                               h-8
                                               rounded-lg
                                               text-slate-400
                                               hover:bg-red-50
                                               hover:text-red-500">

                                        <i class="fa-solid fa-trash text-xs"></i>

                                    </button>

                                </div>

                            </div>



                            <!-- LESSON 2 -->

                            <div class="lesson-item
                                        flex
                                        items-center
                                        gap-3
                                        py-2.5
                                        border-b
                                        border-slate-100">

                                <div class="w-7
                                            h-7
                                            rounded-lg
                                            bg-slate-50
                                            flex
                                            items-center
                                            justify-center
                                            shrink-0">

                                    <span class="lesson-number
                                                 text-[10px]
                                                 font-bold
                                                 text-slate-500">

                                        02

                                    </span>

                                </div>


                                <div class="flex-1 min-w-0">

                                    <p class="text-sm
                                              font-semibold
                                              text-slate-700
                                              truncate">

                                        Excel Interface

                                    </p>

                                    <p class="text-[10px] text-slate-400">

                                        Lesson

                                    </p>

                                </div>


                                <div class="flex items-center gap-1">

                                    <button type="button" class="edit-lesson
                                               w-8
                                               h-8
                                               rounded-lg
                                               text-slate-400
                                               hover:bg-blue-50
                                               hover:text-blue-600">

                                        <i class="fa-solid fa-pen text-xs"></i>

                                    </button>


                                    <button type="button" class="delete-lesson
                                               w-8
                                               h-8
                                               rounded-lg
                                               text-slate-400
                                               hover:bg-red-50
                                               hover:text-red-500">

                                        <i class="fa-solid fa-trash text-xs"></i>

                                    </button>

                                </div>

                            </div>



                            <!-- LESSON 3 -->

                            <div class="lesson-item
                                        flex
                                        items-center
                                        gap-3
                                        py-2.5">

                                <div class="w-7
                                            h-7
                                            rounded-lg
                                            bg-slate-50
                                            flex
                                            items-center
                                            justify-center
                                            shrink-0">

                                    <span class="lesson-number
                                                 text-[10px]
                                                 font-bold
                                                 text-slate-500">

                                        03

                                    </span>

                                </div>


                                <div class="flex-1 min-w-0">

                                    <p class="text-sm
                                              font-semibold
                                              text-slate-700
                                              truncate">

                                        Creating a Workbook

                                    </p>

                                    <p class="text-[10px] text-slate-400">

                                        Lesson

                                    </p>

                                </div>


                                <div class="flex items-center gap-1">

                                    <button type="button" class="edit-lesson
                                               w-8
                                               h-8
                                               rounded-lg
                                               text-slate-400
                                               hover:bg-blue-50
                                               hover:text-blue-600">

                                        <i class="fa-solid fa-pen text-xs"></i>

                                    </button>


                                    <button type="button" class="delete-lesson
                                               w-8
                                               h-8
                                               rounded-lg
                                               text-slate-400
                                               hover:bg-red-50
                                               hover:text-red-500">

                                        <i class="fa-solid fa-trash text-xs"></i>

                                    </button>

                                </div>

                            </div>



                            <!-- ADD LESSON -->

                            <div class="pt-3">

                                <button type="button" class="add-lesson
                                           inline-flex
                                           items-center
                                           gap-2
                                           text-xs
                                           font-semibold
                                           text-blue-600
                                           hover:text-blue-700">

                                    <i class="fa-solid fa-plus"></i>

                                    Add Lesson

                                </button>

                            </div>

                        </div>

                    </div>



                    <!-- ================================================= -->
                    <!-- EMPTY STATE -->
                    <!-- ================================================= -->

                    <div id="courseContentEmpty" class="hidden py-12 text-center">

                        <div class="w-12
                                    h-12
                                    mx-auto
                                    rounded-xl
                                    bg-slate-100
                                    flex
                                    items-center
                                    justify-center">

                            <i class="fa-solid
                                      fa-layer-group
                                      text-slate-400"></i>

                        </div>

                        <h4 class="mt-3
                                   text-sm
                                   font-bold
                                   text-slate-700">

                            No modules yet

                        </h4>

                        <p class="mt-1
                                  text-xs
                                  text-slate-400">

                            Start building your course by adding a module.

                        </p>

                    </div>

                </div>

            </div>



            <!-- ================================================= -->
            <!-- FOOTER -->
            <!-- ================================================= -->

            <div class="px-5
                        py-3
                        border-t
                        border-slate-100
                        bg-white
                        flex
                        items-center
                        justify-between
                        gap-3">

                <p class="text-[11px] text-slate-400">

                    Modules and lessons determine the employee's
                    learning experience.

                </p>


                <button type="button" data-bs-dismiss="modal" class="px-4
                           h-9
                           rounded-lg
                           border
                           border-slate-200
                           text-xs
                           font-semibold
                           text-slate-600
                           hover:bg-slate-50">

                    Done

                </button>

            </div>

        </div>

    </div>

</div>
 
<script>
    (function () {

        console.log('=================================');
        console.log('MANAGE COURSE CONTENT JS LOADED');
        console.log('=================================');

        function initManageContent() {

            const modal = document.getElementById('manageContentModal');

            if (!modal) {
                console.error('ERROR: #manageContentModal does not exist.');
                return;
            }

            console.log('Manage Content modal found.');

            const container = document.getElementById('courseModulesContainer');
            const addModuleButton = document.getElementById('addModuleButton');

            /*
            |--------------------------------------------------------------------------
            | OPEN MODAL
            |--------------------------------------------------------------------------
            */

            modal.addEventListener('show.bs.modal', function (event) {

                const button = event.relatedTarget;

                if (!button) {
                    console.warn('Modal opened without a trigger button.');
                    return;
                }

                const courseId = button.dataset.courseId || '';
                const courseTitle = button.dataset.courseTitle || 'Course Content';

                modal.dataset.courseId = courseId;

                const title = document.getElementById('manageContentCourseTitle');

                if (title) {
                    title.textContent = courseTitle;
                }

                console.log('Course ID:', courseId);
                console.log('Course Title:', courseTitle);
            });


            /*
            |--------------------------------------------------------------------------
            | MODULE COLLAPSE
            |--------------------------------------------------------------------------
            */

            modal.addEventListener('click', function (event) {

                const toggle = event.target.closest('.module-toggle');

                if (!toggle) {
                    return;
                }

                const module = toggle.closest('.course-module');

                if (!module) {
                    return;
                }

                const lessons = module.querySelector('.module-lessons');
                const icon = toggle.querySelector('i');

                if (!lessons) {
                    return;
                }

                lessons.classList.toggle('hidden');

                if (lessons.classList.contains('hidden')) {

                    if (icon) {
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-right');
                    }

                } else {

                    if (icon) {
                        icon.classList.remove('fa-chevron-right');
                        icon.classList.add('fa-chevron-down');
                    }

                }

            });


            /*
            |--------------------------------------------------------------------------
            | ADD MODULE
            |--------------------------------------------------------------------------
            */

            if (addModuleButton) {

                addModuleButton.addEventListener('click', function () {

                    console.log('ADD MODULE CLICKED');

                    const moduleCount =
                        container.querySelectorAll('.course-module').length + 1;

                    const module = document.createElement('div');

                    module.className =
                        'course-module bg-white border border-slate-200 rounded-xl overflow-hidden';

                    module.innerHTML = `
                    <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100">

                        <div class="flex items-center gap-3 min-w-0">

                            <button
                                type="button"
                                class="module-toggle w-7 h-7 rounded-lg bg-slate-50 text-slate-500 flex items-center justify-center hover:bg-slate-100 transition">

                                <i class="fa-solid fa-chevron-down text-[10px]"></i>

                            </button>

                            <div class="min-w-0">

                                <div class="flex items-center gap-2">

                                    <span class="module-number text-[10px] font-bold text-blue-500 uppercase">
                                        Module ${moduleCount}
                                    </span>

                                    <span class="text-xs text-slate-300">
                                        —
                                    </span>

                                    <h5 class="module-title text-sm font-bold text-slate-800 truncate">
                                        New Module
                                    </h5>

                                </div>

                                <p class="module-description text-xs text-slate-400 mt-0.5">
                                    Module description
                                </p>

                            </div>

                        </div>

                        <div class="flex items-center gap-1 shrink-0">

                            <button
                                type="button"
                                class="edit-module w-8 h-8 rounded-lg text-slate-400 hover:bg-blue-50 hover:text-blue-600 transition"
                                title="Edit module">

                                <i class="fa-solid fa-pen text-xs"></i>

                            </button>

                            <button
                                type="button"
                                class="delete-module w-8 h-8 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500 transition"
                                title="Delete module">

                                <i class="fa-solid fa-trash text-xs"></i>

                            </button>

                        </div>

                    </div>

                    <div class="module-lessons px-4 py-3">

                        <div class="lessons-container"></div>

                        <div class="pt-3">

                            <button
                                type="button"
                                class="add-lesson inline-flex items-center gap-2 text-xs font-semibold text-blue-600 hover:text-blue-700">

                                <i class="fa-solid fa-plus"></i>

                                Add Lesson

                            </button>

                        </div>

                    </div>
                `;

                    container.appendChild(module);

                    console.log('Module added.');

                    renumberModules();

                });

            }


            /*
            |--------------------------------------------------------------------------
            | ALL OTHER BUTTONS
            |--------------------------------------------------------------------------
            */

            modal.addEventListener('click', function (event) {

                /*
                |--------------------------------------------------------------------------
                | ADD LESSON
                |--------------------------------------------------------------------------
                */

                const addLesson = event.target.closest('.add-lesson');

                if (addLesson) {

                    const module = addLesson.closest('.course-module');

                    if (!module) {
                        return;
                    }

                    const lessonsContainer =
                        module.querySelector('.lessons-container');

                    if (!lessonsContainer) {
                        return;
                    }

                    const lessonCount =
                        lessonsContainer.querySelectorAll('.lesson-item').length + 1;

                    const lesson = document.createElement('div');

                    lesson.className =
                        'lesson-item flex items-center gap-3 py-2.5 border-b border-slate-100';

                    lesson.innerHTML = `

                    <div class="w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center shrink-0">

                        <span class="lesson-number text-[10px] font-bold text-slate-500">
                            ${String(lessonCount).padStart(2, '0')}
                        </span>

                    </div>

                    <div class="flex-1 min-w-0">

                        <p class="lesson-title text-sm font-semibold text-slate-700 truncate">
                            New Lesson
                        </p>

                        <p class="text-[10px] text-slate-400">
                            Lesson
                        </p>

                    </div>

                    <div class="flex items-center gap-1">

                        <button
                            type="button"
                            class="edit-lesson w-8 h-8 rounded-lg text-slate-400 hover:bg-blue-50 hover:text-blue-600">

                            <i class="fa-solid fa-pen text-xs"></i>

                        </button>

                        <button
                            type="button"
                            class="delete-lesson w-8 h-8 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500">

                            <i class="fa-solid fa-trash text-xs"></i>

                        </button>

                    </div>
                `;

                    lessonsContainer.appendChild(lesson);

                    console.log('Lesson added.');

                    renumberLessons();

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | DELETE MODULE
                |--------------------------------------------------------------------------
                */

                const deleteModule =
                    event.target.closest('.delete-module');

                if (deleteModule) {

                    const module =
                        deleteModule.closest('.course-module');

                    if (!module) {
                        return;
                    }

                    if (!confirm(
                        'Are you sure you want to delete this module and all its lessons?'
                    )) {
                        return;
                    }

                    module.remove();

                    renumberModules();

                    console.log('Module deleted.');

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | DELETE LESSON
                |--------------------------------------------------------------------------
                */

                const deleteLesson =
                    event.target.closest('.delete-lesson');

                if (deleteLesson) {

                    const lesson =
                        deleteLesson.closest('.lesson-item');

                    if (!lesson) {
                        return;
                    }

                    if (!confirm(
                        'Are you sure you want to delete this lesson?'
                    )) {
                        return;
                    }

                    lesson.remove();

                    renumberLessons();

                    console.log('Lesson deleted.');

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | EDIT MODULE
                |--------------------------------------------------------------------------
                */

                const editModule =
                    event.target.closest('.edit-module');

                if (editModule) {

                    const module =
                        editModule.closest('.course-module');

                    if (!module) {
                        return;
                    }

                    const title =
                        module.querySelector('.module-title');

                    const description =
                        module.querySelector('.module-description');

                    const newTitle =
                        prompt(
                            'Module title:',
                            title ? title.textContent.trim() : ''
                        );

                    if (newTitle === null || newTitle.trim() === '') {
                        return;
                    }

                    const newDescription =
                        prompt(
                            'Module description:',
                            description ? description.textContent.trim() : ''
                        );

                    if (title) {
                        title.textContent = newTitle.trim();
                    }

                    if (description && newDescription !== null) {
                        description.textContent = newDescription.trim();
                    }

                    console.log('Module edited.');

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | EDIT LESSON
                |--------------------------------------------------------------------------
                */

                const editLesson =
                    event.target.closest('.edit-lesson');

                if (editLesson) {

                    const lesson =
                        editLesson.closest('.lesson-item');

                    if (!lesson) {
                        return;
                    }

                    const title =
                        lesson.querySelector('.lesson-title');

                    const newTitle =
                        prompt(
                            'Lesson title:',
                            title ? title.textContent.trim() : ''
                        );

                    if (newTitle === null || newTitle.trim() === '') {
                        return;
                    }

                    if (title) {
                        title.textContent = newTitle.trim();
                    }

                    console.log('Lesson edited.');

                    return;
                }

            });


            /*
            |--------------------------------------------------------------------------
            | RENUMBER MODULES
            |--------------------------------------------------------------------------
            */

            function renumberModules() {

                const modules =
                    container.querySelectorAll('.course-module');

                modules.forEach(function (module, index) {

                    const number =
                        module.querySelector('.module-number');

                    if (number) {
                        number.textContent =
                            'Module ' + (index + 1);
                    }

                });

            }


            /*
            |--------------------------------------------------------------------------
            | RENUMBER LESSONS
            |--------------------------------------------------------------------------
            */

            function renumberLessons() {

                const modules =
                    container.querySelectorAll('.course-module');

                modules.forEach(function (module) {

                    const lessons =
                        module.querySelectorAll('.lesson-item');

                    lessons.forEach(function (lesson, index) {

                        const number =
                            lesson.querySelector('.lesson-number');

                        if (number) {

                            number.textContent =
                                String(index + 1).padStart(2, '0');

                        }

                    });

                });

            }

        }


        /*
        |--------------------------------------------------------------------------
        | INITIALIZE
        |--------------------------------------------------------------------------
        */

        if (document.readyState === 'loading') {

            document.addEventListener(
                'DOMContentLoaded',
                initManageContent
            );

        } else {

            initManageContent();

        }

    })();
</script>