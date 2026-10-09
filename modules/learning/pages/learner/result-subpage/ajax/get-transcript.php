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
        SELECT e.course_id, e.status AS enrollment_status, e.enrolled_at, e.completed_at,
               c.title AS course_title, c.category,
               g.final_score AS score, g.status AS grade_status, g.issued_at AS grade_date,
               CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        LEFT JOIN ld_grade g ON g.learner_id = e.learner_id AND g.course_id = e.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
        WHERE e.learner_id = :lid
        ORDER BY e.enrolled_at DESC
    ");
    $stmt->execute([':lid' => $learnerId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $completed = count(array_filter($rows, fn($r) => $r['enrollment_status'] === 'completed'));
    $totalScore = 0;
    $graded = 0;
    foreach ($rows as $r) {
        if ($r['score'] !== null) { $totalScore += (float) $r['score']; $graded++; }
    }

    echo json_encode([
        'success' => true,
        'items' => array_map(function ($r) {
            return [
                'course_id' => (int) $r['course_id'],
                'course_title' => $r['course_title'],
                'category' => $r['category'],
                'instructor' => $r['instructor_name'],
                'enrollment_status' => $r['enrollment_status'],
                'score' => $r['score'] !== null ? round((float) $r['score'], 1) : null,
                'enrolled_at' => $r['enrolled_at'],
                'completed_at' => $r['completed_at'],
            ];
        }, $rows),
        'summary' => [
            'total_courses' => count($rows),
            'completed' => $completed,
            'average_score' => $graded > 0 ? round($totalScore / $graded, 1) : 0,
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load transcript.', 'error' => $e->getMessage()]);
}
