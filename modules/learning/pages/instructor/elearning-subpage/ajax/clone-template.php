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
    $database = new Database();
    $pdo = $database->getConnection();
    $employeeId = (int) $_SESSION['employee_id'];

    $input = json_decode(file_get_contents('php://input'), true);
    $templateId = isset($input['template_id']) ? (int) $input['template_id'] : 0;
    $courseTitle = trim((string) ($input['title'] ?? ''));

    if ($templateId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'template_id is required.']);
        exit;
    }

    // Fetch template
    $stmt = $pdo->prepare("SELECT * FROM ld_course_template WHERE id = :id");
    $stmt->execute([':id' => $templateId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Template not found.']);
        exit;
    }

    $structure = json_decode($template['structure_json'], true);
    if (!$structure) {
        throw new Exception('Invalid template structure.');
    }

    $pdo->beginTransaction();

    // Create new course
    $newTitle = $courseTitle !== '' ? $courseTitle : ($template['title'] ?? 'Cloned Course');
    $courseStmt = $pdo->prepare("INSERT INTO ld_course (instructor_id, title, description, category, status) VALUES (:iid, :title, :desc, :cat, 'draft')");
    $courseStmt->execute([
        ':iid' => $employeeId,
        ':title' => $newTitle,
        ':desc' => $template['description'] ?? '',
        ':cat' => $template['category'] ?? null,
    ]);
    $newCourseId = (int) $pdo->lastInsertId();

    $moduleCount = 0;
    $lessonCount = 0;
    $quizCount = 0;

    foreach (($structure['modules'] ?? []) as $mod) {
        // Create module
        $modStmt = $pdo->prepare("INSERT INTO ld_module (course_id, title, description, order_index, status) VALUES (:cid, :title, :desc, :idx, :status)");
        $modStmt->execute([
            ':cid' => $newCourseId,
            ':title' => $mod['title'] ?? 'Untitled Module',
            ':desc' => $mod['description'] ?? null,
            ':idx' => $mod['order_index'] ?? 0,
            ':status' => $mod['status'] ?? 'active',
        ]);
        $newModuleId = (int) $pdo->lastInsertId();
        $moduleCount++;

        // Create lessons
        foreach (($mod['lessons'] ?? []) as $les) {
            $lesStmt = $pdo->prepare("INSERT INTO ld_lesson (module_id, title, content_type, content_body, video_url, order_index, status) VALUES (:mid, :title, :type, :body, :url, :idx, :status)");
            $lesStmt->execute([
                ':mid' => $newModuleId,
                ':title' => $les['title'] ?? 'Untitled Lesson',
                ':type' => $les['content_type'] ?? 'text',
                ':body' => $les['content_body'] ?? null,
                ':url' => $les['video_url'] ?? null,
                ':idx' => $les['order_index'] ?? 0,
                ':status' => $les['status'] ?? 'active',
            ]);
            $newLessonId = (int) $pdo->lastInsertId();
            $lessonCount++;

            // Copy files (just the records, physical files stay)
            foreach (($les['files'] ?? []) as $file) {
                $fileStmt = $pdo->prepare("INSERT INTO ld_lesson_file (lesson_id, file_path, title) VALUES (:lid, :path, :title)");
                $fileStmt->execute([
                    ':lid' => $newLessonId,
                    ':path' => $file['file_path'] ?? '',
                    ':title' => $file['title'] ?? basename($file['file_path'] ?? ''),
                ]);
            }
        }

        // Create module-level quizzes
        foreach (($mod['quizzes'] ?? []) as $qz) {
            $quizStmt = $pdo->prepare("INSERT INTO ld_quiz (module_id, title, duration_seconds, passing_score, max_attempts, question_count, show_answers_after_submit, status) VALUES (:mid, :title, :dur, :pass, :max, :qc, :show, :status)");
            $quizStmt->execute([
                ':mid' => $newModuleId,
                ':title' => $qz['title'] ?? 'Untitled Quiz',
                ':dur' => $qz['duration_seconds'] ?? 600,
                ':pass' => $qz['passing_score'] ?? 75,
                ':max' => $qz['max_attempts'] ?? 2,
                ':qc' => $qz['question_count'] ?? null,
                ':show' => $qz['show_answers_after_submit'] ?? 0,
                ':status' => $qz['status'] ?? 'active',
            ]);
            $newQuizId = (int) $pdo->lastInsertId();
            $quizCount++;

            // Create questions
            $qIdx = 0;
            foreach (($qz['questions'] ?? []) as $question) {
                $qStmt = $pdo->prepare("INSERT INTO ld_quiz_question (item_type, reference_id, question_text, question_type, order_index, status) VALUES ('quiz', :rid, :text, :type, :idx, 'active')");
                $qStmt->execute([
                    ':rid' => $newQuizId,
                    ':text' => $question['question_text'] ?? '',
                    ':type' => $question['question_type'] ?? 'single_choice',
                    ':idx' => $qIdx++,
                ]);
                $newQuestionId = (int) $pdo->lastInsertId();

                // Create options
                $oIdx = 0;
                foreach (($question['options'] ?? []) as $opt) {
                    $oStmt = $pdo->prepare("INSERT INTO ld_quiz_question_option (question_id, option_text, is_correct, order_index) VALUES (:qid, :text, :correct, :idx)");
                    $oStmt->execute([
                        ':qid' => $newQuestionId,
                        ':text' => $opt['option_text'] ?? '',
                        ':correct' => $opt['is_correct'] ?? 0,
                        ':idx' => $oIdx++,
                    ]);
                }
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'course_id' => $newCourseId,
        'message' => "Cloned! Created course with {$moduleCount} modules, {$lessonCount} lessons, {$quizCount} quizzes.",
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
