<?php

include_once __DIR__ . '/../../../database/db.php';

class QuizSession
{
    private PDO $conn;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
            return;
        }

        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Starts a new attempt for a quiz OR evaluation.
     * - Enforces max_attempts.
     * - Randomizes question order + option order, locks it into the session.
     * - Applies question_count pooling if set (fewer questions than the full bank).
     */
    public function start(int $learnerId, string $itemType, int $referenceId): array
    {
        if (!in_array($itemType, ['quiz', 'evaluation'], true)) {
            return ['success' => false, 'message' => 'Invalid item type.'];
        }

        $item = $this->getParentItem($itemType, $referenceId);

        if (!$item) {
            return ['success' => false, 'message' => ucfirst($itemType) . ' not found.'];
        }

        // In-progress session already exists — resume it instead of starting a new one.
        $existingInProgress = $this->getActiveSession($learnerId, $itemType, $referenceId);
        if ($existingInProgress) {
            return [
                'success' => true,
                'message' => 'Resuming existing attempt.',
                'session_id' => (int) $existingInProgress['id'],
                'duration_seconds' => $existingInProgress['duration_seconds'],
                'started_at' => $existingInProgress['started_at'],
                'question_order' => json_decode($existingInProgress['question_order'], true),
            ];
        }

        $maxAttempts = (int) ($item['max_attempts'] ?? 2);
        $attemptCount = $this->countFinishedAttempts($learnerId, $itemType, $referenceId);

        if ($attemptCount >= $maxAttempts) {
            return ['success' => false, 'message' => "You've used all {$maxAttempts} attempts for this " . $itemType . '.'];
        }

        $questions = $this->getQuestionPool($itemType, $referenceId);

        if (empty($questions)) {
            return ['success' => false, 'message' => 'This ' . $itemType . ' has no questions yet.'];
        }

        $questionCount = $item['question_count'] ?? null;
        if ($questionCount !== null && (int) $questionCount > 0 && (int) $questionCount < count($questions)) {
            shuffle($questions);
            $questions = array_slice($questions, 0, (int) $questionCount);
        }

        shuffle($questions);

        $questionOrder = [];
        foreach ($questions as $question) {
            $options = $this->getOptionsForQuestion((int) $question['id']);
            $optionIds = array_column($options, 'id');
            shuffle($optionIds);

            $questionOrder[] = [
                'question_id' => (int) $question['id'],
                'option_order' => array_map('intval', $optionIds),
            ];
        }

        $durationSeconds = $item['duration_seconds']; // nullable for untimed Evaluation

        $stmt = $this->conn->prepare(
            'INSERT INTO ld_quiz_session (learner_id, item_type, reference_id, duration_seconds, question_order, status)
             VALUES (:learner_id, :item_type, :reference_id, :duration_seconds, :question_order, :status)'
        );
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':item_type' => $itemType,
            ':reference_id' => $referenceId,
            ':duration_seconds' => $durationSeconds,
            ':question_order' => json_encode($questionOrder),
            ':status' => 'in_progress',
        ]);

        $sessionId = (int) $this->conn->lastInsertId();

        // Pre-create blank answer rows so the navigator can show "unanswered" immediately.
        $insertAnswer = $this->conn->prepare(
            'INSERT INTO ld_quiz_session_answer (quiz_session_id, question_id) VALUES (:session_id, :question_id)'
        );
        foreach ($questionOrder as $q) {
            $insertAnswer->execute([':session_id' => $sessionId, ':question_id' => $q['question_id']]);
        }

        return [
            'success' => true,
            'message' => 'Attempt started.',
            'session_id' => $sessionId,
            'duration_seconds' => $durationSeconds,
            'started_at' => date('Y-m-d H:i:s'),
            'question_order' => $questionOrder,
        ];
    }

/**
 * Autosave one answer as the learner works through the quiz — does not finalize anything.
 */
