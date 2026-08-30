<?php

include_once __DIR__ . '/../../../database/db.php';

class Evaluation
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

    public function getById(int $id): ?array
    {
        $sql = 'SELECT id, course_id, title, description, duration_seconds, passing_score, max_attempts, question_count, show_answers_after_submit, status, created_at, updated_at FROM ld_evaluation WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

        return $evaluation ?: null;
    }

    public function getList(int $courseId = 0): array
    {
        $sql = 'SELECT id, course_id, title, status FROM ld_evaluation WHERE 1 = 1';
        $params = [];

        if ($courseId > 0) {
            $sql .= ' AND course_id = :course_id';
            $params[':course_id'] = $courseId;
        }

        $sql .= ' ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(array $input): array
    {
        $evaluationId = (int) ($input['id'] ?? 0);
        $courseId = isset($input['course_id']) ? (int) $input['course_id'] : null;
        $title = trim((string) ($input['title'] ?? ''));
        $description = $input['description'] ?? null;
        $durationSeconds = isset($input['duration_seconds']) ? (int) $input['duration_seconds'] : null;
        $passingScore = isset($input['passing_score']) ? (float) $input['passing_score'] : null;
        $maxAttempts = (int) ($input['max_attempts'] ?? 2);
        $questionCount = isset($input['question_count']) ? (int) $input['question_count'] : null;
        $showAnswersAfterSubmit = !empty($input['show_answers_after_submit']);
        $status = trim((string) ($input['status'] ?? 'active'));
        $questionsRaw = is_string($input['questions'] ?? null) ? $input['questions'] : '';
        $questions = $questionsRaw !== '' ? json_decode($questionsRaw, true) : [];

        if (!is_array($questions)) {
            $questions = [];
        }

        if ($evaluationId <= 0) {
            return ['success' => false, 'message' => 'Evaluation ID is required.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Evaluation title is required.'];
        }

        if ($courseId !== null && $courseId <= 0) {
            $courseId = null;
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $sql = 'UPDATE ld_evaluation SET title = :title, description = :description, duration_seconds = :duration_seconds, passing_score = :passing_score, max_attempts = :max_attempts, question_count = :question_count, show_answers_after_submit = :show_answers_after_submit, status = :status, updated_at = NOW() WHERE id = :id';
        $params = [
            ':title' => $title,
            ':description' => $description,
            ':duration_seconds' => $durationSeconds !== null && $durationSeconds > 0 ? $durationSeconds : null,
            ':passing_score' => $passingScore !== null ? $passingScore : null,
            ':max_attempts' => $maxAttempts > 0 ? $maxAttempts : 2,
            ':question_count' => $questionCount !== null && $questionCount > 0 ? $questionCount : null,
            ':show_answers_after_submit' => $showAnswersAfterSubmit ? 1 : 0,
            ':status' => $status,
            ':id' => $evaluationId,
        ];

        if ($courseId !== null) {
            $sql = 'UPDATE ld_evaluation SET course_id = :course_id, title = :title, description = :description, duration_seconds = :duration_seconds, passing_score = :passing_score, max_attempts = :max_attempts, question_count = :question_count, show_answers_after_submit = :show_answers_after_submit, status = :status, updated_at = NOW() WHERE id = :id';
            $params[':course_id'] = $courseId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $deleteOptionStmt = $this->conn->prepare('DELETE FROM ld_quiz_question_option WHERE question_id IN (SELECT id FROM ld_quiz_question WHERE item_type = ? AND reference_id = ?)');
        $deleteOptionStmt->execute(['evaluation', $evaluationId]);
        $deleteQuestionStmt = $this->conn->prepare('DELETE FROM ld_quiz_question WHERE item_type = ? AND reference_id = ?');
        $deleteQuestionStmt->execute(['evaluation', $evaluationId]);

        foreach ($questions as $index => $questionData) {
            $questionText = trim((string) ($questionData['question_text'] ?? ''));
            if ($questionText === '') {
                continue;
            }

            $questionType = in_array(($questionData['question_type'] ?? 'single_choice'), ['single_choice', 'multiple_choice', 'true_false', 'short_answer', 'long_answer'], true)
                ? $questionData['question_type']
                : 'single_choice';

            $insertQuestion = $this->conn->prepare('INSERT INTO ld_quiz_question (item_type, reference_id, question_text, question_type, order_index, status) VALUES (?, ?, ?, ?, ?, ?)');
            $insertQuestion->execute([
                'evaluation',
                $evaluationId,
                $questionText,
                $questionType,
                (int) ($questionData['order_index'] ?? ($index + 1)),
                'active',
            ]);

            $questionDbId = (int) $this->conn->lastInsertId();
            $options = $questionData['options'] ?? [];

            if (in_array($questionType, ['short_answer', 'long_answer'], true)) {
                $answerText = trim((string) ($questionData['correct_answer'] ?? ''));
                if ($answerText !== '') {
                    $options = [['option_text' => $answerText, 'is_correct' => true]];
                } else {
                    $options = [];
                }
            }

            foreach ($options as $optionIndex => $optionData) {
                $optionText = trim((string) ($optionData['option_text'] ?? ''));
                if ($optionText === '') {
                    continue;
                }

                $insertOption = $this->conn->prepare('INSERT INTO ld_quiz_question_option (question_id, option_text, is_correct, order_index) VALUES (?, ?, ?, ?)');
                $insertOption->execute([
                    $questionDbId,
                    $optionText,
                    !empty($optionData['is_correct']) ? 1 : 0,
                    (int) ($optionIndex + 1),
                ]);
            }
        }

        return [
            'success' => true,
            'id' => $evaluationId,
            'message' => 'Evaluation updated successfully',
        ];
    }

    public function archive(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Evaluation ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_evaluation SET status = 'archived', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return [
            'success' => true,
            'id' => $id,
            'message' => 'Evaluation archived successfully',
        ];
    }

    public function create(array $input, int $courseId = 0): array
    {
        $courseId = (int) ($input['course_id'] ?? $courseId);
        $title = trim((string) ($input['title'] ?? ''));
        $durationSeconds = isset($input['duration_seconds']) ? (int) $input['duration_seconds'] : null;
        $passingScore = isset($input['passing_score']) ? (float) $input['passing_score'] : null;
        $maxAttempts = (int) ($input['max_attempts'] ?? 2);
        $questionCount = isset($input['question_count']) ? (int) $input['question_count'] : null;
        $showAnswersAfterSubmit = !empty($input['show_answers_after_submit']);
        $status = trim((string) ($input['status'] ?? 'active'));
        $questionsRaw = trim((string) ($input['questions'] ?? ''));
        $questions = $questionsRaw !== '' ? json_decode($questionsRaw, true) : [];

        if (!is_array($questions)) {
            $questions = [];
        }

        if ($courseId <= 0) {
            return ['success' => false, 'message' => 'Course ID is required.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Evaluation title is required.'];
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $sql = 'INSERT INTO ld_evaluation (
                    course_id,
                    title,
                    duration_seconds,
                    passing_score,
                    max_attempts,
                    question_count,
                    show_answers_after_submit,
                    status
                ) VALUES (
                    :course_id,
                    :title,
                    :duration_seconds,
                    :passing_score,
                    :max_attempts,
                    :question_count,
                    :show_answers_after_submit,
                    :status
                )';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':course_id' => $courseId,
            ':title' => $title,
            ':duration_seconds' => $durationSeconds !== null && $durationSeconds > 0 ? $durationSeconds : null,
            ':passing_score' => $passingScore !== null ? $passingScore : null,
            ':max_attempts' => $maxAttempts > 0 ? $maxAttempts : 2,
            ':question_count' => $questionCount !== null && $questionCount > 0 ? $questionCount : null,
            ':show_answers_after_submit' => $showAnswersAfterSubmit ? 1 : 0,
            ':status' => $status,
        ]);

        $evaluationId = (int) $this->conn->lastInsertId();

        foreach ($questions as $index => $questionData) {
            $questionText = trim((string) ($questionData['question_text'] ?? ''));
            if ($questionText === '') {
                continue;
            }

            $questionType = in_array(($questionData['question_type'] ?? 'single_choice'), ['single_choice', 'multiple_choice', 'true_false', 'short_answer', 'long_answer'], true)
                ? $questionData['question_type']
                : 'single_choice';

            $insertQuestion = $this->conn->prepare('INSERT INTO ld_quiz_question (item_type, reference_id, question_text, question_type, order_index, status) VALUES (?, ?, ?, ?, ?, ?)');
            $insertQuestion->execute([
                'evaluation',
                $evaluationId,
                $questionText,
                $questionType,
                (int) ($questionData['order_index'] ?? ($index + 1)),
                'active',
            ]);

            $questionDbId = (int) $this->conn->lastInsertId();
            $options = $questionData['options'] ?? [];

            if (in_array($questionType, ['short_answer', 'long_answer'], true)) {
                $answerText = trim((string) ($questionData['correct_answer'] ?? ''));
                if ($answerText !== '') {
                    $options = [['option_text' => $answerText, 'is_correct' => true]];
                } else {
                    $options = [];
                }
            }

            foreach ($options as $optionIndex => $optionData) {
                $optionText = trim((string) ($optionData['option_text'] ?? ''));
                if ($optionText === '') {
                    continue;
                }

                $insertOption = $this->conn->prepare('INSERT INTO ld_quiz_question_option (question_id, option_text, is_correct, order_index) VALUES (?, ?, ?, ?)');
                $insertOption->execute([
                    $questionDbId,
                    $optionText,
                    !empty($optionData['is_correct']) ? 1 : 0,
                    (int) ($optionIndex + 1),
                ]);
            }
        }

        return [
            'success' => true,
            'message' => 'Evaluation created successfully.',
            'id' => $evaluationId,
        ];
    }
}
