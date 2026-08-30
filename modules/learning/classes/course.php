<?php

include_once __DIR__ . '/../../../database/db.php';

class Course
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

    public function getRecent($limit = 20): array
    {
        $sql = 'SELECT id, title, description, category, status, thumbnail_path, created_at, start_date, enrollment_deadline
                FROM ld_course
                ORDER BY created_at DESC
                LIMIT :limit';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM ld_course WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        return $course ?: null;
    }

    public function getList(): array
    {
        $sql = 'SELECT id, title FROM ld_course WHERE status IN (\'draft\', \'active\', \'archived\') ORDER BY title ASC';
        $stmt = $this->conn->query($sql);

        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function update(array $input): array
    {
        $courseId = (int) ($input['id'] ?? 0);
        $title = trim((string) ($input['title'] ?? ''));
        $category = $input['category'] ?? null;
        $status = trim((string) ($input['status'] ?? 'draft'));
        $description = $input['description'] ?? null;
        $startDate = $input['start_date'] ?? null;
        $enrollmentDeadline = $input['enrollment_deadline'] ?? null;

        if ($courseId <= 0) {
            return ['success' => false, 'message' => 'Course ID is required.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Course title is required.'];
        }

        if (!in_array($status, ['draft', 'active', 'archived'], true)) {
            $status = 'draft';
        }

        $stmt = $this->conn->prepare('UPDATE ld_course SET title = ?, category = ?, status = ?, description = ?, start_date = ?, enrollment_deadline = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([
            $title,
            $category,
            $status,
            $description,
            $startDate,
            $enrollmentDeadline,
            $courseId,
        ]);

        // Update skill associations
        if (array_key_exists('skill_ids', $input)) {
            $this->conn->prepare('DELETE FROM ld_course_skill WHERE course_id = :cid')->execute([':cid' => $courseId]);
            $skillIds = array_filter(array_map('intval', $input['skill_ids'] ?? []));
            if (!empty($skillIds)) {
                $skillStmt = $this->conn->prepare('INSERT INTO ld_course_skill (course_id, skill_id) VALUES (:cid, :sid)');
                foreach ($skillIds as $sid) {
                    $skillStmt->execute([':cid' => $courseId, ':sid' => $sid]);
                }
            }
        }

        return [
            'success' => true,
            'id' => $courseId,
            'message' => 'Course updated successfully',
        ];
    }

    public function archive(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Course ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_course SET status = 'archived', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return [
            'success' => true,
            'id' => $id,
            'message' => 'Course archived successfully',
        ];
    }

    public function create(array $input, array $files = [], int $employeeId = 0, ?string $learningRole = null): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $category = trim((string) ($input['category'] ?? ''));
        $thumbnailPath = trim((string) ($input['thumbnail_path'] ?? ''));
        $status = trim((string) ($input['status'] ?? 'draft'));
        $startDate = trim((string) ($input['start_date'] ?? ''));
        $enrollmentDeadline = trim((string) ($input['enrollment_deadline'] ?? ''));

        $learningRole = strtolower((string) ($learningRole ?? ''));
        $isAdmin = $learningRole === 'admin' || !empty($input['is_admin']) || !empty($input['admin_access']);

        if ($employeeId <= 0 && !$isAdmin) {
            return ['success' => false, 'message' => 'Unauthorized.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Course title is required.'];
        }

        if (!in_array($status, ['draft', 'active', 'archived'], true)) {
            $status = 'draft';
        }

        if (!empty($files['thumbnail']['name']) && is_uploaded_file($files['thumbnail']['tmp_name'])) {
            $uploadDir = dirname(__DIR__, 2) . '/assets/uploads/course-thumbnails/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $originalName = basename($files['thumbnail']['name']);
            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
            $fileName = time() . '-' . $safeName;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($files['thumbnail']['tmp_name'], $targetPath)) {
                $thumbnailPath = 'assets/uploads/course-thumbnails/' . $fileName;
            }
        }

        $sql = 'INSERT INTO ld_course (
                    instructor_id,
                    title,
                    description,
                    thumbnail_path,
                    category,
                    status,
                    start_date,
                    enrollment_deadline
                ) VALUES (
                    :instructor_id,
                    :title,
                    :description,
                    :thumbnail_path,
                    :category,
                    :status,
                    :start_date,
                    :enrollment_deadline
                )';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':instructor_id' => $employeeId,
            ':title' => $title,
            ':description' => $description,
            ':thumbnail_path' => $thumbnailPath !== '' ? $thumbnailPath : null,
            ':category' => $category !== '' ? $category : null,
            ':status' => $status,
            ':start_date' => $startDate !== '' ? $startDate : null,
            ':enrollment_deadline' => $enrollmentDeadline !== '' ? $enrollmentDeadline : null,
        ]);

        $courseId = (int) $this->conn->lastInsertId();

        // Save skill associations
        $skillIds = array_filter(array_map('intval', $input['skill_ids'] ?? []));
        if (!empty($skillIds)) {
            $skillStmt = $this->conn->prepare('INSERT INTO ld_course_skill (course_id, skill_id) VALUES (:cid, :sid)');
            foreach ($skillIds as $sid) {
                $skillStmt->execute([':cid' => $courseId, ':sid' => $sid]);
            }
        }

        return [
            'success' => true,
            'message' => 'Course created successfully.',
            'id' => $courseId,
        ];
    }

    /**
     * Publish a course: draft -> active.
     */
    public function publish(int $courseId): array
    {
        if ($courseId <= 0) {
            return ['success' => false, 'message' => 'Invalid course ID.'];
        }

        $stmt = $this->conn->prepare('UPDATE ld_course SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':status' => 'active', ':id' => $courseId]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Course not found or already active.'];
        }

        return ['success' => true, 'message' => 'Course published successfully.'];
    }

    /**
     * Duplicate a course and its nested content (modules, lessons, quizzes).
     * Creates the copy with status = 'draft'.
     */
    public function duplicate(int $courseId, int $instructorId = 0): array
    {
        if ($courseId <= 0) {
            return ['success' => false, 'message' => 'Invalid course ID.'];
        }

        $stmt = $this->conn->prepare('SELECT * FROM ld_course WHERE id = :id');
        $stmt->execute([':id' => $courseId]);
        $course = $stmt->fetch();
        if (!$course) {
            return ['success' => false, 'message' => 'Course not found.'];
        }

        $this->conn->beginTransaction();
        try {
            // Clone course
            $ins = $this->conn->prepare(
                'INSERT INTO ld_course (title, description, category, status, instructor_id, created_at, updated_at)'
                . ' VALUES (:title, :desc, :cat, :status, :iid, NOW(), NOW())'
            );
            $ins->execute([
                ':title'  => $course['title'] . ' — Copy',
                ':desc'   => $course['description'],
                ':cat'    => $course['category'],
                ':status' => 'draft',
                ':iid'    => $instructorId > 0 ? $instructorId : $course['instructor_id'],
            ]);
            $newCourseId = (int) $this->conn->lastInsertId();

            // Clone modules + lessons + quizzes
            $modStmt = $this->conn->prepare('SELECT * FROM ld_module WHERE course_id = :cid ORDER BY order_index');
            $modStmt->execute([':cid' => $courseId]);
            $modules = $modStmt->fetchAll();

            $lessonStmt = $this->conn->prepare('SELECT * FROM ld_lesson WHERE module_id = :mid ORDER BY order_index');
            $quizStmt = $this->conn->prepare('SELECT * FROM ld_quiz WHERE module_id = :mid');
            $questionStmt = $this->conn->prepare('SELECT * FROM ld_quiz_question WHERE quiz_id = :qid ORDER BY id');
            $optionStmt = $this->conn->prepare('SELECT * FROM ld_quiz_question_option WHERE question_id = :qid');

            $insMod = $this->conn->prepare(
                'INSERT INTO ld_module (course_id, title, description, order_index, status)'
                . ' VALUES (:cid, :title, :desc, :oi, :status)'
            );
            $insLesson = $this->conn->prepare(
                'INSERT INTO ld_lesson (module_id, title, content_body, content_type, video_url, order_index, status)'
                . ' VALUES (:mid, :title, :body, :ctype, :vurl, :oi, :status)'
            );
            $insQuiz = $this->conn->prepare(
                'INSERT INTO ld_quiz (module_id, title, duration_seconds, passing_score, max_attempts, show_answers_after_submit, status)'
                . ' VALUES (:mid, :title, :dur, :pass, :max, :show, :status)'
            );
            $insQuestion = $this->conn->prepare(
                'INSERT INTO ld_quiz_question (quiz_id, question_text, question_type, order_index)'
                . ' VALUES (:qid, :text, :type, :oi)'
            );
            $insOption = $this->conn->prepare(
                'INSERT INTO ld_quiz_question_option (question_id, option_text, is_correct, order_index)'
                . ' VALUES (:qid, :text, :correct, :oi)'
            );

            foreach ($modules as $mod) {
                $insMod->execute([
                    ':cid'    => $newCourseId,
                    ':title'  => $mod['title'],
                    ':desc'   => $mod['description'],
                    ':oi'     => $mod['order_index'],
                    ':status' => 'draft',
                ]);
                $newModId = (int) $this->conn->lastInsertId();

                // Clone lessons
                $lessonStmt->execute([':mid' => $mod['id']]);
                foreach ($lessonStmt->fetchAll() as $lesson) {
                    $insLesson->execute([
                        ':mid'    => $newModId,
                        ':title'  => $lesson['title'],
                        ':body'   => $lesson['content_body'] ?? '',
                        ':ctype'  => $lesson['content_type'] ?? 'text',
                        ':vurl'   => $lesson['video_url'] ?? '',
                        ':oi'     => $lesson['order_index'],
                        ':status' => 'draft',
                    ]);
                }

                // Clone quizzes + questions + options
                $quizStmt->execute([':mid' => $mod['id']]);
                foreach ($quizStmt->fetchAll() as $quiz) {
                    $insQuiz->execute([
                        ':mid'    => $newModId,
                        ':title'  => $quiz['title'],
                        ':dur'    => $quiz['duration_seconds'] ?? 600,
                        ':pass'   => $quiz['passing_score'] ?? 60,
                        ':max'    => $quiz['max_attempts'] ?? 2,
                        ':show'   => $quiz['show_answers_after_submit'] ?? 0,
                        ':status' => 'draft',
                    ]);
                    $newQuizId = (int) $this->conn->lastInsertId();

                    $questionStmt->execute([':qid' => $quiz['id']]);
                    foreach ($questionStmt->fetchAll() as $q) {
                        $insQuestion->execute([
                            ':qid'  => $newQuizId,
                            ':text' => $q['question_text'],
                            ':type' => $q['question_type'] ?? 'multiple_choice',
                            ':oi'   => $q['order_index'] ?? 0,
                        ]);
                        $newQid = (int) $this->conn->lastInsertId();

                        $optionStmt->execute([':qid' => $q['id']]);
                        foreach ($optionStmt->fetchAll() as $opt) {
                            $insOption->execute([
                                ':qid'     => $newQid,
                                ':text'    => $opt['option_text'],
                                ':correct' => $opt['is_correct'] ? 1 : 0,
                                ':oi'      => $opt['order_index'] ?? 0,
                            ]);
                        }
                    }
                }
            }

            $this->conn->commit();
            return [
                'success' => true,
                'message' => 'Course duplicated successfully.',
                'new_course_id' => $newCourseId,
            ];
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
