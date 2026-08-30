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

                                <?php foreach ($showInstructorsCourse as $instructor): ?>

                                    <?php
                                    $id =
                                        $instructor['instructor_id']
                                        ?? $instructor['employee_id']
                                        ?? $instructor['id']
                                        ?? null;

                                    $name =
                                        $instructor['instructor_name']
                                        ?? $instructor['employee_name']
                                        ?? $instructor['name']
                                        ?? 'Unknown Instructor';
                                    ?>

                                    <?php if ($id): ?>

                                        <option value="<?= (int) $id ?>">
                                            <?= htmlspecialchars($name) ?>
                                        </option>

                                    <?php endif; ?>

                                <?php endforeach; ?>

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


            /* =====================================================
               GET COURSE ID
            ===================================================== */

            const courseId = Number(
                button.getAttribute('data-course-id')
            );

            console.log(
                'Editing Course ID:',
                courseId
            );


            /* =====================================================
               FIND COURSE
            ===================================================== */

            const course = editCourseData.find(function (item) {

                return Number(item.id) === courseId;

            });


            if (!course) {

                console.error(
                    'Course not found:',
                    courseId
                );

                return;
            }


            console.log(
                'Selected Course:',
                course
            );


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
                document.getElementById(
                    'editThumbnailPreview'
                );

            const thumbnailImage =
                document.getElementById(
                    'editThumbnailImage'
                );


            if (
                course.thumbnail_path &&
                course.thumbnail_path !== ''
            ) {

                thumbnailImage.src =
                    course.thumbnail_path;

                thumbnailPreview.classList.remove(
                    'd-none'
                );

            } else {

                thumbnailImage.src = '';

                thumbnailPreview.classList.add(
                    'd-none'
                );
            }


            /* =====================================================
               OWNER
            ===================================================== */

            populateEditOwner(course);


            /* =====================================================
               CO-INSTRUCTORS
            ===================================================== */

            populateEditCoInstructors(course);


            /* =====================================================
               SKILLS
            ===================================================== */

            populateEditSkills(course);

        });


    /* =========================================================
       POPULATE COURSE OWNER
    ========================================================= */

    function populateEditOwner(course) {

        const ownerSelect =
            document.getElementById(
                'editCourseOwner'
            );


        if (!ownerSelect) {

            console.error(
                'editCourseOwner element not found.'
            );

            return;
        }


        /* ---------------------------------------------------------
           RESET
        --------------------------------------------------------- */

        ownerSelect.value = '';


        /* ---------------------------------------------------------
           FIRST: DIRECT COURSE OWNER
        --------------------------------------------------------- */

        const directOwnerId =
            course.instructor_id ??
            course.owner_id ??
            '';


        if (
            Number(directOwnerId) > 0
        ) {

            ownerSelect.value =
                String(directOwnerId);

            console.log(
                'Owner loaded from course:',
                directOwnerId
            );

            return;
        }


        /* ---------------------------------------------------------
           SECOND: SEARCH COURSE INSTRUCTORS
        --------------------------------------------------------- */

        const instructors =
            Array.isArray(course.instructors)
                ? course.instructors
                : [];


        const owner =
            instructors.find(function (instructor) {

                const role =
                    String(
                        instructor.role ?? ''
                    )
                        .toLowerCase()
                        .trim();


                return (
                    role === 'owner' ||
                    role === 'course owner' ||
                    role === 'course_owner'
                );

            });


        if (!owner) {

            console.warn(
                'No course owner found.',
                instructors
            );

            return;
        }


        /* ---------------------------------------------------------
           GET OWNER ID
        --------------------------------------------------------- */

        const ownerId =
            owner.instructor_id ??
            owner.employee_id ??
            owner.id ??
            '';


        if (
            Number(ownerId) > 0
        ) {

            ownerSelect.value =
                String(ownerId);

            console.log(
                'Owner loaded from instructors:',
                ownerId
            );

        } else {

            console.warn(
                'Owner found but no valid ID:',
                owner
            );
        }
    }


    /* =========================================================
       POPULATE CO-INSTRUCTORS
    ========================================================= */

    function populateEditCoInstructors(course) {

        const container = document.getElementById(
            'editCoInstructorList'
        );

        if (!container) {
            console.error('editCoInstructorList not found.');
            return;
        }

        container.innerHTML = '';

        const instructors = Array.isArray(course.instructors)
            ? course.instructors
            : [];

        console.log('========== CO-INSTRUCTORS ==========');
        console.log('All instructors:', instructors);

        instructors.forEach(function (instructor) {

            const role = String(
                instructor.role ?? ''
            ).toLowerCase().trim();

            /*
             * Only load co-instructors.
             */
            if (
                role !== 'co-instructor' &&
                role !== 'co_instructor' &&
                role !== 'co instructor'
            ) {
                return;
            }

            /*
             * IMPORTANT:
             * Try every possible ID field.
             */
            const instructorId =
                instructor.instructor_id ??
                instructor.employee_id ??
                instructor.user_id ??
                instructor.id ??
                '';

            console.log(
                'Co-instructor object:',
                instructor
            );

            console.log(
                'Resolved co-instructor ID:',
                instructorId
            );

            if (
                instructorId === '' ||
                Number(instructorId) <= 0
            ) {
                console.warn(
                    'Invalid co-instructor ID:',
                    instructor
                );

                return;
            }

            addEditCoInstructor(
                String(instructorId)
            );
        });

        console.log(
            'Loaded co-instructor selects:',
            container.querySelectorAll(
                'select[name="co_instructors[]"]'
            ).length
        );
    }

    /* =========================================================
       ADD CO-INSTRUCTOR
    ========================================================= */
    function addEditCoInstructor(selectedId = '') {

        const container = document.getElementById(
            'editCoInstructorList'
        );

        if (!container) {
            console.error(
                'editCoInstructorList not found.'
            );
            return;
        }

        const wrapper = document.createElement('div');

        wrapper.className =
            'd-flex gap-2 mb-2 edit-co-instructor-item';

        wrapper.innerHTML = `

        <select
            name="co_instructors[]"
            class="form-select">

            <option value="">
                Select co-instructor
            </option>

            <?php foreach ($showInstructorsCourse as $instructor): ?>

                <?php

                $id =
                    $instructor['instructor_id']
                    ?? $instructor['employee_id']
                    ?? $instructor['user_id']
                    ?? $instructor['id']
                    ?? null;

                $name =
                    $instructor['instructor_name']
                    ?? $instructor['employee_name']
                    ?? $instructor['name']
                    ?? 'Unknown Instructor';

                ?>

                <?php if ($id !== null): ?>

                    <option value="<?= (int) $id ?>">
                        <?= htmlspecialchars(
                            $name,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </option>

                <?php endif; ?>

            <?php endforeach; ?>

        </select>

        <button
            type="button"
            class="btn btn-outline-danger"
            onclick="
                this
                    .closest('.edit-co-instructor-item')
                    .remove()
            ">

            <i class="fa-solid fa-trash"></i>

        </button>

    `;

        const select = wrapper.querySelector(
            'select[name="co_instructors[]"]'
        );

        if (select) {

            const wantedId = String(
                selectedId
            );

            /*
             * Find the actual option.
             */
            const matchingOption =
                Array.from(select.options).find(
                    function (option) {
                        return String(option.value) === wantedId;
                    }
                );

            if (matchingOption) {

                matchingOption.selected = true;

                console.log(
                    'Co-instructor selected:',
                    wantedId,
                    matchingOption.text
                );

            } else {

                console.warn(
                    'Co-instructor ID does not exist in select options:',
                    wantedId
                );

                console.log(
                    'Available instructor IDs:',
                    Array.from(select.options).map(
                        option => option.value
                    )
                );
            }
        }

        container.appendChild(wrapper);
    }
    /* =========================================================
       POPULATE SKILLS
    ========================================================= */

    function populateEditSkills(course) {

        /*
         * Uncheck everything first.
         */

        document
            .querySelectorAll(
                '.edit-course-skill'
            )
            .forEach(function (checkbox) {

                checkbox.checked = false;

            });


        const skills =
            Array.isArray(course.skills)
                ? course.skills
                : [];


        console.log(
            'Course skills:',
            skills
        );


        skills.forEach(function (skill) {

            let skillId;


            if (
                typeof skill === 'object' &&
                skill !== null
            ) {

                skillId =
                    skill.skill_id ??
                    skill.id ??
                    '';

            } else {

                skillId =
                    skill;
            }


            skillId =
                Number(skillId);


            if (
                skillId <= 0
            ) {

                return;
            }


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
       ESCAPE HTML
    ========================================================= */

    function escapeHtml(value) {

        return String(value ?? '')
            .replace(
                /&/g,
                '&amp;'
            )
            .replace(
                /</g,
                '&lt;'
            )
            .replace(
                />/g,
                '&gt;'
            )
            .replace(
                /"/g,
                '&quot;'
            )
            .replace(
                /'/g,
                '&#039;'
            );
    }
</script>