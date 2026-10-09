<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 3) . '/classes/CSRF.php';
require_once dirname(__FILE__, 3) . '/classes/LearningPathAccess.php';
require_once dirname(__FILE__, 3) . '/classes/enrollment.php';
require_once dirname(__FILE__, 5) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

CSRF::requireValid();
$learnerId = (int) ($_SESSION['employee_id'] ?? 0);
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$pathId = (int) ($input['learning_path_id'] ?? 0);

if ($learnerId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sign in required.']);
    exit;
}
if ($pathId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Learning path is required.']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();
    $pdo->beginTransaction();
    $pathStmt = $pdo->prepare('SELECT id, status, is_public, assigned_to FROM ld_learning_path WHERE id = :id LIMIT 1');
    $pathStmt->execute([':id' => $pathId]);
    $path = $pathStmt->fetch(PDO::FETCH_ASSOC);

    if (!$path || $path['status'] !== 'active') {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Learning path is unavailable.']);
        exit;
    }

    $invitationStmt = $pdo->prepare('SELECT id, status FROM ld_learning_path_invitation WHERE learning_path_id = :path_id AND employee_id = :employee_id LIMIT 1');
    $invitationStmt->execute([':path_id' => $pathId, ':employee_id' => $learnerId]);
    $existingInvitation = $invitationStmt->fetch(PDO::FETCH_ASSOC);

    if (($existingInvitation['status'] ?? '') === 'enrolled' || (int) ($path['assigned_to'] ?? 0) === $learnerId) {
        $pdo->commit();
        echo json_encode(['success' => true, 'already_enrolled' => true, 'message' => 'This learning path is already in My Learning Path.']);
        exit;
    }

    if (($existingInvitation['status'] ?? '') === 'invited') {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Respond to this learning path invitation in Study first.']);
        exit;
    }

    if (empty($path['is_public']) && !LearningPathAccess::canView($pdo, $pathId, $learnerId)) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'This learning path is invite-only.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO ld_learning_path_invitation
        (learning_path_id, employee_id, status, invited_by, responded_at)
        VALUES (:path_id, :employee_id, 'enrolled', :invited_by, NOW())
        ON DUPLICATE KEY UPDATE status = 'enrolled', invited_by = VALUES(invited_by), responded_at = NOW()");
    $stmt->execute([
        ':path_id' => $pathId,
        ':employee_id' => $learnerId,
        ':invited_by' => $learnerId,
    ]);

    $courseStmt = $pdo->prepare("SELECT reference_id FROM ld_learning_path_item
                                 WHERE learning_path_id = :path_id AND item_type = 'course' AND status = 'active'");
    $courseStmt->execute([':path_id' => $pathId]);
    $enrollment = new Enrollment($pdo);
    foreach ($courseStmt->fetchAll(PDO::FETCH_COLUMN) as $courseId) {
        $existingCourse = $enrollment->getByLearnerAndCourse($learnerId, (int) $courseId);
        if (!$existingCourse) {
            $enrollment->enroll($learnerId, (int) $courseId);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'already_enrolled' => false, 'message' => 'Learning path added to My Learning Path.']);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Learning path enrollment failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not add this learning path.']);
}