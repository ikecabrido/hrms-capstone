<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 7) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $entity = trim((string) ($_POST['entity'] ?? ''));
    $parentId = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : 0;
    $sourceParentId = isset($_POST['source_parent_id']) ? (int) $_POST['source_parent_id'] : $parentId;
    $movedId = isset($_POST['moved_id']) ? (int) $_POST['moved_id'] : 0;
    $ids = $_POST['ids'] ?? [];
    $ids = is_array($ids) ? array_map('intval', $ids) : (strlen((string) $ids) > 0 ? array_map('intval', explode(',', (string) $ids)) : []);
    $sourceIds = $_POST['source_ids'] ?? [];
    $sourceIds = is_array($sourceIds) ? array_map('intval', $sourceIds) : (strlen((string) $sourceIds) > 0 ? array_map('intval', explode(',', (string) $sourceIds)) : []);

    if ($entity === '' || $parentId <= 0 || count($ids) === 0 || $movedId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid reorder payload.']);
        exit;
    }

    if ($entity === 'module') {
        $table = 'ld_module';
        $parentField = 'course_id';
    } elseif ($entity === 'lesson') {
        $table = 'ld_lesson';
        $parentField = 'module_id';
    } elseif ($entity === 'quiz') {
        $table = 'ld_quiz';
        $parentField = 'module_id';
    } else {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Unsupported entity type.']);
        exit;
    }

    if ($sourceParentId !== $parentId) {
        $updateMovedStmt = $pdo->prepare("UPDATE {$table} SET {$parentField} = :new_parent_id WHERE id = :id");
        $updateMovedStmt->execute([
            ':new_parent_id' => $parentId,
            ':id' => $movedId,
        ]);
    }

    foreach ($ids as $index => $id) {
        $stmt = $pdo->prepare("UPDATE {$table} SET {$parentField} = :parent_id, order_index = :order_index WHERE id = :id");
        $stmt->execute([
            ':parent_id' => $parentId,
            ':order_index' => $index + 1,
            ':id' => $id,
        ]);
    }

    if ($sourceParentId !== $parentId && count($sourceIds) > 0) {
        foreach ($sourceIds as $index => $id) {
            $stmt = $pdo->prepare("UPDATE {$table} SET order_index = :order_index WHERE id = :id AND {$parentField} = :parent_id");
            $stmt->execute([
                ':order_index' => $index + 1,
                ':id' => $id,
                ':parent_id' => $sourceParentId,
            ]);
        }
    }

    echo json_encode(['success' => true, 'message' => ucfirst($entity) . ' order updated successfully.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update order.', 'error' => $e->getMessage()]);
}
