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

    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("
        SELECT AVG(g.final_score) AS avg_score,
               MIN(g.final_score) AS min_score,
               MAX(g.final_score) AS max_score,
               COUNT(g.id) AS total_graded
        FROM ld_grade g
        WHERE g.learner_id = :lid
    ");
    $stmt->execute([':lid' => $learnerId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'average_score' => $row['avg_score'] !== null ? round((float) $row['avg_score'], 1) : 0,
        'min_score' => $row['min_score'] !== null ? round((float) $row['min_score'], 1) : 0,
        'max_score' => $row['max_score'] !== null ? round((float) $row['max_score'], 1) : 0,
        'total_graded' => (int) $row['total_graded'],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load average score.', 'error' => $e->getMessage()]);
}
