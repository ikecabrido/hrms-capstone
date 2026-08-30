<?php
try {
    require_once dirname(__DIR__, 5) . '/database/db.php';
    $pdo = (new Database())->getConnection();
    $allSkills = $pdo->query("SELECT id, name FROM ld_skill WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $allSkills = [];
}
?>
<style>
.course-template-btn {
    padding: 0.35rem 0.75rem;
    border: 1.5px solid rgba(32, 0, 130, 0.18);
    border-radius: 999px;
    background: rgba(32, 0, 130, 0.05);
    color: var(--primary);
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}
.course-template-btn:hover {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary);
}
</style>
<div class="module-content">
    <!-- Add Module Modal -->
    <div id="add-module-modal" class="modal-overlay" style="display:none; z-index:2000; ">
        <div style="background:#fff; border:1px solid rgba(32, 0, 130, 0.12); border-radius:18px; width:min(500px, 92vw); max-height:80vh; overflow-y:auto; box-shadow:0 18px 45px rgba(32, 0, 130, 0.18);">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32, 0, 130, 0.12); background:linear-gradient(135deg, rgba(32, 0, 130, 0.08), rgba(81, 70, 183, 0.05));">
                <h2 style="margin:0; font-size:1.1rem; color:var(--primary);">Add Module</h2>
                <button type="button" data-close-modal="add-module-modal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">✕</button>
            </div>
            <div style="padding:1.5rem;">
                <form id="add-module-in-modal-form">
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Module Title *</span>
                        <input type="text" name="title" required placeholder="e.g. Introduction to Basics" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Status</span>
                        <select name="status" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;">
                            <option value="active" selected>Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Lesson Body</span>
                        <textarea name="content_body" rows="4" placeholder="Write the lesson content here... (HTML supported)" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box; resize:vertical;"></textarea>
                    </label>
                    <div style="display:flex; gap:0.75rem;">
                        <button type="submit" style="flex:1; padding:0.8rem; background:var(--primary); color:var(--surface); border:none; border-radius:8px; cursor:pointer; font-weight:700;">Add Module</button>
                        <button type="button" data-close-modal="add-module-modal" style="flex:1; padding:0.8rem; background:rgba(32, 0, 130, 0.08); color:var(--primary); border:1px solid rgba(32, 0, 130, 0.18); border-radius:8px; cursor:pointer; font-weight:700;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="add-lesson-modal" class="modal-overlay" style="display:none; z-index:2000; ">
        <div style="background:#fff; border:1px solid rgba(32, 0, 130, 0.12); border-radius:18px; width:min(500px, 92vw); max-height:80vh; overflow-y:auto; box-shadow:0 18px 45px rgba(32, 0, 130, 0.18);">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32, 0, 130, 0.12); background:linear-gradient(135deg, rgba(32, 0, 130, 0.08), rgba(81, 70, 183, 0.05));">
                <h2 style="margin:0; font-size:1.1rem; color:var(--primary);">Add Lesson</h2>
                <button type="button" data-close-modal="add-lesson-modal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">✕</button>
            </div>
            <div style="padding:1.5rem;">
                <form id="add-lesson-in-modal-form">
                    <input type="hidden" name="module_id" id="add-lesson-module-id" />
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Lesson Title *</span>
                        <input type="text" name="title" required placeholder="e.g. Introduction to Basics" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Content Type</span>
                        <select name="content_type" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;">
                            <option value="text" selected>Text</option>
                            <option value="video">Video</option>
                            <option value="file">File</option>
                            <option value="mixed">Mixed</option>
                        </select>
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Status</span>
                        <select name="status" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;">
                            <option value="active" selected>Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Lesson Body</span>
                        <textarea name="content_body" rows="4" placeholder="Write the lesson content here... (HTML supported)" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box; resize:vertical;"></textarea>
                    </label>
                    <div style="display:flex; gap:0.75rem;">
                        <button type="submit" style="flex:1; padding:0.8rem; background:var(--primary); color:var(--surface); border:none; border-radius:8px; cursor:pointer; font-weight:700;">Add Lesson</button>
                        <button type="button" data-close-modal="add-lesson-modal" style="flex:1; padding:0.8rem; background:rgba(32, 0, 130, 0.08); color:var(--primary); border:1px solid rgba(32, 0, 130, 0.18); border-radius:8px; cursor:pointer; font-weight:700;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="add-quiz-modal" class="modal-overlay" style="display:none; z-index:2000; ">
        <div style="background:#fff; border:1px solid rgba(32, 0, 130, 0.12); border-radius:18px; width:min(500px, 92vw); max-height:80vh; overflow-y:auto; box-shadow:0 18px 45px rgba(32, 0, 130, 0.18);">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32, 0, 130, 0.12); background:linear-gradient(135deg, rgba(32, 0, 130, 0.08), rgba(81, 70, 183, 0.05));">
                <h2 style="margin:0; font-size:1.1rem; color:var(--primary);">Add Quiz</h2>
                <button type="button" data-close-modal="add-quiz-modal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">✕</button>
            </div>
            <div style="padding:1.5rem;">
                <form id="add-quiz-in-modal-form">
                    <input type="hidden" name="module_id" id="add-quiz-module-id" />
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Quiz Title *</span>
                        <input type="text" name="title" required placeholder="e.g. Final Knowledge Check" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Duration (seconds)</span>
                        <input type="number" name="duration_seconds" min="30" value="600" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Passing score</span>
                        <input type="number" name="passing_score" min="0" max="100" step="0.01" value="75" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Max attempts</span>
                        <input type="number" name="max_attempts" min="1" value="2" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Question count</span>
                        <input type="number" name="question_count" min="1" value="10" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Status</span>
                        <select name="status" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;">
                            <option value="active" selected>Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </label>
                    <label style="display:flex; align-items:center; gap:0.6rem; margin-bottom:1.5rem;">
                        <input type="checkbox" name="show_answers_after_submit" value="1" />
                        <span>Show answers after submit</span>
                    </label>
                    <div style="display:flex; gap:0.75rem;">
                        <button type="submit" style="flex:1; padding:0.8rem; background:var(--primary); color:var(--surface); border:none; border-radius:8px; cursor:pointer; font-weight:700;">Add Quiz</button>
                        <button type="button" data-close-modal="add-quiz-modal" style="flex:1; padding:0.8rem; background:rgba(32, 0, 130, 0.08); color:var(--primary); border:1px solid rgba(32, 0, 130, 0.18); border-radius:8px; cursor:pointer; font-weight:700;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="add-evaluation-modal" class="modal-overlay" style="display:none; z-index:2000; ">
        <div style="background:#fff; border:1px solid rgba(32, 0, 130, 0.12); border-radius:18px; width:min(500px, 92vw); max-height:80vh; overflow-y:auto; box-shadow:0 18px 45px rgba(32, 0, 130, 0.18);">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32, 0, 130, 0.12); background:linear-gradient(135deg, rgba(32, 0, 130, 0.08), rgba(81, 70, 183, 0.05));">
                <h2 style="margin:0; font-size:1.1rem; color:var(--primary);">Add Evaluation</h2>
                <button type="button" data-close-modal="add-evaluation-modal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">✕</button>
            </div>
            <div style="padding:1.5rem;">
                <form id="add-evaluation-in-modal-form">
                    <input type="hidden" name="course_id" id="add-evaluation-course-id" />
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Evaluation Title *</span>
                        <input type="text" name="title" required placeholder="e.g. Final Course Assessment" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Duration (seconds)</span>
                        <input type="number" name="duration_seconds" min="0" value="1800" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Passing score</span>
                        <input type="number" name="passing_score" min="0" max="100" step="0.01" value="75" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Max attempts</span>
                        <input type="number" name="max_attempts" min="1" value="2" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Question count</span>
                        <input type="number" name="question_count" min="1" value="10" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Status</span>
                        <select name="status" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;">
                            <option value="active" selected>Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </label>
                    <label style="display:flex; align-items:center; gap:0.6rem; margin-bottom:1.5rem;">
                        <input type="checkbox" name="show_answers_after_submit" value="1" />
                        <span>Show answers after submit</span>
                    </label>
                    <div style="display:flex; gap:0.75rem;">
                        <button type="submit" style="flex:1; padding:0.8rem; background:var(--primary); color:var(--surface); border:none; border-radius:8px; cursor:pointer; font-weight:700;">Add Evaluation</button>
                        <button type="button" data-close-modal="add-evaluation-modal" style="flex:1; padding:0.8rem; background:rgba(32, 0, 130, 0.08); color:var(--primary); border:1px solid rgba(32, 0, 130, 0.18); border-radius:8px; cursor:pointer; font-weight:700;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="toolbar" style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
        <a href="?page=instructor/elearning" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; background:var(--primary); color:#fff; border:none; border-radius:8px; text-decoration:none; font-size:0.85rem; font-weight:600; white-space:nowrap;"><i class="fas fa-arrow-left"></i> Back to E-Learning</a>
        <div class="toolbar-search" style="flex:1;">
            <input type="search" placeholder="Search courses..." aria-label="Search courses" id="course-search" />
        </div>
    </div>

    <div class="mode-card" style="margin-top:1.5rem;">
        <!-- Header with template presets -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
            <div>
                <h2 style="margin:0;" id="form-title">Add Course</h2>
                <p style="margin:0.3rem 0 0;" id="form-subtitle">Create a course with optional modules, lessons, quizzes, and evaluation.</p>
            </div>
            <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                <span style="font-size:0.78rem; font-weight:600; color:var(--text); align-self:center;">Quick start:</span>
                <button type="button" onclick="applyTemplate('programming')" class="course-template-btn">Programming</button>
                <button type="button" onclick="applyTemplate('business')" class="course-template-btn">Business</button>
                <button type="button" onclick="applyTemplate('creative')" class="course-template-btn">Creative</button>
                <button type="button" onclick="applyTemplate('softskills')" class="course-template-btn">Soft Skills</button>
                <button type="button" onclick="applyTemplate('compliance')" class="course-template-btn">Compliance</button>
            </div>
        </div>

        <form id="course-form" data-skip="true" method="post" enctype="multipart/form-data" action="pages/instructor/elearning-subpage/ajax/add-course.php">

            <!-- Section 1: Basic Info -->
            <div style="margin-top:1.5rem; padding-bottom:1rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                <h3 style="margin:0 0 0.75rem 0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fas fa-info-circle"></i> Basic Information
                </h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem;">
                    <label>
                        <span>Course title *</span>
                        <input type="text" name="title" required placeholder="e.g. Advanced SQL Mastery" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                    </label>
                    <label>
                        <span>Category</span>
                        <input type="text" name="category" list="category-list" placeholder="e.g. Data & Analytics" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                        <datalist id="category-list">
                            <option value="Programming"><option value="Data & Analytics"><option value="Business"><option value="Design & Creative"><option value="Soft Skills"><option value="Compliance & Safety"><option value="Leadership"><option value="Technology"><option value="Marketing"><option value="Finance">
                        </datalist>
                    </label>
                    <label>
                        <span>Difficulty</span>
                        <select name="difficulty" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                            <option value="beginner" selected>Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                            <option value="expert">Expert</option>
                        </select>
                    </label>
                    <label>
                        <span>Status</span>
                        <select name="status" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                            <option value="draft">Draft</option>
                            <option value="active" selected>Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </label>
                </div>
            </div>

            <!-- Section 2: Schedule -->
            <div style="margin-top:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                <h3 style="margin:0 0 0.75rem 0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fas fa-calendar-alt"></i> Schedule & Duration
                </h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:1rem;">
                    <label><span>Start date</span><input type="date" name="start_date" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Enrollment deadline</span><input type="date" name="enrollment_deadline" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Estimated hours</span><input type="number" name="estimated_hours" min="0" step="0.5" placeholder="e.g. 12" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Max learners</span><input type="number" name="max_learners" min="0" placeholder="0 = unlimited" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                </div>
            </div>

            <!-- Section 3: Cover Photo -->
            <div style="margin-top:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                <h3 style="margin:0 0 0.75rem 0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fas fa-image"></i> Cover Photo
                </h3>
                <div id="course-thumb-dropzone" style="border:2px dashed rgba(32,0,130,0.2); border-radius:12px; padding:2rem; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s; background:rgba(32,0,130,0.02);" onclick="document.querySelector('input[name=cover_photo]').click()">
                    <i class="fas fa-cloud-upload-alt" style="font-size:2rem; color:rgba(32,0,130,0.25); margin-bottom:0.5rem; display:block;"></i>
                    <p style="margin:0; color:var(--text); font-size:0.85rem;">Drag & drop or <strong style="color:var(--primary);">browse</strong></p>
                    <p style="margin:0.25rem 0 0; color:#999; font-size:0.75rem;">PNG, JPG, WEBP up to 5MB</p>
                </div>
                <input type="file" name="cover_photo" accept="image/*" style="display:none;" onchange="showCoverPreview(this)" />
                <div id="cover-preview" style="display:none; margin-top:0.75rem; position:relative; border-radius:12px; overflow:hidden; max-height:200px;">
                    <img id="cover-preview-img" style="width:100%; height:200px; object-fit:cover; border-radius:12px;" />
                    <button type="button" onclick="document.getElementById('cover-preview').style.display='none'; document.getElementById('course-thumb-dropzone').style.display='block'; this.closest('form').querySelector('input[name=cover_photo]').value='';" style="position:absolute; top:8px; right:8px; width:28px; height:28px; border-radius:50%; background:rgba(0,0,0,0.6); color:#fff; border:none; cursor:pointer; font-size:0.8rem; display:flex; align-items:center; justify-content:center;"><i class="fas fa-times"></i></button>
                </div>
            </div>

            <!-- Section 4: Skills -->
            <div style="margin-top:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                <h3 style="margin:0 0 0.75rem 0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fas fa-cogs"></i> Skills & Tags
                </h3>
                <div id="skill-selector" style="display:flex; flex-wrap:wrap; gap:0.4rem; padding:0.5rem; border:1px solid var(--border); border-radius:10px; min-height:42px; background:#fff;">
                    <?php foreach ($allSkills as $sk): ?>
                    <label style="display:inline-flex; align-items:center; gap:0.3rem; padding:0.3rem 0.6rem; border:1px solid rgba(32,0,130,0.15); border-radius:999px; cursor:pointer; font-size:0.78rem; font-weight:600; background:rgba(32,0,130,0.04); color:var(--text); transition:all 0.15s;">
                        <input type="checkbox" name="skill_ids[]" value="<?= (int)$sk['id'] ?>" style="accent-color:var(--primary);" />
                        <?= htmlspecialchars($sk['name']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Section 5: Description -->
            <div style="margin-top:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                <h3 style="margin:0 0 0.75rem 0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fas fa-align-left"></i> Description & Outcomes
                </h3>
                <label style="display:block; margin-bottom:0.75rem;">
                    <span>Course description</span>
                    <textarea name="description" rows="5" placeholder="Describe the course objectives, learning outcomes, and target audience..." style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border); resize:vertical;"></textarea>
                </label>
                <label style="display:block;">
                    <span>Prerequisites</span>
                    <textarea name="prerequisites" rows="2" placeholder="What should learners know before taking this course? (optional)" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border); resize:vertical;"></textarea>
                </label>
            </div>

            <!-- Section 6: Modules (Optional) -->
            <div style="margin-top:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                    <h3 style="margin:0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                        <i class="fas fa-cubes"></i> Modules & Content <span style="font-size:0.75rem; font-weight:400; color:var(--muted); text-transform:none; letter-spacing:0;">(optional)</span>
                    </h3>
                    <button type="button" id="add-module-btn" style="padding:0.6rem 1.2rem; background:var(--primary); color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.85rem; white-space:nowrap;"><i class="fas fa-plus" style="margin-right:0.3rem;"></i> Add Module</button>
                </div>
                <p style="color:#999; font-size:0.82rem; margin:0 0 0.75rem 0;">Add modules with lessons, quizzes, and evaluations. Skip this section for a simple course.</p>
                <div id="modules-list-container">
                    <div style="text-align:center; padding:2rem; color:#999; border:2px dashed rgba(32,0,130,0.1); border-radius:10px;">
                        <i class="fas fa-cubes" style="font-size:2rem; color:rgba(32,0,130,0.15); margin-bottom:0.5rem; display:block;"></i>
                        <p style="margin:0;">No modules added yet. Click <strong>"Add Module"</strong> to build course content.</p>
                    </div>
                </div>
            </div>

            <!-- Section 7: Evaluation (Optional) -->
            <div style="margin-top:1rem; padding-bottom:1rem;">
                <h3 style="margin:0 0 0.75rem 0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fas fa-clipboard-check"></i> Course Evaluation <span style="font-size:0.75rem; font-weight:400; color:var(--muted); text-transform:none; letter-spacing:0;">(optional)</span>
                </h3>
                <p style="color:#999; font-size:0.82rem; margin:0 0 0.75rem 0;">Add a final evaluation taken after all course content is completed.</p>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-top:0.5rem;">
                    <label><span>Evaluation title</span><input type="text" name="eval_title" data-eval-field="title" placeholder="e.g. Final Assessment" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Duration (seconds)</span><input type="number" name="eval_duration_seconds" data-eval-field="duration_seconds" placeholder="e.g. 1800" min="60" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Passing score (%)</span><input type="number" name="eval_passing_score" data-eval-field="passing_score" placeholder="e.g. 70" min="0" max="100" step="0.5" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Max attempts</span><input type="number" name="eval_max_attempts" data-eval-field="max_attempts" value="2" min="1" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Question count</span><input type="number" name="eval_question_count" data-eval-field="question_count" placeholder="Leave empty for all" min="1" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Status</span><select name="eval_status" data-eval-field="status" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);"><option value="active" selected>Active</option><option value="archived">Archived</option></select></label>
                </div>
                <label style="display:flex; align-items:center; gap:0.5rem; margin-top:0.75rem;">
                    <input type="checkbox" name="eval_show_answers" data-eval-field="show_answers_after_submit" />
                    <span style="font-size:0.85rem;">Show answers after submit</span>
                </label>
                <label style="display:block; margin-top:0.75rem;">
                    <span>Evaluation description</span>
                    <textarea name="eval_description" data-eval-field="description" rows="3" placeholder="Evaluation purpose and instructions..." style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border); resize:vertical;"></textarea>
                </label>
            </div>

            <!-- Prerequisites Section (only in edit mode) -->
            <div id="prerequisites-section" style="display:none; margin-top:2rem; padding-top:1.5rem; border-top:1px solid rgba(32,0,130,0.12);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <div>
                        <h3 style="margin:0; font-size:1.1rem; color:var(--text);">Prerequisites</h3>
                        <p style="margin:0.25rem 0 0; font-size:0.85rem; color:#999;">Learners must complete these before enrolling in this course.</p>
                    </div>
                    <button type="button" id="add-prereq-btn" style="padding:0.5rem 1rem; background:var(--primary); color:var(--surface); border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.85rem;"><i class="fas fa-plus" style="margin-right:0.3rem;"></i> Add</button>
                </div>
                <div id="prerequisites-list" style="display:grid; gap:0.5rem;"></div>
                <!-- Add Prerequisite Modal -->
                <div id="prereq-modal" class="modal-overlay" style="display:none; z-index:2000;">
                    <div style="background:var(--surface,#fff); border:1px solid rgba(32,0,130,0.12); border-radius:18px; width:min(450px,92vw); box-shadow:0 18px 45px rgba(32,0,130,0.18);">
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                            <h3 style="margin:0; font-size:1.1rem; color:var(--text);">Add Prerequisite</h3>
                            <button onclick="document.getElementById('prereq-modal').style.display='none'" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--text);">&times;</button>
                        </div>
                        <div style="padding:1.5rem;">
                            <label style="display:block; margin-bottom:1rem;">
                                <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem; font-size:0.85rem;">Type</span>
                                <select id="prereq-type" style="width:100%; padding:0.7rem; border:1px solid var(--border); border-radius:8px;">
                                    <option value="course">Required Course</option>
                                    <option value="skill">Required Skill</option>
                                </select>
                            </label>
                            <label id="prereq-course-label" style="display:block; margin-bottom:1rem;">
                                <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem; font-size:0.85rem;">Course</span>
                                <select id="prereq-course-id" style="width:100%; padding:0.7rem; border:1px solid var(--border); border-radius:8px;"></select>
                            </label>
                            <label id="prereq-skill-label" style="display:none; margin-bottom:1rem;">
                                <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem; font-size:0.85rem;">Skill</span>
                                <select id="prereq-skill-id" style="width:100%; padding:0.7rem; border:1px solid var(--border); border-radius:8px;"></select>
                            </label>
                            <div style="display:flex; gap:0.75rem;">
                                <button type="button" id="save-prereq-btn" style="flex:1; padding:0.7rem; background:var(--primary); color:var(--surface); border:none; border-radius:8px; cursor:pointer; font-weight:700;">Add Prerequisite</button>
                                <button type="button" onclick="document.getElementById('prereq-modal').style.display='none'" style="flex:1; padding:0.7rem; background:rgba(32,0,130,0.08); color:var(--primary); border:1px solid rgba(32,0,130,0.18); border-radius:8px; cursor:pointer; font-weight:700;">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Summary -->
            <div id="form-summary" style="display:none; padding:0.75rem 1rem; background:rgba(32,0,130,0.03); border:1px solid rgba(32,0,130,0.08); border-radius:10px; margin-top:0.5rem; font-size:0.82rem; color:var(--text);">
                <span id="summary-text"></span>
            </div>

            <!-- Submit -->
            <div class="mode-actions" style="margin-top:1.5rem; display:flex; gap:0.75rem; align-items:center;">
                <button type="button" onclick="previewCourse()" style="padding:0.7rem 1.5rem; background:transparent; color:var(--primary); border:1.5px solid rgba(32,0,130,0.2); border-radius:10px; cursor:pointer; font-weight:600; font-size:0.9rem;"><i class="fas fa-eye" style="margin-right:0.4rem;"></i> Preview</button>
                <button type="submit" class="mode-button" id="save-course-btn"><i class="fas fa-save" style="margin-right:0.4rem;"></i> <span id="save-btn-text">Save Course</span></button>
                <span id="form-status" style="font-size:0.78rem; color:#999; margin-left:0.5rem;"></span>
            </div>
        </form>
    </div>
