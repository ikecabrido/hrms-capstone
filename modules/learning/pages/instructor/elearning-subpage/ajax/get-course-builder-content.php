<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $pdo = (new Database())->getConnection();
    $courseId = (int) ($_GET['course_id'] ?? 0);

    if ($courseId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'course_id is required.']);
        exit;
    }

    $moduleRows = $pdo->prepare("SELECT id, title, description, status, order_index FROM ld_module WHERE course_id = :cid ORDER BY order_index ASC, id ASC");
    $moduleRows->execute([':cid' => $courseId]);
    $modules = [];

    $lessonStmt = $pdo->prepare("SELECT id, title, content_type, content_body, video_url, status, order_index FROM ld_lesson WHERE module_id = :mid ORDER BY order_index ASC, id ASC");
    $quizStmt = $pdo->prepare("SELECT id, title, duration_seconds, passing_score, max_attempts, question_count, show_answers_after_submit, status FROM ld_quiz WHERE module_id = :mid ORDER BY id ASC");
    $questionStmt = $pdo->prepare("SELECT id, question_text, question_type, order_index FROM ld_quiz_question WHERE item_type = 'quiz' AND reference_id = :rid AND status = 'active' ORDER BY order_index ASC, id ASC");
    $optionStmt = $pdo->prepare("SELECT id, option_text, is_correct FROM ld_quiz_question_option WHERE question_id = :qid ORDER BY order_index ASC, id ASC");

    foreach ($moduleRows->fetchAll(PDO::FETCH_ASSOC) as $mod) {
        $module = [
            'id' => (int) $mod['id'],
            'title' => $mod['title'] ?? '',
            'description' => $mod['description'] ?? '',
            'status' => $mod['status'] ?? 'active',
            'lessons' => [],
            'quizzes' => [],
        ];

        $lessonStmt->execute([':mid' => $mod['id']]);
        foreach ($lessonStmt->fetchAll(PDO::FETCH_ASSOC) as $les) {
            $module['lessons'][] = [
                'id' => (int) $les['id'],
                'title' => $les['title'] ?? '',
                'content_type' => $les['content_type'] ?? 'text',
                'content_body' => $les['content_body'] ?? '',
                'video_url' => $les['video_url'] ?? '',
                'status' => $les['status'] ?? 'active',
                'quizzes' => [],
            ];
        }

        $quizStmt->execute([':mid' => $mod['id']]);
        foreach ($quizStmt->fetchAll(PDO::FETCH_ASSOC) as $quiz) {
            $quizData = [
                'id' => (int) $quiz['id'],
                'title' => $quiz['title'] ?? '',
                'duration_seconds' => (int) $quiz['duration_seconds'],
                'passing_score' => $quiz['passing_score'] !== null ? (float) $quiz['passing_score'] : null,
                'max_attempts' => (int) $quiz['max_attempts'],
                'question_count' => $quiz['question_count'] !== null ? (int) $quiz['question_count'] : null,
                'show_answers_after_submit' => (bool) $quiz['show_answers_after_submit'],
                'status' => $quiz['status'] ?? 'active',
                'questions' => [],
            ];

            $questionStmt->execute([':rid' => $quiz['id']]);
            foreach ($questionStmt->fetchAll(PDO::FETCH_ASSOC) as $question) {
                $questionData = [
                    'id' => (int) $question['id'],
                    'question_text' => $question['question_text'] ?? '',
                    'question_type' => $question['question_type'] ?? 'single_choice',
                    'order_index' => (int) $question['order_index'],
                    'options' => [],
                ];
                $optionStmt->execute([':qid' => $question['id']]);
                foreach ($optionStmt->fetchAll(PDO::FETCH_ASSOC) as $opt) {
                    $questionData['options'][] = [
                        'id' => (int) $opt['id'],
                        'option_text' => $opt['option_text'] ?? '',
                        'is_correct' => (int) $opt['is_correct'],
                    ];
                }
                $quizData['questions'][] = $questionData;
            }
            $module['quizzes'][] = $quizData;
        }

        $modules[] = $module;
    }

    // First evaluation for the course (builder models a single final evaluation)
    $eval = null;
    $evalStmt = $pdo->prepare("SELECT id, title, duration_seconds, passing_score, max_attempts, question_count, show_answers_after_submit, status FROM ld_evaluation WHERE course_id = :cid ORDER BY id ASC LIMIT 1");
    $evalStmt->execute([':cid' => $courseId]);
    $evalRow = $evalStmt->fetch(PDO::FETCH_ASSOC);
    if ($evalRow) {
        $eval = [
            'id' => (int) $evalRow['id'],
            'title' => $evalRow['title'] ?? '',
            'duration_seconds' => $evalRow['duration_seconds'] !== null ? (int) $evalRow['duration_seconds'] : null,
            'passing_score' => $evalRow['passing_score'] !== null ? (float) $evalRow['passing_score'] : null,
            'max_attempts' => (int) $evalRow['max_attempts'],
            'question_count' => $evalRow['question_count'] !== null ? (int) $evalRow['question_count'] : null,
            'show_answers_after_submit' => (bool) $evalRow['show_answers_after_submit'],
            'status' => $evalRow['status'] ?? 'active',
            'questions' => [],
        ];

        $evalQuestionStmt = $pdo->prepare("SELECT id, question_text, question_type, order_index FROM ld_quiz_question WHERE item_type = 'evaluation' AND reference_id = :rid AND status = 'active' ORDER BY order_index ASC, id ASC");
        $evalQuestionStmt->execute([':rid' => $evalRow['id']]);
        foreach ($evalQuestionStmt->fetchAll(PDO::FETCH_ASSOC) as $question) {
            $questionData = [
                'id' => (int) $question['id'],
                'question_text' => $question['question_text'] ?? '',
                'question_type' => $question['question_type'] ?? 'single_choice',
                'order_index' => (int) $question['order_index'],
                'options' => [],
            ];
            $optionStmt->execute([':qid' => $question['id']]);
            foreach ($optionStmt->fetchAll(PDO::FETCH_ASSOC) as $opt) {
                $questionData['options'][] = [
                    'id' => (int) $opt['id'],
                    'option_text' => $opt['option_text'] ?? '',
                    'is_correct' => (int) $opt['is_correct'],
                ];
            }
            $eval['questions'][] = $questionData;
        }
    }

    echo json_encode(['success' => true, 'modules' => $modules, 'evaluation' => $eval]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
