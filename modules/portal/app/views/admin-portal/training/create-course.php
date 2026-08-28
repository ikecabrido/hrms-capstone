<!-- =========================================================
     CREATE COURSE MODAL
========================================================= -->

<div id="createCourseModal" style="
        display:none;
        position:fixed;
        inset:0;
        z-index:9999;
        background:rgba(15,23,42,.55);
        backdrop-filter:blur(5px);
        padding:20px;
        align-items:center;
        justify-content:center;
    ">

    <div style="
            width:100%;
            max-width:850px;
            max-height:92vh;
            background:#ffffff;
            border-radius:20px;
            box-shadow:0 25px 60px rgba(15,23,42,.25);
            display:flex;
            flex-direction:column;
            overflow:hidden;
        " onclick="event.stopPropagation()">

        <!-- =====================================================
             HEADER
        ====================================================== -->
        <div style="
                padding:22px 26px 18px;
                border-bottom:1px solid #e2e8f0;
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:15px;
            ">


            <div>
                <h2 style="
                        margin:0;
                        font-size:20px;
                        font-weight:700;
                        color:#0f172a;
                    ">
                    Create Course
                </h2>

                <p style="
                        margin:5px 0 0;
                        font-size:13px;
                        color:#64748b;
                    ">
                    Create a new learning and development course
                </p>
            </div>

            <button type="button" onclick="closeCreateCourseModal()" style="
                    width:38px;
                    height:38px;
                    border:none;
                    border-radius:10px;
                    background:#f8fafc;
                    color:#64748b;
                    cursor:pointer;
                    font-size:16px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    transition:.2s;
                " onmouseover="this.style.background='#f1f5f9';this.style.color='#0f172a'"
                onmouseout="this.style.background='#f8fafc';this.style.color='#64748b'">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <?php require __DIR__ . '/step-indicator.php'; ?>


        <!-- =====================================================
             FORM CONTENT
        ====================================================== -->
        <form id="createCourseForm" style="
        display:flex;
        flex-direction:column;
        min-height:0;
        flex:1;
    " method="POST" action="index.php?url=admin-store-course" enctype="multipart/form-data">


            <div style="
                    padding:26px;
                    overflow-y:auto;
                    flex:1;
                ">

                <!-- =================================================
                     STEP 1 — BASIC INFORMATION
                ================================================== -->
                <?php require __DIR__ . '/create-modal/step-1.php'; ?>


                <!-- =================================================
                    STEP 2 — INSTRUCTORS
                ================================================== -->
                <?php require __DIR__ . '/create-modal/step-2.php'; ?>


                <!-- =================================================
                     STEP 3 — SKILLS
                ================================================== -->
                <?php require __DIR__ . '/create-modal/step-3.php'; ?>



                <!-- =================================================
                     STEP 4 — LESSONS
                ================================================== -->
                <?php require __DIR__ . '/create-modal/step-4.php'; ?>


            </div>


            <!-- =====================================================
                 FOOTER
            ====================================================== -->
            <?php require __DIR__ . '/create-modal/footer.php'; ?>


        </form>

    </div>

</div>

