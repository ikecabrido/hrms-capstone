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

    <div class="toolbar">
        <div class="toolbar-search">
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
        // Change heading and hide course selector
        document.querySelector('.mode-card h2').textContent = 'Edit Module';
        document.querySelector('.mode-card p').textContent = 'Update module details and view hierarchical content structure.';
        const courseLabel = document.querySelector('label:has(input[id="module-course-search"])');
        if (courseLabel) courseLabel.style.display = 'none';
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

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton ? submitButton.textContent : '';
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';
        }

        const formData = new FormData(form);
        const action = isEditMode ? 'pages/instructor/elearning-subpage/ajax/edit-module.php' : form.action;

        fetch(action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
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
                            <button type="button" class="hierarchy-edit" data-edit-url="?page=instructor/elearning-subpage/lesson&id=${lesson.id}" style="padding:0.3rem 0.6rem; font-size:0.75rem; background:var(--primary); color:var(--surface); border:none; border-radius:4px; cursor:pointer; font-weight:700;">Edit</button>
                        </div>
                        <div class="hierarchy-content" data-toggle-id="lesson-${lesson.id}" style="display:block; padding-left:2rem; margin-top:0.4rem;">
                            ${lesson.quizzes.length > 0 ? lesson.quizzes.map((quiz) => `
                                <div style="padding:0.4rem 0.8rem; background:rgba(59,130,246,0.08); border-radius:6px; margin-bottom:0.4rem; display:flex; align-items:center; justify-content:space-between;">
                                    <span style="color:var(--text);"><i class="fas fa-circle-question" style="margin-right:0.5rem;"></i>Quiz: ${quiz.title}</span>
                                    <button type="button" class="hierarchy-edit" data-edit-url="?page=instructor/elearning-subpage/quiz&id=${quiz.id}" style="padding:0.3rem 0.6rem; font-size:0.75rem; background:var(--primary); color:var(--surface); border:none; border-radius:4px; cursor:pointer; font-weight:700;">Edit</button>
                                </div>
                            `).join('') : '<p style="color:#ccc; font-size:0.8rem; margin:0.2rem 0;">No quizzes yet</p>'}
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
                            <button type="button" class="hierarchy-edit" data-edit-url="?page=instructor/elearning-subpage/evaluation&id=${evaluation.id}" style="padding:0.3rem 0.6rem; font-size:0.75rem; background:var(--primary); color:var(--surface); border:none; border-radius:4px; cursor:pointer; font-weight:700;">Edit</button>
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
                        }
                    })
                    .catch(err => console.error('Error:', err))
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
            if (modal) modal.style.display = 'flex';
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
