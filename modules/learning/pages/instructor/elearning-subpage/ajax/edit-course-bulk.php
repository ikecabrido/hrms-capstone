<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $pdo = (new Database())->getConnection();
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data.']);
        exit;
    }

    $courseData = $input['course'] ?? [];
    $modulesData = $input['modules'] ?? [];
    $evaluationData = $input['evaluation'] ?? null;
    $courseId = (int) ($courseData['id'] ?? ($input['id'] ?? 0));

    if ($courseId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Course id is required for editing.']);
        exit;
    }

    $courseTitle = trim((string) ($courseData['title'] ?? ''));
    if ($courseTitle === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Course title is required.']);
        exit;
    }

    // Make sure the course exists
    $existsStmt = $pdo->prepare("SELECT id FROM ld_course WHERE id = :cid LIMIT 1");
    $existsStmt->execute([':cid' => $courseId]);
    if (!$existsStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Course not found.']);
        exit;
    }

    $pdo->beginTransaction();

    // Guard: bulk content replacement is only safe when learners have no recorded
    // activity (progress, quiz attempts/sessions) against this course's content.
    $guardChecks = [
        "SELECT COUNT(*) FROM ld_progress p JOIN ld_enrollment e ON e.id = p.enrollment_id WHERE e.course_id = :cid",
        "SELECT COUNT(*) FROM ld_quiz_attempt qa JOIN ld_quiz q ON q.id = qa.quiz_id JOIN ld_module m ON m.id = q.module_id WHERE m.course_id = :cid",
        "SELECT COUNT(*) FROM ld_quiz_session qs JOIN ld_quiz q ON q.id = qs.reference_id JOIN ld_module m ON m.id = q.module_id WHERE qs.item_type = 'quiz' AND m.course_id = :cid",
    ];
    foreach ($guardChecks as $sql) {
        $gStmt = $pdo->prepare($sql);
        $gStmt->execute([':cid' => $courseId]);
        if ((int) $gStmt->fetchColumn() > 0) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'This course already has learner activity (progress or quiz attempts). Rebuilding content in bulk is disabled to protect learner records. Edit individual modules/quizzes instead, or create a new course.',
            ]);
            exit;
        }
    }

    // 1. Update course meta (mirrors what the course form submits)
    $courseDescription = trim((string) ($courseData['description'] ?? ''));
    $courseCategory = trim((string) ($courseData['category'] ?? ''));
    $courseStatus = trim((string) ($courseData['status'] ?? 'draft'));
    $startDate = trim((string) ($courseData['start_date'] ?? ''));
    $enrollmentDeadline = trim((string) ($courseData['enrollment_deadline'] ?? ''));

    if (!in_array($courseStatus, ['draft', 'active', 'archived'], true)) {
        $courseStatus = 'draft';
    }

    $courseSql = 'UPDATE ld_course SET
                    title = :title,
                    description = :description,
                    category = :category,
                    status = :status,
                    start_date = :start_date,
                    enrollment_deadline = :enrollment_deadline
                  WHERE id = :id';
    $courseStmt = $pdo->prepare($courseSql);
    $courseStmt->execute([
        ':title' => $courseTitle,
        ':description' => $courseDescription !== '' ? $courseDescription : null,
        ':category' => $courseCategory !== '' ? $courseCategory : null,
        ':status' => $courseStatus,
        ':start_date' => $startDate !== '' ? $startDate : null,
        ':enrollment_deadline' => $enrollmentDeadline !== '' ? $enrollmentDeadline : null,
        ':id' => $courseId,
    ]);

    // Replace skill associations
    $pdo->prepare('DELETE FROM ld_course_skill WHERE course_id = :cid')->execute([':cid' => $courseId]);
    $skillIds = array_filter(array_map('intval', $courseData['skill_ids'] ?? []));
    if (!empty($skillIds)) {
        $skillStmt = $pdo->prepare('INSERT INTO ld_course_skill (course_id, skill_id) VALUES (:cid, :sid)');
        foreach ($skillIds as $sid) {
            $skillStmt->execute([':cid' => $courseId, ':sid' => $sid]);
        }
    }

    // 2. Delete existing content (modules → lessons/files → quizzes → questions → options)
    $moduleRows = $pdo->prepare("SELECT id FROM ld_module WHERE course_id = :cid");
    $moduleRows->execute([':cid' => $courseId]);
    $moduleIds = array_map('intval', $moduleRows->fetchAll(PDO::FETCH_COLUMN));

    if (!empty($moduleIds)) {
        $modIn = implode(',', $moduleIds);

        $lessonRows = $pdo->query("SELECT id FROM ld_lesson WHERE module_id IN ($modIn)");
        $lessonIds = array_map('intval', $lessonRows->fetchAll(PDO::FETCH_COLUMN));
        if (!empty($lessonIds)) {
            $lesIn = implode(',', $lessonIds);
            $pdo->exec("DELETE FROM ld_lesson_file WHERE lesson_id IN ($lesIn)");
            $pdo->exec("DELETE FROM ld_lesson WHERE id IN ($lesIn)");
        }

        $quizRows = $pdo->query("SELECT id FROM ld_quiz WHERE module_id IN ($modIn)");
        $quizIds = array_map('intval', $quizRows->fetchAll(PDO::FETCH_COLUMN));
        if (!empty($quizIds)) {
            $qIn = implode(',', $quizIds);
            $qRows = $pdo->query("SELECT id FROM ld_quiz_question WHERE item_type = 'quiz' AND reference_id IN ($qIn)");
            $questionIds = array_map('intval', $qRows->fetchAll(PDO::FETCH_COLUMN));
            if (!empty($questionIds)) {
                $qqIn = implode(',', $questionIds);
                $pdo->exec("DELETE FROM ld_quiz_question_option WHERE question_id IN ($qqIn)");
                $pdo->exec("DELETE FROM ld_quiz_question WHERE id IN ($qqIn)");
            }
            $pdo->exec("DELETE FROM ld_quiz WHERE id IN ($qIn)");
        }

        $pdo->exec("DELETE FROM ld_module WHERE id IN ($modIn)");
    }

    // 3. Rebuild modules, lessons and quizzes (including questions)
    $moduleOrderIndex = 0;
    foreach ($modulesData as $moduleData) {
        $moduleTitle = trim((string) ($moduleData['title'] ?? ''));
        if ($moduleTitle === '') { continue; }

        $moduleDescription = trim((string) ($moduleData['description'] ?? ''));
        $moduleStatus = trim((string) ($moduleData['status'] ?? 'active'));
        if (!in_array($moduleStatus, ['active', 'archived'], true)) { $moduleStatus = 'active'; }

        $modSql = 'INSERT INTO ld_module (course_id, title, description, order_index, status) VALUES (:cid, :title, :desc, :idx, :status)';
        $modStmt = $pdo->prepare($modSql);
        $modStmt->execute([
            ':cid' => $courseId,
            ':title' => $moduleTitle,
            ':desc' => $moduleDescription !== '' ? $moduleDescription : null,
            ':idx' => $moduleOrderIndex,
            ':status' => $moduleStatus,
        ]);
        $moduleId = (int) $pdo->lastInsertId();
        $moduleOrderIndex++;

        // Lessons
        $lessonOrderIndex = 0;
        foreach (($moduleData['lessons'] ?? []) as $lessonData) {
            $lessonTitle = trim((string) ($lessonData['title'] ?? ''));
            if ($lessonTitle === '') { continue; }

            $contentType = trim((string) ($lessonData['content_type'] ?? 'text'));
            if (!in_array($contentType, ['video', 'text', 'file', 'mixed'], true)) { $contentType = 'text'; }
            $contentBody = trim((string) ($lessonData['content_body'] ?? ''));
            $videoUrl = trim((string) ($lessonData['video_url'] ?? ''));
            $lessonStatus = trim((string) ($lessonData['status'] ?? 'active'));
            if (!in_array($lessonStatus, ['active', 'archived'], true)) { $lessonStatus = 'active'; }

            $lessonSql = 'INSERT INTO ld_lesson (module_id, title, content_type, content_body, video_url, order_index, status)
                          VALUES (:mid, :title, :type, :body, :url, :idx, :status)';
            $lessonStmt = $pdo->prepare($lessonSql);
            $lessonStmt->execute([
                ':mid' => $moduleId,
                ':title' => $lessonTitle,
                ':type' => $contentType,
                ':body' => $contentBody !== '' ? $contentBody : null,
                ':url' => $videoUrl !== '' ? $videoUrl : null,
                ':idx' => $lessonOrderIndex,
                ':status' => $lessonStatus,
            ]);
            $lessonOrderIndex++;
        }

        // Quizzes (module level) + any lesson-nested quizzes (both live at module level in ld_quiz)
        $quizOrder = 0;
        $quizGroups = [];
        foreach (($moduleData['quizzes'] ?? []) as $qz) { $quizGroups[] = $qz; }
        foreach (($moduleData['lessons'] ?? []) as $les) {
            foreach (($les['quizzes'] ?? []) as $qz) { $quizGroups[] = $qz; }
        }

        foreach ($quizGroups as $quizData) {
            $quizTitle = trim((string) ($quizData['title'] ?? ''));
            if ($quizTitle === '') { continue; }
            $quizOrder++;

            $durationSeconds = (int) ($quizData['duration_seconds'] ?? 600);
            $passingScore = isset($quizData['passing_score']) && $quizData['passing_score'] !== '' && $quizData['passing_score'] !== null ? (float) $quizData['passing_score'] : null;
            $maxAttempts = (int) ($quizData['max_attempts'] ?? 2);
            $questionCount = isset($quizData['question_count']) ? (int) $quizData['question_count'] : null;
            $showAnswers = !empty($quizData['show_answers_after_submit']);
            $quizStatus = trim((string) ($quizData['status'] ?? 'active'));
            if (!in_array($quizStatus, ['active', 'archived'], true)) { $quizStatus = 'active'; }

            $quizSql = 'INSERT INTO ld_quiz (module_id, title, duration_seconds, passing_score, max_attempts, question_count, show_answers_after_submit, status)
                        VALUES (:mid, :title, :dur, :pass, :max, :qc, :show, :status)';
            $quizStmt = $pdo->prepare($quizSql);
            $quizStmt->execute([
                ':mid' => $moduleId,
                ':title' => $quizTitle,
                ':dur' => $durationSeconds > 0 ? $durationSeconds : 600,
                ':pass' => $passingScore !== null ? $passingScore : null,
                ':max' => $maxAttempts > 0 ? $maxAttempts : 2,
                ':qc' => $questionCount !== null && $questionCount > 0 ? $questionCount : null,
                ':show' => $showAnswers ? 1 : 0,
                ':status' => $quizStatus,
            ]);
            $newQuizId = (int) $pdo->lastInsertId();

            $qIdx = 0;
            foreach (($quizData['questions'] ?? []) as $qData) {
                $qText = trim((string) ($qData['question_text'] ?? ''));
                if ($qText === '') { continue; }
                $qType = in_array($qData['question_type'] ?? '', ['single_choice', 'multiple_choice', 'true_false'], true) ? $qData['question_type'] : 'single_choice';
                $qStmt = $pdo->prepare("INSERT INTO ld_quiz_question (item_type, reference_id, question_text, question_type, order_index, status) VALUES ('quiz', :rid, :text, :type, :idx, 'active')");
                $qStmt->execute([':rid' => $newQuizId, ':text' => $qText, ':type' => $qType, ':idx' => $qIdx++]);
                $newQuestionId = (int) $pdo->lastInsertId();

                $oIdx = 0;
                foreach (($qData['options'] ?? []) as $opt) {
                    $oText = trim((string) ($opt['option_text'] ?? ''));
                    if ($oText === '') { continue; }
                    $oStmt = $pdo->prepare("INSERT INTO ld_quiz_question_option (question_id, option_text, is_correct, order_index) VALUES (:qid, :text, :correct, :idx)");
                    $oStmt->execute([':qid' => $newQuestionId, ':text' => $oText, ':correct' => !empty($opt['is_correct']) ? 1 : 0, ':idx' => $oIdx++]);
                }
            }
        }
    }

    // 4. Upsert the single course evaluation (builder model), preserving its questions
    if ($evaluationData && is_array($evaluationData)) {
        $evalTitle = trim((string) ($evaluationData['title'] ?? ''));
        if ($evalTitle !== '') {
            $evalId = (int) ($evaluationData['id'] ?? 0);
            $durationSeconds = isset($evaluationData['duration_seconds']) ? (int) $evaluationData['duration_seconds'] : null;
            $passingScore = isset($evaluationData['passing_score']) && $evaluationData['passing_score'] !== '' && $evaluationData['passing_score'] !== null ? (float) $evaluationData['passing_score'] : null;
            $maxAttempts = (int) ($evaluationData['max_attempts'] ?? 2);
            $questionCount = isset($evaluationData['question_count']) ? (int) $evaluationData['question_count'] : null;
            $showAnswers = !empty($evaluationData['show_answers_after_submit']);
            $evalStatus = trim((string) ($evaluationData['status'] ?? 'active'));
            if (!in_array($evalStatus, ['active', 'archived'], true)) { $evalStatus = 'active'; }

            $existing = null;
            if ($evalId > 0) {
                $selStmt = $pdo->prepare("SELECT id FROM ld_evaluation WHERE id = :id AND course_id = :cid LIMIT 1");
                $selStmt->execute([':id' => $evalId, ':cid' => $courseId]);
                $existing = $selStmt->fetchColumn();
            }
            if (!$existing) {
                $selStmt = $pdo->prepare("SELECT id FROM ld_evaluation WHERE course_id = :cid ORDER BY id ASC LIMIT 1");
                $selStmt->execute([':cid' => $courseId]);
                $existing = $selStmt->fetchColumn();
            }

            if ($existing) {
                $updStmt = $pdo->prepare('UPDATE ld_evaluation SET title = :title, duration_seconds = :dur, passing_score = :pass, max_attempts = :max, question_count = :qc, show_answers_after_submit = :show, status = :status WHERE id = :id');
                $updStmt->execute([
                    ':title' => $evalTitle,
                    ':dur' => $durationSeconds !== null && $durationSeconds > 0 ? $durationSeconds : null,
                    ':pass' => $passingScore !== null ? $passingScore : null,
                    ':max' => $maxAttempts > 0 ? $maxAttempts : 2,
                    ':qc' => $questionCount !== null && $questionCount > 0 ? $questionCount : null,
                    ':show' => $showAnswers ? 1 : 0,
                    ':status' => $evalStatus,
                    ':id' => (int) $existing,
                ]);
            } else {
                $insStmt = $pdo->prepare('INSERT INTO ld_evaluation (course_id, title, duration_seconds, passing_score, max_attempts, question_count, show_answers_after_submit, status)
                                          VALUES (:cid, :title, :dur, :pass, :max, :qc, :show, :status)');
                $insStmt->execute([
                    ':cid' => $courseId,
                    ':title' => $evalTitle,
                    ':dur' => $durationSeconds !== null && $durationSeconds > 0 ? $durationSeconds : null,
                    ':pass' => $passingScore !== null ? $passingScore : null,
                    ':max' => $maxAttempts > 0 ? $maxAttempts : 2,
                    ':qc' => $questionCount !== null && $questionCount > 0 ? $questionCount : null,
                    ':show' => $showAnswers ? 1 : 0,
                    ':status' => $evalStatus,
                ]);
            }

            // Replace the evaluation's questions/options with the submitted set
            $evalIdToUse = $existing ? (int) $existing : (int) $pdo->lastInsertId();
            $oldEvalQuestions = $pdo->prepare("SELECT id FROM ld_quiz_question WHERE item_type = 'evaluation' AND reference_id = :rid");
            $oldEvalQuestions->execute([':rid' => $evalIdToUse]);
            $oldQuestionIds = array_map('intval', $oldEvalQuestions->fetchAll(PDO::FETCH_COLUMN));
            if (!empty($oldQuestionIds)) {
                $qqIn = implode(',', $oldQuestionIds);
                $pdo->exec("DELETE FROM ld_quiz_question_option WHERE question_id IN ($qqIn)");
                $pdo->exec("DELETE FROM ld_quiz_question WHERE id IN ($qqIn)");
            }

            $qIdx = 0;
            foreach (($evaluationData['questions'] ?? []) as $qData) {
                $qText = trim((string) ($qData['question_text'] ?? ''));
                if ($qText === '') { continue; }
                $qType = in_array($qData['question_type'] ?? '', ['single_choice', 'multiple_choice', 'true_false'], true) ? $qData['question_type'] : 'single_choice';
                $qStmt = $pdo->prepare("INSERT INTO ld_quiz_question (item_type, reference_id, question_text, question_type, order_index, status) VALUES ('evaluation', :rid, :text, :type, :idx, 'active')");
                $qStmt->execute([':rid' => $evalIdToUse, ':text' => $qText, ':type' => $qType, ':idx' => $qIdx++]);
                $newQuestionId = (int) $pdo->lastInsertId();
                $oIdx = 0;
                foreach (($qData['options'] ?? []) as $opt) {
                    $oText = trim((string) ($opt['option_text'] ?? ''));
                    if ($oText === '') { continue; }
                    $oStmt = $pdo->prepare("INSERT INTO ld_quiz_question_option (question_id, option_text, is_correct, order_index) VALUES (:qid, :text, :correct, :idx)");
                    $oStmt->execute([':qid' => $newQuestionId, ':text' => $oText, ':correct' => !empty($opt['is_correct']) ? 1 : 0, ':idx' => $oIdx++]);
                }
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Course updated successfully with all modules, lessons, quizzes, and questions.',
        'id' => $courseId,
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update course.', 'error' => $e->getMessage()]);
}
