<?php
$evaluationEditId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEvaluationEditMode = $evaluationEditId > 0;
$evaluationEditData = null;
$evaluationCourseName = '';
$evaluationCourseId = '';

if ($isEvaluationEditMode) {
    try {
        require_once dirname(__DIR__, 5) . '/database/db.php';
        $database = new Database();
        $pdo = $database->getConnection();

        $stmt = $pdo->prepare('SELECT * FROM ld_evaluation WHERE id = ? LIMIT 1');
        $stmt->execute([$evaluationEditId]);
        $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($evaluation) {
            $evaluationEditData = $evaluation;
            $evaluationCourseId = (string) ($evaluation['course_id'] ?? '');

            if (!empty($evaluation['course_id'])) {
                $courseStmt = $pdo->prepare('SELECT title FROM ld_course WHERE id = ? LIMIT 1');
                $courseStmt->execute([(int) $evaluation['course_id']]);
                $courseRow = $courseStmt->fetch(PDO::FETCH_ASSOC);
                $evaluationCourseName = $courseRow['title'] ?? '';
            }

            $questionsStmt = $pdo->prepare('SELECT q.id, q.question_text, q.question_type, q.order_index, q.status FROM ld_quiz_question q WHERE q.item_type = ? AND q.reference_id = ? ORDER BY q.order_index ASC, q.id ASC');
            $questionsStmt->execute(['evaluation', $evaluationEditId]);
            $questionRows = $questionsStmt->fetchAll(PDO::FETCH_ASSOC);

            $questions = [];
            foreach ($questionRows as $question) {
                $optionStmt = $pdo->prepare('SELECT id, option_text, is_correct, order_index FROM ld_quiz_question_option WHERE question_id = ? ORDER BY order_index ASC, id ASC');
                $optionStmt->execute([(int) $question['id']]);
                $options = $optionStmt->fetchAll(PDO::FETCH_ASSOC);

                $questions[] = [
                    'id' => (int) $question['id'],
                    'question_text' => $question['question_text'],
                    'question_type' => $question['question_type'],
                    'order_index' => (int) $question['order_index'],
                    'status' => $question['status'],
                    'options' => array_map(function ($option) {
                        return [
                            'id' => (int) $option['id'],
                            'option_text' => $option['option_text'],
                            'is_correct' => (bool) $option['is_correct'],
                            'order_index' => (int) $option['order_index'],
                        ];
                    }, $options),
                    'correct_answer' => !empty($options) && !empty($options[0]['option_text']) ? $options[0]['option_text'] : '',
                ];
            }

            $evaluation['questions'] = $questions;
            $evaluationEditData = $evaluation;
        }
    } catch (Throwable $e) {
        $evaluationEditData = null;
    }
}