public function saveAnswer(
    int $sessionId,
    int $learnerId,
    int $questionId,
    ?int $selectedOptionId
): array {
    $session = $this->getOwnedSession($sessionId, $learnerId);

    if (!$session) {
        return [
            'success' => false,
            'message' => 'Session not found.'
        ];
    }

    if ($session['status'] !== 'in_progress') {
        return [
            'success' => false,
            'message' => 'This attempt is no longer active.'
        ];
    }

    if ($this->isExpired($session)) {
        $this->autoSubmit($sessionId, $learnerId);

        return [
            'success' => false,
            'message' => 'Time is up. This attempt has been auto-submitted.'
        ];
    }

    /*
     * ---------------------------------------------------------
     * Verify that the question belongs to this session's quiz.
     * ---------------------------------------------------------
     */
    $questionStmt = $this->conn->prepare(
        'SELECT id
         FROM ld_quiz_question
         WHERE id = :question_id
           AND item_type = :item_type
           AND reference_id = :reference_id
           AND status = \'active\'
         LIMIT 1'
    );

    $questionStmt->execute([
        ':question_id' => $questionId,
        ':item_type' => $session['item_type'],
        ':reference_id' => $session['reference_id'],
    ]);

    $question = $questionStmt->fetch(PDO::FETCH_ASSOC);

    if (!$question) {
        return [
            'success' => false,
            'message' => 'Question does not belong to this quiz.'
        ];
    }

    /*
     * ---------------------------------------------------------
     * Verify that the question is part of this specific
     * randomized quiz session.
     * ---------------------------------------------------------
     */
    $questionOrder = json_decode(
        $session['question_order'],
        true
    );

    if (!is_array($questionOrder)) {
        return [
            'success' => false,
            'message' => 'Invalid quiz session question order.'
        ];
    }

    $questionInSession = false;

    foreach ($questionOrder as $item) {
        if (
            isset($item['question_id']) &&
            (int) $item['question_id'] === $questionId
        ) {
            $questionInSession = true;
            break;
        }
    }

    if (!$questionInSession) {
        return [
            'success' => false,
            'message' => 'Question is not part of this quiz attempt.'
        ];
    }

    /*
     * ---------------------------------------------------------
     * If an option was supplied, verify that it belongs to
     * this question.
     * ---------------------------------------------------------
     */
    if ($selectedOptionId !== null) {
        $optionStmt = $this->conn->prepare(
            'SELECT id
             FROM ld_quiz_question_option
             WHERE id = :option_id
               AND question_id = :question_id
             LIMIT 1'
        );

        $optionStmt->execute([
            ':option_id' => $selectedOptionId,
            ':question_id' => $questionId,
        ]);

        if (!$optionStmt->fetch(PDO::FETCH_ASSOC)) {
            return [
                'success' => false,
                'message' => 'Selected option does not belong to this question.'
            ];
        }
    }

    /*
     * ---------------------------------------------------------
     * Save answer.
     * ---------------------------------------------------------
     */
    $stmt = $this->conn->prepare(
        'UPDATE ld_quiz_session_answer
         SET selected_option_id = :selected_option_id,
             answered_at = NOW()
         WHERE quiz_session_id = :session_id
           AND question_id = :question_id'
    );

    $stmt->execute([
        ':selected_option_id' => $selectedOptionId,
        ':session_id' => $sessionId,
        ':question_id' => $questionId,
    ]);

    /*
     * rowCount() may be zero when the submitted answer is the
     * same as the existing answer. Therefore verify the row
     * exists before reporting failure.
     */
    if ($stmt->rowCount() === 0) {
        $answerStmt = $this->conn->prepare(
            'SELECT id
             FROM ld_quiz_session_answer
             WHERE quiz_session_id = :session_id
               AND question_id = :question_id
             LIMIT 1'
        );

        $answerStmt->execute([
            ':session_id' => $sessionId,
            ':question_id' => $questionId,
        ]);

        if (!$answerStmt->fetch(PDO::FETCH_ASSOC)) {
            return [
                'success' => false,
                'message' => 'Question answer record not found.'
            ];
        }
    }

    return [
        'success' => true,
        'message' => 'Answer saved.',
        'session_id' => $sessionId,
        'question_id' => $questionId,
        'selected_option_id' => $selectedOptionId
    ];
}

    /**
     * Toggles "mark for review" on a question — independent of whether it's answered.
     */
    public function markForReview(int $sessionId, int $learnerId, int $questionId, bool $marked): array
    {
        $session = $this->getOwnedSession($sessionId, $learnerId);

        if (!$session) {
            return ['success' => false, 'message' => 'Session not found.'];
        }

        $stmt = $this->conn->prepare(
            'UPDATE ld_quiz_session_answer SET is_marked_for_review = :marked
             WHERE quiz_session_id = :session_id AND question_id = :question_id'
        );
        $stmt->execute([
            ':marked' => $marked ? 1 : 0,
            ':session_id' => $sessionId,
            ':question_id' => $questionId,
        ]);

        return ['success' => true, 'message' => $marked ? 'Marked for review.' : 'Unmarked.'];
    }

    /**
     * Answered/unanswered/marked state for every question — powers the number-grid navigator.
     */
    public function getStatus(int $sessionId, int $learnerId): array
    {
        $session = $this->getOwnedSession($sessionId, $learnerId);

        if (!$session) {
            return ['success' => false, 'message' => 'Session not found.'];
        }

        $stmt = $this->conn->prepare(
            'SELECT question_id, selected_option_id, is_marked_for_review
             FROM ld_quiz_session_answer WHERE quiz_session_id = :session_id'
        );
        $stmt->execute([':session_id' => $sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $questionOrder = json_decode($session['question_order'], true) ?? [];
        $orderIndex = array_column($questionOrder, 'question_id');

        $items = array_map(function ($row) {
            return [
                'question_id' => (int) $row['question_id'],
                'answered' => $row['selected_option_id'] !== null,
                'is_marked_for_review' => (bool) $row['is_marked_for_review'],
            ];
        }, $rows);

        usort($items, function ($a, $b) use ($orderIndex) {
            return array_search($a['question_id'], $orderIndex) <=> array_search($b['question_id'], $orderIndex);
        });

        $remainingSeconds = $this->getRemainingSeconds($session);

        return [
            'success' => true,
            'status' => $session['status'],
            'remaining_seconds' => $remainingSeconds,
            'items' => $items,
        ];
    }

    /**
     * Final submission — locks the session, scores it, and (for Quiz) marks the
     * parent lesson-equivalent item complete via Progress. Caller (the AJAX file)
     * is responsible for calling Progress::markComplete() afterward using the
     * item_type/reference_id and enrollment_id it already has in scope.
     */
    public function submit(int $sessionId, int $learnerId): array
    {
        $session = $this->getOwnedSession($sessionId, $learnerId);

        if (!$session) {
            return ['success' => false, 'message' => 'Session not found.'];
        }

        if ($session['status'] !== 'in_progress') {
            return ['success' => false, 'message' => 'This attempt has already been submitted.'];
        }

        if ($this->isExpired($session)) {
            return $this->autoSubmit($sessionId, $learnerId);
        }

        return $this->finalizeSession($session);
    }

    /**
     * Server-side fallback when the timer runs out — force-submits whatever
     * was answered so far. Same finalize logic as a normal submit.
     */
    public function autoSubmit(int $sessionId, int $learnerId): array
    {
        $session = $this->getOwnedSession($sessionId, $learnerId);

        if (!$session || $session['status'] !== 'in_progress') {
            return ['success' => false, 'message' => 'Session not found or already finalized.'];
        }

        return $this->finalizeSession($session, true);
    }

    /**
     * Post-submission breakdown per question. Only returns data if:
     *  1. the session belongs to the requesting learner, AND
     *  2. the session is finalized (not in_progress), AND
     *  3. the parent quiz/evaluation has show_answers_after_submit = true.
     */
    public function getResult(int $sessionId, int $learnerId): array
    {
        $session = $this->getOwnedSession($sessionId, $learnerId);

        if (!$session) {
            return ['success' => false, 'message' => 'Session not found.'];
        }

        if ($session['status'] === 'in_progress') {
            return ['success' => false, 'message' => 'This attempt has not been submitted yet.'];
        }

        $item = $this->getParentItem($session['item_type'], $session['reference_id']);

        if (empty($item['show_answers_after_submit'])) {
            return [
                'success' => true,
                'blind' => true,
                'score' => $session['score'],
                'passed' => (bool) $session['passed'],
                'message' => 'Answer review is not enabled for this ' . $session['item_type'] . '.',
            ];
        }

        $stmt = $this->conn->prepare(
            'SELECT sa.question_id, sa.selected_option_id, q.question_text
             FROM ld_quiz_session_answer sa
             JOIN ld_quiz_question q ON q.id = sa.question_id
             WHERE sa.quiz_session_id = :session_id'
        );
        $stmt->execute([':session_id' => $sessionId]);
        $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $breakdown = [];
        foreach ($answers as $answer) {
            $options = $this->getOptionsForQuestion((int) $answer['question_id']);
            $correctOption = null;
            foreach ($options as $opt) {
                if (!empty($opt['is_correct'])) {
                    $correctOption = (int) $opt['id'];
                    break;
                }
            }

            $breakdown[] = [
                'question_id' => (int) $answer['question_id'],
                'question_text' => $answer['question_text'],
                'options' => array_map(function ($o) {
                    return ['id' => (int) $o['id'], 'option_text' => $o['option_text']];
                }, $options),
                'selected_option_id' => $answer['selected_option_id'] !== null ? (int) $answer['selected_option_id'] : null,
                'correct_option_id' => $correctOption,
                'is_correct' => $answer['selected_option_id'] !== null && (int) $answer['selected_option_id'] === $correctOption,
            ];
        }

        return [
            'success' => true,
            'blind' => false,
            'score' => $session['score'],
            'passed' => (bool) $session['passed'],
            'questions' => $breakdown,
        ];
    }

    public function getLatestScore(int $learnerId, string $itemType, int $referenceId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM ld_quiz_session
             WHERE learner_id = :learner_id AND item_type = :item_type AND reference_id = :reference_id
               AND status IN ('submitted','expired')
             ORDER BY submitted_at DESC LIMIT 1"
        );
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':item_type' => $itemType,
            ':reference_id' => $referenceId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    private function finalizeSession(array $session, bool $expired = false): array
    {
        $sessionId = (int) $session['id'];

        $stmt = $this->conn->prepare(
            'SELECT sa.selected_option_id, o.is_correct
             FROM ld_quiz_session_answer sa
             LEFT JOIN ld_quiz_question_option o ON o.id = sa.selected_option_id
             WHERE sa.quiz_session_id = :session_id'
        );
        $stmt->execute([':session_id' => $sessionId]);
        $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = count($answers);
        $correct = 0;
        foreach ($answers as $a) {
            if (!empty($a['is_correct'])) {
                $correct++;
            }
        }

        $score = $total > 0 ? round(($correct / $total) * 100, 2) : 0.0;

        $item = $this->getParentItem($session['item_type'], $session['reference_id']);
        $passingScore = $item['passing_score'] ?? null;
        $passed = $passingScore !== null ? $score >= (float) $passingScore : null;

        $status = $expired ? 'expired' : 'submitted';

        $update = $this->conn->prepare(
            'UPDATE ld_quiz_session
             SET status = :status, submitted_at = NOW(), score = :score, passed = :passed
             WHERE id = :id'
        );
        $update->execute([
            ':status' => $status,
            ':score' => $score,
            ':passed' => $passed,
            ':id' => $sessionId,
        ]);

        // Also record into the simple attempt log used by Analytics/Results summaries.
        $attempt = $this->conn->prepare(
            'INSERT INTO ld_quiz_attempt (learner_id, quiz_id, quiz_session_id, score, total_items, passed)
             VALUES (:learner_id, :quiz_id, :quiz_session_id, :score, :total_items, :passed)'
        );
        $attempt->execute([
            ':learner_id' => $session['learner_id'],
            ':quiz_id' => $session['item_type'] === 'quiz' ? $session['reference_id'] : null,
            ':quiz_session_id' => $sessionId,
            ':score' => $score,
            ':total_items' => $total,
            ':passed' => $passed ?? false,
        ]);

        return [
            'success' => true,
            'message' => $expired ? 'Time expired — attempt auto-submitted.' : 'Attempt submitted.',
            'score' => $score,
            'passed' => $passed,
            'total_items' => $total,
            'correct_items' => $correct,
        ];
    }

    private function getParentItem(string $itemType, int $referenceId): ?array
    {
        $table = $itemType === 'quiz' ? 'ld_quiz' : 'ld_evaluation';

        $stmt = $this->conn->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $referenceId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function getQuestionPool(string $itemType, int $referenceId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM ld_quiz_question
             WHERE item_type = :item_type AND reference_id = :reference_id AND status = 'active'"
        );
        $stmt->execute([':item_type' => $itemType, ':reference_id' => $referenceId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getOptionsForQuestion(int $questionId): array
    {
        $stmt = $this->conn->prepare('SELECT * FROM ld_quiz_question_option WHERE question_id = :question_id');
        $stmt->execute([':question_id' => $questionId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getActiveSession(int $learnerId, string $itemType, int $referenceId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM ld_quiz_session
             WHERE learner_id = :learner_id AND item_type = :item_type AND reference_id = :reference_id
               AND status = 'in_progress'
             LIMIT 1"
        );
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':item_type' => $itemType,
            ':reference_id' => $referenceId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function getOwnedSession(int $sessionId, int $learnerId): ?array
    {
        $stmt = $this->conn->prepare('SELECT * FROM ld_quiz_session WHERE id = :id AND learner_id = :learner_id LIMIT 1');
        $stmt->execute([':id' => $sessionId, ':learner_id' => $learnerId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function countFinishedAttempts(int $learnerId, string $itemType, int $referenceId): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM ld_quiz_session
             WHERE learner_id = :learner_id AND item_type = :item_type AND reference_id = :reference_id
               AND status IN ('submitted','expired')"
        );
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':item_type' => $itemType,
            ':reference_id' => $referenceId,
        ]);

        return (int) $stmt->fetchColumn();
    }

    private function isExpired(array $session): bool
    {
        if ($session['duration_seconds'] === null) {
            return false; // untimed evaluation
        }

        $elapsed = time() - strtotime($session['started_at']);

        return $elapsed > (int) $session['duration_seconds'];
    }

    private function getRemainingSeconds(array $session): ?int
    {
        if ($session['duration_seconds'] === null) {
            return null;
        }

        $elapsed = time() - strtotime($session['started_at']);
        $remaining = (int) $session['duration_seconds'] - $elapsed;

        return max(0, $remaining);
    }
}