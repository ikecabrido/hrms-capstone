<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 3) . '/classes/quizsession.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

/**
 * Redirect from a page that renders inside the layout shell. This page is
 * included after includes/header.php has already been echoed, so a Location
 * header is usually impossible ("headers already sent"). When that happens,
 * fall back to a client-side redirect instead of emitting a PHP warning.
 */
if (!function_exists('ld_study_redirect')) {
    function ld_study_redirect(string $url): void
    {
        if (!headers_sent()) {
            header('Location: ' . $url);
            exit;
        }

        echo '<script>window.location.replace(' . json_encode($url) . ');</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES) . '"></noscript>';
        exit;
    }
}

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$sessionId = (int) ($_GET['session_id'] ?? 0);
$quizId = (int) ($_GET['quiz_id'] ?? 0);
$courseId = (int) ($_GET['course_id'] ?? 0);

$session = null;
$quiz = null;
$course = null;
$questions = [];
$questionOrder = [];
$answers = [];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $quizSession = new QuizSession($pdo);

    // If no session_id, we need to start a new session
    if ($sessionId <= 0 && $quizId > 0) {
        $result = $quizSession->start($learnerId, 'quiz', $quizId);
        if (!empty($result['success'])) {
            $sessionId = (int) $result['session_id'];
        } else {
            // Failed to start — redirect back to quiz intro
            ld_study_redirect('?page=learner/study-subpage/quiz&quiz_id=' . $quizId . '&course_id=' . $courseId . '&error=' . urlencode($result['message'] ?? 'Failed to start'));
        }
    }

    // Load session
    $sessionStmt = $pdo->prepare("SELECT * FROM ld_quiz_session WHERE id = :id AND learner_id = :learner_id LIMIT 1");
    $sessionStmt->execute([':id' => $sessionId, ':learner_id' => $learnerId]);
    $session = $sessionStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$session) {
        ld_study_redirect('?page=learner/study');
    }

    // If already submitted, redirect to review
    if ($session['status'] !== 'in_progress') {
        ld_study_redirect('?page=learner/study-subpage/quiz-review&session_id=' . $sessionId . '&course_id=' . $courseId);
    }

    $quizId = (int) $session['reference_id'];
    if (!$courseId) {
        // Get course from quiz
        $courseStmt = $pdo->prepare("SELECT m.course_id FROM ld_quiz q JOIN ld_module m ON m.id = q.module_id WHERE q.id = :quiz_id LIMIT 1");
        $courseStmt->execute([':quiz_id' => $quizId]);
        $courseId = (int) $courseStmt->fetchColumn();
    }

    // Load quiz details
    $quizStmt = $pdo->prepare("SELECT q.*, m.title AS module_title, c.title AS course_title FROM ld_quiz q JOIN ld_module m ON m.id = q.module_id JOIN ld_course c ON c.id = m.course_id WHERE q.id = :id LIMIT 1");
    $quizStmt->execute([':id' => $quizId]);
    $quiz = $quizStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    $courseStmt = $pdo->prepare("SELECT id, title FROM ld_course WHERE id = :id LIMIT 1");
    $courseStmt->execute([':id' => $courseId]);
    $course = $courseStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    // Load question order from session
    $questionOrder = json_decode($session['question_order'], true) ?? [];

    // Load questions and their options
    foreach ($questionOrder as $qo) {
        $qId = (int) $qo['question_id'];
        $qStmt = $pdo->prepare("SELECT id, question_text, question_type FROM ld_quiz_question WHERE id = :id AND status = 'active' LIMIT 1");
        $qStmt->execute([':id' => $qId]);
        $qRow = $qStmt->fetch(PDO::FETCH_ASSOC);
        if (!$qRow) continue;

        // Load options in the randomized order
        $optionIds = $qo['option_order'] ?? [];
        $options = [];
        foreach ($optionIds as $optId) {
            $oStmt = $pdo->prepare("SELECT id, option_text FROM ld_quiz_question_option WHERE id = :id AND question_id = :qid LIMIT 1");
            $oStmt->execute([':id' => (int) $optId, ':qid' => $qId]);
            $oRow = $oStmt->fetch(PDO::FETCH_ASSOC);
            if ($oRow) $options[] = $oRow;
        }

        $questions[] = [
            'id' => $qId,
            'question_text' => $qRow['question_text'],
            'question_type' => $qRow['question_type'],
            'options' => $options,
        ];
    }

    // Load existing answers
    $ansStmt = $pdo->prepare("SELECT question_id, selected_option_id, is_marked_for_review FROM ld_quiz_session_answer WHERE quiz_session_id = :session_id");
    $ansStmt->execute([':session_id' => $sessionId]);
    $ansRows = $ansStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($ansRows as $ar) {
        $answers[(int) $ar['question_id']] = [
            'selected_option_id' => $ar['selected_option_id'] !== null ? (int) $ar['selected_option_id'] : null,
            'is_marked_for_review' => (bool) $ar['is_marked_for_review'],
        ];
    }

    // Timer values — computed client-side using browser clock
    $quizDurationSeconds = $session['duration_seconds'] !== null && (int) $session['duration_seconds'] > 0
        ? (int) $session['duration_seconds']
        : null;
    $sessionStartedAt = $session['started_at'] ?? null;

} catch (Throwable $e) {
    ld_study_redirect('?page=learner/study');
}
?>
<div class="module-content">
    <!-- Sticky bar: quiz info + timer + submit -->
    <div class="quiz-bar">
        <div class="quiz-bar-info">
            <h2 class="quiz-bar-title"><?= htmlspecialchars($quiz['title'] ?? 'Quiz') ?></h2>
            <span class="quiz-bar-sub"><?= htmlspecialchars($course['title'] ?? '') ?></span>
        </div>
        <div class="quiz-bar-actions">
            <div id="timer-display" class="quiz-timer"><?php if ($quizDurationSeconds !== null): ?>--:--<?php else: ?>&infin;<?php endif; ?></div>
            <button type="button" id="submit-quiz-btn" class="quiz-btn quiz-btn-primary">
                <i class="fas fa-paper-plane"></i> Submit Quiz
            </button>
        </div>
    </div>

    <div class="quiz-layout">
        <!-- Questions -->
        <div id="questions-container">
            <?php foreach ($questions as $idx => $question):
                $qId = $question['id'];
                $selectedOption = $answers[$qId]['selected_option_id'] ?? null;
                $isMarked = $answers[$qId]['is_marked_for_review'] ?? false;
            ?>
            <div class="quiz-question" id="question-<?= $qId ?>" data-question-id="<?= $qId ?>" style="display:<?= $idx === 0 ? 'block' : 'none' ?>;">
                <div class="quiz-q-head">
                    <div class="quiz-q-meta">
                        <span class="quiz-q-num"><?= $idx + 1 ?></span>
                        <span class="quiz-q-type"><?= str_replace('_', ' ', $question['question_type']) ?></span>
                    </div>
                    <button type="button" class="mark-review-btn" data-question-id="<?= $qId ?>" style="background:<?= $isMarked ? '#ffc107' : '#f0f0f0' ?>; color:<?= $isMarked ? '#000' : '#666' ?>;">
                        <i class="fas fa-flag"></i> <?= $isMarked ? 'Unmark' : 'Mark for Review' ?>
                    </button>
                </div>
                <p class="quiz-q-text"><?= htmlspecialchars($question['question_text']) ?></p>

                <div class="quiz-options">
                    <?php foreach ($question['options'] as $opt): $sel = ($selectedOption == $opt['id']); ?>
                        <label class="quiz-option <?= $sel ? 'is-selected' : '' ?>">
                            <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                <input type="checkbox" name="answer[<?= $qId ?>][]" value="<?= $opt['id'] ?>" <?= $sel ? 'checked' : '' ?>>
                            <?php else: ?>
                                <input type="radio" name="answer[<?= $qId ?>]" value="<?= $opt['id'] ?>" <?= $sel ? 'checked' : '' ?>>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($opt['option_text']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Prev/Next navigation within question -->
                <div class="quiz-nav-row">
                    <button type="button" class="nav-prev-btn quiz-btn quiz-btn-ghost" <?= $idx === 0 ? 'disabled' : '' ?> data-index="<?= $idx ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <?php if ($idx < count($questions) - 1): ?>
                    <button type="button" class="nav-next-btn quiz-btn quiz-btn-primary" data-index="<?= $idx ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                    <?php else: ?>
                    <button type="button" id="final-submit-btn" class="quiz-btn quiz-btn-primary">
                        <i class="fas fa-check"></i> Finish
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Navigator Sidebar -->
        <aside class="quiz-nav-panel">
            <h3>Question Navigator</h3>
            <div id="question-nav" class="quiz-nav-grid">
                <?php foreach ($questions as $idx => $question):
                    $qId = $question['id'];
                    $answered = isset($answers[$qId]['selected_option_id']) && $answers[$qId]['selected_option_id'] !== null;
                    $marked = $answers[$qId]['is_marked_for_review'] ?? false;
                ?>
                    <button type="button" class="nav-q-btn <?= $idx === 0 ? 'active' : '' ?>" data-index="<?= $idx ?>"
                        style="border-color:<?= $marked ? '#ffc107' : ($answered ? 'var(--primary)' : '#ddd') ?>; background:<?= $answered ? 'rgba(32,0,130,0.08)' : '#fff' ?>; color:<?= $marked ? '#000' : ($answered ? 'var(--primary)' : '#333') ?>;">
                        <?= $idx + 1 ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="quiz-nav-legend">
                <div class="quiz-nav-legend-row"><span class="quiz-nav-swatch answered"></span> Answered</div>
                <div class="quiz-nav-legend-row"><span class="quiz-nav-swatch"></span> Not Answered</div>
                <div class="quiz-nav-legend-row"><span class="quiz-nav-swatch marked"></span> Marked for Review</div>
            </div>
            <div class="quiz-nav-count"><b id="answered-count">0</b> / <?= count($questions) ?> answered</div>
        </aside>
    </div>
</div>

<!-- Confirm Submit Modal -->
<div id="confirm-modal" class="modal-overlay ld-modal-scrim" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title" style="display:none; z-index:10000; padding:2rem;">
    <div class="ld-modal-panel ld-modal-confirm" style="max-width:450px; width:100%; background:var(--surface, #fff); border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.2); padding:2rem; text-align:center;">
        <div style="font-size:3rem; color:#ffc107; margin-bottom:1rem;"><i class="fas fa-exclamation-triangle"></i></div>
        <h2 id="confirm-modal-title" style="margin:0 0 0.5rem 0;">Submit Quiz?</h2>
        <p id="confirm-message" style="color:#666; margin-bottom:1.5rem;"></p>
        <div class="ld-modal-actions" style="display:flex; gap:1rem; justify-content:center;">
            <button type="button" id="cancel-submit" style="padding:0.75rem 1.5rem; background:#ccc; color:#333; border:none; border-radius:6px; cursor:pointer; font-weight:500;">Cancel</button>
            <button type="button" id="confirm-submit" style="padding:0.75rem 1.5rem; background:var(--primary); color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:700;">Submit</button>
        </div>
    </div>
</div>

<script>
(function() {
    var sessionId = <?= (int) $sessionId ?>;
    var courseId = <?= (int) $courseId ?>;
    var quizId = <?= (int) $quizId ?>;
    var totalQuestions = <?= count($questions) ?>;
    var currentIndex = 0;
    var quizDuration = <?= $quizDurationSeconds !== null ? $quizDurationSeconds : 'null' ?>;
    var sessionStartStr = <?= $sessionStartedAt ? "'" . htmlspecialchars($sessionStartedAt) . "'" : 'null' ?>;
    var questionIds = <?= json_encode(array_column($questions, 'id')) ?>;
    var answers = <?= json_encode($answers) ?>;
    var timerInterval = null;

    // Timer — compute remaining using browser clock to avoid server clock skew
    function getRemaining() {
        if (quizDuration === null || !sessionStartStr) return null;
        var sessionStartMs = new Date(sessionStartStr.replace(' ', 'T')).getTime();
        var elapsedSec = Math.floor((Date.now() - sessionStartMs) / 1000);
        return Math.max(0, quizDuration - elapsedSec);
    }

    function updateTimerDisplay() {
        var rem = getRemaining();
        var display = document.getElementById('timer-display');
        if (rem === null) { display.textContent = '\u221E'; return; }
        var mins = Math.floor(rem / 60);
        var secs = rem % 60;
        display.textContent = (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
        display.style.color = rem < 60 ? '#dc3545' : 'var(--primary)';
        return rem;
    }

    if (quizDuration !== null) {
        updateTimerDisplay();
        timerInterval = setInterval(function() {
            var rem = updateTimerDisplay();
            if (rem !== null && rem <= 0) {
                clearInterval(timerInterval);
                doSubmit(true);
            }
        }, 1000);
    }

    // Navigate to question
    function goToQuestion(index) {
        if (index < 0 || index >= totalQuestions) return;
        document.querySelectorAll('.quiz-question').forEach(function(q) { q.style.display = 'none'; });
        document.querySelectorAll('.nav-q-btn').forEach(function(b) { b.classList.remove('active'); });
        document.getElementById('question-' + questionIds[index]).style.display = 'block';
        document.querySelectorAll('.nav-q-btn')[index].classList.add('active');
        currentIndex = index;
    }

    // Nav buttons
    document.querySelectorAll('.nav-next-btn').forEach(function(btn) {
        btn.addEventListener('click', function() { goToQuestion(parseInt(this.dataset.index) + 1); });
    });
    document.querySelectorAll('.nav-prev-btn').forEach(function(btn) {
        btn.addEventListener('click', function() { goToQuestion(parseInt(this.dataset.index) - 1); });
    });
    document.querySelectorAll('.nav-q-btn').forEach(function(btn) {
        btn.addEventListener('click', function() { goToQuestion(parseInt(this.dataset.index)); });
    });

    // Auto-save answers on change
    document.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(function(input) {
        input.addEventListener('change', function() {
            var name = this.name;
            var qId = parseInt(name.match(/\d+/)[0]);
            var value = this.type === 'checkbox' ? null : parseInt(this.value);

            if (this.type === 'checkbox') {
                // Multiple choice - get all checked
                var checked = document.querySelectorAll('input[name="' + name + '"]:checked');
                if (checked.length > 0) {
                    value = parseInt(checked[0].value); // Save first checked for simplicity
                }
            }

            // Save answer
            var body = new URLSearchParams({
                session_id: sessionId,
                question_id: qId,
                selected_option_id: value || ''
            });
            fetch('pages/learner/study-subpage/ajax/progress/save-quiz-answer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) {
                    // Update local state
                    if (!answers[qId]) answers[qId] = {};
                    answers[qId].selected_option_id = value;
                    updateNavUI(qId, value !== null);
                }
            });

            // Update visual selection
            var label = this.closest('label');
            if (label) {
                var group = label.closest('div');
                group.querySelectorAll('label').forEach(function(l) {
                    l.style.background = '#f9f9f9';
                    l.style.borderColor = 'transparent';
                });
                label.style.background = 'rgba(32,0,130,0.08)';
                label.style.borderColor = 'var(--primary)';
            }
        });
    });

    function updateNavUI(qId, answered) {
        var idx = questionIds.indexOf(qId);
        if (idx === -1) return;
        var btn = document.querySelectorAll('.nav-q-btn')[idx];
        var marked = answers[qId] && answers[qId].is_marked_for_review;
        btn.style.borderColor = marked ? '#ffc107' : (answered ? 'var(--primary)' : '#ddd');
        btn.style.background = answered ? 'rgba(32,0,130,0.08)' : '#fff';
        btn.style.color = marked ? '#000' : (answered ? 'var(--primary)' : '#333');

        // Update count
        var count = 0;
        questionIds.forEach(function(id) {
            if (answers[id] && answers[id].selected_option_id !== null && answers[id].selected_option_id !== undefined) count++;
        });
        document.getElementById('answered-count').textContent = count;
    }

    // Mark for review
    document.querySelectorAll('.mark-review-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var qId = parseInt(this.dataset.questionId);
            var isMarked = !(answers[qId] && answers[qId].is_marked_for_review);
            var body = new URLSearchParams({
                session_id: sessionId,
                question_id: qId,
                marked: isMarked ? '1' : '0'
            });
            fetch('pages/learner/study-subpage/ajax/progress/mark-for-review.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) {
                    if (!answers[qId]) answers[qId] = {};
                    answers[qId].is_marked_for_review = isMarked;
                    btn.style.background = isMarked ? '#ffc107' : '#f0f0f0';
                    btn.style.color = isMarked ? '#000' : '#666';
                    btn.innerHTML = '<i class="fas fa-flag"></i> ' + (isMarked ? 'Unmark' : 'Mark for Review');
                    updateNavUI(qId, answers[qId].selected_option_id !== null);
                }
            });
        });
    });

    // Submit
    var confirmModal = document.getElementById('confirm-modal');
    var confirmMsg = document.getElementById('confirm-message');

    function showConfirm() {
        var unanswered = totalQuestions;
        questionIds.forEach(function(id) {
            if (answers[id] && answers[id].selected_option_id !== null && answers[id].selected_option_id !== undefined) unanswered--;
        });
        // More accurate count
        var answered = 0;
        questionIds.forEach(function(id) {
            if (answers[id] && answers[id].selected_option_id !== null && answers[id].selected_option_id !== undefined) answered++;
        });
        unanswered = totalQuestions - answered;
        confirmMsg.textContent = unanswered > 0
            ? 'You have ' + unanswered + ' unanswered question' + (unanswered !== 1 ? 's' : '') + '. Are you sure you want to submit?'
            : 'All questions answered. Ready to submit?';
        confirmModal.style.display = 'block';
    }

    document.getElementById('submit-quiz-btn').addEventListener('click', showConfirm);
    document.getElementById('final-submit-btn').addEventListener('click', showConfirm);
    document.getElementById('cancel-submit').addEventListener('click', function() { confirmModal.style.display = 'none'; });
    confirmModal.addEventListener('click', function(e) { if (e.target === this) this.style.display = 'none'; });

    document.getElementById('confirm-submit').addEventListener('click', function() {
        doSubmit(false);
    });

    function doSubmit(expired) {
        if (timerInterval) clearInterval(timerInterval);
        confirmModal.style.display = 'none';

        var body = new URLSearchParams({
            session_id: sessionId,
            course_id: courseId,
            quiz_id: quizId
        });

        var url = expired
            ? 'pages/learner/study-subpage/ajax/progress/auto-submit-quiz.php'
            : 'pages/learner/study-subpage/ajax/progress/submit-quiz-answer.php';

        fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body })
            .then(function(response) {
                return response.text().then(function(text) {
                    var data = null;
                    try {
                        data = JSON.parse(text);
                    } catch (parseError) {
                        data = null;
                    }

                    if (!response.ok || !data || !data.success) {
                        var submitError = new Error(
                            data && data.message
                                ? data.message
                                : 'Quiz submission returned an invalid response.'
                        );
                        submitError.response = {
                            status: response.status,
                            statusText: response.statusText,
                            body: data || text
                        };
                        throw submitError;
                    }

                    return data;
                });
            })
            .then(function(data) {
                if (data.warning) {
                    alert(data.warning);
                }
                window.location.href = '?page=learner/study-subpage/quiz-review&session_id=' + sessionId + '&course_id=' + courseId;
            })
            .catch(function(error) {
                console.error('[learning] Quiz submission request failed', {
                    sessionId: sessionId,
                    endpoint: url,
                    message: error.message,
                    response: error.response || null,
                    error: error
                });

                fetch('pages/learner/study-subpage/ajax/progress/get-quiz-status.php?session_id=' + encodeURIComponent(sessionId))
                    .then(function(r) { return r.json(); })
                    .then(function(status) {
                        if (status.success && status.status !== 'in_progress') {
                            window.location.href = '?page=learner/study-subpage/quiz-review&session_id=' + sessionId + '&course_id=' + courseId;
                            return;
                        }

                        alert(error.message || 'Unable to submit quiz. Please try again.');
                    })
                    .catch(function() {
                        alert(error.message || 'Unable to submit quiz. Please try again.');
                    });
            });
    }

    // Initialize count
    var initCount = 0;
    questionIds.forEach(function(id) {
        if (answers[id] && answers[id].selected_option_id !== null && answers[id].selected_option_id !== undefined) initCount++;
    });
    document.getElementById('answered-count').textContent = initCount;
})();
</script>
