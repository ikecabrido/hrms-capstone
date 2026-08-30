<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/report.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $itemType = trim((string) ($_POST['item_type'] ?? ''));
    $referenceId = (int) ($_POST['reference_id'] ?? 0);
    $reason = trim((string) ($_POST['reason'] ?? ''));

    $database = new Database();
    $pdo = $database->getConnection();
    $report = new Report($pdo);

    $result = $report->create([
        'learner_id' => $learnerId,
        'item_type' => $itemType,
        'reference_id' => $referenceId,
        'reason' => $reason,
    ]);

    // If a comment was flagged, permanently mark it as having been reported —
    // this flag never resets, even if later reviewed and found acceptable.
    if (!empty($result['success']) && $itemType === 'comment') {
        require_once dirname(__FILE__, 6) . '/classes/comment.php';
        $comment = new Comment($pdo);
        $comment->flagAsReported($referenceId);
    }

    if (!empty($result['success'])) {
        echo json_encode($result);
        exit;
    }

    http_response_code(422);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit report.',
        'error' => $e->getMessage(),
    ]);
}
exit;
