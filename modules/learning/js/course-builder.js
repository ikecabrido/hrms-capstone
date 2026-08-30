/**
 * Course Builder with Dynamic Forms and localStorage Persistence
 * Handles bulk creation of courses with modules, lessons, quizzes, and evaluations
 */

class CourseBuilder {
    constructor() {
        this.courseData = {
            course: {},
            modules: [],
            evaluation: null
        };
        this.storageKey = 'courseBuilder_draft_' + new Date().toISOString().split('T')[0];
        this.init();
    }

    /**
     * Initialize the course builder
     */
    init() {
        this.loadFromStorage();
        this.attachEventListeners();
        this.renderModulesList();
    }

    /**
     * Attach event listeners to form elements
     */
    attachEventListeners() {
        // Course form submission
        const courseForm = document.getElementById('course-form');
        if (courseForm) {
            courseForm.addEventListener('submit', (e) => this.handleCourseSubmit(e));
        }

        // Add module button
        const addModuleBtn = document.getElementById('add-module-btn');
        if (addModuleBtn) {
            addModuleBtn.addEventListener('click', () => this.addModule());
        }

        // Course basic info changes
        const courseInputs = document.querySelectorAll('[data-course-field]');
        courseInputs.forEach(input => {
            input.addEventListener('change', () => this.updateCourseData());
            input.addEventListener('input', () => this.autoSaveToStorage());
        });

        // Evaluation form changes
        const evalInputs = document.querySelectorAll('[data-eval-field]');
        evalInputs.forEach(input => {
            input.addEventListener('change', () => this.updateEvaluationData());
            input.addEventListener('input', () => this.autoSaveToStorage());
        });
    }

    /**
     * Update course data from form
     */
    updateCourseData() {
        const form = document.getElementById('course-form');
        if (!form) return;

        const formData = new FormData(form);
        var skillCheckboxes = form.querySelectorAll('input[name="skill_ids[]"]:checked');
        var skillIds = [];
        skillCheckboxes.forEach(function(cb) { skillIds.push(parseInt(cb.value)); });
        this.courseData.course = {
            title: formData.get('title') || '',
            description: formData.get('description') || '',
            category: formData.get('category') || '',
            status: formData.get('status') || 'draft',
            start_date: formData.get('start_date') || '',
            enrollment_deadline: formData.get('enrollment_deadline') || '',
            skill_ids: skillIds,
        };

        this.autoSaveToStorage();
    }

    /**
     * Update evaluation data from form
     */
    updateEvaluationData() {
        const form = document.getElementById('course-form');
        if (!form) return;

        const evalTitle = form.querySelector('[name="eval_title"]')?.value || '';

        if (evalTitle.trim()) {
            this.courseData.evaluation = {
                title: evalTitle,
                description: form.querySelector('[name="eval_description"]')?.value || '',
                duration_seconds: parseInt(form.querySelector('[name="eval_duration_seconds"]')?.value || 0) || null,
                passing_score: parseFloat(form.querySelector('[name="eval_passing_score"]')?.value || 0) || null,
                max_attempts: parseInt(form.querySelector('[name="eval_max_attempts"]')?.value || 2) || 2,
                question_count: parseInt(form.querySelector('[name="eval_question_count"]')?.value || 0) || null,
                show_answers_after_submit: form.querySelector('[name="eval_show_answers"]')?.checked || false,
                status: form.querySelector('[name="eval_status"]')?.value || 'active',
            };
        } else {
            this.courseData.evaluation = null;
        }

        this.autoSaveToStorage();
    }

    /**
     * Add a new module to the course
     */
    addModule() {
        const newModule = {
            id: 'temp_' + Date.now(),
            title: '',
            description: '',
            status: 'active',
            lessons: [],
            quizzes: [],
        };

        this.courseData.modules.push(newModule);
        this.renderModulesList();
        this.autoSaveToStorage();
    }

    /**
     * Remove a module
     */
    removeModule(moduleId) {
        this.courseData.modules = this.courseData.modules.filter(m => m.id !== moduleId);
        this.renderModulesList();
        this.autoSaveToStorage();
    }

    /**
     * Duplicate a module with all its lessons and quizzes
     */
    duplicateModule(moduleId) {
        const idx = this.courseData.modules.findIndex(m => m.id === moduleId);
        if (idx === -1) return;
        const src = this.courseData.modules[idx];
        const deepCopy = JSON.parse(JSON.stringify(src));
        // Assign new temp IDs
        deepCopy.id = 'temp_' + Date.now();
        deepCopy.title = (deepCopy.title || 'Module') + ' (Copy)';
        deepCopy.lessons.forEach(l => {
            l.id = 'temp_' + Date.now() + Math.random().toString(36).slice(2,6);
            l.quizzes.forEach(q => { q.id = 'temp_' + Date.now() + Math.random().toString(36).slice(2,6); });
        });
        deepCopy.quizzes.forEach(q => { q.id = 'temp_' + Date.now() + Math.random().toString(36).slice(2,6); });
        this.courseData.modules.splice(idx + 1, 0, deepCopy);
        this.renderModulesList();
        this.autoSaveToStorage();
    }