</div>

<!-- Course Detail Modal -->
<div id="course-modal" class="modal-overlay" style="display:none; z-index:10000;">
    <div style="background:var(--surface, #fff); border-radius:10px; max-width:600px; width:90%; max-height:80vh; overflow:auto; box-shadow:0 4px 20px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:1.5rem; border-bottom:1px solid var(--border); background:#f9f9f9;">
            <h2 id="modal-title" style="margin:0; font-size:1.3rem;"></h2>
            <div style="display:flex; gap:0.5rem;">
                <button id="modal-edit-btn" style="padding:0.5rem 1rem; background:var(--primary); color:white; border:none; border-radius:6px; cursor:pointer; font-weight:500;">Edit</button>
                <button id="modal-archive-btn" style="padding:0.5rem 1rem; background:#ff9800; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:500;">Archive</button>
                <button id="modal-close-btn" style="padding:0.5rem 1rem; background:#ccc; color:#333; border:none; border-radius:6px; cursor:pointer; font-weight:500;">Back</button>
            </div>
        </div>
        <div style="padding:1.5rem;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                <div><label style="color:var(--muted); font-weight:500; font-size:0.9rem;">Category</label><p id="modal-category" style="margin:0.5rem 0 0;"></p></div>
                <div><label style="color:var(--muted); font-weight:500; font-size:0.9rem;">Status</label><p id="modal-status" style="margin:0.5rem 0 0;"></p></div>
                <div><label style="color:var(--muted); font-weight:500; font-size:0.9rem;">Start Date</label><p id="modal-start-date" style="margin:0.5rem 0 0;"></p></div>
                <div><label style="color:var(--muted); font-weight:500; font-size:0.9rem;">Enrollment Deadline</label><p id="modal-deadline" style="margin:0.5rem 0 0;"></p></div>
            </div>
            <div><label style="color:var(--muted); font-weight:500; font-size:0.9rem;">Description</label><p id="modal-description" style="margin:0.5rem 0 0; line-height:1.5;"></p></div>
        </div>
    </div>
