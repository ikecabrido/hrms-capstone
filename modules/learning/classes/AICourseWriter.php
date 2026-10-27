<?php
/**
 * AICourseWriter - transactional writer for a ready AI course blueprint.
 *
 * The writer creates a normal draft course and its modules, lessons, quizzes,
 * evaluation, and skill links. It never creates enrollments, progress, grades,
 * notifications, or any other trainee-side effect. The job row is locked during
 * apply and a repeated apply returns the existing course instead of duplicating
 * it.
 */

require_once __DIR__ . '/AIContentRenderer.php';
require_once __DIR__ . '/AIAudit.php';

class AICourseWriter
{
    private PDO $pdo;
    private ?AIAudit $audit;

    public function __construct(PDO $pdo, ?AIAudit $audit = null)
    {
        $this->pdo = $pdo;
        $this->audit = $audit;
    }

    /**
     * @return array<string, mixed>
     */
    public function apply(int $jobId, int $adminId): array
    {
        if ($jobId <= 0) {
            return $this->error('A valid job id is required.', 422);
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('SELECT * FROM ld_ai_course_job WHERE id = :id LIMIT 1 FOR UPDATE');
            $stmt->execute([':id' => $jobId]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$job) {
                $this->pdo->rollBack();
                return $this->error('Job not found.', 404);
            }

            $status = (string) ($job['status'] ?? '');
            $existingCourseId = (int) ($job['course_id'] ?? 0);
            if ($status === 'applied' && $existingCourseId > 0) {
                $this->pdo->commit();
                return $this->success('This draft course has already been applied.', $existingCourseId, []);
            }

            if ($status !== 'ready') {
                $this->pdo->rollBack();
                return $this->error('Only a ready job can be applied.', 409);
            }

            $blueprint = json_decode((string) ($job['blueprint_json'] ?? ''), true);
            if (!is_array($blueprint)) {
                $this->pdo->rollBack();
                return $this->error('The generated blueprint is missing or invalid.', 422);
            }

            $quality = is_array($blueprint['quality'] ?? null) ? $blueprint['quality'] : [];
            $blocking = is_array($quality['blocking'] ?? null) ? $quality['blocking'] : [];
            if (empty($quality['passed']) || $blocking !== []) {
                $this->pdo->rollBack();
                return $this->error('Resolve the quality blocking issues before applying this draft.', 422);
            }

            $courseData = is_array($blueprint['course'] ?? null) ? $blueprint['course'] : [];
            $modules = is_array($blueprint['modules'] ?? null) ? array_values($blueprint['modules']) : [];
            if ($modules === []) {
                $this->pdo->rollBack();
                return $this->error('The generated blueprint has no modules.', 422);
            }

            $counts = [
                'modules' => 0,
                'lessons' => 0,
                'quizzes' => 0,
                'quiz_questions' => 0,
                'evaluation' => 0,
                'evaluation_questions' => 0,
                'course_skills' => 0,
            ];

            $courseId = $this->insertCourse($job, $courseData);
            $counts['course_skills'] = $this->insertCourseSkills($courseId, $courseData['skill_ids'] ?? []);

            foreach ($modules as $moduleIndex => $module) {
                if (!is_array($module)) {
                    continue;
                }
                $moduleId = $this->insertModule($courseId, $module, $moduleIndex);
                $counts['modules']++;

                $lessons = is_array($module['lessons'] ?? null) ? array_values($module['lessons']) : [];
                foreach ($lessons as $lessonIndex => $lesson) {
                    if (!is_array($lesson)) {
                        continue;
                    }
                    $this->insertLesson($moduleId, $lesson, $lessonIndex);
                    $counts['lessons']++;
                }

                $quiz = is_array($module['quiz'] ?? null) ? $module['quiz'] : null;
                if ($quiz !== null) {
                    $quizResult = $this->insertQuiz($moduleId, $quiz);
                    $counts['quizzes']++;
                    $counts['quiz_questions'] += $quizResult;
                }
            }

            $evaluation = is_array($blueprint['evaluation'] ?? null) ? $blueprint['evaluation'] : null;
            if ($evaluation !== null) {
                $evaluationResult = $this->insertEvaluation($courseId, $evaluation);
                $counts['evaluation'] = 1;
                $counts['evaluation_questions'] = $evaluationResult;
            }

            $update = $this->pdo->prepare(
                "UPDATE ld_ai_course_job
                    SET status = 'applied',
                        course_id = :course_id,
                        applied_at = NOW(),
                        updated_at = NOW()
                  WHERE id = :id AND status = 'ready'"
            );
            $update->execute([':course_id' => $courseId, ':id' => $jobId]);
            if ($update->rowCount() !== 1) {
                $this->pdo->rollBack();
                return $this->error('The job changed while it was being applied. Reload and try again.', 409);
            }

            if ($this->audit !== null) {
                $this->audit->log($adminId, 'course.applied', 'ai_job', (string) $jobId, [
                    'course_id' => $courseId,
                    'modules' => $counts['modules'],
                    'lessons' => $counts['lessons'],
                    'quizzes' => $counts['quizzes'],
                    'quiz_questions' => $counts['quiz_questions'],
                    'evaluation_questions' => $counts['evaluation_questions'],
                ]);
            }

            $this->pdo->commit();

            return $this->success('The draft course was created.', $courseId, $counts);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return $this->error('The draft course could not be created. No partial course was saved.', 500);
        }
    }

