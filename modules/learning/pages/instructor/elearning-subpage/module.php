<?php
// Pre-load courses for the datalist (server-side so it always works)
$modulePageCourses = [];
$moduleEditData = null;
$moduleEditId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
try {
    require_once dirname(__DIR__, 3) . '/classes/course.php';
    require_once dirname(__DIR__, 3) . '/classes/module.php';
    require_once dirname(__DIR__, 5) . '/database/db.php';
    $modulePageDb = new Database();
    $modulePagePdo = $modulePageDb->getConnection();
    $modulePageCourse = new Course($modulePagePdo);
    $modulePageCourses = $modulePageCourse->getList();
    if ($moduleEditId > 0) {
        $modStmt = $modulePagePdo->prepare('SELECT m.*, c.title AS course_name FROM ld_module m JOIN ld_course c ON c.id = m.course_id WHERE m.id = ? LIMIT 1');
        $modStmt->execute([$moduleEditId]);
        $moduleEditData = $modStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Throwable $e) {
    DbError::capture($e, 'instructor/elearning-subpage/module');
    $modulePageCourses = [];
}
?>
<div class="module-content">
    <!-- Add Lesson Modal -->
    <div id="add-lesson-modal" class="modal-overlay" style="display:none; z-index:2000;">
        <div style="background:#fff; border:1px solid rgba(32, 0, 130, 0.12); border-radius:18px; width:min(500px, 92vw); max-height:80vh; overflow-y:auto; box-shadow:0 18px 45px rgba(32, 0, 130, 0.18);">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32, 0, 130, 0.12); background:linear-gradient(135deg, rgba(32, 0, 130, 0.08), rgba(81, 70, 183, 0.05));">
                <h2 style="margin:0; font-size:1.1rem; color:var(--primary);">Add Lesson</h2>
                <button type="button" data-close-modal="add-lesson-modal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">✕</button>
            </div>
            <div style="padding:1.5rem;">
                <form id="add-lesson-in-modal-form">
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

    <!-- Add Quiz Modal -->
    <div id="add-quiz-modal" class="modal-overlay" style="display:none; z-index:2000;">
        <div style="background:#fff; border:1px solid rgba(32, 0, 130, 0.12); border-radius:18px; width:min(500px, 92vw); max-height:80vh; overflow-y:auto; box-shadow:0 18px 45px rgba(32, 0, 130, 0.18);">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32, 0, 130, 0.12); background:linear-gradient(135deg, rgba(32, 0, 130, 0.08), rgba(81, 70, 183, 0.05));">
                <h2 style="margin:0; font-size:1.1rem; color:var(--primary);">Add Quiz</h2>
                <button type="button" data-close-modal="add-quiz-modal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">✕</button>
            </div>
            <div style="padding:1.5rem;">
                <form id="add-quiz-in-modal-form">
                    <input type="hidden" name="lesson_id" id="add-quiz-lesson-id" />
                    <input type="hidden" name="module_id" id="add-quiz-module-id" />
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Quiz Title *</span>
                        <input type="text" name="title" required placeholder="e.g. Lesson Knowledge Check" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                        <label style="display:block; margin-bottom:1rem;">
                            <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Duration (seconds)</span>
                            <input type="number" name="duration_seconds" min="30" value="600" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                        </label>
                        <label style="display:block; margin-bottom:1rem;">
                            <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Passing score (%)</span>
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
                    </div>
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

    <div class="toolbar" style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
        <?= BackLink::anchor('instructor/elearning', 'style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; background:var(--primary); color:#fff; border:none; border-radius:8px; text-decoration:none; font-size:0.85rem; font-weight:600; white-space:nowrap;"') ?>
        <div class="toolbar-search" style="flex:1;">
            <input type="search" placeholder="Search module form..." aria-label="Search module form" />
        </div>
        
    </div>

    <div class="mode-card">
        <h2><?php echo $moduleEditData ? 'Edit Module' : 'Add Module'; ?></h2>
        <p><?php echo $moduleEditData ? 'Update module details and view hierarchical content structure.' : 'A module sits under a course. Each module can contain multiple lessons and assessments.'; ?></p>

        <form id="add-module-form" data-skip="true" method="post" enctype="multipart/form-data" action="pages/instructor/elearning-subpage/ajax/add-module.php">
            <?php if ($moduleEditData): ?>
                <input type="hidden" name="id" value="<?php echo (int) $moduleEditData['id']; ?>" />
            <?php endif; ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-top:1rem;">
                <label>
                    <span>Course</span>
                    <input type="text" list="module-course-list" id="module-course-search" placeholder="Search course by name" value="<?php echo htmlspecialchars($moduleEditData['course_name'] ?? ''); ?>" required style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                    <input type="hidden" name="course_id" id="module-course-id" value="<?php echo (int) ($moduleEditData['course_id'] ?? 0); ?>" required />
                    <datalist id="module-course-list">
                        <?php foreach ($modulePageCourses as $c): ?>
                        <option value="<?php echo htmlspecialchars($c['title']); ?>" data-course-id="<?php echo (int)$c['id']; ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </label>
                <label>
                    <span>Module title</span>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($moduleEditData['title'] ?? ''); ?>" required placeholder="e.g. Getting Started" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                </label>
                <label>
                    <span>Status</span>
                    <select name="status" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                        <option value="active"<?php echo (($moduleEditData['status'] ?? 'active') === 'active') ? ' selected' : ''; ?>>Active</option>
                        <option value="archived"<?php echo (($moduleEditData['status'] ?? '') === 'archived') ? ' selected' : ''; ?>>Archived</option>
                    </select>
                </label>
            </div>

            <div style="display:block; margin-top:1rem;">
                <span style="font-weight:600;">Cover photo</span>
                <div id="module-thumb-dropzone"></div>
            </div>

            <label style="display:block; margin-top:1rem;">
                <span>Description</span>
                <textarea name="description" rows="5" placeholder="Summarize what this module covers..." style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border); resize:vertical;"><?php echo htmlspecialchars($moduleEditData['description'] ?? ''); ?></textarea>
            </label>

            <!-- Lessons Section (content builder) -->
            <div id="module-lessons-section" style="margin-top:1.5rem; padding-top:1rem; border-top:1px solid rgba(32,0,130,0.08);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                    <h3 style="margin:0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                        <i class="fas fa-book"></i> Lessons <span style="font-size:0.75rem; font-weight:400; color:var(--muted); text-transform:none; letter-spacing:0;">(optional)</span>
                    </h3>
                    <button type="button" id="add-lesson-inline-btn" data-open-modal="add-lesson-modal" style="padding:0.5rem 1rem; background:#2196F3; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:0.85rem; font-weight:600; white-space:nowrap;"><i class="fas fa-plus" style="margin-right:0.3rem;"></i> Add Lesson</button>
                </div>
                <div id="module-lessons-list">
                    <p style="color:#999; font-size:0.82rem; text-align:center; padding:1rem; border:2px dashed rgba(32,0,130,0.1); border-radius:10px;">No lessons yet. Click "Add Lesson" to build module content.</p>
                </div>
            </div>

            <!-- Module Quizzes Section (content builder) -->
            <div id="module-quizzes-section" style="margin-top:1.5rem; padding-top:1rem; border-top:1px solid rgba(32,0,130,0.08);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                    <h3 style="margin:0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                        <i class="fas fa-question-circle"></i> Module Quizzes <span style="font-size:0.75rem; font-weight:400; color:var(--muted); text-transform:none; letter-spacing:0;">(optional)</span>
                    </h3>
                    <button type="button" id="add-quiz-inline-btn" data-open-modal="add-quiz-modal" style="padding:0.5rem 1rem; background:#2196F3; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:0.85rem; font-weight:600; white-space:nowrap;"><i class="fas fa-plus" style="margin-right:0.3rem;"></i> Add Quiz</button>
                </div>
                <div id="module-quizzes-list">
                    <p style="color:#999; font-size:0.82rem; text-align:center; padding:1rem; border:2px dashed rgba(32,0,130,0.1); border-radius:10px;">No quizzes yet. Click "Add Quiz" to add an assessment.</p>
                </div>
            </div>

            <div class="mode-actions" style="margin-top:1.5rem;">
                <button type="submit" class="mode-button">Save Module</button>
                
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    // Check for edit mode
    const params = new URLSearchParams(window.location.search);
    const moduleId = params.get('id');
    const isEditMode = !!moduleId;

    if (isEditMode) {
        // Change heading (keep the parent Course selector visible so the parent can be changed)
        document.querySelector('.mode-card h2').textContent = 'Edit Module';
        document.querySelector('.mode-card p').textContent = 'Update module details and view hierarchical content structure.';
        // Edit mode manages lessons/quizzes via the hierarchy below; hide the add-mode builder sections
        const ls = document.getElementById('module-lessons-section');
        const qs = document.getElementById('module-quizzes-section');
        if (ls) ls.style.display = 'none';
        if (qs) qs.style.display = 'none';
    }

    const form = document.getElementById('add-module-form');
    if (!form) return;

    const courseSearch = document.getElementById('module-course-search');
    const courseIdField = document.getElementById('module-course-id');
    const courseList = document.getElementById('module-course-list');
    var courseOptions = <?php echo json_encode(array_map(function($c) { return ['id' => (int)$c['id'], 'name' => trim($c['title'])]; }, $modulePageCourses), JSON_HEX_TAG); ?> || [];

    courseSearch.addEventListener('change', function () {
        var match = courseOptions.find(function (c) { return c.name === courseSearch.value; });
        courseIdField.value = match ? match.id : '';
    });

    // --- In-memory content builder (used in ADD mode: build lessons & quizzes before the module exists) ---
    function escHtml(v) {
        return String(v == null ? '' : v)
            .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    var pendingLessons = [];
    var pendingQuizzes = [];

    function renderPendingContent() {
        var lessonsList = document.getElementById('module-lessons-list');
        if (lessonsList) {
            if (!pendingLessons.length) {
                lessonsList.innerHTML = '<p style="color:#999; font-size:0.82rem; text-align:center; padding:1rem; border:2px dashed rgba(32,0,130,0.1); border-radius:10px;">No lessons yet. Click "Add Lesson" to build module content.</p>';
            } else {
                lessonsList.innerHTML = pendingLessons.map(function (l, i) {
                    return '<div style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem 1rem; background:rgba(33,150,243,0.06); border:1px solid rgba(33,150,243,0.15); border-radius:10px; margin-bottom:0.5rem;">' +
                        '<i class="fas fa-bookmark" style="color:var(--primary);"></i>' +
                        '<span style="flex:1; font-weight:600;">' + escHtml(l.title) + '</span>' +
                        '<span style="font-size:0.72rem; color:#999; text-transform:uppercase;">' + escHtml(l.content_type) + '</span>' +
                        '<button type="button" class="remove-pending-lesson" data-idx="' + i + '" style="padding:0.3rem 0.6rem; font-size:0.75rem; background:#ff6b6b; color:#fff; border:none; border-radius:6px; cursor:pointer;">Remove</button>' +
                        '</div>';
                }).join('');
            }
        }
        var quizzesList = document.getElementById('module-quizzes-list');
        if (quizzesList) {
            if (!pendingQuizzes.length) {
                quizzesList.innerHTML = '<p style="color:#999; font-size:0.82rem; text-align:center; padding:1rem; border:2px dashed rgba(32,0,130,0.1); border-radius:10px;">No quizzes yet. Click "Add Quiz" to add an assessment.</p>';
            } else {
                quizzesList.innerHTML = pendingQuizzes.map(function (q, i) {
                    return '<div style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem 1rem; background:rgba(245,158,11,0.06); border:1px solid rgba(245,158,11,0.2); border-radius:10px; margin-bottom:0.5rem;">' +
                        '<i class="fas fa-circle-question" style="color:#b45309;"></i>' +
                        '<span style="flex:1; font-weight:600;">' + escHtml(q.title) + '</span>' +
                        '<span style="font-size:0.72rem; color:#999;">' + (q.passing_score ? 'Pass ' + q.passing_score + '%' : '') + '</span>' +
                        '<button type="button" class="remove-pending-quiz" data-idx="' + i + '" style="padding:0.3rem 0.6rem; font-size:0.75rem; background:#ff6b6b; color:#fff; border:none; border-radius:6px; cursor:pointer;">Remove</button>' +
                        '</div>';
                }).join('');
            }
        }
    }

    document.addEventListener('click', function (e) {
        var rm = e.target.closest('.remove-pending-lesson');
        if (rm) { pendingLessons.splice(parseInt(rm.dataset.idx, 10), 1); renderPendingContent(); return; }
        var rq = e.target.closest('.remove-pending-quiz');
        if (rq) { pendingQuizzes.splice(parseInt(rq.dataset.idx, 10), 1); renderPendingContent(); return; }
    });

    // ADD-mode: capture lessons into the pending builder (edit mode is handled by loadModuleHierarchy)
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!f || f.id !== 'add-lesson-in-modal-form' || isEditMode) return;
        e.preventDefault();
        var title = (f.querySelector('input[name="title"]').value || '').trim();
        if (!title) { showNotif('Lesson title is required.', 'error'); return; }
        pendingLessons.push({
            title: title,
            content_type: f.querySelector('select[name="content_type"]').value,
            content_body: f.querySelector('textarea[name="content_body"]').value,
            status: f.querySelector('select[name="status"]').value
        });
        f.reset();
        var modal = document.getElementById('add-lesson-modal');
        if (modal) modal.style.display = 'none';
        renderPendingContent();
        showNotif('Lesson added.', 'success');
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton ? submitButton.textContent : '';
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';
        }

        const action = isEditMode ? 'pages/instructor/elearning-subpage/ajax/edit-module.php' : form.action;
        let fetchOptions;
        if (isEditMode) {
            // Edit endpoint expects JSON (and persists the parent course via course_id)
            const idInput = form.querySelector('input[name="id"]');
            const payload = {
                id: parseInt(idInput ? idInput.value : 0, 10),
                course_id: parseInt(courseIdField.value || '0', 10),
                title: form.querySelector('input[name="title"]').value,
                status: form.querySelector('select[name="status"]').value,
                description: form.querySelector('textarea[name="description"]').value
            };
            fetchOptions = {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload)
            };
        } else {
            const formData = new FormData(form);
            // Send the pending lessons & quizzes so they're created with the module
            formData.append('lessons', JSON.stringify(pendingLessons));
            formData.append('quizzes', JSON.stringify(pendingQuizzes));
            fetchOptions = {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            };
        }

        fetch(action, fetchOptions)
        .then(async function (response) {
            const data = await response.json().catch(function () {
                return { success: false, message: 'Request failed.' };
            });

            if (!response.ok || !data.success) {
                throw new Error(data.message || (isEditMode ? 'Unable to update module.' : 'Unable to save module.'));
            }

            // Show notification instead of alert
            const notification = document.createElement('div');
            notification.textContent = 'Saved successfully: ' + (data.message || (isEditMode ? 'Module updated successfully.' : 'Module created successfully.'));
            notification.style.cssText = 'position:fixed;top:20px;right:20px;padding:1rem 1.5rem;border-radius:8px;background:#10b981;color:#fff;z-index:10000;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 4000);

            if (!isEditMode) {
                // Jump to edit mode so the created lessons/quizzes are visible and manageable
                if (data.id) {
                    window.location.href = '?page=instructor/elearning-subpage/module&id=' + data.id;
                    return;
                }
                form.reset();
                courseIdField.value = '';
                courseSearch.value = '';
            }
        })
        .catch(function (error) {
            // Show notification instead of alert
            const notification = document.createElement('div');
            notification.textContent = 'Save failed: ' + (error.message || (isEditMode ? 'Unable to update module.' : 'Unable to save module.'));
            notification.style.cssText = 'position:fixed;top:20px;right:20px;padding:1rem 1.5rem;border-radius:8px;background:#ef4444;color:#fff;z-index:10000;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 4000);
        })
        .finally(function () {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        });
    });

    // If in edit mode, load module data and display hierarchy
    if (isEditMode && moduleId) {
        fetch('pages/instructor/elearning-subpage/ajax/get-module-by-id.php?id=' + moduleId, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.data) return;

            const module = data.data;

            // Populate form
            form.querySelector('input[name="title"]').value = module.title || '';
            form.querySelector('select[name="status"]').value = module.status || 'active';
            form.querySelector('textarea[name="description"]').value = module.description || '';
            courseIdField.value = module.course_id || '';
            courseSearch.value = module.course_name || '';

            // Add hidden ID field
            if (!form.querySelector('input[name="id"]')) {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = module.id;
                form.appendChild(idInput);
            }

            // Load and display hierarchy
            loadModuleHierarchy(moduleId);
        })
        .catch(e => console.error('Error loading module:', e));
    }

    // Function to load and display module hierarchy (lessons, quizzes, and evaluations)
    function loadModuleHierarchy(mid) {
        if (!mid || mid <= 0) {
            console.warn('Invalid module ID for hierarchy load:', mid);
            return;
        }

        const courseIdField = document.getElementById('module-course-id');
        const courseId = courseIdField ? parseInt(courseIdField.value) : 0;

        // Lessons, quizzes and evaluations opened from here return to this module
        // rather than to the E-Learning list they were reached through.
        const moduleBackParam = 'back=' + encodeURIComponent('instructor/elearning-subpage/module?id=' + mid);

        // Remove any existing hierarchy container before loading a new one
        const oldContainer = form.parentNode ? form.parentNode.querySelector('div[style*="margin-top:2rem"][style*="padding:1.5rem"][style*="background:#f9fafb"]') : null;
        if (oldContainer) {
            oldContainer.remove();
        }

        fetch(`pages/instructor/elearning-subpage/ajax/get-lessons-by-module.php?module_id=${mid}`, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(async (lessonsRes) => {
            const lessons = lessonsRes.success ? lessonsRes.items : [];

            const lessonsWithQuizzes = await Promise.all(lessons.map(async (lesson) => {
                const quizzesRes = await fetch(`pages/instructor/elearning-subpage/ajax/get-quizzes-by-lesson.php?lesson_id=${lesson.id}`, { credentials: 'same-origin' }).then(r => r.json()).catch(() => ({ success: false, items: [] }));
                const quizzes = quizzesRes.success ? quizzesRes.items : [];
                return { ...lesson, quizzes };
            }));

            // Load evaluations if course_id is available
            let evaluations = [];
            if (courseId > 0) {
                try {
                    const evalsRes = await fetch(`pages/instructor/elearning-subpage/ajax/get-evaluations-by-course.php?course_id=${courseId}`, { credentials: 'same-origin' }).then(r => r.json());
                    evaluations = evalsRes.success ? evalsRes.items : [];
                } catch (e) {
                    console.error('Error loading evaluations:', e);
                }
            }

            let html = '<div style="margin-top:2rem; padding:1.5rem; background:#f9fafb; border-radius:10px; border:1px solid var(--border);"><div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;"><h3 style="margin:0; color:var(--primary);">Lessons, Quizzes & Evaluations</h3><button id="add-lesson-btn" type="button" data-open-modal="add-lesson-modal" style="padding:0.5rem 1rem; background:var(--primary); color:var(--surface); border:none; border-radius:6px; cursor:pointer; font-weight:700; font-size:0.85rem;">+ Add Lesson</button></div>';
            html += '<div style="font-size:0.9rem; line-height:1.8;">';

            if (lessonsWithQuizzes.length > 0) {
                html += '<div class="reorder-list" data-entity="lesson" data-parent-id="' + mid + '" style="display:flex; flex-direction:column; gap:0.75rem;">';
            }

            lessonsWithQuizzes.forEach((lesson) => {
                html += `
                    <div class="reorder-item" draggable="true" data-id="${lesson.id}" style="margin-bottom:0.8rem; border:1px solid rgba(32,0,130,0.08); border-radius:8px; padding:0.5rem; background:#fff; cursor:grab;">
                        <div class="hierarchy-toggle" data-toggle-target="lesson-${lesson.id}" style="display:flex; align-items:center; gap:0.5rem; padding:0.4rem; background:rgba(81,70,183,0.06); border-radius:6px; cursor:pointer;">
                            <span class="drag-handle" style="color:var(--text); cursor:grab; font-size:1rem; user-select:none;">⋮⋮</span>
                            <span class="hierarchy-arrow" style="font-weight:700; color:var(--text); min-width:1.2rem;">▼</span>
                            <span style="flex:1; color:var(--text);"><i class="fas fa-bookmark" style="margin-right:0.5rem;"></i>Lesson: ${lesson.title}</span>
                            <button type="button" class="hierarchy-edit" data-edit-url="?page=instructor/elearning-subpage/lesson&${moduleBackParam}&id=${lesson.id}" style="padding:0.3rem 0.6rem; font-size:0.75rem; background:var(--primary); color:var(--surface); border:none; border-radius:4px; cursor:pointer; font-weight:700;">Edit</button>
                        </div>
                        <div class="hierarchy-content" data-toggle-id="lesson-${lesson.id}" style="display:block; padding-left:2rem; margin-top:0.4rem;">
                            ${lesson.quizzes.length > 0 ? lesson.quizzes.map((quiz) => `
                                <div style="padding:0.4rem 0.8rem; background:rgba(59,130,246,0.08); border-radius:6px; margin-bottom:0.4rem; display:flex; align-items:center; justify-content:space-between;">
                                    <span style="color:var(--text);"><i class="fas fa-circle-question" style="margin-right:0.5rem;"></i>Quiz: ${quiz.title}</span>
                                    <button type="button" class="hierarchy-edit" data-edit-url="?page=instructor/elearning-subpage/quiz&${moduleBackParam}&id=${quiz.id}" style="padding:0.3rem 0.6rem; font-size:0.75rem; background:var(--primary); color:var(--surface); border:none; border-radius:4px; cursor:pointer; font-weight:700;">Edit</button>
                                </div>
                            `).join('') : '<p style="color:#ccc; font-size:0.8rem; margin:0.2rem 0;">No quizzes yet</p>'}
                            <div style="margin-top:0.4rem;">
                                <button type="button" class="add-quiz-btn" data-open-modal="add-quiz-modal" data-lesson-id="${lesson.id}" data-module-id="${mid}" style="padding:0.3rem 0.7rem; font-size:0.75rem; background:rgba(245,158,11,0.12); color:#b45309; border:1px solid rgba(245,158,11,0.4); border-radius:999px; cursor:pointer; font-weight:700;"><i class="fas fa-plus" style="margin-right:0.25rem;"></i>Add Quiz</button>
                            </div>
                        </div>
                    </div>
                `;
            });

            if (lessonsWithQuizzes.length > 0) {
                html += '</div>';
            }

            // Add evaluations section
            if (evaluations.length > 0) {
                html += '<div style="margin-top:1rem; border-top:1px solid rgba(32,0,130,0.12); padding-top:1rem;"><div style="margin-bottom:0.75rem;"><h4 style="margin:0 0 0.5rem 0; color:var(--primary); font-size:0.95rem;">Evaluations</h4></div>';
                evaluations.forEach((evaluation) => {
                    html += `
                        <div style="padding:0.4rem 0.8rem; background:rgba(168,85,247,0.08); border-radius:6px; margin-bottom:0.4rem; display:flex; align-items:center; justify-content:space-between;">
                            <span style="color:var(--text);"><i class="fas fa-file-check" style="margin-right:0.5rem;"></i>Evaluation: ${evaluation.title}</span>
                            <button type="button" class="hierarchy-edit" data-edit-url="?page=instructor/elearning-subpage/evaluation&${moduleBackParam}&id=${evaluation.id}" style="padding:0.3rem 0.6rem; font-size:0.75rem; background:var(--primary); color:var(--surface); border:none; border-radius:4px; cursor:pointer; font-weight:700;">Edit</button>
                        </div>
                    `;
                });
                html += '</div>';
            } else if (lessonsWithQuizzes.length === 0) {
                html += '<p style="color:#999; text-align:center; font-size:0.9rem;">No lessons or evaluations yet</p>';
            }

            html += '</div></div>';

            const container = document.createElement('div');
            container.innerHTML = html;
            form.parentNode.insertBefore(container, form.nextSibling);

            // Attach toggle listeners
            container.querySelectorAll('.hierarchy-toggle').forEach((item) => {
                item.onclick = (e) => {
                    if (e.target.closest('.hierarchy-edit')) return;
                    const targetId = item.dataset.toggleTarget;
                    const content = container.querySelector(`.hierarchy-content[data-toggle-id="${targetId}"]`);
                    if (content) {
                        const shouldShow = content.style.display === 'none';
                        content.style.display = shouldShow ? 'block' : 'none';
                        const arrow = item.querySelector('.hierarchy-arrow');
                        if (arrow) arrow.textContent = shouldShow ? '▼' : '▶';
                    }
                };
            });

            // Attach edit listeners
            container.querySelectorAll('.hierarchy-edit').forEach((btn) => {
                btn.onclick = () => {
                    window.location.href = btn.dataset.editUrl;
                };
            });

            // Reorder lessons with drag-and-drop
            const reorderList = container.querySelector('.reorder-list[data-entity="lesson"]');
            if (reorderList) {
                let dragState = null;

                reorderList.querySelectorAll('.reorder-item').forEach((item) => {
                    item.addEventListener('dragstart', () => {
                        const sourceList = item.closest('.reorder-list');
                        dragState = {
                            id: Number(item.dataset.id),
                            entity: sourceList ? sourceList.dataset.entity : 'lesson',
                            sourceParentId: sourceList ? Number(sourceList.dataset.parentId) : mid,
                            sourceList: sourceList
                        };
                        item.style.opacity = '0.5';
                    });

                    item.addEventListener('dragend', () => {
                        item.style.opacity = '1';
                        dragState = null;
                    });

                    item.addEventListener('dragover', (e) => {
                        e.preventDefault();
                    });

                    item.addEventListener('drop', (e) => {
                        e.preventDefault();
                        if (!dragState || dragState.entity !== 'lesson') return;

                        const targetList = item.closest('.reorder-list');
                        if (!targetList) return;

                        const draggedId = dragState.id;
                        const targetParentId = Number(targetList.dataset.parentId);
                        const targetIds = Array.from(targetList.querySelectorAll('.reorder-item')).map((node) => Number(node.dataset.id)).filter((id) => id !== draggedId);
                        const dropIndex = targetIds.indexOf(Number(item.dataset.id));
                        const finalTargetIds = [...targetIds];
                        if (dropIndex >= 0) {
                            finalTargetIds.splice(dropIndex, 0, draggedId);
                        } else {
                            finalTargetIds.push(draggedId);
                        }

                        const sourceIds = dragState.sourceList && dragState.sourceList !== targetList
                            ? Array.from(dragState.sourceList.querySelectorAll('.reorder-item')).map((node) => Number(node.dataset.id)).filter((id) => id !== draggedId)
                            : [];

                        fetch('pages/instructor/elearning-subpage/ajax/reorder-children.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
                            body: new URLSearchParams({
                                entity: 'lesson',
                                parent_id: String(targetParentId),
                                source_parent_id: String(dragState.sourceParentId),
                                moved_id: String(draggedId),
                                ids: finalTargetIds.join(','),
                                source_ids: sourceIds.join(',')
                            })
                        })
                        .then(r => r.json())
                        .then((data) => {
                            if (data.success) {
                                loadModuleHierarchy(mid);
                            }
                        })
                        .catch(() => {});
                    });
                });
            }

            // Handle "Add Lesson" modal form submission
            const addLessonForm = document.getElementById('add-lesson-in-modal-form');
            if (addLessonForm) {
                addLessonForm.onsubmit = (e) => {
                    e.preventDefault();
                    const submitBtn = addLessonForm.querySelector('button[type="submit"]');
                    const originalText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Adding...';

                    const formData = new FormData(addLessonForm);
                    formData.append('module_id', mid);

                    fetch('pages/instructor/elearning-subpage/ajax/add-lesson.php', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            // Close modal
                            const modal = document.getElementById('add-lesson-modal');
                            if (modal) modal.style.display = 'none';
                            
                            // Reset form
                            addLessonForm.reset();
                            
                            // Refresh hierarchy after a brief delay
                            setTimeout(() => {
                                loadModuleHierarchy(mid);
                            }, 300);
                        } else {
                            const notif = document.createElement('div');
                            notif.textContent = 'Error adding lesson: ' + (data.message || 'Unknown error');
                            notif.style.cssText = 'position:fixed;top:20px;right:20px;padding:1rem 1.5rem;border-radius:8px;background:#ef4444;color:#fff;z-index:10000;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
                            document.body.appendChild(notif);
                            setTimeout(() => notif.remove(), 4000);
                        }
                    })
                    .catch(err => {
                        const notif = document.createElement('div');
                        notif.textContent = 'Error adding lesson: ' + err.message;
                        notif.style.cssText = 'position:fixed;top:20px;right:20px;padding:1rem 1.5rem;border-radius:8px;background:#ef4444;color:#fff;z-index:10000;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
                        document.body.appendChild(notif);
                        setTimeout(() => notif.remove(), 4000);
                        console.error('Error:', err);
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    });
                };
            }
        })
        .catch(err => console.error('Error loading hierarchy:', err));
    }

    document.addEventListener('click', function (event) {
        const openTrigger = event.target.closest('[data-open-modal]');
        if (openTrigger) {
            const modal = document.getElementById(openTrigger.dataset.openModal);
            if (modal) {
                if (openTrigger.dataset.openModal === 'add-quiz-modal') {
                    const lessonIdField = document.getElementById('add-quiz-lesson-id');
                    const moduleIdField = document.getElementById('add-quiz-module-id');
                    if (lessonIdField && openTrigger.dataset.lessonId) lessonIdField.value = openTrigger.dataset.lessonId;
                    if (moduleIdField && openTrigger.dataset.moduleId) moduleIdField.value = openTrigger.dataset.moduleId;
                }
                modal.style.display = 'flex';
            }
            return;
        }

        const closeTrigger = event.target.closest('[data-close-modal]');
        if (closeTrigger) {
            const modal = document.getElementById(closeTrigger.dataset.closeModal);
            if (modal) modal.style.display = 'none';
            return;
        }

        const backdrop = event.target.closest('#add-lesson-modal');
        if (backdrop && event.target === backdrop) {
            backdrop.style.display = 'none';
        }
    });

    function showNotif(msg, type) {
        const el = document.createElement('div');
        el.style.cssText = 'position:fixed;top:20px;right:20px;padding:0.8rem 1.2rem;border-radius:8px;font-weight:600;font-size:0.85rem;z-index:10000;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
        el.style.background = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6';
        el.style.color = '#fff';
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 3000);
    }

    // Handle "Add Quiz" (under a lesson) modal form submission.
    // Delegated on document so it survives re-injection of the subpage HTML.
    document.addEventListener('submit', function (e) {
        const addQuizForm = e.target && e.target.id === 'add-quiz-in-modal-form' ? e.target : null;
        if (!addQuizForm) return;
        e.preventDefault();

        // ADD mode: collect the quiz in-memory (module doesn't exist yet); saved with the module
        if (!isEditMode) {
            const qTitle = (addQuizForm.querySelector('input[name="title"]').value || '').trim();
            if (!qTitle) { showNotif('Quiz title is required.', 'error'); return; }
            pendingQuizzes.push({
                title: qTitle,
                duration_seconds: parseInt(addQuizForm.querySelector('input[name="duration_seconds"]').value || '600', 10),
                passing_score: addQuizForm.querySelector('input[name="passing_score"]').value ? parseFloat(addQuizForm.querySelector('input[name="passing_score"]').value) : null,
                max_attempts: parseInt(addQuizForm.querySelector('input[name="max_attempts"]').value || '2', 10),
                question_count: addQuizForm.querySelector('input[name="question_count"]').value ? parseInt(addQuizForm.querySelector('input[name="question_count"]').value, 10) : null,
                show_answers_after_submit: addQuizForm.querySelector('input[name="show_answers_after_submit"]').checked ? 1 : 0,
                status: addQuizForm.querySelector('select[name="status"]').value
            });
            addQuizForm.reset();
            const modal = document.getElementById('add-quiz-modal');
            if (modal) modal.style.display = 'none';
            renderPendingContent();
            showNotif('Quiz added.', 'success');
            return;
        }

        const submitBtn = addQuizForm.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : '';
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Adding...'; }

        const lessonId = parseInt(document.getElementById('add-quiz-lesson-id').value || 0, 10);
        const moduleId = parseInt(document.getElementById('add-quiz-module-id').value || 0, 10);
        if (!lessonId) {
            showNotif('Missing lesson. Please try again.', 'error');
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = originalText; }
            return;
        }

        const formData = new FormData(addQuizForm);
        if (!formData.get('module_id')) formData.append('module_id', String(moduleId));

        fetch('pages/instructor/elearning-subpage/ajax/add-quiz.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                const modal = document.getElementById('add-quiz-modal');
                if (modal) modal.style.display = 'none';
                addQuizForm.reset();
                showNotif('Quiz added successfully.', 'success');
                if (typeof loadModuleHierarchy === 'function' && moduleId) {
                    setTimeout(function () { loadModuleHierarchy(moduleId); }, 300);
                }
            } else {
                showNotif('Error adding quiz: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(function (err) {
            showNotif('Error adding quiz: ' + err.message, 'error');
        })
        .finally(function () {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = originalText; }
        });
    });
})();
</script>
<script>
(function() {
    // Initialize DropZone for module thumbnail
    var thumbDrop = document.getElementById('module-thumb-dropzone');
    if (thumbDrop && typeof DropZone !== 'undefined') {
        DropZone.init(thumbDrop, {
            accept: 'image/*',
            label: 'Drag & drop a cover photo here',
            fieldName: 'thumbnail'
        });
    }

    // Show notification popup
    function showNotification(message, type = 'info', duration = 4000) {
        const id = 'notification-' + Date.now();
        const notification = document.createElement('div');
        notification.id = id;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            z-index: 10000;
            animation: slideIn 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            max-width: 400px;
        `;
        
        if (type === 'success') {
            notification.style.background = '#10b981';
            notification.style.color = '#fff';
        } else if (type === 'error') {
            notification.style.background = '#ef4444';
            notification.style.color = '#fff';
        } else if (type === 'warning') {
            notification.style.background = '#f59e0b';
            notification.style.color = '#fff';
        } else {
            notification.style.background = '#3b82f6';
            notification.style.color = '#fff';
        }
        
        notification.textContent = message;
        document.body.appendChild(notification);
        
        if (duration > 0) {
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }
    }

    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
    `;
    document.head.appendChild(style);

    // NOTE: Module data loading is handled in the first IIFE above (in edit mode with hierarchy display)
    // This second IIFE only handles form submission and does NOT reload module hierarchy
    // to prevent duplicate/conflicting hierarchy displays
})();
</script>
</div>
