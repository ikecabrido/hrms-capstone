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

    $evaluationId = isset($_GET['evaluation_id']) ? (int) $_GET['evaluation_id'] : 0;

    $database = new Database();
    $pdo = $database->getConnection();

    if ($evaluationId > 0) {
        $stmt = $pdo->prepare("
            SELECT ef.id, ef.rating, ef.comment, ef.created_at,
                   e.title AS evaluation_title,
                   c.title AS course_title
            FROM ld_evaluation_feedback ef
            JOIN ld_evaluation e ON e.id = ef.evaluation_id
            JOIN ld_course c ON c.id = e.course_id
            WHERE ef.evaluation_id = :eid AND ef.learner_id = :lid
            ORDER BY ef.created_at DESC
        ");
        $stmt->execute([':eid' => $evaluationId, ':lid' => $learnerId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT ef.id, ef.rating, ef.comment, ef.created_at,
                   e.title AS evaluation_title,
                   c.title AS course_title
            FROM ld_evaluation_feedback ef
            JOIN ld_evaluation e ON e.id = ef.evaluation_id
            JOIN ld_course c ON c.id = e.course_id
            WHERE ef.learner_id = :lid
            ORDER BY ef.created_at DESC
        ");
        $stmt->execute([':lid' => $learnerId]);
    }

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items' => array_map(function ($item) {
            return [
                'id' => (int) $item['id'],
                'rating' => $item['rating'] !== null ? (int) $item['rating'] : null,
                'comment' => $item['comment'],
                'evaluation_title' => $item['evaluation_title'],
                'course_title' => $item['course_title'],
                'created_at' => $item['created_at'],
            ];
        }, $items),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load evaluation feedback.', 'error' => $e->getMessage()]);
}
