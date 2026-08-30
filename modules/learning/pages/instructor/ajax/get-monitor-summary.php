<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';

function fetchOne(PDO $pdo, string $sql, array $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: [];
}

function fetchValue(PDO $pdo, string $sql, array $params = []) {
    $row = fetchOne($pdo, $sql, $params);
    if (!$row) {
        return 0;
    }
    $values = array_values($row);
    return (int) ($values[0] ?? 0);
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $type = isset($_GET['type']) ? trim((string) $_GET['type']) : '';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (!$type || !$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Type and ID are required.']);
        exit;
    }

    $data = ['type' => $type, 'id' => $id, 'summary' => [], 'rows' => []];

    if ($type === 'Course') {
        $moduleCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_module WHERE course_id = :id', [':id' => $id]);
        $lessonCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_lesson l INNER JOIN ld_module m ON m.id = l.module_id WHERE m.course_id = :id', [':id' => $id]);
        $quizCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_quiz q INNER JOIN ld_module m ON m.id = q.module_id WHERE m.course_id = :id', [':id' => $id]);
        $evaluationCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_evaluation WHERE course_id = :id', [':id' => $id]);
        $enrollmentCount = fetchValue($pdo, 'SELECT COUNT(DISTINCT learner_id) FROM ld_enrollment WHERE course_id = :id', [':id' => $id]);
        $sessionCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_quiz_session qs INNER JOIN ld_quiz q ON q.id = qs.reference_id WHERE qs.item_type = :item_type AND q.module_id IN (SELECT id FROM ld_module WHERE course_id = :id)', [':item_type' => 'quiz', ':id' => $id]);

        $data['summary'] = [
            ['label' => 'Modules', 'value' => $moduleCount],
            ['label' => 'Lessons', 'value' => $lessonCount],
            ['label' => 'Quizzes', 'value' => $quizCount],
            ['label' => 'Evaluations', 'value' => $evaluationCount],
            ['label' => 'Enrolled learners', 'value' => $enrollmentCount],
            ['label' => 'Assessment attempts', 'value' => $sessionCount],
        ];
    } elseif ($type === 'Module') {
        $courseRes = fetchOne($pdo, 'SELECT course_id, status FROM ld_module WHERE id = :id', [':id' => $id]);
        $courseId = (int) ($courseRes['course_id'] ?? 0);
        $lessonCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_lesson WHERE module_id = :id', [':id' => $id]);
        $quizCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_quiz WHERE module_id = :id', [':id' => $id]);
        $enrollmentCount = fetchValue($pdo, 'SELECT COUNT(DISTINCT learner_id) FROM ld_enrollment WHERE course_id = :course_id', [':course_id' => $courseId]);

        $data['summary'] = [
            ['label' => 'Parent course', 'value' => $courseId ? 'Course #' . $courseId : 'N/A'],
            ['label' => 'Lessons', 'value' => $lessonCount],
            ['label' => 'Quizzes', 'value' => $quizCount],
            ['label' => 'Learners', 'value' => $enrollmentCount],
            ['label' => 'Status', 'value' => $courseRes['status'] ?? 'active'],
        ];
    } elseif ($type === 'Lesson') {
        $moduleRes = fetchOne($pdo, 'SELECT module_id, status FROM ld_lesson WHERE id = :id', [':id' => $id]);
        $moduleId = (int) ($moduleRes['module_id'] ?? 0);
        $quizCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_quiz WHERE module_id = :module_id', [':module_id' => $moduleId]);
        $fileCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_lesson_file WHERE lesson_id = :id', [':id' => $id]);

        $data['summary'] = [
            ['label' => 'Module', 'value' => $moduleId ? 'Module #' . $moduleId : 'N/A'],
            ['label' => 'Quizzes', 'value' => $quizCount],
            ['label' => 'Files', 'value' => $fileCount],
            ['label' => 'Status', 'value' => $moduleRes['status'] ?? 'active'],
        ];
    } elseif ($type === 'Quiz') {
        $moduleRes = fetchOne($pdo, 'SELECT module_id, status, title, question_count, passing_score, max_attempts FROM ld_quiz WHERE id = :id', [':id' => $id]);
        $moduleId = (int) ($moduleRes['module_id'] ?? 0);
        $questionCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_quiz_question WHERE item_type = :item_type AND reference_id = :id', [':item_type' => 'quiz', ':id' => $id]);
        $attemptCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_quiz_session WHERE item_type = :item_type AND reference_id = :id', [':item_type' => 'quiz', ':id' => $id]);
        $passedCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_quiz_session WHERE item_type = :item_type AND reference_id = :id AND passed = 1', [':item_type' => 'quiz', ':id' => $id]);

        $data['summary'] = [
            ['label' => 'Module', 'value' => $moduleId ? 'Module #' . $moduleId : 'N/A'],
            ['label' => 'Questions', 'value' => $questionCount ?: (int) ($moduleRes['question_count'] ?? 0)],
            ['label' => 'Attempts', 'value' => $attemptCount],
            ['label' => 'Passed', 'value' => $passedCount],
            ['label' => 'Status', 'value' => $moduleRes['status'] ?? 'active'],
        ];
    } elseif ($type === 'Evaluation') {
        $courseRes = fetchOne($pdo, 'SELECT course_id, status, title, question_count FROM ld_evaluation WHERE id = :id', [':id' => $id]);
        $courseId = (int) ($courseRes['course_id'] ?? 0);
        $questionCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_quiz_question WHERE item_type = :item_type AND reference_id = :id', [':item_type' => 'evaluation', ':id' => $id]);
        $attemptCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_quiz_session WHERE item_type = :item_type AND reference_id = :id', [':item_type' => 'evaluation', ':id' => $id]);
        $passedCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_quiz_session WHERE item_type = :item_type AND reference_id = :id AND passed = 1', [':item_type' => 'evaluation', ':id' => $id]);

        $data['summary'] = [
            ['label' => 'Course', 'value' => $courseId ? 'Course #' . $courseId : 'N/A'],
            ['label' => 'Questions', 'value' => $questionCount ?: (int) ($courseRes['question_count'] ?? 0)],
            ['label' => 'Attempts', 'value' => $attemptCount],
            ['label' => 'Passed', 'value' => $passedCount],
            ['label' => 'Status', 'value' => $courseRes['status'] ?? 'active'],
        ];
    } elseif ($type === 'Learning Path') {
        $itemCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_learning_path_item WHERE learning_path_id = :id', [':id' => $id]);
        $assignedVal = fetchOne($pdo, 'SELECT assigned_to, status FROM ld_learning_path WHERE id = :id', [':id' => $id]);
        $data['summary'] = [
            ['label' => 'Items', 'value' => $itemCount],
            ['label' => 'Assigned to', 'value' => !empty($assignedVal['assigned_to']) ? 'Learner #' . (int) $assignedVal['assigned_to'] : 'General'],
            ['label' => 'Status', 'value' => $assignedVal['status'] ?? 'active'],
        ];
    } elseif ($type === 'Program') {
        $videoCount = fetchValue($pdo, 'SELECT COUNT(*) FROM ld_video_conference WHERE program_id = :id', [':id' => $id]);
        $status = fetchOne($pdo, 'SELECT status FROM ld_program WHERE id = :id', [':id' => $id]);
        $data['summary'] = [
            ['label' => 'Video conferences', 'value' => $videoCount],
            ['label' => 'Status', 'value' => $status['status'] ?? 'active'],
        ];
    } elseif ($type === 'Video Conference') {
        $row = fetchOne($pdo, 'SELECT program_id, course_id, status, scheduled_at FROM ld_video_conference WHERE id = :id', [':id' => $id]);
        $data['summary'] = [
            ['label' => 'Program', 'value' => !empty($row['program_id']) ? 'Program #' . (int) $row['program_id'] : 'N/A'],
            ['label' => 'Course', 'value' => !empty($row['course_id']) ? 'Course #' . (int) $row['course_id'] : 'N/A'],
            ['label' => 'Status', 'value' => $row['status'] ?? 'scheduled'],
            ['label' => 'Schedule', 'value' => $row['scheduled_at'] ?? 'Not set'],
        ];
    }

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
