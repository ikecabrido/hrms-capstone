<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$sessionId = (int) ($_GET['session_id'] ?? 0);
$courseId = (int) ($_GET['course_id'] ?? 0);
$session = null;
$quiz = null;
$questions = [];
$answers = [];
$score = null;
$passed = false;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("SELECT * FROM ld_quiz_session WHERE id = :id AND learner_id = :lid LIMIT 1");
    $stmt->execute([':id' => $sessionId, ':lid' => $learnerId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($session) {
        $quizId = (int) $session['reference_id'];

        $quizStmt = $pdo->prepare("
             SELECT q.id, q.title, q.passing_score,
                 q.show_answers_after_submit AS show_answers,
                   m.title AS module_title, c.title AS course_title
            FROM ld_quiz q
            JOIN ld_module m ON m.id = q.module_id
            JOIN ld_course c ON c.id = m.course_id
            WHERE q.id = :id LIMIT 1
        ");
        $quizStmt->execute([':id' => $quizId]);
        $quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);

        if (!$courseId) {
            $courseStmt = $pdo->prepare("SELECT m.course_id FROM ld_quiz q JOIN ld_module m ON m.id = q.module_id WHERE q.id = :qid LIMIT 1");
            $courseStmt->execute([':qid' => $quizId]);
            $courseId = (int) $courseStmt->fetchColumn();
        }

        $questionOrder = json_decode($session['question_order'], true) ?? [];

        foreach ($questionOrder as $qo) {
            $qId = (int) $qo['question_id'];
            $qStmt = $pdo->prepare("SELECT id, question_text, question_type FROM ld_quiz_question WHERE id = :id LIMIT 1");
            $qStmt->execute([':id' => $qId]);
            $qRow = $qStmt->fetch(PDO::FETCH_ASSOC);
            if (!$qRow) continue;

            $options = [];
            foreach (($qo['option_order'] ?? []) as $optId) {
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

        $ansStmt = $pdo->prepare("
            SELECT qsa.question_id, qsa.selected_option_id, qpo.option_text AS selected_text,
                   qpo.id AS correct_option_id,
                   (SELECT qqpo2.option_text FROM ld_quiz_question_option qqpo2 WHERE qqpo2.question_id = qsa.question_id AND qqpo2.is_correct = 1 LIMIT 1) AS correct_text
            FROM ld_quiz_session_answer qsa
            LEFT JOIN ld_quiz_question_option qpo ON qpo.id = qsa.selected_option_id
            WHERE qsa.quiz_session_id = :sid
        ");
        $ansStmt->execute([':sid' => $sessionId]);
        $ansRows = $ansStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ansRows as $ar) {
            $answers[(int) $ar['question_id']] = [
                'selected_option_id' => $ar['selected_option_id'] !== null ? (int) $ar['selected_option_id'] : null,
                'selected_text' => $ar['selected_text'] ?? 'Not answered',
                'correct_text' => $ar['correct_text'] ?? '',
                'is_correct' => $ar['selected_text'] === $ar['correct_text'] && $ar['selected_text'] !== null,
            ];
        }

        if ($session['score'] !== null) {
            $score = (float) $session['score'];
            $passed = $quiz && $quiz['passing_score'] && $score >= $quiz['passing_score'];
        }
    }
} catch (Throwable $e) {
    $session = null;
}

if (!$session || !$quiz) {
    echo '<div class="module-content"><div class="mode-card"><h2>Results Not Found</h2><p>Unable to load quiz results.</p>';
    echo '<a href="?page=learner/study" style="display:inline-block; margin-top:1rem; padding:0.6rem 1.2rem; background:var(--primary); color:#fff; border-radius:6px; text-decoration:none;">Back to Study</a></div></div>';
    return;
}
$correctCount = count(array_filter($answers, fn($a) => $a['is_correct']));
$totalCount = count($answers);
?>

<div class="module-content">
    <div style="margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; flex-wrap:wrap;">
        <a href="?page=learner/study" style="color:var(--primary); text-decoration:none; font-weight:500;">My Study</a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <a href="?page=learner/study-subpage/course&course_id=<?= $courseId ?>" style="color:var(--primary); text-decoration:none; font-weight:500;"><?= htmlspecialchars($quiz['course_title']) ?></a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <span style="color:#666;">Quiz Results</span>
    </div>

    <!-- Score Card -->
    <div class="mode-card" style="max-width:700px; margin:0 auto 1.5rem; text-align:center; padding:2.5rem;">
        <div style="width:100px; height:100px; border-radius:50%; background:<?= $passed ? '#d4edda' : '#f8d7da' ?>; color:<?= $passed ? '#155724' : '#721c24' ?>; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem; border:4px solid <?= $passed ? '#28a745' : '#dc3545' ?>;">
            <i class="fas <?= $passed ? 'fa-check' : 'fa-times' ?>" style="font-size:2.5rem;"></i>
        </div>
        <h1 style="margin:0 0 0.5rem 0; font-size:2rem;"><?= $passed ? 'Passed!' : 'Not Passed' ?></h1>
        <p style="color:#666; margin:0 0 1.5rem 0;"><?= htmlspecialchars($quiz['title']) ?></p>

        <div style="display:flex; justify-content:center; gap:2rem; flex-wrap:wrap;">
            <div style="text-align:center;">
                <div style="font-size:2.5rem; font-weight:700; color:var(--primary);"><?= $score !== null ? round($score, 1) . '%' : 'N/A' ?></div>
                <div style="font-size:0.85rem; color:var(--text-muted);">Your Score</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:2.5rem; font-weight:700; color:var(--primary);"><?= $correctCount ?>/<?= $totalCount ?></div>
                <div style="font-size:0.85rem; color:var(--text-muted);">Correct Answers</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:2.5rem; font-weight:700; color:var(--primary);"><?= $quiz['passing_score'] ? $quiz['passing_score'] . '%' : 'N/A' ?></div>
                <div style="font-size:0.85rem; color:var(--text-muted);">Passing Score</div>
            </div>
        </div>
    </div>

    <?php if ($quiz['show_answers']): ?>
        <!-- Answer Review -->
        <div class="mode-card" style="max-width:700px; margin:0 auto;">
            <h2 style="margin-bottom:1.5rem;">Answer Review</h2>
            <?php foreach ($questions as $idx => $question):
                $ans = $answers[$question['id']] ?? null;
            ?>
                <div style="margin-bottom:1.5rem; padding:1.25rem; background:var(--surface, #fff); border:1px solid <?= ($ans && $ans['is_correct']) ? 'rgba(40,167,69,0.3)' : 'rgba(220,53,69,0.3)' ?>; border-radius:10px;">
                    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.75rem;">
                        <span style="background:var(--primary); color:#fff; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; flex-shrink:0;"><?= $idx + 1 ?></span>
                        <i class="fas <?= ($ans && $ans['is_correct']) ? 'fa-check-circle' : 'fa-times-circle' ?>" style="color:<?= ($ans && $ans['is_correct']) ? '#28a745' : '#dc3545' ?>; font-size:1.2rem;"></i>
                    </div>
                    <p style="font-weight:600; margin:0 0 0.75rem 0;"><?= htmlspecialchars($question['question_text']) ?></p>
                    <div style="padding:0.5rem 0.75rem; background:rgba(32,0,130,0.04); border-radius:6px; font-size:0.9rem; margin-bottom:0.3rem;">
                        <strong>Your answer:</strong> <?= htmlspecialchars($ans['selected_text'] ?? 'Not answered') ?>
                    </div>
                    <?php if (!$ans['is_correct'] && !empty($ans['correct_text'])): ?>
                        <div style="padding:0.5rem 0.75rem; background:rgba(40,167,69,0.08); border-radius:6px; font-size:0.9rem;">
                            <strong>Correct answer:</strong> <?= htmlspecialchars($ans['correct_text']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="text-align:center; margin-top:1.5rem;">
        <a href="?page=learner/study-subpage/quiz&quiz_id=<?= $quiz['id'] ?>&course_id=<?= $courseId ?>" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.75rem 1.5rem; background:var(--primary); color:#fff; border-radius:8px; text-decoration:none; font-weight:600; margin-right:0.5rem;">
            <i class="fas fa-redo"></i> Retake Quiz
        </a>
        <a href="?page=learner/study-subpage/course&course_id=<?= $courseId ?>" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.75rem 1.5rem; background:var(--surface, #f0f0f0); border:1px solid rgba(32,0,130,0.12); border-radius:8px; text-decoration:none; font-weight:600; color:inherit;">
            <i class="fas fa-arrow-left"></i> Back to Course
        </a>
    </div>
</div>
