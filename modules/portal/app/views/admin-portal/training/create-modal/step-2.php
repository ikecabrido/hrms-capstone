<div class="course-form-step" data-form-step="2" style="display:none;">

    <h3 style="margin:0;font-size:17px;font-weight:700;color:#0f172a;">
        Course Instructors
    </h3>

    <p style="margin:5px 0 22px;font-size:13px;color:#64748b;">
        Assign the employees or trainers responsible for this course.
    </p>

    <!-- COURSE OWNER -->
    <div style="padding:18px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">

        <label style="display:block;margin-bottom:7px;font-size:13px;font-weight:600;color:#334155;">
            Course Owner
            <span style="color:#ef4444;">*</span>
        </label>

        <select
            name="instructor_id"
            id="courseOwner"
            required
            style="
                width:100%;
                height:46px;
                padding:0 14px;
                border:1px solid #cbd5e1;
                border-radius:10px;
                background:#fff;
                color:#0f172a;
                font-size:14px;
                box-sizing:border-box;
            "
        >
            <option value="">Select course owner</option>

            <?php foreach ($showInstructorsCourse as $instructor): ?>

                <option value="<?= (int) $instructor['employee_id'] ?>">
                    <?= htmlspecialchars($instructor['instructor_name'], ENT_QUOTES, 'UTF-8') ?>
                </option>

            <?php endforeach; ?>

        </select>

    </div>

    <!-- INFO -->
    <div
        style="
            margin-top:18px;
            padding:13px 15px;
            border-radius:10px;
            background:#eff6ff;
            color:#1e40af;
            font-size:12px;
            line-height:1.6;
            display:flex;
            align-items:flex-start;
            gap:8px;
        "
    >
        <i class="fa-solid fa-circle-info" style="margin-top:2px;"></i>

        <span>
            The selected course owner will be saved with the
            <strong>owner</strong> role.
        </span>
    </div>

</div>


<script>

    /*
     * IMPORTANT:
     * Use the SAME PHP variable that contains your 39 employees.
     */
    const instructors = <?= json_encode(
        $showInstructorsCourse ?? [],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT
    ) ?>;


    const addCoInstructorButton =
        document.getElementById('addCoInstructorButton');


    if (addCoInstructorButton) {

        addCoInstructorButton.addEventListener(
            'click',
            addCoInstructor
        );

    }


    function addCoInstructor() {

        const list =
            document.getElementById('coInstructorList');

        const owner =
            document.getElementById('courseOwner');

        const emptyMessage =
            document.getElementById('noCoInstructorMessage');


        if (!list || !owner) {
            return;
        }


        /*
         * Course owner must be selected first.
         */
        if (!owner.value) {

            alert('Please select a course owner first.');

            return;

        }


        /*
         * Remove "No co-instructors added."
         */
        if (emptyMessage) {

            emptyMessage.remove();

        }


        /*
         * Create row
         */
        const row =
            document.createElement('div');

        row.className =
            'co-instructor-row';


        row.style.cssText = `
            display:flex;
            align-items:center;
            gap:10px;
            margin-bottom:10px;
        `;


        /*
         * Create select
         */
        const select =
            document.createElement('select');


        select.name =
            'co_instructors[]';


        select.required = false;


        select.style.cssText = `
            flex:1;
            height:44px;
            padding:0 12px;
            border:1px solid #cbd5e1;
            border-radius:10px;
            background:#fff;
            color:#0f172a;
            font-size:13px;
            box-sizing:border-box;
        `;


        /*
         * Default option
         */
        const defaultOption =
            document.createElement('option');

        defaultOption.value = '';

        defaultOption.textContent =
            'Select co-instructor';

        select.appendChild(defaultOption);


        /*
         * Add employees
         */
        instructors.forEach(function (instructor) {

            /*
             * Do not allow the course owner
             * to appear as a co-instructor.
             */
            if (
                String(instructor.employee_id) ===
                String(owner.value)
            ) {

                return;

            }


            const option =
                document.createElement('option');


            option.value =
                instructor.employee_id;


            option.textContent =
                instructor.instructor_name;


            select.appendChild(option);

        });


        /*
         * Remove button
         */
        const removeButton =
            document.createElement('button');


        removeButton.type =
            'button';


        removeButton.innerHTML =
            '<i class="fa-solid fa-trash"></i>';


        removeButton.style.cssText = `
            width:40px;
            height:40px;
            flex:0 0 40px;
            border:1px solid #fecaca;
            border-radius:9px;
            background:#fef2f2;
            color:#dc2626;
            cursor:pointer;
            display:inline-flex;
            align-items:center;
            justify-content:center;
        `;


        removeButton.addEventListener(
            'click',
            function () {

                row.remove();

                if (
                    list.querySelectorAll(
                        '.co-instructor-row'
                    ).length === 0
                ) {

                    showNoCoInstructorMessage();

                }

            }
        );


        row.appendChild(select);

        row.appendChild(removeButton);

        list.appendChild(row);

    }


    function showNoCoInstructorMessage() {

        const list =
            document.getElementById(
                'coInstructorList'
            );


        if (!list) {
            return;
        }


        if (
            document.getElementById(
                'noCoInstructorMessage'
            )
        ) {

            return;

        }


        const message =
            document.createElement('div');


        message.id =
            'noCoInstructorMessage';


        message.style.cssText = `
            padding:12px;
            border:1px dashed #cbd5e1;
            border-radius:10px;
            background:#f8fafc;
            color:#94a3b8;
            font-size:12px;
            text-align:center;
        `;


        message.textContent =
            'No co-instructors added.';


        list.appendChild(message);

    }

</script>