<div class="modal fade" id="editCourseModal" tabindex="-1" aria-labelledby="editCourseModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered">

        <div class="modal-content border-0 shadow-lg rounded-4">

            <!-- =====================================================
                 HEADER
            ====================================================== -->

            <div class="modal-header px-4 py-3 border-bottom">

                <div>
                    <h5 class="modal-title fw-bold text-dark" id="editCourseModalLabel">
                        Edit Course
                    </h5>

                    <small class="text-muted">
                        Update course information and settings.
                    </small>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>

            </div>


            <!-- =====================================================
                 FORM
            ====================================================== -->

            <form id="editCourseForm" method="POST" action="index.php?url=admin-update-course"
                enctype="multipart/form-data">

                <input type="hidden" name="course_id" id="editCourseId">


                <!-- =================================================
                     SCROLLABLE BODY
                ================================================== -->

                <div class="modal-body px-4 py-4" style="
                        max-height:70vh;
                        overflow-y:auto;
                    ">


                    <!-- =================================================
                         BASIC INFORMATION
                    ================================================= -->

                    <div class="mb-4">

                        <h6 class="fw-bold text-dark mb-1">
                            Basic Information
                        </h6>

                        <p class="text-muted small mb-0">
                            Update the basic information of this course.
                        </p>

                    </div>


                    <!-- COURSE TITLE -->

                    <div class="mb-3">

                        <label for="editCourseTitle" class="form-label fw-semibold">

                            Course Title
                            <span class="text-danger">*</span>

                        </label>

                        <input type="text" class="form-control" name="title" id="editCourseTitle" required>

                    </div>


                    <!-- CATEGORY + STATUS -->

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label for="editCourseCategory" class="form-label fw-semibold">

                                Category
                                <span class="text-danger">*</span>

                            </label>

                            <select class="form-select" name="category" id="editCourseCategory" required>

                                <option value="">
                                    Select category
                                </option>

                                <option value="Communication">
                                    Communication
                                </option>

                                <option value="Leadership">
                                    Leadership
                                </option>

                                <option value="Productivity">
                                    Productivity
                                </option>

                                <option value="Technical Skills">
                                    Technical Skills
                                </option>

                                <option value="Compliance">
                                    Compliance
                                </option>

                                <option value="Customer Service">
                                    Customer Service
                                </option>

                                <option value="Team Development">
                                    Team Development
                                </option>

                                <option value="Information Security">
                                    Information Security
                                </option>

                                <option value="Well-Being">
                                    Well-Being
                                </option>

                                <option value="Professional Development">
                                    Professional Development
                                </option>

                            </select>

                        </div>


                        <div class="col-md-6 mb-3">

                            <label for="editCourseStatus" class="form-label fw-semibold">

                                Status
                                <span class="text-danger">*</span>

                            </label>

                            <select class="form-select" name="status" id="editCourseStatus" required>

                                <option value="draft">
                                    Draft
                                </option>

                                <option value="active">
                                    Active
                                </option>

                                <option value="archived">
                                    Archived
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="mb-3">

                        <label for="editCourseDescription" class="form-label fw-semibold">

                            Description
                            <span class="text-danger">*</span>

                        </label>

                        <textarea class="form-control" name="description" id="editCourseDescription" rows="4"
                            required></textarea>

                    </div>


                    <!-- DATES -->

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label for="editStartDate" class="form-label fw-semibold">

                                Start Date

                            </label>

                            <input type="date" class="form-control" name="start_date" id="editStartDate">

                        </div>


                        <div class="col-md-6 mb-3">

                            <label for="editEnrollmentDeadline" class="form-label fw-semibold">

                                Enrollment Deadline

                            </label>

                            <input type="date" class="form-control" name="enrollment_deadline"
                                id="editEnrollmentDeadline">

                        </div>

                    </div>


                    <!-- THUMBNAIL -->

                    <div class="mb-4">

                        <label for="editCourseThumbnail" class="form-label fw-semibold">

                            Course Thumbnail

                        </label>

                        <div id="editThumbnailPreview" class="mb-2 d-none">

                            <img id="editThumbnailImage" src="" alt="Course Thumbnail" class="rounded-3 border" style="
                                    width:180px;
                                    height:100px;
                                    object-fit:cover;
                                ">

                        </div>

                        <input type="file" class="form-control" name="thumbnail" id="editCourseThumbnail"
                            accept="image/jpeg,image/png,image/webp">

                        <div class="form-text">
                            JPG, PNG, or WEBP. Maximum file size: 5 MB.
                            Leave empty to keep the existing thumbnail.
                        </div>

                    </div>


                    <hr class="my-4">


                    <!-- =================================================
                         INSTRUCTORS
                    ================================================== -->

                    <div class="mb-4">

                        <h6 class="fw-bold text-dark mb-1">
                            Instructors
                        </h6>

                        <p class="text-muted small mb-3">
                            Manage the course owner and co-instructors.
                        </p>


                        <!-- COURSE OWNER -->

                        <div class="mb-3">

                            <label for="editCourseOwner" class="form-label fw-semibold">

                                Course Owner
                                <span class="text-danger">*</span>

                            </label>

                            <select class="form-select" name="instructor_id" id="editCourseOwner" required>

                                <option value="">
                                    Select course owner
                                </option>

                                <?php for ($i = 1; $i <= 10; $i++): ?>

                                    <option value="<?= $i ?>">
                                        Instructor <?= $i ?>
                                    </option>

                                <?php endfor; ?>

                            </select>

                        </div>


                        <!-- CO-INSTRUCTORS -->

                        <div class="mb-4">

                            <div class="d-flex justify-content-between align-items-center mb-2">

                                <label class="form-label fw-semibold mb-0">
                                    Co-Instructors
                                </label>

                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="addEditCoInstructor()">

                                    <i class="fa-solid fa-plus me-1"></i>
                                    Add Instructor

                                </button>

                            </div>


                            <div id="editCoInstructorList">
                            </div>

                        </div>

                    </div>


                    <hr class="my-4">


                    <!-- =================================================
                         SKILLS
                    ================================================== -->

                    <div class="mb-4">

                        <h6 class="fw-bold text-dark mb-1">
                            Course Skills
                        </h6>

                        <p class="text-muted small mb-3">
                            Select the skills associated with this course.
                        </p>


                        <div class="row">

                            <?php for ($i = 1; $i <= 10; $i++): ?>

                                <div class="col-md-6 col-lg-4 mb-2">

                                    <div class="form-check">

                                        <input class="form-check-input edit-course-skill" type="checkbox" name="skills[]"
                                            value="<?= $i ?>" id="editSkill<?= $i ?>">

                                        <label class="form-check-label" for="editSkill<?= $i ?>">

                                            Skill <?= $i ?>

                                        </label>

                                    </div>

                                </div>

                            <?php endfor; ?>

                        </div>

                    </div>


                    <hr class="my-4">


                    <!-- =================================================
                         LESSONS
                    ================================================== -->

                    <div class="mb-3">

                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div>

                                <h6 class="fw-bold text-dark mb-1">
                                    Course Lessons
                                </h6>

                                <p class="text-muted small mb-0">
                                    Manage the lessons included in this course.
                                </p>

                            </div>


                            <button type="button" class="btn btn-sm btn-outline-primary"
                                onclick="addEditCourseLesson()">

                                <i class="fa-solid fa-plus me-1"></i>
                                Add Lesson

                            </button>

                        </div>


                        <div id="editCourseLessons">
                        </div>

                    </div>

                </div>


                <!-- =================================================
                     FOOTER
                ================================================== -->

                <div class="modal-footer px-4 py-3 border-top">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                        Cancel

                    </button>


                    <button type="submit" class="btn btn-primary">

                        <i class="fa-solid fa-check me-1"></i>

                        Save Changes

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script>

    /* =========================================================
       COURSE DATA
    ========================================================= */

    const editCourseData = <?= json_encode(
        $allTrainingCourses,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP
    ) ?>;


    console.log('Edit Course Data:', editCourseData);


    /* =========================================================
       LESSON INDEX
    ========================================================= */

    let editLessonIndex = 0;


    /* =========================================================
       OPEN EDIT MODAL
    ========================================================= */

    document
        .getElementById('editCourseModal')
        .addEventListener('show.bs.modal', function (event) {

            const button = event.relatedTarget;

            if (!button) {
                console.error('No modal trigger button.');
                return;
            }


            const courseId = Number(
                button.getAttribute('data-course-id')
            );


            console.log('Editing Course ID:', courseId);


            const course = editCourseData.find(
                item => Number(item.id) === courseId
            );


            if (!course) {

                console.error(
                    'Course not found:',
                    courseId
                );

                return;
            }


            console.log('Selected Course:', course);


            /* =====================================================
               BASIC INFORMATION
            ===================================================== */

            document.getElementById('editCourseId').value =
                course.id ?? '';

            document.getElementById('editCourseTitle').value =
                course.title ?? '';

            document.getElementById('editCourseDescription').value =
                course.description ?? '';

            document.getElementById('editCourseCategory').value =
                course.category ?? '';

            document.getElementById('editCourseStatus').value =
                course.status ?? 'draft';

            document.getElementById('editStartDate').value =
                course.start_date ?? '';

            document.getElementById('editEnrollmentDeadline').value =
                course.enrollment_deadline ?? '';


            /* =====================================================
               THUMBNAIL
            ===================================================== */

            const thumbnailPreview =
                document.getElementById('editThumbnailPreview');

            const thumbnailImage =
                document.getElementById('editThumbnailImage');


            if (course.thumbnail_path) {

                thumbnailImage.src =
                    course.thumbnail_path;

                thumbnailPreview.classList.remove('d-none');

            } else {

                thumbnailImage.src = '';

                thumbnailPreview.classList.add('d-none');

            }


            /* =====================================================
               OWNER
            ===================================================== */

            document.getElementById('editCourseOwner').value =
                course.instructor_id ?? '';


            /* =====================================================
               CO-INSTRUCTORS
            ===================================================== */

            populateEditCoInstructors(course);


            /* =====================================================
               SKILLS
            ===================================================== */

            populateEditSkills(course);


            /* =====================================================
               LESSONS
            ===================================================== */

            populateEditLessons(course);

        });


    /* =========================================================
       POPULATE CO-INSTRUCTORS
    ========================================================= */

    function populateEditCoInstructors(course) {

        const container =
            document.getElementById('editCoInstructorList');


        container.innerHTML = '';


        const instructors =
            course.instructors ?? [];


        instructors.forEach(function (instructor) {

            if (
                instructor.role !== 'co-instructor'
            ) {
                return;
            }


            addEditCoInstructor(
                instructor.instructor_id
            );

        });

    }


    /* =========================================================
       ADD CO-INSTRUCTOR
    ========================================================= */

    function addEditCoInstructor(selectedId = '') {

        const container =
            document.getElementById('editCoInstructorList');


        const wrapper =
            document.createElement('div');


        wrapper.className =
            'd-flex gap-2 mb-2 edit-co-instructor-item';


        wrapper.innerHTML = `

        <select
            name="co_instructors[]"
            class="form-select">

            <option value="">
                Select co-instructor
            </option>

            <?php for ($i = 1; $i <= 10; $i++): ?>

                <option
                    value="<?= $i ?>"
                    ${Number(selectedId) === <?= $i ?> ? 'selected' : ''}>

                    Instructor <?= $i ?>

                </option>

            <?php endfor; ?>

        </select>


        <button
            type="button"
            class="btn btn-outline-danger"
            onclick="this.closest('.edit-co-instructor-item').remove()">

            <i class="fa-solid fa-trash"></i>

        </button>

    `;


        container.appendChild(wrapper);

    }


    /* =========================================================
       POPULATE SKILLS
    ========================================================= */

    function populateEditSkills(course) {

        document
            .querySelectorAll('.edit-course-skill')
            .forEach(function (checkbox) {

                checkbox.checked = false;

            });


        const skills =
            course.skills ?? [];


        skills.forEach(function (skill) {

            const skillId =
                Number(
                    skill.skill_id ??
                    skill.id ??
                    skill
                );


            const checkbox =
                document.querySelector(
                    `.edit-course-skill[value="${skillId}"]`
                );


            if (checkbox) {
                checkbox.checked = true;
            }

        });

    }


    /* =========================================================
       POPULATE LESSONS
    ========================================================= */

    function populateEditLessons(course) {

        const container =
            document.getElementById('editCourseLessons');

        container.innerHTML = '';

        editLessonIndex = 0;

        let lessons = [];

        const versions =
            Array.isArray(course.versions)
                ? course.versions
                : [];

        console.log('Course versions:', versions);

        if (versions.length > 0) {

            /*
             * Sort versions by version number.
             * This guarantees that the newest snapshot
             * is selected regardless of database order.
             */
            const sortedVersions = [...versions].sort(function (a, b) {

                return Number(a.version_number ?? 0) -
                    Number(b.version_number ?? 0);

            });

            const latestVersion =
                sortedVersions[sortedVersions.length - 1];

            console.log(
                'Latest course version:',
                latestVersion
            );

            let snapshot =
                latestVersion.snapshot;

            if (typeof snapshot === 'string') {

                try {

                    snapshot =
                        JSON.parse(snapshot);

                } catch (error) {

                    console.error(
                        'Unable to decode course snapshot:',
                        error
                    );

                    snapshot = {};
                }
            }

            if (
                snapshot &&
                typeof snapshot === 'object'
            ) {

                lessons =
                    Array.isArray(snapshot.lessons)
                        ? snapshot.lessons
                        : [];

            }
        }

        console.log(
            'Lessons loaded into edit modal:',
            lessons
        );

        if (lessons.length === 0) {

            container.innerHTML = `
            <div class="border rounded-3 p-3 text-muted small">
                No lessons have been added to this course.
            </div>
        `;

            return;
        }

        lessons.forEach(function (lesson) {

            addEditCourseLesson(
                lesson.title ?? '',
                lesson.duration_minutes ?? ''
            );

        });
    }


    /* =========================================================
       ADD LESSON
    ========================================================= */

    function addEditCourseLesson(
        title = '',
        duration = ''
    ) {

        const container =
            document.getElementById('editCourseLessons');


        const lesson =
            document.createElement('div');


        lesson.className =
            'edit-course-lesson border rounded-3 p-3 mb-2';


        lesson.innerHTML = `

        <div class="row align-items-end g-2">


            <!-- LESSON TITLE -->

            <div class="col-md">

                <label class="form-label small fw-semibold">

                    Lesson Title

                </label>

                <input
                    type="text"
                    class="form-control"
                    name="lessons[${editLessonIndex}][title]"
                    value="${escapeHtml(title)}"
                    placeholder="Lesson title"
                    required>

            </div>


            <!-- DURATION -->

            <div class="col-md-3">

                <label class="form-label small fw-semibold">

                    Duration

                </label>

                <select
                    class="form-select"
                    name="lessons[${editLessonIndex}][duration_minutes]"
                    required>

                    <option value="">
                        Select duration
                    </option>

                    <option value="5">
                        5 minutes
                    </option>

                    <option value="10">
                        10 minutes
                    </option>

                    <option value="15">
                        15 minutes
                    </option>

                    <option value="20">
                        20 minutes
                    </option>

                    <option value="30">
                        30 minutes
                    </option>

                    <option value="45">
                        45 minutes
                    </option>

                    <option value="60">
                        1 hour
                    </option>

                    <option value="90">
                        1 hour 30 minutes
                    </option>

                    <option value="120">
                        2 hours
                    </option>

                    <option value="150">
                        2 hours 30 minutes
                    </option>

                    <option value="180">
                        3 hours
                    </option>

                    <option value="240">
                        4 hours
                    </option>

                    <option value="300">
                        5 hours
                    </option>

                    <option value="360">
                        6 hours
                    </option>

                    <option value="480">
                        8 hours
                    </option>

                </select>

            </div>


            <!-- REMOVE -->

            <div class="col-auto">

                <button
                    type="button"
                    class="btn btn-outline-danger"
                    onclick="
                        this.closest('.edit-course-lesson').remove()
                    ">

                    <i class="fa-solid fa-trash"></i>

                </button>

            </div>

        </div>

    `;


        /*
         * Select the duration after inserting
         */

        const durationSelect =
            lesson.querySelector(
                'select[name^="lessons"]'
            );


        durationSelect.value =
            duration;


        container.appendChild(lesson);


        editLessonIndex++;

    }


    /* =========================================================
       ESCAPE HTML
    ========================================================= */

    function escapeHtml(value) {

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

    }

</script>