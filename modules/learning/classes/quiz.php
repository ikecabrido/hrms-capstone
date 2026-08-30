<?php

include_once __DIR__ . '/../../../database/db.php';

class Quiz
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
        $sql = 'SELECT id, module_id, title, duration_seconds, passing_score, max_attempts, question_count, show_answers_after_submit, status, created_at, updated_at FROM ld_quiz WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

        return $quiz ?: null;
    }

    public function getList(int $moduleId = 0): array
    {
        $sql = 'SELECT id, module_id, title, status FROM ld_quiz WHERE 1 = 1';
        $params = [];

        if ($moduleId > 0) {
            $sql .= ' AND module_id = :module_id';
            $params[':module_id'] = $moduleId;
        }

        $sql .= ' ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(array $input): array
    {
        $quizId = (int) ($input['id'] ?? 0);
        $moduleId = isset($input['module_id']) ? (int) $input['module_id'] : null;
        $title = trim((string) ($input['title'] ?? ''));
        $durationSeconds = isset($input['duration_seconds']) ? (int) $input['duration_seconds'] : 600;
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

        if ($quizId <= 0) {
            return ['success' => false, 'message' => 'Quiz ID is required.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Quiz title is required.'];
        }

        if ($moduleId !== null && $moduleId <= 0) {
            $moduleId = null;
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $sql = 'UPDATE ld_quiz SET title = :title, duration_seconds = :duration_seconds, passing_score = :passing_score, max_attempts = :max_attempts, question_count = :question_count, show_answers_after_submit = :show_answers_after_submit, status = :status, updated_at = NOW() WHERE id = :id';
        $params = [
            ':title' => $title,
            ':duration_seconds' => $durationSeconds > 0 ? $durationSeconds : 600,
            ':passing_score' => $passingScore !== null ? $passingScore : null,
            ':max_attempts' => $maxAttempts > 0 ? $maxAttempts : 2,
            ':question_count' => $questionCount !== null && $questionCount > 0 ? $questionCount : null,
            ':show_answers_after_submit' => $showAnswersAfterSubmit ? 1 : 0,
            ':status' => $status,
            ':id' => $quizId,
        ];

        if ($moduleId !== null) {
            $sql = 'UPDATE ld_quiz SET module_id = :module_id, title = :title, duration_seconds = :duration_seconds, passing_score = :passing_score, max_attempts = :max_attempts, question_count = :question_count, show_answers_after_submit = :show_answers_after_submit, status = :status, updated_at = NOW() WHERE id = :id';
            $params[':module_id'] = $moduleId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $deleteOptionStmt = $this->conn->prepare('DELETE FROM ld_quiz_question_option WHERE question_id IN (SELECT id FROM ld_quiz_question WHERE item_type = ? AND reference_id = ?)');
        $deleteOptionStmt->execute(['quiz', $quizId]);
        $deleteQuestionStmt = $this->conn->prepare('DELETE FROM ld_quiz_question WHERE item_type = ? AND reference_id = ?');
        $deleteQuestionStmt->execute(['quiz', $quizId]);

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
                'quiz',
                $quizId,
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
            'id' => $quizId,
            'message' => 'Quiz updated successfully',
        ];
    }

    public function archive(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Quiz ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_quiz SET status = 'archived', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return [
            'success' => true,
            'id' => $id,
            'message' => 'Quiz archived successfully',
        ];
    }

    public function create(array $input, int $moduleId = 0): array
    {
        $moduleId = (int) ($input['module_id'] ?? $moduleId);
        $lessonId = (int) ($input['lesson_id'] ?? 0);
        $title = trim((string) ($input['title'] ?? ''));
        $durationSeconds = isset($input['duration_seconds']) ? (int) $input['duration_seconds'] : 600;
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

        if ($moduleId <= 0 && $lessonId > 0) {
            $lessonStmt = $this->conn->prepare('SELECT module_id FROM ld_lesson WHERE id = :lesson_id LIMIT 1');
            $lessonStmt->execute([':lesson_id' => $lessonId]);
            $moduleId = (int) $lessonStmt->fetchColumn();
        }

        if ($moduleId <= 0) {
            return ['success' => false, 'message' => 'Module ID is required.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Quiz title is required.'];
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $sql = 'INSERT INTO ld_quiz (
                    module_id,
                    title,
                    duration_seconds,
                    passing_score,
                    max_attempts,
                    question_count,
                    show_answers_after_submit,
                    status
                ) VALUES (
                    :module_id,
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
            ':module_id' => $moduleId,
            ':title' => $title,
            ':duration_seconds' => $durationSeconds > 0 ? $durationSeconds : 600,
            ':passing_score' => $passingScore !== null ? $passingScore : null,
            ':max_attempts' => $maxAttempts > 0 ? $maxAttempts : 2,
            ':question_count' => $questionCount !== null && $questionCount > 0 ? $questionCount : null,
            ':show_answers_after_submit' => $showAnswersAfterSubmit ? 1 : 0,
            ':status' => $status,
        ]);

        $quizId = (int) $this->conn->lastInsertId();

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
                'quiz',
                $quizId,
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
            'message' => 'Quiz created successfully.',
            'id' => $quizId,
        ];
    }
}
