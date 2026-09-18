<?php

require_once dirname(__DIR__, 5) . '/database/db.php';
require_once dirname(__DIR__, 3) . '/classes/learningpath.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $learningPath = new LearningPath($pdo);

    // Get all paths with instructor and assignee info
    $stmt = $pdo->query("SELECT id, title, description, status, created_at, updated_at, type, is_public, assigned_to, instructor_id FROM ld_learning_path ORDER BY id ASC");
    $paths = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch employee names for assigned_to and instructor_id
    $employeeIds = array_unique(array_filter(
        array_merge(
            array_map(function($p) { return (int)($p['assigned_to'] ?? 0); }, $paths),
            array_map(function($p) { return (int)($p['instructor_id'] ?? 0); }, $paths)
        )
    ));
    $employeeNames = [];
    if (!empty($employeeIds)) {
        $empStmt = $pdo->prepare("SELECT employee_id, CONCAT(first_name, ' ', last_name) AS full_name FROM em_employees WHERE employee_id IN (" . implode(',', $employeeIds) . ")");
        $empStmt->execute();
        $employeeNames = $empStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // Count items per path
    $itemCounts = [];
    foreach ($paths as $path) {
        $itemCounts[$path['id']] = $pdo->query("SELECT COUNT(*) FROM ld_learning_path_item WHERE learning_path_id = " . (int)$path['id'])->fetchColumn();
    }

    echo json_encode([
        'success' => true,
        'data' => array_values(array_map(function($p) use ($employeeNames, $itemCounts) {
            return [
                'id' => (int)$p['id'],
                'title' => $p['title'],
                'description' => $p['description'] ?? '',
                'status' => $p['status'],
                'type' => $p['type'] ?? 'standard',
                'is_public' => !empty($p['is_public']),
                'assigned_to' => (int)($p['assigned_to'] ?? 0),
                'instructor_id' => (int)($p['instructor_id'] ?? 0),
                'item_count' => (int)($itemCounts[$p['id']] ?? 0),
                'created_at' => $p['created_at'],
                'assigned_to_name' => isset($employeeNames[$p['assigned_to']]) ? $employeeNames[$p['assigned_to']] : '',
                'instructor_name' => isset($employeeNames[$p['instructor_id']]) ? $employeeNames[$p['instructor_id']] : '',
            ];
        }, $paths))
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load learning paths.', 'error' => $e->getMessage()]);
}
