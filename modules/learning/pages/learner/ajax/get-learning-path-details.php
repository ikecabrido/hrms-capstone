<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/classes/LearningPathAccess.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$learnerId = (int) ($_SESSION['employee_id'] ?? 0);
$pathId = (int) ($_GET['learning_path_id'] ?? 0);

if ($learnerId <= 0 || $pathId <= 0) {
    http_response_code($learnerId > 0 ? 422 : 401);
    echo json_encode(['success' => false, 'message' => 'A valid learner and learning path are required.']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();
    if (!LearningPathAccess::canView($pdo, $pathId, $learnerId)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Learning path not found or unavailable.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT lpi.id, lpi.item_type, lpi.reference_id, lpi.order_index,
                                  COALESCE(c.title, m.title, l.title, q.title, ev.title, p.title, vc.title, 'Untitled') AS title,
                                  COALESCE(c.id, m.course_id, lm.course_id, qm.course_id, ev.course_id) AS course_id,
                                  e.status AS enrollment_status, progress.status AS progress_status
                           FROM ld_learning_path_item lpi
                           LEFT JOIN ld_course c ON lpi.item_type = 'course' AND c.id = lpi.reference_id
                           LEFT JOIN ld_module m ON lpi.item_type = 'module' AND m.id = lpi.reference_id
                           LEFT JOIN ld_lesson l ON lpi.item_type = 'lesson' AND l.id = lpi.reference_id
                           LEFT JOIN ld_module lm ON l.module_id = lm.id
                           LEFT JOIN ld_quiz q ON lpi.item_type = 'quiz' AND q.id = lpi.reference_id
                           LEFT JOIN ld_module qm ON q.module_id = qm.id
                           LEFT JOIN ld_evaluation ev ON lpi.item_type = 'evaluation' AND ev.id = lpi.reference_id
                           LEFT JOIN ld_program p ON lpi.item_type = 'program' AND p.id = lpi.reference_id
                           LEFT JOIN ld_video_conference vc ON lpi.item_type = 'video-conference' AND vc.id = lpi.reference_id
                           LEFT JOIN ld_enrollment e ON e.learner_id = :learner_id AND e.course_id = COALESCE(c.id, m.course_id, lm.course_id, qm.course_id, ev.course_id)
                           LEFT JOIN ld_progress progress ON progress.enrollment_id = e.id
                             AND progress.item_type = lpi.item_type AND progress.reference_id = lpi.reference_id
                           WHERE lpi.learning_path_id = :path_id AND lpi.status = 'active'
                           ORDER BY lpi.order_index ASC, lpi.id ASC");
    $stmt->execute([':learner_id' => $learnerId, ':path_id' => $pathId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = ['total_steps' => count($items), 'completed' => 0, 'in_progress' => 0, 'not_started' => 0];
    foreach ($items as &$item) {
        if ($item['item_type'] === 'course') {
            $status = ($item['enrollment_status'] ?? '') === 'completed'
                ? 'completed'
                : (in_array($item['enrollment_status'] ?? '', ['enrolled', 'in_progress'], true) ? 'in_progress' : 'not_started');
        } elseif (($item['enrollment_status'] ?? '') === 'completed') {
            $status = 'completed';
        } else {
            $status = in_array($item['progress_status'] ?? '', ['completed', 'in_progress'], true)
                ? $item['progress_status']
                : 'not_started';
        }
        $item['study_status'] = $status;
        $item['course_id'] = $item['course_id'] !== null ? (int) $item['course_id'] : null;
        $item['reference_id'] = (int) $item['reference_id'];
        $summary[$status]++;
    }
    unset($item);

    $summary['progress_percent'] = $summary['total_steps'] > 0
        ? (int) round($summary['completed'] * 100 / $summary['total_steps'])
        : 0;

    echo json_encode(['success' => true, 'items' => $items, 'summary' => $summary]);
} catch (Throwable $error) {
    error_log('[learning] Catalog learning-path details failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load learning-path details.']);
}