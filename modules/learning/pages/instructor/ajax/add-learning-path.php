<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 4) . '/classes/learningpath.php';
require_once dirname(__FILE__, 6) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $pdo->beginTransaction();
    $learningPath = new LearningPath($pdo);

    $instructorId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;
    $learningRole = strtolower((string) ($_SESSION['learning_role'] ?? $_SESSION['role'] ?? $_SESSION['user_role'] ?? ''));
    $result = $learningPath->create([
        'instructor_id' => $instructorId,
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'status' => $_POST['status'] ?? 'active',
        'is_public' => $_POST['is_public'] ?? 1,
        'learning_role' => $learningRole,
        'is_admin' => !empty($_SESSION['is_admin']) || !empty($_SESSION['admin_access']),
    ], $instructorId);

    if (!empty($result['success'])) {
        $lpId = $result['id'] ?? 0;
        $inviteeIds = $_POST['invitee_ids'] ?? [];
        if (!is_array($inviteeIds)) {
            throw new InvalidArgumentException('Invalid invitee list.');
        }
        $result['invited_count'] = $learningPath->inviteLearners($lpId, $inviteeIds, $instructorId);

        $skillIds = array_filter(array_map('intval', $_POST['skill_ids'] ?? []));
        if ($lpId > 0 && !empty($skillIds)) {
            $skillStmt = $pdo->prepare('INSERT INTO ld_learning_path_skill (learning_path_id, skill_id) VALUES (:lpid, :sid)');
            foreach ($skillIds as $sid) {
                $skillStmt->execute([':lpid' => $lpId, ':sid' => $sid]);
            }
        }
        $pdo->commit();
        echo json_encode($result);
        exit;
    }

    $pdo->rollBack();

    $statusCode = 401;
    if (strpos($result['message'] ?? '', 'required') !== false) {
        $statusCode = 422;
    }

    http_response_code($statusCode);
    echo json_encode($result);
    exit;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create learning path.',
        'error' => $e->getMessage(),
    ]);
    exit;
}