// Load courses for server-side datalist
$evalPageCourses = [];
try {
    require_once dirname(__DIR__, 3) . '/classes/course.php';
    require_once dirname(__DIR__, 5) . '/database/db.php';
    $evalPageDb = new Database();
    $evalPagePdo = $evalPageDb->getConnection();
    $evalPageCourseObj = new Course($evalPagePdo);
    $evalPageCourses = $evalPageCourseObj->getList();
} catch (Throwable $e) {
    $evalPageCourses = [];
}
?>
<div class="module-content">
    <div class="toolbar">
        <div class="toolbar-search">
            <input type="search" placeholder="Search evaluation form..." aria-label="Search evaluation form" />
        </div>
        
    </div>

    <div class="mode-card">
        <h2 id="evaluation-form-title"><?php echo $isEvaluationEditMode ? 'Edit Evaluation' : 'Add Evaluation'; ?></h2>
        <p id="evaluation-form-desc"><?php echo $isEvaluationEditMode ? 'Update evaluation details and question bank.' : 'Per the MD, evaluation attaches directly to a course, not to a module or lesson.'; ?></p>

        <form id="add-evaluation-form" method="post" action="pages/instructor/elearning-subpage/ajax/add-evaluation.php">
            <?php if ($isEvaluationEditMode): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $evaluationEditId); ?>" />
            <?php endif; ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-top:1rem;">
                <label>
                    <span>Course</span>
                    <input type="text" list="evaluation-course-list" id="evaluation-course-search" placeholder="Search course by name" value="<?php echo htmlspecialchars($evaluationCourseName); ?>" required style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                    <input type="hidden" name="course_id" id="evaluation-course-id" value="<?php echo htmlspecialchars((string) $evaluationCourseId); ?>" required />
                    <datalist id="evaluation-course-list">
                        <?php foreach ($evalPageCourses as $c): ?>
                        <option value="<?php echo htmlspecialchars($c['title']); ?>" data-id="<?php echo (int)$c['id']; ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </label>
                <label>
                    <span>Evaluation title</span>
                    <input type="text" name="title" value="<?php echo htmlspecialchars((string) ($evaluationEditData['title'] ?? '')); ?>" required placeholder="e.g. Final Course Assessment" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                </label>
                <label>
                    <span>Duration (seconds)</span>
                    <input type="number" name="duration_seconds" min="0" value="1800" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                </label>
                <label>
                    <span>Passing score</span>
                    <input type="number" name="passing_score" min="0" max="100" step="0.01" value="75" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                </label>
                <label>
                    <span>Max attempts</span>
                    <input type="number" name="max_attempts" min="1" value="2" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                </label>
                <label>
                    <span>Question count</span>
                    <input type="number" name="question_count" min="1" value="10" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                </label>
                <label>
                    <span>Status</span>
                    <select name="status" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                        <option value="active" selected>Active</option>
                        <option value="archived">Archived</option>
                    </select>
                </label>
                <label style="display:flex; align-items:center; gap:0.6rem; padding-top:2.5rem;">
                    <input type="checkbox" name="show_answers_after_submit" value="1" />
                    <span>Show answers after submit</span>
                </label>
            </div>

            <div style="margin-top:2rem; border:1px solid var(--border); border-radius:14px; padding:1rem; background:rgba(255,255,255,0.35);">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1rem; flex-wrap:wrap;">
                    <div>
                        <h3 style="margin:0;">Questions</h3>
                        <p style="margin:0.35rem 0 0; color:var(--text);">Add evaluation questions, choose the question type, and set the expected answer or options.</p>
                    </div>
                    <button type="button" id="add-evaluation-question-btn" class="mode-button" style="padding:0.7rem 1rem;">Add Question</button>
                </div>
                <div id="evaluation-question-list"></div>
            </div>

            <div class="mode-actions" style="margin-top:1.5rem;">
                <button type="submit" class="mode-button">Save Evaluation</button>
                
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('add-evaluation-form');
    if (!form) return;

    const courseSearch = document.getElementById('evaluation-course-search');
    const courseIdField = document.getElementById('evaluation-course-id');
    const courseList = document.getElementById('evaluation-course-list');
    const evaluationId = new URLSearchParams(window.location.search).get('id');
    const isEditMode = !!evaluationId;
    const initialEvaluationData = <?php echo $evaluationEditData ? json_encode($evaluationEditData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null'; ?>;
    let pendingCourseId = null;

    function setSelectedCourse(courseId) {
        if (!courseList || !courseSearch) return;
        const selected = Array.from(courseList.options).find(function (option) {
            return String(option.dataset.id) === String(courseId);
        });

        if (selected) {
            courseSearch.value = selected.value;
            courseIdField.value = selected.dataset.id || '';
            return;
        }

        courseIdField.value = courseId || '';
        if (!courseId) {
            courseSearch.value = '';
        }
    }

    function hydrateSelectedCourse(courseId) {
        if (!courseId) return;
        pendingCourseId = String(courseId);
        if (!courseList || !courseList.options || courseList.options.length === 0) {
            courseIdField.value = pendingCourseId;
            return;
        }

        setSelectedCourse(pendingCourseId);
    }

    if (isEditMode) {
        document.getElementById('evaluation-form-title').textContent = 'Edit Evaluation';
        document.getElementById('evaluation-form-desc').textContent = 'Update evaluation details and question bank.';
    }

    const questionTypeOptions = {
        single_choice: 'Single choice',
        multiple_choice: 'Multiple choice',
        true_false: 'True / false',
        short_answer: 'Short answer',
        long_answer: 'Long answer'
    };

    function buildDefaultOption() {
        return { option_text: '', is_correct: false };
    }

    function createOptionRow(option = {}, onRemove) {
        const row = document.createElement('div');
        row.style.display = 'grid';
        row.style.gridTemplateColumns = '1fr auto auto';
        row.style.gap = '0.5rem';
        row.style.alignItems = 'center';
        row.style.marginBottom = '0.5rem';

        const input = document.createElement('input');
        input.type = 'text';
        input.value = option.option_text || '';
        input.placeholder = 'Option text';
        input.name = 'option-text';
        input.style.width = '100%';
        input.style.padding = '0.72rem';
        input.style.borderRadius = '10px';
        input.style.border = '1px solid var(--border)';

        const correctToggle = document.createElement('label');
        correctToggle.style.display = 'flex';
        correctToggle.style.alignItems = 'center';
        correctToggle.style.gap = '0.4rem';
        correctToggle.style.fontSize = '0.9rem';
        correctToggle.innerHTML = '<input type="checkbox" ' + (option.is_correct ? 'checked' : '') + ' /> <span>Correct</span>';

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.textContent = 'Remove';
        removeBtn.style.padding = '0.6rem 0.8rem';
        removeBtn.style.borderRadius = '8px';
        removeBtn.style.border = '1px solid #d1d5db';
        removeBtn.style.background = '#fff';
        removeBtn.addEventListener('click', function () {
            if (row.parentNode) row.parentNode.removeChild(row);
            if (onRemove) onRemove();
        });

        row.appendChild(input);
        row.appendChild(correctToggle);
        row.appendChild(removeBtn);
        return { row, input, correctToggle };
    }

    function createQuestionCard(question = {}) {
        const card = document.createElement('div');
        card.dataset.questionCard = 'true';
        card.style.border = '1px solid #d9dce3';
        card.style.borderRadius = '14px';
        card.style.background = '#fff';
        card.style.padding = '1rem';
        card.style.marginBottom = '1rem';
        card.style.boxShadow = '0 1px 0 rgba(15, 23, 42, 0.02)';

        const questionText = document.createElement('textarea');
        questionText.value = question.question_text || '';
        questionText.placeholder = 'Question prompt';
        questionText.style.width = '100%';
        questionText.style.minHeight = '74px';
        questionText.style.padding = '0.8rem';
        questionText.style.border = '1px solid var(--border)';
        questionText.style.borderRadius = '10px';
        questionText.style.resize = 'vertical';
        questionText.style.marginBottom = '0.8rem';

        const controls = document.createElement('div');
        controls.style.display = 'grid';
        controls.style.gridTemplateColumns = '1fr auto';
        controls.style.gap = '0.8rem';
        controls.style.alignItems = 'center';
        controls.style.marginBottom = '0.8rem';

        const typeSelect = document.createElement('select');
        typeSelect.style.width = '100%';
        typeSelect.style.padding = '0.8rem';
        typeSelect.style.borderRadius = '10px';
        typeSelect.style.border = '1px solid var(--border)';
        Object.entries(questionTypeOptions).forEach(([value, label]) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = label;
            if ((question.question_type || 'single_choice') === value) option.selected = true;
            typeSelect.appendChild(option);
        });

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.textContent = 'Delete';
        removeBtn.style.padding = '0.7rem 1rem';
        removeBtn.style.borderRadius = '8px';
        removeBtn.style.border = '1px solid #ef4444';
        removeBtn.style.background = '#fff';
        removeBtn.style.color = '#ef4444';
        removeBtn.addEventListener('click', function () {
            if (card.parentNode) card.parentNode.removeChild(card);
        });

        controls.appendChild(typeSelect);
        controls.appendChild(removeBtn);

        const answerWrap = document.createElement('div');
        answerWrap.dataset.answerWrap = 'true';
        answerWrap.style.marginTop = '0.5rem';

        const renderAnswerFields = function () {
            answerWrap.innerHTML = '';
            const type = typeSelect.value;

            if (type === 'short_answer' || type === 'long_answer') {
                const label = document.createElement('label');
                label.style.display = 'block';
                label.innerHTML = '<span style="display:block; margin-bottom:0.35rem; font-weight:600;">Expected answer</span>';
                const textInput = document.createElement('input');
                textInput.type = 'text';
                textInput.value = (question.correct_answer || question.options?.[0]?.option_text || '');
                textInput.placeholder = type === 'short_answer' ? 'Correct answer' : 'Expected response';
                textInput.style.width = '100%';
                textInput.style.padding = '0.82rem';
                textInput.style.borderRadius = '10px';
                textInput.style.border = '1px solid var(--border)';
                label.appendChild(textInput);
                answerWrap.appendChild(label);
                return;
            }

            const optionHeader = document.createElement('div');
            optionHeader.style.display = 'flex';
            optionHeader.style.justifyContent = 'space-between';
            optionHeader.style.alignItems = 'center';
            optionHeader.style.marginBottom = '0.6rem';
            optionHeader.innerHTML = '<strong>Options</strong><button type="button" class="mini-action" style="padding:0.5rem 0.7rem; border:1px solid var(--border); border-radius:8px; background:#fff;">Add option</button>';

            const optionListWrap = document.createElement('div');
            optionListWrap.className = 'option-list';
            const seedOptions = Array.isArray(question.options) && question.options.length > 0 ? question.options : [buildDefaultOption(), buildDefaultOption()];
            seedOptions.forEach(function (option) {
                const { row } = createOptionRow(option);
                optionListWrap.appendChild(row);
            });

            optionHeader.querySelector('button').addEventListener('click', function () {
                optionListWrap.appendChild(createOptionRow(buildDefaultOption()).row);
            });

            answerWrap.appendChild(optionHeader);
            answerWrap.appendChild(optionListWrap);
        };

        typeSelect.addEventListener('change', renderAnswerFields);
        card.appendChild(questionText);
        card.appendChild(controls);
        card.appendChild(answerWrap);
        renderAnswerFields();
        return card;
    }

    function collectQuestions() {
        const cards = Array.from(document.querySelectorAll('[data-question-card="true"]'));
        const questions = cards.map(function (card, index) {
            const questionText = card.querySelector('textarea')?.value?.trim() || '';
            const type = card.querySelector('select')?.value || 'single_choice';
            const answerWrap = card.querySelector('[data-answer-wrap="true"]');
            const freeTextInput = answerWrap?.querySelector('input[type="text"]');

            if (!questionText && !freeTextInput?.value) {
                return null;
            }

            const response = {
                question_text: questionText,
                question_type: type,
                order_index: index + 1,
                options: [],
                correct_answer: ''
            };

            if (type === 'short_answer' || type === 'long_answer') {
                response.correct_answer = freeTextInput ? freeTextInput.value.trim() : '';
                if (response.correct_answer) {
                    response.options.push({ option_text: response.correct_answer, is_correct: true });
                }
                return response;
            }

            const rows = Array.from(answerWrap.querySelectorAll('.option-list > div'));
            rows.forEach(function (row) {
                const input = row.querySelector('input[type="text"]');
                const checkbox = row.querySelector('input[type="checkbox"]');
                const optionText = input ? input.value.trim() : '';
                if (!optionText) return;
                response.options.push({
                    option_text: optionText,
                    is_correct: !!(checkbox && checkbox.checked)
                });
            });

            if (response.options.length === 0) {
                response.options.push({ option_text: 'Option 1', is_correct: true });
            }
            return response;
        }).filter(Boolean);

        const hidden = form.querySelector('input[name="questions"]');
        if (!hidden) {
            const field = document.createElement('input');
            field.type = 'hidden';
            field.name = 'questions';
            form.appendChild(field);
        }
        form.querySelector('input[name="questions"]').value = JSON.stringify(questions);
    }

    var courseOptions = <?php echo json_encode(array_map(function($c) { return ['id' => (int)$c['id'], 'name' => trim($c['title'])]; }, $evalPageCourses), JSON_HEX_TAG); ?> || [];

    if (isEditMode && pendingCourseId) {
        setSelectedCourse(pendingCourseId);
    } else if (isEditMode && evaluationId && courseIdField && courseIdField.value) {
        setSelectedCourse(courseIdField.value);
    } else if (isEditMode && courseSearch && courseSearch.value) {
        const matching = Array.from(courseList.options).find(function (option) {
            return option.value === courseSearch.value;
        });
        if (matching) {
            courseIdField.value = matching.dataset.id || '';
        }
    }

    courseSearch.addEventListener('change', function () {
        const selected = Array.from(courseList.options).find(function (option) {
            return option.value === courseSearch.value;
        });
        courseIdField.value = selected ? (selected.dataset.id || '') : '';
    });

    const questionList = document.getElementById('evaluation-question-list');
    const addQuestionButton = document.getElementById('add-evaluation-question-btn');

    function ensureQuestionList() {
        if (!questionList) return;
        if (questionList.children.length === 0) {
            questionList.appendChild(createQuestionCard());
        }
    }

    if (addQuestionButton) {
        addQuestionButton.addEventListener('click', function () {
            const card = createQuestionCard();
            if (!questionList) {
                const fallback = document.getElementById('evaluation-question-list');
                if (!fallback) return;
                fallback.appendChild(card);
                return;
            }
            questionList.appendChild(card);
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    }

    ensureQuestionList();

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        collectQuestions();
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton ? submitButton.textContent : '';
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';
        }

        const formData = new FormData(form);
        const action = isEditMode ? 'pages/instructor/elearning-subpage/ajax/edit-evaluation.php' : form.action;

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
                throw new Error(data.message || (isEditMode ? 'Unable to update evaluation.' : 'Unable to create evaluation.'));
            }

            if (window.showToast) window.showToast(data.message || (isEditMode ? 'Evaluation updated' : 'Evaluation created'), 'success');
            if (!isEditMode) {
                form.reset();
                courseIdField.value = '';
                courseSearch.value = '';
                questionList.innerHTML = '';
                questionList.appendChild(createQuestionCard());
            }
        })
        .catch(function (error) {
            if (window.showToast) window.showToast(error.message || 'Failed to save evaluation', 'error');
        })
        .finally(function () {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        });
    });

    function applyEvaluationEditData(evaluation) {
        if (!evaluation) return;

        const titleInput = form.querySelector('input[name="title"]');
        if (titleInput) titleInput.value = evaluation.title || '';
        const durationInput = form.querySelector('input[name="duration_seconds"]');
        if (durationInput) durationInput.value = evaluation.duration_seconds || 1800;
        const passingScoreInput = form.querySelector('input[name="passing_score"]');
        if (passingScoreInput) passingScoreInput.value = evaluation.passing_score || 75;
        const maxAttemptsInput = form.querySelector('input[name="max_attempts"]');
        if (maxAttemptsInput) maxAttemptsInput.value = evaluation.max_attempts || 2;
        const questionCountInput = form.querySelector('input[name="question_count"]');
        if (questionCountInput) questionCountInput.value = evaluation.question_count || 10;
        const statusSelect = form.querySelector('select[name="status"]');
        if (statusSelect) statusSelect.value = evaluation.status || 'active';

        const showAnswersToggle = form.querySelector('input[name="show_answers_after_submit"]');
        if (showAnswersToggle) {
            showAnswersToggle.checked = !!evaluation.show_answers_after_submit;
        }

        if (evaluation.course_id) {
            pendingCourseId = String(evaluation.course_id);
            courseIdField.value = evaluation.course_id;
            hydrateSelectedCourse(evaluation.course_id);
        }

        let idInput = form.querySelector('input[name="id"]');
        if (!idInput) {
            idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            form.appendChild(idInput);
        }
        idInput.value = evaluation.id || evaluationId || '';

        const questions = Array.isArray(evaluation.questions) ? evaluation.questions : [];
        questionList.innerHTML = '';
        if (questions.length === 0) {
            questionList.appendChild(createQuestionCard());
            return;
        }

        questions.forEach(function (question) {
            questionList.appendChild(createQuestionCard(question));
        });
    }

    if (evaluationId) {
        if (initialEvaluationData) {
            applyEvaluationEditData(initialEvaluationData);
        } else {
            fetch('pages/instructor/elearning-subpage/ajax/get-evaluation-by-id.php?id=' + evaluationId, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.data) return;
                applyEvaluationEditData(data.data);
            })
            .catch(e => console.error('Error loading evaluation:', e));
        }
    }
})();
</script>
<script>
(function() {
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
})();
</script>
