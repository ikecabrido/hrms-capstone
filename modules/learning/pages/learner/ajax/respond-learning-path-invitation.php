<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once dirname(__DIR__, 3) . '/classes/CSRF.php';
require_once dirname(__DIR__, 3) . '/classes/enrollment.php';
require_once dirname(__DIR__, 5) . '/database/db.php';
CSRF::requireValid();

$learnerId = (int) ($_SESSION['employee_id'] ?? 0);
$input = json_decode(file_get_contents('php://input'), true);
$invitationId = (int) ($input['invitation_id'] ?? 0);
$action = (string) ($input['action'] ?? '');

if ($learnerId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sign in required.']);
    exit;
}

if ($invitationId <= 0 || !in_array($action, ['accept', 'decline'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Choose a valid path invitation action.']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();
    $pdo->beginTransaction();

    $invitationStmt = $pdo->prepare("SELECT pi.id, pi.learning_path_id, pi.invited_by
                                     FROM ld_learning_path_invitation pi
                                     JOIN ld_learning_path lp ON lp.id = pi.learning_path_id
                                     WHERE pi.id = :invitation_id
                                       AND pi.employee_id = :employee_id
                                       AND pi.status = 'invited'
                                       AND lp.status = 'active'
                                     LIMIT 1 FOR UPDATE");
    $invitationStmt->execute([
        ':invitation_id' => $invitationId,
        ':employee_id' => $learnerId,
    ]);
    $invitation = $invitationStmt->fetch(PDO::FETCH_ASSOC);

    if (!$invitation) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Path invitation not found or already handled.']);
        exit;
    }

    $newStatus = $action === 'accept' ? 'enrolled' : 'declined';
    $update = $pdo->prepare('UPDATE ld_learning_path_invitation SET status = :status, responded_at = NOW() WHERE id = :id');
    $update->execute([':status' => $newStatus, ':id' => $invitationId]);

    $courseInvitations = 0;
    if ($action === 'accept') {
        $courseStmt = $pdo->prepare("SELECT reference_id
                                     FROM ld_learning_path_item
                                     WHERE learning_path_id = :path_id
                                       AND item_type = 'course'
                                       AND status = 'active'
                                     ORDER BY order_index ASC");
        $courseStmt->execute([':path_id' => (int) $invitation['learning_path_id']]);
        $courseIds = $courseStmt->fetchAll(PDO::FETCH_COLUMN);
        $enrollment = new Enrollment($pdo);

        foreach ($courseIds as $courseId) {
            $result = $enrollment->invite(
                $learnerId,
                (int) $courseId,
                (int) ($invitation['invited_by'] ?? 0)
            );
            if (!empty($result['success'])) {
                $courseInvitations++;
            }
        }
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'status' => $newStatus,
        'course_invitations' => $courseInvitations,
        'message' => $action === 'accept'
            ? 'Learning path accepted. Course invitations are available in Study.'
            : 'Learning path invitation declined.',
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[learning] Path invitation response failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not update the path invitation.']);
}