<script>

    let currentCourseStep = 1;
    let lessonIndex = 1;


    /* =========================================================
       STEP NAVIGATION
    ========================================================= */

    function nextCourseStep() {

        /* =====================================================
           STEP 1 → 2 → 3 → 4
        ===================================================== */

        if (currentCourseStep < 4) {

            currentCourseStep++;

            updateCourseStep();
            updateCourseStepIndicator(currentCourseStep);

            return;
        }


        /* =====================================================
           STEP 4 → CREATE COURSE
        ===================================================== */

        const form = document.getElementById('createCourseForm');

        if (!form) {
            console.error('Create course form not found.');
            return;
        }


        /* =====================================================
           CHECK REQUIRED INPUTS
        ===================================================== */

        if (!form.checkValidity()) {

            // Show custom reminder
            const message =
                document.getElementById('courseValidationMessage');

            if (message) {
                message.style.display = 'flex';

                message.innerHTML = `
                <i class="fa-solid fa-circle-exclamation"></i>
                <div>
                    <strong>Please complete all required fields.</strong>
                    <div style="
                        margin-top:2px;
                        font-size:11px;
                        color:#b91c1c;
                    ">
                        Some required information is missing.
                        The course cannot be created yet.
                    </div>
                </div>
            `;
            }

            // Browser highlights/focuses the first invalid input
            form.reportValidity();

            return;
        }


        /* =====================================================
           VALID
        ===================================================== */

        const message =
            document.getElementById('courseValidationMessage');

        if (message) {
            message.style.display = 'none';
        }


        /* =====================================================
           SUBMIT FORM
        ===================================================== */

        form.requestSubmit();

    }

    function previousCourseStep() {

        if (currentCourseStep > 1) {

            currentCourseStep--;

            updateCourseStep();
            updateCourseStepIndicator(currentCourseStep);

        }

    }


    /* =========================================================
       UPDATE COURSE STEP
    ========================================================= */

    function updateCourseStep() {

        /* -----------------------------------------
           Show current form step
        ----------------------------------------- */

        document
            .querySelectorAll('.course-form-step')
            .forEach(step => {

                step.style.display =
                    Number(step.dataset.formStep) === currentCourseStep
                        ? 'block'
                        : 'none';

            });


        /* -----------------------------------------
           Update step indicator
        ----------------------------------------- */

        document
            .querySelectorAll('.course-step')
            .forEach(step => {

                const number = Number(step.dataset.step);

                const circle = step.querySelector('.step-circle');

                const title =
                    step.querySelector('div:nth-child(2) div:first-child');


                if (number === currentCourseStep) {

                    // CURRENT
                    circle.style.background = '#2563eb';
                    circle.style.color = '#ffffff';
                    circle.style.borderColor = '#2563eb';
                    circle.style.boxShadow = '0 0 0 4px #eff6ff';

                    if (title) {
                        title.style.color = '#2563eb';
                    }

                } else if (number < currentCourseStep) {

                    // COMPLETED
                    circle.style.background = '#dbeafe';
                    circle.style.color = '#2563eb';
                    circle.style.borderColor = '#bfdbfe';
                    circle.style.boxShadow = 'none';

                    if (title) {
                        title.style.color = '#2563eb';
                    }

                } else {

                    // UPCOMING
                    circle.style.background = '#f1f5f9';
                    circle.style.color = '#94a3b8';
                    circle.style.borderColor = '#e2e8f0';
                    circle.style.boxShadow = 'none';

                    if (title) {
                        title.style.color = '#64748b';
                    }

                }

            });


        /* -----------------------------------------
           Back button
        ----------------------------------------- */

        const backButton =
            document.getElementById('courseBackButton');

        if (backButton) {

            backButton.style.display =
                currentCourseStep === 1
                    ? 'none'
                    : 'inline-flex';

        }


        /* -----------------------------------------
           Next / Create button
        ----------------------------------------- */

        const nextButton =
            document.getElementById('courseNextButton');

        if (!nextButton) {
            return;
        }


        if (currentCourseStep === 4) {

            nextButton.innerHTML =
                '<i class="fa-solid fa-check"></i><span>Create Course</span>';

            nextButton.type = 'button';

        } else {

            nextButton.innerHTML =
                '<span>Next</span><i class="fa-solid fa-arrow-right"></i>';

            nextButton.type = 'button';

        }

    }


    /* =========================================================
       STEP INDICATOR "1 OF 4"
    ========================================================= */

    function updateCourseStepIndicator(step) {

        const indicator =
            document.getElementById('currentCourseStep');

        if (indicator) {
            indicator.textContent = step;
        }

    }


    /* =========================================================
       OPEN MODAL
    ========================================================= */

    function openCreateCourseModal() {

        const modal =
            document.getElementById('createCourseModal');

        modal.style.display = 'flex';

        document.body.style.overflow = 'hidden';

        currentCourseStep = 1;

        updateCourseStep();
        updateCourseStepIndicator(1);

    }


    /* =========================================================
       CLOSE MODAL
    ========================================================= */

    function closeCreateCourseModal() {

        document
            .getElementById('createCourseModal')
            .style.display = 'none';

        document.body.style.overflow = '';

    }

    /* =========================================================
   CO-INSTRUCTORS
========================================================= */

    let coInstructorIndex = 0;


    function addCoInstructor() {

        const owner = document.getElementById('courseOwner');
        const container = document.getElementById('coInstructorList');
        const emptyMessage = document.getElementById('noCoInstructorMessage');

        if (!owner || !container) {
            return;
        }


        /* Owner must be selected first */
        if (!owner.value) {

            alert('Please select a Course Owner first.');

            owner.focus();

            return;
        }


        /* Remove empty message */
        if (emptyMessage) {
            emptyMessage.remove();
        }


        const wrapper = document.createElement('div');

        wrapper.className = 'co-instructor-item';

        wrapper.style.cssText = `
        display:flex;
        align-items:center;
        gap:10px;
        margin-bottom:10px;
    `;


        wrapper.innerHTML = `

        <select
            name="co_instructors[]"
            style="
                flex:1;
                height:42px;
                padding:0 12px;
                border:1px solid #cbd5e1;
                border-radius:9px;
                background:#fff;
                color:#0f172a;
                font-size:13px;
                box-sizing:border-box;
            "
        >

            <option value="">Select co-instructor</option>

            <?php for ($i = 1; $i <= 10; $i++): ?>

                <option value="<?= $i ?>">
                    Instructor <?= $i ?>
                </option>

            <?php endfor; ?>

        </select>


        <button
            type="button"
            onclick="removeCoInstructor(this)"
            style="
                width:42px;
                height:42px;
                border:1px solid #fecaca;
                border-radius:9px;
                background:#fff;
                color:#ef4444;
                cursor:pointer;
                display:flex;
                align-items:center;
                justify-content:center;
                flex-shrink:0;
            "
            title="Remove instructor"
        >
            <i class="fa-solid fa-trash"></i>
        </button>

    `;


        container.appendChild(wrapper);

        coInstructorIndex++;

    }


    function removeCoInstructor(button) {

        const item = button.closest('.co-instructor-item');

        if (item) {
            item.remove();
        }


        const container = document.getElementById('coInstructorList');

        if (
            container &&
            container.querySelectorAll('.co-instructor-item').length === 0
        ) {

            const emptyMessage = document.createElement('div');

            emptyMessage.id = 'noCoInstructorMessage';

            emptyMessage.style.cssText = `
            padding:12px;
            border:1px dashed #cbd5e1;
            border-radius:10px;
            background:#f8fafc;
            color:#94a3b8;
            font-size:12px;
            text-align:center;
        `;

            emptyMessage.textContent = 'No co-instructors added.';

            container.appendChild(emptyMessage);

        }

    }
    function addCourseLesson() {

        const container = document.getElementById('courseLessons');

        const lesson = document.createElement('div');

        lesson.className = 'course-lesson';

        lesson.style.cssText = `
        padding:16px;
        border:1px solid #e2e8f0;
        border-radius:12px;
        margin-bottom:12px;
    `;


        lesson.innerHTML = `

        <div style="
            display:grid;
            grid-template-columns:1fr 150px auto;
            gap:12px;
            align-items:end;
        ">

            <!-- LESSON TITLE -->
            <div>

                <label style="
                    display:block;
                    margin-bottom:6px;
                    font-size:12px;
                    font-weight:600;
                    color:#475569;
                ">
                    Lesson Title
                </label>

                <input
                    type="text"
                    name="lessons[${lessonIndex}][title]"
                    placeholder="Lesson title"
                    required
                    style="
                        width:100%;
                        height:42px;
                        padding:0 12px;
                        border:1px solid #cbd5e1;
                        border-radius:9px;
                        box-sizing:border-box;
                    "
                >

            </div>


            <!-- DURATION -->
            <div>

                <label style="
                    display:block;
                    margin-bottom:6px;
                    font-size:12px;
                    font-weight:600;
                    color:#475569;
                ">
                    Duration
                </label>

                <select
                    name="lessons[${lessonIndex}][duration_minutes]"
                    required
                    style="
                        width:100%;
                        height:42px;
                        padding:0 12px;
                        border:1px solid #cbd5e1;
                        border-radius:9px;
                        background:#fff;
                        color:#334155;
                        font-size:13px;
                        box-sizing:border-box;
                    "
                >

                    <option value="">Select duration</option>

                    <option value="5">5 minutes</option>
                    <option value="10">10 minutes</option>
                    <option value="15">15 minutes</option>
                    <option value="20">20 minutes</option>
                    <option value="30">30 minutes</option>
                    <option value="45">45 minutes</option>

                    <option value="60">1 hour</option>
                    <option value="90">1 hour 30 minutes</option>
                    <option value="120">2 hours</option>
                    <option value="150">2 hours 30 minutes</option>
                    <option value="180">3 hours</option>
                    <option value="240">4 hours</option>
                    <option value="300">5 hours</option>
                    <option value="360">6 hours</option>
                    <option value="480">8 hours</option>

                </select>

            </div>


            <!-- REMOVE LESSON -->
            <button
                type="button"
                onclick="this.closest('.course-lesson').remove()"
                style="
                    width:42px;
                    height:42px;
                    border:1px solid #fecaca;
                    border-radius:9px;
                    background:#fff;
                    color:#ef4444;
                    cursor:pointer;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                "
                title="Remove lesson"
            >
                <i class="fa-solid fa-trash"></i>
            </button>

        </div>

    `;


        container.appendChild(lesson);

        lessonIndex++;
    }
</script>


<style>
    /* =========================================================
   CREATE COURSE RESPONSIVE
========================================================= */

    @media (max-width: 700px) {

        #createCourseModal {
            padding: 10px !important;
        }

        #createCourseModal>div {
            max-height: 96vh !important;
            border-radius: 16px !important;
        }

        #createCourseModal form>div:first-child {
            padding: 20px !important;
        }

        #createCourseModal form>div:last-child {
            padding: 14px 20px !important;
        }

        .course-form-step [style*="grid-template-columns:repeat(2"] {
            grid-template-columns: 1fr !important;
        }

        .course-form-step [style*="grid-template-columns:1fr 150px"] {
            grid-template-columns: 1fr !important;
        }

        .course-lesson>div {
            grid-template-columns: 1fr !important;
        }

    }

    @media (max-width: 560px) {

        #createCourseModal>div {
            width: 100% !important;
        }

        #createCourseModal [style*="min-width:600px"] {
            min-width: 560px !important;
        }

        #createCourseModal h2 {
            font-size: 18px !important;
        }

    }
</style>