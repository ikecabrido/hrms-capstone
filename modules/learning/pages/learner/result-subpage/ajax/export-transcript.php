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

    $learnerStmt = $pdo->prepare("SELECT employee_id, first_name, last_name FROM em_employees WHERE employee_id = :id LIMIT 1");
    $learnerStmt->execute([':id' => $learnerId]);
    $learner = $learnerStmt->fetch(PDO::FETCH_ASSOC);

    $enrollments = $pdo->prepare("
        SELECT e.course_id, e.status, e.enrolled_at, e.completed_at,
               c.title AS course_title, c.category,
               g.final_score AS score
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        LEFT JOIN ld_grade g ON g.learner_id = e.learner_id AND g.course_id = e.course_id
        WHERE e.learner_id = :lid
        ORDER BY e.enrolled_at DESC
    ");
    $enrollments->execute([':lid' => $learnerId]);
    $rows = $enrollments->fetchAll(PDO::FETCH_ASSOC);

    $lines = [];
    $lines[] = 'TRANSCRIPT';
    $lines[] = 'Learner: ' . ($learner ? $learner['first_name'] . ' ' . $learner['last_name'] : 'Unknown');
    $lines[] = 'ID: ' . $learnerId;
    $lines[] = 'Date Generated: ' . date('F j, Y');
    $lines[] = '';
    $lines[] = str_pad('Course', 40) . str_pad('Category', 20) . str_pad('Status', 15) . str_pad('Score', 10) . 'Date';
    $lines[] = str_repeat('-', 100);

    foreach ($rows as $r) {
        $lines[] = str_pad(mb_strimwidth($r['course_title'], 0, 38, '..'), 40)
            . str_pad($r['category'] ?? '-', 20)
            . str_pad($r['status'], 15)
            . str_pad($r['score'] !== null ? round($r['score'], 1) . '%' : '-', 10)
            . ($r['completed_at'] ?? $r['enrolled_at']);
    }

    $lines[] = '';
    $lines[] = 'Total Courses: ' . count($rows);
    $completed = count(array_filter($rows, fn($r) => $r['status'] === 'completed'));
    $lines[] = 'Completed: ' . $completed;

    echo json_encode([
        'success' => true,
        'transcript' => implode("\n", $lines),
        'total' => count($rows),
        'completed' => $completed,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to export transcript.', 'error' => $e->getMessage()]);
}
