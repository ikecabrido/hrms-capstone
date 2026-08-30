<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once dirname(__DIR__, 7) . '/database/db.php';

$learnerId = (int) $_SESSION['employee_id'];
$courseId = (int) ($_GET['course_id'] ?? 0);

if ($courseId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'course_id is required']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();

    // Verify enrollment
    $stmt = $pdo->prepare("SELECT id FROM ld_enrollment WHERE learner_id = :lid AND course_id = :cid LIMIT 1");
    $stmt->execute([':lid' => $learnerId, ':cid' => $courseId]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$enrollment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Not enrolled']);
        exit;
    }

    // Find last accessed incomplete item
    $stmt2 = $pdo->prepare("
        SELECT p.item_type, p.reference_id, p.status, p.completed_at,
               CASE p.item_type
                   WHEN 'lesson' THEN (SELECT l.title FROM ld_lesson l WHERE l.id = p.reference_id)
                   WHEN 'quiz' THEN (SELECT q.title FROM ld_quiz q WHERE q.id = p.reference_id)
                   WHEN 'module' THEN (SELECT m.title FROM ld_module m WHERE m.id = p.reference_id)
                   WHEN 'evaluation' THEN (SELECT e.title FROM ld_evaluation e WHERE e.id = p.reference_id)
               END AS item_title
        FROM ld_progress p
        WHERE p.enrollment_id = :eid AND p.status != 'completed'
        ORDER BY p.id DESC
        LIMIT 1
    ");
    $stmt2->execute([':eid' => $enrollment['id']]);
    $resume = $stmt2->fetch(PDO::FETCH_ASSOC);

    if (!$resume) {
        // Check if all complete
        $stmt3 = $pdo->prepare("SELECT COUNT(*) FROM ld_progress WHERE enrollment_id = :eid");
        $stmt3->execute([':eid' => $enrollment['id']]);
        $totalItems = (int) $stmt3->fetchColumn();

        $stmt4 = $pdo->prepare("SELECT COUNT(*) FROM ld_progress WHERE enrollment_id = :eid AND status = 'completed'");
        $stmt4->execute([':eid' => $enrollment['id']]);
        $completedItems = (int) $stmt4->fetchColumn();

        if ($totalItems > 0 && $completedItems >= $totalItems) {
            echo json_encode(['success' => true, 'resume' => null, 'completed' => true]);
        } else {
            echo json_encode(['success' => true, 'resume' => null, 'completed' => false]);
        }
        exit;
    }

    echo json_encode(['success' => true, 'resume' => $resume, 'completed' => false]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
