<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $learnerId = (int) $_SESSION['employee_id'];
    $database = new Database();
    $pdo = $database->getConnection();

    // Get all enrollments with progress
    $stmt = $pdo->prepare("
        SELECT 
            e.id AS enrollment_id,
            c.id AS course_id,
            c.title AS course_title,
            (SELECT COUNT(*) FROM ld_module WHERE course_id = c.id) as total_modules,
            (SELECT COUNT(DISTINCT p.reference_id) FROM ld_progress p 
             WHERE p.enrollment_id = e.id AND p.item_type = 'module' AND p.status = 'completed') as modules_completed
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        WHERE e.learner_id = :learner_id AND e.status IN ('enrolled', 'in_progress', 'completed')
        ORDER BY c.title
    ");
    $stmt->execute([':learner_id' => $learnerId]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $progress = [];
    foreach ($enrollments as $enrollment) {
        $totalModules = (int) $enrollment['total_modules'];
        $modulesCompleted = (int) $enrollment['modules_completed'];
        $completionPercent = $totalModules > 0 ? round(($modulesCompleted / $totalModules) * 100, 0) : 0;

        $progress[] = [
            'enrollment_id' => $enrollment['enrollment_id'],
            'course_id' => $enrollment['course_id'],
            'course_title' => $enrollment['course_title'],
            'total_modules' => $totalModules,
            'modules_completed' => $modulesCompleted,
            'completion_percent' => $completionPercent,
        ];
    }

    http_response_code(200);
    echo json_encode(['progress' => $progress]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
