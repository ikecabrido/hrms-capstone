/**
 * Course Structure Builder — Visual Drag-and-Drop Engine
 * Native HTML5 drag-and-drop for reordering modules, lessons, and quizzes.
 * Inline creation and editing. Persist via reorder-children.php AJAX.
 */
(function () {
    'use strict';

    /* ──────────────── State ──────────────── */
    let currentCourseId = null;
    let courseData = null;   // { modules: [...] }
    let dragState = {
        type: null,        // 'module' | 'lesson' | 'quiz'
        el: null,
        startX: 0,
        startY: 0
    };

    /* ──────────────── Helpers ──────────────── */
    const $ = (sel, ctx) => (ctx || document).querySelector(sel);
    const $$ = (sel, ctx) => [...(ctx || document).querySelectorAll(sel)];

    let _toastContainer = null;
    function toast(msg, type = 'success') {
        if (!_toastContainer) {
            _toastContainer = document.createElement('div');
            _toastContainer.style.cssText = 'position:fixed; top:20px; right:20px; z-index:100000; display:flex; flex-direction:column; gap:0.5rem; pointer-events:none;';
            document.body.appendChild(_toastContainer);
        }
        const t = document.createElement('div');
        const colors = { success: '#10b981', error: '#ef4444', info: '#3b82f6', warning: '#f59e0b' };
        t.style.cssText = `padding:0.75rem 1.25rem; border-radius:8px; color:#fff; font-weight:600; font-size:0.9rem; box-shadow:0 4px 12px rgba(0,0,0,0.15); animation:fadeSlideIn 0.25s ease; background:${colors[type] || colors.info};`;
        t.textContent = msg;
        _toastContainer.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity 0.3s'; setTimeout(() => t.remove(), 300); }, 3000);
    }

    /* ──────────────── API ──────────────── */
    const API = {
        ajax: (url, opts = {}) => fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', ...(opts.headers || {}) },
            ...opts
        }).then(r => r.json()),

        getCourses: () => API.ajax('pages/instructor/elearning-subpage/ajax/get-course.php'),

        getStructure: (courseId) => API.ajax(`pages/instructor/elearning-subpage/ajax/get-modules-by-course.php?course_id=${courseId}`)
            .then(async data => {
                const modules = data.success ? data.items : [];
                for (const mod of modules) {
                    const lessonsRes = await API.ajax(`pages/instructor/elearning-subpage/ajax/get-lessons-by-module.php?module_id=${mod.id}`);
                    mod.lessons = lessonsRes.success ? lessonsRes.items : [];
                    for (const les of mod.lessons) {
                        const quizzesRes = await API.ajax(`pages/instructor/elearning-subpage/ajax/get-quizzes-by-lesson.php?lesson_id=${les.id}`);
                        les.quizzes = quizzesRes.success ? quizzesRes.items : [];
                        // Load files
                        try {
                            const filesRes = await API.getLessonFiles(les.id);
                            les._files = filesRes.success ? filesRes.items : [];
                            lessonFilesCache[les.id] = les._files;
                        } catch (e) { les._files = []; }
                    }
                    // module-level quizzes
                    const modQuizzesRes = await API.ajax(`pages/instructor/elearning-subpage/ajax/get-quizzes-by-lesson.php?lesson_id=0&module_id=${mod.id}`).catch(() => ({ success: false, items: [] }));
                    mod.quizzes = modQuizzesRes.success ? modQuizzesRes.items : [];
                }
                // Fetch evaluations
                const evalsRes = await API.ajax(`pages/instructor/elearning-subpage/ajax/get-evaluations-by-course.php?course_id=${courseId}`).catch(() => ({ success: false, items: [] }));
                const evaluations = evalsRes.success ? evalsRes.items : [];
                return { modules, evaluations };
            }),

        reorder: (entity, parentId, ids, sourceParentId, movedId) => {
            const fd = new FormData();
            fd.append('entity', entity);
            fd.append('parent_id', parentId);
            fd.append('ids', ids.join(','));
            if (sourceParentId !== parentId) {
                fd.append('source_parent_id', sourceParentId);
            }
            fd.append('moved_id', movedId);
            return API.ajax('pages/instructor/elearning-subpage/ajax/reorder-children.php', {
                method: 'POST',
                body: fd
            });
        },

        addModule: (courseId, title) => {
            const fd = new FormData();
            fd.append('course_id', courseId);
            fd.append('title', title);
            fd.append('status', 'active');
            return API.ajax('pages/instructor/elearning-subpage/ajax/add-module.php', { method: 'POST', body: fd });
        },

        addLesson: (moduleId, title, contentType) => {
            const fd = new FormData();
            fd.append('module_id', moduleId);
            fd.append('title', title);
            fd.append('content_type', contentType || 'text');
            fd.append('status', 'active');
            return API.ajax('pages/instructor/elearning-subpage/ajax/add-lesson.php', { method: 'POST', body: fd });
        },

        addQuiz: (moduleId, title) => {
            const fd = new FormData();
            fd.append('module_id', moduleId);
            fd.append('title', title);
            fd.append('duration_seconds', 600);
            fd.append('passing_score', 75);
            fd.append('max_attempts', 2);
            fd.append('question_count', 10);
            fd.append('status', 'active');
            return API.ajax('pages/instructor/elearning-subpage/ajax/add-quiz.php', { method: 'POST', body: fd });
        },

        editModule: (id, title) => {
            const fd = new FormData();
            fd.append('id', id);
            fd.append('title', title);
            return API.ajax('pages/instructor/elearning-subpage/ajax/edit-module.php', { method: 'POST', body: fd });
        },

        editLesson: (id, title) => {
            const fd = new FormData();
            fd.append('id', id);
            fd.append('title', title);
            return API.ajax('pages/instructor/elearning-subpage/ajax/edit-lesson.php', { method: 'POST', body: fd });
        },

        editQuiz: (id, title) => {
            const fd = new FormData();
            fd.append('id', id);
            fd.append('title', title);
            return API.ajax('pages/instructor/elearning-subpage/ajax/edit-quiz.php', { method: 'POST', body: fd });
        },

        archive: (entity, id) => {
            const endpoint = `pages/instructor/elearning-subpage/ajax/archive-${entity}.php`;
            return API.ajax(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
        },

        // Lesson file APIs
        getLessonFiles: (lessonId) => API.ajax(`pages/instructor/elearning-subpage/ajax/get-lesson-files.php?lesson_id=${lessonId}`),

        saveLessonFile: (lessonId, filePath, title) => {
            const fd = new FormData();
            fd.append('lesson_id', lessonId);
            fd.append('file_path', filePath);
            fd.append('title', title);
            return API.ajax('pages/instructor/elearning-subpage/ajax/save-lesson-file.php', { method: 'POST', body: fd });
        },

        deleteLessonFile: (fileId) => {
            return API.ajax('pages/instructor/elearning-subpage/ajax/delete-lesson-file.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: fileId })
            });
        },

        uploadMedia: (file) => {
            const fd = new FormData();
            fd.append('file', file);
            fd.append('media_type', file.type.startsWith('video/') ? 'video' : 'image');
            return API.ajax('pages/instructor/elearning-subpage/ajax/upload-lesson-media.php', { method: 'POST', body: fd });
        },

        // Quiz/Evaluation question APIs
        getQuizQuestions: (id, itemType) => API.ajax(`pages/instructor/elearning-subpage/ajax/get-quiz-questions.php?id=${id}&type=${itemType || 'quiz'}`),

        addQuizQuestion: (quizId, text, type, itemType, referenceId) => API.ajax('pages/instructor/elearning-subpage/ajax/add-quiz-question.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ quiz_id: quizId, question_text: text, question_type: type, item_type: itemType || 'quiz', reference_id: referenceId || quizId })
        }),

        editQuizQuestion: (id, text, type) => API.ajax('pages/instructor/elearning-subpage/ajax/edit-quiz-question.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, question_text: text, question_type: type })
        }),

        deleteQuizQuestion: (id) => API.ajax('pages/instructor/elearning-subpage/ajax/archive-quiz-question.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        }),

        addQuizOption: (questionId, text, isCorrect) => API.ajax('pages/instructor/elearning-subpage/ajax/add-quiz-option.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ question_id: questionId, option_text: text, is_correct: isCorrect })
        }),

        editQuizOption: (id, text, isCorrect) => API.ajax('pages/instructor/elearning-subpage/ajax/edit-quiz-option.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, option_text: text, is_correct: isCorrect })
        }),

        deleteQuizOption: (id) => API.ajax('pages/instructor/elearning-subpage/ajax/archive-quiz-option.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        }),

        // Template APIs
        saveTemplate: (courseId, title, desc) => API.ajax('pages/instructor/elearning-subpage/ajax/save-template.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ course_id: courseId, title, description: desc })
        }),

        getTemplates: () => API.ajax('pages/instructor/elearning-subpage/ajax/get-templates.php'),

        cloneTemplate: (templateId, title) => API.ajax('pages/instructor/elearning-subpage/ajax/clone-template.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ template_id: templateId, title })
        }),

        deleteTemplate: (id) => API.ajax('pages/instructor/elearning-subpage/ajax/delete-template.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        })
    };

    // Cache for lesson files
    const lessonFilesCache = {};
    async function getLessonFilesCached(lessonId) {
        if (lessonFilesCache[lessonId]) return lessonFilesCache[lessonId];
        try {
            const res = await API.getLessonFiles(lessonId);
            lessonFilesCache[lessonId] = res.success ? res.items : [];
        } catch (e) { lessonFilesCache[lessonId] = []; }
        return lessonFilesCache[lessonId];
    }
    function clearFilesCache(lessonId) { delete lessonFilesCache[lessonId]; }

    /* ──────────────── Rendering ──────────────── */
    function renderTree(data) {
        const tree = $('#structure-tree');
        if (!tree) return;
        const modules = data.modules || data;
        const evaluations = data.evaluations || [];

        if (!modules || modules.length === 0) {
            tree.innerHTML = '<div class="cs-empty-drop" data-drop-type="module" data-parent-id="' + currentCourseId + '" style="padding:2rem;"><i class="fas fa-plus-circle" style="margin-right:0.35rem; opacity:.5;"></i> No modules yet — click "+ Add Module" or drag content here</div>';
            return;
        }

        let html = modules.map((mod, mi) => renderModule(mod, mi)).join('');

        // Render evaluations at the bottom
        if (evaluations.length > 0) {
            html += evaluations.map(ev => renderEvaluation(ev)).join('');
        }

        tree.innerHTML = html;
        attachTreeListeners();
    }

    function renderModule(mod, idx) {
        const lessonCount = (mod.lessons || []).length;
        const quizCount = (mod.quizzes || []).length;
        const lessonsHtml = (mod.lessons || []).map((les, li) => renderLesson(les, li, mod.id)).join('');
        const quizzesHtml = (mod.quizzes || []).map((qz, qi) => renderQuiz(qz, qi, mod.id)).join('');

        return `
        <div class="cs-mod" draggable="true" data-type="module" data-id="${mod.id}" data-parent-id="${currentCourseId}">
            <div class="cs-mod-head">
                <span class="drag-handle" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></span>
                <span class="cs-badge cs-badge-mod"><i class="fas fa-cube"></i> Module</span>
                <span class="cs-title" data-id="${mod.id}" data-entity="module">${esc(mod.title)}</span>
                <span class="cs-badge cs-badge-count">${lessonCount} lesson${lessonCount !== 1 ? 's' : ''}</span>
                <span class="cs-badge cs-badge-count">${quizCount} quiz${quizCount !== 1 ? 'zes' : ''}</span>
                <div class="cs-acts">
                    <button class="cs-act-view" data-action="view" data-type="module" data-id="${mod.id}" title="View"><i class="fas fa-eye"></i> View</button>
                    <button class="cs-act-add" data-action="add-lesson" data-module-id="${mod.id}" title="Add Lesson"><i class="fas fa-plus"></i> Lesson</button>
                    <button class="cs-act-add" data-action="add-quiz" data-module-id="${mod.id}" title="Add Quiz"><i class="fas fa-plus"></i> Quiz</button>
                    <button class="cs-act-edit" data-action="edit" data-entity="module" data-id="${mod.id}" title="Rename"><i class="fas fa-pen"></i></button>
                    <button class="cs-act-del" data-action="delete" data-entity="module" data-id="${mod.id}" title="Delete"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>
            <div class="cs-mod-body">
                <div class="cs-section-label lessons"><i class="fas fa-book-open"></i> Lessons</div>
                <div class="cs-drop" data-drop-type="lesson" data-parent-id="${mod.id}">
                    ${lessonsHtml || '<div class="cs-empty-drop" data-drop-type="lesson" data-parent-id="' + mod.id + '"><i class="fas fa-plus-circle" style="margin-right:0.35rem; opacity:.5;"></i> No lessons yet — click "+ Lesson" or drag here</div>'}
                </div>
                ${quizCount > 0 || lessonsHtml ? `
                <div class="cs-section-label quizzes"><i class="fas fa-question-circle"></i> Module Quizzes</div>
                <div class="cs-drop" data-drop-type="quiz" data-parent-id="${mod.id}">
                    ${quizzesHtml || '<div class="cs-empty-drop" data-drop-type="quiz" data-parent-id="' + mod.id + '"><i class="fas fa-plus-circle" style="margin-right:0.35rem; opacity:.5;"></i> No quizzes yet — click "+ Quiz" or drag here</div>'}
                </div>` : ''}
            </div>
        </div>`;
    }

    function renderLesson(les, idx, moduleId) {
        const typeIconMap = { text: 'fa-file-alt', video: 'fa-video', file: 'fa-file', mixed: 'fa-layer-group' };
        const iconClass = typeIconMap[les.content_type] || 'fa-file-alt';
        const quizCount = (les.quizzes || []).length;
        const quizzesHtml = (les.quizzes || []).map((qz, qi) => renderQuiz(qz, qi, moduleId, les.id)).join('');
        const files = les._files || [];
        const filesHtml = files.map(f => {
            const isImage = /\.(jpe?g|png|gif|webp|svg)$/i.test(f.file_path);
            const isVideo = /\.(mp4|webm|ogg)$/i.test(f.file_path);
            const faIcon = isImage ? 'fa-image' : isVideo ? 'fa-video' : 'fa-file';
            return `<div class="cs-file" data-file-id="${f.id}" data-lesson-id="${les.id}">
                <i class="fas ${faIcon}" style="font-size:0.65rem; color:#3b82f6;"></i>
                <span class="cs-file-name" title="${esc(f.file_path)}">${esc(f.title)}</span>
                ${isImage ? `<img src="/${f.file_path}" alt="" class="cs-file-thumb" onerror="this.style.display='none'" />` : ''}
                <button class="cs-file-del" data-file-id="${f.id}" title="Remove file"><i class="fas fa-times"></i></button>
            </div>`;
        }).join('');

        return `
        <div class="cs-les" draggable="true" data-type="lesson" data-id="${les.id}" data-parent-id="${moduleId}">
            <span class="drag-handle" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></span>
            <span class="cs-badge cs-badge-les"><i class="fas ${iconClass}"></i> Lesson</span>
            <span class="cs-title" data-id="${les.id}" data-entity="lesson" style="font-weight:600;">${esc(les.title)}</span>
            <span class="cs-meta">${les.content_type || 'text'}</span>
            <div class="cs-acts">
                <button class="cs-act-view" data-action="view" data-type="lesson" data-id="${les.id}" title="View"><i class="fas fa-eye"></i></button>
                <button class="cs-act-upload" data-action="upload-file" data-lesson-id="${les.id}" title="Upload Media"><i class="fas fa-paperclip"></i></button>
                <button class="cs-act-edit" data-action="edit" data-entity="lesson" data-id="${les.id}" title="Rename"><i class="fas fa-pen"></i></button>
                <button class="cs-act-del" data-action="delete" data-entity="lesson" data-id="${les.id}" title="Delete"><i class="fas fa-trash-alt"></i></button>
            </div>
        </div>
        <input type="file" class="cb-file-input" data-lesson-id="${les.id}" accept="image/*,video/*" style="display:none;" />
        ${files.length > 0 ? `<div class="cs-files" data-lesson-id="${les.id}">${filesHtml}</div>` : ''}
        ${quizCount > 0 ? `
        <div class="cs-drop" data-drop-type="quiz" data-parent-id="${moduleId}" data-lesson-id="${les.id}" style="margin-left:2.25rem; margin-top:0.2rem;">
            ${quizzesHtml}
        </div>` : ''}`;
    }

    function renderQuiz(qz, idx, moduleId, lessonId) {
        return `
        <div class="cs-qz" draggable="true" data-type="quiz" data-id="${qz.id}" data-parent-id="${moduleId}" ${lessonId ? 'data-lesson-id="' + lessonId + '"' : ''}>
            <span class="drag-handle" title="Drag to reorder"><i class="fas fa-grip-vertical" style="font-size:0.7rem;"></i></span>
            <span class="cs-badge cs-badge-qz"><i class="fas fa-question-circle"></i> Quiz</span>
            <span class="cs-title" data-id="${qz.id}" data-entity="quiz" style="font-weight:600; font-size:0.82rem;">${esc(qz.title)}</span>
            <span class="cs-meta">${qz.duration_seconds ? Math.round(qz.duration_seconds / 60) + 'm' : ''} ${qz.passing_score ? '· ' + qz.passing_score + '%' : ''}</span>
            <div class="cs-acts">
                <button class="cs-act-view" data-action="view" data-type="quiz" data-id="${qz.id}" title="View"><i class="fas fa-eye"></i></button>
                <button class="cs-act-edit" data-action="edit" data-entity="quiz" data-id="${qz.id}" title="Rename"><i class="fas fa-pen"></i></button>
                <button class="cs-act-del" data-action="delete" data-entity="quiz" data-id="${qz.id}" title="Delete"><i class="fas fa-trash-alt"></i></button>
            </div>
        </div>`;
    }

    function renderEvaluation(ev) {
        return `
        <div class="cs-eval">
            <div class="cs-mod-head">
                <span class="cs-badge cs-badge-eval"><i class="fas fa-check-circle"></i> Evaluation</span>
                <span class="cs-title" style="color:var(--text, #333);">${esc(ev.title)}</span>
                <div class="cs-acts">
                    <button class="cs-act-view cb-open-eval" data-eval-id="${ev.id}" data-eval-title="${esc(ev.title)}" title="View Questions"><i class="fas fa-eye"></i> Questions</button>
                </div>
            </div>
        </div>`;
    }

    function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    function updateStats(data) {
        const stats = $('#course-stats');
        if (!stats) return;
        const modules = data.modules || data;
        const evaluations = data.evaluations || [];
        const totalLessons = modules.reduce((s, m) => s + (m.lessons || []).length, 0);
        const totalQuizzes = modules.reduce((s, m) => s + (m.quizzes || []).length, 0);
        const totalLessonQuizzes = modules.reduce((s, m) => s + (m.lessons || []).reduce((ls, l) => ls + (l.quizzes || []).length, 0), 0);
        stats.innerHTML = `
            <span class="cs-stat-chip cs-stat-modules"><i class="fas fa-cube"></i> ${modules.length} Module${modules.length !== 1 ? 's' : ''}</span>
            <span class="cs-stat-chip cs-stat-lessons"><i class="fas fa-book-open"></i> ${totalLessons} Lesson${totalLessons !== 1 ? 's' : ''}</span>
            <span class="cs-stat-chip cs-stat-quizzes"><i class="fas fa-question-circle"></i> ${totalQuizzes + totalLessonQuizzes} Quiz${(totalQuizzes + totalLessonQuizzes) !== 1 ? 'zes' : ''}</span>
            ${evaluations.length > 0 ? '<span class="cs-stat-chip cs-stat-evals"><i class="fas fa-check-circle"></i> ' + evaluations.length + ' Evaluation' + (evaluations.length !== 1 ? 's' : '') + '</span>' : ''}
        `;
    }

    /* ──────────────── Inline Editing ──────────────── */
    function startInlineEdit(titleEl) {
        if (titleEl.querySelector('input')) return; // already editing
        const entity = titleEl.dataset.entity;
        const id = titleEl.dataset.id;
        const originalText = titleEl.textContent;
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'cs-inline-edit';
        input.value = originalText;
        input.style.width = Math.max(120, originalText.length * 8 + 40) + 'px';
        titleEl.textContent = '';
        titleEl.appendChild(input);
        input.focus();
        input.select();

        const save = async () => {
            const newTitle = input.value.trim();
            if (newTitle && newTitle !== originalText) {
                try {
                    if (entity === 'module') await API.editModule(id, newTitle);
                    else if (entity === 'lesson') await API.editLesson(id, newTitle);
                    else if (entity === 'quiz') await API.editQuiz(id, newTitle);
                    titleEl.textContent = newTitle;
                    toast(`${entity.charAt(0).toUpperCase() + entity.slice(1)} renamed`);
                } catch (e) {
                    titleEl.textContent = originalText;
                    toast('Rename failed', 'error');
                }
            } else {
                titleEl.textContent = originalText;
            }
        };

        input.addEventListener('blur', save);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
            if (e.key === 'Escape') { input.value = originalText; input.blur(); }
        });
    }

    /* ──────────────── Inline Add Forms ──────────────── */
    function showAddForm(type, parentId, lessonId) {
        // Remove any existing add forms
        $$('.cs-add-form').forEach(f => f.remove());

        let container;
        let placeholderText;
        let extraFields = '';

        if (type === 'module') {
            container = $('#structure-tree');
            placeholderText = 'Module title...';
        } else if (type === 'lesson') {
            container = $(`.cs-drop[data-drop-type="lesson"][data-parent-id="${parentId}"]`);
            if (!container) return;
            placeholderText = 'Lesson title...';
            extraFields = `<select class="cb-add-content-type" style="margin-left:0.5rem;"><option value="text">Text</option><option value="video">Video</option><option value="file">File</option><option value="mixed">Mixed</option></select>`;
        } else if (type === 'quiz') {
            if (lessonId) {
                container = $(`.cs-drop[data-lesson-id="${lessonId}"][data-drop-type="quiz"]`);
            } else {
                container = $(`.cs-drop[data-drop-type="quiz"][data-parent-id="${parentId}"]:not([data-lesson-id])`);
            }
            if (!container) return;
            placeholderText = 'Quiz title...';
        }

        if (!container) return;

        // Remove empty drop messages in the target
        const emptyDrops = container.querySelectorAll('.cs-empty-drop');
        emptyDrops.forEach(d => d.style.display = 'none');

        const form = document.createElement('div');
        form.className = 'cs-add-form';
        form.innerHTML = `
            <div class="cs-add-form-row">
                <span style="font-size:0.72rem; font-weight:800; color:var(--primary, #320082); text-transform:uppercase; letter-spacing:.04em;">${type}:</span>
                <input type="text" class="cb-add-input" placeholder="${placeholderText}" autofocus />
                ${extraFields}
                <button class="cs-add-save">Add</button>
                <button class="cs-add-cancel">✕</button>
            </div>
        `;

        if (type === 'module') {
            container.prepend(form);
        } else {
            container.appendChild(form);
        }

        const input = form.querySelector('.cb-add-input');
        input.focus();

        const doAdd = async () => {
            const title = input.value.trim();
            if (!title) return;
            try {
                if (type === 'module') {
                    await API.addModule(currentCourseId, title);
                } else if (type === 'lesson') {
                    const ct = form.querySelector('.cb-add-content-type');
                    await API.addLesson(parentId, title, ct ? ct.value : 'text');
                } else if (type === 'quiz') {
                    await API.addQuiz(parentId, title);
                }
                toast(`${type.charAt(0).toUpperCase() + type.slice(1)} created!`);
                await reloadStructure();
            } catch (e) {
                toast('Failed to create ' + type, 'error');
            }
        };

        form.querySelector('.cs-add-save').addEventListener('click', doAdd);
        form.querySelector('.cs-add-cancel').addEventListener('click', () => {
            form.remove();
            emptyDrops.forEach(d => d.style.display = '');
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') doAdd();
            if (e.key === 'Escape') { form.remove(); emptyDrops.forEach(d => d.style.display = ''); }
        });
    }

    /* ──────────────── Drag-and-Drop ──────────────── */
    let draggedEl = null;
    let draggedType = null;
    let draggedId = null;
    let draggedParentId = null;

    /* ──────────────── Preview Drawer ──────────────── */
    function openPreviewDrawer(type, id) {
        const overlay = $('#cs-preview-overlay');
        const titleEl = $('#cs-preview-title span');
        const body = $('#cs-preview-body');
        if (!overlay) return;

        body.innerHTML = '<p style="text-align:center; color:var(--muted); padding:2rem;"><i class="fas fa-spinner fa-spin" style="font-size:1.5rem; display:block; margin-bottom:0.5rem; color:var(--primary);"></i> Loading...</p>';
        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';

        if (type === 'module') {
            const mod = findModule(id);
            if (!mod) { body.innerHTML = '<p style="color:#ef4444;">Module not found.</p>'; return; }
            titleEl.textContent = mod.title;
            const lessonCount = (mod.lessons || []).length;
            const quizCount = (mod.quizzes || []).length;
            const lessonQuizzes = (mod.lessons || []).reduce((s, l) => s + (l.quizzes || []).length, 0);
            body.innerHTML = `
                <div class="cs-drawer-section">
                    <div class="cs-drawer-label">Overview</div>
                    <div class="cs-drawer-stats">
                        <span class="cs-drawer-stat" style="background:rgba(59,130,246,0.08); color:#3b82f6;"><i class="fas fa-book-open"></i> ${lessonCount} Lesson${lessonCount !== 1 ? 's' : ''}</span>
                        <span class="cs-drawer-stat" style="background:rgba(245,158,11,0.08); color:#d97706;"><i class="fas fa-question-circle"></i> ${quizCount + lessonQuizzes} Quiz${(quizCount + lessonQuizzes) !== 1 ? 'zes' : ''}</span>
                    </div>
                </div>
                ${(mod.lessons || []).length > 0 ? `
                <div class="cs-drawer-section">
                    <div class="cs-drawer-label">Lessons</div>
                    <div class="cs-drawer-list">
                        ${(mod.lessons || []).map((les, i) => {
                            const typeIconMap = { text: 'fa-file-alt', video: 'fa-video', file: 'fa-file', mixed: 'fa-layer-group' };
                            const icon = typeIconMap[les.content_type] || 'fa-file-alt';
                            const fileCount = (les._files || []).length;
                            return `<div class="cs-drawer-item">
                                <div class="cs-drawer-item-icon" style="background:rgba(59,130,246,0.1); color:#3b82f6;"><i class="fas ${icon}"></i></div>
                                <span class="cs-drawer-item-title">${esc(les.title)}</span>
                                <span class="cs-drawer-item-meta">${les.content_type || 'text'}${fileCount > 0 ? ' · ' + fileCount + ' file' + (fileCount !== 1 ? 's' : '') : ''}</span>
                            </div>`;
                        }).join('')}
                    </div>
                </div>` : ''}
                ${(mod.quizzes || []).length > 0 ? `
                <div class="cs-drawer-section">
                    <div class="cs-drawer-label">Module Quizzes</div>
                    <div class="cs-drawer-list">
                        ${(mod.quizzes || []).map(qz => `<div class="cs-drawer-item">
                            <div class="cs-drawer-item-icon" style="background:rgba(245,158,11,0.1); color:#d97706;"><i class="fas fa-question-circle"></i></div>
                            <span class="cs-drawer-item-title">${esc(qz.title)}</span>
                            <span class="cs-drawer-item-meta">${qz.passing_score ? qz.passing_score + '% pass' : ''}</span>
                        </div>`).join('')}
                    </div>
                </div>` : ''}
                ${(mod.lessons || []).length === 0 && (mod.quizzes || []).length === 0 ? '<p style="text-align:center; color:var(--muted); padding:2rem;">This module has no lessons or quizzes yet.</p>' : ''}
            `;
        } else if (type === 'lesson') {
            let les = null, modTitle = '';
            for (const m of (courseData.modules || [])) {
                const found = (m.lessons || []).find(l => l.id == id);
                if (found) { les = found; modTitle = m.title; break; }
            }
            if (!les) { body.innerHTML = '<p style="color:#ef4444;">Lesson not found.</p>'; return; }
            titleEl.textContent = les.title;
            const files = les._files || [];
            const quizCount = (les.quizzes || []).length;
            const typeIconMap = { text: 'fa-file-alt', video: 'fa-video', file: 'fa-file', mixed: 'fa-layer-group' };
            body.innerHTML = `
                <div class="cs-drawer-section">
                    <div class="cs-drawer-label">Overview</div>
                    <div class="cs-drawer-stats">
                        <span class="cs-drawer-stat" style="background:rgba(59,130,246,0.08); color:#3b82f6;"><i class="fas ${typeIconMap[les.content_type] || 'fa-file-alt'}"></i> ${(les.content_type || 'text').charAt(0).toUpperCase() + (les.content_type || 'text').slice(1)}</span>
                        <span class="cs-drawer-stat" style="background:rgba(139,92,246,0.08); color:#7c3aed;"><i class="fas fa-paperclip"></i> ${files.length} File${files.length !== 1 ? 's' : ''}</span>
                        <span class="cs-drawer-stat" style="background:rgba(245,158,11,0.08); color:#d97706;"><i class="fas fa-question-circle"></i> ${quizCount} Quiz${quizCount !== 1 ? 'zes' : ''}</span>
                    </div>
                    <div class="cs-drawer-desc" style="margin-top:0.5rem;">${esc(les.description || '')}</div>
                </div>
                ${modTitle ? `<div class="cs-drawer-section"><div class="cs-drawer-label">Module</div><div class="cs-drawer-item"><div class="cs-drawer-item-icon" style="background:rgba(32,0,130,0.08); color:var(--primary, #320082);"><i class="fas fa-cube"></i></div><span class="cs-drawer-item-title">${esc(modTitle)}</span></div></div>` : ''}
                ${files.length > 0 ? `
                <div class="cs-drawer-section">
                    <div class="cs-drawer-label">Files</div>
                    <div class="cs-drawer-files">
                        ${files.map(f => {
                            const isImg = /\.(jpe?g|png|gif|webp|svg)$/i.test(f.file_path);
                            const isVid = /\.(mp4|webm|ogg)$/i.test(f.file_path);
                            const fi = isImg ? 'fa-image' : isVid ? 'fa-video' : 'fa-file';
                            return `<div class="cs-drawer-file">
                                <div class="cs-drawer-file-icon"><i class="fas ${fi}"></i></div>
                                <div class="cs-drawer-file-info"><div class="cs-drawer-file-name">${esc(f.title)}</div><div class="cs-drawer-file-type">${isImg ? 'Image' : isVid ? 'Video' : 'File'}</div></div>
                                ${isImg ? `<img src="/${f.file_path}" alt="" class="cs-drawer-file-thumb" onerror="this.style.display='none'" />` : ''}
                            </div>`;
                        }).join('')}
                    </div>
                </div>` : ''}
                ${(les.quizzes || []).length > 0 ? `
                <div class="cs-drawer-section">
                    <div class="cs-drawer-label">Quizzes</div>
                    <div class="cs-drawer-list">
                        ${(les.quizzes || []).map(qz => `<div class="cs-drawer-item">
                            <div class="cs-drawer-item-icon" style="background:rgba(245,158,11,0.1); color:#d97706;"><i class="fas fa-question-circle"></i></div>
                            <span class="cs-drawer-item-title">${esc(qz.title)}</span>
                            <span class="cs-drawer-item-meta">${qz.passing_score ? qz.passing_score + '% pass' : ''}</span>
                        </div>`).join('')}
                    </div>
                </div>` : ''}
            `;
        } else if (type === 'quiz') {
            // Load quiz questions via API
            let qz = null, modTitle = '';
            for (const m of (courseData.modules || [])) {
                const found = (m.quizzes || []).find(q => q.id == id);
                if (found) { qz = found; modTitle = m.title; break; }
                for (const l of (m.lessons || [])) {
                    const foundL = (l.quizzes || []).find(q => q.id == id);
                    if (foundL) { qz = foundL; modTitle = m.title; break; }
                }
                if (qz) break;
            }
            if (!qz) { body.innerHTML = '<p style="color:#ef4444;">Quiz not found.</p>'; return; }
            titleEl.textContent = qz.title;
            body.innerHTML = `
                <div class="cs-drawer-section">
                    <div class="cs-drawer-label">Overview</div>
                    <div class="cs-drawer-stats">
                        ${qz.passing_score ? `<span class="cs-drawer-stat" style="background:rgba(16,185,129,0.08); color:#059669;"><i class="fas fa-check-circle"></i> ${qz.passing_score}% to pass</span>` : ''}
                        ${qz.duration_seconds ? `<span class="cs-drawer-stat" style="background:rgba(59,130,246,0.08); color:#3b82f6;"><i class="fas fa-clock"></i> ${Math.round(qz.duration_seconds / 60)} min</span>` : ''}
                    </div>
                </div>
                ${modTitle ? `<div class="cs-drawer-section"><div class="cs-drawer-label">Module</div><div class="cs-drawer-item"><div class="cs-drawer-item-icon" style="background:rgba(32,0,130,0.08); color:var(--primary, #320082);"><i class="fas fa-cube"></i></div><span class="cs-drawer-item-title">${esc(modTitle)}</span></div></div>` : ''}
                <div class="cs-drawer-section" id="cs-preview-questions"><p style="text-align:center; color:var(--muted);"><i class="fas fa-spinner fa-spin"></i> Loading questions...</p></div>
            `;
            // Load questions via API
            API.getQuizQuestions(id, 'quiz').then(res => {
                const questions = res.success ? res.data : [];
                const qContainer = document.getElementById('cs-preview-questions');
                if (!qContainer) return;
                if (questions.length === 0) {
                    qContainer.innerHTML = '<div class="cs-drawer-label">Questions</div><p style="text-align:center; color:var(--muted); padding:1rem;">No questions yet.</p>';
                    return;
                }
                qContainer.innerHTML = '<div class="cs-drawer-label">Questions (' + questions.length + ')</div>' + questions.map((q, i) => {
                    const typeLabels = { single_choice: 'Single', multiple_choice: 'Multiple', true_false: 'T/F' };
                    const opts = (q.options || []).map(o =>
                        `<div class="cs-drawer-opt${o.is_correct ? ' correct' : ''}">` +
                        `<span class="cs-drawer-opt-dot">${o.is_correct ? '<i class="fas fa-check" style="font-size:0.5rem;"></i>' : ''}</span>` +
                        `<span>${esc(o.option_text)}</span></div>`
                    ).join('');
                    return `<div class="cs-drawer-q">
                        <div class="cs-drawer-q-head">
                            <span class="cs-drawer-q-num">Q${i + 1}</span>
                            <span class="cs-badge cs-badge-qz cs-drawer-q-badge">${typeLabels[q.question_type] || 'Single'}</span>
                            <span class="cs-drawer-q-text">${esc(q.question_text)}</span>
                        </div>
                        <div class="cs-drawer-q-opts">${opts || '<div style="color:var(--muted); font-size:0.8rem;">No options</div>'}</div>
                    </div>`;
                }).join('');
            }).catch(() => {
                const qContainer = document.getElementById('cs-preview-questions');
                if (qContainer) qContainer.innerHTML = '<p style="color:#ef4444; text-align:center;">Failed to load questions.</p>';
            });
        }
    }

    function findModule(id) {
        if (!courseData || !courseData.modules) return null;
        return courseData.modules.find(m => m.id == id) || null;
    }

    function closePreviewDrawer() {
        const overlay = $('#cs-preview-overlay');
        if (overlay) overlay.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    function attachTreeListeners() {
        // Title click → inline edit
        $$('.cs-title').forEach(el => {
            el.addEventListener('dblclick', () => startInlineEdit(el));
        });

        // Quiz card click → open question editor panel
        $$('.cs-qz').forEach(el => {
            el.addEventListener('dblclick', (e) => {
                if (e.target.closest('.cs-acts') || e.target.closest('.cs-inline-edit')) return;
                const quizId = el.dataset.id;
                const titleEl = el.querySelector('.cs-title');
                const title = titleEl ? titleEl.textContent : 'Quiz';
                openQuizPanel(quizId, title, 'quiz');
            });
        });

        // Evaluation cards → open question editor panel
        $$('.cb-open-eval').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                openQuizPanel(btn.dataset.evalId, btn.dataset.evalTitle, 'evaluation');
            });
        });

        // Action buttons
        $$('[data-action]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const action = btn.dataset.action;
                const entity = btn.dataset.entity;
                const id = btn.dataset.id;
                const moduleId = btn.dataset.moduleId;

                if (action === 'view') openPreviewDrawer(btn.dataset.type, parseInt(btn.dataset.id));
                else if (action === 'add-lesson') showAddForm('lesson', moduleId);
                else if (action === 'add-quiz') showAddForm('quiz', moduleId);
                else if (action === 'upload-file') {
                    const lessonId = btn.dataset.lessonId;
                    const fileInput = $(`.cb-file-input[data-lesson-id="${lessonId}"]`);
                    if (fileInput) fileInput.click();
                }
                else if (action === 'edit') {
                    const titleEl = $(`.cs-title[data-id="${id}"][data-entity="${entity}"]`);
                    if (titleEl) startInlineEdit(titleEl);
                }
                else if (action === 'delete') deleteEntity(entity, id);
            });
        });

        // Drag start
        $$('[draggable="true"]').forEach(el => {
            el.addEventListener('dragstart', onDragStart);
            el.addEventListener('dragend', onDragEnd);
        });

        // Drop zones
        $$('.cs-drop').forEach(zone => {
            zone.addEventListener('dragover', onDragOver);
            zone.addEventListener('dragenter', onDragEnter);
            zone.addEventListener('dragleave', onDragLeave);
            zone.addEventListener('drop', onDrop);
        });

        // Module headers are also drop targets (for reordering modules)
        $$('.cs-mod').forEach(mod => {
            mod.addEventListener('dragover', (e) => {
                if (draggedType !== 'module') return;
                e.preventDefault();
            });
            mod.addEventListener('drop', (e) => {
                if (draggedType !== 'module') return;
                e.preventDefault();
                e.stopPropagation();
                if (mod === draggedEl) return;
                handleModuleReorder(mod);
            });
        });

        // File upload inputs
        $$('.cb-file-input').forEach(input => {
            input.addEventListener('change', async (e) => {
                const lessonId = input.dataset.lessonId;
                const file = e.target.files[0];
                if (!file) return;
                try {
                    toast('Uploading ' + file.name + '...', 'info');
                    const uploadRes = await API.uploadMedia(file);
                    if (!uploadRes.success) throw new Error(uploadRes.message || 'Upload failed');
                    await API.saveLessonFile(lessonId, uploadRes.url, file.name);
                    clearFilesCache(lessonId);
                    toast(file.name + ' uploaded!');
                    await reloadStructure();
                } catch (err) {
                    toast('Upload failed: ' + err.message, 'error');
                }
                input.value = '';
            });
        });

        // File delete buttons
        $$('.cs-file-del').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const fileId = btn.dataset.fileId;
                const chip = btn.closest('.cs-file');
                const lessonId = chip ? chip.dataset.lessonId : null;
                if (!confirm('Remove this file?')) return;
                try {
                    await API.deleteLessonFile(fileId);
                    if (lessonId) clearFilesCache(lessonId);
                    toast('File removed');
                    await reloadStructure();
                } catch (err) {
                    toast('Failed to remove file', 'error');
                }
            });
        });
    }

    function onDragStart(e) {
        draggedEl = e.target.closest('[draggable="true"]');
        if (!draggedEl) return;
        draggedType = draggedEl.dataset.type;
        draggedId = draggedEl.dataset.id;
        draggedParentId = draggedEl.dataset.parentId;

        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', draggedType + ':' + draggedId);

        requestAnimationFrame(() => {
            draggedEl.classList.add('dragging');
        });
    }

    function onDragEnd(e) {
        if (draggedEl) draggedEl.classList.remove('dragging');
        $$('.drag-over').forEach(el => el.classList.remove('drag-over'));
        $$('.cs-drop.active').forEach(el => el.classList.remove('active'));
        draggedEl = null;
        draggedType = null;
        draggedId = null;
        draggedParentId = null;
    }

    function onDragOver(e) {
        if (!draggedType) return;
        const zone = e.target.closest('.cs-drop');
        if (!zone) return;

        // Validate drop compatibility
        const zoneType = zone.dataset.dropType;
        if (draggedType === 'lesson' && zoneType !== 'lesson') return;
        if (draggedType === 'quiz' && zoneType !== 'quiz') return;
        if (draggedType === 'module') return; // modules don't go into drop zones

        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    function onDragEnter(e) {
        if (!draggedType) return;
        const zone = e.target.closest('.cs-drop');
        if (!zone) return;

        const zoneType = zone.dataset.dropType;
        if (draggedType === 'lesson' && zoneType !== 'lesson') return;
        if (draggedType === 'quiz' && zoneType !== 'quiz') return;
        if (draggedType === 'module') return;

        zone.classList.add('active');
    }

    function onDragLeave(e) {
        const zone = e.target.closest('.cs-drop');
        if (zone) zone.classList.remove('active');
    }

    async function onDrop(e) {
        e.preventDefault();
        const zone = e.target.closest('.cs-drop');
        if (!zone) return;
        zone.classList.remove('active');

        if (!draggedType || !draggedId) return;

        const zoneType = zone.dataset.dropType;
        const newParentId = parseInt(zone.dataset.parentId);

        if (draggedType === 'lesson' && zoneType === 'lesson') {
            // Reorder lessons within a module
            const moduleId = newParentId;
            const sourceModuleId = parseInt(draggedParentId);

            // Find the drop position
            const siblings = $$(`.cs-les[data-parent-id="${moduleId}"]`);
            const dropIndex = findDropIndex(siblings, e.clientY);

            // Build new order
            let currentOrder = [];
            if (sourceModuleId === moduleId) {
                // Same module reorder
                currentOrder = siblings.filter(s => s.dataset.id !== draggedId).map(s => s.dataset.id);
                currentOrder.splice(dropIndex, 0, draggedId);
            } else {
                // Cross-module move
                const targetSiblings = $$(`.cs-les[data-parent-id="${moduleId}"]`);
                currentOrder = targetSiblings.map(s => s.dataset.id);
                currentOrder.splice(dropIndex, 0, draggedId);
            }

            try {
                await API.reorder('lesson', moduleId, currentOrder.map(Number), sourceModuleId, parseInt(draggedId));
                toast('Lesson reordered');
                await reloadStructure();
            } catch (err) {
                toast('Reorder failed', 'error');
            }
        } else if (draggedType === 'quiz' && zoneType === 'quiz') {
            // Reorder quizzes
            const moduleId = newParentId;
            const sourceModuleId = parseInt(draggedParentId);

            const siblings = $$(`.cs-qz[data-parent-id="${moduleId}"]`);
            const dropIndex = findDropIndex(siblings, e.clientY);

            let currentOrder = siblings.filter(s => s.dataset.id !== draggedId).map(s => s.dataset.id);
            currentOrder.splice(dropIndex, 0, draggedId);

            try {
                await API.reorder('quiz', moduleId, currentOrder.map(Number), sourceModuleId, parseInt(draggedId));
                toast('Quiz reordered');
                await reloadStructure();
            } catch (err) {
                toast('Reorder failed', 'error');
            }
        }
    }

    function handleModuleReorder(targetMod) {
        if (!draggedEl || draggedType !== 'module') return;

        const allModules = $$('.cs-mod');
        const targetIndex = allModules.indexOf(targetMod);
        const draggedIndex = allModules.indexOf(draggedEl);

        if (draggedIndex === targetIndex) return;

        // Reorder in DOM
        const parent = targetMod.parentNode;
        const draggedCopy = draggedEl;

        if (draggedIndex < targetIndex) {
            parent.insertBefore(draggedCopy, targetMod.nextSibling);
        } else {
            parent.insertBefore(draggedCopy, targetMod);
        }

        // Get new order and save
        const newOrder = $$('.cs-mod').map(m => parseInt(m.dataset.id));
        API.reorder('module', currentCourseId, newOrder, currentCourseId, parseInt(draggedId))
            .then(() => { toast('Module reordered'); })
            .catch(() => { toast('Reorder failed', 'error'); reloadStructure(); });
    }

    function findDropIndex(siblings, y) {
        for (let i = 0; i < siblings.length; i++) {
            const rect = siblings[i].getBoundingClientRect();
            const mid = rect.top + rect.height / 2;
            if (y < mid) return i;
        }
        return siblings.length;
    }

    /* ──────────────── Delete ──────────────── */
    async function deleteEntity(entity, id) {
        const names = { module: 'module', lesson: 'lesson', quiz: 'quiz' };
        if (!confirm(`Delete this ${names[entity] || entity}? This cannot be undone.`)) return;
        try {
            await API.archive(entity, id);
            toast(`${names[entity] || entity} deleted`);
            await reloadStructure();
        } catch (e) {
            toast('Delete failed', 'error');
        }
    }

    /* ──────────────── Load & Reload ──────────────── */
    async function loadStructure(courseId) {
        currentCourseId = courseId;
        const header = $('#course-header');
        const empty = $('#empty-state');
        const tree = $('#structure-tree');

        if (!courseId) {
            header.classList.remove('is-visible');
            empty.style.display = '';
            tree.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        header.classList.add('is-visible');
        tree.style.display = '';
        tree.innerHTML = '<div style="text-align:center; padding:2rem; color:#999;">Loading structure...</div>';

        try {
            const courses = await API.getCourses();
            const course = (courses.items || []).find(c => c.id == courseId);
            if (course) {
                $('#course-title').textContent = course.title || course.name || 'Course';
                $('#course-meta').textContent = (course.category || '') + (course.status ? ' · ' + course.status : '');
            }

            courseData = await API.getStructure(courseId);
            renderTree(courseData);
            updateStats(courseData);
        } catch (e) {
            tree.innerHTML = '<div style="text-align:center; padding:2rem; color:#ef4444;">Failed to load course structure.</div>';
        }
    }

    async function reloadStructure() {
        if (!currentCourseId) return;
        // Clear file caches so fresh data loads
        Object.keys(lessonFilesCache).forEach(k => delete lessonFilesCache[k]);
        courseData = await API.getStructure(currentCourseId);
        renderTree(courseData);
        updateStats(courseData);
    }

    /* ──────────────── Quiz/Evaluation Question Editor Panel ──────────────── */
    let activeQuizId = null;
    let activeItemType = 'quiz';  // 'quiz' or 'evaluation'

    let quizPanelInitialized = false;
    function ensureQuizPanel() {
        if (quizPanelInitialized) return;
        quizPanelInitialized = true;
        // Wire up event listeners for the quiz panel (HTML is in PHP)
        $('#cb-quiz-add-q')?.addEventListener('click', addQuestionFromPanel);
        $('#cb-quiz-qtext')?.addEventListener('keydown', (e) => { if (e.key === 'Enter') addQuestionFromPanel(); });
        $('#cb-quiz-bulk-toggle')?.addEventListener('click', () => {
            const bp = $('#cb-quiz-bulk-panel');
            if (bp) bp.style.display = bp.style.display === 'none' ? 'block' : 'none';
        });
        $('#cb-quiz-bulk-import')?.addEventListener('click', () => bulkImportFromText());
        $('#cb-quiz-bulk-csv')?.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                const ta = $('#cb-quiz-bulk-text');
                if (ta) ta.value = csvToPasteFormat(ev.target.result);
                bulkImportFromText();
            };
            reader.readAsText(file);
            e.target.value = '';
        });
    }

    async function openQuizPanel(quizId, quizTitle, itemType) {
        ensureQuizPanel();
        activeQuizId = quizId;
        activeItemType = itemType || 'quiz';
        const panel = $('#cb-quiz-panel');
        if (panel) panel.classList.add('is-open');
        const titleSpan = $('#cb-quiz-title span');
        if (titleSpan) titleSpan.textContent = quizTitle || (activeItemType === 'evaluation' ? 'Evaluation Questions' : 'Quiz Questions');
        document.body.style.overflow = 'hidden';
        await loadQuizQuestions(quizId);
    }

    function closeQuizPanel() {
        const panel = $('#cb-quiz-panel');
        if (panel) panel.classList.remove('is-open');
        activeQuizId = null;
        document.body.style.overflow = '';
    }

    async function loadQuizQuestions(quizId, scrollToQuestionId) {
        const body = $('#cb-quiz-body');
        const drawerBody = body.closest('.cs-drawer-body');
        const savedScroll = drawerBody ? drawerBody.scrollTop : 0;
        try {
            const res = await API.getQuizQuestions(quizId, activeItemType);
            const questions = res.success ? res.data : [];
            if (questions.length === 0) {
                body.innerHTML = '<div style="text-align:center; padding:2rem; color:#999;"> No questions yet. Add your first question below.</div>';
                return;
            }
            const html = questions.map((q, i) => renderQuestionCard(q, i)).join('');
            body.innerHTML = html;
            attachQuestionListeners();
            if (scrollToQuestionId) {
                const card = body.querySelector(`.cb-q-card[data-q-id="${scrollToQuestionId}"]`);
                if (card) {
                    requestAnimationFrame(() => card.scrollIntoView({ behavior: 'smooth', block: 'nearest' }));
                    return;
                }
            }
            if (drawerBody) requestAnimationFrame(() => { drawerBody.scrollTop = savedScroll; });
        } catch (e) {
            body.innerHTML = '<p style="color:#ef4444; text-align:center;">Failed to load questions.</p>';
        }
    }

    function renderQuestionCard(q, idx) {
        const typeLabels = { single_choice: 'Single', multiple_choice: 'Multiple', true_false: 'T/F' };
        const typeBadge = typeLabels[q.question_type] || 'Single';
        const optionsHtml = (q.options || []).map(opt => `
            <div class="cb-opt-row" data-opt-id="${opt.id}" data-q-id="${q.id}">
                <label class="cb-opt-correct" title="Toggle correct">
                    <input type="radio" name="correct_${q.id}" ${opt.is_correct ? 'checked' : ''} data-opt-id="${opt.id}" data-q-id="${q.id}" />
                    <span class="cb-opt-dot"></span>
                </label>
                <input type="text" class="cb-opt-text" value="${esc(opt.option_text)}" data-opt-id="${opt.id}" />
                <button class="cs-act-del cb-opt-del" data-opt-id="${opt.id}" title="Remove">✕</button>
            </div>
        `).join('');

        return `
        <div class="cb-q-card" data-q-id="${q.id}">
            <div class="cb-q-header">
                <span class="cb-q-num">Q${idx + 1}</span>
                <span class="cs-badge cs-badge-qz" style="font-size:0.65rem;">${typeBadge}</span>
                <span class="cb-q-text" data-q-id="${q.id}" style="flex:1; cursor:text;">${esc(q.question_text)}</span>
                <div class="cs-acts">
                    <button class="cs-act-del cb-q-del" data-q-id="${q.id}" title="Delete question">🗑</button>
                </div>
            </div>
            <div class="cb-q-options" data-q-id="${q.id}">
                ${optionsHtml}
                <div class="cb-opt-add-row" data-q-id="${q.id}">
                    <input type="text" class="cb-opt-add-input" placeholder="Add option..." data-q-id="${q.id}" />
                    <button class="cs-act-add cb-opt-add-btn" data-q-id="${q.id}">+ Add</button>
                </div>
            </div>
        </div>`;
    }

    function attachQuestionListeners() {
        // Question text double-click → inline edit
        $$('.cb-q-text').forEach(el => {
            el.addEventListener('dblclick', () => {
                const id = el.dataset.qId;
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'cb-inline-edit';
                input.value = el.textContent;
                input.style.width = '100%';
                el.textContent = '';
                el.appendChild(input);
                input.focus();
                input.select();
                const save = async () => {
                    const val = input.value.trim();
                    if (val && val !== el.textContent) {
                        await API.editQuizQuestion(id, val);
                        el.textContent = val;
                        toast('Question updated');
                    } else {
                        el.textContent = input.value;
                    }
                };
                input.addEventListener('blur', save);
                input.addEventListener('keydown', (e) => { if (e.key === 'Enter') input.blur(); if (e.key === 'Escape') { input.value = el.textContent; input.blur(); } });
            });
        });

        // Delete question
        $$('.cb-q-del').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Delete this question and all its options?')) return;
                await API.deleteQuizQuestion(btn.dataset.qId);
                toast('Question deleted');
                await loadQuizQuestions(activeQuizId);
            });
        });

        // Option text change → save
        $$('.cb-opt-text').forEach(input => {
            input.addEventListener('change', async () => {
                const optId = input.dataset.optId;
                const row = input.closest('.cb-opt-row');
                const radio = row.querySelector('input[type=radio]');
                await API.editQuizOption(optId, input.value, radio.checked);
            });
        });

        // Correct answer toggle
        $$('.cb-opt-correct input[type=radio]').forEach(radio => {
            radio.addEventListener('change', async () => {
                const optId = radio.dataset.optId;
                const row = radio.closest('.cb-opt-row');
                const textInput = row.querySelector('.cb-opt-text');
                await API.editQuizOption(optId, textInput.value, true);
                // Uncheck others in same question
                const qId = radio.dataset.qId;
                $$(`.cb-opt-correct input[name="correct_${qId}"]`).forEach(r => {
                    if (r !== radio) r.checked = false;
                });
                toast('Correct answer updated');
            });
        });

        // Delete option
        $$('.cb-opt-del').forEach(btn => {
            btn.addEventListener('click', async () => {
                const row = btn.closest('.cb-opt-row');
                const qId = row ? row.dataset.qId : null;
                await API.deleteQuizOption(btn.dataset.optId);
                toast('Option removed');
                await loadQuizQuestions(activeQuizId, qId);
            });
        });

        // Add option (Enter key)
        $$('.cb-opt-add-input').forEach(input => {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') addOptionFromInput(input);
            });
        });

        // Add option button
        $$('.cb-opt-add-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.previousElementSibling;
                addOptionFromInput(input);
            });
        });
    }

    async function addOptionFromInput(input) {
        const text = input.value.trim();
        if (!text) return;
        const qId = input.dataset.qId;
        await API.addQuizOption(qId, text, false);
        input.value = '';
        toast('Option added');
        await loadQuizQuestions(activeQuizId, qId);
    }

    async function addQuestionFromPanel() {
        if (!activeQuizId) return;
        const text = $('#cb-quiz-qtext').value.trim();
        const type = $('#cb-quiz-qtype').value;
        if (!text) { toast('Type a question first', 'warning'); return; }
        await API.addQuizQuestion(activeQuizId, text, type, activeItemType, activeQuizId);
        $('#cb-quiz-qtext').value = '';
        toast('Question added!');
        await loadQuizQuestions(activeQuizId);
    }

    /* ──────────────── Bulk Import ──────────────── */
    function parsePasteFormat(text) {
        const lines = text.split('\n').map(l => l.trim()).filter(l => l.length > 0);
        const questions = [];
        let current = null;

        for (const line of lines) {
            // Check if this is an option line: A) ..., B) ..., 1) ..., a) ..., etc.
            const optMatch = line.match(/^\*?([A-Da-d]|\d+)[\.\)]\s*(.+)/);
            if (optMatch && current) {
                const isCorrect = line.startsWith('*');
                const optText = optMatch[2].trim();
                current.options.push({ text: optText, correct: isCorrect });
                continue;
            }

            // Check for True/False question
            const tfMatch = line.match(/^(true|false)$/i);
            if (tfMatch && current) {
                current.options.push({ text: tfMatch[1], correct: false });
                continue;
            }

            // If we have a current question with options, save it
            if (current && current.options.length > 0) {
                questions.push(current);
                current = null;
            }

            // If the line looks like a question (ends with ? or is just text)
            // and doesn't match an option pattern, start a new question
            if (!optMatch) {
                // Check if this might be a True/False option context
                if (current && (line.toLowerCase() === 'true' || line.toLowerCase() === 'false')) {
                    current.options.push({ text: line, correct: false });
                    continue;
                }
                current = { text: line.replace(/\?$/, ''), options: [] };
            }
        }

        // Push last question
        if (current && current.options.length > 0) {
            questions.push(current);
        }

        return questions;
    }

    function csvToPasteFormat(csvText) {
        const lines = csvText.split('\n').filter(l => l.trim());
        const output = [];
        for (const line of lines) {
            // Parse CSV line (simple: no quoted commas)
            const parts = line.split(',').map(s => s.trim());
            if (parts.length < 3) continue;
            const question = parts[0];
            if (question.toLowerCase() === 'question' || question.toLowerCase() === 'question_text') continue; // skip header
            output.push(question);
            for (let i = 1; i < parts.length; i++) {
                const prefix = parts.length > 5 && i === parseInt(parts[parts.length - 1]) ? '*' : '';
                const letter = String.fromCharCode(64 + i); // A, B, C, D...
                output.push(prefix + letter + ') ' + parts[i]);
            }
            output.push('');
        }
        return output.join('\n');
    }

    async function bulkImportFromText() {
        if (!activeQuizId) return;
        const text = $('#cb-quiz-bulk-text').value.trim();
        if (!text) { toast('Paste some questions first', 'warning'); return; }

        const questions = parsePasteFormat(text);
        if (questions.length === 0) {
            toast('No valid questions found. Check the format.', 'warning');
            return;
        }

        const statusEl = $('#cb-quiz-bulk-status');
        let imported = 0;

        for (const q of questions) {
            try {
                // Determine question type
                const isTF = q.options.length === 2 && q.options.every(o => ['true', 'false'].includes(o.text.toLowerCase()));
                const qType = isTF ? 'true_false' : (q.options.length > 2 ? 'multiple_choice' : 'single_choice');

                const res = await API.addQuizQuestion(activeQuizId, q.text, qType, activeItemType, activeQuizId);
                if (res.success && res.id) {
                    // Add options
                    for (const opt of q.options) {
                        await API.addQuizOption(res.id, opt.text, opt.correct);
                    }
                    imported++;
                }
            } catch (e) {
                console.error('Failed to import question:', q.text, e);
            }
        }

        if (statusEl) statusEl.textContent = `Imported ${imported}/${questions.length} questions`;
        toast(`Imported ${imported} questions!`);
        $('#cb-quiz-bulk-text').value = '';
        await loadQuizQuestions(activeQuizId);
    }

    /* ──────────────── Template Modal ──────────────── */
    function closeTemplateModal() {
        const modal = $('#cs-template-modal');
        if (modal) modal.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    async function doSaveTemplate() {
        const input = $('#cs-template-input');
        const title = input ? input.value.trim() : '';
        if (!title) { if (input) input.focus(); return; }
        closeTemplateModal();
        try {
            toast('Saving template...', 'info');
            const res = await API.saveTemplate(currentCourseId, title);
            if (res.success) {
                toast(res.message || 'Template saved!');
            } else {
                toast(res.message || 'Failed to save template', 'error');
            }
        } catch (e) {
            toast('Failed to save template', 'error');
        }
    }

    /* ──────────────── Init ──────────────── */
    document.addEventListener('DOMContentLoaded', async () => {
        // Safeguard: reset body overflow in case it was left hidden
        document.body.style.overflow = '';

        // Load course list
        const selector = $('#course-selector');
        try {
            const data = await API.getCourses();
            const courses = data.items || [];
            courses.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.title || c.name || ('Course #' + c.id);
                selector.appendChild(opt);
            });
        } catch (e) {
            console.error('Failed to load courses', e);
        }

        // Check URL params
        const params = new URLSearchParams(window.location.search);
        const preselected = params.get('course_id');
        if (preselected) {
            selector.value = preselected;
            loadStructure(preselected);
        }

        selector.addEventListener('change', () => loadStructure(selector.value));

        // Add module button
        $('#btn-add-module')?.addEventListener('click', () => showAddForm('module', currentCourseId));

        // Preview course link
        const previewLink = $('#btn-preview-course');
        if (previewLink) {
            previewLink.addEventListener('click', (e) => {
                if (!currentCourseId) { e.preventDefault(); toast('Select a course first', 'warning'); return; }
                previewLink.href = '?page=instructor/elearning-subpage/course-preview&course_id=' + currentCourseId;
            });
        }

        // Save as Template — custom modal
        $('#btn-save-template')?.addEventListener('click', () => {
            if (!currentCourseId) { toast('Select a course first', 'warning'); return; }
            const modal = $('#cs-template-modal');
            const input = $('#cs-template-input');
            if (!modal || !input) return;
            input.value = '';
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            setTimeout(() => input.focus(), 50);
        });
        $('#cs-template-close')?.addEventListener('click', closeTemplateModal);
        $('#cs-template-cancel')?.addEventListener('click', closeTemplateModal);
        $('#cs-template-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'cs-template-modal') closeTemplateModal();
        });
        $('#cs-template-save')?.addEventListener('click', doSaveTemplate);
        $('#cs-template-input')?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') doSaveTemplate();
            if (e.key === 'Escape') closeTemplateModal();
        });

        // Collapse / Expand all
        $('#btn-collapse-all')?.addEventListener('click', () => {
            $$('.cs-mod-body').forEach(b => b.style.display = 'none');
        });
        $('#btn-expand-all')?.addEventListener('click', () => {
            $$('.cs-mod-body').forEach(b => b.style.display = '');
        });

        // Preview drawer close
        $('#cs-preview-close')?.addEventListener('click', closePreviewDrawer);
        $('#cs-preview-overlay')?.addEventListener('click', (e) => {
            if (e.target.id === 'cs-preview-overlay') closePreviewDrawer();
        });

        // Quiz editor drawer close
        $('#cb-quiz-close')?.addEventListener('click', closeQuizPanel);
        $('#cb-quiz-panel')?.addEventListener('click', (e) => {
            if (e.target.id === 'cb-quiz-panel') closeQuizPanel();
        });
    });
})();