    /**
     * @param array<string, mixed> $job
     * @param array<string, mixed> $courseData
     */
    private function insertCourse(array $job, array $courseData): int
    {
        $title = trim((string) ($courseData['title'] ?? 'Untitled AI course'));
        if ($title === '') {
            $title = 'Untitled AI course';
        }
        $description = trim((string) ($courseData['description'] ?? ''));
        $category = trim((string) ($courseData['category'] ?? ''));
        $deliveryMode = (string) ($courseData['delivery_mode'] ?? 'online');
        if (!in_array($deliveryMode, ['online', 'face_to_face', 'hybrid'], true)) {
            $deliveryMode = 'online';
        }

        $ownerId = (int) ($job['course_owner_id'] ?? 0);
        if ($ownerId <= 0) {
            $ownerId = (int) ($job['created_by'] ?? 0);
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO ld_course
                (instructor_id, title, description, category, status, delivery_mode)
             VALUES
                (:instructor_id, :title, :description, :category, 'draft', :delivery_mode)"
        );
        $stmt->execute([
            ':instructor_id' => $ownerId > 0 ? $ownerId : null,
            ':title' => mb_substr($title, 0, 255),
            ':description' => $description,
            ':category' => $category !== '' ? mb_substr($category, 0, 100) : null,
            ':delivery_mode' => $deliveryMode,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<int, mixed> $skillIds
     */
    private function insertCourseSkills(int $courseId, array $skillIds): int
    {
        $ids = [];
        foreach ($skillIds as $skillId) {
            $id = (int) $skillId;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
        if ($ids === []) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO ld_course_skill (course_id, skill_id) VALUES (:course_id, :skill_id)'
        );
        $count = 0;
        foreach (array_keys($ids) as $skillId) {
            $stmt->execute([':course_id' => $courseId, ':skill_id' => $skillId]);
            $count += $stmt->rowCount();
        }

        return $count;
    }

    /**
     * @param array<string, mixed> $module
     */
    private function insertModule(int $courseId, array $module, int $index): int
    {
        $title = trim((string) ($module['title'] ?? 'Untitled module'));
        if ($title === '') {
            $title = 'Untitled module';
        }
        $description = trim((string) ($module['description'] ?? ''));

        $stmt = $this->pdo->prepare(
            "INSERT INTO ld_module (course_id, title, description, order_index, status)
             VALUES (:course_id, :title, :description, :order_index, 'active')"
        );
        $stmt->execute([
            ':course_id' => $courseId,
            ':title' => mb_substr($title, 0, 255),
            ':description' => $description,
            ':order_index' => $index + 1,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $lesson
     */
    private function insertLesson(int $moduleId, array $lesson, int $index): int
    {
        $title = trim((string) ($lesson['title'] ?? 'Untitled lesson'));
        if ($title === '') {
            $title = 'Untitled lesson';
        }
        $content = AIContentRenderer::render((string) ($lesson['content_markdown'] ?? ''));

        $stmt = $this->pdo->prepare(
            "INSERT INTO ld_lesson (module_id, title, content_type, content_body, order_index, status)
             VALUES (:module_id, :title, 'text', :content_body, :order_index, 'active')"
        );
        $stmt->execute([
            ':module_id' => $moduleId,
            ':title' => mb_substr($title, 0, 255),
            ':content_body' => $content,
            ':order_index' => $index + 1,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $quiz
     */
    private function insertQuiz(int $moduleId, array $quiz): int
    {
        $questions = is_array($quiz['questions'] ?? null) ? array_values($quiz['questions']) : [];
        $title = trim((string) ($quiz['title'] ?? 'Module knowledge check'));
        if ($title === '') {
            $title = 'Module knowledge check';
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO ld_quiz
                (module_id, title, duration_seconds, passing_score, max_attempts,
                 question_count, show_answers_after_submit, status)
             VALUES
                (:module_id, :title, :duration_seconds, :passing_score, :max_attempts,
                 :question_count, :show_answers, 'active')"
        );
        $stmt->execute([
            ':module_id' => $moduleId,
            ':title' => mb_substr($title, 0, 255),
            ':duration_seconds' => $this->boundedInt($quiz['duration_seconds'] ?? 600, 60, 7200, 600),
            ':passing_score' => $this->boundedFloat($quiz['passing_score'] ?? 70, 0, 100, 70),
            ':max_attempts' => $this->boundedInt($quiz['max_attempts'] ?? 2, 1, 10, 2),
            ':question_count' => count($questions),
            ':show_answers' => !empty($quiz['show_answers_after_submit']) ? 1 : 0,
        ]);

        $quizId = (int) $this->pdo->lastInsertId();

        return $this->insertQuestions('quiz', $quizId, $questions);
    }

    /**
     * @param array<string, mixed> $evaluation
     */
    private function insertEvaluation(int $courseId, array $evaluation): int
    {
        $questions = is_array($evaluation['questions'] ?? null) ? array_values($evaluation['questions']) : [];
        $title = trim((string) ($evaluation['title'] ?? 'Final evaluation'));
        if ($title === '') {
            $title = 'Final evaluation';
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO ld_evaluation
                (course_id, title, duration_seconds, passing_score, max_attempts,
                 question_count, show_answers_after_submit, status)
             VALUES
                (:course_id, :title, :duration_seconds, :passing_score, :max_attempts,
                 :question_count, :show_answers, 'active')"
        );
        $stmt->execute([
            ':course_id' => $courseId,
            ':title' => mb_substr($title, 0, 255),
            ':duration_seconds' => $this->boundedInt($evaluation['duration_seconds'] ?? 1200, 60, 7200, 1200),
            ':passing_score' => $this->boundedFloat($evaluation['passing_score'] ?? 70, 0, 100, 70),
            ':max_attempts' => $this->boundedInt($evaluation['max_attempts'] ?? 2, 1, 10, 2),
            ':question_count' => count($questions),
            ':show_answers' => !empty($evaluation['show_answers_after_submit']) ? 1 : 0,
        ]);

        $evaluationId = (int) $this->pdo->lastInsertId();

        return $this->insertQuestions('evaluation', $evaluationId, $questions);
    }

    /**
     * @param array<int, mixed> $questions
     */
    private function insertQuestions(string $itemType, int $referenceId, array $questions): int
    {
        $questionStmt = $this->pdo->prepare(
            "INSERT INTO ld_quiz_question
                (item_type, reference_id, question_text, question_type, order_index, status)
             VALUES
                (:item_type, :reference_id, :question_text, :question_type, :order_index, 'active')"
        );
        $optionStmt = $this->pdo->prepare(
            'INSERT INTO ld_quiz_question_option (question_id, option_text, is_correct, order_index)
             VALUES (:question_id, :option_text, :is_correct, :order_index)'
        );

        $inserted = 0;
        foreach ($questions as $questionIndex => $question) {
            if (!is_array($question)) {
                continue;
            }
            $text = trim((string) ($question['question_text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $type = (string) ($question['question_type'] ?? 'single_choice');
            if (!in_array($type, ['single_choice', 'true_false'], true)) {
                $type = 'single_choice';
            }
            $questionStmt->execute([
                ':item_type' => $itemType,
                ':reference_id' => $referenceId,
                ':question_text' => $text,
                ':question_type' => $type,
                ':order_index' => $questionIndex + 1,
            ]);
            $questionId = (int) $this->pdo->lastInsertId();
            $inserted++;

            $options = is_array($question['options'] ?? null) ? array_values($question['options']) : [];
            foreach ($options as $optionIndex => $option) {
                if (!is_array($option)) {
                    continue;
                }
                $optionText = trim((string) ($option['text'] ?? ''));
                if ($optionText === '') {
                    continue;
                }
                $optionStmt->execute([
                    ':question_id' => $questionId,
                    ':option_text' => $optionText,
                    ':is_correct' => !empty($option['is_correct']) ? 1 : 0,
                    ':order_index' => $optionIndex + 1,
                ]);
            }
        }

        return $inserted;
    }

    private function boundedInt(mixed $value, int $min, int $max, int $fallback): int
    {
        $number = is_numeric($value) ? (int) $value : $fallback;
        return max($min, min($max, $number));
    }

    private function boundedFloat(mixed $value, float $min, float $max, float $fallback): float
    {
        $number = is_numeric($value) ? (float) $value : $fallback;
        return max($min, min($max, $number));
    }

    /**
     * @param array<string, mixed> $counts
     * @return array<string, mixed>
     */
    private function success(string $message, int $courseId, array $counts): array
    {
        return [
            'success' => true,
            'message' => $message,
            'course_id' => $courseId,
            'redirect' => '?page=instructor/elearning-subpage/course-structure&course_id=' . $courseId,
            'counts' => $counts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function error(string $message, int $status): array
    {
        return [
            'success' => false,
            'message' => $message,
            'http' => $status,
        ];
    }
}
