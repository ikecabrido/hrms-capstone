<div class="course-form-step" data-form-step="3" style="display:none;">

    <h3 style="margin:0;font-size:17px;font-weight:700;color:#0f172a;">
        Course Skills
    </h3>

    <p style="margin:5px 0 22px;font-size:13px;color:#64748b;">
        Select the skills employees are expected to develop.
    </p>

    <div style="
                            display:grid;
                            grid-template-columns:repeat(2,minmax(0,1fr));
                            gap:10px;
                        ">

        <?php
        $skills = [
            'Communication',
            'Leadership',
            'Time Management',
            'Problem Solving',
            'Teamwork',
            'Critical Thinking',
            'Customer Service',
            'Data Analysis',
            'Digital Literacy',
            'Decision Making'
        ];
        ?>

        <?php foreach ($skills as $index => $skill): ?>

            <label style="
                                    display:flex;
                                    align-items:center;
                                    gap:10px;
                                    padding:13px;
                                    border:1px solid #e2e8f0;
                                    border-radius:10px;
                                    cursor:pointer;
                                    background:#fff;
                                ">

                <input type="checkbox" name="skills[]" value="<?= $index + 1 ?>">

                <span style="
                                        font-size:13px;
                                        color:#334155;
                                        font-weight:500;
                                    ">
                    <?= htmlspecialchars($skill) ?>
                </span>

            </label>

        <?php endforeach; ?>

    </div>

</div>