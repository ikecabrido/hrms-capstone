<div class="course-form-step" data-form-step="1">

    <div style="margin-bottom:22px;">

        <h3 style="
                                margin:0;
                                font-size:17px;
                                font-weight:700;
                                color:#0f172a;
                            ">
            Basic Information
        </h3>

        <p style="
                                margin:5px 0 0;
                                font-size:13px;
                                color:#64748b;
                            ">
            Provide the basic information about your course.
        </p>

    </div>


    <!-- Course Title -->
    <div style="margin-bottom:20px;">

        <label style="
        display:block;
        margin-bottom:7px;
        font-size:13px;
        font-weight:600;
        color:#334155;
    ">
            Course Title
            <span style="color:#ef4444;">*</span>
        </label>

        <input type="text" name="title" list="courseTitleSuggestions"
            placeholder="e.g. Effective Workplace Communication" required style="
            width:100%;
            height:46px;
            padding:0 14px;
            border:1px solid #cbd5e1;
            border-radius:10px;
            background:#fff;
            color:#0f172a;
            font-size:14px;
            outline:none;
            box-sizing:border-box;
        ">

        <datalist id="courseTitleSuggestions">

            <option value="Effective Workplace Communication">
            <option value="Leadership and Team Management">
            <option value="Time Management and Productivity">
            <option value="Professional Customer Service">
            <option value="Problem Solving and Decision Making">
            <option value="Digital Literacy in the Workplace">
            <option value="Information Security Awareness">
            <option value="Workplace Ethics and Professionalism">
            <option value="Stress Management and Employee Well-Being">
            <option value="Data Analysis Fundamentals">

        </datalist>

    </div>


    <!-- Category -->
    <div style="margin-bottom:20px;">

        <label style="
                                display:block;
                                margin-bottom:7px;
                                font-size:13px;
                                font-weight:600;
                                color:#334155;
                            ">
            Category
            <span style="color:#ef4444;">*</span>
        </label>

        <select name="category" required style="
                                width:100%;
                                height:46px;
                                padding:0 14px;
                                border:1px solid #cbd5e1;
                                border-radius:10px;
                                background:#fff;
                                color:#0f172a;
                                font-size:14px;
                                outline:none;
                                box-sizing:border-box;
                            ">
            <option value="">Select category</option>
            <option value="Communication">Communication</option>
            <option value="Leadership">Leadership</option>
            <option value="Productivity">Productivity</option>
            <option value="Technical Skills">Technical Skills</option>
            <option value="Compliance">Compliance</option>
            <option value="Customer Service">Customer Service</option>
            <option value="Team Development">Team Development</option>
            <option value="Information Security">Information Security</option>
            <option value="Well-Being">Well-Being</option>
            <option value="Professional Development">
                Professional Development
            </option>
        </select>

    </div>


    <!-- Description -->
    <div style="margin-bottom:20px;">

        <label style="
                                display:block;
                                margin-bottom:7px;
                                font-size:13px;
                                font-weight:600;
                                color:#334155;
                            ">
            Description
            <span style="color:#ef4444;">*</span>
        </label>

        <textarea name="description" rows="2" placeholder="Describe what employees will learn from this course..."
            required style="
                                width:100%;
                                padding:12px 14px;
                                border:1px solid #cbd5e1;
                                border-radius:10px;
                                background:#fff;
                                color:#0f172a;
                                font-size:14px;
                                resize:vertical;
                                outline:none;
                                box-sizing:border-box;
                                font-family:inherit;
                            "></textarea>

    </div>

    <?php
    $today = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime('+3 months'));
    $enrollmentDeadline = date('Y-m-d', strtotime('+2 months'));
    ?>

    <!-- Dates -->
    <div style="
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:16px;
">

        <!-- Start Date -->
        <div>

            <label style="
            display:block;
            margin-bottom:7px;
            font-size:13px;
            font-weight:600;
            color:#334155;
        ">
                Start Date
            </label>

            <input type="date" name="start_date" value="<?= $startDate ?>"
                min="<?= date('Y-m-d', strtotime('+3 months')) ?>" style="
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

        </div>


        <!-- Enrollment Deadline -->
        <div>

            <label style="
            display:block;
            margin-bottom:7px;
            font-size:13px;
            font-weight:600;
            color:#334155;
        ">
                Enrollment Deadline
            </label>

            <input type="date" name="enrollment_deadline" value="<?= $enrollmentDeadline ?>" min="<?= $today ?>"
                max="<?= date('Y-m-d', strtotime('-1 day', strtotime($startDate))) ?>" style="
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

        </div>

    </div>

    <div style="margin-bottom:20px;">

        <label style="
        display:block;
        margin-bottom:7px;
        font-size:13px;
        font-weight:600;
        color:#334155;
    ">
            Course Thumbnail
        </label>

        <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp" style="
            width:100%;
            padding:10px 12px;
            border:1px solid #cbd5e1;
            border-radius:10px;
            background:#fff;
            color:#334155;
            font-size:13px;
            box-sizing:border-box;
        ">

        <div style="
        margin-top:5px;
        font-size:11px;
        color:#94a3b8;
    ">
            JPG, PNG, or WEBP. Maximum file size: 5 MB.
        </div>

    </div>
</div>