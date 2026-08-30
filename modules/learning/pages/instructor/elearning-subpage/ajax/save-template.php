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
    $courseId = isset($input['course_id']) ? (int) $input['course_id'] : 0;
    $templateTitle = trim((string) ($input['title'] ?? ''));
    $templateDesc = trim((string) ($input['description'] ?? ''));

    if ($courseId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'course_id is required.']);
        exit;
    }

    // Fetch full course structure
    $courseStmt = $pdo->prepare("SELECT id, title, description, category FROM ld_course WHERE id = :id");
    $courseStmt->execute([':id' => $courseId]);
    $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
    if (!$course) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Course not found.']);
        exit;
    }

    // Fetch modules
    $modStmt = $pdo->prepare("SELECT id, title, description, order_index, status FROM ld_module WHERE course_id = :cid ORDER BY order_index ASC");
    $modStmt->execute([':cid' => $courseId]);
    $modules = $modStmt->fetchAll(PDO::FETCH_ASSOC);

    $structure = ['modules' => []];
    $totalLessons = 0;
    $totalQuizzes = 0;

    foreach ($modules as &$mod) {
        // Fetch lessons for this module
        $lesStmt = $pdo->prepare("SELECT id, title, content_type, content_body, video_url, order_index, status FROM ld_lesson WHERE module_id = :mid ORDER BY order_index ASC");
        $lesStmt->execute([':mid' => $mod['id']]);
        $lessons = $lesStmt->fetchAll(PDO::FETCH_ASSOC);

        $mod['lessons'] = [];
        foreach ($lessons as &$les) {
            // Fetch quiz questions for this lesson (if any quiz is linked)
            $les['quizzes'] = [];
            $totalLessons++;

            // Fetch files
            $fileStmt = $pdo->prepare("SELECT file_path, title FROM ld_lesson_file WHERE lesson_id = :lid");
            $fileStmt->execute([':lid' => $les['id']]);
            $les['files'] = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

            $mod['lessons'][] = $les;
        }
        unset($les);

        // Fetch module-level quizzes
        $quizStmt = $pdo->prepare("SELECT id, title, duration_seconds, passing_score, max_attempts, question_count, show_answers_after_submit, status FROM ld_quiz WHERE module_id = :mid ORDER BY id ASC");
        $quizStmt->execute([':mid' => $mod['id']]);
        $quizzes = $quizStmt->fetchAll(PDO::FETCH_ASSOC);

        $mod['quizzes'] = [];
        foreach ($quizzes as &$qz) {
            // Fetch questions
            $qStmt = $pdo->prepare("SELECT id, question_text, question_type, order_index FROM ld_quiz_question WHERE item_type = 'quiz' AND reference_id = :qid ORDER BY order_index ASC");
            $qStmt->execute([':qid' => $qz['id']]);
            $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

            $qz['questions'] = [];
            foreach ($questions as &$question) {
                $oStmt = $pdo->prepare("SELECT option_text, is_correct, order_index FROM ld_quiz_question_option WHERE question_id = :qid ORDER BY order_index ASC");
                $oStmt->execute([':qid' => $question['id']]);
                $question['options'] = $oStmt->fetchAll(PDO::FETCH_ASSOC);
                $qz['questions'][] = $question;
                unset($question);
            }
            unset($question);

            $mod['quizzes'][] = $qz;
            $totalQuizzes++;
            unset($qz);
        }
        unset($qz);

        $structure['modules'][] = $mod;
        unset($mod);
    }
    unset($mod);

    if ($templateTitle === '') {
        $templateTitle = ($course['title'] ?? 'Untitled') . ' — Template';
    }

    $stmt = $pdo->prepare("INSERT INTO ld_course_template (title, description, category, created_by, structure_json, module_count, lesson_count, quiz_count) VALUES (:title, :desc, :cat, :creator, :json, :mc, :lc, :qc)");
    $stmt->execute([
        ':title' => $templateTitle,
        ':desc' => $templateDesc !== '' ? $templateDesc : ($course['description'] ?? ''),
        ':cat' => $course['category'] ?? null,
        ':creator' => $employeeId,
        ':json' => json_encode($structure),
        ':mc' => count($modules),
        ':lc' => $totalLessons,
        ':qc' => $totalQuizzes,
    ]);

    $templateId = (int) $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'id' => $templateId,
        'message' => 'Template saved with ' . count($modules) . ' modules, ' . $totalLessons . ' lessons, ' . $totalQuizzes . ' quizzes.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