    /**
     * Move module up or down
     */
    moveModule(moduleId, direction) {
        const idx = this.courseData.modules.findIndex(m => m.id === moduleId);
        if (idx === -1) return;
        const newIdx = direction === 'up' ? idx - 1 : idx + 1;
        if (newIdx < 0 || newIdx >= this.courseData.modules.length) return;
        const temp = this.courseData.modules[idx];
        this.courseData.modules[idx] = this.courseData.modules[newIdx];
        this.courseData.modules[newIdx] = temp;
        this.renderModulesList();
        this.autoSaveToStorage();
    }

    /**
     * Handle drag start on module
     */
    onDragStart(e) {
        const block = e.target.closest('.module-block');
        if (!block) return;
        this._dragModuleId = block.dataset.moduleId;
        block.style.opacity = '0.4';
        e.dataTransfer.effectAllowed = 'move';
    }

    /**
     * Handle drag over on module
     */
    onDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const block = e.target.closest('.module-block');
        if (block) block.style.boxShadow = '0 0 0 2px var(--color1)';
    }

    /**
     * Handle drag leave on module
     */
    onDragLeave(e) {
        const block = e.target.closest('.module-block');
        if (block) block.style.boxShadow = '';
    }

    /**
     * Handle drop on module to reorder
     */
    onDrop(e) {
        e.preventDefault();
        const targetBlock = e.target.closest('.module-block');
        if (!targetBlock) return;
        targetBlock.style.boxShadow = '';
        const targetId = targetBlock.dataset.moduleId;
        const sourceId = this._dragModuleId;
        if (!sourceId || sourceId === targetId) return;
        const fromIdx = this.courseData.modules.findIndex(m => m.id === sourceId);
        const toIdx = this.courseData.modules.findIndex(m => m.id === targetId);
        if (fromIdx === -1 || toIdx === -1) return;
        const moved = this.courseData.modules.splice(fromIdx, 1)[0];
        this.courseData.modules.splice(toIdx, 0, moved);
        this._dragModuleId = null;
        this.renderModulesList();
        this.autoSaveToStorage();
    }

    /**
     * Handle drag end (cleanup)
     */
    onDragEnd(e) {
        const block = e.target.closest('.module-block');
        if (block) block.style.opacity = '';
        document.querySelectorAll('.module-block').forEach(b => b.style.boxShadow = '');
        this._dragModuleId = null;
    }

    /**
     * Add a lesson to a module
     */
    addLesson(moduleId) {
        const module = this.courseData.modules.find(m => m.id === moduleId);
        if (!module) return;

        const newLesson = {
            id: 'temp_' + Date.now(),
            title: '',
            content_type: 'text',
            content_body: '',
            video_url: '',
            status: 'active',
            quizzes: [],
        };

        module.lessons.push(newLesson);
        this.renderModulesList();
        this.autoSaveToStorage();
    }

    /**
     * Remove a lesson from a module
     */
    removeLesson(moduleId, lessonId) {
        const module = this.courseData.modules.find(m => m.id === moduleId);
        if (!module) return;

        module.lessons = module.lessons.filter(l => l.id !== lessonId);
        this.renderModulesList();
        this.autoSaveToStorage();
    }

    /**
     * Add a quiz to a lesson
     */
    addQuizToLesson(moduleId, lessonId) {
        const module = this.courseData.modules.find(m => m.id === moduleId);
        if (!module) return;

        const lesson = module.lessons.find(l => l.id === lessonId);
        if (!lesson) return;

        const newQuiz = {
            id: 'temp_' + Date.now(),
            title: '',
            duration_seconds: 600,
            passing_score: null,
            max_attempts: 2,
            question_count: null,
            show_answers_after_submit: false,
            status: 'active',
        };

        lesson.quizzes.push(newQuiz);
        this.renderModulesList();
        this.autoSaveToStorage();
    }

    /**
     * Remove a quiz from a lesson
     */
    removeQuizFromLesson(moduleId, lessonId, quizId) {
        const module = this.courseData.modules.find(m => m.id === moduleId);
        if (!module) return;

        const lesson = module.lessons.find(l => l.id === lessonId);
        if (!lesson) return;

        lesson.quizzes = lesson.quizzes.filter(q => q.id !== quizId);
        this.renderModulesList();
        this.autoSaveToStorage();
    }

    /**
     * Add a module-level quiz
     */
    addModuleQuiz(moduleId) {
        const module = this.courseData.modules.find(m => m.id === moduleId);
        if (!module) return;

        const newQuiz = {
            id: 'temp_' + Date.now(),
            title: '',
            duration_seconds: 600,
            passing_score: null,
            max_attempts: 2,
            question_count: null,
            show_answers_after_submit: false,
            status: 'active',
        };

        module.quizzes.push(newQuiz);
        this.renderModulesList();
        this.autoSaveToStorage();
    }

    /**
     * Remove a module-level quiz
     */
    removeModuleQuiz(moduleId, quizId) {
        const module = this.courseData.modules.find(m => m.id === moduleId);
        if (!module) return;

        module.quizzes = module.quizzes.filter(q => q.id !== quizId);
        this.renderModulesList();
        this.autoSaveToStorage();
    }

    /**
     * Update module field
     */
    updateModuleField(moduleId, field, value) {
        const module = this.courseData.modules.find(m => m.id === moduleId);
        if (module) {
            module[field] = value;
            this.autoSaveToStorage();
        }
    }

    /**
     * Update lesson field
     */
    updateLessonField(moduleId, lessonId, field, value) {
        const module = this.courseData.modules.find(m => m.id === moduleId);
        if (!module) return;

        const lesson = module.lessons.find(l => l.id === lessonId);
        if (lesson) {
            lesson[field] = value;
            this.autoSaveToStorage();
        }
    }

    /**
     * Update lesson quiz field
     */
    updateLessonQuizField(moduleId, lessonId, quizId, field, value) {
        const module = this.courseData.modules.find(m => m.id === moduleId);
        if (!module) return;

        const lesson = module.lessons.find(l => l.id === lessonId);
        if (!lesson) return;

        const quiz = lesson.quizzes.find(q => q.id === quizId);
        if (quiz) {
            if (field === 'duration_seconds' || field === 'max_attempts' || field === 'question_count') {
                quiz[field] = parseInt(value) || null;
            } else if (field === 'passing_score') {
                quiz[field] = parseFloat(value) || null;
            } else if (field === 'show_answers_after_submit') {
                quiz[field] = value === 'on' || value === true;
            } else {
                quiz[field] = value;
            }
            this.autoSaveToStorage();
        }
    }

    /**
     * Update module quiz field
     */
    updateModuleQuizField(moduleId, quizId, field, value) {
        const module = this.courseData.modules.find(m => m.id === moduleId);
        if (!module) return;

        const quiz = module.quizzes.find(q => q.id === quizId);
        if (quiz) {
            if (field === 'duration_seconds' || field === 'max_attempts' || field === 'question_count') {
                quiz[field] = parseInt(value) || null;
            } else if (field === 'passing_score') {
                quiz[field] = parseFloat(value) || null;
            } else if (field === 'show_answers_after_submit') {
                quiz[field] = value === 'on' || value === true;
            } else {
                quiz[field] = value;
            }
            this.autoSaveToStorage();
        }
    }

    /**
     * Render the modules list with all dynamic forms
     */
    renderModulesList() {
        const container = document.getElementById('modules-list-container');
        if (!container) return;

        container.innerHTML = '';

        if (this.courseData.modules.length === 0) {
            container.innerHTML = '<p style="color: #666; text-align: center; padding: 2rem;">No modules added yet. Click "Add Module" to get started.</p>';
            return;
        }

        this.courseData.modules.forEach((module, moduleIdx) => {
            const moduleHtml = this.renderModuleBlock(module, moduleIdx);
            container.innerHTML += moduleHtml;
        });

        // Reattach event listeners for dynamically created elements
        this.attachDynamicListeners();
    }

    /**
     * Render a single module block with lessons and quizzes
     */
    renderModuleBlock(module, moduleIdx) {
        const moduleId = module.id;

        let html = `
        <div class="module-block" data-module-id="${moduleId}" draggable="true" style="border:1px solid var(--color4); border-radius:10px; padding:1rem; margin-bottom:1rem; background:#fafafa; cursor:default; transition: box-shadow 0.2s;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span class="drag-handle" title="Drag to reorder" style="cursor:grab; color:#999; font-size:1rem; user-select:none;">&#9776;</span>
                    <h4 style="margin:0;">Module ${moduleIdx + 1}</h4>
                    <button type="button" class="move-module-btn" data-module-id="${moduleId}" data-dir="up" title="Move up" style="padding:0.2rem 0.5rem; background:rgba(32,0,130,0.08); color:var(--color1); border:1px solid rgba(32,0,130,0.15); border-radius:6px; cursor:pointer; font-size:0.75rem;">&#9650;</button>
                    <button type="button" class="move-module-btn" data-module-id="${moduleId}" data-dir="down" title="Move down" style="padding:0.2rem 0.5rem; background:rgba(32,0,130,0.08); color:var(--color1); border:1px solid rgba(32,0,130,0.15); border-radius:6px; cursor:pointer; font-size:0.75rem;">&#9660;</button>
                </div>
                <div style="display:flex; gap:0.4rem;">
                    <button type="button" class="duplicate-module-btn" data-module-id="${moduleId}" title="Duplicate module" style="padding:0.4rem 0.8rem; background:rgba(32,0,130,0.08); color:var(--color1); border:1px solid rgba(32,0,130,0.15); border-radius:8px; cursor:pointer; font-size:0.8rem; font-weight:600;"><i class="fas fa-copy"></i> Duplicate</button>
                    <button type="button" class="remove-module-btn" data-module-id="${moduleId}" style="padding:0.4rem 0.8rem; background:#ff6b6b; color:white; border:none; border-radius:8px; cursor:pointer; font-size:0.8rem; font-weight:600;">Remove</button>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1rem;">
                <label>
                    <span>Module title</span>
                    <input type="text" class="module-title-input" data-module-id="${moduleId}" value="${module.title || ''}" placeholder="e.g., Introduction to SQL" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--color4);" required />
                </label>
                <label>
                    <span>Status</span>
                    <select class="module-status-input" data-module-id="${moduleId}" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--color4);">
                        <option value="active" ${module.status === 'active' ? 'selected' : ''}>Active</option>
                        <option value="archived" ${module.status === 'archived' ? 'selected' : ''}>Archived</option>
                    </select>
                </label>
            </div>

            <label style="display:block; margin-bottom:1rem;">
                <span>Module description</span>
                <textarea class="module-description-input" data-module-id="${moduleId}" rows="3" placeholder="Describe this module..." style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--color4); resize:vertical;">${module.description || ''}</textarea>
            </label>

            <!-- LESSONS SECTION -->
            <div style="background:white; padding:1rem; border-radius:10px; margin-bottom:1rem; border:1px solid var(--color4);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h5 style="margin:0;">Lessons</h5>
                    <button type="button" class="add-lesson-btn" data-module-id="${moduleId}" style="padding:0.5rem 1rem; background:#2196F3; color:white; border:none; border-radius:10px; cursor:pointer; font-size:0.85rem;">+ Add Lesson</button>
                </div>
                <div class="lessons-list" data-module-id="${moduleId}">
        `;

        if (module.lessons.length === 0) {
            html += '<p style="color:#999; text-align:center; padding:1rem;">No lessons yet.</p>';
        } else {
            module.lessons.forEach((lesson, lessonIdx) => {
                html += this.renderLessonBlock(module.id, lesson, lessonIdx);
            });
        }

        html += `
                </div>
            </div>

            <!-- MODULE-LEVEL QUIZZES SECTION -->
            <div style="background:white; padding:1rem; border-radius:10px; border:1px solid var(--color4);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h5 style="margin:0;">Module Quizzes</h5>
                    <button type="button" class="add-module-quiz-btn" data-module-id="${moduleId}" style="padding:0.5rem 1rem; background:#2196F3; color:white; border:none; border-radius:10px; cursor:pointer; font-size:0.85rem;">+ Add Quiz</button>
                </div>
                <div class="module-quizzes-list" data-module-id="${moduleId}">
        `;

        if (module.quizzes.length === 0) {
            html += '<p style="color:#999; text-align:center; padding:1rem;">No quizzes yet.</p>';
        } else {
            module.quizzes.forEach((quiz, quizIdx) => {
                html += this.renderModuleQuizBlock(moduleId, quiz, quizIdx);
            });
        }

        html += `
                </div>
            </div>
        </div>
        `;

        return html;
    }

    /**
     * Render a single lesson block
     */
    renderLessonBlock(moduleId, lesson, lessonIdx) {
        const lessonId = lesson.id;
        let html = `
        <div class="lesson-block" data-lesson-id="${lessonId}" style="background:#f9f9f9; padding:0.8rem; margin-bottom:0.8rem; border-radius:10px; border:1px solid var(--color4);">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.8rem;">
                <span style="font-weight:500;">Lesson ${lessonIdx + 1}</span>
                <button type="button" class="remove-lesson-btn" data-module-id="${moduleId}" data-lesson-id="${lessonId}" style="padding:0.3rem 0.6rem; background:#ff6b6b; color:white; border:none; border-radius:10px; cursor:pointer; font-size:0.8rem;">Remove</button>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:0.8rem;">
                <label>
                    <span>Lesson title</span>
                    <input type="text" class="lesson-title-input" data-module-id="${moduleId}" data-lesson-id="${lessonId}" value="${lesson.title || ''}" placeholder="e.g., Basic Queries" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--color4);" required />
                </label>
                <label>
                    <span>Content type</span>
                    <select class="lesson-content-type-input" data-module-id="${moduleId}" data-lesson-id="${lessonId}" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--color4);">
                        <option value="text" ${lesson.content_type === 'text' ? 'selected' : ''}>Text</option>
                        <option value="video" ${lesson.content_type === 'video' ? 'selected' : ''}>Video</option>
                        <option value="file" ${lesson.content_type === 'file' ? 'selected' : ''}>File</option>
                        <option value="mixed" ${lesson.content_type === 'mixed' ? 'selected' : ''}>Mixed</option>
                    </select>
                </label>
            </div>

            <label style="display:block; margin-bottom:0.8rem;">
                <span>Content body</span>
                <textarea class="lesson-content-body-input" data-module-id="${moduleId}" data-lesson-id="${lessonId}" rows="2" placeholder="Lesson content..." style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--color4); resize:vertical;">${lesson.content_body || ''}</textarea>
            </label>

            <label style="display:block; margin-bottom:0.8rem;">
                <span>Video URL (if applicable)</span>
                <input type="url" class="lesson-video-url-input" data-module-id="${moduleId}" data-lesson-id="${lessonId}" value="${lesson.video_url || ''}" placeholder="https://..." style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--color4);" />
            </label>

            <!-- QUIZZES FOR THIS LESSON -->
            <div style="background:white; padding:0.8rem; border-radius:10px; border:1px solid var(--color4);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                    <span style="font-weight:500; font-size:0.9rem;">Lesson Quizzes</span>
                    <button type="button" class="add-lesson-quiz-btn" data-module-id="${moduleId}" data-lesson-id="${lessonId}" style="padding:0.3rem 0.6rem; background:#2196F3; color:white; border:none; border-radius:10px; cursor:pointer; font-size:0.8rem;">+ Quiz</button>
                </div>
                <div class="lesson-quizzes-list" data-module-id="${moduleId}" data-lesson-id="${lessonId}">
        `;

        if (lesson.quizzes.length === 0) {
            html += '<p style="color:#ccc; font-size:0.85rem; margin:0;">No quizzes yet.</p>';
        } else {
            lesson.quizzes.forEach((quiz, quizIdx) => {
                html += this.renderLessonQuizBlock(moduleId, lessonId, quiz, quizIdx);
            });
        }

        html += `
                </div>
            </div>
        </div>
        `;

        return html;
    }

    /**
     * Render a quiz block within a lesson
     */
    renderLessonQuizBlock(moduleId, lessonId, quiz, quizIdx) {
        const quizId = quiz.id;
        return `
        <div class="lesson-quiz-block" data-quiz-id="${quizId}" style="background:#fff8e1; padding:0.6rem; margin-bottom:0.5rem; border-radius:10px; border:1px solid var(--color4);">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.5rem;">
                <span style="font-weight:500; font-size:0.85rem;">Quiz ${quizIdx + 1}</span>
                <button type="button" class="remove-lesson-quiz-btn" data-module-id="${moduleId}" data-lesson-id="${lessonId}" data-quiz-id="${quizId}" style="padding:0.2rem 0.5rem; background:#ff6b6b; color:white; border:none; border-radius:10px; cursor:pointer; font-size:0.8rem;">Remove</button>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:0.5rem;">
                <label>
                    <span style="font-size:0.85rem;">Quiz title</span>
                    <input type="text" class="lesson-quiz-title-input" data-module-id="${moduleId}" data-lesson-id="${lessonId}" data-quiz-id="${quizId}" value="${quiz.title || ''}" placeholder="e.g., Quiz 1" style="width:100%; margin-top:0.25rem; padding:0.6rem; border-radius:10px; border:1px solid var(--color4); font-size:0.85rem;" required />
                </label>
                <label>
                    <span style="font-size:0.85rem;">Duration (sec)</span>
                    <input type="number" class="lesson-quiz-duration-input" data-module-id="${moduleId}" data-lesson-id="${lessonId}" data-quiz-id="${quizId}" value="${quiz.duration_seconds || 600}" min="60" style="width:100%; margin-top:0.25rem; padding:0.6rem; border-radius:10px; border:1px solid var(--color4); font-size:0.85rem;" />
                </label>
                <label>
                    <span style="font-size:0.85rem;">Passing score (%)</span>
                    <input type="number" class="lesson-quiz-passing-score-input" data-module-id="${moduleId}" data-lesson-id="${lessonId}" data-quiz-id="${quizId}" value="${quiz.passing_score || ''}" min="0" max="100" step="0.5" style="width:100%; margin-top:0.25rem; padding:0.6rem; border-radius:10px; border:1px solid var(--color4); font-size:0.85rem;" />
                </label>
                <label>
                    <span style="font-size:0.85rem;">Max attempts</span>
                    <input type="number" class="lesson-quiz-max-attempts-input" data-module-id="${moduleId}" data-lesson-id="${lessonId}" data-quiz-id="${quizId}" value="${quiz.max_attempts || 2}" min="1" style="width:100%; margin-top:0.25rem; padding:0.6rem; border-radius:10px; border:1px solid var(--color4); font-size:0.85rem;" />
                </label>
                <label>
                    <span style="font-size:0.85rem;">Question count</span>
                    <input type="number" class="lesson-quiz-question-count-input" data-module-id="${moduleId}" data-lesson-id="${lessonId}" data-quiz-id="${quizId}" value="${quiz.question_count || ''}" min="1" style="width:100%; margin-top:0.25rem; padding:0.6rem; border-radius:10px; border:1px solid var(--color4); font-size:0.85rem;" />
                </label>
            </div>

            <label style="display:flex; align-items:center; gap:0.3rem; margin-top:0.5rem; font-size:0.85rem;">
                <input type="checkbox" class="lesson-quiz-show-answers-input" data-module-id="${moduleId}" data-lesson-id="${lessonId}" data-quiz-id="${quizId}" ${quiz.show_answers_after_submit ? 'checked' : ''} />
                Show answers after submit
            </label>
        </div>
        `;
    }

    /**
     * Render a module-level quiz block
     */
    renderModuleQuizBlock(moduleId, quiz, quizIdx) {
        const quizId = quiz.id;
        return `
        <div class="module-quiz-block" data-quiz-id="${quizId}" style="background:#e3f2fd; padding:0.8rem; margin-bottom:0.8rem; border-radius:10px; border:1px solid var(--color4);">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.8rem;">
                <span style="font-weight:500;">Quiz ${quizIdx + 1}</span>
                <button type="button" class="remove-module-quiz-btn" data-module-id="${moduleId}" data-quiz-id="${quizId}" style="padding:0.3rem 0.6rem; background:#ff6b6b; color:white; border:none; border-radius:10px; cursor:pointer; font-size:0.8rem;">Remove</button>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:0.5rem; margin-bottom:0.5rem;">
                <label>
                    <span style="font-size:0.85rem;">Quiz title</span>
                    <input type="text" class="module-quiz-title-input" data-module-id="${moduleId}" data-quiz-id="${quizId}" value="${quiz.title || ''}" placeholder="e.g., Assessment" style="width:100%; margin-top:0.25rem; padding:0.6rem; border-radius:10px; border:1px solid var(--color4); font-size:0.85rem;" required />
                </label>
                <label>
                    <span style="font-size:0.85rem;">Duration (sec)</span>
                    <input type="number" class="module-quiz-duration-input" data-module-id="${moduleId}" data-quiz-id="${quizId}" value="${quiz.duration_seconds || 600}" min="60" style="width:100%; margin-top:0.25rem; padding:0.6rem; border-radius:10px; border:1px solid var(--color4); font-size:0.85rem;" />
                </label>
                <label>
                    <span style="font-size:0.85rem;">Passing score (%)</span>
                    <input type="number" class="module-quiz-passing-score-input" data-module-id="${moduleId}" data-quiz-id="${quizId}" value="${quiz.passing_score || ''}" min="0" max="100" step="0.5" style="width:100%; margin-top:0.25rem; padding:0.6rem; border-radius:10px; border:1px solid var(--color4); font-size:0.85rem;" />
                </label>
                <label>
                    <span style="font-size:0.85rem;">Max attempts</span>
                    <input type="number" class="module-quiz-max-attempts-input" data-module-id="${moduleId}" data-quiz-id="${quizId}" value="${quiz.max_attempts || 2}" min="1" style="width:100%; margin-top:0.25rem; padding:0.6rem; border-radius:10px; border:1px solid var(--color4); font-size:0.85rem;" />
                </label>
                <label>
                    <span style="font-size:0.85rem;">Question count</span>
                    <input type="number" class="module-quiz-question-count-input" data-module-id="${moduleId}" data-quiz-id="${quizId}" value="${quiz.question_count || ''}" min="1" style="width:100%; margin-top:0.25rem; padding:0.6rem; border-radius:10px; border:1px solid var(--color4); font-size:0.85rem;" />
                </label>
            </div>

            <label style="display:flex; align-items:center; gap:0.3rem; font-size:0.85rem;">
                <input type="checkbox" class="module-quiz-show-answers-input" data-module-id="${moduleId}" data-quiz-id="${quizId}" ${quiz.show_answers_after_submit ? 'checked' : ''} />
                Show answers after submit
            </label>
        </div>
        `;
    }

    /**
     * Attach event listeners to dynamically created elements
     */
    attachDynamicListeners() {
        // Module title changes
        document.querySelectorAll('.module-title-input').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateModuleField(e.target.dataset.moduleId, 'title', e.target.value);
            });
            input.addEventListener('input', (e) => {
                this.updateModuleField(e.target.dataset.moduleId, 'title', e.target.value);
            });
        });

        // Module description changes
        document.querySelectorAll('.module-description-input').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateModuleField(e.target.dataset.moduleId, 'description', e.target.value);
            });
            input.addEventListener('input', (e) => {
                this.updateModuleField(e.target.dataset.moduleId, 'description', e.target.value);
            });
        });

        // Module status changes
        document.querySelectorAll('.module-status-input').forEach(select => {
            select.addEventListener('change', (e) => {
                this.updateModuleField(e.target.dataset.moduleId, 'status', e.target.value);
            });
        });

        // Remove module buttons
        document.querySelectorAll('.remove-module-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (confirm('Remove this module and all its content?')) {
                    this.removeModule(btn.dataset.moduleId);
                }
            });
        });

        // Duplicate module buttons
        document.querySelectorAll('.duplicate-module-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.duplicateModule(btn.dataset.moduleId);
            });
        });

        // Move module up/down buttons
        document.querySelectorAll('.move-module-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.moveModule(btn.dataset.moduleId, btn.dataset.dir);
            });
        });

        // Drag-and-drop reorder for modules
        document.querySelectorAll('.module-block').forEach(block => {
            block.addEventListener('dragstart', (e) => this.onDragStart(e));
            block.addEventListener('dragover', (e) => this.onDragOver(e));
            block.addEventListener('dragleave', (e) => this.onDragLeave(e));
            block.addEventListener('drop', (e) => this.onDrop(e));
            block.addEventListener('dragend', (e) => this.onDragEnd(e));
        });

        // Add lesson buttons
        document.querySelectorAll('.add-lesson-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.addLesson(btn.dataset.moduleId);
            });
        });

        // Lesson title changes
        document.querySelectorAll('.lesson-title-input').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateLessonField(e.target.dataset.moduleId, e.target.dataset.lessonId, 'title', e.target.value);
            });
            input.addEventListener('input', (e) => {
                this.updateLessonField(e.target.dataset.moduleId, e.target.dataset.lessonId, 'title', e.target.value);
            });
        });

        // Lesson content type changes
        document.querySelectorAll('.lesson-content-type-input').forEach(select => {
            select.addEventListener('change', (e) => {
                this.updateLessonField(e.target.dataset.moduleId, e.target.dataset.lessonId, 'content_type', e.target.value);
            });
        });

        // Lesson content body changes
        document.querySelectorAll('.lesson-content-body-input').forEach(textarea => {
            textarea.addEventListener('change', (e) => {
                this.updateLessonField(e.target.dataset.moduleId, e.target.dataset.lessonId, 'content_body', e.target.value);
            });
            textarea.addEventListener('input', (e) => {
                this.updateLessonField(e.target.dataset.moduleId, e.target.dataset.lessonId, 'content_body', e.target.value);
            });
        });

        // Lesson video URL changes
        document.querySelectorAll('.lesson-video-url-input').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateLessonField(e.target.dataset.moduleId, e.target.dataset.lessonId, 'video_url', e.target.value);
            });
            input.addEventListener('input', (e) => {
                this.updateLessonField(e.target.dataset.moduleId, e.target.dataset.lessonId, 'video_url', e.target.value);
            });
        });

        // Remove lesson buttons
        document.querySelectorAll('.remove-lesson-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (confirm('Remove this lesson?')) {
                    this.removeLesson(btn.dataset.moduleId, btn.dataset.lessonId);
                }
            });
        });

        // Add lesson quiz buttons
        document.querySelectorAll('.add-lesson-quiz-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.addQuizToLesson(btn.dataset.moduleId, btn.dataset.lessonId);
            });
        });

        // Lesson quiz inputs
        document.querySelectorAll('.lesson-quiz-title-input').forEach(input => {
            input.addEventListener('input', (e) => {
                this.updateLessonQuizField(e.target.dataset.moduleId, e.target.dataset.lessonId, e.target.dataset.quizId, 'title', e.target.value);
            });
        });

        document.querySelectorAll('.lesson-quiz-duration-input').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateLessonQuizField(e.target.dataset.moduleId, e.target.dataset.lessonId, e.target.dataset.quizId, 'duration_seconds', e.target.value);
            });
        });

        document.querySelectorAll('.lesson-quiz-passing-score-input').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateLessonQuizField(e.target.dataset.moduleId, e.target.dataset.lessonId, e.target.dataset.quizId, 'passing_score', e.target.value);
            });
        });

        document.querySelectorAll('.lesson-quiz-max-attempts-input').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateLessonQuizField(e.target.dataset.moduleId, e.target.dataset.lessonId, e.target.dataset.quizId, 'max_attempts', e.target.value);
            });
        });

        document.querySelectorAll('.lesson-quiz-question-count-input').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateLessonQuizField(e.target.dataset.moduleId, e.target.dataset.lessonId, e.target.dataset.quizId, 'question_count', e.target.value);
            });
        });

        document.querySelectorAll('.lesson-quiz-show-answers-input').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateLessonQuizField(e.target.dataset.moduleId, e.target.dataset.lessonId, e.target.dataset.quizId, 'show_answers_after_submit', e.target.checked);
            });
        });

        // Remove lesson quiz buttons
        document.querySelectorAll('.remove-lesson-quiz-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (confirm('Remove this quiz?')) {
                    this.removeQuizFromLesson(btn.dataset.moduleId, btn.dataset.lessonId, btn.dataset.quizId);
                }
            });
        });

        // Add module quiz buttons
        document.querySelectorAll('.add-module-quiz-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.addModuleQuiz(btn.dataset.moduleId);
            });
        });

        // Module quiz inputs
        document.querySelectorAll('.module-quiz-title-input').forEach(input => {
            input.addEventListener('input', (e) => {
                this.updateModuleQuizField(e.target.dataset.moduleId, e.target.dataset.quizId, 'title', e.target.value);
            });
        });

        document.querySelectorAll('.module-quiz-duration-input').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateModuleQuizField(e.target.dataset.moduleId, e.target.dataset.quizId, 'duration_seconds', e.target.value);
            });
        });

        document.querySelectorAll('.module-quiz-passing-score-input').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateModuleQuizField(e.target.dataset.moduleId, e.target.dataset.quizId, 'passing_score', e.target.value);
            });
        });

        document.querySelectorAll('.module-quiz-max-attempts-input').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateModuleQuizField(e.target.dataset.moduleId, e.target.dataset.quizId, 'max_attempts', e.target.value);
            });
        });

        document.querySelectorAll('.module-quiz-question-count-input').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateModuleQuizField(e.target.dataset.moduleId, e.target.dataset.quizId, 'question_count', e.target.value);
            });
        });

        document.querySelectorAll('.module-quiz-show-answers-input').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateModuleQuizField(e.target.dataset.moduleId, e.target.dataset.quizId, 'show_answers_after_submit', e.target.checked);
            });
        });

        // Remove module quiz buttons
        document.querySelectorAll('.remove-module-quiz-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (confirm('Remove this quiz?')) {
                    this.removeModuleQuiz(btn.dataset.moduleId, btn.dataset.quizId);
                }
            });
        });
    }

    /**
     * Handle form submission
     */
    handleCourseSubmit(event) {
        event.preventDefault();

        // Update data from form
        this.updateCourseData();
        this.updateEvaluationData();

        // Validate required fields
        if (!this.courseData.course.title) {
            alert('Course title is required.');
            return;
        }

        if (this.courseData.modules.length === 0) {
            alert('Please add at least one module.');
            return;
        }

        // Validate each module has a title and at least one lesson or quiz
        for (let mod of this.courseData.modules) {
            if (!mod.title) {
                alert('All modules must have a title.');
                return;
            }
            if (mod.lessons.length === 0 && mod.quizzes.length === 0) {
                alert(`Module "${mod.title}" must have at least one lesson or quiz.`);
                return;
            }
            // Validate each lesson has a title
            for (let les of mod.lessons) {
                if (!les.title) {
                    alert(`Module "${mod.title}" has a lesson without a title.`);
                    return;
                }
            }
            // Validate each module quiz has a title
            for (let quiz of mod.quizzes) {
                if (!quiz.title) {
                    alert(`Module "${mod.title}" has a quiz without a title.`);
                    return;
                }
            }
        }

        // Submit the form
        this.submitCourseData();
    }

    /**
     * Submit course data to the server
     */
    submitCourseData() {
        const form = document.getElementById('course-form');
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton ? submitButton.textContent : '';

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Creating Course...';
        }

        var payload = {
            course: this.courseData.course,
            modules: this.courseData.modules,
            evaluation: this.courseData.evaluation,
        };
        // Ensure skill_ids are in the payload
        if (!payload.course.skill_ids) {
            var sc = document.querySelectorAll('#course-form input[name="skill_ids[]"]:checked');
            payload.course.skill_ids = [];
            sc.forEach(function(c) { payload.course.skill_ids.push(parseInt(c.value)); });
        }

        fetch('pages/instructor/elearning-subpage/ajax/add-course-bulk.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(async response => {
            const data = await response.json().catch(() => ({
                success: false,
                message: 'Invalid response from server.'
            }));

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Unable to create course.');
            }

            alert('Course created successfully with all modules, lessons, quizzes, and evaluation!');
            this.clearStorage();
            form.reset();
            window.location.href = '?page=instructor/elearning';
        })
        .catch(error => {
            alert('Save failed: ' + (error.message || 'Unable to create course.'));
        })
        .finally(() => {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        });
    }

    /**
     * Auto-save to localStorage with debounce
     */
    autoSaveToStorage() {
        if (this.autoSaveTimeout) {
            clearTimeout(this.autoSaveTimeout);
        }

        this.autoSaveTimeout = setTimeout(() => {
            this.saveToStorage();
        }, 1000); // Save 1 second after last change
    }

    /**
     * Save data to localStorage
     */
    saveToStorage() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(this.courseData));
        } catch (e) {
            console.error('Failed to save to localStorage:', e);
        }
    }

    /**
     * Load data from localStorage
     */
    loadFromStorage() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            if (stored) {
                const data = JSON.parse(stored);
                this.courseData = data;
                this.populateFormFromData();
            }
        } catch (e) {
            console.error('Failed to load from localStorage:', e);
        }
    }

    /**
     * Populate form fields from loaded data
     */
    populateFormFromData() {
        const form = document.getElementById('course-form');
        if (!form) return;

        // Populate course fields
        if (this.courseData.course) {
            form.querySelector('[name="title"]').value = this.courseData.course.title || '';
            form.querySelector('[name="description"]').value = this.courseData.course.description || '';
            form.querySelector('[name="category"]').value = this.courseData.course.category || '';
            form.querySelector('[name="status"]').value = this.courseData.course.status || 'draft';
            form.querySelector('[name="start_date"]').value = this.courseData.course.start_date || '';
            form.querySelector('[name="enrollment_deadline"]').value = this.courseData.course.enrollment_deadline || '';
        }

        // Populate evaluation fields
        if (this.courseData.evaluation) {
            form.querySelector('[name="eval_title"]').value = this.courseData.evaluation.title || '';
            form.querySelector('[name="eval_description"]').value = this.courseData.evaluation.description || '';
            form.querySelector('[name="eval_duration_seconds"]').value = this.courseData.evaluation.duration_seconds || '';
            form.querySelector('[name="eval_passing_score"]').value = this.courseData.evaluation.passing_score || '';
            form.querySelector('[name="eval_max_attempts"]').value = this.courseData.evaluation.max_attempts || 2;
            form.querySelector('[name="eval_question_count"]').value = this.courseData.evaluation.question_count || '';
            form.querySelector('[name="eval_show_answers"]').checked = this.courseData.evaluation.show_answers_after_submit || false;
            form.querySelector('[name="eval_status"]').value = this.courseData.evaluation.status || 'active';
        }

        // Render modules
        this.renderModulesList();
    }

    /**
     * Clear storage
     */
    clearStorage() {
        try {
            localStorage.removeItem(this.storageKey);
        } catch (e) {
            console.error('Failed to clear localStorage:', e);
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.courseBuilder = new CourseBuilder();
});
