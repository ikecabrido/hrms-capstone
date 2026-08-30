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
        SELECT s.id, s.name, s.description,
               COUNT(DISTINCT cs.course_id) AS total_courses,
               SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completed_courses
        FROM ld_skill s
        INNER JOIN ld_course_skill cs ON cs.skill_id = s.id
        INNER JOIN ld_enrollment e ON e.course_id = cs.course_id AND e.learner_id = :lid
        GROUP BY s.id, s.name, s.description
        ORDER BY completed_courses DESC, total_courses DESC
    ");
    $stmt->execute([':lid' => $learnerId]);
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items' => array_map(function ($skill) {
            return [
                'id' => (int) $skill['id'],
                'name' => $skill['name'],
                'description' => $skill['description'],
                'total_courses' => (int) $skill['total_courses'],
                'completed_courses' => (int) $skill['completed_courses'],
                'proficiency' => $skill['total_courses'] > 0
                    ? round(($skill['completed_courses'] / $skill['total_courses']) * 100)
                    : 0,
            ];
        }, $skills),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load skill summary.', 'error' => $e->getMessage()]);
}
