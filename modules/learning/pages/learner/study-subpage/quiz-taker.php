<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 3) . '/classes/quizsession.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

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
            header('Location: ?page=learner/study-subpage/quiz&quiz_id=' . $quizId . '&course_id=' . $courseId . '&error=' . urlencode($result['message'] ?? 'Failed to start'));
            exit;
        }
    }

    // Load session
    $sessionStmt = $pdo->prepare("SELECT * FROM ld_quiz_session WHERE id = :id AND learner_id = :learner_id LIMIT 1");
    $sessionStmt->execute([':id' => $sessionId, ':learner_id' => $learnerId]);
    $session = $sessionStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$session) {
        header('Location: ?page=learner/study');
        exit;
    }

    // If already submitted, redirect to review
    if ($session['status'] !== 'in_progress') {
        header('Location: ?page=learner/study-subpage/quiz-review&session_id=' . $sessionId . '&course_id=' . $courseId);
        exit;
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
    $quizDurationSeconds = $session['duration_seconds'] !== null ? (int) $session['duration_seconds'] : null;
    $sessionStartedAt = $session['started_at'] ?? null;

} catch (Throwable $e) {
    header('Location: ?page=learner/study');
    exit;
}
?>
<div class="module-content" style="max-width:1200px; margin:0 auto;">
    <!-- Top Bar: Timer + Quiz Info -->
    <div style="display:flex; justify-content:space-between; align-items:center; padding:1rem 1.5rem; background:var(--surface, #fff); border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-bottom:1.5rem; position:sticky; top:0; z-index:100;">
        <div>
            <h2 style="margin:0; font-size:1.1rem;"><?= htmlspecialchars($quiz['title'] ?? 'Quiz') ?></h2>
            <span style="color:#999; font-size:0.85rem;"><?= htmlspecialchars($course['title'] ?? '') ?></span>
        </div>
        <div style="display:flex; align-items:center; gap:2rem;">
            <div id="timer-display" style="font-size:1.5rem; font-weight:700; color:var(--primary); font-family:monospace;">
                <?php if ($quizDurationSeconds !== null): ?>
                    --:--
                <?php else: ?>
                    ∞
                <?php endif; ?>
            </div>
            <button type="button" id="submit-quiz-btn" style="padding:0.75rem 1.5rem; background:var(--primary); color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:700;">
                <i class="fas fa-paper-plane"></i> Submit Quiz
            </button>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 280px; gap:1.5rem; align-items:start;">
        <!-- Questions Column -->
        <div id="questions-container">
            <?php foreach ($questions as $idx => $question):
                $qId = $question['id'];
                $selectedOption = $answers[$qId]['selected_option_id'] ?? null;
                $isMarked = $answers[$qId]['is_marked_for_review'] ?? false;
            ?>
            <div class="quiz-question" id="question-<?= $qId ?>" data-question-id="<?= $qId ?>" style="display:<?= $idx === 0 ? 'block' : 'none' ?>; padding:2rem; background:var(--surface, #fff); border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.05); margin-bottom:1rem;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem;">
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <span style="background:var(--primary); color:#fff; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.9rem; flex-shrink:0;"><?= $idx + 1 ?></span>
                        <span class="pill" style="background:rgba(32,0,130,0.1); color:var(--primary);"><?= str_replace('_', ' ', ucfirst($question['question_type'])) ?></span>
                    </div>
                    <button type="button" class="mark-review-btn" data-question-id="<?= $qId ?>" style="padding:0.4rem 0.8rem; background:<?= $isMarked ? '#ffc107' : '#f0f0f0' ?>; color:<?= $isMarked ? '#000' : '#666' ?>; border:none; border-radius:6px; cursor:pointer; font-size:0.85rem; font-weight:500;">
                        <i class="fas fa-flag"></i> <?= $isMarked ? 'Unmark' : 'Mark for Review' ?>
                    </button>
                </div>
                <p style="font-size:1.1rem; line-height:1.7; margin-bottom:1.5rem;"><?= htmlspecialchars($question['question_text']) ?></p>

                <?php if ($question['question_type'] === 'true_false'): ?>
                    <div style="display:grid; gap:0.5rem;">
                        <?php foreach ($question['options'] as $opt): ?>
                            <label style="display:flex; align-items:center; gap:0.75rem; padding:1rem; background:<?= $selectedOption == $opt['id'] ? 'rgba(32,0,130,0.08)' : '#f9f9f9' ?>; border:2px solid <?= $selectedOption == $opt['id'] ? 'var(--primary)' : 'transparent' ?>; border-radius:10px; cursor:pointer; transition: all 0.2s;">
                                <input type="radio" name="answer[<?= $qId ?>]" value="<?= $opt['id'] ?>" <?= $selectedOption == $opt['id'] ? 'checked' : '' ?> style="accent-color:var(--primary);">
                                <span style="font-size:1rem;"><?= htmlspecialchars($opt['option_text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="display:grid; gap:0.5rem;">
                        <?php foreach ($question['options'] as $opt): ?>
                            <label style="display:flex; align-items:center; gap:0.75rem; padding:1rem; background:<?= $selectedOption == $opt['id'] ? 'rgba(32,0,130,0.08)' : '#f9f9f9' ?>; border:2px solid <?= $selectedOption == $opt['id'] ? 'var(--primary)' : 'transparent' ?>; border-radius:10px; cursor:pointer; transition: all 0.2s;">
                                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                    <input type="checkbox" name="answer[<?= $qId ?>][]" value="<?= $opt['id'] ?>" style="accent-color:var(--primary); width:18px; height:18px;">
                                <?php else: ?>
                                    <input type="radio" name="answer[<?= $qId ?>]" value="<?= $opt['id'] ?>" <?= $selectedOption == $opt['id'] ? 'checked' : '' ?> style="accent-color:var(--primary);">
                                <?php endif; ?>
                                <span style="font-size:1rem;"><?= htmlspecialchars($opt['option_text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Prev/Next navigation within question -->
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1.5rem; padding-top:1rem; border-top:1px solid rgba(32,0,130,0.08);">
                    <button type="button" class="nav-prev-btn" <?= $idx === 0 ? 'disabled style="opacity:0.3; cursor:not-allowed;"' : '' ?> data-index="<?= $idx ?>" style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.6rem 1.2rem;background:transparent;color:var(--muted,#666);border:1px solid rgba(32,0,130,0.15);border-radius:8px;cursor:pointer;font-weight:600;font-size:0.88rem;transition:all 0.2s;">
                        <i class="fas fa-chevron-left" style="font-size:0.75rem;"></i> Previous
                    </button>
                    <?php if ($idx < count($questions) - 1): ?>
                    <button type="button" class="nav-next-btn" data-index="<?= $idx ?>" style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.6rem 1.2rem;background:var(--primary);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;font-size:0.88rem;transition:all 0.2s;box-shadow:0 2px 8px rgba(32,0,130,0.2);">
                        Next <i class="fas fa-chevron-right" style="font-size:0.75rem;"></i>
                    </button>
                    <?php else: ?>
                    <button type="button" id="final-submit-btn" style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.6rem 1.2rem;background:var(--primary);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;font-size:0.88rem;transition:all 0.2s;box-shadow:0 2px 8px rgba(32,0,130,0.2);">
                        <i class="fas fa-check" style="font-size:0.75rem;"></i> Finish
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Navigator Sidebar -->
        <div style="position:sticky; top:80px;">
            <div style="padding:1.5rem; background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
                <h3 style="margin:0 0 1rem 0; font-size:1rem;">Question Navigator</h3>
                <div id="question-nav" style="display:grid; grid-template-columns:repeat(5, 1fr); gap:0.5rem; margin-bottom:1rem;">
                    <?php foreach ($questions as $idx => $question):
                        $qId = $question['id'];
                        $answered = isset($answers[$qId]['selected_option_id']) && $answers[$qId]['selected_option_id'] !== null;
                        $marked = $answers[$qId]['is_marked_for_review'] ?? false;
                    ?>
                        <button type="button" class="nav-q-btn <?= $idx === 0 ? 'active' : '' ?>" data-index="<?= $idx ?>"
                            style="width:36px; height:36px; border-radius:6px; border:2px solid <?= $marked ? '#ffc107' : ($answered ? 'var(--primary)' : '#ddd') ?>; background:<?= $answered ? 'rgba(32,0,130,0.08)' : '#fff' ?>; color:<?= $marked ? '#000' : ($answered ? 'var(--primary)' : '#333') ?>; cursor:pointer; font-weight:600; font-size:0.85rem; display:flex; align-items:center; justify-content:center; transition: all 0.2s; padding:0;">
                            <?= $idx + 1 ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div style="display:grid; gap:0.35rem; font-size:0.85rem; color:#666;">
                    <div style="display:flex; align-items:center; gap:0.5rem;"><span style="width:14px; height:14px; background:rgba(32,0,130,0.08); border:2px solid var(--primary); border-radius:3px;"></span> Answered</div>
                    <div style="display:flex; align-items:center; gap:0.5rem;"><span style="width:14px; height:14px; background:#fff; border:2px solid #ddd; border-radius:3px;"></span> Not Answered</div>
                    <div style="display:flex; align-items:center; gap:0.5rem;"><span style="width:14px; height:14px; background:#fff; border:2px solid #ffc107; border-radius:3px;"></span> Marked for Review</div>
                </div>
                <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid #eee; text-align:center;">
                    <span id="answered-count" style="font-weight:600; color:var(--primary);">0</span> / <?= count($questions) ?> answered
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Submit Modal -->
<div id="confirm-modal" class="modal-overlay" style="display:none; z-index:10000; padding:2rem;">
    <div style="max-width:450px; width:100%; background:var(--surface, #fff); border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.2); padding:2rem; text-align:center;">
        <div style="font-size:3rem; color:#ffc107; margin-bottom:1rem;"><i class="fas fa-exclamation-triangle"></i></div>
        <h2 style="margin:0 0 0.5rem 0;">Submit Quiz?</h2>
        <p id="confirm-message" style="color:#666; margin-bottom:1.5rem;"></p>
        <div style="display:flex; gap:1rem; justify-content:center;">
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
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    throw new Error(data.message || 'Unable to submit quiz.');
                }
                window.location.href = '?page=learner/study-subpage/quiz-review&session_id=' + sessionId + '&course_id=' + courseId;
            })
            .catch(function(error) {
                alert(error.message || 'Unable to submit quiz. Please try again.');
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
