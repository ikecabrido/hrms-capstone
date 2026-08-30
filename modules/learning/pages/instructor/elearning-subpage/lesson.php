<?php
// Pre-load modules for the datalist
$lessonPageModules = [];
try {
    require_once dirname(__DIR__, 3) . '/classes/module.php';
    require_once dirname(__DIR__, 5) . '/database/db.php';
    $lessonPageDb = new Database();
    $lessonPagePdo = $lessonPageDb->getConnection();
    $lessonPageModObj = new Module($lessonPagePdo);
    $lessonPageModules = $lessonPageModObj->getList();
} catch (Throwable $e) {
    $lessonPageModules = [];
}
?>
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<div class="module-content">
    <!-- Add Quiz Modal -->
    <div id="add-quiz-modal" class="modal-overlay" style="display:none; z-index:2000;">
        <div style="background:#fff; border:1px solid rgba(32, 0, 130, 0.12); border-radius:18px; width:min(500px, 92vw); max-height:80vh; overflow-y:auto; box-shadow:0 18px 45px rgba(32, 0, 130, 0.18);">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32, 0, 130, 0.12); background:linear-gradient(135deg, rgba(32, 0, 130, 0.08), rgba(81, 70, 183, 0.05));">
                <h2 style="margin:0; font-size:1.1rem; color:var(--primary);">Add Quiz</h2>
                <button type="button" data-close-modal="add-quiz-modal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">✕</button>
            </div>
            <div style="padding:1.5rem;">
                <form id="add-quiz-in-modal-form">
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Quiz Title *</span>
                        <input type="text" name="title" required placeholder="e.g. Chapter 1 Assessment" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Duration (seconds)</span>
                        <input type="number" name="duration_seconds" min="30" value="600" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Passing Score (%)</span>
                        <input type="number" name="passing_score" min="0" max="100" step="0.01" value="75" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Max Attempts</span>
                        <input type="number" name="max_attempts" min="1" value="2" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Status</span>
                        <select name="status" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;">
                            <option value="active" selected>Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </label>
                    <div style="display:flex; gap:0.75rem;">
                        <button type="submit" style="flex:1; padding:0.8rem; background:var(--primary); color:var(--surface); border:none; border-radius:8px; cursor:pointer; font-weight:700;">Add Quiz</button>
                        <button type="button" data-close-modal="add-quiz-modal" style="flex:1; padding:0.8rem; background:rgba(32, 0, 130, 0.08); color:var(--primary); border:1px solid rgba(32, 0, 130, 0.18); border-radius:8px; cursor:pointer; font-weight:700;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="toolbar">
        <div class="toolbar-search">
            <input type="search" placeholder="Search lesson form..." aria-label="Search lesson form" />
        </div>
        
    </div>

    <div class="mode-card">
        <h2 id="lesson-form-title">Add Lesson</h2>
        <p id="lesson-form-desc">A lesson sits inside a module. This is where the actual learning material is added.</p>

        <form id="add-lesson-form" data-skip="true" method="post" enctype="multipart/form-data" action="pages/instructor/elearning-subpage/ajax/add-lesson.php">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-top:1rem;">
                <label>
                    <span>Module</span>
                    <input type="text" list="lesson-module-list" id="lesson-module-search" placeholder="Search module by name" required style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                    <input type="hidden" name="module_id" id="lesson-module-id" required />
                    <datalist id="lesson-module-list">
                        <?php foreach ($lessonPageModules as $mod): ?>
                        <option value="<?php echo htmlspecialchars($mod['title']); ?>" data-id="<?php echo (int)$mod['id']; ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </label>
                <label>
                    <span>Lesson title</span>
                    <input type="text" name="title" required placeholder="e.g. Introduction to SQL" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                </label>
                <label>
                    <span>Content type</span>
                    <select name="content_type" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                        <option value="text" selected>Text</option>
                        <option value="video">Video</option>
                        <option value="file">File</option>
                        <option value="mixed">Mixed</option>
                    </select>
                </label>
                <label>
                    <span>Status</span>
                    <select name="status" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                        <option value="active" selected>Active</option>
                        <option value="archived">Archived</option>
                    </select>
                </label>
            </div>

            <div style="display:block; margin-top:1rem;">
                <span style="font-weight:600;">Cover photo</span>
                <div id="lesson-thumb-dropzone"></div>
            </div>

            <label style="display:block; margin-top:1rem;">
                <span>Video URL</span>
                <input type="url" name="video_url" placeholder="https://..." style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
            </label>

            <div style="margin-top:1rem;">
                <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Lesson body</span>
                <p style="color:#666; font-size:0.85rem; margin:0 0 0.5rem 0;">Use the toolbar to format text, embed images, upload videos, or add YouTube videos.</p>
                <input type="hidden" name="content_body" id="quill-content-hidden" />
                <input type="hidden" name="order_index" value="0" />
                <div id="quill-editor" style="min-height:300px; border-radius:10px; border:1px solid var(--border); background:#fff;"></div>
            </div>

            <!-- YouTube Embed Modal -->
            <div id="youtube-embed-modal" class="modal-overlay" style="display:none; z-index:3000;">
                <div style="background:#fff; border-radius:14px; width:min(480px, 92vw); box-shadow:0 18px 45px rgba(0,0,0,0.2);">
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid #eee;">
                        <h3 style="margin:0; font-size:1rem;">Embed YouTube Video</h3>
                        <button type="button" id="close-youtube-modal" style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:#666;">&times;</button>
                    </div>
                    <div style="padding:1.5rem;">
                        <label style="display:block; margin-bottom:1rem;">
                            <span style="display:block; margin-bottom:0.35rem; font-weight:600;">YouTube URL</span>
                            <input type="url" id="youtube-url-input" placeholder="https://www.youtube.com/watch?v=..." style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                        </label>
                        <div style="display:flex; gap:0.75rem;">
                            <button type="button" id="insert-youtube-btn" style="flex:1; padding:0.75rem; background:var(--primary); color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600;">Embed</button>
                            <button type="button" id="cancel-youtube-btn" style="flex:1; padding:0.75rem; background:#f3f4f6; color:#333; border:none; border-radius:8px; cursor:pointer; font-weight:600;">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mode-actions" style="margin-top:1.5rem;">
                <button type="submit" class="mode-button">Save Lesson</button>
                
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    // Check for edit mode
    const params = new URLSearchParams(window.location.search);
    const lessonId = params.get('id');
    const isEditMode = !!lessonId;

    if (isEditMode) {
        // Change heading and hide module selector
        document.getElementById('lesson-form-title').textContent = 'Edit Lesson';
        document.getElementById('lesson-form-desc').textContent = 'Update lesson details and view hierarchical quiz structure.';
        const moduleLabel = document.querySelector('label:has(input[id="lesson-module-search"])');
        if (moduleLabel) moduleLabel.style.display = 'none';
    }

    const form = document.getElementById('add-lesson-form');
    if (!form) return;

    const moduleSearch = document.getElementById('lesson-module-search');
    const moduleIdField = document.getElementById('lesson-module-id');
    const moduleList = document.getElementById('lesson-module-list');

    var moduleOptions = <?php echo json_encode(array_map(function($m) { return ['id' => (int)$m['id'], 'name' => trim($m['title'])]; }, $lessonPageModules), JSON_HEX_TAG); ?> || [];

    moduleSearch.addEventListener('change', function () {
        const selected = Array.from(moduleList.options).find(function (option) {
            return option.value === moduleSearch.value;
        });

        if (selected) {
            moduleIdField.value = selected.dataset.id || '';
        } else {
            moduleIdField.value = '';
        }
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
        const action = isEditMode ? 'pages/instructor/elearning-subpage/ajax/edit-lesson.php' : form.action;

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
                throw new Error(data.message || (isEditMode ? 'Unable to update lesson.' : 'Unable to create lesson.'));
            }

            if (window.showToast) window.showToast(data.message || (isEditMode ? 'Lesson updated' : 'Lesson created'), 'success');
            if (!isEditMode) {
                form.reset();
                moduleIdField.value = '';
                moduleSearch.value = '';
            }
        })
        .catch(function (error) {
            if (window.showToast) window.showToast(error.message || 'Failed to save lesson', 'error');
        })
        .finally(function () {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        });
    });

    // If in edit mode, load lesson data and display quizzes
    if (isEditMode && lessonId) {
        fetch('pages/instructor/elearning-subpage/ajax/get-lesson-by-id.php?id=' + lessonId, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.data) return;

            const lesson = data.data;

            // Populate form
            form.querySelector('input[name="title"]').value = lesson.title || '';
            form.querySelector('select[name="content_type"]').value = lesson.content_type || 'text';
            form.querySelector('input[name="order_index"]').value = lesson.order_index || 0;
            form.querySelector('select[name="status"]').value = lesson.status || 'active';
            form.querySelector('input[name="video_url"]').value = lesson.video_url || '';
            moduleIdField.value = lesson.module_id || '';
            moduleSearch.value = lesson.module_name || '';

            // Add hidden ID field
            if (!form.querySelector('input[name="id"]')) {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = lesson.id;
                form.appendChild(idInput);
            }

            // Load and display quizzes
            loadLessonQuizzes(lessonId);
        })
        .catch(e => console.error('Error loading lesson:', e));
    }

    // Function to load and display quizzes for this lesson
    function loadLessonQuizzes(lid) {
        fetch(`pages/instructor/elearning-subpage/ajax/get-quizzes-by-lesson.php?lesson_id=${lid}`, { credentials: 'same-origin' })
        .then(r => r.json())
        .then((quizzesRes) => {
            const quizzes = quizzesRes.success ? quizzesRes.items : [];

            let html = '<div style="margin-top:2rem; padding:1.5rem; background:#f9fafb; border-radius:10px; border:1px solid var(--border);"><div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;"><h3 style="margin:0; color:var(--primary);"><i class="fas fa-circle-question" style="margin-right:0.5rem;"></i>Quizzes</h3><button id="add-quiz-btn" type="button" data-open-modal="add-quiz-modal" style="padding:0.5rem 1rem; background:var(--primary); color:var(--surface); border:none; border-radius:6px; cursor:pointer; font-weight:700; font-size:0.85rem;">+ Add Quiz</button></div>';
            html += '<div style="font-size:0.9rem; line-height:1.8;">';

            if (quizzes.length > 0) {
                quizzes.forEach((quiz) => {
                    html += `
                        <div style="padding:0.5rem 0.8rem; background:rgba(59,130,246,0.08); border-radius:6px; margin-bottom:0.5rem; display:flex; align-items:center; justify-content:space-between;">
                            <span style="color:var(--text);">${quiz.title}</span>
                            <button type="button" class="hierarchy-edit" data-edit-url="?page=instructor/elearning-subpage/quiz&id=${quiz.id}" style="padding:0.3rem 0.6rem; font-size:0.75rem; background:var(--primary); color:var(--surface); border:none; border-radius:4px; cursor:pointer; font-weight:700;">Edit</button>
                        </div>
                    `;
                });
            } else {
                html += '<p style="color:#999; text-align:center; font-size:0.9rem;">No quizzes yet</p>';
            }

            html += '</div></div>';

            const container = document.createElement('div');
            container.innerHTML = html;
            form.parentNode.insertBefore(container, form.nextSibling);

            // Attach edit listeners
            container.querySelectorAll('.hierarchy-edit').forEach((btn) => {
                btn.onclick = () => {
                    window.location.href = btn.dataset.editUrl;
                };
            });

            // Handle "Add Quiz" modal form submission
            const addQuizForm = document.getElementById('add-quiz-in-modal-form');
            if (addQuizForm) {
                addQuizForm.onsubmit = (e) => {
                    e.preventDefault();
                    const submitBtn = addQuizForm.querySelector('button[type="submit"]');
                    const originalText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Adding...';

                    const formData = new FormData(addQuizForm);
                    formData.append('lesson_id', lid);

                    fetch('pages/instructor/elearning-subpage/ajax/add-quiz.php', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            // Close modal
                            const modal = document.getElementById('add-quiz-modal');
                            if (modal) modal.style.display = 'none';
                            
                            // Reset form
                            addQuizForm.reset();
                            
                            // Refresh hierarchy
                            setTimeout(() => {
                                const oldContainer = form.parentNode.querySelector('[style*="margin-top:2rem"]');
                                if (oldContainer) oldContainer.remove();
                                loadLessonQuizzes(lid);
                            }, 300);
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
        .catch(err => console.error('Error loading quizzes:', err));
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

        const backdrop = event.target.closest('#add-quiz-modal');
        if (backdrop && event.target === backdrop) {
            backdrop.style.display = 'none';
        }
    });
})();
</script>

<script>
(function() {
    // Show notification popup
    function showNotification(message, type = 'info', duration = 4000) {
        const notification = document.createElement('div');
        notification.style.cssText = `position:fixed;top:20px;right:20px;padding:1rem 1.5rem;border-radius:8px;font-weight:500;z-index:10000;color:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.15);`;
        notification.textContent = message;
        if (type === 'success') notification.style.background = '#10b981';
        else if (type === 'error') notification.style.background = '#ef4444';
        else if (type === 'warning') notification.style.background = '#f59e0b';
        else notification.style.background = '#3b82f6';
        document.body.appendChild(notification);
        if (duration > 0) setTimeout(() => notification.remove(), duration);
    }

    const params = new URLSearchParams(window.location.search);
    const lessonId = params.get('id');
    
    if (lessonId) {
        fetch('pages/instructor/elearning-subpage/ajax/get-lesson-by-id.php?id=' + lessonId, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.data) return;
            
            const lesson = data.data;
            const form = document.getElementById('add-lesson-form');
            
            form.parentElement.querySelector('h2').textContent = 'Edit Lesson';
            form.querySelector('input[name="title"]').value = lesson.title || '';
            form.querySelector('select[name="content_type"]').value = lesson.content_type || 'text';
            form.querySelector('select[name="status"]').value = lesson.status || 'active';
            form.querySelector('input[name="video_url"]').value = lesson.video_url || '';
            
            if (lesson.module_id) {
                form.querySelector('#lesson-module-id').value = lesson.module_id;
            }
            
            if (!form.querySelector('input[name="id"]')) {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = lessonId;
                form.appendChild(idInput);
            }
            
            form.action = 'pages/instructor/elearning-subpage/ajax/edit-lesson.php';
            form.querySelector('button[type="submit"]').textContent = 'Update Lesson';
        })
        .catch(e => console.error('Error loading lesson:', e));
    }

    // Handle form submission for both add and edit
    const addLessonForm = document.getElementById('add-lesson-form');
    if (addLessonForm) {
        addLessonForm.addEventListener('submit', function(e) {
            if (this.action.includes('edit-lesson')) {
                e.preventDefault();
                
                const lessonId = this.querySelector('input[name="id"]')?.value;
                if (!lessonId) {
                    showNotification('Lesson ID is missing', 'error');
                    return;
                }

                const formData = new FormData(this);
                const data = {
                    id: parseInt(lessonId),
                    title: formData.get('title'),
                    content_type: formData.get('content_type'),
                    status: formData.get('status'),
                    content_body: document.getElementById('quill-content-hidden')?.value || '',
                    video_url: formData.get('video_url'),
                    module_id: formData.get('module_id') || null
                };

                fetch(this.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(result => {
                    if (result.success) {
                        showNotification('Lesson updated successfully', 'success');
                        setTimeout(() => {
                            window.location.href = '?page=instructor/elearning';
                        }, 1500);
                    } else {
                        showNotification('Error updating lesson: ' + (result.error || 'Unknown error'), 'error');
                    }
                })
                .catch(e => {
                    showNotification('Error: ' + e.message, 'error');
                    console.error('Update error:', e);
                });
            }
        });
    }
})();
</script>

<script>
(function() {
    // Initialize DropZone for lesson thumbnail
    var thumbDrop = document.getElementById('lesson-thumb-dropzone');
    if (thumbDrop && typeof DropZone !== 'undefined') {
        DropZone.init(thumbDrop, {
            accept: 'image/*',
            label: 'Drag & drop a cover photo here',
            fieldName: 'thumbnail'
        });
    }

    // Initialize Quill rich text editor
    const quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Write the lesson content here... Use the toolbar to format text, add images, videos, and more.',
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, 4, 5, 6, false] }],
                [{ font: [] }],
                [{ size: ['small', false, 'large', 'huge'] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ color: [] }, { background: [] }],
                [{ script: 'sub' }, { script: 'super' }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ indent: '-1' }, { indent: '+1' }],
                [{ align: [] }],
                ['blockquote', 'code-block'],
                ['link', 'image', 'video'],
                ['clean']
            ]
        }
    });

    // Store quill instance globally for the image upload handler
    window._lessonQuill = quill;

    // Override image handler to upload files instead of pasting URLs
    const toolbar = quill.getModule('toolbar');
    toolbar.addHandler('image', function() {
        const input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml');
        input.click();
        input.onchange = async function() {
            const file = input.files[0];
            if (!file) return;
            const range = quill.getSelection(true);
            quill.insertText(range.index, '[Uploading image...]', { color: '#999' });
            try {
                const fd = new FormData();
                fd.append('file', file);
                fd.append('media_type', 'image');
                const resp = await fetch('pages/instructor/elearning-subpage/ajax/upload-lesson-media.php', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await resp.json();
                quill.deleteText(range.index, '[Uploading image...]'.length);
                if (data.success) {
                    quill.insertEmbed(range.index, 'image', data.url);
                    if (window.showToast) window.showToast('Image uploaded', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Image upload failed', 'error');
                }
            } catch (err) {
                quill.deleteText(range.index, '[Uploading image...]'.length);
                if (window.showToast) window.showToast('Image upload failed', 'error');
            }
        };
    });

    // Override video handler to upload video files
    toolbar.addHandler('video', function() {
        const input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'video/mp4,video/webm,video/ogg');
        input.click();
        input.onchange = async function() {
            const file = input.files[0];
            if (!file) return;
            const range = quill.getSelection(true);
            quill.insertText(range.index, '[Uploading video...]', { color: '#999' });
            try {
                const fd = new FormData();
                fd.append('file', file);
                fd.append('media_type', 'video');
                const resp = await fetch('pages/instructor/elearning-subpage/ajax/upload-lesson-media.php', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await resp.json();
                quill.deleteText(range.index, '[Uploading video...]'.length);
                if (data.success) {
                    quill.insertEmbed(range.index, 'video', data.url);
                    if (window.showToast) window.showToast('Video uploaded', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Video upload failed', 'error');
                }
            } catch (err) {
                quill.deleteText(range.index, '[Uploading video...]'.length);
                if (window.showToast) window.showToast('Video upload failed', 'error');
            }
        };
    });

    // YouTube embed modal
    const ytModal = document.getElementById('youtube-embed-modal');
    const ytUrlInput = document.getElementById('youtube-url-input');
    const ytInsertBtn = document.getElementById('insert-youtube-btn');
    const ytCancelBtn = document.getElementById('cancel-youtube-btn');
    const ytCloseBtn = document.getElementById('close-youtube-modal');

    function extractYouTubeId(url) {
        const patterns = [
            /(?:youtube\.com\/watch\?v=)([a-zA-Z0-9_-]{11})/,
            /(?:youtu\.be\/)([a-zA-Z0-9_-]{11})/,
            /(?:youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/,
            /(?:youtube\.com\/v\/)([a-zA-Z0-9_-]{11})/,
        ];
        for (const p of patterns) {
            const m = url.match(p);
            if (m) return m[1];
        }
        return null;
    }

    // Add YouTube button to toolbar manually
    const toolbarContainer = document.querySelector('.ql-toolbar');
    if (toolbarContainer) {
        const ytBtn = document.createElement('button');
        ytBtn.className = 'ql-youtube';
        ytBtn.innerHTML = '<i class="fab fa-youtube" style="color:#ff0000; font-size:1rem;"></i>';
        ytBtn.title = 'Embed YouTube Video';
        ytBtn.style.cssText = 'background:none; border:1px solid #ccc; border-radius:4px; cursor:pointer; padding:3px 6px; display:flex; align-items:center; justify-content:center;';
        const cleanBtn = toolbarContainer.querySelector('.ql-clean');
        if (cleanBtn) {
            cleanBtn.parentNode.insertBefore(ytBtn, cleanBtn);
        }
        ytBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (ytUrlInput) ytUrlInput.value = '';
            if (ytModal) ytModal.style.display = 'flex';
        });
    }

    if (ytInsertBtn) {
        ytInsertBtn.addEventListener('click', function() {
            const url = (ytUrlInput.value || '').trim();
            const videoId = extractYouTubeId(url);
            if (!videoId) {
                alert('Please enter a valid YouTube URL (e.g. https://www.youtube.com/watch?v=abc123)');
                return;
            }
            const embedUrl = 'https://www.youtube.com/embed/' + videoId;
            const range = quill.getSelection(true);
            quill.insertEmbed(range.index, 'video', embedUrl);
            quill.insertText(range.index + 1, '\n');
            if (ytModal) ytModal.style.display = 'none';
        });
    }

    if (ytCancelBtn) ytCancelBtn.addEventListener('click', function() { if (ytModal) ytModal.style.display = 'none'; });
    if (ytCloseBtn) ytCloseBtn.addEventListener('click', function() { if (ytModal) ytModal.style.display = 'none'; });

    // Sync Quill content to hidden input before form submit
    const form = document.getElementById('add-lesson-form');
    if (form) {
        form.addEventListener('submit', function() {
            const hidden = document.getElementById('quill-content-hidden');
            if (hidden && quill) {
                hidden.value = quill.root.innerHTML;
            }
        });
    }

    // In edit mode, load existing content into Quill
    const params = new URLSearchParams(window.location.search);
    const lessonId = params.get('id');
    if (lessonId) {
        fetch('pages/instructor/elearning-subpage/ajax/get-lesson-by-id.php?id=' + lessonId, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data && data.data.content_body) {
                quill.root.innerHTML = data.data.content_body;
            }
        })
        .catch(e => console.error('Error loading lesson content:', e));
    }
})();
</script>