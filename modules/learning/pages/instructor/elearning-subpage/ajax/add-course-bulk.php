<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 7) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Get request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data.']);
        exit;
    }

    $instructorId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;
    $learningRole = strtolower((string) ($_SESSION['learning_role'] ?? $_SESSION['role'] ?? $_SESSION['user_role'] ?? ''));
    $isAdmin = $learningRole === 'admin' || !empty($_SESSION['is_admin']) || !empty($_SESSION['admin_access']);

    if ($instructorId <= 0 && !$isAdmin) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    // Extract course data
    $courseData = $data['course'] ?? [];
    $modulesData = $data['modules'] ?? [];
    $evaluationData = $data['evaluation'] ?? null;

    // Validate course data
    $courseTitle = trim((string) ($courseData['title'] ?? ''));
    if ($courseTitle === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Course title is required.']);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    // 1. Create course
    $courseDescription = trim((string) ($courseData['description'] ?? ''));
    $courseCategory = trim((string) ($courseData['category'] ?? ''));
    $courseStatus = trim((string) ($courseData['status'] ?? 'draft'));
    $startDate = trim((string) ($courseData['start_date'] ?? ''));
    $enrollmentDeadline = trim((string) ($courseData['enrollment_deadline'] ?? ''));
    $thumbnailPath = trim((string) ($courseData['thumbnail_path'] ?? ''));

    if (!in_array($courseStatus, ['draft', 'active', 'archived'], true)) {
        $courseStatus = 'draft';
    }

    $courseSql = 'INSERT INTO ld_course (
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

    $courseStmt = $pdo->prepare($courseSql);
    $courseStmt->execute([
        ':instructor_id' => $instructorId,
        ':title' => $courseTitle,
        ':description' => $courseDescription !== '' ? $courseDescription : null,
        ':thumbnail_path' => $thumbnailPath !== '' ? $thumbnailPath : null,
        ':category' => $courseCategory !== '' ? $courseCategory : null,
        ':status' => $courseStatus,
        ':start_date' => $startDate !== '' ? $startDate : null,
        ':enrollment_deadline' => $enrollmentDeadline !== '' ? $enrollmentDeadline : null,
    ]);

    $courseId = (int) $pdo->lastInsertId();

    // Save skill associations
    $skillIds = array_filter(array_map('intval', $courseData['skill_ids'] ?? []));
    if (!empty($skillIds)) {
        $skillStmt = $pdo->prepare('INSERT INTO ld_course_skill (course_id, skill_id) VALUES (:cid, :sid)');
        foreach ($skillIds as $sid) {
            $skillStmt->execute([':cid' => $courseId, ':sid' => $sid]);
        }
    }

    // 2. Create modules with their lessons and quizzes
    $createdModules = [];
    $moduleOrderIndex = 0;

    if (is_array($modulesData)) {
        foreach ($modulesData as $moduleData) {
            $moduleTitle = trim((string) ($moduleData['title'] ?? ''));
            if ($moduleTitle === '') {
                continue; // Skip empty modules
            }

            $moduleDescription = trim((string) ($moduleData['description'] ?? ''));
            $moduleStatus = trim((string) ($moduleData['status'] ?? 'active'));

            if (!in_array($moduleStatus, ['active', 'archived'], true)) {
                $moduleStatus = 'active';
            }

            $moduleSql = 'INSERT INTO ld_module (course_id, title, description, order_index, status) 
                         VALUES (:course_id, :title, :description, :order_index, :status)';
            $moduleStmt = $pdo->prepare($moduleSql);
            $moduleStmt->execute([
                ':course_id' => $courseId,
                ':title' => $moduleTitle,
                ':description' => $moduleDescription !== '' ? $moduleDescription : null,
                ':order_index' => $moduleOrderIndex,
                ':status' => $moduleStatus,
            ]);

            $moduleId = (int) $pdo->lastInsertId();
            $moduleOrderIndex++;

            $createdModules[$moduleOrderIndex - 1] = [
                'id' => $moduleId,
                'title' => $moduleTitle,
                'lessons' => [],
                'quizzes' => []
            ];

            // 2a. Create lessons for this module
            $lessonOrderIndex = 0;
            $lessonsData = $moduleData['lessons'] ?? [];

            if (is_array($lessonsData)) {
                foreach ($lessonsData as $lessonData) {
                    $lessonTitle = trim((string) ($lessonData['title'] ?? ''));
                    if ($lessonTitle === '') {
                        continue; // Skip empty lessons
                    }

                    $contentType = trim((string) ($lessonData['content_type'] ?? 'text'));
                    $contentBody = trim((string) ($lessonData['content_body'] ?? ''));
                    $videoUrl = trim((string) ($lessonData['video_url'] ?? ''));
                    $lessonStatus = trim((string) ($lessonData['status'] ?? 'active'));

                    if (!in_array($contentType, ['video', 'text', 'file', 'mixed'], true)) {
                        $contentType = 'text';
                    }

                    if (!in_array($lessonStatus, ['active', 'archived'], true)) {
                        $lessonStatus = 'active';
                    }

                    $lessonSql = 'INSERT INTO ld_lesson (
                                    module_id,
                                    title,
                                    content_type,
                                    content_body,
                                    video_url,
                                    order_index,
                                    status
                                ) VALUES (
                                    :module_id,
                                    :title,
                                    :content_type,
                                    :content_body,
                                    :video_url,
                                    :order_index,
                                    :status
                                )';

                    $lessonStmt = $pdo->prepare($lessonSql);
                    $lessonStmt->execute([
                        ':module_id' => $moduleId,
                        ':title' => $lessonTitle,
                        ':content_type' => $contentType,
                        ':content_body' => $contentBody !== '' ? $contentBody : null,
                        ':video_url' => $videoUrl !== '' ? $videoUrl : null,
                        ':order_index' => $lessonOrderIndex,
                        ':status' => $lessonStatus,
                    ]);

                    $lessonId = (int) $pdo->lastInsertId();
                    $createdModules[$moduleOrderIndex - 1]['lessons'][] = [
                        'id' => $lessonId,
                        'title' => $lessonTitle
                    ];

                    // 2a-i. Create quiz for this lesson (if provided)
                    $lessonQuizzesData = $lessonData['quizzes'] ?? [];
                    if (is_array($lessonQuizzesData)) {
                        foreach ($lessonQuizzesData as $quizData) {
                            $quizTitle = trim((string) ($quizData['title'] ?? ''));
                            if ($quizTitle === '') {
                                continue;
                            }

                            $durationSeconds = (int) ($quizData['duration_seconds'] ?? 600);
                            $passingScore = isset($quizData['passing_score']) ? (float) $quizData['passing_score'] : null;
                            $maxAttempts = (int) ($quizData['max_attempts'] ?? 2);
                            $questionCount = isset($quizData['question_count']) ? (int) $quizData['question_count'] : null;
                            $showAnswers = isset($quizData['show_answers_after_submit']) ? (bool) $quizData['show_answers_after_submit'] : false;
                            $quizStatus = trim((string) ($quizData['status'] ?? 'active'));

                            if (!in_array($quizStatus, ['active', 'archived'], true)) {
                                $quizStatus = 'active';
                            }

                            $quizSql = 'INSERT INTO ld_quiz (
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

                            $quizStmt = $pdo->prepare($quizSql);
                            $quizStmt->execute([
                                ':module_id' => $moduleId,
                                ':title' => $quizTitle,
                                ':duration_seconds' => $durationSeconds > 0 ? $durationSeconds : 600,
                                ':passing_score' => $passingScore !== null ? $passingScore : null,
                                ':max_attempts' => $maxAttempts > 0 ? $maxAttempts : 2,
                                ':question_count' => $questionCount !== null && $questionCount > 0 ? $questionCount : null,
                                ':show_answers_after_submit' => $showAnswers ? 1 : 0,
                                ':status' => $quizStatus,
                            ]);
                        }
                    }

                    $lessonOrderIndex++;
                }
            }

            // 2b. Create quizzes for this module (module-level quizzes)
            $quizzesData = $moduleData['quizzes'] ?? [];

            if (is_array($quizzesData)) {
                foreach ($quizzesData as $quizData) {
                    $quizTitle = trim((string) ($quizData['title'] ?? ''));
                    if ($quizTitle === '') {
                        continue;
                    }

                    $durationSeconds = (int) ($quizData['duration_seconds'] ?? 600);
                    $passingScore = isset($quizData['passing_score']) ? (float) $quizData['passing_score'] : null;
                    $maxAttempts = (int) ($quizData['max_attempts'] ?? 2);
                    $questionCount = isset($quizData['question_count']) ? (int) $quizData['question_count'] : null;
                    $showAnswers = isset($quizData['show_answers_after_submit']) ? (bool) $quizData['show_answers_after_submit'] : false;
                    $quizStatus = trim((string) ($quizData['status'] ?? 'active'));

                    if (!in_array($quizStatus, ['active', 'archived'], true)) {
                        $quizStatus = 'active';
                    }

                    $quizSql = 'INSERT INTO ld_quiz (
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

                    $quizStmt = $pdo->prepare($quizSql);
                    $quizStmt->execute([
                        ':module_id' => $moduleId,
                        ':title' => $quizTitle,
                        ':duration_seconds' => $durationSeconds > 0 ? $durationSeconds : 600,
                        ':passing_score' => $passingScore !== null ? $passingScore : null,
                        ':max_attempts' => $maxAttempts > 0 ? $maxAttempts : 2,
                        ':question_count' => $questionCount !== null && $questionCount > 0 ? $questionCount : null,
                        ':show_answers_after_submit' => $showAnswers ? 1 : 0,
                        ':status' => $quizStatus,
                    ]);

                    $createdModules[$moduleOrderIndex - 1]['quizzes'][] = [
                        'id' => (int) $pdo->lastInsertId(),
                        'title' => $quizTitle
                    ];
                }
            }
        }
    }

    // 3. Create evaluation (course-level)
    if ($evaluationData && is_array($evaluationData)) {
        $evalTitle = trim((string) ($evaluationData['title'] ?? ''));
        if ($evalTitle !== '') {
            $durationSeconds = isset($evaluationData['duration_seconds']) ? (int) $evaluationData['duration_seconds'] : null;
            $passingScore = isset($evaluationData['passing_score']) ? (float) $evaluationData['passing_score'] : null;
            $maxAttempts = (int) ($evaluationData['max_attempts'] ?? 2);
            $questionCount = isset($evaluationData['question_count']) ? (int) $evaluationData['question_count'] : null;
            $showAnswers = isset($evaluationData['show_answers_after_submit']) ? (bool) $evaluationData['show_answers_after_submit'] : false;
            $evalStatus = trim((string) ($evaluationData['status'] ?? 'active'));

            if (!in_array($evalStatus, ['active', 'archived'], true)) {
                $evalStatus = 'active';
            }

            $evalSql = 'INSERT INTO ld_evaluation (
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

            $evalStmt = $pdo->prepare($evalSql);
            $evalStmt->execute([
                ':course_id' => $courseId,
                ':title' => $evalTitle,
                ':duration_seconds' => $durationSeconds !== null && $durationSeconds > 0 ? $durationSeconds : null,
                ':passing_score' => $passingScore !== null ? $passingScore : null,
                ':max_attempts' => $maxAttempts > 0 ? $maxAttempts : 2,
                ':question_count' => $questionCount !== null && $questionCount > 0 ? $questionCount : null,
                ':show_answers_after_submit' => $showAnswers ? 1 : 0,
                ':status' => $evalStatus,
            ]);
        }
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Course created successfully with modules, lessons, quizzes, and evaluation.',
        'id' => $courseId,
        'modules' => $createdModules,
    ]);
} catch (Throwable $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create course.',
        'error' => $e->getMessage(),
    ]);
}
