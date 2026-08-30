<div class="modal fade" id="viewCourseModal" tabindex="-1" aria-labelledby="viewCourseModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered">

        <div class="modal-content border-0 shadow-lg rounded-4">

            <!-- =====================================================
                 HEADER
            ====================================================== -->

            <div class="modal-header px-4 py-3 border-bottom">

                <div>
                    <h5 class="modal-title fw-bold text-dark" id="viewCourseModalLabel">
                        Course Details
                    </h5>

                    <small class="text-muted">
                        View course information, instructors, skills, and lessons.
                    </small>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>

            </div>


            <!-- =====================================================
                 BODY
            ====================================================== -->

            <div class="modal-body px-4 py-4" style="max-height:70vh; overflow-y:auto;">

                <!-- =================================================
                     BASIC INFORMATION
                ================================================== -->

                <div class="mb-4">

                    <h6 class="fw-bold text-dark mb-1">
                        Basic Information
                    </h6>

                    <p class="text-muted small mb-0">
                        Course information and settings.
                    </p>

                </div>


                <!-- TITLE -->

                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        Course Title
                    </label>

                    <div id="viewCourseTitle" class="form-control bg-light">
                    </div>

                </div>


                <!-- CATEGORY + STATUS -->

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-semibold">
                            Category
                        </label>

                        <div id="viewCourseCategory" class="form-control bg-light">
                        </div>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-semibold">
                            Status
                        </label>

                        <div>
                            <span id="viewCourseStatus" class="badge">
                            </span>
                        </div>

                    </div>

                </div>


                <!-- DESCRIPTION -->

                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        Description
                    </label>

                    <div id="viewCourseDescription" class="form-control bg-light"
                        style="min-height:100px; white-space:pre-wrap;">
                    </div>

                </div>


                <!-- DATES -->

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-semibold">
                            Start Date
                        </label>

                        <div id="viewStartDate" class="form-control bg-light">
                        </div>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-semibold">
                            Enrollment Deadline
                        </label>

                        <div id="viewEnrollmentDeadline" class="form-control bg-light">
                        </div>

                    </div>

                </div>


                <!-- THUMBNAIL -->

                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Course Thumbnail
                    </label>

                    <div id="viewThumbnailContainer" class="mt-2 d-none">

                        <img id="viewCourseThumbnail" src="" alt="Course Thumbnail" class="rounded-3 border" style="
                                width:260px;
                                height:145px;
                                object-fit:cover;
                            ">

                    </div>

                    <div id="viewNoThumbnail" class="text-muted small">
                        No thumbnail available.
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
                        Course owner and assigned co-instructors.
                    </p>


                    <!-- COURSE OWNER -->

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Course Owner
                        </label>

                        <div id="viewCourseOwner" class="form-control bg-light">
                        </div>

                    </div>


                    <!-- CO-INSTRUCTORS -->

                    <div>

                        <label class="form-label fw-semibold">
                            Co-Instructors
                        </label>

                        <div id="viewCoInstructorList">

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
                        Skills associated with this course.
                    </p>


                    <div id="viewCourseSkills" class="d-flex flex-wrap gap-2">

                    </div>

                </div>


                <hr class="my-4">


                <!-- =================================================
                     LESSONS
                ================================================== -->

                <div class="mb-3">

                    <h6 class="fw-bold text-dark mb-1">
                        Course Lessons
                    </h6>

                    <p class="text-muted small mb-3">
                        Lessons included in this course.
                    </p>


                    <div id="viewCourseLessons">

                    </div>

                </div>

            </div>


            <!-- =====================================================
                 FOOTER
            ====================================================== -->

            <div class="modal-footer px-4 py-3 border-top">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                    <i class="fa-solid fa-xmark me-1"></i>

                    Close

                </button>

            </div>

        </div>

    </div>

</div>

