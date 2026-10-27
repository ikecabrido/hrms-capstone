<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/module.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $module = new Module($pdo);

    $result = $module->create($_POST);

    if (!empty($result['success'])) {
        $moduleId = (int) $result['id'];

        // Create lessons attached to the module (JSON array submitted by the add-page builder)
        $lessons = json_decode($_POST['lessons'] ?? '[]', true);
        if (!is_array($lessons)) { $lessons = []; }
        $lessonOrder = 0;
        foreach ($lessons as $lessonData) {
            $lessonTitle = trim((string) ($lessonData['title'] ?? ''));
            if ($lessonTitle === '') { continue; }
            $contentType = in_array($lessonData['content_type'] ?? 'text', ['text', 'video', 'file', 'mixed'], true) ? $lessonData['content_type'] : 'text';
            $contentBody = trim((string) ($lessonData['content_body'] ?? ''));
            $lessonStatus = trim((string) ($lessonData['status'] ?? 'active'));
            if (!in_array($lessonStatus, ['active', 'archived'], true)) { $lessonStatus = 'active'; }
            $lStmt = $pdo->prepare('INSERT INTO ld_lesson (module_id, title, content_type, content_body, order_index, status) VALUES (:mid, :title, :type, :body, :idx, :status)');
            $lStmt->execute([
                ':mid' => $moduleId,
                ':title' => $lessonTitle,
                ':type' => $contentType,
                ':body' => $contentBody !== '' ? $contentBody : null,
                ':idx' => $lessonOrder++,
                ':status' => $lessonStatus,
            ]);
        }

        // Create module-level quizzes attached to the module (JSON array)
        $quizzes = json_decode($_POST['quizzes'] ?? '[]', true);
        if (!is_array($quizzes)) { $quizzes = []; }
        foreach ($quizzes as $quizData) {
            $quizTitle = trim((string) ($quizData['title'] ?? ''));
            if ($quizTitle === '') { continue; }
            $duration = (int) ($quizData['duration_seconds'] ?? 600);
            $passing = isset($quizData['passing_score']) && $quizData['passing_score'] !== '' && $quizData['passing_score'] !== null ? (float) $quizData['passing_score'] : null;
            $maxAttempts = (int) ($quizData['max_attempts'] ?? 2);
            $qCount = isset($quizData['question_count']) && $quizData['question_count'] ? (int) $quizData['question_count'] : null;
            $showAnswers = !empty($quizData['show_answers_after_submit']);
            $quizStatus = trim((string) ($quizData['status'] ?? 'active'));
            if (!in_array($quizStatus, ['active', 'archived'], true)) { $quizStatus = 'active'; }
            $qStmt = $pdo->prepare('INSERT INTO ld_quiz (module_id, title, duration_seconds, passing_score, max_attempts, question_count, show_answers_after_submit, status) VALUES (:mid, :title, :dur, :pass, :max, :qc, :show, :status)');
            $qStmt->execute([
                ':mid' => $moduleId,
                ':title' => $quizTitle,
                ':dur' => $duration > 0 ? $duration : 600,
                ':pass' => $passing,
                ':max' => $maxAttempts > 0 ? $maxAttempts : 2,
                ':qc' => $qCount,
                ':show' => $showAnswers ? 1 : 0,
                ':status' => $quizStatus,
            ]);
        }

        echo json_encode($result);
        exit;
    }

    $statusCode = 401;
    if (strpos($result['message'] ?? '', 'required') !== false) {
        $statusCode = 422;
    }

    http_response_code($statusCode);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create module.',
        'error' => $e->getMessage(),
    ]);
}
exit;
