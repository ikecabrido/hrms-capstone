<div class="course-form-step" data-form-step="2" style="display:none;">

    <h3 style="
        margin:0;
        font-size:17px;
        font-weight:700;
        color:#0f172a;
    ">
        Course Instructors
    </h3>

    <p style="
        margin:5px 0 22px;
        font-size:13px;
        color:#64748b;
    ">
        Assign the employees or trainers responsible for this course.
    </p>


    <!-- COURSE OWNER -->
    <div style="
        padding:18px;
        border:1px solid #e2e8f0;
        border-radius:12px;
        background:#f8fafc;
    ">

        <label style="
            display:block;
            margin-bottom:7px;
            font-size:13px;
            font-weight:600;
            color:#334155;
        ">
            Course Owner
            <span style="color:#ef4444;">*</span>
        </label>

        <select name="instructor_id" id="courseOwner" required style="
                width:100%;
                height:46px;
                padding:0 14px;
                border:1px solid #cbd5e1;
                border-radius:10px;
                background:#fff;
                color:#0f172a;
                font-size:14px;
                box-sizing:border-box;
            ">

            <option value="">Select course owner</option>

            <?php for ($i = 1; $i <= 10; $i++): ?>

                <option value="<?= $i ?>">
                    Instructor <?= $i ?>
                </option>

            <?php endfor; ?>

        </select>

    </div>


    <!-- CO-INSTRUCTORS -->
    <div style="
        margin-top:18px;
        padding:18px;
        border:1px solid #e2e8f0;
        border-radius:12px;
        background:#ffffff;
    ">

        <div style="
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            margin-bottom:12px;
        ">

            <div>

                <div style="
                    font-size:13px;
                    font-weight:700;
                    color:#334155;
                ">
                    Co-Instructors
                </div>

                <div style="
                    margin-top:3px;
                    font-size:11px;
                    color:#94a3b8;
                ">
                    Optional additional instructors
                </div>

            </div>


            <button type="button" id="addCoInstructorButton" onclick="addCoInstructor()" style="
                    height:36px;
                    padding:0 12px;
                    border:1px solid #bfdbfe;
                    border-radius:9px;
                    background:#eff6ff;
                    color:#2563eb;
                    font-size:12px;
                    font-weight:600;
                    cursor:pointer;
                    display:inline-flex;
                    align-items:center;
                    gap:6px;
                    white-space:nowrap;
                ">
                <i class="fa-solid fa-plus"></i>
                Add Instructor
            </button>

        </div>


        <!-- CO-INSTRUCTOR LIST -->
        <div id="coInstructorList">

            <div style="
                padding:12px;
                border:1px dashed #cbd5e1;
                border-radius:10px;
                background:#f8fafc;
                color:#94a3b8;
                font-size:12px;
                text-align:center;
            " id="noCoInstructorMessage">

                No co-instructors added.

            </div>

        </div>

    </div>


    <!-- INFORMATION -->
    <div style="
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
    ">

        <i class="fa-solid fa-circle-info" style="margin-top:2px;"></i>

        <span>
            The selected course owner will be saved with the
            <strong>owner</strong> role. Additional instructors will be
            saved as <strong>co-instructors</strong>.
        </span>

    </div>

</div>