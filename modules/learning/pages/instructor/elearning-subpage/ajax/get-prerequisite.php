<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once dirname(__DIR__, 6) . '/database/db.php';

$courseId = (int) ($_GET['course_id'] ?? 0);
if ($courseId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'course_id is required']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare("
        SELECT p.id, p.required_course_id, p.required_skill_id,
               c.title AS course_title, s.name AS skill_name
        FROM ld_prerequisite p
        LEFT JOIN ld_course c ON c.id = p.required_course_id
        LEFT JOIN ld_skill s ON s.id = p.required_skill_id
        WHERE p.course_id = :cid
        ORDER BY p.id ASC
    ");
    $stmt->execute([':cid' => $courseId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'items' => $items]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