</div>

<script src="js/course-builder.js"></script>
<script>
var courseTemplates = {
    programming: { title:'Web Development Fundamentals', category:'Programming', difficulty:'beginner', estimated_hours:20, description:'Learn web development fundamentals including HTML, CSS, and JavaScript.', prerequisites:'Basic computer literacy.' },
    business: { title:'Project Management Essentials', category:'Business', difficulty:'intermediate', estimated_hours:15, description:'Master core project management principles.', prerequisites:'Some team experience.' },
    creative: { title:'Digital Design & Visual Communication', category:'Design & Creative', difficulty:'beginner', estimated_hours:12, description:'Explore visual design principles for digital media.', prerequisites:'No prior design experience.' },
    softskills: { title:'Effective Communication & Leadership', category:'Soft Skills', difficulty:'beginner', estimated_hours:8, description:'Develop essential communication and leadership skills.', prerequisites:'Open to all levels.' },
    compliance: { title:'Workplace Safety & Compliance', category:'Compliance & Safety', difficulty:'beginner', estimated_hours:5, description:'Ensure a safe and compliant workplace.', prerequisites:'None. Mandatory for all.' }
};
function applyTemplate(key) {
    var t = courseTemplates[key]; if (!t) return;
    var f = document.getElementById('course-form'); if (!f) return;
    ['title','category','difficulty','estimated_hours','description','prerequisites'].forEach(function(k) {
        var sel = k==='title'?'input[name="title"]':k==='category'?'input[name="category"]':k==='difficulty'?'select[name="difficulty"]':k==='estimated_hours'?'input[name="estimated_hours"]':'textarea[name="'+k+'"]';
        var el = f.querySelector(sel);
        if (el && t[k] !== undefined) el.value = t[k];
    });
    f.style.transition='background 0.3s'; f.style.background='rgba(32,0,130,0.03)'; setTimeout(function(){f.style.background='';},600);
    showNotif(key.charAt(0).toUpperCase()+key.slice(1)+' template applied!','success');
}
function showCoverPreview(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    if (!file.type.startsWith('image/')) return;
    var r = new FileReader();
    r.onload = function(ev) {
        var img = document.getElementById('cover-preview-img');
        var pv = document.getElementById('cover-preview');
        var dz = document.getElementById('course-thumb-dropzone');
        if (img && pv) { img.src = ev.target.result; pv.style.display = 'block'; dz.style.display = 'none'; }
    };
    r.readAsDataURL(file);
}
(function() {
    var f = document.getElementById('course-form'); if (!f) return;
    var s = document.getElementById('form-summary'); var st = document.getElementById('summary-text');
    f.addEventListener('input', update); f.addEventListener('change', update);
    function update() {
        var title = f.querySelector('input[name="title"]').value;
        var cat = f.querySelector('input[name="category"]').value;
        var diff = f.querySelector('select[name="difficulty"]').value;
        var h = f.querySelector('input[name="estimated_hours"]').value;
        var sk = f.querySelectorAll('input[name="skill_ids[]"]:checked').length;
        if (!title && !cat && !sk) { s.style.display = 'none'; return; }
        var p = [];
        if (title) p.push('<strong>'+title+'</strong>');
        if (cat) p.push(cat);
        if (diff) p.push(diff.charAt(0).toUpperCase()+diff.slice(1));
        if (h) p.push(h+'h');
        if (sk) p.push(sk+' skill'+(sk>1?'s':''));
        st.innerHTML = '<i class="fas fa-file-alt" style="margin-right:0.4rem;"></i> '+p.join(' &bull; ');
        s.style.display = 'block';
    }
})();
function previewCourse() {
    var f = document.getElementById('course-form'); if (!f) return;
    var t=f.querySelector('input[name="title"]').value||'Untitled';
    var c=f.querySelector('input[name="category"]').value||'General';
    var d=f.querySelector('select[name="difficulty"]').value;
    var st=f.querySelector('select[name="status"]').value;
    var h=f.querySelector('input[name="estimated_hours"]').value;
    var desc=f.querySelector('textarea[name="description"]').value;
    var sk=[]; f.querySelectorAll('input[name="skill_ids[]"]:checked').forEach(function(b){sk.push(b.parentElement.textContent.trim());});
    var dl={beginner:'Beginner',intermediate:'Intermediate',advanced:'Advanced',expert:'Expert'};
    var html='<div style="position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(3px);" onclick="if(event.target===this)this.remove()"><div style="background:var(--surface,#fff);border-radius:16px;width:min(600px,92vw);max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);"><div style="padding:1.5rem 2rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;"><h2 style="margin:0;font-size:1.2rem;">Course Preview</h2><button onclick="this.closest(\'div[style*=fixed]\').remove()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#999;"><i class="fas fa-times"></i></button></div><div style="padding:1.5rem 2rem 2rem;"><h3 style="margin:0 0 0.5rem;font-size:1.3rem;">'+t+'</h3><div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem;"><span style="padding:0.25rem 0.6rem;background:rgba(32,0,130,0.08);border-radius:999px;font-size:0.78rem;font-weight:600;color:var(--primary);">'+c+'</span><span style="padding:0.25rem 0.6rem;background:rgba(32,0,130,0.1);border-radius:999px;font-size:0.78rem;font-weight:600;">'+(dl[d]||'Beginner')+'</span><span style="padding:0.25rem 0.6rem;background:rgba(16,185,129,0.1);color:#10b981;border-radius:999px;font-size:0.78rem;font-weight:600;">'+st.charAt(0).toUpperCase()+st.slice(1)+'</span>'+(h?'<span style="padding:0.25rem 0.6rem;background:rgba(0,0,0,0.05);border-radius:999px;font-size:0.78rem;font-weight:600;">'+h+'h</span>':'')+'</div>';
    if(sk.length){html+='<div style="display:flex;flex-wrap:wrap;gap:0.3rem;">';sk.forEach(function(s){html+='<span style="padding:0.2rem 0.5rem;background:rgba(32,0,130,0.06);border-radius:999px;font-size:0.75rem;color:var(--primary);font-weight:600;">'+s+'</span>';});html+='</div>';}
    if(desc) html+='<div style="margin-top:1rem;padding:1rem;background:rgba(0,0,0,0.03);border-radius:10px;font-size:0.9rem;line-height:1.6;white-space:pre-wrap;">'+desc.replace(/</g,'&lt;')+'</div>';
    html+='</div></div></div>';
    document.body.insertAdjacentHTML('beforeend',html);
}
(function() {
    var params = new URLSearchParams(window.location.search);
    var courseId = params.get('id');
    if (!courseId) return;
    document.getElementById('form-title').textContent = 'Edit Course';
    document.getElementById('form-subtitle').textContent = 'Update course details, modules, and content.';
    document.getElementById('save-btn-text').textContent = 'Update Course';
    document.querySelector('form#course-form').action = 'pages/instructor/elearning-subpage/ajax/edit-course.php';
    fetch('pages/instructor/elearning-subpage/ajax/get-course-by-id.php?id='+courseId, {credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(function(r){return r.json()}).then(function(data){
        if (!data.success || !data.data) return;
        var course = data.data;
        var form = document.getElementById('course-form');
        form.querySelector('input[name="title"]').value = course.title||'';
        form.querySelector('input[name="category"]').value = course.category||'';
        form.querySelector('select[name="difficulty"]').value = course.difficulty||'beginner';
        form.querySelector('select[name="status"]').value = course.status||'active';
        form.querySelector('input[name="start_date"]').value = course.start_date?course.start_date.split(' ')[0]:'';
        form.querySelector('input[name="enrollment_deadline"]').value = course.enrollment_deadline?course.enrollment_deadline.split(' ')[0]:'';
        form.querySelector('input[name="estimated_hours"]').value = course.estimated_hours||'';
        form.querySelector('input[name="max_learners"]').value = course.max_learners||'';
        form.querySelector('textarea[name="description"]').value = course.description||'';
        form.querySelector('textarea[name="prerequisites"]').value = course.prerequisites||'';
        var skillIds = course.skill_ids || [];
        form.querySelectorAll('input[name="skill_ids[]"]').forEach(function(cb){ cb.checked = skillIds.indexOf(parseInt(cb.value))!==-1; });
        var idInput = document.createElement('input'); idInput.type='hidden'; idInput.name='id'; idInput.value=course.id;
        form.appendChild(idInput);
    });
})();
function showNotif(msg,type){var el=document.createElement('div');el.style.cssText='position:fixed;top:20px;right:20px;padding:0.8rem 1.2rem;border-radius:8px;font-weight:600;font-size:0.85rem;z-index:10000;box-shadow:0 4px 12px rgba(0,0,0,0.15);';el.style.background=type==='success'?'#10b981':type==='error'?'#ef4444':'#3b82f6';el.style.color='#fff';el.textContent=msg;document.body.appendChild(el);setTimeout(function(){el.remove();},3000);}
(function(){var f=document.getElementById('course-form');var ind=document.getElementById('form-status');if(!f||!ind)return;f.addEventListener('input',function(){ind.textContent='* unsaved changes';ind.style.color='var(--primary)';});f.addEventListener('submit',function(){ind.textContent='saving...';ind.style.color='#10b981';});})();

// --- Prerequisites Management ---
(function() {
    var params = new URLSearchParams(window.location.search);
    var courseId = params.get('id');
    if (!courseId) return; // Only show in edit mode

    var section = document.getElementById('prerequisites-section');
    var list = document.getElementById('prerequisites-list');
    var addBtn = document.getElementById('add-prereq-btn');
    var modal = document.getElementById('prereq-modal');
    var typeSelect = document.getElementById('prereq-type');
    var courseLabel = document.getElementById('prereq-course-label');
    var skillLabel = document.getElementById('prereq-skill-label');
    var courseSelect = document.getElementById('prereq-course-id');
    var skillSelect = document.getElementById('prereq-skill-id');
    var saveBtn = document.getElementById('save-prereq-btn');

    if (!section) return;
    section.style.display = 'block';

    // Toggle course/skill dropdowns
    typeSelect.addEventListener('change', function() {
        courseLabel.style.display = typeSelect.value === 'course' ? 'block' : 'none';
        skillLabel.style.display = typeSelect.value === 'skill' ? 'block' : 'none';
    });

    // Load existing prerequisites
    function loadPrereqs() {
        fetch('pages/instructor/elearning-subpage/ajax/get-prerequisite.php?course_id=' + courseId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !data.items.length) {
                list.innerHTML = '<p style="color:#999; font-size:0.85rem; padding:0.5rem;">No prerequisites set.</p>';
                return;
            }
            var html = '';
            data.items.forEach(function(item) {
                var name = item.course_title || item.skill_name || 'Unknown';
                var type = item.required_course_id ? 'Course' : 'Skill';
                var icon = item.required_course_id ? 'fa-graduation-cap' : 'fa-star';
                html += '<div style="display:flex; align-items:center; justify-content:space-between; padding:0.75rem 1rem; background:rgba(32,0,130,0.03); border:1px solid rgba(32,0,130,0.08); border-radius:10px;">';
                html += '<div style="display:flex; align-items:center; gap:0.75rem;">';
                html += '<div style="width:36px; height:36px; border-radius:8px; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="fas ' + icon + '" style="font-size:0.8rem;"></i></div>';
                html += '<div><div style="font-weight:600; font-size:0.9rem; color:var(--text);">' + name + '</div>';
                html += '<div style="font-size:0.75rem; color:#999;">' + type + '</div></div>';
                html += '</div>';
                html += '<button type="button" class="remove-prereq-btn" data-id="' + item.id + '" style="padding:0.35rem 0.7rem; background:rgba(239,68,68,0.08); color:#ef4444; border:1px solid rgba(239,68,68,0.2); border-radius:6px; cursor:pointer; font-size:0.75rem; font-weight:600;"><i class="fas fa-times" style="margin-right:0.2rem;"></i>Remove</button>';
                html += '</div>';
            });
            list.innerHTML = html;
            // Attach remove handlers
            list.querySelectorAll('.remove-prereq-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    removePrereq(this.dataset.id);
                });
            });
        })
        .catch(function() { list.innerHTML = '<p style="color:#ef4444; font-size:0.85rem;">Failed to load prerequisites.</p>'; });
    }

    // Load courses and skills for the add modal
    function loadDropdowns() {
        fetch('pages/instructor/elearning-subpage/ajax/get-course-by-id.php?id=' + courseId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            // Populate course dropdown (all active courses except this one)
            return fetch('pages/learner/catalog-subpage/ajax/discover/get-course.php');
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.items) {
                courseSelect.innerHTML = '<option value="">Select a course...</option>';
                data.items.forEach(function(c) {
                    if (c.id == courseId) return;
                    courseSelect.innerHTML += '<option value="' + c.id + '">' + c.title + '</option>';
                });
            }
        })
        .catch(function() {});

        fetch('pages/learner/catalog-subpage/ajax/discover/get-skill.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.items) {
                skillSelect.innerHTML = '<option value="">Select a skill...</option>';
                data.items.forEach(function(s) {
                    skillSelect.innerHTML += '<option value="' + s.id + '">' + s.name + '</option>';
                });
            }
        })
        .catch(function() {});
    }

    // Add prerequisite
    saveBtn.addEventListener('click', function() {
        var type = typeSelect.value;
        var payload = { course_id: parseInt(courseId) };
        if (type === 'course') {
            payload.required_course_id = parseInt(courseSelect.value);
            if (!payload.required_course_id) { if (window.showToast) window.showToast('Please select a course', 'error'); return; }
        } else {
            payload.required_skill_id = parseInt(skillSelect.value);
            if (!payload.required_skill_id) { if (window.showToast) window.showToast('Please select a skill', 'error'); return; }
        }
        saveBtn.disabled = true;
        fetch('pages/instructor/elearning-subpage/ajax/add-prerequisite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            saveBtn.disabled = false;
            if (data.success) {
                modal.style.display = 'none';
                loadPrereqs();
                if (window.showToast) window.showToast('Prerequisite added', 'success');
            } else {
                if (window.showToast) window.showToast(data.error || 'Failed to add prerequisite', 'error');
            }
        })
        .catch(function() { saveBtn.disabled = false; if (window.showToast) window.showToast('Network error', 'error'); });
    });

    // Remove prerequisite
    function removePrereq(id) {
        if (!confirm('Remove this prerequisite?')) return;
        fetch('pages/instructor/elearning-subpage/ajax/remove-prerequisite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(id) })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                loadPrereqs();
                if (window.showToast) window.showToast('Prerequisite removed', 'success');
            } else {
                if (window.showToast) window.showToast(data.error || 'Failed to remove', 'error');
            }
        })
        .catch(function() { if (window.showToast) window.showToast('Network error', 'error'); });
    }

    addBtn.addEventListener('click', function() {
        modal.style.display = 'flex';
        typeSelect.value = 'course';
        typeSelect.dispatchEvent(new Event('change'));
    });

    loadPrereqs();
    loadDropdowns();
})();
</script>