<div id="courseValidationMessage" class="mt-2" style="
        display:none;
        align-items:flex-start;
        gap:10px;
        margin:0 26px 16px;
        padding:12px 14px;
        border:1px solid #fecaca;
        border-radius:10px;
        background:#fef2f2;
        color:#dc2626;
        font-size:12px;
        line-height:1.5;
        box-sizing:border-box;
    ">
    <i class="fa-solid fa-circle-exclamation" style="
            margin-top:2px;
            font-size:14px;
        "></i>

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
</div>


<div style="
    width:100%;
    padding:18px 24px 20px;
    background:#ffffff;
    border-bottom:1px solid #e2e8f0;
    box-sizing:border-box;
    overflow-x:auto;
">

    <!-- Indicator Header -->
    <div style="
        width:100%;
        max-width:900px;
        margin:0 auto 16px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
    ">

        <div style="
            display:flex;
            align-items:center;
            gap:8px;
        ">
            <div style="
                width:28px;
                height:28px;
                border-radius:8px;
                background:#eff6ff;
                color:#2563eb;
                display:flex;
                align-items:center;
                justify-content:center;
                font-size:12px;
            ">
                <i class="fa-solid fa-list-check"></i>
            </div>

            <div>
                <div style="
                    font-size:12px;
                    font-weight:700;
                    color:#0f172a;
                    line-height:1.2;
                ">
                    Create Course
                </div>

                <div style="
                    margin-top:2px;
                    font-size:10px;
                    color:#94a3b8;
                ">
                    Complete each step to create your course
                </div>
            </div>
        </div>

        <div style="
            padding:5px 10px;
            border-radius:999px;
            background:#f8fafc;
            border:1px solid #e2e8f0;
            color:#64748b;
            font-size:10px;
            font-weight:600;
            white-space:nowrap;
        ">
            Step <span id="currentCourseStep">1</span> of 4
        </div>

    </div>


    <!-- Progress Steps -->
    <div style="
        width:100%;
        max-width:900px;
        min-width:680px;
        margin:0 auto;
        display:flex;
        align-items:center;
    ">


        <!-- =================================================
             STEP 1
        ================================================== -->
        <div class="course-step active" data-step="1" style="
                display:flex;
                align-items:center;
                gap:9px;
                flex:1;
                min-width:140px;
                box-sizing:border-box;
            ">

            <div class="step-circle" style="
                    width:36px;
                    height:36px;
                    min-width:36px;
                    border-radius:50%;
                    background:#2563eb;
                    color:#ffffff;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    font-size:12px;
                    font-weight:700;
                    border:1px solid #2563eb;
                    box-shadow:0 0 0 4px #eff6ff;
                    box-sizing:border-box;
                ">
                1
            </div>

            <div style="
                min-width:0;
                overflow:hidden;
            ">
                <div style="
                    font-size:12px;
                    font-weight:700;
                    color:#2563eb;
                    line-height:1.3;
                    white-space:nowrap;
                ">
                    Basic Info
                </div>

                <div style="
                    margin-top:3px;
                    font-size:10px;
                    color:#94a3b8;
                    line-height:1.3;
                    white-space:nowrap;
                ">
                    Course details
                </div>
            </div>

        </div>


        <!-- CONNECTOR -->
        <div style="
            width:45px;
            min-width:45px;
            height:2px;
            background:#e2e8f0;
            border-radius:999px;
        "></div>


        <!-- =================================================
             STEP 2
        ================================================== -->
        <div class="course-step" data-step="2" style="
                display:flex;
                align-items:center;
                gap:9px;
                flex:1;
                min-width:140px;
                box-sizing:border-box;
            ">

            <div class="step-circle" style="
                    width:36px;
                    height:36px;
                    min-width:36px;
                    border-radius:50%;
                    background:#f8fafc;
                    color:#64748b;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    font-size:12px;
                    font-weight:700;
                    border:1px solid #e2e8f0;
                    box-sizing:border-box;
                ">
                2
            </div>

            <div style="
                min-width:0;
                overflow:hidden;
            ">
                <div style="
                    font-size:12px;
                    font-weight:700;
                    color:#64748b;
                    line-height:1.3;
                    white-space:nowrap;
                ">
                    Instructors
                </div>

                <div style="
                    margin-top:3px;
                    font-size:10px;
                    color:#94a3b8;
                    line-height:1.3;
                    white-space:nowrap;
                ">
                    Assign instructors
                </div>
            </div>

        </div>


        <!-- CONNECTOR -->
        <div style="
            width:45px;
            min-width:45px;
            height:2px;
            background:#e2e8f0;
            border-radius:999px;
        "></div>


        <!-- =================================================
             STEP 3
        ================================================== -->
        <div class="course-step" data-step="3" style="
                display:flex;
                align-items:center;
                gap:9px;
                flex:1;
                min-width:140px;
                box-sizing:border-box;
            ">

            <div class="step-circle" style="
                    width:36px;
                    height:36px;
                    min-width:36px;
                    border-radius:50%;
                    background:#f8fafc;
                    color:#64748b;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    font-size:12px;
                    font-weight:700;
                    border:1px solid #e2e8f0;
                    box-sizing:border-box;
                ">
                3
            </div>

            <div style="
                min-width:0;
                overflow:hidden;
            ">
                <div style="
                    font-size:12px;
                    font-weight:700;
                    color:#64748b;
                    line-height:1.3;
                    white-space:nowrap;
                ">
                    Skills
                </div>

                <div style="
                    margin-top:3px;
                    font-size:10px;
                    color:#94a3b8;
                    line-height:1.3;
                    white-space:nowrap;
                ">
                    Course skills
                </div>
            </div>

        </div>


        <!-- CONNECTOR -->
        <div style="
            width:45px;
            min-width:45px;
            height:2px;
            background:#e2e8f0;
            border-radius:999px;
        "></div>


        <!-- =================================================
             STEP 4
        ================================================== -->
        <div class="course-step" data-step="4" style="
                display:flex;
                align-items:center;
                gap:9px;
                flex:1;
                min-width:140px;
                box-sizing:border-box;
            ">

            <div class="step-circle" style="
                    width:36px;
                    height:36px;
                    min-width:36px;
                    border-radius:50%;
                    background:#f8fafc;
                    color:#64748b;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    font-size:12px;
                    font-weight:700;
                    border:1px solid #e2e8f0;
                    box-sizing:border-box;
                ">
                4
            </div>

            <div style="
                min-width:0;
                overflow:hidden;
            ">
                <div style="
                    font-size:12px;
                    font-weight:700;
                    color:#64748b;
                    line-height:1.3;
                    white-space:nowrap;
                ">
                    Lessons
                </div>

                <div style="
                    margin-top:3px;
                    font-size:10px;
                    color:#94a3b8;
                    line-height:1.3;
                    white-space:nowrap;
                ">
                    Course content
                </div>
            </div>

        </div>

    </div>

</div>