<script>

    /* =========================================================
       VIEW COURSE DATA
    ========================================================= */

    const viewCourseData = <?= json_encode(
        $allTrainingCourses,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP
    ) ?>;


    console.log('View Course Data:', viewCourseData);


    /* =========================================================
       OPEN VIEW MODAL
    ========================================================= */

    document
        .getElementById('viewCourseModal')
        .addEventListener('show.bs.modal', function (event) {

            const button = event.relatedTarget;

            if (!button) {
                console.error('No modal trigger button.');
                return;
            }


            const courseId = Number(
                button.getAttribute('data-course-id')
            );


            console.log('Viewing Course ID:', courseId);


            const course = viewCourseData.find(
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

            document.getElementById('viewCourseTitle').textContent =
                course.title ?? '—';


            document.getElementById('viewCourseCategory').textContent =
                course.category ?? '—';


            document.getElementById('viewCourseDescription').textContent =
                course.description ?? '—';


            document.getElementById('viewStartDate').textContent =
                course.start_date ?? '—';


            document.getElementById('viewEnrollmentDeadline').textContent =
                course.enrollment_deadline ?? '—';


            /* =====================================================
               STATUS
            ===================================================== */

            const statusElement =
                document.getElementById('viewCourseStatus');


            const status =
                course.status ?? 'draft';


            statusElement.textContent =
                status.charAt(0).toUpperCase() + status.slice(1);


            statusElement.className =
                'badge';


            if (status === 'active') {

                statusElement.classList.add(
                    'bg-success'
                );

            } else if (status === 'archived') {

                statusElement.classList.add(
                    'bg-secondary'
                );

            } else {

                statusElement.classList.add(
                    'bg-warning',
                    'text-dark'
                );

            }

            /* =====================================================
               THUMBNAIL
            ===================================================== */

            const thumbnailContainer =
                document.getElementById('viewThumbnailContainer');

            const thumbnail =
                document.getElementById('viewCourseThumbnail');

            const noThumbnail =
                document.getElementById('viewNoThumbnail');


            if (course.thumbnail_path) {

                let imagePath = course.thumbnail_path;

                if (!imagePath.startsWith('/')) {

                    imagePath =
                        '/hrms-capstone/modules/portal/public/' +
                        imagePath;

                }

                console.log('Thumbnail URL:', imagePath);

                thumbnail.src = imagePath;

                thumbnailContainer.classList.remove('d-none');
                noThumbnail.classList.add('d-none');


                /* Handle broken image */
                thumbnail.onerror = function () {

                    console.error(
                        'Unable to load thumbnail:',
                        imagePath
                    );

                    thumbnailContainer.classList.add('d-none');

                    noThumbnail.textContent =
                        'Unable to load thumbnail.';

                    noThumbnail.classList.remove('d-none');

                };


            } else {

                thumbnail.src = '';

                thumbnailContainer.classList.add('d-none');

                noThumbnail.textContent =
                    'No thumbnail available.';

                noThumbnail.classList.remove('d-none');

            }

            /* =====================================================
               COURSE OWNER
            ===================================================== */

            const owner =
                document.getElementById(
                    'viewCourseOwner'
                );


            owner.textContent =
                getInstructorName(
                    course.instructor_id
                );


            /* =====================================================
               CO-INSTRUCTORS
            ===================================================== */

            populateViewCoInstructors(course);


            /* =====================================================
               SKILLS
            ===================================================== */

            populateViewSkills(course);


            /* =====================================================
               LESSONS
            ===================================================== */

            populateViewLessons(course);

        });


    /* =========================================================
       INSTRUCTOR NAME
    ========================================================= */

    function getInstructorName(id) {

        if (!id) {
            return 'Not assigned';
        }

        return 'Instructor ' + id;

    }


    /* =========================================================
       CO-INSTRUCTORS
    ========================================================= */

    function populateViewCoInstructors(course) {

        const container =
            document.getElementById(
                'viewCoInstructorList'
            );


        container.innerHTML = '';


        const instructors =
            course.instructors ?? [];


        const coInstructors =
            instructors.filter(function (instructor) {

                return instructor.role === 'co-instructor';

            });


        if (coInstructors.length === 0) {

            container.innerHTML = `
                <div class="text-muted small">
                    No co-instructors assigned.
                </div>
            `;

            return;
        }


        coInstructors.forEach(function (instructor) {

            const item =
                document.createElement('div');


            item.className =
                'd-flex align-items-center gap-2 mb-2';


            item.innerHTML = `

                <span class="badge bg-light text-dark border">

                    <i class="fa-solid fa-user me-1"></i>

                    ${escapeViewHtml(
                getInstructorName(
                    instructor.instructor_id
                )
            )}

                </span>

            `;


            container.appendChild(item);

        });

    }


    /* =========================================================
       SKILLS
    ========================================================= */

    function populateViewSkills(course) {

        const container =
            document.getElementById(
                'viewCourseSkills'
            );


        container.innerHTML = '';


        const skills =
            course.skills ?? [];


        if (skills.length === 0) {

            container.innerHTML = `
                <span class="text-muted small">
                    No skills assigned.
                </span>
            `;

            return;
        }


        skills.forEach(function (skill) {

            const skillId =
                Number(
                    skill.skill_id ??
                    skill.id ??
                    skill
                );


            const badge =
                document.createElement('span');


            badge.className =
                'badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2';


            badge.textContent =
                'Skill ' + skillId;


            container.appendChild(badge);

        });

    }


    /* =========================================================
       LESSONS
    ========================================================= */

    function populateViewLessons(course) {

        const container =
            document.getElementById(
                'viewCourseLessons'
            );


        container.innerHTML = '';


        let lessons = [];


        /* -----------------------------------------------------
           Lessons are stored in the latest version snapshot
        ----------------------------------------------------- */

        const versions =
            course.versions ?? [];


        if (versions.length > 0) {

            const latestVersion =
                versions[versions.length - 1];


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


            lessons =
                snapshot.lessons ?? [];

        }


        if (lessons.length === 0) {

            container.innerHTML = `
                <div class="border rounded-3 p-3 text-muted small">
                    No lessons have been added to this course.
                </div>
            `;

            return;
        }


        lessons.forEach(function (lesson, index) {

            const lessonElement =
                document.createElement('div');


            lessonElement.className =
                'border rounded-3 p-3 mb-2';


            lessonElement.innerHTML = `

                <div class="d-flex align-items-center justify-content-between">

                    <div>

                        <div class="fw-semibold text-dark">

                            ${index + 1}.
                            ${escapeViewHtml(
                lesson.title ?? 'Untitled Lesson'
            )}

                        </div>

                    </div>


                    <span class="badge bg-light text-dark border">

                        <i class="fa-regular fa-clock me-1"></i>

                        ${escapeViewHtml(
                formatLessonDuration(
                    lesson.duration_minutes
                )
            )}

                    </span>

                </div>

            `;


            container.appendChild(
                lessonElement
            );

        });

    }


    /* =========================================================
       LESSON DURATION
    ========================================================= */

    function formatLessonDuration(minutes) {

        minutes = Number(minutes);


        if (!minutes) {
            return 'Duration not specified';
        }


        if (minutes < 60) {
            return minutes + ' minutes';
        }


        const hours =
            Math.floor(minutes / 60);


        const remaining =
            minutes % 60;


        if (remaining === 0) {

            return hours +
                (hours === 1 ? ' hour' : ' hours');

        }


        return hours +
            (hours === 1 ? ' hour ' : ' hours ') +
            remaining +
            ' minutes';

    }


    /* =========================================================
       ESCAPE HTML
    ========================================================= */

    function escapeViewHtml(value) {

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

    }

</script>