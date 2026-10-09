<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

require_once dirname(__FILE__, 4) . '/classes/learningpath.php';
require_once dirname(__FILE__, 4) . '/classes/LearningPathAccess.php';
require_once dirname(__FILE__, 6) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $pdo->beginTransaction();
    $learningPath = new LearningPath($pdo);

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['id'])) {
        $pdo->rollBack();
        http_response_code(400);
        die(json_encode(['error' => 'Invalid request data']));
    }

    if (!LearningPathAccess::canManage($pdo, (int) $data['id'], (int) ($_SESSION['employee_id'] ?? 0))) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You cannot manage this learning path.']);
        exit;
    }

    $result = $learningPath->update($data);

    if (!empty($result['success'])) {
        if (isset($data['skill_ids']) && !is_array($data['skill_ids'])) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid skill list.']);
            exit;
        }
        if (isset($data['skill_ids'])) {
            $pdo->prepare('DELETE FROM ld_learning_path_skill WHERE learning_path_id = :lpid')->execute([':lpid' => (int) $data['id']]);
            $skillStmt = $pdo->prepare('INSERT INTO ld_learning_path_skill (learning_path_id, skill_id) VALUES (:lpid, :sid)');
            foreach (array_unique(array_filter(array_map('intval', $data['skill_ids']))) as $skillId) {
                $skillStmt->execute([':lpid' => (int) $data['id'], ':sid' => $skillId]);
            }
        }
        $inviteeIds = $data['invitee_ids'] ?? [];
        if (!is_array($inviteeIds)) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid invitee list.']);
            exit;
        }
        $result['invited_count'] = $learningPath->inviteLearners((int) $data['id'], $inviteeIds, (int) ($_SESSION['employee_id'] ?? 0));
        $pdo->commit();
        http_response_code(200);
        echo json_encode($result);
        exit;
    }

    $pdo->rollBack();
    $statusCode = 401;
    if (strpos($result['message'] ?? '', 'required') !== false) {
        $statusCode = 400;
    }

    http_response_code($statusCode);
    echo json_encode($result);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

