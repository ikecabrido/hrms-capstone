<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $quizId = isset($_GET['quiz_id']) ? (int) $_GET['quiz_id'] : 0;
    $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

    $database = new Database();
    $pdo = $database->getConnection();

    $sql = "SELECT
                qa.id,
                qa.quiz_id,
                qa.quiz_session_id,
                qa.score,
                qa.total_items,
                qa.passed,
                qa.attempted_at,
                q.title AS quiz_title,
                q.passing_score,
                m.course_id,
                c.title AS course_title
            FROM ld_quiz_attempt qa
            INNER JOIN ld_quiz q ON q.id = qa.quiz_id
            INNER JOIN ld_module m ON m.id = q.module_id
            INNER JOIN ld_course c ON c.id = m.course_id
            WHERE qa.learner_id = :learner_id";

    $params = [':learner_id' => $learnerId];

    if ($quizId > 0) {
        $sql .= ' AND qa.quiz_id = :quiz_id';
        $params[':quiz_id'] = $quizId;
    }

    if ($courseId > 0) {
        $sql .= ' AND m.course_id = :course_id';
        $params[':course_id'] = $courseId;
    }

    $sql .= ' ORDER BY qa.attempted_at DESC, qa.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = array_map(function (array $row): array {
        return [
            'id' => (int) $row['id'],
            'quiz_id' => (int) $row['quiz_id'],
            'quiz_session_id' => $row['quiz_session_id'] !== null ? (int) $row['quiz_session_id'] : null,
            'quiz_title' => $row['quiz_title'],
            'course_id' => (int) $row['course_id'],
            'course_title' => $row['course_title'],
            'score' => (float) $row['score'],
            'total_items' => (int) $row['total_items'],
            'passing_score' => $row['passing_score'] !== null ? (float) $row['passing_score'] : null,
            'passed' => (bool) $row['passed'],
            'attempted_at' => $row['attempted_at'],
        ];
    }, $rows);

    echo json_encode([
        'success' => true,
        'items' => $items,
        'count' => count($items),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load quiz attempts.',
    ]);
}
