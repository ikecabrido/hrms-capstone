<div class="course-form-step" data-form-step="4" style="display:none;">

    <div style="
                            display:flex;
                            justify-content:space-between;
                            align-items:center;
                            gap:15px;
                            margin-bottom:18px;
                        ">

        <div>

            <h3 style="margin:0;font-size:17px;font-weight:700;color:#0f172a;">
                Course Lessons
            </h3>

            <p style="margin:5px 0 0;font-size:13px;color:#64748b;">
                Add the lessons included in this course.
            </p>

        </div>

        <button type="button" onclick="addCourseLesson()" style="
                                height:38px;
                                padding:0 13px;
                                border:1px solid #bfdbfe;
                                border-radius:9px;
                                background:#eff6ff;
                                color:#2563eb;
                                font-size:12px;
                                font-weight:600;
                                cursor:pointer;
                                white-space:nowrap;
                            ">
            <i class="fa-solid fa-plus"></i>
            Add Lesson
        </button>

    </div>


    <div id="courseLessons">

        <div class="course-lesson" style="
                                padding:16px;
                                border:1px solid #e2e8f0;
                                border-radius:12px;
                                margin-bottom:12px;
                            ">

            <div style="
                                    display:grid;
                                    grid-template-columns:1fr 150px;
                                    gap:12px;
                                ">

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

                    <input type="text" name="lessons[0][title]" placeholder="e.g. Communication Fundamentals" style="
                                            width:100%;
                                            height:42px;
                                            padding:0 12px;
                                            border:1px solid #cbd5e1;
                                            border-radius:9px;
                                            box-sizing:border-box;
                                        ">

                </div>


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

                    <select name="lessons[${lessonIndex}][duration_minutes]" required style="
        width:100%;
        height:42px;
        padding:0 12px;
        border:1px solid #cbd5e1;
        border-radius:9px;
        background:#ffffff;
        color:#334155;
        font-size:13px;
        cursor:pointer;
        box-sizing:border-box;
        outline:none;
    ">
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

            </div>

        </div>

    </div>

</div>